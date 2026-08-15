<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

try {

    $pdo = getDBConnection();

    // Fetch the order and ensure it belongs to the logged-in user
    $stmt = $pdo->prepare("
        SELECT
            id,
            total_amount,
            shipping_address,
            payment_method,
            order_status,
            created_at
        FROM orders
        WHERE id = :order_id
          AND user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':order_id' => $orderId,
        ':user_id'  => $_SESSION['user_id']
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: orders.php');
        exit;
    }

    // Fetch all products in the order
    $stmtItems = $pdo->prepare("
        SELECT
            oi.quantity,
            oi.price AS item_price,
            p.id AS product_id,
            p.name AS product_name,
            p.brand AS product_brand,
            p.image AS product_image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id
    ");

    $stmtItems->execute([
        ':order_id' => $orderId
    ]);

    $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die(
        'Database error: ' .
        htmlspecialchars($e->getMessage())
    );

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Order #ADV-<?= (int) $order['id'] ?> Details - Advaya</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .order-details-container {

            max-width: 1100px;

            margin: 60px auto;

            padding: 0 20px;

        }


        .back-link {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            color: var(--primary);

            text-decoration: none;

            font-size: 0.9rem;

            font-weight: 500;

            margin-bottom: 25px;

            transition: var(--transition-smooth);

        }


        .back-link:hover {

            color: var(--primary-light);

            transform: translateX(-4px);

        }


        .order-details-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 35px;

            border-bottom: 1px solid var(--border);

            padding-bottom: 25px;

        }


        .order-title h1 {

            font-family: var(--font-heading);

            font-size: 2.2rem;

            font-weight: 400;

            margin-bottom: 8px;

        }


        .order-meta-info {

            display: flex;

            gap: 20px;

            color: var(--text-secondary);

            font-size: 0.9rem;

            flex-wrap: wrap;

        }


        .order-meta-info span {

            display: flex;

            align-items: center;

            gap: 6px;

        }


        .status-badge {

            padding: 6px 14px;

            border-radius: 20px;

            font-size: 0.85rem;

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


        .details-grid {

            display: grid;

            grid-template-columns: 7fr 3fr;

            gap: 30px;

            align-items: start;

        }


        .items-card {

            background: var(--bg-card);

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 30px;

            box-shadow: var(--shadow-premium);

        }


        .items-card h2 {

            font-family: var(--font-heading);

            font-size: 1.5rem;

            font-weight: 400;

            margin-bottom: 25px;

            color: var(--primary-light);

        }


        .order-item-row {

            display: flex;

            align-items: center;

            gap: 20px;

            padding: 20px 0;

            border-bottom: 1px solid rgba(255, 255, 255, 0.05);

        }


        .order-item-row:last-child {

            border-bottom: none;

            padding-bottom: 0;

        }


        .order-item-row:first-child {

            padding-top: 0;

        }


        .item-image {

            width: 90px;

            height: 90px;

            background: radial-gradient(circle, #20242a 0%, #131518 100%);

            border-radius: 8px;

            overflow: hidden;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 1px solid rgba(255, 255, 255, 0.05);

            flex-shrink: 0;

        }


        .item-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .image-placeholder-small {

            font-size: 0.75rem;

            font-weight: 600;

            color: var(--text-muted);

            text-transform: uppercase;

            letter-spacing: 1px;

            text-align: center;

            padding: 5px;

        }


        .item-details {

            flex-grow: 1;

        }


        .item-brand {

            font-size: 0.75rem;

            text-transform: uppercase;

            color: var(--primary);

            letter-spacing: 1.5px;

            font-weight: 600;

            margin-bottom: 4px;

        }


        .item-name {

            font-family: var(--font-body);

            font-size: 1.1rem;

            font-weight: 500;

            color: var(--text-primary);

            text-decoration: none;

            transition: var(--transition-smooth);

            display: inline-block;

        }


        .item-name:hover {

            color: var(--primary);

        }


        .item-pricing {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-top: 8px;

        }


        .item-meta {

            font-size: 0.9rem;

            color: var(--text-secondary);

        }


        .item-subtotal {

            font-family: var(--font-heading);

            font-size: 1.15rem;

            color: var(--primary-light);

            font-weight: 600;

        }


        .summary-card {

            background: var(--bg-card);

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 25px;

            box-shadow: var(--shadow-premium);

            position: sticky;

            top: 100px;

        }


        .summary-card h2 {

            font-family: var(--font-heading);

            font-size: 1.4rem;

            font-weight: 400;

            margin-bottom: 20px;

            color: var(--primary-light);

            border-bottom: 1px solid var(--border);

            padding-bottom: 12px;

        }


        .summary-section {

            margin-bottom: 20px;

        }


        .summary-section:last-of-type {

            margin-bottom: 0;

        }


        .summary-label {

            display: block;

            font-size: 0.8rem;

            text-transform: uppercase;

            letter-spacing: 1px;

            color: var(--text-muted);

            margin-bottom: 6px;

            font-weight: 600;

        }


        .summary-value {

            font-size: 0.95rem;

            color: var(--text-primary);

            font-weight: 500;

            line-height: 1.5;

        }


        .address-box {

            white-space: pre-line;

            background: rgba(255, 255, 255, 0.02);

            border: 1px solid rgba(255, 255, 255, 0.05);

            padding: 12px;

            border-radius: 6px;

            font-size: 0.9rem;

        }


        .total-amount-box {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding-top: 15px;

            margin-top: 15px;

            border-top: 1px solid rgba(255, 255, 255, 0.08);

        }


        .total-label {

            font-size: 1rem;

            font-weight: 600;

            color: var(--text-primary);

        }


        .total-value {

            font-family: var(--font-heading);

            font-size: 1.7rem;

            color: var(--primary);

            font-weight: 700;

        }


        @media (max-width: 900px) {

            .details-grid {

                grid-template-columns: 1fr;

            }


            .summary-card {

                position: static;

            }

        }

    </style>

</head>


<body>


<header id="main-header">

    <div class="container header-content">

        <a
            href="../index.php"
            class="logo"
        >

            Advaya
            <span>Watch Store</span>

        </a>


        <ul class="nav-links">

            <li>

                <a href="../index.php">
                    Home
                </a>

            </li>


            <li>

                <a href="../index.php#catalog">
                    Catalog
                </a>

            </li>


            <li>

                <a href="profile.php">
                    My Account
                </a>

            </li>


            <li>

                <a href="../logout.php">
                    Logout
                </a>

            </li>

        </ul>

    </div>

</header>


<main>

    <div class="order-details-container">


        <a
            href="orders.php"
            class="back-link"
        >

            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>

            Back to Orders

        </a>


        <div class="order-details-header">


            <div class="order-title">

                <h1>
                    Order #ADV-<?= (int) $order['id'] ?>
                </h1>


                <div class="order-meta-info">

                    <span>

                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>

                        Placed on <?= date(
                            'd M Y, h:i A',
                            strtotime($order['created_at'])
                        ) ?>

                    </span>

                </div>

            </div>


            <?php
            $statusClass = 'status-pending';
            $status = strtolower($order['order_status']);
            if ($status === 'processing') {
                $statusClass = 'status-processing';
            } elseif ($status === 'shipped') {
                $statusClass = 'status-shipped';
            } elseif ($status === 'delivered') {
                $statusClass = 'status-delivered';
            } elseif ($status === 'cancelled') {
                $statusClass = 'status-cancelled';
            }
            ?>

            <span class="status-badge <?= $statusClass ?>">
                <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
            </span>


        </div>


        <div class="details-grid">


            <div class="items-card">

                <h2>
                    Order Items
                </h2>


                <?php foreach ($orderItems as $item): ?>

                    <div class="order-item-row">


                        <div class="item-image">

                            <?php if (
                                !empty($item['product_image']) &&
                                file_exists(
                                    __DIR__ .
                                    '/../uploads/' .
                                    $item['product_image']
                                )
                            ): ?>

                                <img
                                    src="../uploads/<?= htmlspecialchars($item['product_image']) ?>"
                                    alt="<?= htmlspecialchars($item['product_name']) ?>"
                                >

                            <?php else: ?>

                                <div class="image-placeholder-small">
                                    <?= htmlspecialchars($item['product_brand']) ?>
                                </div>

                            <?php endif; ?>

                        </div>


                        <div class="item-details">

                            <div class="item-brand">
                                <?= htmlspecialchars($item['product_brand']) ?>
                            </div>

                            <a
                                href="../product.php?id=<?= (int) $item['product_id'] ?>"
                                class="item-name"
                            >
                                <?= htmlspecialchars($item['product_name']) ?>
                            </a>


                            <div class="item-pricing">

                                <div class="item-meta">

                                    $<?= number_format(
                                        (float) $item['item_price'],
                                        2
                                    ) ?>

                                    &times;

                                    <?= (int) $item['quantity'] ?>

                                </div>


                                <div class="item-subtotal">

                                    $<?= number_format(
                                        (float) (
                                            $item['item_price'] *
                                            $item['quantity']
                                        ),
                                        2
                                    ) ?>

                                </div>

                            </div>

                        </div>


                    </div>

                <?php endforeach; ?>

            </div>


            <div class="summary-card">

                <h2>
                    Order Summary
                </h2>


                <div class="summary-section">

                    <span class="summary-label">
                        Payment Method
                    </span>

                    <span class="summary-value">

                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            style="vertical-align: middle; margin-right: 4px;"
                        >
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>

                        <?= htmlspecialchars($order['payment_method']) ?>

                    </span>

                </div>


                <div class="summary-section">

                    <span class="summary-label">
                        Shipping Address
                    </span>

                    <div class="summary-value address-box">
                        <?= htmlspecialchars($order['shipping_address']) ?>
                    </div>

                </div>


                <div class="total-amount-box">

                    <span class="total-label">
                        Total Amount
                    </span>

                    <span class="total-value">
                        $<?= number_format(
                            (float) $order['total_amount'],
                            2
                        ) ?>
                    </span>

                </div>


            </div>


        </div>


    </div>

</main>


<footer id="main-footer">

    <div class="container">

        <div class="footer-bottom">

            <p>
                &copy;
                <?= date('Y') ?>
                Advaya Watch Store.
                All rights reserved.
            </p>

        </div>

    </div>

</footer>


</body>

</html>
