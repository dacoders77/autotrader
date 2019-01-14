<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Execution; // Link model
use App\Symbol;
use App\Signal;
use Mockery\Exception;

class CalculateClientOrderVolume implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $signalId;
    private $symbolInXBT;
    private $symbolQuote;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($signalId)
    {
        $this->signalId = $signalId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * Run through all records in executions table
         * With a specific signal id
         * And where client funds != 0. If == 0 it means that API keys did not work and we did not get the balance
         */
        foreach (Execution::where('signal_id', $this->signalId)
                     ->where('client_funds_value', '!=', null)
                     ->get() as $execution){

            /* Balance share calculation */
            $balancePortionXBT = Execution::where('id', $execution->id)
                    ->value('client_funds_value') * Execution::where('id', $execution->id)->value('percent') / 100;
            $this->symbolQuote = Signal::where('id', $execution->signal_id)->value('quote_value');

            /**
             * Contract formula
             * Formulas are set in Symbols.vue
             * Get the formula. Use symbol as the key
             *
             * Symbol guide:
             * @see https://www.bitmex.com/app/seriesGuide/TRX
             */
            $formula = Symbol::where('execution_name', $execution->symbol)->value('formula');
            if ($formula == "=1/symbolQuote(BTC)") $this->symbolInXBT = 1 / $this->symbolQuote;
            if ($formula == "=symbolQuote*multp(ETH)") $this->symbolInXBT = $this->symbolQuote * 0.000001;
            if ($formula == "=symbolQuote") $this->symbolInXBT = $this->symbolQuote;

            Execution::where('id', $execution->id)
                ->update([
                    'client_volume' => round($balancePortionXBT / $this->symbolInXBT * $execution->leverage), // Only values with no decimal points accepted
                    'client_funds_use' => $balancePortionXBT,
                ]);
        }
    }
}
