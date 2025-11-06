<x-mail::message>
# Mesaj Nou de Contact

Ai primit un mesaj nou prin formularul de contact ContractRO.

**De la:** {{ $senderName }}
**Email:** {{ $senderEmail }}
@if($senderCompany)
**Companie:** {{ $senderCompany }}
@endif
**Subiect:** {{ $contactSubject }}

---

## Mesaj:

{{ $messageContent }}

---

<x-mail::button :url="'mailto:' . $senderEmail">
Răspunde la {{ $senderName }}
</x-mail::button>

Mulțumim,<br>
{{ config('app.name') }}
</x-mail::message>
