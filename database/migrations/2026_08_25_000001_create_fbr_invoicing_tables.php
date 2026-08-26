<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modify or Ensure Users Table has FBR Fields
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('ADMIN');
            }
            if (!Schema::hasColumn('users', 'pos_id')) {
                $table->string('pos_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'seller_ntn')) {
                $table->string('seller_ntn')->nullable();
            }
        });

        // 2. Invoice Batches Table
        Schema::create('invoice_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->integer('total_count')->default(0);
            $table->integer('valid_count')->default(0);
            $table->integer('invalid_count')->default(0);
            $table->string('status')->default('PENDING'); // PENDING, PROCESSING, COMPLETED, FAILED
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });

        // 3. Invoices Table
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('batch_id')->constrained('invoice_batches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->string('invoice_number');
            $table->dateTime('invoice_date');
            $table->string('seller_ntn');
            $table->string('seller_name');
            $table->string('seller_pos_id');

            $table->string('buyer_ntn')->nullable();
            $table->string('buyer_name');
            $table->string('buyer_cnic')->nullable();
            $table->string('buyer_phone')->nullable();

            $table->string('payment_mode')->default('1'); // 1: Cash, 2: Card, 3: Wallet/Cheque
            $table->double('total_sale_value');
            $table->double('total_quantity');
            $table->double('total_tax_amount');
            $table->double('discount')->default(0);
            $table->double('further_tax')->default(0);
            $table->double('total_bill');

            // Validation State
            $table->string('validation_status')->default('PENDING'); // VALID, INVALID, DUPLICATE, MISSING_REQUIRED_FIELD
            $table->text('validation_errors')->nullable(); // JSON

            // FBR State Machine: PENDING -> VALIDATING -> READY -> PROCESSING -> SUBMITTED -> ACCEPTED -> FAILED
            $table->string('fbr_status')->default('PENDING');
            $table->string('fbr_invoice_number')->nullable(); // FBR USIN reference
            $table->integer('retry_count')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('fbr_status');
            $table->index('fbr_invoice_number');
            $table->index('created_at');
            $table->index('batch_id');
        });

        // 4. Invoice Items Table
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->onDelete('cascade');

            $table->string('item_code');
            $table->string('item_name');
            $table->string('pct_code'); // FBR Tariff Code
            $table->double('quantity');
            $table->double('unit_price');
            $table->double('discount')->default(0);
            $table->double('sale_value');
            $table->double('tax_rate');
            $table->double('tax_charged');
            $table->double('total_amount');

            $table->index('invoice_id');
        });

        // 5. FBR Submissions Log Table
        Schema::create('fbr_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignUuid('batch_id')->nullable()->constrained('invoice_batches')->onDelete('set null');

            $table->timestamp('submission_time')->useCurrent();
            $table->string('status'); // SUCCESS, FAILED, RETRIED
            $table->text('request_payload');
            $table->text('response_payload');
            $table->integer('http_status');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('fbr_invoice_number')->nullable();
            $table->string('idempotency_key')->unique();

            $table->timestamps();

            $table->index('invoice_id');
            $table->index('submission_time');
            $table->index('idempotency_key');
        });

        // 6. FBR Responses Table
        Schema::create('fbr_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')->constrained('fbr_submissions')->onDelete('cascade');
            $table->foreignUuid('invoice_id')->constrained('invoices')->onDelete('cascade');

            $table->string('status'); // ACCEPTED, REJECTED, ERROR
            $table->string('response_code');
            $table->text('response_message');
            $table->string('fbr_usin')->nullable();
            $table->text('raw_response');

            $table->timestamps();

            $table->index('submission_id');
            $table->index('invoice_id');
        });

        // 7. Audit Logs Table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // UPLOAD, SUBMIT, RETRY, LOGIN, EXPORT
            $table->string('entity_type');
            $table->string('entity_id')->nullable();
            $table->text('details')->nullable(); // JSON
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('fbr_responses');
        Schema::dropIfExists('fbr_submissions');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_batches');
    }
};
