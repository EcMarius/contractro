<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * SMS Service for signature verification
 * Supports multiple providers: Twilio, ClickSend, SMS Link Romania
 */
class SmsService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'twilio');
        $this->config = config("services.sms.{$this->provider}", []);
    }

    /**
     * Send SMS verification code
     */
    public function sendVerificationCode(string $phone, string $code): bool
    {
        $message = "Codul tau de verificare ContractRO este: {$code}. Valabil 15 minute.";

        return $this->send($phone, $message);
    }

    /**
     * Send SMS notification
     */
    public function send(string $phone, string $message): bool
    {
        // Clean phone number - ensure +40 format for Romania
        $cleanPhone = $this->cleanPhoneNumber($phone);

        try {
            return match($this->provider) {
                'twilio' => $this->sendViaTwilio($cleanPhone, $message),
                'clicksend' => $this->sendViaClickSend($cleanPhone, $message),
                'smslink' => $this->sendViaSMSLink($cleanPhone, $message),
                'log' => $this->sendViaLog($cleanPhone, $message),
                default => throw new \Exception("Unsupported SMS provider: {$this->provider}"),
            };
        } catch (\Exception $e) {
            Log::error('SMS send error', [
                'phone' => $cleanPhone,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send via Twilio (most popular, works worldwide)
     */
    protected function sendViaTwilio(string $phone, string $message): bool
    {
        $accountSid = $this->config['account_sid'] ?? '';
        $authToken = $this->config['auth_token'] ?? '';
        $fromNumber = $this->config['from_number'] ?? '';

        if (empty($accountSid) || empty($authToken)) {
            throw new \Exception('Twilio credentials not configured');
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'To' => $phone,
                'From' => $fromNumber,
                'Body' => $message,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Twilio API error: ' . $response->body());
        }

        Log::info('SMS sent via Twilio', [
            'phone' => $phone,
            'sid' => $response->json()['sid'] ?? null,
        ]);

        return true;
    }

    /**
     * Send via ClickSend (good alternative, competitive pricing)
     */
    protected function sendViaClickSend(string $phone, string $message): bool
    {
        $username = $this->config['username'] ?? '';
        $apiKey = $this->config['api_key'] ?? '';

        if (empty($username) || empty($apiKey)) {
            throw new \Exception('ClickSend credentials not configured');
        }

        $response = Http::withBasicAuth($username, $apiKey)
            ->post('https://rest.clicksend.com/v3/sms/send', [
                'messages' => [
                    [
                        'to' => $phone,
                        'body' => $message,
                        'from' => $this->config['from'] ?? 'ContractRO',
                    ]
                ]
            ]);

        if (!$response->successful()) {
            throw new \Exception('ClickSend API error: ' . $response->body());
        }

        Log::info('SMS sent via ClickSend', ['phone' => $phone]);

        return true;
    }

    /**
     * Send via SMS Link (Romanian provider, good for local delivery)
     */
    protected function sendViaSMSLink(string $phone, string $message): bool
    {
        $connectionId = $this->config['connection_id'] ?? '';
        $password = $this->config['password'] ?? '';

        if (empty($connectionId) || empty($password)) {
            throw new \Exception('SMS Link credentials not configured');
        }

        $response = Http::get('https://www.smslink.ro/get/', [
            'connection_id' => $connectionId,
            'password' => $password,
            'to' => $phone,
            'message' => $message,
        ]);

        if (!$response->successful() || !str_contains($response->body(), 'Message sent')) {
            throw new \Exception('SMS Link error: ' . $response->body());
        }

        Log::info('SMS sent via SMS Link', ['phone' => $phone]);

        return true;
    }

    /**
     * Development mode - just log SMS (for testing)
     */
    protected function sendViaLog(string $phone, string $message): bool
    {
        Log::info('SMS (development mode)', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Clean and format phone number to +40 Romanian format
     */
    protected function cleanPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $clean = preg_replace('/[^0-9+]/', '', $phone);

        // If starts with 07, convert to +407
        if (str_starts_with($clean, '07')) {
            return '+4' . $clean;
        }

        // If starts with 407, add +
        if (str_starts_with($clean, '407')) {
            return '+' . $clean;
        }

        // If already has +40, return as is
        if (str_starts_with($clean, '+40')) {
            return $clean;
        }

        // Default: assume it's Romanian and add +40
        return '+40' . ltrim($clean, '0');
    }

    /**
     * Validate Romanian phone number format
     */
    public function isValidRomanianPhone(string $phone): bool
    {
        $clean = $this->cleanPhoneNumber($phone);

        // Romanian mobile numbers: +407XX XXX XXX (10 digits after +40)
        return preg_match('/^\+407[0-9]{8}$/', $clean) === 1;
    }
}
