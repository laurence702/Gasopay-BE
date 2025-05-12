<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\TwilioSmsService;
use Illuminate\Support\Facades\Artisan;
use Mockery;

class TwilioCommandTest extends TestCase
{
    public function testTwilioCommandBasic()
    {
        // Mock the TwilioSmsService
        $twilioServiceMock = Mockery::mock(TwilioSmsService::class);
        
        // Set up expectations
        $twilioServiceMock->shouldReceive('send')
            ->once()
            ->with('+2348131361241', 'This is a test message from Gasopay.')
            ->andReturn([
                'success' => true,
                'data' => [
                    [
                        'sid' => 'SM123456',
                        'status' => 'queued',
                        'to' => '+2348131361241'
                    ]
                ]
            ]);
        
        // Bind the mock to the container
        $this->app->instance(TwilioSmsService::class, $twilioServiceMock);
        
        // Call the command
        $this->artisan('twilio:test', [
            'phone' => '+2348131361241',
            '--type' => 'basic'
        ])->assertExitCode(0);
    }
    
    public function testTwilioCommandOrderConfirmation()
    {
        // Mock the TwilioSmsService
        $twilioServiceMock = Mockery::mock(TwilioSmsService::class);
        
        // Set up expectations
        $twilioServiceMock->shouldReceive('sendOrderConfirmation')
            ->once()
            ->with('+2348131361241', 'Test Customer', '12345', 'CNG', 10000)
            ->andReturn([
                'success' => true,
                'data' => [
                    [
                        'sid' => 'SM123456',
                        'status' => 'queued',
                        'to' => '+2348131361241'
                    ]
                ]
            ]);
        
        // Bind the mock to the container
        $this->app->instance(TwilioSmsService::class, $twilioServiceMock);
        
        // Call the command
        $this->artisan('twilio:test', [
            'phone' => '+2348131361241',
            '--type' => 'order'
        ])->assertExitCode(0);
    }
    
    public function testTwilioCommandWhatsApp()
    {
        // Mock the TwilioSmsService
        $twilioServiceMock = Mockery::mock(TwilioSmsService::class);
        
        // Set up expectations
        $twilioServiceMock->shouldReceive('sendWhatsApp')
            ->once()
            ->with('+2348131361241', 'This is a test WhatsApp message from Gasopay.')
            ->andReturn([
                'success' => true,
                'data' => [
                    'sid' => 'SM123456',
                    'status' => 'queued',
                    'to' => 'whatsapp:+2348131361241'
                ]
            ]);
        
        // Bind the mock to the container
        $this->app->instance(TwilioSmsService::class, $twilioServiceMock);
        
        // Call the command
        $this->artisan('twilio:test', [
            'phone' => '+2348131361241',
            '--whatsapp' => true
        ])->assertExitCode(0);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 