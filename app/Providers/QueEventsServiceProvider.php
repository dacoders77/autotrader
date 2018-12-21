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
 * Jobs are added to the window in the realtime. Once a job is copleted, it removes from the window.
 * In order to acces the window click on signal status (new, error, success etc.) at signals page.
 * The window is shown at Execution.vue.
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
     * Payload cell is too heavy and can not be fit int 10240 byte pusher packet size.
     * We take only displayName out of payload.
     *
     * @return array
     */
    private function buildJobsTable(){
        $this->json = [];
        foreach (Job::all()->take(50) as $job){
            $displayName = json_decode($job->payload)->displayName;
            $displayName = str_replace("App\\Jobs\\", '', $displayName); // Shorten the string
            array_push($this->json, ['id' => $job->id, 'displayName' => $displayName, 'attempts' => $job->attempts]);
        }
        return $this->json;
    }

    private function makeEventObject(){
        $array = [
            'eventName' => 'execution',
            'payLoad' => [
                'jobsTable' => self::buildJobsTable(),
                'jobsQuantity' => Job::count(),
                'failedJobsQuantity' => Failed_job::count(),
            ]
        ];
        return $array;
    }
}
