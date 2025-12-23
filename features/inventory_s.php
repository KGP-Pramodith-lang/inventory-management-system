<?php require '../connection/db.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .inventory-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        }
        .header-card {
        background-color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        }
        .page-title {
        color: #1f2937;
        font-weight: 600;
        font-size: 28px;
        margin: 0;
        }
        .table-card {
        background-color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .table {
        margin: 0;
        }
        .table thead th {
        background-color: #584cf4;
        color: white;
        border: none;
        padding: 15px;
        font-weight: 500;
        }
        .table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #e5e7eb;
        }
        .table tbody tr:last-child td {
        border-bottom: none;
        }
        .table tbody tr:hover {
        background-color: #f9fafb;
        }
        .btn-back {
        background-color: #6b7280;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        transition: background-color 0.2s;
        }
        .btn-back:hover {
        background-color: #4b5563;
        color: white;
        }
        .btn-add {
        background-color: #10b981;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.2s;
        }
        .btn-add:hover {
        background-color: #059669;
        color: white;
        }
        .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="inventory-container">
        <div class="header-actions">
            <a href="../index.php" class="btn-back">
                &larr; Back
            </a>
        </div>

        <div class="header-card">
            <h1 class="page-title">Current Inventory</h1>
        </div>

        <div class="table-card">
            <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total Value</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch all products
            try {
                $stmt = $pdo->query("SELECT * FROM products");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $total_value = $row['price'] * $row['quantity'];
                    
                    // Color code low stock
                    $stock_class = ($row['quantity'] < 5) ? 'text-danger fw-bold' : '';
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['sku']) . "</td>";
                    echo "<td>$" . number_format($row['price'], 2) . "</td>";
                    echo "<td class='$stock_class'>" . htmlspecialchars($row['quantity']) . "</td>";
                    echo "<td>$" . number_format($total_value, 2) . "</td>";
                    
                    echo "</tr>";
                }
            } catch (PDOException $e) {
                echo "<tr><td colspan='6' class='text-center text-danger'>Error loading inventory: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
            }
            ?>
        </tbody>
            </table>
        </div>
    </div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>