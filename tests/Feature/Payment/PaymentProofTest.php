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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $rider;
    private User $admin;
    private Branch $branch;
    private Order $order;
    private PaymentHistory $paymentHistory;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock AfricasTalkingService
        $this->mock(AfricasTalkingService::class, function ($mock) {
            $mock->shouldReceive('sendSms')->andReturn(true);
        });

        // Create a branch
        $this->branch = Branch::factory()->create();

        // Create a rider
        $this->rider = User::factory()->create([
            'role' => 'rider',
            'branch_id' => $this->branch->id,
        ]);

        // Create an admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create a payment history for the rider
        $this->paymentHistory = \App\Models\PaymentHistory::factory()->create([
            'user_id' => $this->rider->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_rider_can_upload_payment_proof()
    {
        $proofData = [
            'payment_history_id' => $this->paymentHistory->id,
            'payment_amount' => 10000,
            'payment_method' => PaymentMethodEnum::BankTransfer->value,
            'reference' => 'REF123456',
            'proof_url' => 'http://example.com/proof.jpg',
        ];

        $response = $this->actingAs($this->rider)
            ->postJson('/api/payment-proofs', $proofData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'payment_history_id',
                    'proof_url',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('payment_proofs', [
            'payment_history_id' => $this->paymentHistory->id,
            'status' => ProofStatusEnum::Pending->value,
        ]);
    }

    public function test_admin_can_approve_payment_proof()
    {
        $paymentProof = PaymentProof::factory()->create([
            'payment_history_id' => $this->paymentHistory->id,
            'status' => ProofStatusEnum::Pending->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/payment-proofs/{$paymentProof->id}/approve");

        $response->assertOk()
            ->assertJson([
                'message' => 'Payment proof approved successfully.',
            ]);

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $paymentProof->id,
            'status' => ProofStatusEnum::Approved->value,
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_reject_payment_proof()
    {
        $paymentProof = PaymentProof::factory()->create([
            'payment_history_id' => $this->paymentHistory->id,
            'status' => ProofStatusEnum::Pending->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/payment-proofs/{$paymentProof->id}/reject");

        $response->assertOk()
            ->assertJson([
                'message' => 'Payment proof rejected successfully.',
            ]);

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $paymentProof->id,
            'status' => ProofStatusEnum::Rejected->value,
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_rider_cannot_access_admin_endpoints()
    {
        $paymentProof = PaymentProof::factory()->create([
            'payment_history_id' => $this->paymentHistory->id,
            'status' => ProofStatusEnum::Pending->value,
        ]);

        $response = $this->actingAs($this->rider)
            ->postJson("/api/admin/payment-proofs/{$paymentProof->id}/approve");

        $response->assertForbidden();
    }

    public function test_rider_cannot_upload_proof_for_another_rider()
    {
        $anotherRider = User::factory()->create([
            'role' => 'rider',
            'branch_id' => $this->branch->id,
        ]);

        $anotherPaymentHistory = \App\Models\PaymentHistory::factory()->create([
            'user_id' => $anotherRider->id,
            'branch_id' => $this->branch->id,
        ]);

        $proofData = [
            'payment_history_id' => $anotherPaymentHistory->id,
            'payment_amount' => 10000,
            'payment_method' => PaymentMethodEnum::BankTransfer->value,
            'reference' => 'REF123456',
            'proof_url' => 'http://example.com/proof.jpg',
        ];

        $response = $this->actingAs($this->rider)
            ->postJson('/api/payment-proofs', $proofData);

        $response->assertForbidden();
    }
} 