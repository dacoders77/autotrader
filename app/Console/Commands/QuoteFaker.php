<?php

namespace App\Console\Commands;

use App\Classes\WebSocketStream;
use Illuminate\Console\Command;

/**
 * Generate fake quotes for testing purposes.
 * Sine curve is used as a quotes stream source.
 *
 * Class QuoteFaker
 * @package App\Console\Commands
 */
class QuoteFaker extends Command
{
    private $arr = array();

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * $x1 - step number
     * $y1 - sine curve value
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * Example:
         * Height: 100
         * 6050 -> 6095 -> 6005 -> 6095 -> 6005
         */
        $height = 100; // Height of the canvas. 200
        $steps = 2;     // Rising speed. The jump in value of X - Axis for each loop.
        $x1=1;

        $i = 0;
        //for($z = 1; $z < 1000; $z++) {
        while (true) {
            //$y1 = ($height / 2) - number_format(sin(deg2rad($x1)) * 90, 0); // * 90 - sine values start coming down.
            $y1 = ($height / 2) + number_format(sin(deg2rad($x1)) * 45, 0); // * 45 - starts rising at the beginning
            $x2 = $x1 + $steps;
            //dump($x1 . ' ' . $y1); // Output values
            $x1 = $x2;
            $i++;
            $time = time();

            $jsonMessage = [
                "table" => "instrument",
                "action" => "update",
                "data" => [
                    "symbol" => "XBTUSD",
                    "lastPrice" => 6000 + $y1, // 3525.5
                    "lastTickDirection" => "PlusTick",
                    "lastChangePcnt" => -0.0441,
                    "timestamp" => date('c', $time) // 2019-01-21T08:37:31.536Z
                ]
            ];

            //array_push($this->arr, $y1); // Use it with for loop, for testing

            dump($jsonMessage);
            WebSocketStream::Parse([$jsonMessage['data']]); // Update quotes, send events to vue
            WebSocketStream::stopLossCheck([$jsonMessage['data']]); // Stop loss execution
            WebSocketStream::takeProfitCheck([$jsonMessage['data']]); // Take profit check and execution
        }

        //dump($this->arr);
    }
}
