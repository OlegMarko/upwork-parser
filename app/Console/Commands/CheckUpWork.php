<?php

namespace App\Console\Commands;

use App\Services\UpWorkReader;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Maknz\Slack\Client;
use Log;

class CheckUpWork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:upwork';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get new jobs from UpWork';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function unique_obj($obj) {
        static $idList = array();
        if(in_array($obj->id,$idList)) {
            return false;
        }
        $idList []= $obj->id;
        return true;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $now = Carbon::now();
        if ($now->hour < 7 || $now->hour > 16) {
            return true;
        }

        $reader = new UpWorkReader();

        $jobs1 = $reader->fetchJobs('email chain');
        sleep(3);
        $jobs2 = $reader->fetchJobs('CRM setup');
        sleep(3);
        $jobs3 = $reader->fetchJobs('automation');
        sleep(3);
        $jobs4 = $reader->fetchJobs('HubSpot');
        sleep(3);
        $jobs5 = $reader->fetchJobs('pipedrive');
        sleep(3);
        $jobs6 = $reader->fetchJobs('Marketing Automation Email Marketing');

        $jobsM1 = array_merge_recursive($jobs1, $jobs2);
        $jobsM2 = array_merge_recursive($jobs3, $jobs4);
        $jobsM3 = array_merge_recursive($jobs5, $jobs6);

        $collection = array_merge_recursive($jobsM1, $jobsM2);
        $collection = collect(array_merge_recursive($collection, $jobsM3));

        $jobs = $collection->map(function ($array) {
            return collect($array)->unique()->all();
        });

        $settings = [
            'username' => 'UpWork Bot',
            'channel' => '#upwork',
            'link_names' => true
        ];

        $client = new Client(env('SLACK_WEBHOOK_URL'), $settings);
        $count = 0;

        foreach($jobs as $job) {
            if ($now->timestamp - (int)$job['created_timestamp'] > (16 * 60)) continue;

            $client->to('#upwork')->attach([
                'title'=> $job['title'],
                'title_link'=> $job['link'],
                'color' => '#00cc00',
                'fields' => [[
                    'title' => 'category',
                    'value' => $job['category'],
                ],[
                    'title' => 'country',
                    'value' => $job['country'],
                ],[
                    'title' => 'skills',
                    'value' => is_array($job['skills']) ? implode(', ', $job['skills']) : 'empty',
                ],[
                    'title' => 'budget',
                    'value' => str_replace("\n", ' $', $job['budget']),
                ],[
                    'title' => 'posted_date',
                    'value' => $job['created_date'],
                ],[
                    'title' => 'posted_date',
                    'value' => Carbon::parse($job['created_date'])->diffForHumans(),
                ]]
            ])->send('');

            $count++;
        }

        Log::info($count);

        return true;
    }
}
