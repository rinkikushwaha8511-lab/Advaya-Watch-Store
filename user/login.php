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
$passwordError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if ($email === '') {
        $errors[] = ['field' => 'email', 'msg' => 'Email address is required.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = ['field' => 'email', 'msg' => 'Please enter a valid email address.'];
    }

    if ($password === '') {
        $errors[] = ['field' => 'password', 'msg' => 'Password is required.'];
    }

    if (empty($errors)) {

        try {

            $pdo = getDBConnection();

            $stmt = $pdo->prepare(
                'SELECT id, name, email, password, role
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: ../index.php');
                }
                exit;

            } else {
                $passwordError = true;
                $errors[] = ['field' => 'password', 'msg' => 'Invalid email or password. Please try again.'];
            }

        } catch (PDOException $e) {
            $errors[] = ['field' => 'general', 'msg' => 'A server error occurred. Please try again.'];
        }
    }
}

// Helper to check if a field has an error
function fieldError(array $errors, string $field): string {
    foreach ($errors as $e) {
        if ($e['field'] === $field) return $e['msg'];
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Advaya Watch Store</title>
    <meta name="description" content="Sign in to your Advaya account to explore premium watches.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ============================
           CSS VARIABLES
           ============================ */
        :root {
            --bg-main:       #07080b;
            --bg-card:       #111318;
            --primary:       #c5a880;
            --primary-light: #dfcbb3;
            --primary-dark:  #8c7250;
            --primary-glow:  rgba(197, 168, 128, 0.25);
            --text-primary:  #f3f4f6;
            --text-secondary:#9ca3af;
            --text-muted:    #6b7280;
            --border:        rgba(197, 168, 128, 0.15);
            --border-focus:  rgba(197, 168, 128, 0.5);
            --error:         #ef4444;
            --error-bg:      rgba(239, 68, 68, 0.08);
            --error-border:  rgba(239, 68, 68, 0.4);
            --success:       #10b981;
            --font-heading:  'Playfair Display', serif;
            --font-body:     'Plus Jakarta Sans', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            font-family: var(--font-body);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ============================
           LAYOUT — SPLIT SCREEN
           ============================ */
        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* LEFT PANEL — brand visual */
        .login-brand {
            position: relative;
            background: radial-gradient(ellipse at 30% 50%, rgba(197,168,128,0.12) 0%, transparent 65%),
                        linear-gradient(145deg, #0d0e12 0%, #07080b 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            overflow: hidden;
            border-right: 1px solid var(--border);
        }

        /* Decorative rings */
        .brand-rings {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(197, 168, 128, 0.08);
            animation: ringPulse 8s ease-in-out infinite;
        }
        .ring:nth-child(1) { width: 260px; height: 260px; animation-delay: 0s; }
        .ring:nth-child(2) { width: 380px; height: 380px; animation-delay: 1s; border-color: rgba(197,168,128,0.05); }
        .ring:nth-child(3) { width: 500px; height: 500px; animation-delay: 2s; border-color: rgba(197,168,128,0.03); }
        .ring:nth-child(4) { width: 620px; height: 620px; animation-delay: 3s; border-color: rgba(197,168,128,0.02); }

        @keyframes ringPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50%       { transform: scale(1.04); opacity: 0.6; }
        }

        /* 3D Watch on brand panel */
        .watch-3d-container {
            position: relative;
            z-index: 1;
            perspective: 800px;
            margin-bottom: 2.5rem;
        }

        .watch-3d {
            width: 160px;
            height: 200px;
            position: relative;
            transform-style: preserve-3d;
            animation: watchFloat 5s ease-in-out infinite, watchRotate 12s ease-in-out infinite;
        }

        @keyframes watchFloat {
            0%, 100% { transform: translateY(0px) rotateY(-15deg) rotateX(5deg); }
            50%       { transform: translateY(-18px) rotateY(15deg) rotateX(-5deg); }
        }
        @keyframes watchRotate {
            0%   { filter: drop-shadow(0 20px 40px rgba(197,168,128,0.3)); }
            50%  { filter: drop-shadow(0 30px 60px rgba(197,168,128,0.5)); }
            100% { filter: drop-shadow(0 20px 40px rgba(197,168,128,0.3)); }
        }

        /* SVG Watch face */
        .watch-svg { width: 100%; height: 100%; }

        .brand-logo {
            font-family: var(--font-heading);
            font-size: 2.4rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 4px;
            text-transform: uppercase;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .brand-tagline {
            font-size: 0.8rem;
            color: var(--text-muted);
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-top: 0.5rem;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .brand-quote {
            margin-top: 2.5rem;
            max-width: 280px;
            text-align: center;
            font-family: var(--font-heading);
            font-style: italic;
            font-size: 1.05rem;
            color: var(--text-secondary);
            line-height: 1.7;
            position: relative;
            z-index: 1;
        }
        .brand-quote::before {
            content: '"';
            font-size: 3rem;
            color: var(--primary);
            opacity: 0.4;
            display: block;
            line-height: 0.8;
            margin-bottom: 0.5rem;
        }

        /* RIGHT PANEL — form */
        .login-form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: var(--bg-main);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ============================
           HEADER
           ============================ */
        .form-header {
            margin-bottom: 2rem;
        }

        .form-header .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.8rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            transition: color 0.2s;
        }
        .form-header .back-link:hover { color: var(--primary); }
        .form-header .back-link svg { transition: transform 0.2s; }
        .form-header .back-link:hover svg { transform: translateX(-3px); }

        .form-header h1 {
            font-family: var(--font-heading);
            font-size: 2.2rem;
            font-weight: 400;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }

        .form-header p {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        /* ============================
           GENERAL ERROR
           ============================ */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 10px;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97);
        }

        @keyframes shake {
            10%, 90% { transform: translateX(-2px); }
            20%, 80% { transform: translateX(4px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }

        /* ============================
           FORM GROUPS
           ============================ */
        .form-group {
            margin-bottom: 1.4rem;
        }

        .form-group label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 16px;
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 0.95rem;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
            outline: none;
            -webkit-appearance: none;
        }
        .form-control:focus {
            border-color: var(--border-focus);
            background: rgba(255,255,255,0.05);
            box-shadow: 0 0 0 3px rgba(197,168,128,0.08);
        }
        .form-control.is-error {
            border-color: var(--error-border);
            background: var(--error-bg);
        }
        .form-control.is-error:focus {
            box-shadow: 0 0 0 3px rgba(239,68,68,0.1);
        }

        /* password field with toggle */
        .password-input-wrapper {
            position: relative;
        }
        .password-input-wrapper .form-control {
            padding-right: 48px;
        }
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }
        .toggle-password:hover { color: var(--primary); }

        .field-error {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            color: var(--error);
            margin-top: 6px;
        }

        /* Password hint box */
        .password-hint {
            margin-top: 8px;
            padding: 10px 14px;
            border-radius: 8px;
            background: rgba(197, 168, 128, 0.06);
            border: 1px solid rgba(197, 168, 128, 0.15);
            font-size: 0.78rem;
            color: var(--text-secondary);
            line-height: 1.6;
            animation: fadeIn 0.3s ease;
        }
        .password-hint strong {
            display: block;
            color: var(--primary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .password-hint ul {
            padding-left: 14px;
        }
        .password-hint li { margin-bottom: 2px; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Forgot password link */
        .forgot-link {
            font-size: 0.78rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s, text-decoration 0.2s;
        }
        .forgot-link:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        /* ============================
           SUBMIT BUTTON
           ============================ */
        .btn-login {
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(197, 168, 128, 0.35);
        }
        .btn-login:hover::before { opacity: 1; }
        .btn-login:active { transform: translateY(0); }

        /* ============================
           DIVIDER
           ============================ */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 1.5rem 0;
            color: var(--text-muted);
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ============================
           FOOTER LINKS
           ============================ */
        .form-footer {
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .form-footer a:hover { color: var(--primary-light); }

        /* ============================
           RESPONSIVE
           ============================ */
        @media (max-width: 900px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }
            .login-brand {
                display: none;
            }
            .login-form-panel {
                padding: 2rem 1.5rem;
                align-items: flex-start;
                padding-top: 3rem;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">

    <!-- =====================
         LEFT — BRAND PANEL
         ===================== -->
    <aside class="login-brand" aria-hidden="true">
        <div class="brand-rings">
            <div class="ring"></div>
            <div class="ring"></div>
            <div class="ring"></div>
            <div class="ring"></div>
        </div>

        <!-- 3D Animated Watch SVG -->
        <div class="watch-3d-container">
            <div class="watch-3d">
                <svg class="watch-svg" viewBox="0 0 160 200" xmlns="http://www.w3.org/2000/svg">
                    <!-- Watch strap top -->
                    <rect x="55" y="0" width="50" height="48" rx="6" fill="#1a1c22" stroke="rgba(197,168,128,0.3)" stroke-width="1"/>
                    <rect x="63" y="6" width="34" height="4" rx="2" fill="rgba(197,168,128,0.15)"/>
                    <rect x="63" y="14" width="34" height="4" rx="2" fill="rgba(197,168,128,0.1)"/>
                    <rect x="63" y="22" width="34" height="4" rx="2" fill="rgba(197,168,128,0.08)"/>
                    <rect x="63" y="30" width="34" height="4" rx="2" fill="rgba(197,168,128,0.06)"/>
                    <rect x="63" y="38" width="34" height="4" rx="2" fill="rgba(197,168,128,0.04)"/>

                    <!-- Watch case -->
                    <rect x="22" y="44" width="116" height="112" rx="30" fill="url(#caseGrad)" stroke="url(#caseStroke)" stroke-width="2"/>

                    <!-- Case lug -->
                    <rect x="152" y="80" width="8" height="10" rx="3" fill="#2a2c35" stroke="rgba(197,168,128,0.3)" stroke-width="1"/>

                    <!-- Dial background -->
                    <circle cx="80" cy="100" r="46" fill="url(#dialGrad)"/>

                    <!-- Hour markers -->
                    <g stroke="rgba(197,168,128,0.8)" stroke-width="2" stroke-linecap="round">
                        <line x1="80" y1="58" x2="80" y2="65"/>
                        <line x1="80" y1="135" x2="80" y2="142"/>
                        <line x1="38" y1="100" x2="45" y2="100"/>
                        <line x1="115" y1="100" x2="122" y2="100"/>
                    </g>
                    <g stroke="rgba(197,168,128,0.3)" stroke-width="1" stroke-linecap="round">
                        <line x1="104.1" y1="62.5" x2="101.3" y2="67.3"/>
                        <line x1="117.5" y1="75.9" x2="112.7" y2="78.7"/>
                        <line x1="117.5" y1="124.1" x2="112.7" y2="121.3"/>
                        <line x1="104.1" y1="137.5" x2="101.3" y2="132.7"/>
                        <line x1="55.9" y1="137.5" x2="58.7" y2="132.7"/>
                        <line x1="42.5" y1="124.1" x2="47.3" y2="121.3"/>
                        <line x1="42.5" y1="75.9" x2="47.3" y2="78.7"/>
                        <line x1="55.9" y1="62.5" x2="58.7" y2="67.3"/>
                    </g>

                    <!-- Hour hand -->
                    <line id="hour-hand" x1="80" y1="100" x2="80" y2="75" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" transform-origin="80 100"/>
                    <!-- Minute hand -->
                    <line id="min-hand" x1="80" y1="100" x2="80" y2="66" stroke="#e5e7eb" stroke-width="2" stroke-linecap="round" transform-origin="80 100"/>
                    <!-- Second hand -->
                    <line id="sec-hand" x1="80" y1="108" x2="80" y2="62" stroke="#ef4444" stroke-width="1" stroke-linecap="round" transform-origin="80 100"/>

                    <!-- Center dot -->
                    <circle cx="80" cy="100" r="4" fill="var(--primary)"/>
                    <circle cx="80" cy="100" r="2" fill="#0a0b0d"/>

                    <!-- Brand text on dial -->
                    <text x="80" y="118" text-anchor="middle" font-family="serif" font-size="7" fill="rgba(197,168,128,0.7)" letter-spacing="2">ADVAYA</text>
                    <text x="80" y="126" text-anchor="middle" font-family="sans-serif" font-size="4.5" fill="rgba(197,168,128,0.4)" letter-spacing="1">SWISS MADE</text>

                    <!-- Watch strap bottom -->
                    <rect x="55" y="152" width="50" height="48" rx="6" fill="#1a1c22" stroke="rgba(197,168,128,0.3)" stroke-width="1"/>
                    <rect x="63" y="158" width="34" height="4" rx="2" fill="rgba(197,168,128,0.04)"/>
                    <rect x="63" y="166" width="34" height="4" rx="2" fill="rgba(197,168,128,0.06)"/>
                    <rect x="63" y="174" width="34" height="4" rx="2" fill="rgba(197,168,128,0.08)"/>
                    <rect x="63" y="182" width="34" height="4" rx="2" fill="rgba(197,168,128,0.1)"/>
                    <rect x="63" y="190" width="34" height="4" rx="2" fill="rgba(197,168,128,0.15)"/>

                    <defs>
                        <linearGradient id="caseGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%"   stop-color="#2e3040"/>
                            <stop offset="50%"  stop-color="#1a1d28"/>
                            <stop offset="100%" stop-color="#0f1118"/>
                        </linearGradient>
                        <linearGradient id="caseStroke" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%"   stop-color="rgba(197,168,128,0.6)"/>
                            <stop offset="50%"  stop-color="rgba(197,168,128,0.2)"/>
                            <stop offset="100%" stop-color="rgba(197,168,128,0.4)"/>
                        </linearGradient>
                        <radialGradient id="dialGrad" cx="40%" cy="35%">
                            <stop offset="0%"   stop-color="#1e2130"/>
                            <stop offset="100%" stop-color="#0d0f1a"/>
                        </radialGradient>
                    </defs>
                </svg>
            </div>
        </div>

        <div class="brand-logo">Advaya</div>
        <div class="brand-tagline">Premium Timepieces</div>

        <p class="brand-quote">
            Time is the most precious luxury. Wear it wisely.
        </p>
    </aside>


    <!-- =====================
         RIGHT — FORM PANEL
         ===================== -->
    <section class="login-form-panel">
        <div class="login-card">

            <div class="form-header">
                <a href="../index.php" class="back-link" id="back-to-store">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 5l-7 7 7 7"/>
                    </svg>
                    Back to Store
                </a>
                <h1>Welcome Back</h1>
                <p>Sign in to your Advaya account</p>
            </div>

            <!-- General error -->
            <?php $generalError = fieldError($errors, 'general'); ?>
            <?php if ($generalError): ?>
                <div class="alert-error" role="alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" flex-shrink="0">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <?= htmlspecialchars($generalError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="login-form" novalidate>

                <!-- Email -->
                <?php $emailErr = fieldError($errors, 'email'); ?>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control <?= $emailErr ? 'is-error' : '' ?>"
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="you@example.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                    <?php if ($emailErr): ?>
                        <span class="field-error">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <?= htmlspecialchars($emailErr) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <?php $pwdErr = fieldError($errors, 'password'); ?>
                <div class="form-group">
                    <label for="password">
                        Password
                        <a href="forgot-password.php" class="forgot-link" id="forgot-password-link">Forgot password?</a>
                    </label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control <?= $pwdErr ? 'is-error' : '' ?>"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-password" id="toggle-pwd" aria-label="Toggle password visibility">
                            <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <?php if ($pwdErr): ?>
                        <span class="field-error">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <?= htmlspecialchars($pwdErr) ?>
                        </span>
                        <?php if ($passwordError): ?>
                            <div class="password-hint" role="note">
                                <strong>Password Requirements</strong>
                                <ul>
                                    <li>Minimum 8 characters long</li>
                                    <li>Must include letters and numbers</li>
                                    <li>May include special characters (!, @, #, $…)</li>
                                    <li>Passwords are case-sensitive</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-login" id="submit-login">
                    Sign In
                </button>

            </form>

            <div class="divider">or</div>

            <div class="form-footer">
                Don't have an account?
                <a href="register.php" id="create-account-link">Create Account</a>
            </div>

        </div>
    </section>

</div>

<script>
    // =====================
    // Live Clock on Watch
    // =====================
    function updateWatchHands() {
        const now = new Date();
        const s = now.getSeconds();
        const m = now.getMinutes() + s / 60;
        const h = (now.getHours() % 12) + m / 60;

        const secDeg  = s * 6;
        const minDeg  = m * 6;
        const hourDeg = h * 30;

        const secHand  = document.getElementById('sec-hand');
        const minHand  = document.getElementById('min-hand');
        const hourHand = document.getElementById('hour-hand');

        if (secHand)  secHand.setAttribute('transform',  `rotate(${secDeg},  80, 100)`);
        if (minHand)  minHand.setAttribute('transform',  `rotate(${minDeg},  80, 100)`);
        if (hourHand) hourHand.setAttribute('transform', `rotate(${hourDeg}, 80, 100)`);
    }
    updateWatchHands();
    setInterval(updateWatchHands, 1000);

    // =====================
    // Show/Hide Password
    // =====================
    const toggleBtn = document.getElementById('toggle-pwd');
    const pwdInput  = document.getElementById('password');
    const eyeIcon   = document.getElementById('eye-icon');

    const eyeOpen   = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    const eyeSlash  = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            eyeIcon.innerHTML = isPassword ? eyeSlash : eyeOpen;
            toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    }
</script>

</body>
</html>