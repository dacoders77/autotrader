<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessing;
use App\Events\AttrUpdateEvent;
use App\Job;
use App\Failed_job;

/**
 * Add que actions: before and after. 
 * These actions are needed for real-time jobs management table render in Execution.vue.
 *
 * Class QueEventsServiceProvider
 * @package App\Providers
 * @return void
 */
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
        Queue::before(function (JobProcessing $event) {
            /**
             * Filter pusher events calls.
             * Event calls fire these before and after methods and cause pipe overload error!
             */
              if ($event->job->payload()['displayName'] != 'App\Events\AttrUpdateEvent'){
                  event(new AttrUpdateEvent(self::makeEventObject()));
              }
        });

        Queue::after(function (JobProcessed $event) {
            if ($event->job->payload()['displayName'] != 'App\Events\AttrUpdateEvent'){
                event(new AttrUpdateEvent(self::makeEventObject()));
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

    /**
     * Build jobs table and pass it to Execuion.vue
     * Payload cell is too heavy and can not be fit int 1024 byte pusher packet size.
     * We take only displayName out of payload.
     *
     * @return array
     */
    private function buildJobsTable(){
        $this->json = [];
        foreach (Job::all() as $job){
            $displayName = json_decode($job->payload)->displayName;
            $displayName = str_replace("App\\Jobs\\", '', $displayName);
            array_push($this->json, ['id' => $job->id, 'displayName' => $displayName, 'attempts' => $job->attempts]);
        }
        return $this->json;
    }

    private function makeEventObject(){
        $array = [
            'eventName' => 'execution',
            'payLoad' => [
                'jobsTable' => self::buildJobsTable(),
                'failedJobsQuantity' => Failed_job::count()
            ]
        ];
        return $array;
    }
}
