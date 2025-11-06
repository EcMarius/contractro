<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\ContractSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractSignatureRequested extends Notification implements ShouldQueue
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
        $signUrl = route('contracts.sign', ['token' => $this->signature->verification_token]);
        $expiresIn = $this->signature->expires_at->diffForHumans();

        return (new MailMessage)
            ->subject('Signature Requested: ' . $this->contract->title)
            ->greeting('Hello ' . $this->signature->signer_name . ',')
            ->line('You have been requested to sign the following contract:')
            ->line('**' . $this->contract->title . '**')
            ->line('Contract Number: ' . $this->contract->contract_number)
            ->line('This signature request expires ' . $expiresIn . '.')
            ->action('Review and Sign Contract', $signUrl)
            ->line('If you have any questions about this contract, please contact ' . $this->contract->user->name . '.')
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
            'signature_id' => $this->signature->id,
            'action' => 'signature_requested',
            'message' => 'You have been requested to sign: ' . $this->contract->title,
        ];
    }
}
