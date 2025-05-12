<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsService
{
    protected $AT;
    protected $sms;

    public function __construct()
    {
        // Log all configuration values (except API key)
        Log::info('Initializing SMS Service', [
            'username' => config('services.africastalking.username'),
            'sender_id' => config('services.africastalking.from'),
        ]);

        try {
            $this->AT = new AfricasTalking(
                config('services.africastalking.username'),
                config('services.africastalking.api_key')
            );
            $this->sms = $this->AT->sms();
            Log::info('AfricasTalking SDK initialized successfully');
        } catch (\Throwable $e) {
            Log::error('Failed to initialize AfricasTalking SDK', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Send an SMS message
     *
     * @param string|array $recipients Phone number(s) to send to
     * @param string $message Message content
     * @param string|null $from Sender ID (optional)
     * @return array Response from AfricasTalking
     */
    public function send($recipients, string $message, ?string $from = null): array
    {
        try {
            // Format recipients to ensure it's an array of strings
            $to = is_array($recipients) ? $recipients : [$recipients];
            
            // Prepare options
            $options = [
                'to'      => $to,
                'message' => $message,
            ];
            
            // Add sender ID if provided
            if ($from) {
                $options['from'] = $from;
            }
            
            // Send the message
            $response = $this->sms->send($options);
            
            // Log response details
            Log::info('SMS sent', [
                'to' => $to,
                'message_preview' => substr($message, 0, 30) . '...',
                'response' => json_encode($response)
            ]);
            
            // Process the response
            $processedResponse = [];
            
            // Extract the important parts of the response
            if (isset($response['data'])) {
                $smsData = $response['data'];
                if (isset($smsData->SMSMessageData)) {
                    $processedResponse = [
                        'status' => $smsData->SMSMessageData->Recipients[0]->status ?? 'Unknown',
                        'messageId' => $smsData->SMSMessageData->Recipients[0]->messageId ?? 'Unknown',
                        'number' => $smsData->SMSMessageData->Recipients[0]->number ?? 'Unknown',
                        'cost' => $smsData->SMSMessageData->Recipients[0]->cost ?? 'Unknown',
                    ];
                }
            }
            
            return [
                'success' => true,
                'data' => $processedResponse ?: $response // Return processed data or full response if processing failed
            ];
        } catch (Throwable $e) {
            Log::error('Failed to send SMS', [
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
} 