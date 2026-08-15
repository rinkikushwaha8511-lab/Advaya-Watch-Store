<?php
$pageTitle = 'Manage Products';
$activePage = 'products';
require_once __DIR__ . '/includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $pdo = getDBConnection();

    // Fetch all products joined with categories
    $stmt = $pdo->query("
        SELECT 
            p.id, 
            p.name, 
            p.brand, 
            p.price, 
            p.stock, 
            p.image, 
            p.status, 
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Database error: ' . htmlspecialchars($e->getMessage());
}
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div>
            <h1>Products</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 5px;">
                Manage your catalog, edit details, and add new luxury watches.
            </p>
        </div>
        <a href="product-add.php" class="btn-admin btn-admin-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Watch
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

    <?php if (empty($products)): ?>
        <div class="admin-card" style="text-align: center; padding: 50px;">
            <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 10px;">No products found</h2>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">Start adding premium watches to catalog.</p>
            <a href="product-add.php" class="btn-admin btn-admin-primary">Add Your First Product</a>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Watch</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>#<?= (int)$product['id'] ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <?php if (!empty($product['image']) && file_exists(__DIR__ . '/../uploads/' . $product['image'])): ?>
                                        <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" alt="" class="product-thumb">
                                    <?php else: ?>
                                        <div class="product-thumb" style="display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: bold; color: var(--text-muted); text-transform: uppercase;">
                                            No Image
                                        </div>
                                    <?php endif; ?>
                                    <div style="font-weight: 500; font-family: var(--font-body); max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($product['brand']) ?></td>
                            <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                            <td style="font-weight: 600; color: var(--primary-light);">$<?= number_format((float)$product['price'], 2) ?></td>
                            <td>
                                <?php if ($product['stock'] <= 0): ?>
                                    <span style="color: var(--danger); font-weight: 600;">Out of Stock</span>
                                <?php elseif ($product['stock'] <= 5): ?>
                                    <span style="color: #f59e0b; font-weight: 600;"><?= (int)$product['stock'] ?> Left</span>
                                <?php else: ?>
                                    <?= (int)$product['stock'] ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= strtolower($product['status']) === 'active' ? 'status-active' : 'status-inactive' ?>">
                                    <?= htmlspecialchars(ucfirst($product['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-links">
                                    <a href="product-edit.php?id=<?= (int)$product['id'] ?>" class="btn-admin btn-admin-secondary">
                                        Edit
                                    </a>
                                    <a href="product-delete.php?id=<?= (int)$product['id'] ?>" class="btn-admin btn-admin-danger" onclick="return confirm('Are you sure you want to delete this product?');">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
