<?php

namespace App\Http\Controllers;

use App\Classes\LogToFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Signal; // Link model
use App\Client; // Link model
use App\Execution; // Link model
use ccxt\bitmex;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

class SymbolController extends Controller
{
    private $orderVolume;
    private $exchange;

    public function __construct()
    {
        $this->exchange = new bitmex();
    }

    // add: signal id, client id
    public function executeSymbol(Request $request){

        LogToFile::createTextLogFile();

        // Do it onlt for new signal
        if ($request['status'] == "new"){
            $this->fillExecutionsTable($request);
            $this->getClientsFunds($request, $this->exchange);
            $this->fillVolume($request, $this->exchange);
        }

        // Do for both: new and open signals
        foreach (Execution::where('signal_id', $request['id'])
                     ->where('client_volume', '!=', null)
                     ->get() as $execution){

            $this->exchange->apiKey = Client::where('id', $execution->client_id)->value('api');
            //$exchange->apiKey = 123;
            $this->exchange->secret = Client::where('id', $execution->client_id)->value('api_secret');

            if($request['status'] == "new"){
                if ($request['direction'] == "long"){
                    $this->openPosition($this->exchange, $execution, "long");
                }
                else{
                    $this->openPosition($this->exchange, $execution, "short");
                }

                //Signal::where('id', $request->id)->update(array(
                //    'status' => 'open',
                //));
            }
            else
            {
                if ($request['direction'] == "long"){
                    $this->openPosition($this->exchange, $execution, "short");
                }
                else{
                    $this->openPosition($this->exchange, $execution, "long");
                }

                //Signal::where('id', $request->id)->update(array(
                //    'status' => 'executed',
                //));
            }



        }



        //return "position closed";

        //return response('No symbol found', 412);



    }

    /**
     * Calculate and fill volume for each client (each record in the table)
     * @param Request $request
     * @param bitmex $exchange
     * @return string
     */
    public function fillVolume(Request $request, bitmex $exchange){
        // fill volume
        try {
            $symbolQuote = $this->exchange->fetch_ticker($request['symbol'])['last'];
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        foreach (Execution::where('signal_id', $request['id'])->get() as $execution){
            $balancePortionXBT = $execution->client_funds * $execution->percent / 100;
            $symbolInXBT = $symbolQuote * $execution->multiplier;
            Execution::where('signal_id', $request['id'])
                ->where('client_funds', '!=', null)
                ->where('client_id', $execution->client_id)
                ->update(['client_volume' => round($balancePortionXBT / $symbolInXBT), 'status' => 'new', 'info' => 'Volume calculated']);
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
                //return $response;
            }
            catch (\Exception $e){
                Execution::where('signal_id', $request['id'])
                    ->where('client_id', $execution->client_id)
                    ->update(['open_response' => 'Error getting client balance', 'info' => $e->getMessage()]);
                //return $e->getMessage();
            }
        }
    }

    /**
     * Fill executions table with a job.
     * Clone signal to all clients.
     * Quantity of records = quantity of clients
     * @param Request $request
     */
    private function fillExecutionsTable(Request $request){
        foreach (Client::all() as $client){
            Execution::create([
                'signal_id' => $request['id'],
                'client_id' => $client->id,
                'client_name' => $client->name,
                'symbol' => $request['symbol'],
                'multiplier' => $request['multiplier'],
                'direction' => $request['direction'],
                'percent' => $request['percent'],
                'leverage' => $request['leverage'],
                //'symbol' => $request['percent'],
                //'leverage' => $request['leverage'],
            ]);
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

        if ($direction == 'long'){
            try{
                $response = ($exchange->createMarketBuyOrder($execution->symbol, $execution->client_volume, []));
            }
            catch (\Exception $e){
                Execution::where('id', $execution->id)->update(array(
                    'status' => 'error',
                    'info' => $e->getMessage()
                ));
            }
        }
        else{
            try{
                $response = ($exchange->createMarketSellOrder($execution->symbol, $execution->client_volume, []));
            }
            catch (\Exception $e)
            {
                Execution::where('id', $execution->id)->update(array(
                    'status' => 'error',
                    'info' => $e->getMessage()
                ));
            }
        }

        // Write statuses to DB
        if ($execution->status == "new"){
            Signal::where('id', $execution->signal_id)->update(array(
                'status' => 'open',
                'quote' => $response['price'],
                'open_date' => date("Y-m-d G:i:s", $response['timestamp'] / 1000),
                'open_price' => $response['price']
            ));

            Execution::where('id', $execution->id)->update(array(
                'status' => 'open',
                'open_status' => 'ok',
                'open_response' => json_encode($response),
                'open_price' => $response['price'],
                'info' => 'Position opened ok'
            ));
        }

        if ($execution->status == "open"){
            Signal::where('id', $execution->signal_id)->update(array(
                'status' => 'executed',
                'close_date' => date("Y-m-d G:i:s", $response['timestamp'] / 1000),
                'close_price' => $response['price']
            ));
            Execution::where('id', $execution->id)->update(array(
                'status' => 'executed',
                'close_status' => 'ok',
                'close_response' => json_encode($response),
                'close_price' => $response['price'],
                'info' => 'Position closed ok'
            ));
        }

        Execution::where('id', $execution->id)->update(array(
            //'info' => json_encode($response)
            //'close_date' => date("Y-m-d G:i:s", $response['timestamp'] / 1000),
            //'close_price' => $response['price']
        ));

        //Signal::where('id', $request->id)->update(array(
        //    'close_date' => date("Y-m-d G:i:s", $response['timestamp'] / 1000),
        //    'close_price' => $response['price']
        //));



        //return $response;
    }

}


/*
        $exchange = new bitmex();
        //dump(array_keys($exchange->load_markets())); // ETH/USD BTC/USD
        //dump($exchange->fetch_ticker('BTC/USD'));
        $exchange->apiKey = Client::where('id', '>', 0)->orderby('id', 'desc')->first()->api;
        $exchange->secret = Client::where('id', '>', 0)->orderby('id', 'desc')->first()->api_secret;
*/