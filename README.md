# Instructor Revenue Ledger

An online learning platform (LMS) payment core. It safely splits student subscription payments among instructors, deducts the platform share, and processes instructor payouts without ever double-paying.

---

## 1. Setup Instructions

Follow these simple steps to run the project on your machine:

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL Database

### Step-by-Step Setup

1. **Clone the repository**
   ```bash
   git clone <your-repository-url>
   cd instructor-revenue-ledger
   
   1.Install PHP dependencies
   composer install
   2.Set up the environment file
   Copy .env.example to create .env:
   cp .env.example .env
   3.Configure your MySQL database
   Open .env in your text editor and update your database credentials:
   Code snippet
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=instructor_ledger
   DB_USERNAME=root
   DB_PASSWORD=
   4.Generate application key
   php artisan key:generate
   5.Run database migrations and seed mock data
   php artisan migrate:fresh --seed
   6.Run the payout process command
   php artisan payouts:process
   7.Start the local web server
   php artisan serve
   Open http://127.0.0.1:8000/admin in your browser to view the Filament panel.

2. How to Run Tests
We use Pest PHP for automated testing.

Run All Tests
php artisan test
or
vendor/bin/pest

3. Assumptions Made

1.All Money in Cents: All prices, payouts, and revenue allocations are stored as integers (amount_cents) to avoid floating-point math errors.

2.Platform Share: The platform takes a fixed cut (e.g., 30%), and the remaining amount (70%) is distributed among course instructors.

3.Handling Leftover Cents: When dividing revenue evenly creates remainder cents, they are given one-by-one to instructors in order so zero money is lost.

4.Calculated Balances: We do not keep a single mutable balance column. An instructor's balance is calculated dynamically by subtracting total debits (payouts) from total credits (earnings).

5.Idempotency Keys: Every payout and ledger entry requires a unique idempotency_key. The database automatically rejects duplicate key entries.

6.Provider Timeouts: If the external payment provider times out, we mark the status as unknown_timeout. The system never assumes success or sends money again until the real status is checked.