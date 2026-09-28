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
        Schema::create('ledger_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
    $table->foreignId('subscription_id')->nullable()->constrained();
    $table->foreignId('payout_id')->nullable()->constrained();
    $table->string('type'); 
    $table->bigInteger('amount_cents'); 
    $table->string('idempotency_key')->unique(); 
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->index(['instructor_id', 'created_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
