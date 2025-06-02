<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\User;
use App\Models\Branch;
use App\Models\PaymentProof;
use App\Models\PaymentHistory;
use App\Enums\RoleEnum;
use App\Enums\ProofStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;
use App\Services\AfricasTalkingService;
use Illuminate\Contracts\Auth\Authenticatable;
use function Pest\Laravel\{getJson, postJson, putJson};
use Mockery;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;
    protected User $rider;
    protected Branch $branch;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock AfricasTalkingService
        $this->mock(AfricasTalkingService::class, function ($mock) {
            $mock->shouldReceive('sendSms')->andReturn(true);
        });

        // Create branch first
        $this->branch = Branch::factory()->create();

        // Create users with proper roles and branch assignments
        $this->admin = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->rider = User::factory()->rider()->create(['branch_id' => $this->branch->id]);

        // Create an order for the rider
        $this->order = Order::factory()->create([
            'payer_id' => $this->rider->id,
            'created_by' => $this->admin->id,
            'branch_id' => $this->branch->id,
            'product' => 'cng',
            'amount_due' => 10000,
            'payment_type' => 'cash',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
        ]);
    }

    public function test_rider_can_upload_payment_proof()
    {
        $this->actingAs($this->rider);

        $proofData = [
            'order_id' => $this->order->id,
            'proof_url' => 'http://example.com/proof.jpg',
        ];

        $response = $this->postJson('/api/payment-proofs', $proofData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_id',
                    'proof_url',
                    'status',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('payment_proofs', [
            'order_id' => $this->order->id,
            'proof_url' => 'http://example.com/proof.jpg',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_payment_proof()
    {
        $this->actingAs($this->admin);

        $proof = PaymentProof::factory()->create([
            'order_id' => $this->order->id,
            'proof_url' => 'http://example.com/proof.jpg',
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/payment-proofs/{$proof->id}/approve");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_id',
                    'proof_url',
                    'status',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $proof->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_admin_can_reject_payment_proof()
    {
        $this->actingAs($this->admin);

        $proof = PaymentProof::factory()->create([
            'order_id' => $this->order->id,
            'proof_url' => 'http://example.com/proof.jpg',
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/payment-proofs/{$proof->id}/reject");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_id',
                    'proof_url',
                    'status',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $proof->id,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_rider_cannot_access_admin_endpoints()
    {
        $this->actingAs($this->rider);

        $proof = PaymentProof::factory()->create([
            'order_id' => $this->order->id,
            'proof_url' => 'http://example.com/proof.jpg',
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/payment-proofs/{$proof->id}/approve");

        $response->assertForbidden();
    }

    public function test_rider_cannot_upload_proof_for_another_rider()
    {
        $this->actingAs($this->rider);

        $otherRider = User::factory()->rider()->create(['branch_id' => $this->branch->id]);
        $otherOrder = Order::factory()->create([
            'payer_id' => $otherRider->id,
            'created_by' => $this->admin->id,
            'branch_id' => $this->branch->id,
            'product' => 'cng',
            'amount_due' => 10000,
            'payment_type' => 'cash',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
        ]);

        $proofData = [
            'order_id' => $otherOrder->id,
            'proof_url' => 'http://example.com/proof.jpg',
        ];

        $response = $this->postJson('/api/payment-proofs', $proofData);

        $response->assertForbidden();
    }
} 