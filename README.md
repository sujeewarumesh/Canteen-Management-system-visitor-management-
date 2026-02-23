# Canteen-Management-system-visitor-management-
PHP and SQL Server–based Canteen Management System for managing employee meal transactions. Supports barcode/manual ID scanning, automatic meal detection by time, duplicate prevention, transaction logging, and admin reporting. Includes an optional Visitor Management module for recording visitor passes.

# Canteen Management System (GRI)

A simple PHP + SQL Server web system to manage a company canteen:
- Scan employee ID (barcode/manual)
- Auto-detect meal type by time (Breakfast/Lunch/Dinner)
- Prevent duplicate meal entries for the same employee on the same day
- Store all transactions in SQL Server
- Admin report view by employee and date range
- Record outstanding payments (stored as Payment transactions)

## Tech Stack
- PHP (sqlsrv)
- Microsoft SQL Server
- Bootstrap 5

## Project Structure
- `index.php`  : Canteen scanning/meal record screen
- `admin.php`  : Admin view (reports, totals, outstanding/payments)
- `visitor.php`: Visitor pass entry (optional module)
- `conn.php`   : SQL Server connection (DO NOT COMMIT)

## Setup
1. Clone the repository
2. Copy config:
   - `conn.sample.php` → `conn.php`
   - Update SQL Server host/user/password and DB names
3. Ensure PHP has SQL Server drivers enabled:
   - `sqlsrv` + `pdo_sqlsrv`
4. Deploy on IIS/XAMPP (Windows recommended for sqlsrv)
5. Open:
   - `index.php` (canteen scan UI)
   - `admin.php` (admin dashboard)

## Database Tables (suggested)
### employees (in `scales_gri`)
- EMPid (PK)
- full_name
- department
- photo

### canteen_records (in `canteen_db`)
- id (PK, identity)
- emp_id
- item_type
- type
- item_description
- amount
- transaction_date (datetime)
- payment_method

### visitor_passes (optional module)
- id (PK, identity)
- pass_id
- visitor_name
- purpose_of_visit
- date_of_visit
- time_of_visit

## Security Notes
- Never commit `conn.php` or real credentials.
- Use a DB user with limited permissions (avoid `sa`).
