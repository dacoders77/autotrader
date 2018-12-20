<?php

namespace App\Console\Commands;

use App\Classes\WebSocketStream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Ratchet\Client\WebSocket;

class btmxws extends Command
{

    protected $connection;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btmxws:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'BTMX ratchet/pawl ws client console application';

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

        /**
         * Ratchet/pawl websocket library
         * @see https://github.com/ratchetphp/Pawl
         */
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, [
            'dns' => '8.8.8.8', // Does not work through OKADO internet provider. Timeout error
            'timeout' => 10
        ]);

        /* Start subscription when the console command is started. Symbols will be taken from the DB */
        Cache::put('object', ['subscribeInit' => true], 5);

        $loop->addPeriodicTimer(2, function() use($loop) {
            \App\Classes\Websocket::listenCache($this->connection);
        });

        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        /** Pick up the right websocket endpoint accordingly to the exchange */
        $exchangeWebSocketEndPoint = "wss://www.bitmex.com/realtime";

        $connector($exchangeWebSocketEndPoint, [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop) {
                $this->connection = $conn;

                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop) {
                    $jsonMessage = json_decode($socketMessage->getPayload(), true);
                    dump($jsonMessage);
                    if (array_key_exists('data', $jsonMessage)){
                        if (array_key_exists('lastPrice', $jsonMessage['data'][0])){
                            WebSocketStream::Parse($jsonMessage['data']); // Update quotes, send events to vue
                            WebSocketStream::stopLossCheck($jsonMessage['data']); // Stop loss execution
                        }
                    }
                });

                $conn->on('close', function($code = null, $reason = null) use ($loop) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    $this->info("line 82. connection closed");
                    $this->error("Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt
                    $this->handle(); // Call the main method of this class
                });

                /* Manual subscription object. If on - subscription at the start must be disabled */
                /*$requestObject = json_encode([
                    "op" => "subscribe",
                    "args" => ["instrument:XBTUSD", "instrument:ETHUSD"]
                ]);
                $conn->send($requestObject);*/

            }, function(\Exception $e) use ($loop) {
                $errorString = "RatchetPawlSocket.php Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;
                sleep(5); // Wait 5 seconds before next connection try will attpemt
                $this->handle(); // Call the main method of this class
                //$loop->stop();
            });
        $loop->run();
    }
}
