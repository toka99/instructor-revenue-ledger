# Architecture Documentation

This document explains the technical design and engineering decisions for the **Instructor Revenue Ledger**.

---

## 1. Key Architectural Decisions

### Append-Only Double-Entry Ledger
We do not store a single changeable `balance` column in the database (e.g., `instructors.balance = 100`). Instead, we calculate balances live using an **append-only transaction ledger** (`ledger_entries`).
* **Credits:** Money earned by the instructor.
* **Debits:** Money paid out to the instructor.
* **Balance Formula:** $\text{Current Balance} = \sum(\text{Credits}) - \sum(\text{Debits})$

This structure ensures a complete audit trail and prevents balance errors when multiple transactions happen at the same time.

### Money Stored in Cents (Integers)
All money amounts are stored as whole integers (`amount_cents`) instead of decimals (floats). This completely avoids computer rounding bugs ($0.1 + 0.2 \neq 0.3$).

---

## 2. Revenue Allocation Strategy

When a student buys a subscription:
1. **Platform Cut:** The platform takes its fee (e.g., 30%).
2. **Instructor Share:** The rest (70%) is split among the course instructors.
3. **Handling Leftover Cents:** When dividing money leaves remainder cents, they are given one-by-one to instructors in sequence. This guarantees:
$$\sum(\text{Instructor Earnings}) + \text{Platform Cut} = \text{Total Subscription Payment}$$
Zero cents are lost or floating in the system.

---

## 3. Idempotency Approach

To stop duplicate payouts under any failure:
* **Unique Keys:** Every payout and ledger transaction has a unique `idempotency_key` saved in the database with a `UNIQUE` index constraint.
* **Deterministic Keys:** Key formats follow a pattern like `payout_inst_1_2026-09`. If the payout command runs twice, the database rejects the second try automatically.
* **Database Row Locks:** Queue jobs use `lockForUpdate()` during execution. If two workers pick up the same job at the exact same moment, the second worker waits until the first finishes and sees the job is already done.

---

## 4. Provider Timeout Handling

If the payment gateway stops responding mid-payment:
1. The payout status is changed to `unknown_timeout`.
2. No debit entry is written to the ledger yet.
3. The job does NOT send another payment request automatically.
4. A separate status check (`checkStatus`) runs later to confirm if the gateway actually moved the money or not before updating the database.

---

## 5. Scaling Considerations

* **Database Indexing:** We indexed `(instructor_id, created_at)` and `(instructor_id, type)` so balance calculations stay fast across millions of rows.
* **Ledger Snapshots:** If the database grows to tens of millions of rows, daily or monthly snapshot balance tables can be added to keep `SUM()` queries instant.
* **Background Jobs:** All payout actions run inside queued background workers so the admin UI stays fast.

---

## 6. Known Limitations

* **Live Calculation Overhead:** Calculating balances live via `SUM()` works fast with proper indexes, but requires snapshot caching at massive scale (100M+ records).
* **Synchronous Testing Queue:** Tests use the `sync` queue driver. In production, Redis or Amazon SQS should be used for real async worker queues.

