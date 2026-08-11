<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 32)->unique(); // INV-2026-0001 (tenant-scoped sequence)
            $table->string('public_token', 64)->unique();   // magic-link token
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 16)->default('draft');
            // draft → sent → viewed → paid (terminal). overdue is a *computed* sub-state of sent/viewed.
            // void is terminal (cancelled / replaced).

            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->decimal('subtotal',    14, 2)->default(0);
            $table->decimal('tax_total',   14, 2)->default(0);
            $table->decimal('total',       14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);

            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->text('payment_terms')->nullable();
            $table->string('pdf_path')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index(['customer_id', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity',   12, 3)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('unit_cost',  12, 2)->default(0); // for profit on payment
            $table->decimal('tax_rate',   5, 2)->default(0);  // percent, e.g. 8.50
            $table->decimal('line_total', 14, 2)->default(0); // (qty * price) — pre-tax
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
