<?php

namespace App\Http\Controllers\API;

use App\Classes\LogToFile;
use App\Jobs\GetClientFundsCheck;
use App\Jobs\InPlaceOrder;
use App\Jobs\OutPlaceOrder;
use App\Jobs\SetLeverageCheck;
use App\Jobs\SmallOrderCheck;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Signal; // Link model
use App\Client; // Link model
use App\Symbol; // Link model
use App\Execution; // Link model
use ccxt\bitmex;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

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
     * Execute a symbol an multiple clients accounts.
     *
     * @param Request $request
     */
    public function executeSymbol(Request $request){

        //LogToFile::createTextLogFile();


        // Do for both: new and open signals
        foreach (Execution::where('signal_id', $request['id']) //
                     //->where('client_volume', '!=', null)
                     ->get() as $execution) {

            /* Checks to perform. Each check is added to que and proceeded independently. Last - place order. */
            GetClientFundsCheck::dispatch($this->exchange, $execution);
            SetLeverageCheck::dispatch($this->exchange, $execution);
            SmallOrderCheck::dispatch($this->exchange, $execution);
            InPlaceOrder::dispatch($this->exchange, $execution);
        }

        Signal::where('id', $execution->signal_id)->update(['status' => 'pending']);


        return 'Return from exec controller! ' . __FILE__;
        die(__FILE__);

        // Add controller: execclose
        // Controller is fired
        // Foreach through all records in executions where in_place_order_status == ok
        // Place sell order
        // Change close buttons on signals.vue, execution.vue


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
        }

        //return "position closed";
        //return response('No symbol found', 412);



    /**
     * Calculate and fill volume for each client (each record in the table).
     * @param Request $request
     * @param bitmex $exchange
     * @return string
     */
    public function fillVolume(Request $request, bitmex $exchange){

        /* Get quote */
        try {
            $this->symbolQuote = $this->exchange->fetch_ticker($request['symbol'])['last'];
            //LogToFile::add(__FILE__ . __LINE__, $this->symbolQuote);
        } catch (\Exception $e) {
            //LogToFile::add(__FILE__ . __LINE__, $e->getMessage());
            throw (new Exception($e->getMessage()));
            //return $e->getMessage();
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
    private function openPosition(bitmex $exchange, $execution, $direction){
        /* Set leverage */
        try{
            //$setLeverageResponse = $exchange->privatePostPositionLeverage(array('symbol' => Symbol::where('execution_name', $execution->symbol)->value('leverage_name'), 'leverage' => $execution->leverage));
            $setLeverageResponse = $exchange->privatePostPositionLeverage(array('symbol' => 'ETHUSD_ddd', 'leverage' => $execution->leverage));
            //LogToFile::add(__FILE__ . __LINE__, "SET LEVERAGE RESPONSE: " . Symbol::where('execution_name', $execution->symbol)->value('leverage_name'));

        }
        catch (\Exception $e){
            throw (New Exception('Leverage set error. ' . $e->getMessage()));
        }

        /* Place order */
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

    /**
     * Display a listing of the resource.
     * Called from Execution.vue
     *
     * @return \Illuminate\Http\Response
     */
    public function getExecution($id)
    {
        return Execution::latest()->where('signal_id', $id)->paginate(10);
    }

    public function closeSymbol(Request $request){
        foreach (Execution::where('signal_id', $request['id']) //
        ->where('in_place_order_status', 'ok')
        ->get() as $execution) {
            OutPlaceOrder::dispatch($this->exchange, $execution);
        }

        //Signal::where('id', $execution->signal_id)->update(['status' => 'pending']);
    }
}


