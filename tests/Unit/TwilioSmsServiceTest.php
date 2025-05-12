<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TwilioSmsService;
use Twilio\Rest\Client;
use Mockery;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TwilioSmsServiceTest extends TestCase
{
    protected $twilioServiceMock;
    protected $clientMock;
    protected $messagesMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Twilio Client
        $this->clientMock = Mockery::mock(Client::class);
        $this->messagesMock = Mockery::mock(\stdClass::class);
        $this->clientMock->messages = $this->messagesMock;
        
        // Mock Config
        Config::shouldReceive('get')
            ->with('services.twilio.account_sid')
            ->andReturn('test_account_sid');
            
        Config::shouldReceive('get')
            ->with('services.twilio.auth_token')
            ->andReturn('test_auth_token');
            
        Config::shouldReceive('get')
            ->with('services.twilio.from_number')
            ->andReturn('+12025550123');
        
        // Mock Log
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
        
        // Create a partial mock of TwilioSmsService
        $this->twilioServiceMock = Mockery::mock(TwilioSmsService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
            
        // Set the mocked client
        $this->twilioServiceMock->shouldReceive('createClient')
            ->andReturn($this->clientMock);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function testSendSmsSuccess()
    {
        $recipient = '+2348131361241';
        $message = 'Test message';
        
        // Mock successful message response
        $messageMock = Mockery::mock(\stdClass::class);
        $messageMock->sid = 'SM123456';
        $messageMock->status = 'queued';
        $messageMock->to = $recipient;
        $messageMock->from = '+12025550123';
        $messageMock->price = '0.10';
        $messageMock->errorCode = null;
        $messageMock->errorMessage = null;
        
        // Set up expectations
        $this->messagesMock->shouldReceive('create')
            ->once()
            ->with($recipient, ['from' => '+12025550123', 'body' => $message])
            ->andReturn($messageMock);
        
        // Call the method
        $result = $this->twilioServiceMock->send($recipient, $message);
        
        // Assert result structure
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('SM123456', $result['data'][0]['sid']);
        $this->assertEquals('queued', $result['data'][0]['status']);
    }
    
    public function testSendSmsException()
    {
        $recipient = '+2348131361241';
        $message = 'Test message';
        
        // Mock exception
        $this->messagesMock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Test exception'));
        
        // Call the method
        $result = $this->twilioServiceMock->send($recipient, $message);
        
        // Assert result structure
        $this->assertFalse($result['success']);
        $this->assertEquals('Test exception', $result['error']);
    }
    
    public function testSendOrderConfirmation()
    {
        $phone = '+2348131361241';
        $name = 'Test User';
        $orderId = '12345';
        $productName = 'CNG';
        $amount = 10000;
        
        // Expected message format
        $expectedMessage = "Hello Test User, your order #12345 for CNG has been confirmed. Amount: NGN10,000.00. Thank you for choosing Gasopay!";
        
        // Mock successful message response
        $messageMock = Mockery::mock(\stdClass::class);
        $messageMock->sid = 'SM123456';
        $messageMock->status = 'queued';
        $messageMock->to = $phone;
        $messageMock->from = '+12025550123';
        $messageMock->price = '0.10';
        $messageMock->errorCode = null;
        $messageMock->errorMessage = null;
        
        // Set up expectations
        $this->messagesMock->shouldReceive('create')
            ->once()
            ->with($phone, ['from' => '+12025550123', 'body' => $expectedMessage])
            ->andReturn($messageMock);
        
        // Call the method
        $result = $this->twilioServiceMock->sendOrderConfirmation($phone, $name, $orderId, $productName, $amount);
        
        // Assert result structure
        $this->assertTrue($result['success']);
    }
    
    public function testSendWhatsApp()
    {
        $phone = '+2348131361241';
        $message = 'Test WhatsApp message';
        
        // Expected WhatsApp format
        $whatsappTo = "whatsapp:{$phone}";
        $whatsappFrom = "whatsapp:+12025550123";
        
        // Mock successful message response
        $messageMock = Mockery::mock(\stdClass::class);
        $messageMock->sid = 'SM123456';
        $messageMock->status = 'queued';
        $messageMock->to = $whatsappTo;
        $messageMock->from = $whatsappFrom;
        $messageMock->price = '0.10';
        
        // Set up expectations
        $this->messagesMock->shouldReceive('create')
            ->once()
            ->with($whatsappTo, ['from' => $whatsappFrom, 'body' => $message])
            ->andReturn($messageMock);
        
        // Call the method
        $result = $this->twilioServiceMock->sendWhatsApp($phone, $message);
        
        // Assert result structure
        $this->assertTrue($result['success']);
        $this->assertEquals('SM123456', $result['data']['sid']);
        $this->assertEquals('queued', $result['data']['status']);
    }
} 