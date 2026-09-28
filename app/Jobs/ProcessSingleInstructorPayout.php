<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Models\LedgerEntry;
use App\Services\PaymentProvider\MockPaymentProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessSingleInstructorPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payout $payout) {}

    public function handle(MockPaymentProvider $provider): void
    {
        $this->payout->refresh();

        if (in_array($this->payout->status, ['completed', 'processing'])) {
            return;
        }

        $this->payout->update(['status' => 'processing']);

        try {
            $status = $provider->processPayout(
                $this->payout->id, 
                $this->payout->amount_cents, 
                $this->payout->idempotency_key
            );

            if ($status === MockPaymentProvider::STATUS_SUCCESS) {
                DB::transaction(function () {
                    $payout = Payout::where('id', $this->payout->id)->lockForUpdate()->first();

                    if ($payout->status === 'completed') {
                        return;
                    }

                    $payout->update([
                        'status' => 'completed',
                        'processed_at' => now()
                    ]);

                    LedgerEntry::create([
                        'instructor_id' => $payout->instructor_id,
                        'payout_id' => $payout->id,
                        'type' => 'debit',
                        'amount_cents' => $payout->amount_cents,
                        'idempotency_key' => "payout_ledger_debit_{$payout->id}"
                    ]);
                });
            }
        } catch (\Exception $e) {
            $this->payout->update(['status' => 'unknown_timeout']);
        }
    }
}