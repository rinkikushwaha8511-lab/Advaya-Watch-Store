<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$cartItems = [];
$cartTotal = 0;
$totalItems = 0;
$error = '';
$success = '';

try {

    $pdo = getDBConnection();

    /*
    |--------------------------------------------------------------------------
    | ADD TO CART
    |--------------------------------------------------------------------------
    */

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['add_to_cart'])
    ) {

        $productId = filter_input(
            INPUT_POST,
            'product_id',
            FILTER_VALIDATE_INT
        );

        $quantity = filter_input(
            INPUT_POST,
            'quantity',
            FILTER_VALIDATE_INT
        );

        if (!$productId || !$quantity || $quantity < 1) {

            $error = 'Invalid product or quantity.';

        } else {

            $stmt = $pdo->prepare(
                "SELECT id, name, price, stock, status
                 FROM products
                 WHERE id = :id
                 LIMIT 1"
            );

            $stmt->execute([
                ':id' => $productId
            ]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {

                $error = 'Product not found.';

            } elseif ($product['status'] !== 'active') {

                $error = 'This product is currently unavailable.';

            } elseif ((int) $product['stock'] <= 0) {

                $error = 'This product is out of stock.';

            } else {

                $stmt = $pdo->prepare(
                    "SELECT id, quantity
                     FROM cart
                     WHERE user_id = :user_id
                     AND product_id = :product_id
                     LIMIT 1"
                );

                $stmt->execute([
                    ':user_id' => $_SESSION['user_id'],
                    ':product_id' => $productId
                ]);

                $existingCartItem = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingCartItem) {

                    $newQuantity =
                        (int) $existingCartItem['quantity']
                        + $quantity;

                    $newQuantity = min(
                        $newQuantity,
                        (int) $product['stock']
                    );

                    $stmt = $pdo->prepare(
                        "UPDATE cart
                         SET quantity = :quantity
                         WHERE id = :id
                         AND user_id = :user_id"
                    );

                    $stmt->execute([
                        ':quantity' => $newQuantity,
                        ':id' => $existingCartItem['id'],
                        ':user_id' => $_SESSION['user_id']
                    ]);

                } else {

                    $quantity = min(
                        $quantity,
                        (int) $product['stock']
                    );

                    $stmt = $pdo->prepare(
                        "INSERT INTO cart
                         (user_id, product_id, quantity)
                         VALUES
                         (:user_id, :product_id, :quantity)"
                    );

                    $stmt->execute([
                        ':user_id' => $_SESSION['user_id'],
                        ':product_id' => $productId,
                        ':quantity' => $quantity
                    ]);
                }

                header('Location: cart.php?added=1');
                exit;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX QUANTITY UPDATE
    |--------------------------------------------------------------------------
    */

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['ajax_update_quantity'])
    ) {

        header('Content-Type: application/json');

        $cartId = filter_input(
            INPUT_POST,
            'cart_id',
            FILTER_VALIDATE_INT
        );

        $quantity = filter_input(
            INPUT_POST,
            'quantity',
            FILTER_VALIDATE_INT
        );

        if (!$cartId || !$quantity || $quantity < 1) {

            echo json_encode([
                'success' => false,
                'message' => 'Invalid quantity.'
            ]);

            exit;
        }


        /*
        | Get cart item
        */

        $stmt = $pdo->prepare(
            "SELECT
                c.id,
                c.product_id,
                p.price,
                p.stock

             FROM cart c

             INNER JOIN products p
                ON c.product_id = p.id

             WHERE c.id = :cart_id
             AND c.user_id = :user_id

             LIMIT 1"
        );

        $stmt->execute([
            ':cart_id' => $cartId,
            ':user_id' => $_SESSION['user_id']
        ]);

        $item = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$item) {

            echo json_encode([
                'success' => false,
                'message' => 'Cart item not found.'
            ]);

            exit;
        }


        /*
        | Stock validation
        */

        $stock = (int) $item['stock'];

        if ($quantity > $stock) {
            $quantity = $stock;
        }


        if ($quantity < 1) {

            echo json_encode([
                'success' => false,
                'message' => 'Quantity cannot be less than 1.'
            ]);

            exit;
        }


        /*
        | Update database
        */

        $stmt = $pdo->prepare(
            "UPDATE cart

             SET quantity = :quantity

             WHERE id = :cart_id
             AND user_id = :user_id"
        );

        $stmt->execute([
            ':quantity' => $quantity,
            ':cart_id' => $cartId,
            ':user_id' => $_SESSION['user_id']
        ]);


        /*
        | Item subtotal
        */

        $itemSubtotal =
            (float) $item['price']
            * $quantity;


        /*
        | Cart summary
        */

        $stmt = $pdo->prepare(
            "SELECT
                COALESCE(SUM(c.quantity), 0) AS total_items,

                COALESCE(
                    SUM(c.quantity * p.price),
                    0
                ) AS cart_total

             FROM cart c

             INNER JOIN products p
                ON c.product_id = p.id

             WHERE c.user_id = :user_id"
        );

        $stmt->execute([
            ':user_id' => $_SESSION['user_id']
        ]);

        $cartSummary = $stmt->fetch(PDO::FETCH_ASSOC);


        echo json_encode([
            'success' => true,

            'quantity' => $quantity,

            'item_subtotal' => number_format(
                $itemSubtotal,
                2
            ),

            'cart_total' => number_format(
                (float) $cartSummary['cart_total'],
                2
            ),

            'total_items' => (int) $cartSummary['total_items']
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE ITEM
    |--------------------------------------------------------------------------
    */

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['remove_item'])
    ) {

        $cartId = filter_input(
            INPUT_POST,
            'cart_id',
            FILTER_VALIDATE_INT
        );

        if ($cartId) {

            $stmt = $pdo->prepare(
                "DELETE FROM cart
                 WHERE id = :cart_id
                 AND user_id = :user_id"
            );

            $stmt->execute([
                ':cart_id' => $cartId,
                ':user_id' => $_SESSION['user_id']
            ]);
        }

        header('Location: cart.php?removed=1');
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR CART
    |--------------------------------------------------------------------------
    */

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['clear_cart'])
    ) {

        $stmt = $pdo->prepare(
            "DELETE FROM cart
             WHERE user_id = :user_id"
        );

        $stmt->execute([
            ':user_id' => $_SESSION['user_id']
        ]);

        header('Location: cart.php?cleared=1');
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | FETCH CART
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT

            c.id AS cart_id,
            c.quantity,

            p.id AS product_id,
            p.name,
            p.brand,
            p.description,
            p.price,
            p.stock,
            p.image,

            cat.name AS category_name

         FROM cart c

         INNER JOIN products p
            ON c.product_id = p.id

         LEFT JOIN categories cat
            ON p.category_id = cat.id

         WHERE c.user_id = :user_id

         ORDER BY c.created_at DESC"
    );

    $stmt->execute([
        ':user_id' => $_SESSION['user_id']
    ]);

    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | CALCULATE TOTAL
    |--------------------------------------------------------------------------
    */

    foreach ($cartItems as &$item) {

        $item['item_total'] =
            (float) $item['price']
            * (int) $item['quantity'];

        $cartTotal += $item['item_total'];

        $totalItems += (int) $item['quantity'];
    }

    unset($item);


    /*
    |--------------------------------------------------------------------------
    | MESSAGES
    |--------------------------------------------------------------------------
    */

    if (isset($_GET['added'])) {
        $success = 'Product added to your cart.';
    }

    if (isset($_GET['removed'])) {
        $success = 'Item removed from your cart.';
    }

    if (isset($_GET['cleared'])) {
        $success = 'Your cart has been cleared.';
    }


} catch (PDOException $e) {

    $error = 'Unable to process your cart.';

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

    <title>Shopping Cart - Advaya</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* ==================================================
           CART PAGE
           ================================================== */

        .cart-container {
            max-width: 1150px;
            margin: 60px auto;
            padding: 20px;
        }


        .cart-header {
            margin-bottom: 30px;
        }


        .cart-header h1 {
            margin-bottom: 8px;
        }


        .cart-header p {
            opacity: 0.7;
        }


        /* ==================================================
           MESSAGES
           ================================================== */

        .message {
            padding: 14px 18px;
            margin-bottom: 20px;
            border-radius: 8px;
        }


        .success-message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }


        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }


        /* ==================================================
           CART LAYOUT
           ================================================== */

        .cart-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 30px;
            align-items: start;
        }


        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }


        /* ==================================================
           CART ITEM
           ================================================== */

        .cart-item {
            display: grid;
            grid-template-columns: 120px minmax(0, 1fr) auto;
            gap: 22px;
            align-items: center;

            padding: 20px;

            border-radius: 14px;

            background: rgba(255, 255, 255, 0.03);

            border: 1px solid rgba(255, 255, 255, 0.08);
        }


        /* ==================================================
           PRODUCT IMAGE
           ================================================== */

        .cart-item-image {
            width: 120px;
            height: 120px;

            border-radius: 10px;

            overflow: hidden;

            background: #20242a;

            display: flex;
            align-items: center;
            justify-content: center;
        }


        .cart-item-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        .cart-item-image span {
            font-size: 12px;
            opacity: 0.7;
        }


        /* ==================================================
           PRODUCT INFO
           ================================================== */

        .cart-item-brand {
            font-size: 0.75rem;

            text-transform: uppercase;

            letter-spacing: 2px;

            opacity: 0.6;
        }


        .cart-item-name {
            margin: 5px 0;

            font-size: 1.15rem;
        }


        .cart-item-category {
            font-size: 0.85rem;

            opacity: 0.6;
        }


        .cart-item-price {
            margin-top: 12px;

            font-size: 1rem;

            font-weight: 600;
        }


        .cart-item-price small {
            font-size: 0.75rem;

            font-weight: normal;

            opacity: 0.6;
        }


        /* ==================================================
           ACTION AREA
           ================================================== */

        .cart-item-actions {
            display: flex;

            flex-direction: column;

            align-items: flex-end;

            gap: 12px;
        }


        /* ==================================================
           QUANTITY CONTROLLER
           ================================================== */

        .quantity-controller {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            height: 42px;

            border: 1px solid rgba(255, 255, 255, 0.18);

            border-radius: 8px;

            overflow: hidden;

            background: rgba(255, 255, 255, 0.05);
        }


        /* PLUS / MINUS */

        .quantity-btn {
            width: 42px;
            height: 42px;

            padding: 0;
            margin: 0;

            border: none;

            outline: none;

            display: flex;

            align-items: center;

            justify-content: center;

            background: transparent;

            color: inherit;

            font-size: 20px;

            font-weight: 500;

            line-height: 1;

            cursor: pointer;

            transition:
                background 0.2s ease,
                opacity 0.2s ease;
        }


        .quantity-btn:hover {
            background: rgba(255, 255, 255, 0.10);
        }


        .quantity-btn:active {
            background: rgba(255, 255, 255, 0.15);
        }


        /* ==================================================
           QUANTITY NUMBER
           ================================================== */

        .quantity-input {
            width: 45px;

            min-width: 45px;

            max-width: 45px;

            height: 42px;

            padding: 0;

            margin: 0;

            border: none;

            outline: none;

            background: transparent;

            color: #ffffff;

            text-align: center;

            font-size: 16px;

            font-weight: 600;

            line-height: 42px;

            display: block;

            box-sizing: border-box;

            opacity: 1;

            visibility: visible;

            -webkit-text-fill-color: #ffffff;
        }


        /* Remove number input arrows */

        .quantity-input::-webkit-outer-spin-button,
        .quantity-input::-webkit-inner-spin-button {

            -webkit-appearance: none;

            margin: 0;
        }


        .quantity-input {

            -moz-appearance: textfield;
        }


        /* Disabled button */

        .quantity-btn:disabled {

            opacity: 0.5;

            cursor: not-allowed;
        }


        /* ==================================================
           ITEM TOTAL
           ================================================== */

        .item-total {

            font-size: 1.05rem;

            font-weight: 700;

            min-width: 90px;

            text-align: right;
        }


        /* ==================================================
           REMOVE
           ================================================== */

        .remove-button {

            background: transparent;

            border: none;

            color: inherit;

            opacity: 0.65;

            cursor: pointer;

            text-decoration: underline;

            padding: 5px;
        }


        .remove-button:hover {

            opacity: 1;
        }


        /* ==================================================
           CART SUMMARY
           ================================================== */

        .cart-summary {

            padding: 25px;

            border-radius: 14px;

            background: rgba(255, 255, 255, 0.03);

            border: 1px solid rgba(255, 255, 255, 0.08);

            position: sticky;

            top: 30px;
        }


        .cart-summary h2 {

            margin-bottom: 20px;
        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            gap: 15px;

            padding: 12px 0;
        }


        .summary-total {

            border-top:
                1px solid rgba(255, 255, 255, 0.12);

            margin-top: 10px;

            padding-top: 20px;

            font-size: 1.2rem;

            font-weight: 700;
        }


        /* ==================================================
           CHECKOUT
           ================================================== */

        .checkout-button {

            display: block;

            width: 100%;

            padding: 14px;

            margin-top: 20px;

            text-align: center;

            text-decoration: none;

            box-sizing: border-box;

            border-radius: 8px;

            background: #ffffff;

            color: #111111;

            font-weight: 600;

            cursor: pointer;

            transition: opacity 0.2s ease;
        }


        .checkout-button:hover {

            opacity: 0.85;
        }


        /* ==================================================
           CLEAR CART
           ================================================== */

        .clear-cart-form {

            margin-top: 10px;

            text-align: right;
        }


        .clear-button {

            padding: 8px 15px;

            cursor: pointer;

            border: none;

            background: transparent;

            color: inherit;

            opacity: 0.65;

            text-decoration: underline;
        }


        .clear-button:hover {

            opacity: 1;
        }


        /* ==================================================
           EMPTY CART
           ================================================== */

        .empty-cart {

            text-align: center;

            padding: 80px 20px;
        }


        .empty-cart h2 {

            margin-bottom: 10px;
        }


        .continue-shopping {

            display: inline-block;

            margin-top: 20px;

            padding: 12px 22px;

            text-decoration: none;

            border-radius: 8px;
        }


        /* ==================================================
           MOBILE
           ================================================== */

        @media (max-width: 850px) {

            .cart-layout {

                grid-template-columns: 1fr;
            }


            .cart-summary {

                position: static;
            }

        }


        @media (max-width: 650px) {

            .cart-item {

                grid-template-columns: 85px minmax(0, 1fr);

                gap: 15px;
            }


            .cart-item-image {

                width: 85px;

                height: 85px;
            }


            .cart-item-actions {

                grid-column: 1 / -1;

                align-items: flex-start;

                width: 100%;
            }


            .item-total {

                text-align: left;
            }

        }

            .continue-shopping-top {
    margin-bottom: 20px;
}

.continue-shopping-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 11px 18px;

    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;

    text-decoration: none;

    color: inherit;

    background: rgba(255, 255, 255, 0.04);

    transition: all 0.2s ease;
}

.continue-shopping-button:hover {
    background: rgba(255, 255, 255, 0.10);
    transform: translateX(-2px);
}

    </style>

</head>


<body>


<!-- ==================================================
     HEADER
     ================================================== -->

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

                <a
                    href="cart.php"
                    class="active"
                >

                    Cart

                    <?php if ($totalItems > 0): ?>

                        (
                        <span id="nav-cart-count">
                            <?= $totalItems ?>
                        </span>
                        )

                    <?php endif; ?>

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


<!-- ==================================================
     MAIN
     ================================================== -->

<main>

    <div class="cart-container">


        <div class="cart-header">

            <h1>
                Your Shopping Cart
            </h1>

            <p>
                Review your selected watches before checkout.
            </p>

        </div>


        <?php if ($success): ?>

            <div class="message success-message">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="message error-message">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <?php if (empty($cartItems)): ?>


            <div class="empty-cart">

                <h2>
                    Your cart is empty
                </h2>

                <p>
                    Discover a watch that matches your style.
                </p>


                <a
                    href="index.php#catalog"
                    class="continue-shopping"
                >
                    Continue Shopping
                </a>

            </div>


        <?php else: ?>


            <div class="continue-shopping-top">
               <a
                    href="index.php#catalog"
                    class="continue-shopping-button"
                >
                ← Continue Shopping
                </a>
            </div>


                <!-- ==================================================
                     CART ITEMS
                     ================================================== -->

                <div class="cart-items">


                    <?php foreach ($cartItems as $item): ?>


                        <div
                            class="cart-item"
                            id="cart-item-<?= (int) $item['cart_id'] ?>"
                        >


                            <!-- PRODUCT IMAGE -->

                            <div class="cart-item-image">

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


                            <!-- PRODUCT DETAILS -->

                            <div>

                                <div class="cart-item-brand">

                                    <?= htmlspecialchars(
                                        $item['brand']
                                    ) ?>

                                </div>


                                <h3 class="cart-item-name">

                                    <?= htmlspecialchars(
                                        $item['name']
                                    ) ?>

                                </h3>


                                <?php if (
                                    !empty($item['category_name'])
                                ): ?>

                                    <div class="cart-item-category">

                                        <?= htmlspecialchars(
                                            $item['category_name']
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                                <div class="cart-item-price">

                                    $
                                    <?= number_format(
                                        (float) $item['price'],
                                        2
                                    ) ?>

                                    <small>
                                        / item
                                    </small>

                                </div>

                            </div>


                            <!-- ACTIONS -->

                            <div class="cart-item-actions">


                                <!-- ==================================================
                                     QUANTITY CONTROLLER
                                     ================================================== -->

                                <div
                                    class="quantity-controller"
                                    data-cart-id="<?= (int) $item['cart_id'] ?>"
                                    data-stock="<?= (int) $item['stock'] ?>"
                                >


                                    <button
                                        type="button"
                                        class="quantity-btn decrease-btn"
                                        aria-label="Decrease quantity"
                                    >
                                        −
                                    </button>


                                    <input
                                        type="number"
                                        class="quantity-input"
                                        value="<?= (int) $item['quantity'] ?>"
                                        min="1"
                                        max="<?= (int) $item['stock'] ?>"
                                        readonly
                                        aria-label="Product quantity"
                                    >


                                    <button
                                        type="button"
                                        class="quantity-btn increase-btn"
                                        aria-label="Increase quantity"
                                    >
                                        +
                                    </button>


                                </div>


                                <!-- ITEM TOTAL -->

                                <div
                                    class="item-total"
                                    id="item-total-<?= (int) $item['cart_id'] ?>"
                                >

                                    $
                                    <?= number_format(
                                        (float) $item['item_total'],
                                        2
                                    ) ?>

                                </div>


                                <!-- REMOVE -->

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="cart_id"
                                        value="<?= (int) $item['cart_id'] ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="remove_item"
                                        class="remove-button"
                                    >
                                        Remove
                                    </button>

                                </form>


                            </div>


                        </div>


                    <?php endforeach; ?>


                    <!-- CLEAR CART -->

                    <form
                        method="POST"
                        class="clear-cart-form"
                    >

                        <button
                            type="submit"
                            name="clear_cart"
                            class="clear-button"
                        >
                            Clear Cart
                        </button>

                    </form>


                </div>


                <!-- ==================================================
                     ORDER SUMMARY
                     ================================================== -->

                <aside class="cart-summary">


                    <h2>
                        Order Summary
                    </h2>


                    <div class="summary-row">

                        <span>
                            Items
                        </span>

                        <span id="summary-items">
                            <?= $totalItems ?>
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <span id="summary-subtotal">

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

                        <span id="summary-total">

                            $
                            <?= number_format(
                                $cartTotal,
                                2
                            ) ?>

                        </span>

                    </div>


                    <a
                        href="checkout.php"
                        class="checkout-button"
                    >
                        Proceed to Checkout
                    </a>

                </aside>


            </div>


        <?php endif; ?>


    </div>

</main>


<!-- ==================================================
     JAVASCRIPT
     ================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const controllers =
            document.querySelectorAll(
                '.quantity-controller'
            );


        controllers.forEach(
            function (controller) {


                const decreaseButton =
                    controller.querySelector(
                        '.decrease-btn'
                    );


                const increaseButton =
                    controller.querySelector(
                        '.increase-btn'
                    );


                const quantityInput =
                    controller.querySelector(
                        '.quantity-input'
                    );


                const cartId =
                    controller.dataset.cartId;


                const stock =
                    parseInt(
                        controller.dataset.stock,
                        10
                    );


                /*
                |--------------------------------------------------------------------------
                | Update Quantity
                |--------------------------------------------------------------------------
                */

                function updateQuantity(quantity) {

                    quantity =
                        parseInt(
                            quantity,
                            10
                        );


                    if (isNaN(quantity)) {
                        return;
                    }


                    if (quantity < 1) {

                        quantity = 1;

                    }


                    if (quantity > stock) {

                        quantity = stock;

                    }


                    /*
                    | Show number immediately
                    */

                    quantityInput.value =
                        quantity;


                    /*
                    | Disable buttons during request
                    */

                    decreaseButton.disabled =
                        true;

                    increaseButton.disabled =
                        true;


                    /*
                    | Prepare AJAX request
                    */

                    const formData =
                        new FormData();


                    formData.append(
                        'ajax_update_quantity',
                        '1'
                    );


                    formData.append(
                        'cart_id',
                        cartId
                    );


                    formData.append(
                        'quantity',
                        quantity
                    );


                    /*
                    | Send request
                    */

                    fetch(
                        'cart.php',
                        {
                            method: 'POST',
                            body: formData
                        }
                    )

                    .then(
                        function (response) {

                            return response.json();

                        }
                    )

                    .then(
                        function (data) {


                            if (!data.success) {

                                alert(
                                    data.message ||
                                    'Unable to update cart.'
                                );

                                return;
                            }


                            /*
                            | Update quantity
                            */

                            quantityInput.value =
                                data.quantity;


                            /*
                            | Update item subtotal
                            */

                            const itemTotal =
                                document.getElementById(
                                    'item-total-' +
                                    cartId
                                );


                            if (itemTotal) {

                                itemTotal.textContent =
                                    '$' +
                                    data.item_subtotal;

                            }


                            /*
                            | Update cart subtotal
                            */

                            const summarySubtotal =
                                document.getElementById(
                                    'summary-subtotal'
                                );


                            if (summarySubtotal) {

                                summarySubtotal.textContent =
                                    '$' +
                                    data.cart_total;

                            }


                            /*
                            | Update total
                            */

                            const summaryTotal =
                                document.getElementById(
                                    'summary-total'
                                );


                            if (summaryTotal) {

                                summaryTotal.textContent =
                                    '$' +
                                    data.cart_total;

                            }


                            /*
                            | Update item count
                            */

                            const summaryItems =
                                document.getElementById(
                                    'summary-items'
                                );


                            if (summaryItems) {

                                summaryItems.textContent =
                                    data.total_items;

                            }


                            /*
                            | Update navbar cart count
                            */

                            const navCartCount =
                                document.getElementById(
                                    'nav-cart-count'
                                );


                            if (navCartCount) {

                                navCartCount.textContent =
                                    data.total_items;

                            }

                        }
                    )

                    .catch(
                        function (error) {

                            console.error(
                                'Cart update error:',
                                error
                            );

                            alert(
                                'Something went wrong while updating the cart.'
                            );

                        }
                    )

                    .finally(
                        function () {

                            decreaseButton.disabled =
                                false;

                            increaseButton.disabled =
                                false;

                        }
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Decrease Quantity
                |--------------------------------------------------------------------------
                */

                decreaseButton.addEventListener(
                    'click',
                    function () {


                        let quantity =
                            parseInt(
                                quantityInput.value,
                                10
                            );


                        if (isNaN(quantity)) {

                            quantity = 1;

                        }


                        if (quantity > 1) {

                            quantity--;

                            updateQuantity(
                                quantity
                            );

                        }

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Increase Quantity
                |--------------------------------------------------------------------------
                */

                increaseButton.addEventListener(
                    'click',
                    function () {


                        let quantity =
                            parseInt(
                                quantityInput.value,
                                10
                            );


                        if (isNaN(quantity)) {

                            quantity = 1;

                        }


                        if (quantity < stock) {

                            quantity++;

                            updateQuantity(
                                quantity
                            );

                        } else {

                            alert(
                                'You cannot add more than available stock.'
                            );

                        }

                    }
                );

            }
        );

    }
);

</script>


</body>

</html>