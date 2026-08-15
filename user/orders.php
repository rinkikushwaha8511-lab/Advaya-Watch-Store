<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

try {

    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        SELECT
            id,
            total_amount,
            shipping_address,
            payment_method,
            order_status,
            created_at
        FROM orders
        WHERE user_id = :user_id
        ORDER BY created_at DESC
    ");

    $stmt->execute([
        ':user_id' => $_SESSION['user_id']
    ]);

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    <title>My Orders - Advaya</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .orders-container {

            max-width: 1100px;

            margin: 60px auto;

            padding: 20px;

        }


        .orders-header {

            margin-bottom: 30px;

        }


        .orders-header h1 {

            margin-bottom: 8px;

        }


        .orders-header p {

            opacity: 0.65;

        }


        .order-card {

            padding: 25px;

            margin-bottom: 20px;

            border-radius: 14px;

            background:
                rgba(255,255,255,0.03);

            border:
                1px solid rgba(255,255,255,0.08);

        }


        .order-top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 20px;

        }


        .order-number {

            font-size: 18px;

            font-weight: 700;

        }


        .order-date {

            opacity: 0.6;

            font-size: 14px;

            margin-top: 5px;

        }


        .order-status {

            padding: 7px 12px;

            border-radius: 20px;

            background:
                rgba(255,255,255,0.08);

            font-size: 13px;

            text-transform: capitalize;

        }


        .order-info {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            padding: 20px 0;

            border-top:
                1px solid rgba(255,255,255,0.07);

            border-bottom:
                1px solid rgba(255,255,255,0.07);

        }


        .info-label {

            display: block;

            opacity: 0.55;

            font-size: 13px;

            margin-bottom: 6px;

        }


        .info-value {

            font-weight: 600;

        }


        .order-actions {

            margin-top: 20px;

            display: flex;

            justify-content: flex-end;

        }


        .view-order-button {

            display: inline-block;

            padding: 11px 18px;

            border-radius: 8px;

            text-decoration: none;

            background: #ffffff;

            color: #111111;

            font-weight: 600;

        }


        .empty-orders {

            text-align: center;

            padding: 70px 20px;

            border-radius: 14px;

            background:
                rgba(255,255,255,0.03);

            border:
                1px solid rgba(255,255,255,0.08);

        }


        .empty-orders h2 {

            margin-bottom: 10px;

        }


        .empty-orders p {

            opacity: 0.65;

            margin-bottom: 25px;

        }


        .shop-button {

            display: inline-block;

            padding: 12px 20px;

            border-radius: 8px;

            text-decoration: none;

            background: #ffffff;

            color: #111111;

            font-weight: 600;

        }


        @media (max-width: 700px) {

            .order-top {

                flex-direction: column;

                align-items: flex-start;

            }


            .order-info {

                grid-template-columns: 1fr;

            }


            .order-actions {

                justify-content: flex-start;

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

    <div class="orders-container">


        <div class="orders-header">

            <h1>
                My Orders
            </h1>

            <p>
                Track and manage your Advaya orders.
            </p>

        </div>


        <?php if (empty($orders)): ?>


            <div class="empty-orders">

                <h2>
                    No Orders Yet
                </h2>

                <p>
                    You haven't placed any orders yet.
                </p>

                <a
                    href="../index.php#catalog"
                    class="shop-button"
                >
                    Start Shopping
                </a>

            </div>


        <?php else: ?>


            <?php foreach ($orders as $order): ?>


                <div class="order-card">


                    <div class="order-top">


                        <div>

                            <div class="order-number">

                                #ADV-<?= (int) $order['id'] ?>

                            </div>


                            <div class="order-date">

                                <?= date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $order['created_at']
                                    )
                                ) ?>

                            </div>

                        </div>


                        <div class="order-status">

                            <?= htmlspecialchars(
                                ucfirst(
                                    $order['order_status']
                                )
                            ) ?>

                        </div>


                    </div>


                    <div class="order-info">


                        <div>

                            <span class="info-label">
                                Total Amount
                            </span>

                            <span class="info-value">

                                $
                                <?= number_format(
                                    (float)
                                    $order['total_amount'],
                                    2
                                ) ?>

                            </span>

                        </div>


                        <div>

                            <span class="info-label">
                                Payment Method
                            </span>

                            <span class="info-value">

                                <?= htmlspecialchars(
                                    $order['payment_method']
                                ) ?>

                            </span>

                        </div>


                        <div>

                            <span class="info-label">
                                Delivery Address
                            </span>

                            <span class="info-value">

                                <?= htmlspecialchars(
                                    $order['shipping_address']
                                ) ?>

                            </span>

                        </div>


                    </div>


                    <div class="order-actions">

                        <a
                            href="order-details.php?order_id=<?= (int) $order['id'] ?>"
                            class="view-order-button"
                        >

                            View Order Details

                        </a>

                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>

</main>


</body>

</html>