<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send order created notification to rider
     * 
     * @param Order $order The created order
     * @return void
     */
    public function sendOrderCreatedNotification(Order $order): void
    {
        try {
            $rider = $order->payer;
            if (!$rider) {
                Log::warning('Rider not found for order', ['order_id' => $order->id]);
                return;
            }

            // Send SMS notification if phone is available
            if ($rider->phone) {
                $this->smsService->sendOrderConfirmation(
                    $rider->phone,
                    $rider->fullname,
                    $order->id,
                    $order->product,
                    $order->amount_due
                );
                Log::info('Order SMS notification sent', ['order_id' => $order->id, 'rider_id' => $rider->id]);
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
     * @return void
     */
    public function sendPaymentNotification(Order $order, float $amountPaid, float $balance = 0): void
    {
        try {
            $rider = $order->payer;
            if (!$rider) {
                Log::warning('Rider not found for payment', ['order_id' => $order->id]);
                return;
            }

            // Send SMS notification if phone is available
            if ($rider->phone) {
                $this->smsService->sendPaymentReceipt(
                    $rider->phone,
                    $rider->fullname,
                    $order->id,
                    $amountPaid,
                    $balance
                );
                Log::info('Payment SMS notification sent', ['order_id' => $order->id, 'rider_id' => $rider->id]);
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
     * @return void
     */
    public function sendVerificationNotification(User $rider, string $status): void
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

            $this->smsService->send($rider->phone, $message);
            Log::info('Verification SMS notification sent', ['rider_id' => $rider->id, 'status' => $status]);
            
            // Here you can add more notification channels (email, push, etc.)
            
        } catch (\Throwable $e) {
            Log::error('Failed to send verification notification', [
                'rider_id' => $rider->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 