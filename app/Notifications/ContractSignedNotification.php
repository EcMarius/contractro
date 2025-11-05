<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractSignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Contract $contract) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contract semnat - ' . $this->contract->contract_number)
            ->greeting('Bună ziua!')
            ->line('Contractul **' . $this->contract->title . '** a fost semnat de toate părțile.')
            ->line('**Număr contract:** ' . $this->contract->contract_number)
            ->line('**Semnat la:** ' . $this->contract->signed_at->format('d.m.Y H:i'))
            ->action('Vezi Contractul', route('contracts.show', $this->contract))
            ->line('Puteți descărca PDF-ul semnat din panoul de control.')
            ->line('Contractul este arhivat conform cerințelor legale.');
    }
}
