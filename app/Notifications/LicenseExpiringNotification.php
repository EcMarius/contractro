<?php

namespace App\Notifications;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public License $license,
        public int $daysRemaining
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
        $subject = $this->daysRemaining <= 0
            ? 'Your License Has Expired'
            : "Your License Expires in {$this->daysRemaining} Days";

        $greeting = $this->daysRemaining <= 0
            ? 'License Expired'
            : 'License Expiring Soon';

        $message = $this->daysRemaining <= 0
            ? "Your license for {$this->license->domain} has expired."
            : "Your license for {$this->license->domain} will expire in {$this->daysRemaining} days on {$this->license->expires_at->format('M j, Y')}.";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line($greeting)
            ->line($message)
            ->line('**License Details:**')
            ->line("- Product: {$this->license->product_name}")
            ->line("- Domain: {$this->license->domain}")
            ->line("- License Key: {$this->license->license_key}")
            ->line("- Type: " . ucfirst($this->license->type))
            ->action('Renew License', url("/admin/licenses/{$this->license->id}/edit"))
            ->line('To continue using your license without interruption, please renew it before the expiration date.')
            ->line('If you have any questions, please contact our support team.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'license_id' => $this->license->id,
            'license_key' => $this->license->license_key,
            'domain' => $this->license->domain,
            'product_name' => $this->license->product_name,
            'days_remaining' => $this->daysRemaining,
            'expires_at' => $this->license->expires_at?->toIso8601String(),
            'type' => 'license_expiring',
        ];
    }
}
