<?php

use App\Models\Instructor;
use App\Models\Payout;
use App\Jobs\ProcessSingleInstructorPayout;
use App\Services\PaymentProvider\MockPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ensures running payout process twice never double-pays', function () {
    $instructor = Instructor::factory()->create();
    $payout = Payout::create([
        'instructor_id' => $instructor->id,
        'amount_cents' => 5000,
        'status' => 'pending',
        'idempotency_key' => 'payout_unique_key_1001'
    ]);

    $mockProvider = Mockery::mock(MockPaymentProvider::class);
    $mockProvider->shouldReceive('processPayout')->once()->andReturn(MockPaymentProvider::STATUS_SUCCESS);

    (new ProcessSingleInstructorPayout($payout))->handle($mockProvider);

    (new ProcessSingleInstructorPayout($payout))->handle($mockProvider);

    expect($instructor->ledgerEntries()->where('type', 'debit')->count())->toBe(1);
});