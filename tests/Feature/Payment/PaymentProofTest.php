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

        // Mock AfricasTalking service
        $this->mock(\App\Services\AfricasTalkingService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturn(true);
            $mock->shouldReceive('sendBulk')->andReturn(true);
        });

        // Create a branch
        $this->branch = Branch::factory()->create();

        // Create an admin
        $this->admin = User::factory()->create([
            'role' => RoleEnum::Admin,
            'branch_id' => $this->branch->id,
        ]);

        // Create a rider
        $this->rider = User::factory()->create([
            'role' => RoleEnum::Rider,
            'branch_id' => $this->branch->id,
        ]);

        // Create an order for the rider
        $this->order = Order::create([
            'id' => Str::uuid(),
            'payer_id' => $this->rider->id,
            'created_by' => $this->admin->id,
            'branch_id' => $this->branch->id,
            'product' => 'cng',
            'amount_due' => 5000,
            'payment_type' => PaymentTypeEnum::Part,
            'payment_method' => PaymentMethodEnum::Cash,
            'payment_status' => PaymentStatusEnum::Pending,
        ]);
    }

    public function test_rider_can_upload_payment_proof()
    {
        Sanctum::actingAs($this->rider);

        $response = $this->postJson('/api/payment-proofs', [
            'order_id' => $this->order->id,
            'payment_amount' => 2000,
            'proof_url' => 'https://example.com/proof.jpg',
            'payment_method' => PaymentMethodEnum::BankTransfer->value,
            'reference' => 'TRX12345',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Payment proof uploaded successfully. Awaiting admin approval.');

        $this->assertDatabaseHas('payment_histories', [
            'order_id' => $this->order->id,
            'user_id' => $this->rider->id,
            'amount' => 2000,
            'payment_method' => 'bank_transfer',
            'status' => PaymentStatusEnum::Pending,
        ]);

        $this->assertDatabaseHas('payment_proofs', [
            'proof_url' => 'https://example.com/proof.jpg',
            'status' => ProofStatusEnum::Pending,
        ]);
    }

    public function test_admin_can_approve_payment_proof()
    {
        // First create a payment history and proof
        $paymentHistory = PaymentHistory::create([
            'order_id' => $this->order->id,
            'user_id' => $this->rider->id,
            'amount' => 2000,
            'payment_method' => 'bank_transfer',
            'status' => PaymentStatusEnum::Pending,
            'reference' => 'TRX12345',
        ]);

        $paymentProof = PaymentProof::create([
            'payment_history_id' => $paymentHistory->id,
            'proof_url' => 'https://example.com/proof.jpg',
            'status' => ProofStatusEnum::Pending,
        ]);

        // Admin approves the proof
        Sanctum::actingAs($this->admin);
        $response = $this->postJson('/api/payment-proofs/'.$paymentProof->id.'/approve');

        $response->assertOk()
            ->assertJsonPath('message', 'Payment proof approved successfully');

        // Verify payment history was updated
        $this->assertDatabaseHas('payment_histories', [
            'id' => $paymentHistory->id,
            'status' => PaymentStatusEnum::Paid,
            'approved_by' => $this->admin->id,
        ]);

        // Verify payment proof was updated
        $this->assertDatabaseHas('payment_proofs', [
            'id' => $paymentProof->id,
            'status' => ProofStatusEnum::Approved,
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_reject_payment_proof()
    {
        // First create a payment history and proof
        $paymentHistory = PaymentHistory::create([
            'order_id' => $this->order->id,
            'user_id' => $this->rider->id,
            'amount' => 2000,
            'payment_method' => 'bank_transfer',
            'status' => PaymentStatusEnum::Pending,
            'reference' => 'TRX12345',
        ]);

        $paymentProof = PaymentProof::create([
            'payment_history_id' => $paymentHistory->id,
            'proof_url' => 'https://example.com/proof.jpg',
            'status' => ProofStatusEnum::Pending,
        ]);

        // Admin rejects the proof
        Sanctum::actingAs($this->admin);
        $response = $this->postJson('/api/payment-proofs/'.$paymentProof->id.'/reject', [
            'rejection_reason' => 'Blurry image, please upload a clearer one.',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Payment proof rejected successfully');

        // Verify payment history was updated
        $this->assertDatabaseHas('payment_histories', [
            'id' => $paymentHistory->id,
            'status' => PaymentStatusEnum::Rejected,
        ]);

        // Verify payment proof was updated
        $this->assertDatabaseHas('payment_proofs', [
            'id' => $paymentProof->id,
            'status' => ProofStatusEnum::Rejected,
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_rider_cannot_access_admin_endpoints()
    {
        Sanctum::actingAs($this->rider);

        $response = $this->getJson('/api/payment-proofs');
        $response->assertForbidden();

        $paymentProof = PaymentProof::create([
            'payment_history_id' => PaymentHistory::create([
                'order_id' => $this->order->id,
                'user_id' => $this->rider->id,
                'amount' => 2000,
                'payment_method' => 'bank_transfer',
                'status' => PaymentStatusEnum::Pending,
                'reference' => 'TRX12345',
            ])->id,
            'proof_url' => 'https://example.com/proof.jpg',
            'status' => ProofStatusEnum::Pending,
        ]);

        $response = $this->postJson('/api/payment-proofs/'.$paymentProof->id.'/approve');
        $response->assertForbidden();

        $response = $this->postJson('/api/payment-proofs/'.$paymentProof->id.'/reject', [
            'rejection_reason' => 'Test reason',
        ]);
        $response->assertForbidden();
    }

    public function test_rider_cannot_upload_proof_for_another_riders_order()
    {
        $anotherRider = User::factory()->create([
            'role' => RoleEnum::Rider,
            'branch_id' => $this->branch->id,
        ]);

        $anotherOrder = Order::create([
            'id' => Str::uuid(),
            'payer_id' => $anotherRider->id,
            'created_by' => $this->admin->id,
            'branch_id' => $this->branch->id,
            'product' => 'cng',
            'amount_due' => 5000,
            'payment_type' => PaymentTypeEnum::Part,
            'payment_method' => PaymentMethodEnum::Cash,
            'payment_status' => PaymentStatusEnum::Pending,
        ]);

        Sanctum::actingAs($this->rider);

        $response = $this->postJson('/api/payment-proofs', [
            'order_id' => $anotherOrder->id,
            'payment_amount' => 2000,
            'proof_url' => 'https://example.com/proof.jpg',
            'payment_method' => 'bank_transfer',
            'reference' => 'TRX12345',
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('message', 'Payment proof upload failed: Unauthorized to add payment for this order');
    }
} 