<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\InvoiceBatch;
use App\Models\Invoice;
use App\Services\AuditService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Default Admin User
        $user = User::updateOrCreate(
            ['email' => 'owner@almadina.pk'],
            [
                'name' => 'Al-Madina Admin',
                'password' => Hash::make('password123'),
                'role' => 'ADMIN',
                'pos_id' => 'POS-100234',
                'seller_ntn' => '7890123-4',
            ]
        );

        // 2. Seed Initial Sample Invoice Batch
        $batch = InvoiceBatch::create([
            'user_id' => $user->id,
            'file_name' => 'sample_invoices.json',
            'file_type' => 'json',
            'file_size' => 4820,
            'total_count' => 3,
            'valid_count' => 3,
            'invalid_count' => 0,
            'status' => 'COMPLETED',
        ]);

        // Invoice 1
        $inv1 = $batch->invoices()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-001',
            'invoice_date' => now()->subHours(2),
            'seller_ntn' => '7890123-4',
            'seller_name' => 'AL-MADINA TRADERS LTD',
            'seller_pos_id' => 'POS-100234',
            'buyer_name' => 'Tariq Electronics',
            'buyer_ntn' => '1234567-8',
            'buyer_phone' => '03001234567',
            'payment_mode' => '1',
            'total_sale_value' => 15000.00,
            'total_quantity' => 5,
            'total_tax_amount' => 2700.00,
            'discount' => 500.00,
            'further_tax' => 0.00,
            'total_bill' => 17200.00,
            'validation_status' => 'VALID',
            'fbr_status' => 'ACCEPTED',
            'fbr_invoice_number' => 'FBR-20260825-8X9Y1Z2A',
        ]);

        $inv1->items()->createMany([
            [
                'item_code' => 'SKU-101',
                'item_name' => 'LED Ceiling Panel 24W',
                'pct_code' => '9405.1000',
                'quantity' => 5,
                'unit_price' => 3100.00,
                'discount' => 500.00,
                'sale_value' => 15000.00,
                'tax_rate' => 18.00,
                'tax_charged' => 2700.00,
                'total_amount' => 17700.00,
            ]
        ]);

        // Invoice 2
        $inv2 = $batch->invoices()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-002',
            'invoice_date' => now()->subHour(1),
            'seller_ntn' => '7890123-4',
            'seller_name' => 'AL-MADINA TRADERS LTD',
            'seller_pos_id' => 'POS-100234',
            'buyer_name' => 'Usman Hardware',
            'buyer_cnic' => '42101-1234567-1',
            'payment_mode' => '2',
            'total_sale_value' => 8000.00,
            'total_quantity' => 10,
            'total_tax_amount' => 1440.00,
            'discount' => 0.00,
            'further_tax' => 0.00,
            'total_bill' => 9440.00,
            'validation_status' => 'VALID',
            'fbr_status' => 'READY',
        ]);

        $inv2->items()->createMany([
            [
                'item_code' => 'SKU-202',
                'item_name' => 'Copper Extension Cord 5M',
                'pct_code' => '8544.4200',
                'quantity' => 10,
                'unit_price' => 800.00,
                'discount' => 0.00,
                'sale_value' => 8000.00,
                'tax_rate' => 18.00,
                'tax_charged' => 1440.00,
                'total_amount' => 9440.00,
            ]
        ]);

        // Invoice 3
        $inv3 = $batch->invoices()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-003',
            'invoice_date' => now(),
            'seller_ntn' => '7890123-4',
            'seller_name' => 'AL-MADINA TRADERS LTD',
            'seller_pos_id' => 'POS-100234',
            'buyer_name' => 'Kamran Superstore',
            'payment_mode' => '1',
            'total_sale_value' => 22000.00,
            'total_quantity' => 2,
            'total_tax_amount' => 3960.00,
            'discount' => 1000.00,
            'further_tax' => 0.00,
            'total_bill' => 24960.00,
            'validation_status' => 'VALID',
            'fbr_status' => 'FAILED',
            'retry_count' => 1,
            'last_error' => 'FBR API connection timeout after 15 seconds.',
        ]);

        $inv3->items()->createMany([
            [
                'item_code' => 'SKU-303',
                'item_name' => 'Industrial Circuit Breaker 63A',
                'pct_code' => '8536.2000',
                'quantity' => 2,
                'unit_price' => 11500.00,
                'discount' => 1000.00,
                'sale_value' => 22000.00,
                'tax_rate' => 18.00,
                'tax_charged' => 3960.00,
                'total_amount' => 25960.00,
            ]
        ]);

        AuditService::log('SYSTEM_SEED', 'User', $user->id);
    }
}
