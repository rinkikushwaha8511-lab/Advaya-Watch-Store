<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$orderId = isset($_GET['order_id'])
    ? (int) $_GET['order_id']
    : 0;

if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

try {

    $pdo = getDBConnection();

    /*
     * Fetch only the logged-in user's order.
     * This prevents another user from opening
     * someone else's order using the URL.
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            user_id,
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
        header('Location: index.php');
        exit;
    }

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

    <title>Order Confirmed - Advaya</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        .success-container {

            max-width: 700px;

            margin: 80px auto;

            padding: 20px;

            text-align: center;

        }


        .success-card {

            padding: 45px 35px;

            border-radius: 16px;

            background:
                rgba(255, 255, 255, 0.03);

            border:
                1px solid rgba(255, 255, 255, 0.08);

        }


        .success-icon {

            width: 70px;

            height: 70px;

            margin: 0 auto 25px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 32px;

            background:
                rgba(34, 197, 94, 0.12);

            color: #22c55e;

        }


        .success-card h1 {

            margin-bottom: 12px;

        }


        .success-card > p {

            opacity: 0.7;

            margin-bottom: 30px;

        }


        .order-details {

            text-align: left;

            padding: 20px;

            border-radius: 10px;

            background:
                rgba(255, 255, 255, 0.03);

            border:
                1px solid rgba(255, 255, 255, 0.08);

            margin-bottom: 25px;

        }


        .detail-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

            border-bottom:
                1px solid rgba(255, 255, 255, 0.07);

        }


        .detail-row:last-child {

            border-bottom: none;

        }


        .detail-label {

            opacity: 0.6;

        }


        .detail-value {

            text-align: right;

            font-weight: 600;

        }


        .shipping-address {

            white-space: pre-line;

            font-weight: normal;

            line-height: 1.5;

            max-width: 55%;

        }


        .success-buttons {

            display: flex;

            gap: 12px;

            justify-content: center;

            flex-wrap: wrap;

        }


        .success-button {

            display: inline-block;

            padding: 12px 20px;

            border-radius: 8px;

            text-decoration: none;

            background: #ffffff;

            color: #111111;

            font-weight: 600;

        }


        .secondary-button {

            background:
                rgba(255, 255, 255, 0.06);

            color: inherit;

            border:
                1px solid rgba(255, 255, 255, 0.12);

        }


        .order-date {

            opacity: 0.9;

        }


        @media (max-width: 600px) {

            .success-container {

                margin: 40px auto;

                padding: 15px;

            }


            .success-card {

                padding: 30px 20px;

            }


            .detail-row {

                flex-direction: column;

                gap: 5px;

            }


            .detail-value {

                text-align: left;

            }


            .shipping-address {

                max-width: 100%;

            }

        }

    </style>

</head>


<body>


<!-- Header -->

<header id="main-header">

    <div class="container header-content">

        <a
            href="index.php"
            class="logo"
        >

            Advaya
            <span>Watch Store</span>

        </a>


        <ul class="nav-links">

            <li>

                <a href="index.php">

                    Home

                </a>

            </li>


            <li>

                <a href="index.php#catalog">

                    Catalog

                </a>

            </li>


            <li>

                <a href="user/profile.php">

                    My Account

                </a>

            </li>


            <li>

                <a href="logout.php">

                    Logout

                </a>

            </li>

        </ul>

    </div>

</header>


<!-- Main Content -->

<main>

    <div class="success-container">

        <div class="success-card">


            <!-- Success Icon -->

            <div class="success-icon">

                ✓

            </div>


            <!-- Heading -->

            <h1>

                Order Placed Successfully!

            </h1>


            <p>

                Thank you for shopping with Advaya.
                Your order has been received successfully.

            </p>


            <!-- Order Details -->

            <div class="order-details">


                <!-- Order Number -->

                <div class="detail-row">

                    <span class="detail-label">

                        Order Number

                    </span>

                    <span class="detail-value">

                        #ADV-<?= (int) $order['id'] ?>

                    </span>

                </div>


                <!-- Order Status -->

                <div class="detail-row">

                    <span class="detail-label">

                        Order Status

                    </span>

                    <span class="detail-value">

                        <?= htmlspecialchars(
                            ucfirst(
                                $order['order_status']
                            )
                        ) ?>

                    </span>

                </div>


                <!-- Payment Method -->

                <div class="detail-row">

                    <span class="detail-label">

                        Payment

                    </span>

                    <span class="detail-value">

                        <?= htmlspecialchars(
                            $order['payment_method']
                        ) ?>

                    </span>

                </div>


                <!-- Total -->

                <div class="detail-row">

                    <span class="detail-label">

                        Total Amount

                    </span>

                    <span class="detail-value">

                        $
                        <?= number_format(
                            (float) $order['total_amount'],
                            2
                        ) ?>

                    </span>

                </div>


                <!-- Order Date -->

                <div class="detail-row">

                    <span class="detail-label">

                        Order Date

                    </span>

                    <span class="detail-value order-date">

                        <?= date(
                            'd M Y, h:i A',
                            strtotime(
                                $order['created_at']
                            )
                        ) ?>

                    </span>

                </div>


                <!-- Shipping Address -->

                <div class="detail-row">

                    <span class="detail-label">

                        Delivery Address

                    </span>

                    <span class="detail-value shipping-address">

                        <?= htmlspecialchars(
                            $order['shipping_address']
                        ) ?>

                    </span>

                </div>


            </div>


            <!-- Buttons -->

            <div class="success-buttons">


                <a
                    href="index.php#catalog"
                    class="success-button"
                >

                    Continue Shopping

                </a>


                <a
                    href="user/orders.php"
                    class="success-button secondary-button"
                >

                    View My Orders

                </a>


            </div>


        </div>

    </div>

</main>


</body>

</html>