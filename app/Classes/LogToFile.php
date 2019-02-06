<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 10/31/2018
 * Time: 4:49 AM
 */

namespace App\Classes;

class LogToFile
{
    static public function add ($source, $message){
        $txt = date("Y-m-d G:i:s") . " $source " . $message . "\n";
        file_put_contents(__DIR__ . "/../../storage/logs/debug.txt", $txt, FILE_APPEND);
        echo getcwd();
    }

    static public function createTextLogFile (){
        //$handle = fopen("../storage/logs/debug.txt", "w") or die("Unable to open ../storage/logs/debug.txt!");
    }
}


