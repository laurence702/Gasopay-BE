<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\PaymentHistory;
use App\Models\PaymentProof;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create additional branches
        $branches = [
            ['name' => 'Test Branch 1', 'location' => 'Test Location 1', 'branch_phone' => '08012345678'],
            ['name' => 'Test Branch 2', 'location' => 'Test Location 2', 'branch_phone' => '08023456789'],
            ['name' => 'Test Branch 3', 'location' => 'Test Location 3', 'branch_phone' => '08034567890'],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }

        // Create test admin user
        $admin = User::create([
            'fullname' => 'Test Admin',
            'phone' => '08011111111',
            'email' => 'test.admin@example.com',
            'role' => 'admin',
            'branch_id' => Branch::first()->id,
            'password' => Hash::make('password'),
            'verification_status' => 'verified',
        ]);

        // Create test riders
        $riders = [];
        for ($i = 1; $i <= 5; $i++) {
            $rider = User::create([
                'fullname' => "Test Rider {$i}",
                'phone' => "0802{$i}000000",
                'email' => "test.rider{$i}@example.com",
                'role' => 'rider',
                'branch_id' => Branch::inRandomOrder()->first()->id,
                'password' => Hash::make('password'),
                'verification_status' => 'verified',
            ]);

            UserProfile::create([
                'user_id' => $rider->id,
                'phone' => $rider->phone,
                'address' => "123 Test Rider {$i} Street, Lagos",
                'nin' => "TESTNIN{$i}000000",
                'guarantors_name' => "Test Guarantor {$i}",
                'guarantors_address' => "456 Test Guarantor {$i} Street, Lagos",
                'guarantors_phone' => "0803{$i}000000",
                'vehicle_type' => ['keke', 'car'][rand(0, 1)],
                'profile_pic_url' => null,
                'barcode' => Str::random(10),
            ]);

            $riders[] = $rider;
        }

        // Create test regular users
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'fullname' => "Test User {$i}",
                'phone' => "0804{$i}000000",
                'email' => "test.user{$i}@example.com",
                'role' => 'regular',
                'password' => Hash::make('password'),
                'verification_status' => 'verified',
            ]);
            $users[] = $user;
        }

        // Create products
        $products = [
            ['name' => 'Test Keke Napep', 'unit' => 'unit', 'description' => 'Test Tricycle', 'price' => 5000.00],
            ['name' => 'Test Car', 'unit' => 'unit', 'description' => 'Test Car', 'price' => 10000.00],
            ['name' => 'Test CNG', 'unit' => 'kg', 'description' => 'Test Compressed Natural Gas', 'price' => 200.00],
            ['name' => 'Test PMS', 'unit' => 'litre', 'description' => 'Test Premium Motor Spirit', 'price' => 650.00],
            ['name' => 'Test LPG', 'unit' => 'kg', 'description' => 'Test Liquefied Petroleum Gas', 'price' => 850.00],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Create orders with payment histories and proofs
        $paymentMethods = ['cash', 'bank_transfer', 'wallet'];
        $paymentStatuses = ['pending', 'paid', 'failed', 'completed', 'approved', 'rejected'];
        $products = ['keke', 'car', 'cng', 'pms', 'lpg'];

        for ($i = 1; $i <= 50; $i++) {
            $order = Order::create([
                'order_reference' => 'TEST' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'payer_id' => $users[array_rand($users)]->id,
                'created_by' => $riders[array_rand($riders)]->id,
                'branch_id' => Branch::inRandomOrder()->first()->id,
                'product' => $products[array_rand($products)],
                'amount_due' => rand(1000, 10000),
                'payment_type' => ['full', 'part'][rand(0, 1)],
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            // Create payment history
            $paymentHistory = PaymentHistory::create([
                'order_id' => $order->id,
                'user_id' => $order->payer_id,
                'amount' => $order->amount_due,
                'payment_method' => $order->payment_method,
                'status' => $order->payment_status,
                'reference' => 'TESTPAY' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'approved_by' => $order->payment_status === 'approved' ? $admin->id : null,
                'approved_at' => $order->payment_status === 'approved' ? now() : null,
                'branch_id' => $order->branch_id,
                'created_at' => $order->created_at,
            ]);

            // Create payment proof if payment method is bank_transfer
            if ($order->payment_method === 'bank_transfer') {
                PaymentProof::create([
                    'payment_history_id' => $paymentHistory->id,
                    'proof_url' => 'https://example.com/proofs/test-' . Str::random(10) . '.jpg',
                    'status' => $order->payment_status === 'approved' ? 'approved' : 'pending',
                    'approved_by' => $order->payment_status === 'approved' ? $admin->id : null,
                    'approved_at' => $order->payment_status === 'approved' ? now() : null,
                ]);
            }
        }
    }
} 