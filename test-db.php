<?php
/**
 * Advaya Watch Store - Database Connection Test Page
 * 
 * Verifies connection and lists database information.
 */

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_connected = false;
$db_error = null;
$db_name = null;

try {
    // 1. Include config/database.php
    require_once __DIR__ . '/config/database.php';

    // 2. Connect to advaya_db
    $pdo = getDBConnection();
    $db_connected = true;

    // 3. Run SELECT DATABASE()
    $stmt = $pdo->query("SELECT DATABASE()");
    $db_name = $stmt->fetchColumn();

} catch (Exception $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test — Advaya</title>
    <!-- Premium Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .test-container {
            max-width: 600px;
            margin: 6rem auto;
            padding: 2.5rem;
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow-premium);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: var(--transition-smooth);
        }
        .test-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }
        .test-container:hover {
            border-color: var(--border-hover);
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.8), 0 0 20px var(--accent-glow);
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        .status-icon.success {
            color: var(--success);
            text-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }
        .status-icon.error {
            color: var(--danger);
            text-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
        }
        .test-title {
            font-family: var(--font-heading);
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        .test-message {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        .db-info-box {
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
        }
        .info-value {
            font-family: monospace;
            color: var(--primary-light);
            font-size: 1rem;
        }
        .btn-back {
            display: inline-block;
            padding: 0.75rem 2rem;
            background-color: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: 0.85rem;
            transition: var(--transition-smooth);
        }
        .btn-back:hover {
            background-color: var(--primary);
            color: var(--bg-main);
            box-shadow: 0 0 15px rgba(197, 168, 128, 0.4);
        }
        .error-details {
            font-family: monospace;
            color: var(--danger);
            word-break: break-all;
            background: var(--danger-bg);
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid rgba(239, 68, 68, 0.2);
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <header id="main-header">
        <div class="container header-content">
            <a href="index.php" class="logo" id="header-logo">
                Advaya <span>Watch Store</span>
            </a>
            <ul class="nav-links" id="header-navigation">
                <li><a href="index.php" id="nav-home">Home</a></li>
                <li><a href="test-db.php" class="active" id="nav-test-db">DB Test</a></li>
            </ul>
        </div>
    </header>

    <main class="container">
        <div class="test-container" id="test-result-card">
            <?php if ($db_connected): ?>
                <div class="status-icon success" id="status-icon">✓</div>
                <h1 class="test-title" id="test-title">Database Connected</h1>
                <p class="test-message" id="test-message">Successfully connected to the local MySQL server using PDO.</p>
                
                <div class="db-info-box" id="info-box">
                    <div class="info-row">
                        <span class="info-label">Host:</span>
                        <span class="info-value"><?= htmlspecialchars(DB_HOST) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Port:</span>
                        <span class="info-value"><?= htmlspecialchars(DB_PORT) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Connected Database:</span>
                        <span class="info-value" style="color: var(--primary); font-weight: bold;"><?= htmlspecialchars($db_name) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Charset:</span>
                        <span class="info-value"><?= htmlspecialchars(DB_CHARSET) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Driver:</span>
                        <span class="info-value">PDO (MySQL)</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="status-icon error" id="status-icon">✗</div>
                <h1 class="test-title" id="test-title">Connection Failed</h1>
                <p class="test-message" id="test-message">Unable to establish database connection.</p>
                
                <div class="db-info-box" id="info-box">
                    <div class="info-row">
                        <span class="info-label">Attempted Host:</span>
                        <span class="info-value"><?= htmlspecialchars(DB_HOST) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Attempted Port:</span>
                        <span class="info-value"><?= htmlspecialchars(DB_PORT) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Target Database:</span>
                        <span class="info-value"><?= htmlspecialchars(DB_NAME) ?></span>
                    </div>
                    <div class="error-details" id="error-details">
                        <strong>PDO Error:</strong><br>
                        <?= htmlspecialchars($db_error) ?>
                    </div>
                </div>
            <?php endif; ?>

            <a href="index.php" class="btn-back" id="back-home-btn">Return to Store</a>
        </div>
    </main>

</body>
</html>
