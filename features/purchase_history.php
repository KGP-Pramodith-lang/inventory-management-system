<?php
session_start();
require '../connection/db.php';

$back_href = "../index.php";
if (isset($_SESSION['role'])) {
    $back_href = ($_SESSION['role'] === 'admin') ? '../dashboard_m.php' : '../dashboard_s.php';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Purchase History</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .history-container {
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
    <div class="history-container">
        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= htmlspecialchars($back_href) ?>" class="btn btn-secondary">&larr; Back</a>
                <h1 class="page-title">Purchase History</h1>
            </div>
            <a href="purchase.php" class="btn btn-success">+ Add New Stock</a>
        </div>

        <div class="card">
            <div class="card-header header-purple">Purchase (In-Stock) History</div>
            <div class="card-body">
                <div class="accordion" id="supplyAccordion">
        <?php
        // Fetch Supply Orders
        $stmt = $pdo->query("SELECT * FROM supply_orders ORDER BY order_date DESC");
        $orders = $stmt->fetchAll();

        foreach ($orders as $order):
            $id = $order['id'];
            $date = date("d M Y", strtotime($order['order_date']));
        ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?= $id ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $id ?>">
                        <strong>Order #<?= $id ?></strong> &nbsp;|&nbsp; <?= $order['supplier_name'] ?> &nbsp;|&nbsp; <span class="text-danger">Cost: $<?= $order['total_cost'] ?></span>
                    </button>
                </h2>
                <div id="collapse<?= $id ?>" class="accordion-collapse collapse" data-bs-parent="#supplyAccordion">
                    <div class="accordion-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Cost Price</th>
                                    <th>Qty Added</th>
                                    <th>Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $item_stmt = $pdo->prepare("SELECT * FROM supply_items WHERE supply_order_id = ?");
                                $item_stmt->execute([$id]);
                                while ($item = $item_stmt->fetch()):
                                ?>
                                    <tr>
                                        <td><?= $item['product_name'] ?></td>
                                        <td>$<?= $item['buying_price'] ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>$<?= number_format($item['buying_price'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>