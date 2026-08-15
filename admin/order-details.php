<?php
$pageTitle = 'Order Details';
$activePage = 'orders';
require_once __DIR__ . '/includes/header.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $pdo = getDBConnection();
    
    // Process Status Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $newStatus = trim($_POST['order_status'] ?? '');
        $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($newStatus, $allowedStatuses)) {
            $error = 'Invalid status selected.';
        } else {
            $updateStmt = $pdo->prepare("UPDATE orders SET order_status = :status WHERE id = :id");
            $updateStmt->execute([
                ':status' => $newStatus,
                ':id' => $orderId
            ]);
            header('Location: order-details.php?order_id=' . $orderId . '&success=' . urlencode('Order status updated successfully!'));
            exit;
        }
    }
    
    // Fetch Order details with customer info
    $stmt = $pdo->prepare("
        SELECT 
            o.id, 
            o.total_amount, 
            o.shipping_address, 
            o.payment_method, 
            o.order_status, 
            o.created_at, 
            u.name AS customer_name, 
            u.email AS customer_email,
            u.phone AS customer_phone
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = :order_id
        LIMIT 1
    ");
    $stmt->execute([':order_id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php?error=' . urlencode('Order not found!'));
        exit;
    }
    
    // Fetch Items in the order
    $stmtItems = $pdo->prepare("
        SELECT 
            oi.quantity, 
            oi.price AS item_price, 
            p.id AS product_id, 
            p.name AS product_name, 
            p.brand AS product_brand, 
            p.image AS product_image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id
    ");
    $stmtItems->execute([':order_id' => $orderId]);
    $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Database error: ' . htmlspecialchars($e->getMessage());
}
?>

<style>
    .order-details-grid {
        display: grid;
        grid-template-columns: 7fr 3fr;
        gap: 30px;
        align-items: start;
        margin-top: 25px;
    }

    .order-card-header {
        border-bottom: 1px solid var(--border);
        padding-bottom: 15px;
        margin-bottom: 25px;
    }

    .order-item-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .order-item-row {
        display: flex;
        align-items: center;
        gap: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .order-item-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .item-image {
        width: 70px;
        height: 70px;
        background: radial-gradient(circle, #20242a 0%, #131518 100%);
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.05);
        flex-shrink: 0;
    }

    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .item-details {
        flex-grow: 1;
    }

    .item-brand {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: var(--primary);
        letter-spacing: 1.5px;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .item-name {
        font-size: 1rem;
        font-weight: 500;
        color: var(--text-primary);
        text-decoration: none;
    }

    .item-pricing {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 5px;
    }

    .item-subtotal {
        font-family: var(--font-heading);
        font-size: 1.1rem;
        color: var(--primary-light);
        font-weight: 600;
    }

    .info-section {
        margin-bottom: 25px;
    }

    .info-section:last-child {
        margin-bottom: 0;
    }

    .info-label {
        display: block;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-secondary);
        margin-bottom: 6px;
        font-weight: 600;
    }

    .info-value {
        font-size: 0.95rem;
        color: var(--text-primary);
        font-weight: 500;
        line-height: 1.5;
    }

    .address-box {
        white-space: pre-line;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 12px;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .total-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        margin-top: 20px;
    }

    @media (max-width: 900px) {
        .order-details-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="admin-container">
    <div class="admin-page-header">
        <div>
            <h1>Order Fulfillment</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 5px;">
                Manage order state, print details, and review customer shipping information.
            </p>
        </div>
        <a href="orders.php" class="btn-admin btn-admin-secondary">
            Back to Orders
        </a>
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

    <div class="order-details-grid">
        
        <!-- Left details: Items -->
        <div class="admin-card">
            <div class="order-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 400; color: var(--primary-light);">
                        Order #ADV-<?= (int)$order['id'] ?>
                    </h2>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                    </p>
                </div>
                
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
                <span class="status-badge <?= $statusClass ?>">
                    <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                </span>
            </div>

            <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 20px;">Products List</h3>
            
            <div class="order-item-list">
                <?php foreach ($orderItems as $item): ?>
                    <div class="order-item-row">
                        <div class="item-image">
                            <?php if (!empty($item['product_image']) && file_exists(__DIR__ . '/../uploads/' . $item['product_image'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($item['product_image']) ?>" alt="">
                            <?php else: ?>
                                <div class="image-placeholder-small" style="font-size: 0.65rem; font-weight: bold; color: var(--text-muted); text-transform: uppercase;">
                                    <?= htmlspecialchars($item['product_brand']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="item-details">
                            <div class="item-brand"><?= htmlspecialchars($item['product_brand']) ?></div>
                            <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                            
                            <div class="item-pricing">
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                    $<?= number_format((float)$item['item_price'], 2) ?> &times; <?= (int)$item['quantity'] ?>
                                </div>
                                <div class="item-subtotal">
                                    $<?= number_format((float)($item['item_price'] * $item['quantity']), 2) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right details: Customer and Status management -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Update Status Box -->
            <div class="admin-card">
                <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 400; margin-bottom: 20px; color: var(--primary-light); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    Update Fulfillment Status
                </h2>
                <form action="order-details.php?order_id=<?= (int)$order['id'] ?>" method="POST">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="form-group">
                        <label for="order_status">Current Status</label>
                        <select name="order_status" id="order_status" class="form-control">
                            <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending Payment</option>
                            <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Processing order</option>
                            <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped / Dispatched</option>
                            <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
                        Update Status
                    </button>
                </form>
            </div>

            <!-- Customer Details Card -->
            <div class="admin-card">
                <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 400; margin-bottom: 20px; color: var(--primary-light); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    Billing & Shipping
                </h2>
                
                <div class="info-section">
                    <span class="info-label">Customer Name</span>
                    <span class="info-value"><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>

                <div class="info-section">
                    <span class="info-label">Customer Email</span>
                    <span class="info-value" style="font-family: monospace; font-size: 0.85rem;"><?= htmlspecialchars($order['customer_email']) ?></span>
                </div>

                <?php if (!empty($order['customer_phone'])): ?>
                    <div class="info-section">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value"><?= htmlspecialchars($order['customer_phone']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="info-section">
                    <span class="info-label">Payment Method</span>
                    <span class="info-value" style="display: inline-flex; align-items: center; gap: 5px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <?= htmlspecialchars($order['payment_method']) ?>
                    </span>
                </div>

                <div class="info-section">
                    <span class="info-label">Delivery Address</span>
                    <div class="info-value address-box"><?= htmlspecialchars($order['shipping_address']) ?></div>
                </div>

                <div class="total-box">
                    <span style="font-weight: 600; font-size: 0.95rem;">Total Amount</span>
                    <span style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: bold; color: var(--primary);">$<?= number_format((float)$order['total_amount'], 2) ?></span>
                </div>
            </div>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
