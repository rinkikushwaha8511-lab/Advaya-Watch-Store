<?php
$pageTitle = 'Manage Categories';
$activePage = 'categories';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

try {
    $pdo = getDBConnection();
    
    // Process Add Category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($name === '') {
            $errors[] = 'Category name is required.';
        } else {
            // Check uniqueness
            $check = $pdo->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
            $check->execute([':name' => $name]);
            if ($check->fetch()) {
                $errors[] = 'Category name already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null
                ]);
                $success = 'Category added successfully!';
            }
        }
    }
    
    // Process Delete Category
    if (isset($_GET['action']) && $_GET['action'] === 'delete') {
        $deleteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($deleteId > 0) {
            try {
                $delStmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
                $delStmt->execute([':id' => $deleteId]);
                $success = 'Category deleted successfully!';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'a foreign key constraint fails')) {
                    $errors[] = 'Cannot delete category because it contains products. Move those products to another category first.';
                } else {
                    $errors[] = 'Failed to delete category: ' . $e->getMessage();
                }
            }
        }
    }
    
    // Fetch all categories with product counts
    $catListStmt = $pdo->query("
        SELECT 
            c.id, 
            c.name, 
            c.description,
            COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    $categories = $catListStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
}
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div>
            <h1>Categories</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 5px;">
                Manage watch categories and classify your inventory.
            </p>
        </div>
    </div>

    <!-- Alert notifications -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul style="padding-left: 20px; margin: 0;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
        
        <!-- Category Table -->
        <div class="admin-table-container" style="margin-top: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th style="text-align: center;">Watches Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No categories available.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--primary-light);"><?= htmlspecialchars($cat['name']) ?></td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: normal;">
                                    <?= htmlspecialchars($cat['description'] ?? 'No description provided.') ?>
                                </td>
                                <td style="text-align: center; font-weight: 600;"><?= (int)$cat['product_count'] ?></td>
                                <td>
                                    <a href="categories.php?action=delete&id=<?= (int)$cat['id'] ?>" class="btn-admin btn-admin-danger" onclick="return confirm('Are you sure you want to delete this category?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Category Form -->
        <div class="admin-card">
            <h2 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 400; margin-bottom: 20px; color: var(--primary-light); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                Create Category
            </h2>
            <form action="categories.php" method="POST">
                <input type="hidden" name="add_category" value="1">
                
                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Classic, Chronograph" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" placeholder="Brief category description..." style="min-height: 80px;"></textarea>
                </div>

                <div style="margin-top: 25px;">
                    <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; justify-content: center;">
                        Save Category
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
