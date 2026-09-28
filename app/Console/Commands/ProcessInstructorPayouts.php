<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSingleInstructorPayout;
use App\Models\Instructor;
use App\Models\Payout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessInstructorPayouts extends Command
{
    protected $signature = 'payouts:process';
    protected $description = 'Calculate outstanding instructor balances and trigger payout jobs';

    public function handle(): int
    {
        $this->info('Starting payout processing cycle...');

        $instructors = Instructor::query()
            ->withSum(['ledgerEntries as total_credits' => fn ($q) => $q->where('type', 'credit')], 'amount_cents')
            ->withSum(['ledgerEntries as total_debits' => fn ($q) => $q->where('type', 'debit')], 'amount_cents')
            ->get();

        $processedCount = 0;
        $periodKey = now()->format('Y-m'); 

        foreach ($instructors as $instructor) {
            $earnedCents = $instructor->total_credits ?? 0;
            $paidCents = $instructor->total_debits ?? 0;
            $outstandingBalance = $earnedCents - $paidCents;

            if ($outstandingBalance <= 0) {
                continue;
            }

            $idempotencyKey = "payout_inst_{$instructor->id}_{$periodKey}";

            $payout = DB::transaction(function () use ($instructor, $outstandingBalance, $idempotencyKey) {
                return Payout::firstOrCreate(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'instructor_id' => $instructor->id,
                        'amount_cents'  => $outstandingBalance,
                        'status'        => 'pending',
                    ]
                );
            });

            if ($payout->status === 'pending') {
                ProcessSingleInstructorPayout::dispatch($payout);
                $processedCount++;
            }
        }

        $this->info("Payout cycle completed. Dispatched {$processedCount} payout jobs.");

        return Command::SUCCESS;
    }
}