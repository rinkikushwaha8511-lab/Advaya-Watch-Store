<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$profile = null;
$error = '';

try {

    $pdo = getDBConnection();

    $stmt = $pdo->prepare(
        'SELECT id, name, email, phone, address, role, created_at
         FROM users
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute([
        ':id' => $_SESSION['user_id']
    ]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        $error = 'User account could not be found.';
    }

} catch (PDOException $e) {

    $error = 'Unable to load your profile.';
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

    <title>My Account - Advaya</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .profile-container {
            max-width: 700px;
            margin: 70px auto;
            padding: 20px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .profile-header h1 {
            margin-bottom: 8px;
        }

        .profile-header p {
            opacity: 0.7;
        }

        .profile-card {
            padding: 30px;
            border-radius: 12px;
        }

        .profile-row {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            padding: 18px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .profile-row:last-child {
            border-bottom: none;
        }

        .profile-label {
            font-weight: 600;
            min-width: 120px;
        }

        .profile-value {
            text-align: right;
            opacity: 0.85;
            word-break: break-word;
        }

        .profile-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .profile-button {
            display: inline-block;
            padding: 12px 22px;
            text-decoration: none;
            border-radius: 6px;
        }

        .error-box {
            padding: 15px;
            text-align: center;
            border-radius: 8px;
        }

        @media (max-width: 600px) {

            .profile-row {
                flex-direction: column;
                gap: 6px;
            }

            .profile-value {
                text-align: left;
            }

            .profile-actions {
                flex-direction: column;
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
            id="header-logo"
        >
            Advaya <span>Watch Store</span>
        </a>

        <ul
            class="nav-links"
            id="header-navigation"
        >

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
                <a href="profile.php" class="active">
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

    <div class="profile-container">

        <div class="profile-header">

            <h1>
                My Account
            </h1>

            <p>
                Manage and view your Advaya account details
            </p>

        </div>


        <?php if ($error): ?>

            <div class="error-box">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php elseif ($profile): ?>

            <div class="profile-card">

                <div class="profile-row">

                    <span class="profile-label">
                        Name
                    </span>

                    <span class="profile-value">
                        <?= htmlspecialchars($profile['name']) ?>
                    </span>

                </div>


                <div class="profile-row">

                    <span class="profile-label">
                        Email
                    </span>

                    <span class="profile-value">
                        <?= htmlspecialchars($profile['email']) ?>
                    </span>

                </div>


                <div class="profile-row">

                    <span class="profile-label">
                        Phone
                    </span>

                    <span class="profile-value">
                        <?= !empty($profile['phone'])
                            ? htmlspecialchars($profile['phone'])
                            : 'Not provided'
                        ?>
                    </span>

                </div>


                <div class="profile-row">

                    <span class="profile-label">
                        Address
                    </span>

                    <span class="profile-value">
                        <?= !empty($profile['address'])
                            ? nl2br(htmlspecialchars($profile['address']))
                            : 'Not provided'
                        ?>
                    </span>

                </div>


                <div class="profile-row">

                    <span class="profile-label">
                        Account Type
                    </span>

                    <span class="profile-value">
                        <?= htmlspecialchars(
                            ucfirst($profile['role'])
                        ) ?>
                    </span>

                </div>


                <div class="profile-row">

                    <span class="profile-label">
                        Member Since
                    </span>

                    <span class="profile-value">
                        <?= htmlspecialchars(
                            date(
                                'd M Y',
                                strtotime($profile['created_at'])
                            )
                        ) ?>
                    </span>

                </div>


                <div class="profile-actions">

                    <a
                        href="../index.php"
                        class="profile-button"
                    >
                        Continue Shopping
                    </a>

                    <a
                        href="../logout.php"
                        class="profile-button"
                    >
                        Logout
                    </a>

                </div>

            </div>

        <?php endif; ?>

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