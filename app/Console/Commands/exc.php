<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\ExecutionController;
use Illuminate\Console\Command;

/**'
 *
 * DELETE THIS COMMAND!
 *
 * Class exc
 * @package App\Console\Commands
 */

class exc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exc';

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
        $exc = new ExecutionController();
        dump($exc->exchange->urls);
    }
}
