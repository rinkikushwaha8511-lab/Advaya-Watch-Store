<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';

try {
    $pdo = getDBConnection();

    // 1. Calculate Total Sales (excluding cancelled orders)
    $salesStmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE order_status != 'cancelled'");
    $totalSales = (float) $salesStmt->fetchColumn();

    // 2. Count Total Orders
    $ordersCountStmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int) $ordersCountStmt->fetchColumn();

    // 3. Count Total Products
    $productsCountStmt = $pdo->query("SELECT COUNT(*) FROM products");
    $totalProducts = (int) $productsCountStmt->fetchColumn();

    // 4. Count Total Customers
    $customersCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $totalCustomers = (int) $customersCountStmt->fetchColumn();

    // 5. Fetch 5 Recent Orders
    $recentOrdersStmt = $pdo->query("
        SELECT 
            o.id, 
            o.total_amount, 
            o.order_status, 
            o.created_at, 
            u.name AS customer_name, 
            u.email AS customer_email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Database error: ' . htmlspecialchars($e->getMessage()));
}
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 25px;
        box-shadow: var(--shadow-premium);
        transition: var(--transition-smooth);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary);
        opacity: 0.7;
    }

    .stat-card:hover {
        border-color: var(--border-hover);
        transform: translateY(-4px);
    }

    .stat-label {
        display: block;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-secondary);
        margin-bottom: 8px;
        font-weight: 600;
    }

    .stat-value {
        font-family: var(--font-heading);
        font-size: 2rem;
        color: var(--primary-light);
        font-weight: 700;
    }

    .dashboard-section-title {
        font-family: var(--font-heading);
        font-size: 1.5rem;
        font-weight: 400;
        margin-top: 30px;
        margin-bottom: 20px;
        color: var(--primary-light);
    }
</style>

<main class="admin-container">
    <div class="admin-page-header">
        <div>
            <h1>Overview Dashboard</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 5px;">
                Welcome back, Administrator. Here is the latest watch store activity.
            </p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-label">Total Revenue</span>
            <div class="stat-value">$<?= number_format($totalSales, 2) ?></div>
        </div>
        <div class="stat-card">
            <span class="stat-label">Total Orders</span>
            <div class="stat-value"><?= $totalOrders ?></div>
        </div>
        <div class="stat-card">
            <span class="stat-label">Total Watches</span>
            <div class="stat-value"><?= $totalProducts ?></div>
        </div>
        <div class="stat-card">
            <span class="stat-label">Total Customers</span>
            <div class="stat-value"><?= $totalCustomers ?></div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <h2 class="dashboard-section-title">Recent Orders</h2>
    
    <?php if (empty($recentOrders)): ?>
        <div class="admin-card" style="text-align: center; padding: 40px;">
            <p style="color: var(--text-secondary);">No orders have been placed yet.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <?php
                        $statusClass = 'status-pending';
                        $status = strtolower($order['order_status']);
                        if ($status === 'processing') {
                            $statusClass = 'status-processing';
                        } elseif ($status === 'shipped') {
                            $statusClass = 'status-shipped';
                        } elseif ($status === 'delivered') {
                            $statusClass = 'status-delivered';
                        } elseif ($status === 'cancelled') {
                            $statusClass = 'status-cancelled';
                        }
                        ?>
                        <tr>
                            <td>#ADV-<?= (int) $order['id'] ?></td>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($order['customer_name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($order['customer_email']) ?></div>
                            </td>
                            <td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td>
                            <td style="font-weight: 600; color: var(--primary-light);">$<?= number_format((float)$order['total_amount'], 2) ?></td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="order-details.php?order_id=<?= (int)$order['id'] ?>" class="btn-admin btn-admin-secondary">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
