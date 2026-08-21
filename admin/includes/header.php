<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Admin' : 'Admin Dashboard - Advaya' ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Admin-specific layout extensions */
        .admin-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .admin-nav {
            background: rgba(10, 11, 15, 0.95);
            border-bottom: 1px solid var(--border);
            padding: 1.1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .admin-nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .admin-logo {
            font-family: var(--font-heading);
            font-size: 1.7rem;
            color: var(--primary);
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-weight: 700;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .admin-logo:hover {
            color: var(--primary-light);
            text-shadow: 0 0 20px rgba(197,168,128,0.4);
        }

        .admin-logo span {
            font-size: 0.65rem;
            color: var(--text-muted);
            font-weight: 400;
            font-family: var(--font-body);
            margin-left: 2px;
            padding: 3px 8px;
            border: 1px solid var(--border);
            border-radius: 4px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .admin-nav-links {
            display: flex;
            gap: 4px;
            list-style: none;
            align-items: center;
        }

        .admin-nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.78rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
            transition: var(--transition-smooth);
            padding: 6px 14px;
            border-radius: 6px;
        }

        .admin-nav-links a:hover {
            color: var(--primary);
            background: rgba(197,168,128,0.07);
        }

        .admin-nav-links a.active {
            color: var(--primary);
            background: rgba(197,168,128,0.1);
            border: 1px solid rgba(197,168,128,0.2);
        }

        /* Tables styling */
        .admin-table-container {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: var(--shadow-premium);
            margin-top: 25px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        .admin-table th {
            background: rgba(0, 0, 0, 0.20);
            color: var(--primary-light);
            font-weight: 600;
            padding: 16px 20px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--border);
        }

        .admin-table td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
            color: var(--text-primary);
            vertical-align: middle;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }

        /* Status colors */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .status-processing {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .status-shipped {
            background-color: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .status-delivered {
            background-color: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-cancelled {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .status-active {
            background-color: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-inactive {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Buttons & Forms */
        .btn-admin {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 6px;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            text-decoration: none;
            letter-spacing: 1px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .btn-admin-primary {
            background: var(--primary);
            color: var(--bg-main);
        }

        .btn-admin-primary:hover {
            background: var(--primary-light);
            box-shadow: 0 0 15px rgba(197, 168, 128, 0.3);
        }

        .btn-admin-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-admin-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-admin-danger {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-admin-danger:hover {
            background: var(--danger);
            color: var(--text-primary);
        }

        .action-links {
            display: flex;
            gap: 8px;
        }

        /* Alert boxes */
        .alert {
            padding: 14px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Forms styling */
        .admin-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow-premium);
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-control {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 12px 16px;
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 0.95rem;
            transition: var(--transition-smooth);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(197, 168, 128, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Header action button */
        .admin-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            gap: 20px;
        }

        .admin-page-header h1 {
            font-family: var(--font-heading);
            font-size: 2.2rem;
            font-weight: 400;
        }

        .product-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            background: radial-gradient(circle, #20242a 0%, #131518 100%);
        }

        @media (max-width: 768px) {
            .admin-nav-content {
                flex-direction: column;
                gap: 15px;
                padding: 0 1rem;
            }
            .admin-nav-links {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
            .admin-page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<header class="admin-nav">
    <div class="admin-nav-content">
        <a href="dashboard.php" class="admin-logo">
            Advaya <span>Admin</span>
        </a>
        <ul class="admin-nav-links">
            <li><a href="dashboard.php" class="<?= isset($activePage) && $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="products.php" class="<?= isset($activePage) && $activePage === 'products' ? 'active' : '' ?>">Products</a></li>
            <li><a href="categories.php" class="<?= isset($activePage) && $activePage === 'categories' ? 'active' : '' ?>">Categories</a></li>
            <li><a href="orders.php" class="<?= isset($activePage) && $activePage === 'orders' ? 'active' : '' ?>">Orders</a></li>
            <li><a href="../index.php" style="color: var(--primary);">&#8594; Store</a></li>
            <li><a href="../logout.php" style="color: var(--danger);">Logout</a></li>
        </ul>
    </div>
</header>

