<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\ContractSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureReminder extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $signUrl = route('contracts.sign', ['token' => $this->signature->verification_token]);
        $expiresAt = $this->signature->expires_at->format('F d, Y');

        return (new MailMessage)
            ->subject('Reminder: Signature Requested for ' . $this->contract->title)
            ->greeting('Hello ' . $this->signature->signer_name . ',')
            ->line('This is a friendly reminder that you have a pending signature request:')
            ->line('**' . $this->contract->title . '**')
            ->line('Contract Number: ' . $this->contract->contract_number)
            ->line('This signature request expires on ' . $expiresAt . '.')
            ->action('Review and Sign Contract', $signUrl)
            ->line('If you have already signed this contract, please disregard this reminder.')
            ->line('If you have any questions, please contact ' . $this->contract->user->name . '.')
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
            'action' => 'signature_reminder',
            'message' => 'Reminder to sign: ' . $this->contract->title,
        ];
    }
}
