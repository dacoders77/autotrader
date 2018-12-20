<?php

namespace App\Jobs;

use App\Classes\ExecutionCheck;
use App\Symbol;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Client;
use App\Execution;
use Mockery\Exception;

class GetClientTradingBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exchange;
    protected $execution;
    private $response;
    private $tradingBalance;

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
                'in_balance_status' => 'pending',
            ]);

        $this->exchange->apiKey = Client::where('id', $this->execution->client_id)->value('api');
        $this->exchange->secret = Client::where('id', $this->execution->client_id)->value('api_secret');

        try{
            $this->response = $this->exchange->privateGetPosition();
        }
        catch (\Exception $e)
        {
            $this->response = $e->getMessage();
            Execution::where('id', $this->execution->id)
                ->update([
                    'in_balance_status' => 'error',
                    'in_balance_response' => json_encode($this->response)
                ]);
        }

        /* In case of invalid api keys string value instead of array is received */
        if (gettype($this->response) == 'array' ){
            foreach ($this->response as $symbol){
                if ($symbol['symbol'] == Symbol::where('execution_name', $this->execution->symbol)->value('leverage_name'))
                    $this->tradingBalance = $symbol['currentQty'];
            }
        }

        if (gettype($this->response) == 'array'){
            // Success
            Execution::where('id', $this->execution->id)
                ->update([
                    'in_balance_value' => $this->tradingBalance,
                    'in_balance_response' => json_encode($this->response),
                    'in_balance_status' => 'ok'
                ]);
        }

        dump(gettype($this->response));
        dump($this->response);


        if (gettype($this->response) == 'string'){
            // Error
            /**
             * @todo move all text possible errors to a dictionary. Allow user to change these values.
             */
            if ($this->response == "bitmex {\"error\":{\"message\":\"The system is currently overloaded. Please try again later.\",\"name\":\"HTTPError\"}}\""){
                // Set get client status to: overloaded
                dump('EXCHANGE OVERLOADED! RESTART JOB! IN client balance');
                throw new Exception();
            }
        }

        ExecutionCheck::inExecutionCheck($this->execution);
        return;
    }

}



