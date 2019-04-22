<?php

namespace App\Http\Controllers\API;

use App\Classes\LogToFile;
use App\Classes\QueLock;
use App\Jobs\GetClientFundsCheck;
use App\Jobs\GetClientTradingBalance;
use App\Jobs\GetClientTradingBalanceOut;
use App\Jobs\InPlaceOrder;
use App\Jobs\OutPlaceOrder;
use App\Jobs\SetLeverageCheck;
use App\Jobs\SmallOrderCheck;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Signal; // Link model
use App\Client; // Link model
use App\Symbol; // Link model
use App\Job;
use App\Failed_job;
use App\Execution; // Link model
use ccxt\bitmex;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\ParameterBag;
use test\Mockery\SubclassWithFinalWakeup;

/**
 * Class SymbolController.
 * Fill execution table with signal info.
 * Fill funds.
 * Calculate and fill volume.
 * Place and execute orders.
 *
 * @package App\Http\Controllers
 */
class ExecutionController extends Controller
{
    private $orderVolume;
    public $exchange;
    private $placeOrderResponse;
    private $symbolInXBT;
    private $symbolQuote;

    public function __construct()
    {
        $this->exchange = new bitmex();
        /**
         * Live or testnet API
         * If getting "unknown index error", run: php artisan config:clear
         */
        $this->exchange->urls['api'] = $this->exchange->urls[env("BITMEX_API_PATH")];
        //$this->exchange->urls['api'] = $this->exchange->urls[$_ENV['BITMEX_API_PATH']];
    }

    /**
     * Controller is called from Signals.vue and Execution.vue.
     * Execute a symbol on multiple clients accounts.
     *
     * @param Request $request
     * @return string
     */
    public function executeSymbol(Request $request){

        /* Action is not allowed if job and failed_job tables are not empty. Que tasks may be in progress */
        if (!QueLock::getStatus()){
            throw (new Exception('Some jobs are in progress! Wait until them finish or truncate Job and Failed job tables.'));
        }
        
        /* Do for both: new and open signals */
        foreach (Execution::where('signal_id', $request['id'])
                     //->where('client_volume', '!=', null)
                     ->get() as $execution) {
            /* Checks to perform. Each check is added to que and proceeded independently. Last - place order. */
            GetClientFundsCheck::dispatch($this->exchange, $execution);
            SetLeverageCheck::dispatch($this->exchange, $execution);
            //SmallOrderCheck::dispatch($this->exchange, $execution);
            /* Place order */
            InPlaceOrder::dispatch($this->exchange, $execution);
            /* Get trading balance after order execution */
            GetClientTradingBalance::dispatch($this->exchange, $execution)->delay(5);
        }

        Signal::where('id', $request['id'])->update(['status' => 'pending']);

        return 'Return from exec controller! ' . __FILE__;
        dump('Stopped in ExecutionController. Line: ' . __LINE__);
        die(__FILE__);
        }

    /**
     * Repeat failed signal. Sometimes some clients inside an execution do not get their signals executed.
     * Allows to executa failed clients manually.
     * Called from Execution.vue by clicking the reload icon.
     *
     * @param Request $request
     */
    public function repeatSignal(Request $request){
        if (!QueLock::getStatus()){
            throw (new Exception('Some jobs are in progress! Wait until them finish or truncate Job and Failed job tables.'));
        }

        $execution = Execution::where('id', $request['id'])->get()[0];
        if ($execution->in_place_order_status == 'ok' && $execution->out_place_order_status == 'ok'){
            throw (new Exception('Selected signal does not contain any errors and will not be repeated'));
        }
        
        if($execution->in_place_order_status == 'error'){
            GetClientFundsCheck::dispatch($this->exchange, $execution);
            SetLeverageCheck::dispatch($this->exchange, $execution);
            InPlaceOrder::dispatch($this->exchange, $execution);
            GetClientTradingBalance::dispatch($this->exchange, $execution);
            Signal::where('id', $request['id'])->update(['status' => 'repeating']);
        }

        if($execution->out_place_order_status == 'error'){
            OutPlaceOrder::dispatch($this->exchange, $execution);
            GetClientTradingBalanceOut::dispatch($this->exchange, $execution);

            /* Set info column to manual_close. This will not let stop loss to fire when the position is manually closed. */
            Signal::where('id', $request['id'])->update(['info' => 'manual_close']);
        }
    }

    /**
     * Calculate and fill volume for each client (each record in the table).
     *
     * @param Request $request
     * @param bitmex $exchange
     * @return string
     */
    public function fillVolume(Request $request, bitmex $exchange){

        /* Get quote */
        try {
            $this->symbolQuote = $this->exchange->fetch_ticker($request['symbol'])['last'];
        } catch (\Exception $e) {
            throw (new Exception($e->getMessage()));
        }

        /**
         * Run through all records in executions table
         * With a specific signal id
         * And where client funds != 0. If == 0 it means that API keys did not work and we did not get the balance
         */
        foreach (Execution::where('signal_id', $request['id'])
                     ->where('client_funds', '!=', null)
                     ->get() as $execution){

            // Balance share calculation
            $balancePortionXBT = $execution->client_funds * $execution->percent / 100;

            // Contract formula

            // Formulas are set in Symbols.vue
            // Get the formula. Use symbol as the key
            $formula = Symbol::where('execution_name', $execution->symbol)->value('formula');
            if ($formula == "=1/symbolQuote(BTC)") $this->symbolInXBT = 1 / $this->symbolQuote;
            if ($formula == "=symbolQuote*multp(ETH)") $this->symbolInXBT = $this->symbolQuote * 0.000001;
            if ($formula == "=symbolQuote")$this->symbolInXBT = $this->symbolQuote;

            Execution::where('signal_id', $request['id'])
                ->where('client_id', $execution->client_id)
                ->update(['client_volume' => round($balancePortionXBT / $this->symbolInXBT),
                    'status' => 'new',
                    'info' => 'volume calculated']);
        }
    }

    /**
     * Run through job list (executions table) and get funds(free XBT balance for each client).
     * @param Request $request
     * @param bitmex $exchange
     */
    public function getClientsFunds(Request $request, bitmex $exchange){
        foreach (Execution::where('signal_id', $request['id'])->get() as $execution){
            $exchange->apiKey = Client::where('id', $execution->client_id)->value('api');
            $exchange->secret = Client::where('id', $execution->client_id)->value('api_secret');

            try{
                $response = $exchange->fetchBalance()['BTC']['free'];
                Execution::where('signal_id', $request['id'])
                    ->where('client_id', $execution->client_id)
                    ->update(['client_funds' => $response, 'open_response' => 'Got balance ok']);

                Client::where('id', $execution->client_id)
                    ->update(['funds' => $response]);
            }
            catch (\Exception $e){
                Execution::where('signal_id', $request['id'])
                    ->where('client_id', $execution->client_id)
                    ->update(['open_response' => 'Error getting client balance', 'info' => $e->getMessage()]);
                throw (new Exception('Can\'t get client funds. Client id: ' . $execution->client_id . " " . $e->getMessage()));
            }
        }
    }

    /**
     * Display a listing of the resource.
     * Called from Execution.vue
     *
     * @return \Illuminate\Http\Response
     */
    public function getExecution($id)
    {
        $signal = Signal::where('id', $id)->get();
        // 10 clients bug is here
        // return(['execution' => Execution::latest()->where('signal_id', $id)->paginate(10), 'signal' => $signal]);
        // LogToFile::add(__FILE__, json_encode(Execution::latest()->where('signal_id', $id)->get()));
        return(['execution' => Execution::latest()->where('signal_id', $id)->get(), 'signal' => $signal]);
    }

    /**
     * Stop button handler.
     * Called from Execution.vue
     * Route: execclose
     * $request->json()->all()[1] - is a type variable. Can be: stopLoss, takeProfit0, takeProfit1, takeProfit2, takeProfit3
     *
     * @param Request $request
     * @return void
     */
    public function closeSymbol(Request $request){
        /* Action is not allowed if job and failed_job tables are not empty. Que tasks may be in progress. */
        if (!QueLock::getStatus()){
            throw (new Exception('Some jobs are in progress! Wait until them finish or truncate Job and Failed job tables.'));
        }

        /* Set info column to manual_close. This will not let stop loss to fire again when the position is manually closed. */
        Signal::where('id', $request[0]['id'])->update(['info' => 'manual_close stop loss or take profit']);

        // Here we need to pass an exit param: stop loss or take profit 1,2,3 or 4
        // Extract this value from the request
        $this->stopLoss($request[0]['id'], $request->json()->all()[1]);

    }

    /**
     * Stop loss feature for open positions.
     * Used in both cases: stop loss and take profit.
     *
     * @param $signalId
     * @param $exitType
     */
    public function stopLoss($signalId, $exitType){
        // Run through all executions
        foreach (Execution::where('signal_id', $signalId)
        ->where('in_place_order_status', 'ok')
         ->get() as $execution) {
            OutPlaceOrder::dispatch($this->exchange, $execution, $exitType);
            // @todo Need to pass Balance get type
            GetClientTradingBalanceOut::dispatch($this->exchange, $execution, $exitType)->delay(5);
        }
    }

    /**
     * Empty jobs and failed_jobs tables.
     * Empty button click performed in Execution.vue
     *
     * @return void
     */
    public function clearJobTables(){
        Job::truncate();
        Failed_job::truncate();
    }

}


