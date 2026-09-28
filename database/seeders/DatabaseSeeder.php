<?php

namespace Database\Seeders;

use App\Models\Instructor;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            User::firstOrCreate(
                ['email' => 'admin@admin.com'],
                [
                    'name' => 'Admin User',
                    'password' => bcrypt('password'),
                ]
            );

            Instructor::factory(10)->create()->each(function (Instructor $instructor) {
                
                for ($i = 0; $i < rand(2, 5); $i++) {
                    LedgerEntry::create([
                        'instructor_id' => $instructor->id,
                        'type' => 'credit',
                        'amount_cents' => rand(5000, 20000), // $50.00 to $200.00
                        'idempotency_key' => 'course_sale_' . $instructor->id . '_' . uniqid(),
                        'metadata' => ['description' => 'Course Enrollment Revenue'],
                    ]);
                }

                $payout = Payout::create([
                    'instructor_id' => $instructor->id,
                    'amount_cents' => 5000, // $50.00
                    'status' => 'completed',
                    'idempotency_key' => 'payout_' . $instructor->id . '_' . uniqid(),
                    'processed_at' => now(),
                ]);

                LedgerEntry::create([
                    'instructor_id' => $instructor->id,
                    'payout_id' => $payout->id,
                    'type' => 'debit',
                    'amount_cents' => 5000,
                    'idempotency_key' => 'debit_' . $payout->idempotency_key,
                ]);
            });
        });
    }
}