# Swd Shop

A PHP 8 and MySQL shop for XAMPP, with a session cart, transactional checkout, order tracking, and a password-protected order admin.

## Requirements

- XAMPP with PHP 8 and MySQL/MariaDB
- PHP extensions `pdo_mysql` and `openssl`

## Setup

1. Place the project at `C:\xampp\htdocs\swd\09-php-shop1`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Create and populate the database by importing `database.sql` in phpMyAdmin, or from PowerShell:

   ```powershell
   & 'C:\xampp\mysql\bin\mysql.exe' -u root -e 'CREATE DATABASE IF NOT EXISTS swd_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
   cmd.exe /c '""C:\xampp\mysql\bin\mysql.exe" -u root < "C:\xampp\htdocs\swd\09-php-shop1\database.sql""'
   ```

4. If your local MySQL credentials differ, update `$dbHost`, `$dbName`, `$dbUser`, and `$dbPassword` in `app/bootstrap.php`.
5. (Optional) Seed or refresh the example catalog from PowerShell:

   ```powershell
   & 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\swd\09-php-shop1\seed.php'
   ```

6. Create an admin account from PowerShell. Use a unique password with at least 12 characters; the script hashes it with PHP `password_hash()` and never stores the plaintext:

   ```powershell
   $env:ADMIN_USERNAME = 'storeadmin'
   $env:ADMIN_PASSWORD = Read-Host 'Admin password'
   & 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\swd\09-php-shop1\admin\create-admin.php'
   Remove-Item Env:ADMIN_USERNAME
   Remove-Item Env:ADMIN_PASSWORD
   ```

   The `admins` table has a unique username. To add another administrator, run the script with a different username.

7. Open the storefront at `http://localhost/swd/09-php-shop1/`, order lookup at `/swd/09-php-shop1/order-lookup.php`, and the admin login at `/swd/09-php-shop1/admin/login.php`.

The database connection defaults to XAMPP's local `root` account with an empty password. Set a MySQL password and update `app/bootstrap.php` before exposing this shop beyond a local development machine. Use HTTPS for any deployed site.

## Included pages

- `index.php`, `product.php`: storefront and product details
- `cart.php`, `checkout.php`, `order-success.php`: cart and transactional ordering
- `order-lookup.php`: customer order status lookup by reference and checkout email
- `admin/orders.php`, `admin/order.php`, `admin/index.php`: order management and dashboard

## Database export

`database.sql` contains the `swd_shop` schema and data as exported when this project snapshot was prepared. To create a fresh local export after changing the catalog or orders:

```powershell
& 'C:\xampp\mysql\bin\mysqldump.exe' -u root --databases swd_shop --default-character-set=utf8mb4 --result-file='C:\xampp\htdocs\swd\09-php-shop1\database.sql'
```

Review the export before sharing it; database dumps can contain customer and administrator data.
