# Advaya – E-Commerce Watch Store

Advaya is a clean, modern, and highly responsive e-commerce watch store built using raw **PHP 8+**, **MySQL**, **HTML5**, **CSS3**, and **Vanilla JavaScript**. 

This project is a rebuild of a legacy PHP watch-store, focusing on a clean, modular, and maintainable project structure using native PHP patterns, sessions, and PDO prepared statements.

---

## 📂 Project Structure

The project setup contains the following directory tree:

```text
advaya/
├── admin/            # Administrator panel (products, categories, order management)
├── assets/           # Client-side static resources
│   ├── css/          # Custom stylesheets (e.g. style.css)
│   ├── js/           # Custom JavaScript files (e.g. main.js)
│   └── images/       # Static UI icons/graphics
├── config/           # App configuration files (database parameters, schema.sql)
├── includes/         # Reusable HTML layout blocks (headers, navbars, footers)
├── user/             # Customer profile, dashboard, and settings
├── products/         # Product browsing, categories, search, details
├── cart/             # Shopping cart, checkout checkout flow
├── uploads/          # Uploaded product watch images
├── index.php         # App homepage & development connection test-bench
├── logout.php        # Customer/Admin logout session destruction script
└── README.md         # Documentation & setup guide
```

---

## 🛠️ Tech Stack & Requirements

- **Server-side:** PHP 8.0 or higher
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **Extensions:** `pdo_mysql` (standard in modern PHP installations)
- **Frontend:** Vanilla HTML5, modern CSS3 (Custom Variables, CSS Grid, Flexbox), and Vanilla ES6+ JavaScript.

---

## 🚀 Local Installation & Configuration

Follow these steps to configure the project on your machine:

### 1. Set Workspace in IDE
For the best coding and browsing experience, set the directory below as your active workspace in your IDE:
`C:\Users\PRATHAM\.gemini\antigravity-ide\scratch\advaya`

### 2. Configure the MySQL Database
1. Open your MySQL client (e.g., phpMyAdmin, Command Line, DBeaver, etc.).
2. Create a new database named `advaya_db`:
   ```sql
   CREATE DATABASE IF NOT EXISTS `advaya_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the SQL schema file [schema.sql](file:///C:/Users/PRATHAM/.gemini/antigravity-ide/scratch/advaya/config/schema.sql) located at `config/schema.sql`. This will create the 6 tables (`users`, `categories`, `products`, `cart`, `orders`, `order_items`) and load 5 premium sample watches and categories.

### 3. Configure Database Credentials
Open the file [database.php](file:///C:/Users/PRATHAM/.gemini/antigravity-ide/scratch/advaya/config/database.php) located in `config/database.php`.
Update the constants if your MySQL server is running on a different port, or requires a password:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'advaya_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Add your database password here
```

### 4. Start the Local Server
Choose one of the methods below to run the project:

#### Method A: Using PHP's Built-in Server (Recommended for development)
1. Open a terminal or shell.
2. Navigate to the project root directory:
   `cd C:\Users\PRATHAM\.gemini\antigravity-ide\scratch\advaya`
3. Start the PHP built-in web server:
   ```bash
   php -S localhost:8000
   ```
4. Open your browser and navigate to `http://localhost:8000`.

#### Method B: Using XAMPP / WampServer / Laragon
1. Move or clone the `advaya` directory to your web server root (e.g., `htdocs` for XAMPP, or `www` for WampServer).
2. Start the Apache and MySQL modules.
3. Open your browser and access the page (e.g., `http://localhost/advaya/index.php`).

---

## 🔒 Security Practices Maintained

1. **SQL Injection Protection:** All database interactions will use PDO prepared statements with parameter binding.
2. **Password Security:** User passwords must be stored using `password_hash()` (defaulting to bcrypt) and authenticated using `password_verify()`.
3. **PDO Fail-safes:** Database credentials are kept in a separate file, and PDO handles database errors elegantly without printing connection parameters.
