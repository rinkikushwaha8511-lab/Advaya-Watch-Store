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
           HERO SECTION OVERRIDES
           ========================================== */
        .hero {
            padding: 5rem 0 2rem;
            text-align: center;
            position: relative;
        }

        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            margin-bottom: 1rem;
        }

        .hero p {
            max-width: 600px;
            margin: 0 auto 2rem;
            line-height: 1.8;
        }

        /* ==========================================
           3D WATCH HERO DISPLAY
           ========================================== */
        .hero-watches {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 3rem;
            margin: 1.5rem auto 3.5rem;
            max-width: 680px;
        }

        .hero-watch-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.7rem;
            text-decoration: none;
            cursor: pointer;
        }

        .watch-3d-hero {
            animation: heroFloat 4s ease-in-out infinite;
            transition: filter 0.4s;
            filter: drop-shadow(0 15px 35px rgba(197,168,128,0.18));
        }

        .hero-watch-item:nth-child(1) .watch-3d-hero { animation-delay: 0s; }
        .hero-watch-item:nth-child(2) .watch-3d-hero { animation-delay: -1.3s; }
        .hero-watch-item:nth-child(3) .watch-3d-hero { animation-delay: -2.6s; }

        @keyframes heroFloat {
            0%, 100% { transform: translateY(0) rotateY(-8deg) rotateX(3deg); }
            50%       { transform: translateY(-14px) rotateY(8deg) rotateX(-3deg); }
        }

        .hero-watch-item.featured .watch-3d-hero {
            filter: drop-shadow(0 25px 55px rgba(197,168,128,0.38));
            transform-origin: center bottom;
        }

        .hero-watch-item:hover .watch-3d-hero {
            filter: drop-shadow(0 30px 65px rgba(197,168,128,0.5));
            animation-play-state: paused;
        }

        .hero-watch-item.side .watch-3d-hero {
            transform: scale(0.78);
        }

        .hero-watch-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
            transition: color 0.3s;
        }
        .hero-watch-item:hover .hero-watch-label { color: var(--primary); }

        .hero-watch-price {
            font-family: var(--font-heading);
            font-size: 0.9rem;
            color: var(--primary-light);
        }

        /* ==========================================
           NEW ARRIVALS STRIP
           ========================================== */
        .new-arrivals-strip {
            background: linear-gradient(90deg, transparent 0%, rgba(197,168,128,0.05) 20%, rgba(197,168,128,0.05) 80%, transparent 100%);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 0.7rem 0;
            overflow: hidden;
        }

        .marquee-track {
            display: flex;
            gap: 2.5rem;
            white-space: nowrap;
            animation: marqueeScroll 30s linear infinite;
            width: max-content;
        }

        .new-arrivals-strip:hover .marquee-track {
            animation-play-state: paused;
        }

        .marquee-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
        }

        .marquee-item .dot {
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: var(--primary);
        }

        .marquee-item .new-tag {
            background: var(--primary);
            color: #0a0b0d;
            font-size: 0.58rem;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        @keyframes marqueeScroll {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* ==========================================
           PRODUCTS
           ========================================== */
        .products-section { padding: 5rem 0 6rem; }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-label {
            display: block;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.6rem;
        }

        .section-title {
            font-family: var(--font-heading);
            font-size: 2.2rem;
            font-weight: 400;
            letter-spacing: 1px;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 45px;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), transparent);
            margin: 0.85rem auto 0;
        }

        /* ==========================================
           RESPONSIVE
           ========================================== */
        @media (max-width: 700px) {
            .hero-watches { gap: 1rem; }
            .hero-watch-item.side { display: none; }
            .product-actions { grid-template-columns: 1fr; }
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

    <section class="hero" id="hero-section">
        <div class="container">

            <?php if ($db_connected): ?>
                <div class="status-banner success" id="connection-badge">
                    <span class="status-indicator"></span>
                    Live — Connected to Advaya Store
                </div>
            <?php else: ?>
                <div class="status-banner error" id="connection-badge">
                    <span class="status-indicator"></span>
                    Database Connection Failed
                </div>
                <div style="max-width:600px;margin:20px auto;padding:20px;text-align:left;">
                    <strong>Connection Error:</strong>
                    <p><?= htmlspecialchars($db_error) ?></p>
                </div>
            <?php endif; ?>

            <h1 id="hero-title">
                A Legacy of <span>Precision</span> &amp; Style
            </h1>

            <p id="hero-subtitle">
                Discover timeless watches crafted for precision,
                elegance and individuality. Find the perfect timepiece
                for your style.
            </p>

            <!-- =============================================
                 3D ANIMATED WATCH HERO DISPLAY
                 ============================================= -->
            <div class="hero-watches" id="hero-watch-display">

                <!-- Watch 1 — side -->
                <div class="hero-watch-item side">
                    <div class="watch-3d-hero">
                        <svg width="100" height="130" viewBox="0 0 160 200" xmlns="http://www.w3.org/2000/svg">
                            <rect x="55" y="0" width="50" height="44" rx="6" fill="#16181f" stroke="rgba(197,168,128,0.2)" stroke-width="1"/>
                            <rect x="22" y="40" width="116" height="120" rx="30" fill="url(#caseB)" stroke="url(#caseStB)" stroke-width="1.5"/>
                            <circle cx="80" cy="100" r="46" fill="url(#dialB)"/>
                            <g stroke="rgba(197,168,128,0.6)" stroke-width="1.8" stroke-linecap="round">
                                <line x1="80" y1="58" x2="80" y2="65"/>
                                <line x1="80" y1="135" x2="80" y2="142"/>
                                <line x1="38" y1="100" x2="45" y2="100"/>
                                <line x1="115" y1="100" x2="122" y2="100"/>
                            </g>
                            <line class="hw-hour" x1="80" y1="100" x2="80" y2="78" stroke="#c5a880" stroke-width="2.5" stroke-linecap="round"/>
                            <line class="hw-min"  x1="80" y1="100" x2="80" y2="67" stroke="#e5e7eb" stroke-width="1.8" stroke-linecap="round"/>
                            <line class="hw-sec"  x1="80" y1="108" x2="80" y2="63" stroke="#ef4444" stroke-width="1" stroke-linecap="round"/>
                            <circle cx="80" cy="100" r="3.5" fill="#c5a880"/>
                            <text x="80" y="120" text-anchor="middle" font-family="serif" font-size="6.5" fill="rgba(197,168,128,0.6)" letter-spacing="2">ADVAYA</text>
                            <rect x="55" y="160" width="50" height="40" rx="6" fill="#16181f" stroke="rgba(197,168,128,0.2)" stroke-width="1"/>
                            <defs>
                                <linearGradient id="caseB" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#2a2d3e"/><stop offset="100%" stop-color="#0f1015"/>
                                </linearGradient>
                                <linearGradient id="caseStB" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="rgba(197,168,128,0.5)"/><stop offset="100%" stop-color="rgba(197,168,128,0.15)"/>
                                </linearGradient>
                                <radialGradient id="dialB" cx="40%" cy="35%">
                                    <stop offset="0%" stop-color="#252838"/><stop offset="100%" stop-color="#0b0d14"/>
                                </radialGradient>
                            </defs>
                        </svg>
                    </div>
                    <span class="hero-watch-label">Classic</span>
                    <span class="hero-watch-price">$299</span>
                </div>

                <!-- Watch 2 — featured center -->
                <div class="hero-watch-item featured">
                    <div class="watch-3d-hero">
                        <svg id="main-watch-svg" width="140" height="175" viewBox="0 0 160 200" xmlns="http://www.w3.org/2000/svg">
                            <rect x="55" y="0" width="50" height="48" rx="6" fill="#1a1c22" stroke="rgba(197,168,128,0.3)" stroke-width="1"/>
                            <g fill="none" stroke="rgba(197,168,128,0.08)">
                                <line x1="63" y1="8"  x2="97" y2="8"/>
                                <line x1="63" y1="16" x2="97" y2="16"/>
                                <line x1="63" y1="24" x2="97" y2="24"/>
                                <line x1="63" y1="32" x2="97" y2="32"/>
                                <line x1="63" y1="40" x2="97" y2="40"/>
                            </g>
                            <rect x="22" y="44" width="116" height="112" rx="30" fill="url(#caseM)" stroke="url(#caseStM)" stroke-width="2"/>
                            <rect x="152" y="80" width="8" height="10" rx="3" fill="#2a2c35" stroke="rgba(197,168,128,0.4)" stroke-width="1"/>
                            <circle cx="80" cy="100" r="46" fill="url(#dialM)"/>
                            <!-- Outer bezel tick marks -->
                            <g stroke="rgba(197,168,128,0.8)" stroke-width="2" stroke-linecap="round">
                                <line x1="80" y1="57" x2="80" y2="65"/>
                                <line x1="80" y1="135" x2="80" y2="143"/>
                                <line x1="37" y1="100" x2="45" y2="100"/>
                                <line x1="115" y1="100" x2="123" y2="100"/>
                            </g>
                            <g stroke="rgba(197,168,128,0.25)" stroke-width="1" stroke-linecap="round">
                                <line x1="103.6" y1="62.2" x2="100.7" y2="67.2"/>
                                <line x1="117.8" y1="76.4" x2="112.8" y2="79.3"/>
                                <line x1="117.8" y1="123.6" x2="112.8" y2="120.7"/>
                                <line x1="103.6" y1="137.8" x2="100.7" y2="132.8"/>
                                <line x1="56.4" y1="137.8" x2="59.3" y2="132.8"/>
                                <line x1="42.2" y1="123.6" x2="47.2" y2="120.7"/>
                                <line x1="42.2" y1="76.4" x2="47.2" y2="79.3"/>
                                <line x1="56.4" y1="62.2" x2="59.3" y2="67.2"/>
                            </g>
                            <!-- Hands -->
                            <line id="hero-hour" x1="80" y1="100" x2="80" y2="76" stroke="#c5a880" stroke-width="3" stroke-linecap="round"/>
                            <line id="hero-min"  x1="80" y1="100" x2="80" y2="65" stroke="#e5e7eb" stroke-width="2" stroke-linecap="round"/>
                            <line id="hero-sec"  x1="80" y1="108" x2="80" y2="61" stroke="#ef4444" stroke-width="1.2" stroke-linecap="round"/>
                            <circle cx="80" cy="100" r="4" fill="#c5a880"/>
                            <circle cx="80" cy="100" r="2" fill="#07080b"/>
                            <text x="80" y="118" text-anchor="middle" font-family="serif" font-size="7" fill="rgba(197,168,128,0.7)" letter-spacing="2">ADVAYA</text>
                            <text x="80" y="126" text-anchor="middle" font-family="sans-serif" font-size="4.5" fill="rgba(197,168,128,0.35)" letter-spacing="1">SWISS MADE</text>
                            <rect x="55" y="156" width="50" height="44" rx="6" fill="#1a1c22" stroke="rgba(197,168,128,0.3)" stroke-width="1"/>
                            <g fill="none" stroke="rgba(197,168,128,0.08)">
                                <line x1="63" y1="162" x2="97" y2="162"/>
                                <line x1="63" y1="170" x2="97" y2="170"/>
                                <line x1="63" y1="178" x2="97" y2="178"/>
                                <line x1="63" y1="186" x2="97" y2="186"/>
                                <line x1="63" y1="194" x2="97" y2="194"/>
                            </g>
                            <defs>
                                <linearGradient id="caseM" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#2e3040"/><stop offset="50%" stop-color="#1a1d28"/><stop offset="100%" stop-color="#0f1118"/>
                                </linearGradient>
                                <linearGradient id="caseStM" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="rgba(197,168,128,0.7)"/><stop offset="50%" stop-color="rgba(197,168,128,0.25)"/><stop offset="100%" stop-color="rgba(197,168,128,0.5)"/>
                                </linearGradient>
                                <radialGradient id="dialM" cx="40%" cy="35%">
                                    <stop offset="0%" stop-color="#1e2130"/><stop offset="100%" stop-color="#0d0f1a"/>
                                </radialGradient>
                            </defs>
                        </svg>
                    </div>
                    <span class="hero-watch-label">Signature</span>
                    <span class="hero-watch-price">$599</span>
                </div>

                <!-- Watch 3 — side -->
                <div class="hero-watch-item side">
                    <div class="watch-3d-hero">
                        <svg width="100" height="130" viewBox="0 0 160 200" xmlns="http://www.w3.org/2000/svg">
                            <rect x="55" y="0" width="50" height="44" rx="6" fill="#16181f" stroke="rgba(197,168,128,0.2)" stroke-width="1"/>
                            <rect x="22" y="40" width="116" height="120" rx="30" fill="url(#caseC)" stroke="url(#caseStC)" stroke-width="1.5"/>
                            <circle cx="80" cy="100" r="46" fill="url(#dialC)"/>
                            <g stroke="rgba(197,168,128,0.6)" stroke-width="1.8" stroke-linecap="round">
                                <line x1="80" y1="58" x2="80" y2="65"/>
                                <line x1="80" y1="135" x2="80" y2="142"/>
                                <line x1="38" y1="100" x2="45" y2="100"/>
                                <line x1="115" y1="100" x2="122" y2="100"/>
                            </g>
                            <line class="hw-hour" x1="80" y1="100" x2="80" y2="78" stroke="#c5a880" stroke-width="2.5" stroke-linecap="round"/>
                            <line class="hw-min"  x1="80" y1="100" x2="80" y2="67" stroke="#e5e7eb" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="80" cy="100" r="3.5" fill="#c5a880"/>
                            <text x="80" y="120" text-anchor="middle" font-family="serif" font-size="6.5" fill="rgba(197,168,128,0.6)" letter-spacing="2">ADVAYA</text>
                            <rect x="55" y="160" width="50" height="40" rx="6" fill="#16181f" stroke="rgba(197,168,128,0.2)" stroke-width="1"/>
                            <defs>
                                <linearGradient id="caseC" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#1e2235"/><stop offset="100%" stop-color="#080a12"/>
                                </linearGradient>
                                <linearGradient id="caseStC" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="rgba(197,168,128,0.45)"/><stop offset="100%" stop-color="rgba(197,168,128,0.12)"/>
                                </linearGradient>
                                <radialGradient id="dialC" cx="40%" cy="35%">
                                    <stop offset="0%" stop-color="#1a1f2e"/><stop offset="100%" stop-color="#08090f"/>
                                </radialGradient>
                            </defs>
                        </svg>
                    </div>
                    <span class="hero-watch-label">Elite</span>
                    <span class="hero-watch-price">$899</span>
                </div>

            </div><!-- /.hero-watches -->

        </div>
    </section>


    <!-- ==========================================
         NEW ARRIVALS MARQUEE STRIP
         ========================================== -->
    <div class="new-arrivals-strip" id="new-arrivals-strip" aria-label="New arrivals">
        <div class="marquee-track">
            <?php
            // Generate marquee items — use DB products if available, else fallback
            $marqueeItems = !empty($products)
                ? array_slice($products, 0, 8)
                : [['brand'=>'Advaya','name'=>'Signature Series'],['brand'=>'Elite','name'=>'Chronograph Pro'],['brand'=>'Classic','name'=>'Heritage GMT']];
            // Duplicate for seamless loop
            $allItems = array_merge($marqueeItems, $marqueeItems);
            foreach ($allItems as $mi): ?>
                <span class="marquee-item">
                    <span class="dot"></span>
                    <span class="new-tag">New</span>
                    <?= htmlspecialchars($mi['brand']) ?> — <?= htmlspecialchars($mi['name']) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>



    <!-- ======================================
         PRODUCT CATALOG
         ====================================== -->

    <?php if ($db_connected): ?>


        <section class="products-section" id="catalog">

            <div class="container">

                <div class="section-header">
                    <span class="section-label" id="catalog-label">Our Collection</span>
                    <h2 class="section-title" id="catalog-title">Explore the Timepieces</h2>
                </div>


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

<script>
    // ============================================
    // LIVE WATCH CLOCK — Updates every second
    // ============================================
    function updateHeroClocks() {
        const now  = new Date();
        const s    = now.getSeconds();
        const m    = now.getMinutes() + s / 60;
        const h    = (now.getHours() % 12) + m / 60;

        const secDeg  = s * 6;
        const minDeg  = m * 6;
        const hourDeg = h * 30;

        // Center featured watch
        const heroSec  = document.getElementById('hero-sec');
        const heroMin  = document.getElementById('hero-min');
        const heroHour = document.getElementById('hero-hour');
        if (heroSec)  heroSec.setAttribute('transform',  `rotate(${secDeg},  80, 100)`);
        if (heroMin)  heroMin.setAttribute('transform',  `rotate(${minDeg},  80, 100)`);
        if (heroHour) heroHour.setAttribute('transform', `rotate(${hourDeg}, 80, 100)`);

        // Side watches (hour + min only)
        document.querySelectorAll('.hw-hour').forEach(el =>
            el.setAttribute('transform', `rotate(${hourDeg}, 80, 100)`)
        );
        document.querySelectorAll('.hw-min').forEach(el =>
            el.setAttribute('transform', `rotate(${minDeg}, 80, 100)`)
        );
        document.querySelectorAll('.hw-sec').forEach(el =>
            el.setAttribute('transform', `rotate(${secDeg}, 80, 100)`)
        );
    }
    updateHeroClocks();
    setInterval(updateHeroClocks, 1000);

    // ============================================
    // SCROLL REVEAL for product cards
    // ============================================
    if ('IntersectionObserver' in window) {
        const cards = document.querySelectorAll('.product-card');
        const obs = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = (i * 0.06) + 's';
                    entry.target.classList.add('animate-fade-up');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        cards.forEach(c => obs.observe(c));
    }
</script>


</body>

</html>