<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

class TwilioSmsService
{
    protected $client;
    protected $fromNumber;

    public function __construct()
    {
        // Log all configuration values (except API key)
        Log::info('Initializing Twilio SMS Service', [
            'account_sid' => config('services.twilio.account_sid'),
            'from_number' => config('services.twilio.from_number'),
        ]);

        try {
            $this->client = $this->createClient();
            $this->fromNumber = config('services.twilio.from_number');
            Log::info('Twilio SDK initialized successfully');
        } catch (\Throwable $e) {
            Log::error('Failed to initialize Twilio SDK', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create a new Twilio client
     * This method is extracted to make testing easier
     * 
     * @return Client
     */
    protected function createClient(): Client
    {
        return new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
    }

    /**
     * Send an SMS message
     *
     * @param string|array $recipients Phone number(s) to send to
     * @param string $message Message content
     * @param string|null $from Sender phone number (optional)
     * @return array Response from Twilio
     */
    public function send($recipients, string $message, ?string $from = null): array
    {
        try {
            // Format recipients to ensure it's an array of strings
            $to = is_array($recipients) ? $recipients : [$recipients];
            $from = $from ?: $this->fromNumber;
            $responses = [];
            
            // Send message to each recipient
            foreach ($to as $recipient) {
                // Send the message using Twilio
                $message = $this->client->messages->create(
                    $recipient,
                    [
                        'from' => $from,
                        'body' => $message,
                    ]
                );
                
                // Process the response
                $responses[] = [
                    'sid' => $message->sid,
                    'status' => $message->status,
                    'to' => $message->to,
                    'from' => $message->from,
                    'price' => $message->price,
                    'errorCode' => $message->errorCode,
                    'errorMessage' => $message->errorMessage,
                ];
            }
            
            // Log success
            Log::info('SMS sent via Twilio', [
                'to' => $to,
                'message_preview' => substr($message, 0, 30) . '...',
                'responses' => $responses
            ]);
            
            return [
                'success' => true,
                'data' => $responses
            ];
        } catch (Throwable $e) {
            // Log error
            Log::error('Failed to send SMS via Twilio', [
                'to' => $recipients,
                'message_preview' => substr($message, 0, 30) . '...',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send order confirmation SMS to rider
     *
     * @param string $phone Rider's phone number
     * @param string $name Rider's name
     * @param string $orderId Order ID
     * @param string $productName Product name
     * @param float $amount Amount
     * @return array
     */
    public function sendOrderConfirmation(string $phone, string $name, string $orderId, string $productName, float $amount): array
    {
        $message = "Hello $name, your order #$orderId for $productName has been confirmed. Amount: NGN" . number_format($amount, 2) . ". Thank you for choosing Gasopay!";
        
        return $this->send($phone, $message);
    }

    /**
     * Send payment receipt SMS
     *
     * @param string $phone Recipient's phone number
     * @param string $name Recipient's name
     * @param string $orderId Order ID
     * @param float $amountPaid Amount paid
     * @param float $balance Outstanding balance (if any)
     * @return array
     */
    public function sendPaymentReceipt(string $phone, string $name, string $orderId, float $amountPaid, float $balance = 0): array
    {
        $message = "Hello $name, we've received your payment of NGN" . number_format($amountPaid, 2) . " for order #$orderId.";
        
        if ($balance > 0) {
            $message .= " Outstanding balance: NGN" . number_format($balance, 2) . ".";
        } else {
            $message .= " Payment completed. Thank you!";
        }
        
        return $this->send($phone, $message);
    }
    
    /**
     * Send verification SMS with OTP
     *
     * @param string $phone Recipient's phone number
     * @param string $otp One-time password
     * @return array
     */
    public function sendVerificationOtp(string $phone, string $otp): array
    {
        $message = "Your Gasopay verification code is: $otp. This code will expire in 10 minutes.";
        
        return $this->send($phone, $message);
    }
    
    /**
     * Send a WhatsApp message instead of SMS
     * Note: Requires a WhatsApp enabled Twilio number
     *
     * @param string $to Recipient's phone number (must be WhatsApp-enabled)
     * @param string $message Message content
     * @return array
     */
    public function sendWhatsApp(string $to, string $message): array
    {
        try {
            // Send the message using Twilio WhatsApp
            // Note: Format for WhatsApp is "whatsapp:+1234567890"
            $whatsappTo = "whatsapp:{$to}";
            $whatsappFrom = "whatsapp:{$this->fromNumber}";
            
            $message = $this->client->messages->create(
                $whatsappTo,
                [
                    'from' => $whatsappFrom,
                    'body' => $message,
                ]
            );
            
            // Process the response
            $response = [
                'sid' => $message->sid,
                'status' => $message->status,
                'to' => $message->to,
                'from' => $message->from,
                'price' => $message->price,
            ];
            
            // Log success
            Log::info('WhatsApp message sent via Twilio', [
                'to' => $to,
                'message_preview' => substr($message, 0, 30) . '...',
                'response' => $response
            ]);
            
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (Throwable $e) {
            // Log error
            Log::error('Failed to send WhatsApp message via Twilio', [
                'to' => $to,
                'message_preview' => substr($message, 0, 30) . '...',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 