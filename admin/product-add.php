<?php
$pageTitle = 'Add New Watch';
$activePage = 'products';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = '';

$name = '';
$brand = '';
$categoryId = '';
$price = '';
$stock = '';
$status = 'active';
$description = '';

try {
    $pdo = getDBConnection();
    
    // Fetch categories for selection
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $status = trim($_POST['status'] ?? 'active');
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if ($name === '') {
        $errors[] = 'Watch name is required.';
    }
    if ($brand === '') {
        $errors[] = 'Brand is required.';
    }
    if ($categoryId === false || $categoryId <= 0) {
        $errors[] = 'Please select a valid category.';
    }
    if ($price === false || $price <= 0) {
        $errors[] = 'Please enter a valid price greater than 0.';
    }
    if ($stock === false || $stock < 0) {
        $errors[] = 'Stock cannot be negative.';
    }
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }
    
    $imageFilename = null;
    
    // Handle file upload
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed with error code: ' . $file['error'];
        } else {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $fileInfo = pathinfo($file['name']);
            $ext = strtolower($fileInfo['extension'] ?? '');
            
            if (!in_array($ext, $allowedExtensions)) {
                $errors[] = 'Invalid file type. Allowed formats: JPG, JPEG, PNG, WEBP, GIF.';
            } else {
                $imageFilename = uniqid('watch_') . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $destination = $uploadDir . $imageFilename;
                
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $errors[] = 'Failed to save uploaded image.';
                    $imageFilename = null;
                }
            }
        }
    }
    
    // Insert into DB if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (category_id, name, brand, description, price, stock, image, status)
                VALUES (:category_id, :name, :brand, :description, :price, :stock, :image, :status)
            ");
            
            $stmt->execute([
                ':category_id' => $categoryId,
                ':name'        => $name,
                ':brand'       => $brand,
                ':description' => $description !== '' ? $description : null,
                ':price'       => $price,
                ':stock'       => $stock,
                ':image'       => $imageFilename,
                ':status'      => $status
            ]);
            
            header('Location: products.php?success=' . urlencode('Watch added successfully!'));
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div>
            <h1>Add New Watch</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 5px;">
                Enter watch details below to publish to the catalog.
            </p>
        </div>
        <a href="products.php" class="btn-admin btn-admin-secondary">
            Back to Products
        </a>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul style="padding-left: 20px; margin: 0;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <form action="product-add.php" method="POST" enctype="multipart/form-data">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="brand">Brand *</label>
                    <input type="text" name="brand" id="brand" class="form-control" placeholder="e.g. Rolex, Omega" value="<?= htmlspecialchars($brand) ?>" required>
                </div>

                <div class="form-group">
                    <label for="name">Watch Name *</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Submariner Date" value="<?= htmlspecialchars($name) ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select name="category_id" id="category_id" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <input type="number" name="price" id="price" class="form-control" placeholder="0.00" step="0.01" min="0.01" value="<?= htmlspecialchars($price) ?>" required>
                </div>

                <div class="form-group">
                    <label for="stock">Stock Level *</label>
                    <input type="number" name="stock" id="stock" class="form-control" placeholder="0" min="0" value="<?= htmlspecialchars($stock) ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="status">Catalog Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Watch Image</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                        Allowed formats: JPG, JPEG, PNG, WEBP, GIF. Max file size: 5MB.
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Product Description</label>
                <textarea name="description" id="description" class="form-control" placeholder="Describe the timepiece details..."><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn-admin btn-admin-primary">
                    Publish Watch
                </button>
            </div>

        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
