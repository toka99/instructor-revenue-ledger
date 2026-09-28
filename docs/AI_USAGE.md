# AI Usage & Engineering Decisions

## Overview
This document explains how AI tools were used while building the **Instructor Revenue Ledger**. The overall system design, financial logic, and safety checks were written and checked by hand to ensure 100% money accuracy. AI was only used as a helper to write simple setup code faster.

---

## 1. How AI Was Used
AI was used only for small, repetitive tasks:
- Writing basic database setup files (migrations).
- Writing basic form fields for Filament screens.
- Writing simple test outlines.

---

## 2. Main Prompts Used
Only short, simple prompts were used for basic code blocks. No prompts were used for financial rules or main design decisions.

*Examples:*
1. *"Write a Laravel migration for a ledger table with columns for instructor_id, amount_cents, type, idempotency_key, and metadata."*
2. *"Show Filament v3 code to hide the 'Create & create another' button."*

---

## 3. What Was Made by AI vs. Hand-Written

| Part of Code | Made By | What Was Done by Hand |
| :--- | :--- | :--- |
| **System Design & Ledger** | Hand-Written | Designed the rule where balance is calculated as: $Total Earned - Total Paid = Balance$. |
| **Safety & Duplicate Locks** | Hand-Written | Added unique database keys and row locks (`lockForUpdate`) to stop double payments. |
| **Database Migrations** | AI + Hand | AI wrote the basic setup; added unique indexes and integer types (`amount_cents`) by hand. |
| **Money Sharing Logic** | Hand-Written | Wrote code to divide money and handle extra cents fairly so no money is lost. |
| **Artisan Command & Jobs** | AI + Hand | AI wrote the command outline; wrote safety checks and background jobs by hand. |
| **Payment Provider Mock** | Hand-Written | Wrote the fake payment gateway to test successes, outright failures, and timeouts. |

---

## 4. Key Engineering Decisions
1. **No Fixed Balance Column:** We do not save a single balance number in the database that gets updated. Instead, we calculate balance by adding up all income entries and subtracting all payout entries. This prevents mistakes when two requests happen at the same time.
2. **Using Cents (Integers):** Money is stored as whole cents (integers) instead of decimal numbers to prevent rounding errors.
3. **Handling Leftover Cents:** When splitting money between instructors, extra cents are shared one by one so every single cent is accounted for.
4. **Database Locks:** We use row locks in the database so two workers can never process the same payment at the same moment.
5. **Handling Network Timeouts:** If a payment gateway stops responding, the payout is marked as `unknown_timeout`. The system will not send money again until it checks the real status first.

---

## 5. What Makes This Code Better
- **Double Payment Protection:** Even if background tasks crash, retry, or run twice at the exact same time, no instructor will ever be paid twice.
- **Real Accounting Rules:** The system works like a real bank ledger instead of a simple database table.

---

## 6. Trade-offs & Next Steps
- **Speed at Large Scale:** Calculating balances live works fast for millions of rows with proper database indexes. For hundreds of millions of rows, saving a daily summary balance table would be a good future upgrade.