<?php

namespace Tests\Unit;

use App\Models\Order;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class OrderReferenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_valid_order_reference()
    {
        $order = Order::factory()->create();
        
        $this->assertNotNull($order->order_reference);
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-[A-Z0-9]{5}$/', $order->order_reference);
        $this->assertLessThanOrEqual(14, strlen($order->order_reference));
    }

    /** @test */
    public function it_generates_unique_order_references()
    {
        $order1 = Order::factory()->create();
        $order2 = Order::factory()->create();
        
        $this->assertNotEquals($order1->order_reference, $order2->order_reference);
    }

    /** @test */
    public function it_validates_order_reference_format()
    {
        $rules = Order::rules()['order_reference'];
        
        // Valid reference
        $this->assertTrue(Validator::make(
            ['order_reference' => 'ORD-2302-ABC12'],
            ['order_reference' => $rules]
        )->passes());

        // Invalid format
        $this->assertFalse(Validator::make(
            ['order_reference' => 'INVALID-REF'],
            ['order_reference' => $rules]
        )->passes());

        // Too short
        $this->assertFalse(Validator::make(
            ['order_reference' => 'ORD-2302-A'],
            ['order_reference' => $rules]
        )->passes());

        // Too long
        $this->assertFalse(Validator::make(
            ['order_reference' => 'ORD-2302-ABCDEFGHIJK'],
            ['order_reference' => $rules]
        )->passes());
    }

    /** @test */
    public function it_prevents_duplicate_order_references()
    {
        $order1 = Order::factory()->create();
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Order::factory()->create([
            'order_reference' => $order1->order_reference
        ]);
    }

    /** @test */
    public function it_uses_provided_order_reference()
    {
        $customReference = 'ORD-2302-CUSTOM';
        $order = Order::factory()->create([
            'order_reference' => $customReference
        ]);
        
        $this->assertEquals($customReference, $order->order_reference);
    }
} 