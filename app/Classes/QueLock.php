<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 12/12/2018
 * Time: 4:26 AM
 */

namespace App\Classes;

use App\Job; // Link model
use App\Failed_job;

/**
 * If there are jobs in tables - don't let to:
 * - CRUD new signals
 * - Execute existing signals
 *
 * Class QueLock
 * @package App\Classes
 * @return boolean
 */
class QueLock
{
    public static function getStatus(){
        if (Job::count() == 0 && Failed_job::count() == 0){
            return true;
        }
        else{
            return false;
        }
    }
}