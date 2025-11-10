<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public $contract;
    public $daysUntilExpiration;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract, int $daysUntilExpiration)
    {
        $this->contract = $contract;
        $this->daysUntilExpiration = $daysUntilExpiration;
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
        $viewUrl = route('contracts.show', $this->contract->id);
        $expirationDate = $this->contract->expiration_date->format('F d, Y');

        $daysText = $this->daysUntilExpiration === 1 ? '1 day' : $this->daysUntilExpiration . ' days';

        return (new MailMessage)
            ->subject('Contract Expiring Soon: ' . $this->contract->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a reminder that your contract is expiring soon:')
            ->line('**' . $this->contract->title . '**')
            ->line('Contract Number: ' . $this->contract->contract_number)
            ->line('Expiration Date: ' . $expirationDate . ' (' . $daysText . ')')
            ->action('View Contract', $viewUrl)
            ->line('Please take action if renewal or extension is needed.')
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contract_id' => $this->contract->id,
            'contract_title' => $this->contract->title,
            'contract_number' => $this->contract->contract_number,
            'days_until_expiration' => $this->daysUntilExpiration,
            'expiration_date' => $this->contract->expiration_date->toDateString(),
            'action' => 'contract_expiring_soon',
            'message' => 'Contract expiring in ' . $this->daysUntilExpiration . ' days: ' . $this->contract->title,
        ];
    }
}
