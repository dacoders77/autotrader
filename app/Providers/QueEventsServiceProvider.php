<?php

namespace App\Providers;


use App\Job;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessing;
use App\Events\AttrUpdateEvent;

class QueEventsServiceProvider extends ServiceProvider
{
    private $json;
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        /*$this->json = [
            '0' => 'zx',
            '1' => 'vvb',
            '3' => 'fgg'
        ];*/




        Queue::before(function (JobProcessing $event) {

            $this->json = [];
            foreach (Job::all() as $job){
                $displayName = json_decode($job->payload)->displayName;
                $displayName = str_replace("App\\Jobs\\", '', $displayName);
                array_push($this->json, ['id' => $job->id, 'displayName' => $displayName, 'attempts' => $job->attempts]);
            }

                // $event->connectionName
              if ($event->job->payload()['displayName'] != 'App\Events\AttrUpdateEvent'){
                  event(new AttrUpdateEvent(json_encode($this->json)));

              }
        });


        Queue::after(function (JobProcessed $event) {

            $this->json = [];
            foreach (Job::all() as $job){
                $displayName = json_decode($job->payload)->displayName;
                $displayName = str_replace("App\\Jobs\\", '', $displayName);
                array_push($this->json, ['id' => $job->id, 'displayName' => $displayName, 'attempts' => $job->attempts]);
            }

            if ($event->job->payload()['displayName'] != 'App\Events\AttrUpdateEvent'){
                event(new AttrUpdateEvent(json_encode($this->json)));
            }
        });

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
