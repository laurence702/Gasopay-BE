<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Services\TwilioSmsService;
use Illuminate\Support\Facades\Log;

class TwilioNotificationService
{
    protected $twilioService;

    public function __construct(TwilioSmsService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Send order created notification to rider
     * 
     * @param Order $order The created order
     * @param bool $useWhatsApp Whether to send via WhatsApp instead of SMS
     * @return void
     */
    public function sendOrderCreatedNotification(Order $order, bool $useWhatsApp = false): void
    {
        try {
            $rider = $order->payer;
            if (!$rider) {
                Log::warning('Rider not found for order', ['order_id' => $order->id]);
                return;
            }

            // Send notification if phone is available
            if ($rider->phone) {
                if ($useWhatsApp) {
                    // Send via WhatsApp
                    $message = "Hello {$rider->fullname}, your order #{$order->id} for {$order->product} has been confirmed. Amount: NGN" . 
                        number_format($order->amount_due, 2) . ". Thank you for choosing Gasopay!";
                    
                    $this->twilioService->sendWhatsApp($rider->phone, $message);
                } else {
                    // Send via SMS
                    $this->twilioService->sendOrderConfirmation(
                        $rider->phone,
                        $rider->fullname,
                        $order->id,
                        $order->product,
                        $order->amount_due
                    );
                }
                
                Log::info('Order notification sent via ' . ($useWhatsApp ? 'WhatsApp' : 'SMS'), [
                    'order_id' => $order->id, 
                    'rider_id' => $rider->id,
                    'channel' => $useWhatsApp ? 'WhatsApp' : 'SMS'
                ]);
            }

            // Here you can add more notification channels (email, push, etc.)
            
        } catch (\Throwable $e) {
            Log::error('Failed to send order notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment notification to rider
     * 
     * @param Order $order The order being paid for
     * @param float $amountPaid Amount paid
     * @param float $balance Outstanding balance
     * @param bool $useWhatsApp Whether to send via WhatsApp instead of SMS
     * @return void
     */
    public function sendPaymentNotification(Order $order, float $amountPaid, float $balance = 0, bool $useWhatsApp = false): void
    {
        try {
            $rider = $order->payer;
            if (!$rider) {
                Log::warning('Rider not found for payment', ['order_id' => $order->id]);
                return;
            }

            // Send notification if phone is available
            if ($rider->phone) {
                if ($useWhatsApp) {
                    // Send via WhatsApp
                    $message = "Hello {$rider->fullname}, we've received your payment of NGN" . 
                        number_format($amountPaid, 2) . " for order #{$order->id}.";
                    
                    if ($balance > 0) {
                        $message .= " Outstanding balance: NGN" . number_format($balance, 2) . ".";
                    } else {
                        $message .= " Payment completed. Thank you!";
                    }
                    
                    $this->twilioService->sendWhatsApp($rider->phone, $message);
                } else {
                    // Send via SMS
                    $this->twilioService->sendPaymentReceipt(
                        $rider->phone,
                        $rider->fullname,
                        $order->id,
                        $amountPaid,
                        $balance
                    );
                }
                
                Log::info('Payment notification sent via ' . ($useWhatsApp ? 'WhatsApp' : 'SMS'), [
                    'order_id' => $order->id, 
                    'rider_id' => $rider->id,
                    'channel' => $useWhatsApp ? 'WhatsApp' : 'SMS'
                ]);
            }
            
            // Here you can add more notification channels (email, push, etc.)
            
        } catch (\Throwable $e) {
            Log::error('Failed to send payment notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send rider verification notification
     * 
     * @param User $rider The rider being verified
     * @param string $status The verification status
     * @param bool $useWhatsApp Whether to send via WhatsApp instead of SMS
     * @return void
     */
    public function sendVerificationNotification(User $rider, string $status, bool $useWhatsApp = false): void
    {
        try {
            if (!$rider->phone) {
                Log::warning('Rider has no phone number for verification notification', ['rider_id' => $rider->id]);
                return;
            }

            $message = match($status) {
                'verified' => "Congratulations! Your Gasopay account has been verified. You can now make orders.",
                'rejected' => "Your Gasopay account verification was not successful. Please contact support for assistance.",
                default => "Your Gasopay account verification status has been updated to {$status}.",
            };

            if ($useWhatsApp) {
                $this->twilioService->sendWhatsApp($rider->phone, $message);
            } else {
                $this->twilioService->send($rider->phone, $message);
            }
            
            Log::info('Verification notification sent via ' . ($useWhatsApp ? 'WhatsApp' : 'SMS'), [
                'rider_id' => $rider->id, 
                'status' => $status,
                'channel' => $useWhatsApp ? 'WhatsApp' : 'SMS'
            ]);
            
            // Here you can add more notification channels (email, push, etc.)
            
        } catch (\Throwable $e) {
            Log::error('Failed to send verification notification', [
                'rider_id' => $rider->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 