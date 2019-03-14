<?php

namespace App\Console\Commands;

use App\Services\UpWorkReader;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Maknz\Slack\Client;
use Log;

class CheckDevUpWork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:upwork_2';

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

        $keywords = [
            'Laravel',
            'Yii',
            'Symfony',
            'Angular',
            'Javascript',
            'Full-stack',
            'Back-end',
            'backend',
            'Front-end',
            'frontend',
            'development',
            'Mysql',
            'Postygresql',
            'Develop website',
            'CRM',
            'ERP',
            'PHP',
        ];

        $jobs = [];

        foreach ($keywords as $keyword) {
            $newJobs = $reader->fetchJobs($keyword);

            $jobs = array_merge_recursive($jobs, $newJobs);

            sleep(2);
        }

        $settings = [
            'username' => 'UpWork Bot',
            'channel' => '#upwork',
            'link_names' => true
        ];

        $client = new Client(env('SLACK_WEBHOOK_URL_2'), $settings);
        $count = 0;

        $existing = [];

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

        Log::info($count);

        return true;
    }
}
