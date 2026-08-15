<?php

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$success = '';

$name = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {

        try {

            $pdo = getDBConnection();

            // Check whether email already exists
            $check = $pdo->prepare(
                'SELECT id FROM users WHERE email = :email LIMIT 1'
            );

            $check->execute([
                ':email' => $email
            ]);

            if ($check->fetch()) {

                $errors[] = 'This email is already registered. Please use another email.';

            } else {

                // Secure password hash
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert user
                $stmt = $pdo->prepare(
                    'INSERT INTO users
                    (name, email, password, phone, address, role)
                    VALUES
                    (:name, :email, :password, :phone, :address, :role)'
                );

                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':phone' => $phone !== '' ? $phone : null,
                    ':address' => $address !== '' ? $address : null,
                    ':role' => 'customer'
                ]);

                $success = 'Registration successful! You can now login.';

                // Clear form
                $name = '';
                $email = '';
                $phone = '';
                $address = '';
            }

        } catch (PDOException $e) {

            $errors[] = 'Database error: ' . $e->getMessage();

        }
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

    <title>Create Account - Advaya</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .register-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 30px;
        }

        .register-container h1 {
            text-align: center;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            margin-bottom: 30px;
            opacity: 0.7;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .register-btn {
            width: 100%;
            padding: 13px;
            cursor: pointer;
        }

        .error-box {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .success-box {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

    </style>

</head>

<body>

<div class="register-container">

    <h1>Create Account</h1>

    <p class="subtitle">
        Create your Advaya account
    </p>


    <?php if (!empty($errors)): ?>

        <div class="error-box">

            <?php foreach ($errors as $error): ?>

                <div>
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <?php if ($success): ?>

        <div class="success-box">

            <?= htmlspecialchars($success) ?>

            <br><br>

            <a href="login.php">
                Go to Login
            </a>

        </div>

    <?php endif; ?>


    <form method="POST" action="">

        <div class="form-group">

            <label for="name">
                Full Name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars($name) ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="email">
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email) ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="phone">
                Phone
            </label>

            <input
                type="text"
                id="phone"
                name="phone"
                value="<?= htmlspecialchars($phone) ?>"
            >

        </div>


        <div class="form-group">

            <label for="address">
                Address
            </label>

            <textarea
                id="address"
                name="address"
            ><?= htmlspecialchars($address) ?></textarea>

        </div>


        <div class="form-group">

            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >

        </div>


        <div class="form-group">

            <label for="confirm_password">
                Confirm Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
            >

        </div>


        <button
            type="submit"
            class="register-btn"
        >
            Create Account
        </button>

    </form>


    <div class="login-link">

        Already have an account?

        <a href="login.php">
            Login
        </a>

    </div>

</div>

</body>

</html>