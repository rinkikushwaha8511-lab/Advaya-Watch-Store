<?php

require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_connected = false;
$db_error = '';
$products = [];
$cartCount = 0;

try {

    $pdo = getDBConnection();

    $db_connected = true;

    /*
     * ==========================================
     * FETCH ACTIVE PRODUCTS
     * ==========================================
     */

    $stmt = $pdo->query(
        "SELECT
            p.id,
            p.category_id,
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

         WHERE p.status = 'active'

         ORDER BY p.created_at DESC, p.id DESC"
    );

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
     * ==========================================
     * CART COUNT
     * ==========================================
     */

    if (isset($_SESSION['user_id'])) {

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(quantity), 0)
             FROM cart
             WHERE user_id = :user_id"
        );

        $stmt->execute([
            ':user_id' => $_SESSION['user_id']
        ]);

        $cartCount = (int) $stmt->fetchColumn();
    }

} catch (PDOException $e) {

    $db_connected = false;

    $db_error = $e->getMessage();

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
        Advaya - Premium Watch Store
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* ==========================================
           HERO
           ========================================== */

        .hero {
            padding: 70px 20px;
            text-align: center;
        }


        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }


        .hero h1 span {
            color: var(--primary);
        }


        .hero p {
            max-width: 750px;
            margin: 0 auto 30px;
            line-height: 1.8;
            opacity: 0.75;
        }


        /* ==========================================
           DATABASE STATUS
           ========================================== */

        .status-banner {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            border-radius: 30px;
            margin-bottom: 30px;
            font-size: 0.85rem;
        }


        .status-banner.success {
            background: rgba(34, 197, 94, 0.1);
        }


        .status-banner.error {
            background: rgba(239, 68, 68, 0.1);
        }


        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }


        /* ==========================================
           PRODUCTS
           ========================================== */

        .products-section {
            padding: 70px 20px;
        }


        .section-title {
            text-align: center;
            margin-bottom: 45px;
        }


        .product-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(260px, 1fr));

            gap: 25px;
        }


        .product-card {
            overflow: hidden;
            border-radius: 12px;
            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease;
        }


        .product-card:hover {
            transform: translateY(-5px);
        }


        /* ==========================================
           PRODUCT IMAGE
           ========================================== */

        .product-image-container {
            position: relative;
            height: 300px;
            overflow: hidden;
            background: #20242a;
        }


        .product-image-container a {
            display: block;
            width: 100%;
            height: 100%;
        }


        .product-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }


        .product-card:hover
        .product-image-container img {
            transform: scale(1.05);
        }


        .image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            letter-spacing: 2px;
            text-transform: uppercase;
        }


        /* ==========================================
           PRODUCT BADGE
           ========================================== */

        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            background: rgba(34, 197, 94, 0.9);
        }


        .product-badge.out-of-stock {
            background: rgba(239, 68, 68, 0.9);
        }


        /* ==========================================
           PRODUCT INFORMATION
           ========================================== */

        .product-info {
            padding: 22px;
        }


        .product-brand {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.6;
        }


        .product-title {
            margin: 7px 0 10px;
        }


        .product-title a {
            color: inherit;
            text-decoration: none;
        }


        .product-title a:hover {
            text-decoration: underline;
        }


        .product-description {
            font-size: 0.9rem;
            line-height: 1.6;
            opacity: 0.7;

            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;

            min-height: 68px;
        }


        /* ==========================================
           PRICE + STOCK
           ========================================== */

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            gap: 10px;
        }


        .product-price {
            font-size: 1.15rem;
            font-weight: 700;
        }


        .product-stock {
            font-size: 0.8rem;
            opacity: 0.65;
        }


        /* ==========================================
           PRODUCT ACTIONS
           ========================================== */

        .product-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 18px;
        }


        .product-action {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.15);
            text-decoration: none;
            cursor: pointer;
            box-sizing: border-box;
            font-size: 0.85rem;
        }


        .view-details {
            background: transparent;
        }


        .add-cart {
            border: none;
            font-weight: 600;
        }


        .add-cart:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }


        /* ==========================================
           EMPTY PRODUCTS
           ========================================== */

        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
        }


        /* ==========================================
           RESPONSIVE
           ========================================== */

        @media (max-width: 650px) {

            .hero h1 {
                font-size: 2.2rem;
            }


            .product-actions {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- ==========================================
     HEADER
     ========================================== -->

<header id="main-header">

    <div class="container header-content">


        <a
            href="index.php"
            class="logo"
            id="header-logo"
        >
            Advaya
            <span>Watch Store</span>
        </a>


        <ul
            class="nav-links"
            id="header-navigation"
        >

            <li>

                <a
                    href="index.php"
                    class="active"
                >
                    Home
                </a>

            </li>


            <li>

                <a href="#catalog">
                    Catalog
                </a>

            </li>


            <?php if (isset($_SESSION['user_id'])): ?>


                <li>

                    <a href="cart.php">

                        Cart

                        <?php if ($cartCount > 0): ?>

                            (<?= $cartCount ?>)

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



<!-- ==========================================
     MAIN
     ========================================== -->

<main>


    <!-- ======================================
         HERO
         ====================================== -->

    <section
        class="hero"
        id="hero-section"
    >

        <div class="container">


            <?php if ($db_connected): ?>

                <div
                    class="status-banner success"
                    id="connection-badge"
                >

                    <span class="status-indicator"></span>

                    Database Connected Successfully

                </div>


            <?php else: ?>


                <div
                    class="status-banner error"
                    id="connection-badge"
                >

                    <span class="status-indicator"></span>

                    Database Connection Failed

                </div>


                <div
                    style="
                        max-width:600px;
                        margin:20px auto;
                        padding:20px;
                        text-align:left;
                    "
                >

                    <strong>
                        Connection Error:
                    </strong>

                    <p>
                        <?= htmlspecialchars($db_error) ?>
                    </p>

                </div>


            <?php endif; ?>


            <h1 id="hero-title">

                A Legacy of

                <span>
                    Precision
                </span>

                & Style

            </h1>


            <p id="hero-subtitle">

                Discover timeless watches crafted for
                precision, elegance and individuality.

                Explore the Advaya collection and find
                the perfect timepiece for your style.

            </p>


        </div>

    </section>



    <!-- ======================================
         PRODUCT CATALOG
         ====================================== -->

    <?php if ($db_connected): ?>


        <section
            class="products-section"
            id="catalog"
        >

            <div class="container">


                <h2
                    class="section-title"
                    id="catalog-title"
                >
                    Explore the Collection
                </h2>


                <div
                    class="product-grid"
                    id="watch-grid"
                >


                    <?php if (empty($products)): ?>


                        <div class="no-products">

                            <h3>
                                No Products Available
                            </h3>

                            <p>
                                Please add products to the
                                database.
                            </p>

                        </div>


                    <?php else: ?>


                        <?php foreach ($products as $product): ?>


                            <article
                                class="product-card"
                                id="product-<?= (int) $product['id'] ?>"
                            >


                                <!-- ==========================
                                     PRODUCT IMAGE
                                     ========================== -->

                                <div
                                    class="product-image-container"
                                >


                                    <a
                                        href="product.php?id=<?= (int) $product['id'] ?>"
                                    >


                                        <?php if (
                                            !empty($product['image']) &&
                                            file_exists(
                                                __DIR__ .
                                                '/uploads/' .
                                                $product['image']
                                            )
                                        ): ?>


                                            <img
                                                src="uploads/<?= htmlspecialchars($product['image']) ?>"
                                                alt="<?= htmlspecialchars($product['name']) ?>"
                                                loading="lazy"
                                            >


                                        <?php else: ?>


                                            <div
                                                class="image-placeholder"
                                            >

                                                <?= htmlspecialchars(
                                                    $product['brand']
                                                ) ?>

                                            </div>


                                        <?php endif; ?>


                                    </a>


                                    <span
                                        class="
                                            product-badge
                                            <?= (
                                                (int) $product['stock'] > 0
                                            )
                                                ? ''
                                                : 'out-of-stock'
                                            ?>
                                        "
                                    >

                                        <?php if (
                                            (int) $product['stock'] > 0
                                        ): ?>

                                            In Stock

                                        <?php else: ?>

                                            Out of Stock

                                        <?php endif; ?>

                                    </span>


                                </div>



                                <!-- ==========================
                                     PRODUCT INFORMATION
                                     ========================== -->

                                <div class="product-info">


                                    <span class="product-brand">

                                        <?= htmlspecialchars(
                                            $product['brand']
                                        ) ?>

                                    </span>


                                    <h3 class="product-title">

                                        <a
                                            href="product.php?id=<?= (int) $product['id'] ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $product['name']
                                            ) ?>

                                        </a>

                                    </h3>


                                    <p class="product-description">

                                        <?= htmlspecialchars(
                                            $product['description']
                                        ) ?>

                                    </p>



                                    <!-- Price + Stock -->

                                    <div class="product-meta">


                                        <span class="product-price">

                                            $<?= number_format(
                                                (float) $product['price'],
                                                2
                                            ) ?>

                                        </span>


                                        <span class="product-stock">

                                            Stock:
                                            <?= (int) $product['stock'] ?>

                                        </span>


                                    </div>



                                    <!-- ==========================
                                         ACTION BUTTONS
                                         ========================== -->

                                    <div class="product-actions">


                                        <!-- View Details -->

                                        <a
                                            href="product.php?id=<?= (int) $product['id'] ?>"
                                            class="product-action view-details"
                                        >

                                            View Details

                                        </a>



                                        <!-- Add To Cart -->

                                        <?php if (
                                            (int) $product['stock'] > 0
                                        ): ?>


                                            <?php if (
                                                isset($_SESSION['user_id'])
                                            ): ?>


                                                <form
                                                    method="POST"
                                                    action="cart.php"
                                                    style="margin:0;"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="product_id"
                                                        value="<?= (int) $product['id'] ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="quantity"
                                                        value="1"
                                                    >


                                                    <button
                                                        type="submit"
                                                        name="add_to_cart"
                                                        class="product-action add-cart"
                                                    >

                                                        Add to Cart

                                                    </button>

                                                </form>


                                            <?php else: ?>


                                                <a
                                                    href="user/login.php?redirect=<?= urlencode(
                                                        'product.php?id=' .
                                                        $product['id']
                                                    ) ?>"
                                                    class="product-action add-cart"
                                                >

                                                    Add to Cart

                                                </a>


                                            <?php endif; ?>


                                        <?php else: ?>


                                            <button
                                                type="button"
                                                class="product-action add-cart"
                                                disabled
                                            >

                                                Out of Stock

                                            </button>


                                        <?php endif; ?>


                                    </div>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


            </div>

        </section>


    <?php endif; ?>


</main>



<!-- ==========================================
     FOOTER
     ========================================== -->

<footer id="main-footer">


    <div class="container">


        <div class="footer-grid">


            <div
                class="footer-col"
                id="footer-col-about"
            >

                <h3>
                    Advaya Store
                </h3>

                <p>

                    Advaya is a premium watch store
                    built with pure PHP, MySQL and
                    modern web technologies.

                </p>

            </div>



            <div
                class="footer-col"
                id="footer-col-tech"
            >

                <h3>
                    Tech Stack
                </h3>

                <p>

                    <strong>
                        Backend:
                    </strong>

                    PHP 8+, PDO MySQL

                    <br>

                    <strong>
                        Frontend:
                    </strong>

                    HTML5, CSS3, JavaScript

                    <br>

                    <strong>
                        Security:
                    </strong>

                    Prepared Statements,
                    Password Hashing

                </p>

            </div>



            <div
                class="footer-col"
                id="footer-col-quicklinks"
            >

                <h3>
                    Quick Links
                </h3>

                <ul>

                    <li>

                        <a href="#catalog">
                            Catalog
                        </a>

                    </li>


                    <?php if (
                        isset($_SESSION['user_id'])
                    ): ?>

                        <li>

                            <a href="cart.php">
                                Shopping Cart
                            </a>

                        </li>


                        <li>

                            <a href="user/profile.php">
                                My Account
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
                                Create Account
                            </a>

                        </li>


                    <?php endif; ?>


                </ul>

            </div>


        </div>



        <div class="footer-bottom">


            <p>

                &copy;
                <?= date('Y') ?>
                Advaya Watch Store.
                All rights reserved.

            </p>


            <div class="social-links">

                <a href="#">
                    Instagram
                </a>

                <a href="#">
                    Twitter
                </a>

            </div>


        </div>


    </div>

</footer>



<script src="assets/js/main.js"></script>


</body>

</html>