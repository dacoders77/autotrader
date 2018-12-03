<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Client; // Link model
use App\Signal;
use Mockery\Exception;

/**
 * Class GetSignalSymbolQuote
 * Called from SignalController.php when executions table is filled
 *
 * @package App\Jobs
 */
class GetSignalSymbolQuote implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exchange;
    private $response;
    private $tradingSymbol;
    private $signalId;

    /**
     * Create a new job instance.
     *
     * GetSignalSymbolQuote constructor.
     * @param $exchange
     * @param $tradingSymbol
     * @param $signalId
     */
    public function __construct($exchange, $tradingSymbol, $signalId)
    {
        $this->exchange = $exchange;
        $this->tradingSymbol = $tradingSymbol;
        $this->signalId = $signalId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Signal::where('id', $this->signalId)
            ->update([
                'quote_status' => 'pending',
            ]);

        try{
            $this->response = $this->exchange->fetch_ticker($this->tradingSymbol)['last'];
        }
        catch (\Exception $e)
        {
            $this->response = $e->getMessage();
            Signal::where('id', $this->signalId)
                ->update([
                    'quote_status' => 'error',
                    'quote_response' => $this->response // Overloaded should be here
                ]);
        }

        if (gettype($this->response) == 'double'){
            // Success
            Signal::where('id', $this->signalId)
                ->update([
                    'quote_value' => $this->response,
                    'quote_response' => $this->response,
                    'quote_status' => 'ok',
                ]);
        }

        dump(gettype($this->response));
        dump($this->response);

        if (gettype($this->response) == 'string'){
            // Error
            /**
             * @todo move all text possible errors to a dictionary. Allow user to change these values.
             */

            // Overload message: "The system is currently overloaded. Please try again later."
            // "bitmex requires `apiKey`"
            if ($this->response == "bitmex {\"error\":{\"message\":\"The system is currently overloaded. Please try again later.\",\"name\":\"HTTPError\"}}\""){
                // Set get client status to: overloaded
                dump('EXCHANGE OVERLOADED! RESTART JOB!' . __FILE__);
                throw new Exception();
            }
        }

        return;
    }
}
