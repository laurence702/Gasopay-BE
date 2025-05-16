<?php

namespace App\Interfaces;

interface SmsServiceInterface
{
    /**
     * Send an SMS message
     *
     * @param string $to Recipient phone number 
     * @param string $message The message to send
     * @return bool Success status
     */
    public function send(string $to, string $message): bool;
    
    /**
     * Send bulk SMS messages
     * 
     * @param array $recipients Array of phone numbers
     * @param string $message The message to send
     * @return bool Success status
     */
    public function sendBulk(array $recipients, string $message): bool;
} 