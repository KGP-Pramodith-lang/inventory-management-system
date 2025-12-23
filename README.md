# Inventory Management System (IMS)

A simple PHP + MySQL (XAMPP) Inventory Management System with:

- Login (admin / staff roles)
- Inventory dashboard
- Sales / POS billing
- Purchase stock + purchase history
- Sales history
- Analytics dashboard
- Refund management (request + approve/reject)

---

## Requirements

- XAMPP (Apache + MySQL/MariaDB)
- PHP 8+ (included in XAMPP)
- phpMyAdmin (included in XAMPP)

---

## Setup (Beginner Steps)

### 1) Put the project in XAMPP htdocs

Make sure the project folder is inside:

- `xamppfiles/htdocs/`

Example:

- `xamppfiles/htdocs/ims/`

### 2) Start Apache + MySQL

Open **XAMPP Control Panel** and start:

- Apache
- MySQL

### 3) Create/import the database

This project uses a database named: `ims`

**Option A: Import using phpMyAdmin (recommended)**

1. Open: `http://localhost/phpmyadmin`
2. Click **Import**
3. Choose: `connection/database.sql`
4. Click **Go**

**Option B: Import using Terminal**

```bash
mysql -u root -p < /path/to/ims/connection/database.sql
```

> On many XAMPP installs the MySQL root password is empty. If prompted, press Enter.

### 4) Configure DB connection (if needed)

Database settings are in:

- `connection/db.php`

Defaults:

- host: `localhost`
- db: `ims`
- user: `root`
- password: `` (empty)

### 5) Open the app

Visit:

- `http://localhost/ims/`

---

## Login Credentials

The app stores users in the `users` table.

- If the `users` table is empty, the app automatically creates default users when you open the login page.
- You can change usernames/passwords from **Settings** (admin only).

Default accounts (first run):

- Admin: `admin` / `123`
- Staff: `staff` / `456`

---

## Main Pages

- Login: `login.php`
- Admin dashboard: `dashboard_m.php`
- Staff dashboard: `dashboard_s.php`
- Settings (admin only): `settings.php`

Features:

- Sales / POS: `features/sales.php`
- Sales history: `features/sales_history.php`
- Inventory (admin): `features/inventory.php`
- Inventory (staff): `features/inventory_s.php`
- Purchase stock: `features/purchase.php`
- Purchase history: `features/purchase_history.php`
- Analytics: `features/analytics.php`
- Refund management: `features/refunds.php`

---

## Refund Management (How It Works)

### Staff

- Staff can create a **refund request** for a bill inside `features/refunds.php`.
- A request is stored as `pending`.

### Admin

- Admin can **approve** or **reject** pending requests.
- When **approved**, the system restores stock for all items in that bill (adds back the quantities into `products`).

Backend handlers:

- `logics/refund_request.php`
- `logics/refund_action.php`

Database table:

- `refunds`

---

## Footer

All pages include a shared footer:

- `partials/footer.php`

---

## Troubleshooting

### “Database connection failed”

- Confirm MySQL is started in XAMPP.
- Confirm the database name is `ims`.
- Check credentials in `connection/db.php`.

### “Table not found” errors

- Re-import `connection/database.sql`.
- If you already imported before updates, the app can also auto-create some tables at runtime:
  - Users table (login)
  - Refunds table (refund page)

### Can’t see seeded users

- Open `http://localhost/ims/login.php` once.
- If the `users` table is empty, it will seed admin/staff automatically.

---

## Project Structure

```
connection/
  db.php
  auth.php
  database.sql
features/
  sales.php
  sales_history.php
  inventory.php
  inventory_s.php
  purchase.php
  purchase_history.php
  analytics.php
  refunds.php
logics/
  add.php
  delete.php
  update_stock.php
  refund_request.php
  refund_action.php
partials/
  footer.php
css/
  style.css
```
