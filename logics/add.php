<?php require '../connection/db.php'; ?>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $price = $_POST['price'] ?? '';
    $quantity = $_POST['quantity'] ?? '';

    if ($sku === '') {
        $letters_only = preg_replace('/[^a-zA-Z]/', '', $name);
        $prefix_raw = strtoupper(substr($letters_only, 0, 3));
        $prefix = str_pad($prefix_raw, 3, 'X');

        $sku_prefix = $prefix . '-';
        $sku_regex = '^' . preg_quote($prefix, '/') . '-[0-9]{3}$';

        $stmtMax = $pdo->prepare(
            "SELECT MAX(CAST(SUBSTRING(sku, 5) AS UNSIGNED)) AS max_seq\n" .
            "FROM products\n" .
            "WHERE sku LIKE ? AND sku REGEXP ?"
        );
        $stmtMax->execute([$sku_prefix . '%', $sku_regex]);
        $maxSeq = (int)($stmtMax->fetchColumn() ?? 0);

        $sku = $prefix . '-' . sprintf('%03d', $maxSeq + 1);
    }

    // Prepare SQL statement (Prevents SQL Injection)
    $sql = "INSERT INTO products (name, sku, price, quantity) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        if ($stmt->execute([$name, $sku, $price, $quantity])) {
            header("Location: ../features/inventory.php"); // Redirect to inventory after success
            exit();
        }
        echo "Error adding product.";
    } catch (PDOException $e) {
        echo "Error adding product: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .page-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 24px;
        }
        .form-card {
            width: 100%;
            max-width: 520px;
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }
        .form-card h2 {
            font-size: 18px;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 24px;
            text-align: center;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .form-actions .btn {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="form-card">
            <h2>Add New Product</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">SKU (Code)</label>
                    <input type="text" name="sku" class="form-control" placeholder="Leave blank to auto-generate">
                </div>
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Initial Quantity</label>
                    <input type="number" name="quantity" class="form-control" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Product</button>
                    <a href="../features/inventory.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>