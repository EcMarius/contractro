<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractFullySigned extends Notification implements ShouldQueue
{
    use Queueable;

    public $contract;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
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
        $downloadUrl = route('contracts.download', $this->contract->id);

        return (new MailMessage)
            ->subject('Contract Fully Signed: ' . $this->contract->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! Your contract has been fully signed by all parties:')
            ->line('**' . $this->contract->title . '**')
            ->line('Contract Number: ' . $this->contract->contract_number)
            ->line('All ' . $this->contract->signatures->count() . ' required signatures have been collected.')
            ->action('View Contract', $viewUrl)
            ->line('You can download the signed PDF from the contract page.')
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
            'action' => 'contract_fully_signed',
            'message' => 'Contract fully signed: ' . $this->contract->title,
        ];
    }
}
