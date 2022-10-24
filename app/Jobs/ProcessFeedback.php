<?php

namespace App\Jobs;

use App\Models\v1\Feedback;
use GrahamCampbell\GitHub\GitHubManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFeedback implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $feedback;
    protected $action;
    protected $type;
    protected $mapPriority = [
        '---',
        "Low",
        "Medium",
        "High",
        "Very High",
        "Critical",
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Feedback $feedback, $type, $action)
    {
        $this->feedback = $feedback;
        $this->type = $type;
        $this->action = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(GitHubManager $github)
    {
        $this->githubProcessor($this->feedback, $github, $this->type, $this->action);
    }

    protected function issue($number, $github)
    {
        $issue = null;
        try {
            $issue = $github->issues()->show(
                config('services.github.owner', 'perfect-wedding'),
                config('services.github.repo', 'perfect-api'),
                $number
            );
        } catch (\Github\Exception\RuntimeException $e) {
            Log::build([
              'driver' => 'single',
              'path' => storage_path('logs/custom.log'),
            ])->debug("Github Error: " . $e->getMessage());
        }
        return $issue;
    }

    protected function githubProcessor($feedback, $github, $type, $action)
    {
        try {
            $issue = $feedback->issue_num ? $this->issue($feedback->issue_num ?? '--', $github) : null;
            if ($type == 'issue') {
                if ($action == 'open') {
                    if ($feedback->issue_num && $issue) {
                        $issue = $github->issues()->update(
                            config('services.github.owner', 'perfect-wedding'),
                            config('services.github.repo', 'perfect-api'),
                            $issue['number'],
                            [
                                'title' => __("Feedback :0 from :1", [ucfirst($feedback->type), $feedback->user->username]),
                                'body' => $feedback->message,
                                'labels' => [
                                    $this->mapPriority[$feedback->priority],
                                    $feedback->type,
                                ],
                                'state' => 'open'
                            ]
                        );
                    } else {
                        $issue = $github->issues()->create(
                            config('services.github.owner', 'perfect-wedding'),
                            config('services.github.repo', 'perfect-api'),
                            [
                                'title' => __("Feedback :0 from :1", [ucfirst($feedback->type), $feedback->user->username]),
                                'body' => $feedback->message,
                                'labels' => [
                                    $this->mapPriority[$feedback->priority],
                                    $feedback->type,
                                ],
                            ]
                        );
                    }

                    $feedback->replies()->latest()->limit(5)->get()->each(function ($reply) use ($github, $issue) {
                        $github->issues()->comments()->create('perfect-wedding', 'perfect-api', $issue['number'], [
                            'body' => $reply->user->username . " said:\n" . $reply->message
                        ]);
                    });

                    $feedback->issue_url = $issue['html_url'];
                    $feedback->issue_num = $issue['number'];
                    $feedback->save();
                } elseif ($action == 'close') {
                    if ($issue) {
                        $github->issues()->update(
                            config('services.github.owner', 'perfect-wedding'),
                            config('services.github.repo', 'perfect-api'),
                            $issue['number'],
                            ['state' => 'closed']
                        );
                        $feedback->issue_num = $issue['number'];
                    }

                    $feedback->issue_url = '';
                    $feedback->save();
                }
            }
        } catch (\Github\Exception\RuntimeException $e) {
            Log::build([
              'driver' => 'single',
              'path' => storage_path('logs/custom.log'),
            ])->debug("Github Error: " . $e->getMessage());
        }
    }
}