<?php

namespace App\Console\Commands;

use App\Services\UpWorkReader;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Maknz\Slack\Client;

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

        $jobs1 = $reader->fetchJobs('php');
        sleep(3);
        $jobs2 = $reader->fetchJobs('php,laravel');
        sleep(3);
        $jobs3 = $reader->fetchJobs('php,symphony');
        sleep(3);
        $jobs4 = $reader->fetchJobs('javascript,angular');

        $jobsM1 = array_merge_recursive($jobs1, $jobs2);
        $jobsM2 = array_merge_recursive($jobs3, $jobs4);

        $jobs = array_merge_recursive($jobsM1, $jobsM2);

        $settings = [
            'username' => 'UpWork Bot',
            'channel' => '#upwork',
            'link_names' => true
        ];

        $client = new Client(env('SLACK_WEBHOOK_URL'), $settings);

        foreach($jobs as $job) {

            if ($now->timestamp - (int)$job->created_timestamp >= (16 * 60)) continue;

            $client->to('#upwork')->attach([
                'title'=> $job->title,
                'title_link'=> $job->link,
                'color' => '#00cc00',
                'fields' => [[
                    'title' => 'category',
                    'value' => $job->category,
                ],[
                    'title' => 'country',
                    'value' => $job->country,
                ],[
                    'title' => 'skills',
                    'value' => implode(', ', $job->skills),
                ],[
                    'title' => 'budget',
                    'value' => $job->budget . " $",
                ],[
                    'title' => 'posted_date',
                    'value' => $job->created_date,
                ],[
                    'title' => 'posted_date',
                    'value' => Carbon::parse($job->created_date)->diffForHumans(),
                ]]
            ])->send('');
        }

        return true;
    }
}
