<?php

namespace App\Console\Commands;

use App\Services\TwilioSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestTwilioCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twilio:test {phone} {--message=} {--t|type=basic} {--whatsapp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Twilio SMS service with different message types';

    /**
     * Execute the console command.
     */
    public function handle(TwilioSmsService $smsService)
    {
        $phone = $this->argument('phone');
        $type = $this->option('type');
        $message = $this->option('message');
        $useWhatsApp = $this->option('whatsapp');

        $this->info("Sending a test " . ($useWhatsApp ? "WhatsApp message" : "SMS") . " to: {$phone}");
        
        if ($useWhatsApp) {
            $this->warn("Note: WhatsApp messaging requires a WhatsApp-enabled Twilio number and recipient must have WhatsApp installed.");
        }

        try {
            $result = $useWhatsApp 
                ? $smsService->sendWhatsApp($phone, $message ?? 'This is a test WhatsApp message from Gasopay.')
                : $this->sendSmsBasedOnType($smsService, $type, $phone, $message);

            if ($result['success']) {
                $this->info("Message sent successfully!");
                $this->line('Response:');
                $this->info(json_encode($result['data'], JSON_PRETTY_PRINT));
                
                try {
                    if (is_array($result['data']) && !empty($result['data'])) {
                        $this->table(['Key', 'Value'], $this->flattenArray(is_array($result['data'][0] ?? null) ? $result['data'][0] : $result['data']));
                    }
                } catch (\Throwable $e) {
                    $this->error("Error displaying response table: " . $e->getMessage());
                    $this->warn("This is just a display error, the message was still processed.");
                }
            } else {
                $this->error("Failed to send message: {$result['error']}");
            }
        } catch (\Throwable $e) {
            $this->error("An error occurred: {$e->getMessage()}");
            Log::error("Twilio Test Command Error", ['exception' => $e]);
        }

        return 0;
    }
    
    /**
     * Send the appropriate SMS type based on the option
     */
    private function sendSmsBasedOnType(TwilioSmsService $smsService, string $type, string $phone, ?string $message): array
    {
        return match($type) {
            'order' => $smsService->sendOrderConfirmation(
                $phone, 
                'Test Customer', 
                '12345', 
                'CNG', 
                10000
            ),
            'payment' => $smsService->sendPaymentReceipt(
                $phone, 
                'Test Customer', 
                '12345', 
                5000, 
                5000
            ),
            'otp' => $smsService->sendVerificationOtp(
                $phone, 
                '123456'
            ),
            'basic' => $smsService->send(
                $phone, 
                $message ?? 'This is a test message from Gasopay.'
            ),
            default => throw new \InvalidArgumentException("Invalid message type: {$type}")
        };
    }

    /**
     * Flatten a multi-dimensional array for table display
     */
    private function flattenArray($array, $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $prefix . $key . '.'));
            } else if (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $result[] = [$prefix . $key, (string)$value];
                } else {
                    // For objects that can't be converted to strings, show their class name
                    $result[] = [$prefix . $key, '[Object: ' . get_class($value) . ']'];
                }
            } else {
                $result[] = [$prefix . $key, $value ?? 'null'];
            }
        }
        return $result;
    }
} 