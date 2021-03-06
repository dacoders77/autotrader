<?php

namespace App\Jobs;

use App\Classes\ExecutionCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Client;
use App\Execution;
use Mockery\Exception;

class InPlaceOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exchange;
    protected $execution;
    private $response;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($exchange, $execution)
    {
        $this->exchange = $exchange;
        $this->execution = $execution;
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
                'in_place_order_status' => 'pending',
            ]);

        $this->exchange->apiKey = Client::where('id', $this->execution->client_id)->value('api');
        $this->exchange->secret = Client::where('id', $this->execution->client_id)->value('api_secret');

        try{
            if ($this->execution->direction == 'long'){
                $this->response = $this->exchange->createMarketBuyOrder($this->execution->symbol, $this->execution->client_volume, []);
            }
            else{
                $this->response = $this->exchange->createMarketSellOrder($this->execution->symbol, $this->execution->client_volume, []);
            }

        }
        catch (\Exception $e)
        {
            $this->response = $e->getMessage();
            Execution::where('id', $this->execution->id)
                ->update([
                    'in_place_order_status' => 'error',
                    'in_place_order_response' => json_encode($this->response)
                ]);
        }

        if (gettype($this->response) == 'array'){
            // Success
            Execution::where('id', $this->execution->id)
                ->update([
                    'in_place_order_value' => $this->response['price'],
                    'in_place_order_response' => json_encode($this->response),
                    'in_place_order_status' => 'ok',
                ]);
        }

        //dump(gettype($this->response));
        //dump($this->response);

        if (gettype($this->response) == 'string'){
            // Error
            /**
             * @todo move all text possible errors to a dictionary. Allow user to change these values.
             */
            if ($this->response == "bitmex {\"error\":{\"message\":\"The system is currently overloaded. Please try again later.\",\"name\":\"HTTPError\"}}\""){
                // Set get client status to: overloaded
                dump('EXCHANGE OVERLOADED! RESTART JOB! IN place order');
                throw new Exception();
            }
        }

        ExecutionCheck::inExecutionCheck($this->execution);
        return;
    }

}
