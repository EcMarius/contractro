<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\ContractSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractSigned extends Notification implements ShouldQueue
{
    use Queueable;

    public $contract;
    public $signature;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract, ContractSignature $signature)
    {
        $this->contract = $contract;
        $this->signature = $signature;
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
        $totalSignatures = $this->contract->signatures->count();
        $completedSignatures = $this->contract->signatures->where('status', 'signed')->count();

        return (new MailMessage)
            ->subject('Contract Signed: ' . $this->contract->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('**' . $this->signature->signer_name . '** has signed the contract:')
            ->line('**' . $this->contract->title . '**')
            ->line('Contract Number: ' . $this->contract->contract_number)
            ->line('Progress: ' . $completedSignatures . ' of ' . $totalSignatures . ' signatures collected.')
            ->action('View Contract', $viewUrl)
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
            'signer_name' => $this->signature->signer_name,
            'action' => 'contract_signed',
            'message' => $this->signature->signer_name . ' signed: ' . $this->contract->title,
        ];
    }
}
