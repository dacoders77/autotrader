<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Signal; // Link model
use App\Client; // Link model
use App\Execution; // Link model
use ccxt\bitmex;
use Illuminate\Support\Facades\Cache;

class SymbolController extends Controller
{
    private $orderVolume;

    // add: signal id, client id
    public function executeSymbol(Request $request){

        /* executeSignal
         * 1. Loop through all clients
         * 2. Calculate volume for each client
         * 3. Add this volume to exec table (and other values)
         *
         * 4. When table is prepared - run thrugh it
         * 5. Add job
         *
         * When job is executed - write it's resut to exec table
         *
         */

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

        // ** FETCH FUNDS AND ADD TO DB
        // foreach
        // executions
        // where: signal_id = $request['id'] // 24
        // fetch balance. where: api = client, api_secret = client[id]
        // update -ADD CLIENT FUNDS TO EXECUTIONS?



/*
        $exchange = new bitmex();
        //dump(array_keys($exchange->load_markets())); // ETH/USD BTC/USD
        //dump($exchange->fetch_ticker('BTC/USD'));
        $exchange->apiKey = Client::where('id', '>', 0)->orderby('id', 'desc')->first()->api;
        $exchange->secret = Client::where('id', '>', 0)->orderby('id', 'desc')->first()->api_secret;


        if($request['status'] == "new"){

            // Get XBT balance
            // get ETH price
            // express ETH in XBT - USE multiplier
            // Calculate 30% of account
            // ETH in XBT price / 30% account
            // volume

            $percent = 30; // 30%
            $clientBalanceXBT = $exchange->fetchBalance()['BTC']['free'];
            $symbolQuote = $exchange->fetch_ticker('ETH/USD')['last'];
            $symbolXbtQuote = $symbolQuote * 0.000001; // ETH!
            $balancePortionXBT = $clientBalanceXBT * $percent / 100;
            $this->orderVolume = round($balancePortionXBT / $symbolXbtQuote);
            // store this volume value in cache

            Cache::put("12345", $this->orderVolume, 5);


            if ($request['direction'] == "long"){
                $this->openPosition($exchange, $request, "long", $this->orderVolume);
            }
            else{
                $this->openPosition($exchange, $request, "short", $this->orderVolume);
            }

            Signal::where('id', $request->id)->update(array(
                'status' => 'open',
            ));
        }
        else
        {
            if ($request['direction'] == "long"){
                $this->openPosition($exchange, $request, "short", Cache::get('12345'));
            }
            else{
                $this->openPosition($exchange, $request, "long", Cache::get('12345'));
            }

            Cache::forget('12345');
            Signal::where('id', $request->id)->update(array(
                'status' => 'executed',
            ));

        }


        // Write statuses to DB
        if ($request['status'] == "new"){

            Signal::where('id', $request->id)->update(array(
                'status' => 'open',
                //'open_date' => date("Y-m-d G:i:s", $response['timestamp'] / 1000),
                //'open_price' => $response['price']
            ));

        }

        if ($request['status'] == "open"){

            //$response = ($exchange->createMarketSellOrder('ETH/USD', 1, []));

            Signal::where('id', $request->id)->update(array(
                'status' => 'executed',
                //'close_date' => date("Y-m-d G:i:s", $response['timestamp'] / 1000),
                //'close_price' => $response['price']
            ));
        }

        return "position closed";

        //return response('No symbol found', 412);

*/


    }

    private function openPosition(bitmex $exchange, Request $request, $direction, $orderVolume){
        /* Position open */
        if ($direction == 'long'){
            $response = ($exchange->createMarketBuyOrder('ETH/USD', $orderVolume, []));
        }
        else{
            $response = ($exchange->createMarketSellOrder('ETH/USD', $orderVolume, []));
        }

        Signal::where('id', $request->id)->update(array(
            'close_date' => date("Y-m-d G:i:s", $response['timestamp'] / 1000),
            'close_price' => $response['price']
        ));

        // Add info to orders log:


        return $response;
    }

}
