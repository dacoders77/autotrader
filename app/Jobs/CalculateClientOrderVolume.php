<?php

namespace App\Jobs;

use App\Classes\LogToFile;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Execution; // Link model
use App\Symbol;
use App\Signal;
use Mockery\Exception;
use Illuminate\Support\Facades\DB;

/**
 * Calculate order IN volume for each client in the signal.
 * Calculate order volume for take profit exits. Quantity of exits can be 1-4.
 * This class is called when a signal is created in Signals.vue
 *
 * Class CalculateClientOrderVolume
 * @package App\Jobs
 */
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
         * Run through all records in executions table.
         * With a specific signal id.
         * And where client funds != 0. If == 0 it means that API keys did not work and we did not get the balance.
         * This client will not be executed.
         */
        foreach (Execution::where('signal_id', $this->signalId)
                 ->where('client_funds_value', '!=', null)
                 ->get() as $execution) {
            /**
             * Balance share calculation. Calculate value in XBT.
             * This value represents 100% of a signal, 100% of volume.
             */
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
                    'out_volume_1' => round($balancePortionXBT / $this->symbolInXBT * $execution->leverage) * Execution::where('id', $execution->id)->value('out_percent_1') / 100,
                    'out_volume_2' => round($balancePortionXBT / $this->symbolInXBT * $execution->leverage) * Execution::where('id', $execution->id)->value('out_percent_2') / 100,
                    'out_volume_3' => round($balancePortionXBT / $this->symbolInXBT * $execution->leverage) * Execution::where('id', $execution->id)->value('out_percent_3') / 100,
                    'out_volume_4' => round($balancePortionXBT / $this->symbolInXBT * $execution->leverage) * Execution::where('id', $execution->id)->value('out_percent_4') / 100,
                ]);


            /**
             * Go through the executions list again.
             * Sum the volume and calculate the error.
             * For example: if we have 4 exits, when % is calculated, it gets rounded.
             * Then if you sum all these values - you wont get the original value - it will be smaller.
             * We need to calculate this error and add to the last exit. It can 1st or even the last exit.
             */

            // 1. Sum all exits out_volume_1 + out_volume_2 + out_volume_3 + out_volume_4

            $outSumVolume = 0;
            for ($i = 1; $i <= 4; $i++) {
                $outSumVolume += Execution::where('id', $execution->id)->value("out_volume_" . $i);
                //LogToFile::add(__FILE__, Execution::where('id', $execution->id)->value("out_volume_" . $i) . " " . "out_volume_" . $i);
            }

            // 2. Write it to out_status_4
            /*Execution::where('id', $execution->id)
                ->update([
                    'out_status_4' => $outSumVolume // Works good
                ]);*/
            // 3. open_value - exits_sum = exit_error
            // 4. out_value_4 = out_value_4 + exit_error

            for ($i = 4; $i >= 1; $i--) {
                if(Execution::where('id', $execution->id)->value("out_volume_" . $i) != 0){
                    // Increase out volume
                    Execution::where('id', $execution->id)
                        ->update([
                            "out_volume_" . $i => Execution::where('id', $execution->id)->value("out_volume_" . $i) + ( Execution::where('id', $execution->id)->value("client_volume") - $outSumVolume)
                        ]);
                    break; // Once the first exit != is found - correct it. We do this loop because the quantity of exits cna vary.
                }
            }
        }
    }
}
