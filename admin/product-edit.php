<?php
$pageTitle = 'Edit Watch';
$activePage = 'products';
require_once __DIR__ . '/includes/header.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$errors = [];
$success = '';

try {
    $pdo = getDBConnection();
    
    // Fetch product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: products.php?error=' . urlencode('Watch not found!'));
        exit;
    }
    
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
    
    $imageFilename = $product['image']; // Default to existing image
    
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
                $newFilename = uniqid('watch_') . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $destination = $uploadDir . $newFilename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Delete old image if it exists
                    if (!empty($product['image']) && file_exists($uploadDir . $product['image'])) {
                        @unlink($uploadDir . $product['image']);
                    }
                    $imageFilename = $newFilename;
                } else {
                    $errors[] = 'Failed to save uploaded image.';
                }
            }
        }
    }
    
    // Update DB if no errors
    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE products 
                SET category_id = :category_id, 
                    name = :name, 
                    brand = :brand, 
                    description = :description, 
                    price = :price, 
                    stock = :stock, 
                    image = :image, 
                    status = :status
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':category_id' => $categoryId,
                ':name'        => $name,
                ':brand'       => $brand,
                ':description' => $description !== '' ? $description : null,
                ':price'       => $price,
                ':stock'       => $stock,
                ':image'       => $imageFilename,
                ':status'      => $status,
                ':id'          => $id
            ]);
            
            header('Location: products.php?success=' . urlencode('Watch updated successfully!'));
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }
    
    // Re-assign form values so they reflect what user inputted
    $product['name'] = $name;
    $product['brand'] = $brand;
    $product['category_id'] = $categoryId;
    $product['price'] = $price;
    $product['stock'] = $stock;
    $product['status'] = $status;
    $product['description'] = $description;
}
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div>
            <h1>Edit Watch details</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 5px;">
                Modify watch details for "#ADV-<?= (int)$product['id'] ?>" in the catalog.
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
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <form action="product-edit.php?id=<?= (int)$product['id'] ?>" method="POST" enctype="multipart/form-data">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="brand">Brand *</label>
                    <input type="text" name="brand" id="brand" class="form-control" placeholder="e.g. Rolex, Omega" value="<?= htmlspecialchars($product['brand']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="name">Watch Name *</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Submariner Date" value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select name="category_id" id="category_id" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <input type="number" name="price" id="price" class="form-control" placeholder="0.00" step="0.01" min="0.01" value="<?= htmlspecialchars($product['price']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="stock">Stock Level *</label>
                    <input type="number" name="stock" id="stock" class="form-control" placeholder="0" min="0" value="<?= htmlspecialchars($product['stock']) ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="status">Catalog Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                        <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Replace Watch Image</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                        Leave blank to keep current image. Allowed formats: JPG, JPEG, PNG, WEBP, GIF.
                    </p>
                    
                    <?php if (!empty($product['image'])): ?>
                        <div style="margin-top: 15px; display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 0.85rem; color: var(--text-secondary);">Current:</span>
                            <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" alt="" class="product-thumb">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Product Description</label>
                <textarea name="description" id="description" class="form-control" placeholder="Describe the timepiece details..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn-admin btn-admin-primary">
                    Update Watch Info
                </button>
            </div>

        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
