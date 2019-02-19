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

class GetClientTradingBalanceOut implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exchange;
    protected $execution;
    private $response;
    private $tradingBalance;
    private $exitType;

    private $outBalanceStatusCell;
    private $outBalanceResponseCell;
    private $outBalanceValueCell;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($exchange, $execution, $exitType)
    {
        $this->exchange = $exchange;
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
        switch ($this->exitType) {
            case("stopLoss") :
                $this->outBalanceResponseCell = 'out_balance_response';
                $this->outBalanceValueCell = 'out_balance_value';
                $this->outBalanceStatusCell = 'out_balance_status';
                break;
            case("takeProfit0") :
                $this->outBalanceResponseCell = 'out_balance_response_1';
                $this->outBalanceValueCell = 'out_balance_value_1';
                $this->outBalanceStatusCell = 'out_balance_status_1';
                break;
            case("takeProfit1") :
                $this->outBalanceResponseCell = 'out_balance_response_2';
                $this->outBalanceValueCell = 'out_balance_value_2';
                $this->outBalanceStatusCell = 'out_balance_status_2';
                break;
            case("takeProfit2") :
                $this->outBalanceResponseCell = 'out_balance_response_3';
                $this->outBalanceValueCell = 'out_balance_value_3';
                $this->outBalanceStatusCell = 'out_balance_status_3';
                break;
            case("takeProfit3") :
                $this->outBalanceResponseCell = 'out_balance_response_4';
                $this->outBalanceValueCell = 'out_balance_value_4';
                $this->outBalanceStatusCell = 'out_balance_status_4';
                break;
        }

        Execution::where('id', $this->execution->id)
            ->update([
                $this->outBalanceStatusCell => 'pending',
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
                    $this->outBalanceStatusCell => 'error',
                    $this->outBalanceResponseCell => json_encode($this->response)
                ]);
        }

        foreach ($this->response as $symbol){
            if ($symbol['symbol'] == Symbol::where('execution_name', $this->execution->symbol)->value('leverage_name'))
                $this->tradingBalance = $symbol['currentQty'];
        }

        if (gettype($this->response) == 'array'){
            // Success
            Execution::where('id', $this->execution->id)
                ->update([
                    $this->outBalanceValueCell => $this->tradingBalance,
                    $this->outBalanceResponseCell => json_encode($this->response),
                    $this->outBalanceStatusCell => 'ok'
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
                dump('EXCHANGE OVERLOADED! RESTART JOB! Out client balance');
                throw new Exception();
            }
        }

        ExecutionCheck::inExecutionCheck($this->execution);
        return;
    }

}



