<?php

namespace App\Jobs;

use App\Classes\ExecutionCheck;
use App\Execution;
use ccxt\ExchangeNotAvailable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Client;
use Mockery\Exception;

class GetClientFundsCheck implements ShouldQueue
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
                'client_funds_status' => 'pending',
            ]);

        $this->exchange->apiKey = Client::where('id', $this->execution->client_id)->value('api');
        $this->exchange->secret = Client::where('id', $this->execution->client_id)->value('api_secret');

        try{
            $this->response = $this->exchange->fetchBalance()['BTC']['free'];
        }
        catch (\Exception $e)
        {
            $this->response = $e->getMessage();
            Execution::where('id', $this->execution->id)
                ->update([
                    'client_funds_status' => 'error',
                    'client_funds_response' => $this->response // Overloaded should be here
                ]);
        }

       if (gettype($this->response) == 'double'){
            // Success
           Execution::where('id', $this->execution->id)
               ->update([
                   'client_funds_value' => $this->response,
                   'client_funds_response' => $this->response,
                   'client_funds_status' => 'ok',
                   //'status' => 'proceeded'
               ]);
       }

        dump(gettype($this->response));
        dump($this->response);

        if (gettype($this->response) == 'string'){

            /**
             * Error.
             * @todo move all text possible errors to a dictionary. Allow user to change these values.
             */
            // Overload message: "The system is currently overloaded. Please try again later."
            // "bitmex requires `apiKey`"
            if ($this->response == "bitmex {\"error\":{\"message\":\"The system is currently overloaded. Please try again later.\",\"name\":\"HTTPError\"}}\""){
                // Set get client status to: overloaded
                dump('EXCHANGE OVERLOADED! RESTART JOB! get client funds');
                throw new Exception();
            }
        }

        return;
    }
}
