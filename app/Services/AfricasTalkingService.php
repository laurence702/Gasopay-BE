<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Interfaces\SmsServiceInterface;

class AfricasTalkingService implements SmsServiceInterface
{
    protected string $username;
    protected string $apiKey;
    protected string $senderId;
    protected string $baseUrl = 'https://api.africastalking.com/version1/messaging';
    
    public function __construct()
    {
        $this->username = config('services.africastalking.username');
        $this->apiKey = config('services.africastalking.api_key');
        $this->senderId = config('services.africastalking.sender_id');
    }
    
    /**
     * Send an SMS message
     *
     * @param string $to Recipient phone number (international format without +)
     * @param string $message The message to send
     * @return bool Success status
     */
    public function send(string $to, string $message): bool
    {
        try {
            // Format the phone number - AfricasTalking requires + prefix
            if (!str_starts_with($to, '+')) {
                $to = '+' . $to;
            }
            
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'ApiKey' => $this->apiKey,
            ])->asForm()->post($this->baseUrl, [
                'username' => $this->username,
                'to' => $to,
                'message' => $message,
                'from' => $this->senderId,
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['SMSMessageData']['Recipients'][0]['status']) && 
                    $result['SMSMessageData']['Recipients'][0]['status'] === 'Success') {
                    return true;
                }
                
                Log::warning('AfricasTalking SMS not delivered', [
                    'response' => $result,
                    'to' => $to,
                ]);
                return false;
            } else {
                Log::error('AfricasTalking SMS API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $to,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('AfricasTalking SMS exception: ' . $e->getMessage(), [
                'exception' => $e,
                'to' => $to,
            ]);
            return false;
        }
    }
    
    /**
     * Send bulk SMS messages
     * 
     * @param array $recipients Array of phone numbers
     * @param string $message The message to send
     * @return bool Success status
     */
    public function sendBulk(array $recipients, string $message): bool
    {
        try {
            // Format all phone numbers with + prefix
            $formattedRecipients = array_map(function($number) {
                return !str_starts_with($number, '+') ? '+' . $number : $number;
            }, $recipients);
            
            $to = implode(',', $formattedRecipients);
            
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'ApiKey' => $this->apiKey,
            ])->asForm()->post($this->baseUrl, [
                'username' => $this->username,
                'to' => $to,
                'message' => $message,
                'from' => $this->senderId,
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['SMSMessageData']['Recipients']) && 
                    count($result['SMSMessageData']['Recipients']) > 0) {
                    return true;
                }
                
                Log::warning('AfricasTalking bulk SMS not delivered', [
                    'response' => $result,
                    'recipients' => $recipients,
                ]);
                return false;
            } else {
                Log::error('AfricasTalking bulk SMS API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'recipients' => $recipients,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('AfricasTalking bulk SMS exception: ' . $e->getMessage(), [
                'exception' => $e,
                'recipients' => $recipients,
            ]);
            return false;
        }
    }
} 