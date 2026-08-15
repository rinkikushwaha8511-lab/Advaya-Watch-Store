<?php

require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$product = null;
$error = '';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId) {
    $error = 'Invalid product.';
} else {

    try {

        $pdo = getDBConnection();

        $stmt = $pdo->prepare(
            "SELECT
                p.id,
                p.name,
                p.brand,
                p.description,
                p.price,
                p.stock,
                p.image,
                p.status,
                c.name AS category_name
             FROM products p
             LEFT JOIN categories c
                ON p.category_id = c.id
             WHERE p.id = :id
             AND p.status = 'active'
             LIMIT 1"
        );

        $stmt->execute([
            ':id' => $productId
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $error = 'Product not found.';
        }

    } catch (PDOException $e) {

        $error = 'Unable to load product details.';

    }
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

    <title>
        <?= $product
            ? htmlspecialchars($product['name']) . ' - Advaya'
            : 'Product - Advaya'
        ?>
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        .product-detail-container {
            max-width: 1100px;
            margin: 60px auto;
            padding: 20px;
        }

        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .product-detail-image {
            position: relative;
            min-height: 500px;
            border-radius: 12px;
            overflow: hidden;
        }

        .product-detail-image img {
            width: 100%;
            height: 100%;
            min-height: 500px;
            object-fit: cover;
        }

        .product-detail-image .image-placeholder {
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #20242a;
            font-size: 1.5rem;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .product-category {
            display: inline-block;
            margin-bottom: 10px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.7;
        }

        .product-brand-detail {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 10px;
            opacity: 0.7;
        }

        .product-detail h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .product-detail-description {
            line-height: 1.8;
            opacity: 0.8;
            margin-bottom: 25px;
        }

        .product-detail-price {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .product-detail-stock {
            margin-bottom: 25px;
        }

        .in-stock {
            color: #22c55e;
        }

        .out-stock {
            color: #ef4444;
        }

        .quantity-group {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .quantity-group label {
            font-weight: 600;
        }

        .quantity-group input {
            width: 80px;
            padding: 10px;
        }

        .add-cart-btn {
            display: inline-block;
            width: 100%;
            padding: 15px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            text-decoration: none;
        }

        .error-message {
            text-align: center;
            padding: 50px 20px;
        }

        @media (max-width: 768px) {

            .product-detail {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .product-detail h1 {
                font-size: 2rem;
            }

            .product-detail-image,
            .product-detail-image img,
            .product-detail-image .image-placeholder {
                min-height: 350px;
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
            id="header-logo"
        >
            Advaya <span>Watch Store</span>
        </a>


        <ul
            class="nav-links"
            id="header-navigation"
        >

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


            <?php if (isset($_SESSION['user_id'])): ?>

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

            <?php else: ?>

                <li>
                    <a href="user/login.php">
                        Login
                    </a>
                </li>

                <li>
                    <a href="user/register.php">
                        Register
                    </a>
                </li>

            <?php endif; ?>

        </ul>

    </div>

</header>


<main>

    <div class="product-detail-container">


        <a
            href="index.php#catalog"
            class="back-link"
        >
            ← Back to Collection
        </a>


        <?php if ($error): ?>

            <div class="error-message">

                <h2>
                    <?= htmlspecialchars($error) ?>
                </h2>

                <p>
                    The product you are looking for
                    could not be found.
                </p>

            </div>


        <?php else: ?>


            <div class="product-detail">


                <!-- Product Image -->

                <div class="product-detail-image">

                    <?php if (
                        !empty($product['image']) &&
                        file_exists(
                            __DIR__ . '/uploads/' . $product['image']
                        )
                    ): ?>

                        <img
                            src="uploads/<?= htmlspecialchars($product['image']) ?>"
                            alt="<?= htmlspecialchars($product['name']) ?>"
                        >

                    <?php else: ?>

                        <div class="image-placeholder">

                            <?= htmlspecialchars($product['brand']) ?>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- Product Information -->

                <div>


                    <?php if (!empty($product['category_name'])): ?>

                        <span class="product-category">

                            <?= htmlspecialchars(
                                $product['category_name']
                            ) ?>

                        </span>

                    <?php endif; ?>


                    <div class="product-brand-detail">

                        <?= htmlspecialchars($product['brand']) ?>

                    </div>


                    <h1>

                        <?= htmlspecialchars($product['name']) ?>

                    </h1>


                    <p class="product-detail-description">

                        <?= htmlspecialchars($product['description']) ?>

                    </p>


                    <div class="product-detail-price">

                        $<?= number_format(
                            (float) $product['price'],
                            2
                        ) ?>

                    </div>


                    <div class="product-detail-stock">

                        <?php if ((int) $product['stock'] > 0): ?>

                            <span class="in-stock">

                                ✓ In Stock

                            </span>

                            <span>
                                — <?= (int) $product['stock'] ?>
                                available
                            </span>

                        <?php else: ?>

                            <span class="out-stock">

                                ✕ Out of Stock

                            </span>

                        <?php endif; ?>

                    </div>


                    <?php if ((int) $product['stock'] > 0): ?>

                        <form
                            method="POST"
                            action="cart.php"
                        >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int) $product['id'] ?>"
                            >


                            <div class="quantity-group">

                                <label for="quantity">
                                    Quantity:
                                </label>

                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="<?= (int) $product['stock'] ?>"
                                    required
                                >

                            </div>


                            <button
                                type="submit"
                                name="add_to_cart"
                                class="add-cart-btn"
                            >
                                Add to Cart
                            </button>

                        </form>

                    <?php else: ?>

                        <button
                            type="button"
                            class="add-cart-btn"
                            disabled
                        >
                            Out of Stock
                        </button>

                    <?php endif; ?>


                </div>

            </div>


        <?php endif; ?>


    </div>

</main>


<!-- Footer -->

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