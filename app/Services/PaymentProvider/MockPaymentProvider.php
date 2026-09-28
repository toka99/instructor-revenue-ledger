<?php
namespace App\Services\PaymentProvider;

use Exception;

class MockPaymentProvider
{
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_TIMEOUT = 'TIMEOUT';

    public function processPayout(string $payoutId, int $amountCents, string $idempotencyKey): string
    {
        $scenario = rand(1, 3);

        return match ($scenario) {
            1 => self::STATUS_SUCCESS,
            2 => self::STATUS_FAILED,
            3 => throw new Exception("Provider Timeout / Network Dropped", 504),
        };
    }

    public function checkStatus(string $idempotencyKey): string
    {
        return self::STATUS_SUCCESS; 
    }
}