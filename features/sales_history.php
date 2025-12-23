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
    <title>Sales Bill History</title>
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
                <a href="<?= htmlspecialchars($back_href) ?>" class="btn btn-secondary">&larr; Dashboard</a>
                <h1 class="page-title">Sales History</h1>
            </div>
            <a href="sales.php" class="btn btn-primary">+ New Sale</a>
        </div>

        <div class="card">
            <div class="card-header header-purple">Sales Bill History</div>
            <div class="card-body">
                <div class="accordion" id="billsAccordion">
        <?php
        // 1. Fetch all Bills (Newest first)
        // We join with the bills table to get the main info
        $stmt = $pdo->query("SELECT * FROM bills ORDER BY bill_date DESC");
        $bills = $stmt->fetchAll();

        if (count($bills) == 0) {
            echo "<div class='alert alert-info'>No sales found yet. Go make some money!</div>";
        }

        foreach ($bills as $bill):
            $bill_id = $bill['id'];
            $date = date("d M Y, h:i A", strtotime($bill['bill_date']));
            $customer = $bill['customer_name'] ?? 'Walk-in'; // Default to Walk-in if null
        ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?= $bill_id ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $bill_id ?>">
                        <div class="d-flex w-100 justify-content-between me-3">
                            <span><strong>Bill #<?= $bill_id ?></strong> <span class="text-muted mx-2">|</span> <?= $date ?></span>
                            <span class="text-success fw-bold">$<?= number_format($bill['total_amount'], 2) ?></span>
                        </div>
                    </button>
                </h2>
                
                <div id="collapse<?= $bill_id ?>" class="accordion-collapse collapse" data-bs-parent="#billsAccordion">
                    <div class="accordion-body bg-light">
                        <div class="d-flex justify-content-between">
                            <h5>Customer: <?= $customer ?></h5>
                            <button onclick="window.print()" class="btn btn-sm btn-outline-dark">Print Page</button>
                        </div>
                        <hr>
                        <table class="table table-sm table-bordered bg-white">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Product Name</th>
                                    <th>Unit Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 2. Fetch Items specific to THIS bill ID
                                $item_stmt = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
                                $item_stmt->execute([$bill_id]);
                                
                                while ($item = $item_stmt->fetch()):
                                    $subtotal = $item['price'] * $item['quantity'];
                                ?>
                                    <tr>
                                        <td><?= $item['product_name'] ?></td>
                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>$<?= number_format($subtotal, 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                    <td class="fw-bold">$<?= number_format($bill['total_amount'], 2) ?></td>
                                </tr>
                            </tfoot>
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