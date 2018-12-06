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
        $now = Carbon::now()->timestamp;

        $reader = new UpWorkReader();
        $jobs = $reader->fetchJobs('php,laravel');

        $settings = [
            'username' => 'UpWork Bot',
            'channel' => '#upwork',
            'link_names' => true
        ];

        $client = new Client(env('SLACK_WEBHOOK_URL'), $settings);

        foreach($jobs as $job) {

            dd(Carbon::parse($job->posted_date)->timestamp);

            $client->to('#upwork')->attach([
                'title'=> $job->title,
                'title_link'=> $job->link,
                'color' => '#36a64f',
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
                    'value' => $job->posted_date,
                ]]
            ])->send('');
        }
    }
}
