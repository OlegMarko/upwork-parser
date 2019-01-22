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

        $jobs1 = $reader->fetchJobs('Blockchain');
        sleep(3);
        $jobs2 = $reader->fetchJobs('STO');
        sleep(3);
        $jobs3 = $reader->fetchJobs('ICO');
        sleep(3);
        $jobs4 = $reader->fetchJobs('White Paper');
        sleep(3);
        $jobs5 = $reader->fetchJobs('token sale');
        sleep(3);
        $jobs6 = $reader->fetchJobs('bitcoin');
        sleep(3);
        $jobs7 = $reader->fetchJobs('ethereum');
        sleep(3);
        $jobs8 = $reader->fetchJobs('smart contract');
        sleep(3);
        $jobs9 = $reader->fetchJobs('ICO Marketing');

        $jobsM1 = array_merge_recursive($jobs1, $jobs2);
        $jobsM2 = array_merge_recursive($jobs3, $jobs4);
        $jobsM3 = array_merge_recursive($jobs5, $jobs6);
        $jobsM4 = array_merge_recursive($jobs7, $jobs8);

        $jobs1 = array_merge_recursive($jobsM1, $jobsM2);
        $jobs2 = array_merge_recursive($jobsM3, $jobsM4);

        $jobs3 = array_merge_recursive($jobs1, $jobs2);

        $jobs = array_merge_recursive($jobs3, $jobs9);

        $existing = [];

        $settings = [
            'username' => 'UpWork Bot',
            'channel' => '#upwork',
            'link_names' => true
        ];

        $client = new Client(env('SLACK_WEBHOOK_URL'), $settings);
        $count = 0;

        foreach($jobs as $job) {
            if ($now->timestamp - (int)$job->created_timestamp > ((15 * 60) + 1)) continue;
            if (in_array($job->title, $existing)) continue;

            $existing[] = $job->title;

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
                    'value' => is_array($job->skills) ? implode(', ', $job->skills) : 'empty',
                ],[
                    'title' => 'budget',
                    'value' => str_replace("\n", ' $', $job->budget),
                ],[
                    'title' => 'posted_date',
                    'value' => $job->created_date,
                ],[
                    'title' => 'posted_date',
                    'value' => Carbon::parse($job->created_date)->diffForHumans(),
                ]]
            ])->send('');

            $count++;
        }

        return true;
    }
}
