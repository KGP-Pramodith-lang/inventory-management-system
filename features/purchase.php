<?php
session_start();
require '../connection/db.php';

// Initialize the Supply Cart
if (!isset($_SESSION['supply_cart'])) {
    $_SESSION['supply_cart'] = [];
}

$msg = "";

$back_href = "../index.php";
if (isset($_SESSION['role'])) {
    $back_href = ($_SESSION['role'] === 'admin') ? '../dashboard_m.php' : '../dashboard_s.php';
}

// --- LOGIC 1: ADD ITEM TO LIST ---
if (isset($_POST['add_to_list'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 0);
    $buying_price = (float)($_POST['buying_price'] ?? 0); // We enter cost manually, as it changes

    if ($product_id <= 0 || $qty <= 0 || $buying_price <= 0) {
        $msg = "<div class='alert alert-danger'>Error: Please select a valid product, quantity, and cost price.</div>";
    } else {

    // Fetch name for display
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

        if ($product) {
            $line_total = $buying_price * $qty;
            
            $_SESSION['supply_cart'][] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $buying_price,
                'qty' => $qty,
                'line_total' => $line_total
            ];
        } else {
            $msg = "<div class='alert alert-danger'>Error: Selected product not found.</div>";
        }
    }
}

// --- LOGIC 2: CLEAR LIST ---
if (isset($_POST['clear_list'])) {
    $_SESSION['supply_cart'] = [];
}

// --- LOGIC 3: SAVE STOCK (FINALIZE) ---
if (isset($_POST['save_supply']) && !empty($_SESSION['supply_cart'])) {
    $supplier = trim($_POST['supplier_name'] ?? '');

    if ($supplier === '') {
        $msg = "<div class='alert alert-danger'>Error: Supplier name is required.</div>";
    } else {

    try {
        $pdo->beginTransaction();

        // 1. Calculate Total Cost
        $total_cost = 0;
        foreach ($_SESSION['supply_cart'] as $item) {
            $total_cost += $item['line_total'];
        }

        // 2. Save Supply Order Header
        $stmt = $pdo->prepare("INSERT INTO supply_orders (supplier_name, total_cost) VALUES (?, ?)");
        $stmt->execute([$supplier, $total_cost]);
        $order_id = $pdo->lastInsertId();

        // 3. Loop items: Save history AND Increase Stock
        foreach ($_SESSION['supply_cart'] as $item) {
            // Save to history
            $stmt = $pdo->prepare("INSERT INTO supply_items (supply_order_id, product_id, product_name, quantity, buying_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['name'], $item['qty'], $item['price']]);

            // UPDATE STOCK (INCREASE +)
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$item['qty'], $item['id']]);
        }

        $pdo->commit();
        $_SESSION['supply_cart'] = []; // Clear list
        $msg = "<div class='alert alert-success'>Stock Added Successfully! Reference Order #$order_id</div>";

        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Fetch products for dropdown
$products = $pdo->query("SELECT * FROM products")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Stock (Purchase)</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .purchase-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .card-header {
            border-bottom: 1px solid #e5e7eb;
        }
        .card-header.header-purple {
            background-color: #584cf4;
            color: white;
        }
    </style>
</head>
<body>
    <div class="purchase-container">
        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= htmlspecialchars($back_href) ?>" class="btn btn-secondary">&larr; Back</a>
                <h1 class="page-title">Purchase / Add Stock</h1>
            </div>
        </div>

        <?= $msg ?>

        <div class="row g-4">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header header-purple">Incoming Stock Details</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Product</label>
                            <input list="product_list" name="product_id" class="form-control" placeholder="Search..." required>
                            <datalist id="product_list">
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= $p['name'] ?> (Current Stock: <?= $p['quantity'] ?>)</option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cost Price (Per Unit)</label>
                                <input type="number" step="0.01" name="buying_price" class="form-control" placeholder="0.00" min="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="qty" class="form-control" placeholder="Qty" min="1" required>
                            </div>
                        </div>

                        <button type="submit" name="add_to_list" class="btn btn-primary w-100">Add to List</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between">
                    <span>Items to Add</span>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="clear_list" class="btn btn-sm btn-danger">Clear</button>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Cost</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            if (!empty($_SESSION['supply_cart'])): 
                                foreach ($_SESSION['supply_cart'] as $item):
                                    $grand_total += $item['line_total'];
                            ?>
                                <tr>
                                    <td><?= $item['name'] ?></td>
                                    <td>Rs. <?= number_format($item['price'], 2) ?></td>
                                    <td><?= $item['qty'] ?></td>
                                    <td>Rs. <?= number_format($item['line_total'], 2) ?></td>
                                </tr>
                            <?php 
                                endforeach; 
                            endif;
                            ?>
                            <tr class="fw-bold fs-5">
                                <td colspan="3" class="text-end">Total Cost:</td>
                                <td>Rs. <?= number_format($grand_total, 2) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if ($grand_total > 0): ?>
                        <form method="POST" class="mt-4">
                            <label class="fw-bold">Supplier Name / Source:</label>
                            <input type="text" name="supplier_name" class="form-control mb-3" placeholder="e.g. Walmart, Ali Suppliers..." required>
                            
                            <button type="submit" name="save_supply" class="btn btn-success w-100 btn-lg">Finalize & Add to Stock</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>