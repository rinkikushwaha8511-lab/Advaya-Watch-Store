<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';

try {
    $pdo = getDBConnection();

    // 1. Total Revenue
    $salesStmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status != 'cancelled'");
    $totalSales = (float) $salesStmt->fetchColumn();

    // 2. Total Orders
    $ordersCountStmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int) $ordersCountStmt->fetchColumn();

    // 3. Total Products
    $productsCountStmt = $pdo->query("SELECT COUNT(*) FROM products");
    $totalProducts = (int) $productsCountStmt->fetchColumn();

    // 4. Total Customers
    $customersCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $totalCustomers = (int) $customersCountStmt->fetchColumn();

    // 5. Recent 5 orders
    $recentOrdersStmt = $pdo->query("
        SELECT
            o.id, o.total_amount, o.order_status, o.created_at,
            u.name AS customer_name, u.email AS customer_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Pending orders count
    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'");
    $pendingOrders = (int) $pendingStmt->fetchColumn();

} catch (PDOException $e) {
    die('Database error: ' . htmlspecialchars($e->getMessage()));
}

$today = date('l, d F Y');
?>

<style>
    /* =====================================================
       DASHBOARD PAGE STYLES
       ===================================================== */

    /* Breadcrumb */
    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.78rem;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .breadcrumb span { color: var(--primary); }

    /* Page header */
    .dash-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 2.5rem;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .dash-header h1 {
        font-family: var(--font-heading);
        font-size: 2.4rem;
        font-weight: 400;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }
    .dash-header .date-badge {
        font-size: 0.78rem;
        color: var(--text-muted);
        letter-spacing: 0.5px;
    }
    .dash-header .action-group {
        display: flex;
        gap: 10px;
    }

    /* =====================================================
       STATS GRID
       ===================================================== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2.5rem;
    }

    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        transition: var(--transition-smooth);
        cursor: default;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0;
        transition: opacity 0.3s;
        border-radius: 14px;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: var(--border-hover);
        box-shadow: var(--shadow-premium);
    }
    .stat-card:hover::before { opacity: 1; }

    /* Accent stripe */
    .stat-card .stripe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        border-radius: 14px 14px 0 0;
    }

    .stat-card .card-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.1rem;
        flex-shrink: 0;
    }

    .stat-card .stat-label {
        display: block;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-muted);
        font-weight: 600;
        margin-bottom: 6px;
    }

    .stat-card .stat-value {
        font-family: var(--font-heading);
        font-size: 2.2rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 6px;
    }

    .stat-card .stat-sub {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* Individual card accent colors */
    .stat-card.revenue .stripe     { background: linear-gradient(90deg, #c5a880, #8c7250); }
    .stat-card.revenue .card-icon  { background: rgba(197,168,128,0.1); }
    .stat-card.revenue .stat-value { color: var(--primary-light); }

    .stat-card.orders .stripe      { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
    .stat-card.orders .card-icon   { background: rgba(59,130,246,0.1); }
    .stat-card.orders .stat-value  { color: #93c5fd; }

    .stat-card.products .stripe    { background: linear-gradient(90deg, #10b981, #059669); }
    .stat-card.products .card-icon { background: rgba(16,185,129,0.1); }
    .stat-card.products .stat-value{ color: #6ee7b7; }

    .stat-card.customers .stripe   { background: linear-gradient(90deg, #a855f7, #7c3aed); }
    .stat-card.customers .card-icon{ background: rgba(168,85,247,0.1); }
    .stat-card.customers .stat-value{ color: #d8b4fe; }

    /* =====================================================
       SECTION TITLE
       ===================================================== */
    .dash-section-title {
        font-family: var(--font-heading);
        font-size: 1.4rem;
        font-weight: 400;
        color: var(--text-primary);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dash-section-title .pill {
        font-family: var(--font-body);
        font-size: 0.72rem;
        background: rgba(197,168,128,0.1);
        color: var(--primary);
        border: 1px solid rgba(197,168,128,0.2);
        padding: 2px 10px;
        border-radius: 20px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* =====================================================
       PENDING ALERT BANNER
       ===================================================== */
    .pending-alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        background: rgba(245,158,11,0.08);
        border: 1px solid rgba(245,158,11,0.2);
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
        color: #f59e0b;
    }
    .pending-alert a {
        color: #f59e0b;
        font-weight: 600;
        margin-left: auto;
        text-decoration: none;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .pending-alert a:hover { text-decoration: underline; }

    /* =====================================================
       ORDERS TABLE  (inherits admin-table styles from header)
       ===================================================== */
    .order-row-new td:first-child::before {
        content: 'NEW';
        font-size: 0.55rem;
        background: var(--primary);
        color: #0a0b0d;
        padding: 1px 5px;
        border-radius: 3px;
        font-weight: 700;
        margin-right: 6px;
        letter-spacing: 0.5px;
        vertical-align: middle;
    }

    /* =====================================================
       QUICK STATS ROW (bottom)
       ===================================================== */
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-top: 2rem;
    }
    .quick-stat-item {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 1.1rem 1.3rem;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: var(--transition-smooth);
    }
    .quick-stat-item:hover {
        border-color: var(--border-hover);
    }
    .quick-stat-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .quick-stat-info { flex: 1; min-width: 0; }
    .quick-stat-label {
        font-size: 0.72rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        display: block;
        margin-bottom: 2px;
    }
    .quick-stat-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-primary);
    }
</style>

<main class="admin-container">

    <!-- Dashboard Header -->
    <div class="dash-header">
        <div>
            <div class="breadcrumb">Admin <span>&#8250;</span> Dashboard</div>
            <h1>Overview Dashboard</h1>
            <span class="date-badge"><?= $today ?></span>
        </div>
        <div class="action-group">
            <a href="products.php" class="btn-admin btn-admin-secondary">Manage Products</a>
            <a href="product-add.php" class="btn-admin btn-admin-primary">+ Add Watch</a>
        </div>
    </div>

    <!-- Pending Alert -->
    <?php if ($pendingOrders > 0): ?>
        <div class="pending-alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            You have <strong><?= $pendingOrders ?> pending order<?= $pendingOrders > 1 ? 's' : '' ?></strong> that require attention.
            <a href="orders.php">View Orders &rarr;</a>
        </div>
    <?php endif; ?>

    <!-- ============== STATS GRID ============== -->
    <div class="stats-grid">

        <!-- Revenue -->
        <div class="stat-card revenue">
            <div class="stripe"></div>
            <div class="card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="rgba(197,168,128,0.9)" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <span class="stat-label">Total Revenue</span>
            <div class="stat-value">$<?= number_format($totalSales, 0) ?></div>
            <span class="stat-sub">Excluding cancelled orders</span>
        </div>

        <!-- Orders -->
        <div class="stat-card orders">
            <div class="stripe"></div>
            <div class="card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="rgba(59,130,246,0.9)" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
            </div>
            <span class="stat-label">Total Orders</span>
            <div class="stat-value"><?= $totalOrders ?></div>
            <span class="stat-sub"><?= $pendingOrders ?> pending review</span>
        </div>

        <!-- Products -->
        <div class="stat-card products">
            <div class="stripe"></div>
            <div class="card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="rgba(16,185,129,0.9)" stroke-width="2">
                    <circle cx="12" cy="12" r="7"/>
                    <polyline points="12 9 12 12 13.5 13.5"/>
                    <path d="M16.51 17.35l-.35 3.83a2 2 0 0 1-2 1.82H9.83a2 2 0 0 1-2-1.82l-.35-3.83m.01-10.7l.35-3.83A2 2 0 0 1 9.83 1h4.35a2 2 0 0 1 2 1.82l.35 3.83"/>
                </svg>
            </div>
            <span class="stat-label">Total Watches</span>
            <div class="stat-value"><?= $totalProducts ?></div>
            <span class="stat-sub">Active in catalog</span>
        </div>

        <!-- Customers -->
        <div class="stat-card customers">
            <div class="stripe"></div>
            <div class="card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="rgba(168,85,247,0.9)" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <span class="stat-label">Total Customers</span>
            <div class="stat-value"><?= $totalCustomers ?></div>
            <span class="stat-sub">Registered accounts</span>
        </div>

    </div><!-- /.stats-grid -->


    <!-- ============== RECENT ORDERS ============== -->
    <h2 class="dash-section-title">
        Recent Orders
        <span class="pill">Last 5</span>
    </h2>

    <?php if (empty($recentOrders)): ?>
        <div class="admin-card" style="text-align:center; padding:50px 20px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" style="margin: 0 auto 16px;">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
            </svg>
            <p style="color: var(--text-secondary);">No orders have been placed yet.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date &amp; Time</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $i => $order): ?>
                        <?php
                        $status = strtolower($order['order_status']);
                        $statusClass = match($status) {
                            'processing' => 'status-processing',
                            'shipped'    => 'status-shipped',
                            'delivered'  => 'status-delivered',
                            'cancelled'  => 'status-cancelled',
                            default      => 'status-pending',
                        };
                        $isNew = $i === 0; // mark first row as "new"
                        ?>
                        <tr class="<?= $isNew ? 'order-row-new' : '' ?>">
                            <td><strong>#ADV-<?= (int) $order['id'] ?></strong></td>
                            <td>
                                <div style="font-weight:600;"><?= htmlspecialchars($order['customer_name']) ?></div>
                                <div style="font-size:0.78rem; color:var(--text-muted);"><?= htmlspecialchars($order['customer_email']) ?></div>
                            </td>
                            <td style="white-space:nowrap;"><?= date('d M Y', strtotime($order['created_at'])) ?><br><span style="font-size:0.75rem;color:var(--text-muted);"><?= date('h:i A', strtotime($order['created_at'])) ?></span></td>
                            <td>
                                <span style="font-weight:700; color:var(--primary-light);">
                                    $<?= number_format((float)$order['total_amount'], 2) ?>
                                </span>
                            </td>
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

        <div style="text-align:right; margin-top:14px;">
            <a href="orders.php" class="btn-admin btn-admin-primary">View All Orders &rarr;</a>
        </div>
    <?php endif; ?>


    <!-- ============== QUICK STATS BOTTOM ROW ============== -->
    <div class="quick-stats">
        <div class="quick-stat-item">
            <div class="quick-stat-dot" style="background:#f59e0b;"></div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Pending</span>
                <span class="quick-stat-value"><?= $pendingOrders ?></span>
            </div>
        </div>
        <div class="quick-stat-item">
            <div class="quick-stat-dot" style="background:#10b981;"></div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Avg. Order Value</span>
                <span class="quick-stat-value">$<?= $totalOrders > 0 ? number_format($totalSales / $totalOrders, 0) : '0' ?></span>
            </div>
        </div>
        <div class="quick-stat-item">
            <div class="quick-stat-dot" style="background:#c5a880;"></div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Revenue / Customer</span>
                <span class="quick-stat-value">$<?= $totalCustomers > 0 ? number_format($totalSales / $totalCustomers, 0) : '0' ?></span>
            </div>
        </div>
        <div class="quick-stat-item">
            <div class="quick-stat-dot" style="background:#a855f7;"></div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Catalog Items</span>
                <span class="quick-stat-value"><?= $totalProducts ?> Watches</span>
            </div>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
