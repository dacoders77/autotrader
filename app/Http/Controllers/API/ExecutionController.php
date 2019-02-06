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
    private $exchange;
    private $placeOrderResponse;
    private $symbolInXBT;
    private $symbolQuote;

    public function __construct()
    {
        $this->exchange = new bitmex();
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
/*
Delete this code
        // Do it once. Only for a new signal
        if ($request['status'] == "new"){
            // $this->fillExecutionsTable($request); // Moved to Signal controller
            //$this->getClientsFunds($request, $this->exchange);
            //$this->fillVolume($request, $this->exchange);
        }

            $this->exchange->apiKey = Client::where('id', $execution->client_id)->value('api');
            $this->exchange->secret = Client::where('id', $execution->client_id)->value('api_secret');

            if($request['status'] == "new"){
                if ($request['direction'] == "long"){
                    $this->openPosition($this->exchange, $execution, "long");
                }
                else{
                    $this->openPosition($this->exchange, $execution, "short");
                }
            }
            else
            {
                if ($request['direction'] == "long"){
                    $this->openPosition($this->exchange, $execution, "short");
                }
                else{
                    $this->openPosition($this->exchange, $execution, "long");
                }
            }
*/
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
            //$exchange->apiKey = 123;
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
     * Open positions.
     * Performed for each client record in executions table.
     * @param bitmex $exchange
     * @param $direction
     * @param $orderVolume
     */
    /*
    private function openPosition(bitmex $exchange, $execution, $direction){
        // Set leverage
        try{
            //$setLeverageResponse = $exchange->privatePostPositionLeverage(array('symbol' => Symbol::where('execution_name', $execution->symbol)->value('leverage_name'), 'leverage' => $execution->leverage));
            $setLeverageResponse = $exchange->privatePostPositionLeverage(array('symbol' => 'ETHUSD_ddd', 'leverage' => $execution->leverage));
            //LogToFile::add(__FILE__ . __LINE__, "SET LEVERAGE RESPONSE: " . Symbol::where('execution_name', $execution->symbol)->value('leverage_name'));

        }
        catch (\Exception $e){
            throw (New Exception('Leverage set error. ' . $e->getMessage()));
        }

        if ($direction == 'long'){
            try{
                $this->placeOrderResponse = $exchange->createMarketBuyOrder($execution->symbol, $execution->client_volume * $execution->leverage, []);
            }
            catch (\Exception $e){
                $this->placeOrderResponse = $e->getMessage();
            }
        }
        else{
            try{
                $this->placeOrderResponse = $exchange->createMarketSellOrder($execution->symbol, $execution->client_volume * $execution->leverage, []);
            }
            catch (\Exception $e)
            {
                $this->placeOrderResponse = $e->getMessage();
            }
        }

        // CHECK WHETHER SUCCESS OR NOT!
        if (gettype($this->placeOrderResponse) == 'array'){
            // Success
            //LogToFile::add(__FILE__ . __LINE__, "ORDER PLACED SUCCESS: " . gettype($this->placeOrderResponse));
        }
        else{
            // Error
            //LogToFile::add(__FILE__ . __LINE__, "ORDER ERROR: " . gettype($this->placeOrderResponse));
        }

        // CATCH BITMEX ERROR INSUFFICIENT FUNDS
        // IS SO - STOP EXECUTION WITH ERROR AND THROW IT TO THE BROWSER


        $updateSignalStatuses = ["status" => "proceeded"];
        $updateExecutionOpenStatuses = [
            'status' => 'open_placed',
            'open_status' => (gettype($this->placeOrderResponse) == 'array' ? 'ok' : 'error'),
            'open_response' => json_encode($this->placeOrderResponse),
            'open_price' => (gettype($this->placeOrderResponse) == 'array' ? $this->placeOrderResponse['price'] : null),
            'leverage_response' => json_encode($setLeverageResponse),
        ];

        $updateExecutionCloseStatuses = [
            'status' => 'close_placed',
            'close_status' => (gettype($this->placeOrderResponse) == 'array' ? 'ok' : 'error'),
            'close_response' => json_encode($this->placeOrderResponse),
            'close_price' => (gettype($this->placeOrderResponse) == 'array' ? $this->placeOrderResponse['price'] : null),
        ];

        // Write statuses to DB
        // Open
        Signal::where('id', $execution->signal_id)->update($updateSignalStatuses);

        if ($execution->status == "new") {
            Execution::where('id', $execution->id)->update($updateExecutionOpenStatuses);
            Signal::where('id', $execution->signal_id)->update([
                'open_date' => (gettype($this->placeOrderResponse) == 'array' ? date("Y-m-d G:i:s", $this->placeOrderResponse['timestamp'] / 1000) : null),
                'open_price' => (gettype($this->placeOrderResponse) == 'array' ? $this->placeOrderResponse['price'] : null),
                'quote' => (gettype($this->placeOrderResponse) == 'array' ? $this->placeOrderResponse['price'] : null),
            ]);
        }

        // Close
        if ($execution->status == "open_placed"){
            Execution::where('id', $execution->id)->update($updateExecutionCloseStatuses);
            Signal::where('id', $execution->signal_id)->update([
                'status' => 'finished',
                'close_date' => (gettype($this->placeOrderResponse) == 'array' ? date("Y-m-d G:i:s", $this->placeOrderResponse['timestamp'] / 1000) : null),
                'close_price' => (gettype($this->placeOrderResponse) == 'array' ? $this->placeOrderResponse['price'] : null),
            ]);
        }
    }
    */

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
     * $request->json()->all()[1] - is an type variable. Can be: stopLoss, takeProfit0, takeProfit1, takeProfit2, takeProfit3
     *
     * @param Request $request
     * @return void
     */
    public function closeSymbol(Request $request){
        /* Action is not allowed if job and failed_job tables are not empty. Que tasks may be in progress. */
        if (!QueLock::getStatus()){
            throw (new Exception('Some jobs are in progress! Wait until them finish or truncate Job and Failed job tables.'));
        }

        $this->stopLoss($request[0]['id'], $request->json()->all()[1]); // Here we need to pass an exit param: stop loss or take profit 1,2,3 or 4
        //LogToFile::add(__FILE__, print_r($request->json()->all()[1], true));

        /* Set info column to manual_close. This will not let stop loss to fire again when the position is manually closed. */
        Signal::where('id', $request['id'])->update(['info' => 'manual_close stop loss or take profit']);
    }

    /**
     *
     *
     * @param $signalId
     * @param $exitType
     */
    public function stopLoss($signalId, $exitType){

        LogToFile::add(__FILE__, $signalId);

        foreach (Execution::where('signal_id', $signalId)
        ->where('in_place_order_status', 'ok')
         ->get() as $execution) {

            OutPlaceOrder::dispatch($this->exchange, $execution, $exitType);
            GetClientTradingBalanceOut::dispatch($this->exchange, $execution)->delay(5);
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


