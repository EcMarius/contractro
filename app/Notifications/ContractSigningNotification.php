<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\ContractParty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractSigningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Contract $contract,
        public ContractParty $party,
        public string $signingUrl
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contract pentru semnare - ' . $this->contract->contract_number)
            ->greeting('Bună ziua, ' . $this->party->name)
            ->line('Aveți un contract nou de semnat.')
            ->line('**Contract:** ' . $this->contract->title)
            ->line('**Număr:** ' . $this->contract->contract_number)
            ->line('**Companie:** ' . $this->contract->company->name)
            ->action('Semnează Contractul', $this->signingUrl)
            ->line('Semnarea se realizează prin validare SMS, conform EU eIDAS.')
            ->line('Link-ul este valabil 30 de zile.')
            ->line('Dacă nu recunoașteți acest contract, vă rugăm să ignorați acest email.');
    }
}
