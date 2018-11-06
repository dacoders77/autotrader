<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Signal; // Link model
use App\Client; // Link model
use ccxt\bitmex;

class SymbolController extends Controller
{
    public function executeSymbol(Request $request){

        $exchange = new bitmex();
        //dump(array_keys($exchange->load_markets())); // ETH/USD BTC/USD
        //dump($exchange->fetch_ticker('BTC/USD'));
        $exchange->apiKey = Client::where('id', '>', 0)->orderby('id', 'desc')->first()->api; //'WkrVX4BG6aj4Y1rVGfZfB9CG';
        $exchange->secret = Client::where('id', '>', 0)->orderby('id', 'desc')->first()->api_secret; //'IFnTQcesYzCy3c8Srs5ULB8qZGpnHAOBvrfOwmnsHDJLLsFi';

        if($request['status'] == "new"){

            if ($request['direction'] == "long"){
                $this->openPosition($exchange, $request, "long");
            }
            else{
                $this->openPosition($exchange, $request, "short");
            }

            Signal::where('id', $request->id)->update(array(
                'status' => 'open',
            ));
        }
        else
        {
            if ($request['direction'] == "long"){
                $this->openPosition($exchange, $request, "short");
            }
            else{
                $this->openPosition($exchange, $request, "long");
            }

            Signal::where('id', $request->id)->update(array(
                'status' => 'executed',
            ));

        }


        /* Write statuses to DB */
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
    }

    private function openPosition(bitmex $exchange, Request $request, $direction){
        /* Position open */
        if ($direction == 'long'){
            $response = ($exchange->createMarketBuyOrder('ETH/USD', 1, []));
        }
        else{
            $response = ($exchange->createMarketSellOrder('ETH/USD', 1, []));
        }

        Signal::where('id', $request->id)->update(array(
            'close_date' => date("Y-m-d G:i:s", $response['timestamp'] / 1000),
            'close_price' => $response['price']
        ));

        return $response;
    }
}
