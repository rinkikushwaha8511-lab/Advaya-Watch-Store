<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();

$userId = (int) $_SESSION['user_id'];

$errors = [];

$fullName = '';
$phone = '';
$address = '';
$city = '';
$state = '';
$pincode = '';
$paymentMethod = 'Cash on Delivery';


/*
|--------------------------------------------------------------------------
| Get Logged-in User
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        email,
        phone,
        address
    FROM users
    WHERE id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $userId
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: user/login.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Default User Information
|--------------------------------------------------------------------------
*/

$fullName = $user['name'] ?? '';
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';


/*
|--------------------------------------------------------------------------
| PLACE ORDER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($fullName === '') {
        $errors[] = 'Please enter your full name.';
    }

    if ($phone === '') {
        $errors[] = 'Please enter your phone number.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Please enter a valid 10-digit phone number.';
    }

    if ($address === '') {
        $errors[] = 'Please enter your address.';
    }

    if ($city === '') {
        $errors[] = 'Please enter your city.';
    }

    if ($state === '') {
        $errors[] = 'Please enter your state.';
    }

    if ($pincode === '') {
        $errors[] = 'Please enter your pincode.';
    } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $errors[] = 'Please enter a valid 6-digit pincode.';
    }

    if ($paymentMethod !== 'Cash on Delivery') {
        $errors[] = 'Please select a valid payment method.';
    }


    /*
    |--------------------------------------------------------------------------
    | Process Order
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            /*
            | Start transaction
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Get Cart Items
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    c.id AS cart_id,
                    c.product_id,
                    c.quantity,

                    p.name,
                    p.brand,
                    p.price,
                    p.stock,
                    p.status

                FROM cart c

                INNER JOIN products p
                    ON c.product_id = p.id

                WHERE c.user_id = :user_id

                FOR UPDATE
            ");

            $stmt->execute([
                ':user_id' => $userId
            ]);

            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);


            /*
            |--------------------------------------------------------------------------
            | Empty Cart
            |--------------------------------------------------------------------------
            */

            if (empty($cartItems)) {

                $pdo->rollBack();

                header('Location: cart.php');
                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate Total + Check Stock
            |--------------------------------------------------------------------------
            */

            $totalAmount = 0;

            foreach ($cartItems as $item) {

                $quantity = (int) $item['quantity'];
                $stock = (int) $item['stock'];
                $price = (float) $item['price'];

                if ($item['status'] !== 'active') {

                    throw new Exception(
                        $item['name'] .
                        ' is currently unavailable.'
                    );
                }

                if ($quantity <= 0) {

                    throw new Exception(
                        'Invalid quantity for ' .
                        $item['name']
                    );
                }

                if ($stock < $quantity) {

                    throw new Exception(
                        'Not enough stock available for ' .
                        $item['name'] .
                        '. Available stock: ' .
                        $stock
                    );
                }

                $totalAmount += $price * $quantity;
            }


            /*
            |--------------------------------------------------------------------------
            | Shipping Address
            |--------------------------------------------------------------------------
            */

            $shippingAddress =
                $fullName .
                "\n" .
                $phone .
                "\n" .
                $address .
                "\n" .
                $city .
                ', ' .
                $state .
                ' - ' .
                $pincode;


            /*
            |--------------------------------------------------------------------------
            | Create Order
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO orders
                (
                    user_id,
                    total_amount,
                    shipping_address,
                    payment_method,
                    order_status
                )
                VALUES
                (
                    :user_id,
                    :total_amount,
                    :shipping_address,
                    :payment_method,
                    :order_status
                )
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':total_amount' => $totalAmount,
                ':shipping_address' => $shippingAddress,
                ':payment_method' => $paymentMethod,
                ':order_status' => 'pending'
            ]);


            /*
            |--------------------------------------------------------------------------
            | Get New Order ID
            |--------------------------------------------------------------------------
            */

            $orderId = (int) $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Prepare Order Item Query
            |--------------------------------------------------------------------------
            */

            $insertOrderItem = $pdo->prepare("
                INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    quantity,
                    price
                )
                VALUES
                (
                    :order_id,
                    :product_id,
                    :quantity,
                    :price
                )
            ");


            /*
            |--------------------------------------------------------------------------
            | Prepare Stock Update Query
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | Each placeholder has a unique name.
            | This prevents PDO HY093 errors.
            |
            */

            $updateStock = $pdo->prepare("
                UPDATE products

                SET stock = stock - :quantity_set

                WHERE id = :product_id

                AND stock >= :quantity_check
            ");


            /*
            |--------------------------------------------------------------------------
            | Insert Order Items + Reduce Stock
            |--------------------------------------------------------------------------
            */

            foreach ($cartItems as $item) {

                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];
                $productId = (int) $item['product_id'];


                /*
                | Insert Order Item
                */

                $insertOrderItem->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $productId,
                    ':quantity' => $quantity,
                    ':price' => $price
                ]);


                /*
                | Reduce Product Stock
                */

                $updateStock->execute([
                    ':quantity_set' => $quantity,
                    ':product_id' => $productId,
                    ':quantity_check' => $quantity
                ]);


                /*
                | Verify Stock Update
                */

                if ($updateStock->rowCount() !== 1) {

                    throw new Exception(
                        'Unable to update stock for ' .
                        $item['name']
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Clear Cart
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                DELETE FROM cart
                WHERE user_id = :user_id
            ");

            $stmt->execute([
                ':user_id' => $userId
            ]);


            /*
            |--------------------------------------------------------------------------
            | Update User Information
            |--------------------------------------------------------------------------
            */

            $savedAddress =
                $address .
                ', ' .
                $city .
                ', ' .
                $state .
                ' - ' .
                $pincode;

            $stmt = $pdo->prepare("
                UPDATE users

                SET
                    name = :name,
                    phone = :phone,
                    address = :address

                WHERE id = :user_id
            ");

            $stmt->execute([
                ':name' => $fullName,
                ':phone' => $phone,
                ':address' => $savedAddress,
                ':user_id' => $userId
            ]);


            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            header(
                'Location: order-success.php?order_id=' .
                $orderId
            );

            exit;


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $e->getMessage();


        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'Unable to place your order. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fetch Cart for Checkout Display
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        c.id AS cart_id,
        c.product_id,
        c.quantity,

        p.name,
        p.brand,
        p.price,
        p.stock,
        p.image

    FROM cart c

    INNER JOIN products p
        ON c.product_id = p.id

    WHERE c.user_id = :user_id

    ORDER BY c.created_at DESC
");

$stmt->execute([
    ':user_id' => $userId
]);

$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Empty Cart
|--------------------------------------------------------------------------
*/

if (empty($cartItems)) {

    header('Location: cart.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Calculate Checkout Total
|--------------------------------------------------------------------------
*/

$cartTotal = 0;
$totalItems = 0;

foreach ($cartItems as &$item) {

    $item['subtotal'] =
        (float) $item['price'] *
        (int) $item['quantity'];

    $cartTotal += $item['subtotal'];

    $totalItems +=
        (int) $item['quantity'];
}

unset($item);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout - Advaya Watch Store</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        .checkout-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }


        .checkout-header {
            margin-bottom: 30px;
        }


        .checkout-header h1 {
            margin-bottom: 8px;
        }


        .checkout-header p {
            opacity: 0.7;
        }


        .checkout-errors {
            margin-bottom: 25px;
            padding: 16px 20px;
            border-radius: 10px;
            background: rgba(239, 68, 68, 0.10);
            border: 1px solid rgba(239, 68, 68, 0.25);
        }


        .checkout-errors div {
            margin: 5px 0;
        }


        .checkout-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 30px;
            align-items: start;
        }


        .checkout-card {
            padding: 28px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }


        .checkout-card h2 {
            margin-bottom: 22px;
        }


        .form-group {
            margin-bottom: 18px;
        }


        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }


        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 500;
        }


        .form-group input,
        .form-group textarea {

            width: 100%;
            box-sizing: border-box;

            padding: 13px 14px;

            border-radius: 8px;

            border: 1px solid rgba(255, 255, 255, 0.15);

            background: rgba(255, 255, 255, 0.04);

            color: inherit;

            outline: none;

            font-family: inherit;

            font-size: 15px;
        }


        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }


        .form-group input:focus,
        .form-group textarea:focus {
            border-color: rgba(255, 255, 255, 0.4);
        }


        .payment-option {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 16px;

            border-radius: 10px;

            border: 1px solid rgba(255, 255, 255, 0.15);

            background: rgba(255, 255, 255, 0.04);

            cursor: pointer;
        }


        .payment-option input {
            width: 18px;
            height: 18px;
        }


        .payment-title {
            font-weight: 600;
            display: block;
        }


        .payment-description {
            font-size: 13px;
            opacity: 0.6;
            display: block;
            margin-top: 3px;
        }


        .order-summary {

            position: sticky;

            top: 25px;

            padding: 25px;

            border-radius: 14px;

            background: rgba(255, 255, 255, 0.03);

            border: 1px solid rgba(255, 255, 255, 0.08);
        }


        .order-summary h2 {
            margin-bottom: 20px;
        }


        .checkout-product {

            display: grid;

            grid-template-columns: 65px 1fr auto;

            gap: 12px;

            align-items: center;

            padding: 12px 0;

            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }


        .checkout-product-image {

            width: 65px;

            height: 65px;

            border-radius: 8px;

            overflow: hidden;

            background: #20242a;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .checkout-product-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;
        }


        .checkout-product-image span {

            font-size: 10px;

            opacity: 0.6;
        }


        .checkout-product-name {

            font-weight: 600;

            font-size: 14px;
        }


        .checkout-product-meta {

            font-size: 12px;

            opacity: 0.6;

            margin-top: 4px;
        }


        .checkout-product-price {

            font-size: 14px;

            font-weight: 600;

            white-space: nowrap;
        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            gap: 15px;

            padding: 12px 0;
        }


        .summary-total {

            border-top: 1px solid rgba(255, 255, 255, 0.12);

            margin-top: 8px;

            padding-top: 18px;

            font-size: 1.15rem;

            font-weight: 700;
        }


        .place-order-button {

            width: 100%;

            padding: 15px;

            margin-top: 20px;

            border: none;

            border-radius: 8px;

            background: #ffffff;

            color: #111111;

            font-size: 16px;

            font-weight: 700;

            cursor: pointer;

            transition: opacity 0.2s ease;
        }


        .place-order-button:hover {
            opacity: 0.85;
        }


        .place-order-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }


        .back-cart {

            display: inline-block;

            margin-bottom: 25px;

            text-decoration: none;

            opacity: 0.7;
        }


        .back-cart:hover {
            opacity: 1;
        }


        @media (max-width: 850px) {

            .checkout-layout {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }

        }


        @media (max-width: 600px) {

            .form-row {
                grid-template-columns: 1fr;
            }

            .checkout-card {
                padding: 20px;
            }

            .checkout-container {
                padding: 15px;
            }

        }

    </style>

</head>


<body>


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
                <a href="cart.php">
                    Cart
                </a>
            </li>

            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li>
                    <a href="admin/dashboard.php">
                        Admin Panel
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="logout.php">
                    Logout
                </a>
            </li>

        </ul>

    </div>

</header>


<main>

    <div class="checkout-container">


        <a
            href="cart.php"
            class="back-cart"
        >
            ← Back to Cart
        </a>


        <div class="checkout-header">

            <h1>
                Checkout
            </h1>

            <p>
                Complete your delivery details and place your order.
            </p>

        </div>


        <?php if (!empty($errors)): ?>

            <div class="checkout-errors">

                <?php foreach ($errors as $error): ?>

                    <div>
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            id="checkout-form"
        >

            <div class="checkout-layout">


                <!-- LEFT SIDE -->

                <div>


                    <!-- DELIVERY INFORMATION -->

                    <section class="checkout-card">

                        <h2>
                            Delivery Information
                        </h2>


                        <div class="form-group">

                            <label for="full_name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= htmlspecialchars($fullName) ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="phone">
                                Phone Number
                            </label>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($phone) ?>"
                                maxlength="10"
                                pattern="[0-9]{10}"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="address">
                                Address
                            </label>

                            <textarea
                                id="address"
                                name="address"
                                required
                            ><?= htmlspecialchars($address) ?></textarea>

                        </div>


                        <div class="form-row">

                            <div class="form-group">

                                <label for="city">
                                    City
                                </label>

                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    value="<?= htmlspecialchars($city) ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="state">
                                    State
                                </label>

                                <input
                                    type="text"
                                    id="state"
                                    name="state"
                                    value="<?= htmlspecialchars($state) ?>"
                                    required
                                >

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="pincode">
                                Pincode
                            </label>

                            <input
                                type="text"
                                id="pincode"
                                name="pincode"
                                value="<?= htmlspecialchars($pincode) ?>"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                required
                            >

                        </div>

                    </section>


                    <!-- PAYMENT -->

                    <section class="checkout-card">

                        <h2>
                            Payment Method
                        </h2>


                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="Cash on Delivery"
                                checked
                                required
                            >

                            <span>

                                <span class="payment-title">
                                    Cash on Delivery
                                </span>

                                <span class="payment-description">
                                    Pay when your watch is delivered.
                                </span>

                            </span>

                        </label>

                    </section>

                </div>


                <!-- RIGHT SIDE -->

                <aside class="order-summary">

                    <h2>
                        Order Summary
                    </h2>


                    <?php foreach ($cartItems as $item): ?>

                        <div class="checkout-product">


                            <div class="checkout-product-image">

                                <?php if (
                                    !empty($item['image']) &&
                                    file_exists(
                                        __DIR__ .
                                        '/uploads/' .
                                        $item['image']
                                    )
                                ): ?>

                                    <img
                                        src="uploads/<?= htmlspecialchars(
                                            $item['image']
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $item['name']
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <span>
                                        <?= htmlspecialchars(
                                            $item['brand']
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div>

                                <div class="checkout-product-name">

                                    <?= htmlspecialchars(
                                        $item['name']
                                    ) ?>

                                </div>


                                <div class="checkout-product-meta">

                                    <?= htmlspecialchars(
                                        $item['brand']
                                    ) ?>

                                    ×

                                    <?= (int) $item['quantity'] ?>

                                </div>

                            </div>


                            <div class="checkout-product-price">

                                $
                                <?= number_format(
                                    $item['subtotal'],
                                    2
                                ) ?>

                            </div>

                        </div>

                    <?php endforeach; ?>


                    <div class="summary-row">

                        <span>
                            Total Items
                        </span>

                        <span>
                            <?= $totalItems ?>
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <span>
                            $
                            <?= number_format(
                                $cartTotal,
                                2
                            ) ?>
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            Shipping
                        </span>

                        <span>
                            Free
                        </span>

                    </div>


                    <div class="summary-row summary-total">

                        <span>
                            Total
                        </span>

                        <span>
                            $
                            <?= number_format(
                                $cartTotal,
                                2
                            ) ?>
                        </span>

                    </div>


                    <button
                        type="submit"
                        class="place-order-button"
                        id="place-order-button"
                    >
                        Place Order
                    </button>

                </aside>

            </div>

        </form>

    </div>

</main>


<script>

document
    .getElementById('checkout-form')
    .addEventListener('submit', function () {

        const button =
            document.getElementById(
                'place-order-button'
            );

        button.disabled = true;

        button.textContent =
            'Placing Order...';

    });

</script>


</body>

</html>