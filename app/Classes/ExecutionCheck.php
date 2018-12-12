<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 11/28/2018
 * Time: 6:10 AM
 */

namespace App\Classes;

use App\Execution;
use App\Signal; // Link model

/**
 * Class ExecutionCheck
 * Check whether all checks prior to order placement (in/out) were passed.
 * If so - set change the status of the signal.
 * @package App\Classes
 */
class ExecutionCheck
{
    public static function inExecutionCheck($execution){
        // If check are not null, set in status to pending
        // May be possible that some checks are still in progress or even place order is in progress.
        // Is some executions are failed - this should not prevent us to close the signal.
        // Meaning that if all executions have at least pending status - stop button will become available.
        // Some can have ok status and some pending/fail


        // Run through all executions and see whether +
        // in_order_status != null -> set button to Stop. status = proceeded
        // in_order_status == ok -> set button to Stop. status = success
        // any of in_order_status == error -> set button to Stop. status = error



        $arr = Execution::where('signal_id', $execution->signal_id)->get(['in_place_order_status']); // Get executions with the same signal_id
        $push = array();
        foreach($arr as $object)
        {
            if ($object->{'in_place_order_status'})
                array_push($push, $object->{'in_place_order_status'});
        }
        dump(array_flip($push));
        dump(count(array_keys(array_flip($push))));

        if(count(array_keys(array_flip($push))) == 1){
            if (array_key_exists('ok', array_flip($push))){
                dump('signal status: ok');
                Signal::where('id', $execution->signal_id)->update(['status' => 'success']);
            }
        }
        if(count(array_keys(array_flip($push))) >= 1){
            if (array_key_exists('error', array_flip($push))){
                dump('signal status: error');
                Signal::where('id', $execution->signal_id)->update(['status' => 'error']);
            }
        }
    }
}