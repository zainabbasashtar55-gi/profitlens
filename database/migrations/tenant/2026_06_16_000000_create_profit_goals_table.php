<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_goals', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 16)->default('month'); // month (room to grow: quarter/year)
            $table->date('period_start');                         // first day of the goal period
            $table->decimal('target_amount', 14, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One goal per period type + start (e.g. one monthly goal per month).
            $table->unique(['period_type', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_goals');
    }
};
