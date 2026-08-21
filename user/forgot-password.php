<?php
// Forgot Password page placeholder
// Connect to a mailer/reset system when ready
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }

$sent = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // TODO: implement actual token-based reset email
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Advaya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #07080b;
            --primary: #c5a880;
            --primary-light: #dfcbb3;
            --primary-dark: #8c7250;
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --border: rgba(197, 168, 128, 0.15);
            --border-focus: rgba(197, 168, 128, 0.5);
            --error: #ef4444;
            --error-bg: rgba(239, 68, 68, 0.08);
            --error-border: rgba(239, 68, 68, 0.3);
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.08);
            --success-border: rgba(16, 185, 129, 0.3);
            --font-heading: 'Playfair Display', serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg-main);
            color: var(--text-primary);
            font-family: var(--font-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-image: radial-gradient(ellipse at 50% 0%, rgba(197,168,128,0.06) 0%, transparent 60%);
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            backdrop-filter: blur(12px);
            animation: slideUp 0.5s ease both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--primary); }
        .icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(197,168,128,0.1);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        h1 {
            font-family: var(--font-heading);
            font-size: 1.9rem;
            font-weight: 400;
            margin-bottom: 0.5rem;
        }
        p.subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 1.8rem;
            line-height: 1.6;
        }
        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        input[type="email"] {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 16px;
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
        }
        input[type="email"]:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(197,168,128,0.08);
        }
        .form-group { margin-bottom: 1.4rem; }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #0a0b0d;
            border: none;
            border-radius: 10px;
            font-family: var(--font-body);
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(197,168,128,0.35);
        }
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-error   { background: var(--error-bg); border: 1px solid var(--error-border); color: var(--error); }
        .alert-success { background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success); }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <a href="login.php" class="back-link">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
        Back to Login
    </a>

    <div class="icon-circle">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="rgba(197,168,128,0.8)" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
    </div>

    <h1>Forgot Password?</h1>
    <p class="subtitle">
        Enter your registered email address and we'll send you a link to reset your password.
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($errors[0]) ?>
        </div>
    <?php endif; ?>

    <?php if ($sent): ?>
        <div class="alert alert-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            If an account exists with that email, a reset link has been sent. Please check your inbox.
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email">
            </div>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
    <?php endif; ?>

    <div class="login-link">
        Remembered your password? <a href="login.php">Sign In</a>
    </div>
</div>
</body>
</html>
