<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount_cents');
            
            // Explicit status values with default 'pending'
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'unknown_timeout',
            ])->default('pending')->index();

            // Idempotency key with unique index
            $table->string('idempotency_key')->unique();
            
            $table->string('external_provider_reference')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};