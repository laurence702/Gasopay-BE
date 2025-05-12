<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test {phone} {--message=} {--t|type=basic}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the SMS service with different message types';

    /**
     * Execute the console command.
     */
    public function handle(SmsService $smsService)
    {
        $phone = $this->argument('phone');
        $type = $this->option('type');
        $message = $this->option('message');

        $this->info("Sending a test SMS to: {$phone}");

        try {
            $result = match($type) {
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

            if ($result['success']) {
                $this->info("SMS sent successfully!");
                
                // Dump the raw response for debugging
                $this->line('Raw response:');
                $this->info(json_encode($result['data'], JSON_PRETTY_PRINT));
                
                try {
                    $this->table(['Key', 'Value'], $this->flattenArray($result['data']));
                } catch (\Throwable $e) {
                    $this->error("Error displaying response table: " . $e->getMessage());
                    $this->warn("This is just a display error, the SMS was still processed.");
                }
            } else {
                $this->error("Failed to send SMS: {$result['error']}");
            }
        } catch (\Throwable $e) {
            $this->error("An error occurred: {$e->getMessage()}");
            Log::error("SMS Test Command Error", ['exception' => $e]);
        }

        return 0;
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
                $result[] = [$prefix . $key, $value];
            }
        }
        return $result;
    }
} 