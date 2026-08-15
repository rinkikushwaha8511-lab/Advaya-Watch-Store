<?php

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {

        try {

            $pdo = getDBConnection();

            // Find user by email
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password, role
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $stmt->execute([
                ':email' => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password
            if ($user && password_verify($password, $user['password'])) {

                // Create a new session ID after login
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: ../index.php');
                }

                exit;

            } else {

                $errors[] = 'Invalid email or password.';

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

    <title>Login - Advaya</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .auth-container {
            max-width: 450px;
            margin: 80px auto;
            padding: 35px;
        }

        .auth-container h1 {
            text-align: center;
            margin-bottom: 10px;
        }

        .auth-subtitle {
            text-align: center;
            margin-bottom: 30px;
            opacity: 0.75;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
        }

        .auth-button {
            width: 100%;
            padding: 13px;
            cursor: pointer;
        }

        .error-box {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 6px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
        }

    </style>

</head>

<body>

<div class="auth-container">

    <h1>Welcome Back</h1>

    <p class="auth-subtitle">
        Login to your Advaya account
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

    <form method="POST" action="">

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

        <button
            type="submit"
            class="auth-button"
        >
            Login
        </button>

    </form>

    <div class="auth-footer">

        Don't have an account?

        <a href="register.php">
            Create Account
        </a>

    </div>

</div>

</body>

</html>