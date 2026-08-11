<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_connections', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('name')->nullable();
            $table->string('external_id')->nullable();
            $table->text('access_token')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index('provider');
        });

        Schema::create('imported_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 40);
            $table->string('external_id');
            $table->string('kind', 20);
            $table->decimal('amount', 14, 2);
            $table->date('transaction_date');
            $table->string('name');
            $table->string('merchant_name')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('match_type', 30)->nullable();
            $table->unsignedBigInteger('matched_sale_id')->nullable();
            $table->unsignedBigInteger('matched_expense_id')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index(['kind', 'transaction_date']);
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->json('events');
            $table->string('secret');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('imported_transactions');
        Schema::dropIfExists('integration_connections');
    }
};
