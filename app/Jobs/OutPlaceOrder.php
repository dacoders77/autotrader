<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Client;
use App\Execution; // Link model
use Mockery\Exception;


/**
 * There may by 5 types of out:
 * 1. Stop loss (Stop button)
 * 2. Exit 1 (Take progit button)
 * 3. Exit 2
 * 4. Exit 3
 * 5. Exit 4
 *
 * In All these cases the result must be recorded to certain columns in DB.
 *
 * The sequence:
 * 1. Send exit call
 * 2. Pass a param (stop loss, out1, out2, out3 or out4)
 * 3. If stop loss:
 * Calculate the volume for execution
 * = client_volume - (out_value_1 + out_value_2 + out_value_3 + out_value_4)
 * We take only the volume which has been actually opened. Not the volume which needs to be open - out_volume_1,2,3,4
 */

class OutPlaceOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exchange;
    protected $execution;
    private $response;
    private $exitType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($exchnage, $execution, $exitType)
    {
        $this->exchange = $exchnage;
        $this->execution = $execution;
        $this->exitType = $exitType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Execution::where('id', $this->execution->id)
            ->update([
                'out_place_order_status' => 'pending',
            ]);

        // Get order volume accordingly with exitType
        // stopLoss - $this->execution->client_volume
        // takeProfit1 = $this->execution->out_volume_1

        $this->exchange->apiKey = Client::where('id', $this->execution->client_id)->value('api');
        $this->exchange->secret = Client::where('id', $this->execution->client_id)->value('api_secret');

        try{
            if ($this->execution->direction == 'long'){
                $this->response = $this->exchange->createMarketSellOrder($this->execution->symbol, $this->execution->client_volume, []);
            }
            else{
                $this->response = $this->exchange->createMarketBuyOrder($this->execution->symbol, $this->execution->client_volume, []);
            }
        }
        catch (\Exception $e)
        {
            // Need to write to DB cell accordingly to exitType

            // Error
            $this->response = $e->getMessage();
            /*Execution::where('id', $this->execution->id)
                ->update([
                    'out_place_order_status' => 'error',
                    'out_place_order_response' => json_encode($this->response)
                ]);*/

            $this->writeOutOrderStatus(null, json_encode($this->response), 'error', $this->exitType);
        }

        if (gettype($this->response) == 'array'){

            // Need to write to DB cell accordingly to exitType

            // Success
            $this->writeOutOrderStatus($this->response['price'], json_encode($this->response), 'ok', $this->exitType);
        }

        if (gettype($this->response) == 'string'){
            // Exchange overload
            /**
             * @todo move all text possible errors to a dictionary. Allow user to change these values.
             */
            if ($this->response == "bitmex {\"error\":{\"message\":\"The system is currently overloaded. Please try again later.\",\"name\":\"HTTPError\"}}\""){
                dump('EXCHANGE OVERLOADED! RESTART JOB! IN place order');
                throw new Exception();
            }
        }

        /**
         * @todo Do the same check as for open but only for sell order placement.
         * If status changes - change signals status as well.
         * It can be success at open and error at close.
         */
        //ExecutionCheck::inExecutionCheck($this->execution);
        return;
    }

    private function writeOutOrderStatus($orderExecutionPrice, $orderExecutionJsonResponse, $orderExecutionStatus, $exitType){

        switch ($exitType){
            case "stopLoss":
                Execution::where('id', $this->execution->id)
                    ->update([
                        'out_place_order_value' => $orderExecutionPrice,
                        'out_place_order_response' => $orderExecutionJsonResponse,
                        'out_place_order_status' => $orderExecutionStatus,
                    ]);
                break;
            case "takeProfit0":
                Execution::where('id', $this->execution->id)
                    ->update([
                        'out_value_1' => $orderExecutionPrice,
                        'out_response_1' => $orderExecutionJsonResponse,
                        'out_status_1' => $orderExecutionStatus,
                    ]);
                break;
            case "takeProfit1":
                Execution::where('id', $this->execution->id)
                    ->update([
                        'out_value_2' => $orderExecutionPrice,
                        'out_response_2' => $orderExecutionJsonResponse,
                        'out_status_2' => $orderExecutionStatus,
                    ]);
                break;
            case "takeProfit2":
                Execution::where('id', $this->execution->id)
                    ->update([
                        'out_value_3' => $orderExecutionPrice,
                        'out_response_3' => $orderExecutionJsonResponse,
                        'out_status_3' => $orderExecutionStatus,
                    ]);
                break;
            case "takeProfit3":
                Execution::where('id', $this->execution->id)
                    ->update([
                        'out_value_4' => $orderExecutionPrice,
                        'out_response_4' => $orderExecutionJsonResponse,
                        'out_status_4' => $orderExecutionStatus,
                    ]);
                break;

        }
    }

}
