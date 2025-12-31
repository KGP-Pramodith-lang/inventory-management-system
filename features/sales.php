<?php
session_start(); // Start the session to store the "Cart/Bill"
require '../connection/db.php';

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$msg = "";

$back_href = "../index.php";
if (isset($_SESSION['role'])) {
    $back_href = ($_SESSION['role'] === 'admin') ? '../dashboard_m.php' : '../dashboard_s.php';
}

// --- LOGIC 1: ADD ITEM TO BILL ---
if (isset($_POST['add_to_bill'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 0);

    if ($product_id <= 0 || $qty <= 0) {
        $msg = "<div class='alert alert-danger'>Error: Please select a valid product and quantity.</div>";
    } else {

        // Fetch product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            if ($qty > (int)$product['quantity']) {
                $msg = "<div class='alert alert-danger'>Error: Not enough stock! You requested <b>" . htmlspecialchars((string)$qty) . "</b>, but only <b>" . htmlspecialchars((string)$product['quantity']) . "</b> are available.</div>";
            } else {
                $line_total = (float)$product['price'] * $qty;
                
                $_SESSION['cart'][] = [
                    'id' => (int)$product['id'],
                    'name' => $product['name'],
                    'price' => (float)$product['price'],
                    'qty' => $qty,
                    'line_total' => $line_total
                ];
            }
        } else {
            $msg = "<div class='alert alert-danger'>Error: Selected product not found.</div>";
        }
    }
}

// --- LOGIC 2: CLEAR BILL ---
if (isset($_POST['clear_bill'])) {
    $_SESSION['cart'] = [];
}

// --- LOGIC 3: CHECKOUT (FINALISE SALE & SAVE BILL) ---
if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    
    try {
        // Start a transaction (Ensures all data is saved, or none of it is)
        $pdo->beginTransaction();

        // 1. Calculate Grand Total
        $grand_total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $grand_total += $item['line_total'];
        }

        // 2. Insert into BILLS table
        $stmt = $pdo->prepare("INSERT INTO bills (total_amount) VALUES (?)");
        $stmt->execute([$grand_total]);
        $bill_id = $pdo->lastInsertId(); // Get the ID of the bill we just created

        // 3. Loop through cart to save items and update stock
        foreach ($_SESSION['cart'] as $item) {
            // Save into bill_items
            $stmt = $pdo->prepare("INSERT INTO bill_items (bill_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$bill_id, $item['id'], $item['name'], $item['qty'], $item['price']]);

            // Update Product Stock
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$item['qty'], $item['id']]);
        }

        // Commit the transaction
        $pdo->commit();

        // Clear cart
        $_SESSION['cart'] = [];
        $msg = "<div class='alert alert-success'>Bill #$bill_id Saved Successfully!</div>";

    } catch (Exception $e) {
        $pdo->rollBack(); // Undo changes if something went wrong
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch all products for the search dropdown
$products = $pdo->query("SELECT * FROM products")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales / Billing</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .sales-container {
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
    <div class="sales-container">
        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= htmlspecialchars($back_href) ?>" class="btn btn-secondary">&larr; Back</a>
                <h1 class="page-title">Sales / Billing</h1>
            </div>
        </div>

        <?= $msg ?>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header header-purple">Select Item</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Search Product</label>
                                <input list="product_list" name="product_id" class="form-control" placeholder="Type to search..." required>
                                <datalist id="product_list">
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= $p['name'] ?> - Rs. <?= $p['price'] ?> (Stock: <?= $p['quantity'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="qty" class="form-control" value="1" min="1" required>
                            </div>

                            <button type="submit" name="add_to_bill" class="btn btn-success w-100">Add to Bill</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <span>Current Bill</span>
                        <form method="POST" style="display:inline;">
                            <button type="submit" name="clear_bill" class="btn btn-sm btn-danger">Clear</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $grand_total = 0;
                                if (!empty($_SESSION['cart'])): 
                                    foreach ($_SESSION['cart'] as $item):
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
                                    <td colspan="3" class="text-end">Grand Total:</td>
                                    <td>Rs. <?= number_format($grand_total, 2) ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <?php if ($grand_total > 0): ?>
                            <form method="POST">
                                <button type="submit" name="checkout" class="btn btn-primary w-100 btn-lg">Complete Sale (Print Bill)</button>
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