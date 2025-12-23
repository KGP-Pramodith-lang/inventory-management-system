<?php require '../connection/db.php'; ?>

<?php
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"] ?? "";

    if ($id === "" || !ctype_digit($id)) {
        $error = "Please enter a valid numeric product ID.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([(int)$id]);
            header("Location: ../features/inventory.php");
            exit();
        } catch (PDOException $e) {
            $error = "Delete failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Product</title>
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
            margin-bottom: 8px;
            text-align: center;
        }
        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 24px;
            text-align: center;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .form-actions .btn {
            flex: 1;
        }
        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="form-card">
            <h2>Delete Product</h2>
            <p class="subtitle">Enter the product ID to permanently delete it.</p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Product ID</label>
                    <input type="number" name="id" class="form-control" min="1" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">Delete</button>
                    <a href="../features/inventory.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
