<?php

namespace App\Console\Commands;

use App\Classes\WebSocketStream;
use Illuminate\Console\Command;

/**
 * Generate fake quotes for testing purposes.
 *
 * Class QuoteFaker
 * @package App\Console\Commands
 */
class QuoteFaker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotefaker:start';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $height = 200; // Height of the canvas
        $steps=2;     // The Jump in value of X - Axis for each loop.
        $x1=88;

        $i = 0;
        while (true) {
            $y1 = ($height / 2) - number_format(sin(deg2rad($x1)) * 90, 0);
            $x2 = $x1 + $steps;
            //dump($x1 . ' ' . $y1);
            $x1 = $x2;
            $i++;
            $time = time();

            $jsonMessage = [
                "table" => "instrument",
                "action" => "update",
                "data" => [
                    "symbol" => "XBTUSD",
                    "lastPrice" => $x1, // 3525.5
                    "lastTickDirection" => "PlusTick",
                    "lastChangePcnt" => -0.0441,
                    "timestamp" => date('c', $time) // 2019-01-21T08:37:31.536Z
                ]
            ];

            dump($jsonMessage);
            WebSocketStream::Parse([$jsonMessage['data']]); // Update quotes, send events to vue
            WebSocketStream::stopLossCheck($jsonMessage['data']); // Stop loss execution

        }
    }
}
