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
use App\Symbol;
use Mockery\Exception;

class SetLeverageCheck implements ShouldQueue
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
    public function __construct($exchnage, $execution)
    {
        $this->exchange = $exchnage;
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
                'leverage_status' => 'pending',
            ]);

        $this->exchange->apiKey = Client::where('id', $this->execution->client_id)->value('api');
        $this->exchange->secret = Client::where('id', $this->execution->client_id)->value('api_secret');

        try{
            $this->response = $this->exchange->privatePostPositionLeverage(array('symbol' => Symbol::where('execution_name', $this->execution->symbol)->value('leverage_name'), 'leverage' => $this->execution->leverage));
        }
        catch (\Exception $e)
        {
            $this->response = $e->getMessage();
            Execution::where('id', $this->execution->id)
                ->update([
                    'leverage_status' => 'error',
                    'leverage_response' => json_encode($this->response) // Exchange overload should be here
                ]);
        }

        if (gettype($this->response) == 'array'){
            // Success
            Execution::where('id', $this->execution->id)
                ->update([
                    'leverage_status' => 'ok',
                    'leverage_value' => $this->execution->leverage,
                    'leverage_response' => json_encode($this->response),
                ]);
        }

        if (gettype($this->response) == 'string'){
            // Error
            /**
             * @todo move all text possible errors to a dictionary. Allow user to change these values.
             */
            if ($this->response == "bitmex {\"error\":{\"message\":\"The system is currently overloaded. Please try again later.\",\"name\":\"HTTPError\"}}\""){
                // Set get client status to: overloaded
                dump('EXCHANGE OVERLOADED! RESTART JOB! leverage');
                throw new Exception();
            }
        }
        return;
    }
}
