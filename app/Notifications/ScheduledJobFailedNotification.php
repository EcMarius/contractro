<?php

namespace App\Notifications;

use App\Models\ScheduledJobRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduledJobFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ScheduledJobRun $jobRun,
        public int $consecutiveFailures = 1
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->consecutiveFailures > 1
            ? "ALERT: Scheduled Job '{$this->jobRun->job_name}' Failed {$this->consecutiveFailures} Times"
            : "Scheduled Job Failed: {$this->jobRun->job_name}";

        $message = (new MailMessage)
            ->error()
            ->subject($subject)
            ->greeting('Scheduled Job Failure Alert')
            ->line("The scheduled job **{$this->jobRun->job_name}** has failed.");

        if ($this->consecutiveFailures > 1) {
            $message->line("⚠️ **This job has failed {$this->consecutiveFailures} times in the last 24 hours.**");
        }

        $message->line('**Job Details:**')
            ->line("- Job Name: {$this->jobRun->job_name}")
            ->line("- Started: {$this->jobRun->started_at->format('Y-m-d H:i:s')}")
            ->line("- Duration: {$this->jobRun->duration_seconds} seconds")
            ->line("- Status: Failed");

        if ($this->jobRun->error_message) {
            $message->line('')
                ->line('**Error Message:**')
                ->line("```")
                ->line($this->jobRun->error_message)
                ->line("```");
        }

        if ($this->jobRun->output) {
            $message->line('')
                ->line('**Output:**')
                ->line("```")
                ->line(substr($this->jobRun->output, 0, 500))
                ->line("```");
        }

        $message->action('View Job Logs', url("/admin/scheduled-jobs/{$this->jobRun->id}"))
            ->line('Please investigate and resolve this issue as soon as possible.')
            ->salutation('System Monitor - ' . config('app.name'));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_run_id' => $this->jobRun->id,
            'job_name' => $this->jobRun->job_name,
            'status' => $this->jobRun->status,
            'consecutive_failures' => $this->consecutiveFailures,
            'error_message' => $this->jobRun->error_message,
            'started_at' => $this->jobRun->started_at->toIso8601String(),
            'type' => 'scheduled_job_failed',
        ];
    }
}
