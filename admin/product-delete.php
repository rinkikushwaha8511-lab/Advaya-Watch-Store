<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: products.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Fetch product to get the image filename
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: products.php?error=' . urlencode('Watch not found!'));
        exit;
    }
    
    // Attempt deletion
    $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $deleteStmt->execute([':id' => $id]);
    
    // Clean up uploaded image file
    if (!empty($product['image'])) {
        $filePath = __DIR__ . '/../uploads/' . $product['image'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
    
    header('Location: products.php?success=' . urlencode('Watch deleted successfully!'));
    exit;

} catch (PDOException $e) {
    // Catch foreign key constraint violation (RESTRICT check on order items)
    if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'a foreign key constraint fails')) {
        header('Location: products.php?error=' . urlencode('Cannot delete this watch because it exists in past customer orders. You should set its status to "Inactive" instead.'));
    } else {
        header('Location: products.php?error=' . urlencode('Failed to delete watch: ' . $e->getMessage()));
    }
    exit;
}
