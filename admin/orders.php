<?php
$pageTitle = 'Manage Orders';
$activePage = 'orders';
require_once __DIR__ . '/includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $pdo = getDBConnection();

    // Fetch all orders
    $stmt = $pdo->query("
        SELECT 
            o.id, 
            o.total_amount, 
            o.payment_method, 
            o.order_status, 
            o.created_at, 
            u.name AS customer_name, 
            u.email AS customer_email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Database error: ' . htmlspecialchars($e->getMessage());
}
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div>
            <h1>Orders</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 5px;">
                Manage customer purchases, track fulfillment states, and update status.
            </p>
        </div>
    </div>

    <!-- Alert notifications -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="admin-card" style="text-align: center; padding: 50px;">
            <p style="color: var(--text-secondary);">No customer orders have been placed yet.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date Placed</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
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
                            <td>#ADV-<?= (int)$order['id'] ?></td>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($order['customer_name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($order['customer_email']) ?></div>
                            </td>
                            <td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td>
                            <td style="font-weight: 600; color: var(--primary-light);">$<?= number_format((float)$order['total_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($order['payment_method']) ?></td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="order-details.php?order_id=<?= (int)$order['id'] ?>" class="btn-admin btn-admin-secondary">
                                    Manage Details
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
