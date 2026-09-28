<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class RevenueAllocationService
{
    /**
     * Splits net subscription amount between instructors after platform cut (e.g. 30%).
     */
    public function allocateSubscriptionPayment(Subscription $subscription, array $instructorCourseShares, float $platformCutPercentage = 0.30): void
    {
        DB::transaction(function () use ($subscription, $instructorCourseShares, $platformCutPercentage) {
            $totalCents = $subscription->amount_cents;
            $platformCutCents = (int) round($totalCents * $platformCutPercentage);
            $distributableCents = $totalCents - $platformCutCents;

            $totalInstructors = count($instructorCourseShares);
            $baseShare = intdiv($distributableCents, $totalInstructors);
            $remainderCents = $distributableCents % $totalInstructors;

            foreach ($instructorCourseShares as $index => $instructorId) {
                $allocatedAmount = $baseShare + ($index < $remainderCents ? 1 : 0);

                LedgerEntry::create([
                    'instructor_id' => $instructorId,
                    'subscription_id' => $subscription->id,
                    'type' => 'credit',
                    'amount_cents' => $allocatedAmount,
                    'idempotency_key' => "sub_{$subscription->id}_inst_{$instructorId}_allocation",
                    'metadata' => ['platform_cut_cents' => $platformCutCents]
                ]);
            }
        });
    }
}