<?php
session_start();
require '../connection/db.php';

$back_href = "../index.php";
if (isset($_SESSION['role'])) {
    $back_href = ($_SESSION['role'] === 'admin') ? '../dashboard_m.php' : '../dashboard_s.php';
}

// ... (KEEP YOUR EXISTING PHP CALCULATION CODE HERE) ...
// Copy the PHP logic from the previous step exactly as it was.
// I am hiding it here to save space, but make sure you keep the
// queries for $inventory_value, $total_sales, $top_sellers, etc.

// --- RE-INSERT PHP DATA LOGIC HERE ---
$stmt = $pdo->query("SELECT SUM(quantity * price) as total_value FROM products");
$inventory_value = $stmt->fetch()['total_value'];

$stmt = $pdo->query("SELECT SUM(total_amount) as total_sales FROM bills");
$total_sales = $stmt->fetch()['total_sales'];

$stmt = $pdo->query("SELECT COUNT(*) as low_count FROM products WHERE quantity < 5");
$low_stock_count = $stmt->fetch()['low_count'];

$low_stock_items = $pdo->query("SELECT * FROM products WHERE quantity < 5")->fetchAll();

$sql = "SELECT product_name, SUM(quantity) as total_sold FROM bill_items GROUP BY product_name ORDER BY total_sold DESC LIMIT 5";
$top_sellers = $pdo->query($sql)->fetchAll();
$seller_names = []; $seller_qtys = [];
foreach ($top_sellers as $s) { $seller_names[] = $s['product_name']; $seller_qtys[] = $s['total_sold']; }

$sql_stock = "SELECT name, quantity FROM products ORDER BY quantity DESC LIMIT 5";
$stock_items = $pdo->query($sql_stock)->fetchAll();
$stock_names = []; $stock_qtys = [];
foreach ($stock_items as $s) { $stock_names[] = $s['name']; $stock_qtys[] = $s['quantity']; }
// -------------------------------------

?>

<!DOCTYPE html>
<html>
<head>
    <title>Analytics Dashboard</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .analytics-container {
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
    <div class="analytics-container">
        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= htmlspecialchars($back_href) ?>" class="btn btn-secondary">&larr; Back</a>
                <h1 class="page-title">Analytics</h1>
            </div>
            <button onclick="generatePDF()" class="btn btn-primary">Download PDF Report</button>
        </div>

        <div class="card">
            <div class="card-header header-purple">Business Analytics</div>
            <div class="card-body">
                <div id="printableArea">
        
        <p class="text-muted text-center">Generated on: <?= date("d M Y") ?></p>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Inventory Value</div>
                    <div class="card-body">
                        <h3 class="card-title">Rs. <?= number_format($inventory_value, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Total Revenue</div>
                    <div class="card-body">
                        <h3 class="card-title">Rs. <?= number_format($total_sales, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-header">Low Stock Alerts</div>
                    <div class="card-body">
                        <h3 class="card-title"><?= $low_stock_count ?> Items</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-danger text-white">
                        <strong>⚠️ Low Stock Warnings</strong>
                    </div>
                    <div class="card-body">
                        <?php if (count($low_stock_items) > 0): ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_items as $item): ?>
                                        <tr>
                                            <td><?= $item['name'] ?></td>
                                            <td class="text-danger fw-bold"><?= $item['quantity'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-success">All stock levels are healthy!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Top 5 Best Sellers</strong></div>
                    <div class="card-body">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Highest Stock Items</strong></div>
                    <div class="card-body">
                        <canvas id="stockChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ... (Keep your existing Chart.js code here) ...
        const ctxSales = document.getElementById('salesChart');
        new Chart(ctxSales, {
            type: 'bar',
            data: {
                labels: <?= json_encode($seller_names) ?>,
                datasets: [{
                    label: '# Sold',
                    data: <?= json_encode($seller_qtys) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderWidth: 1
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });

        const ctxStock = document.getElementById('stockChart');
        new Chart(ctxStock, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($stock_names) ?>,
                datasets: [{
                    label: 'Qty in Stock',
                    data: <?= json_encode($stock_qtys) ?>,
                    backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(255, 206, 86, 0.6)', 'rgba(75, 192, 192, 0.6)', 'rgba(153, 102, 255, 0.6)', 'rgba(255, 159, 64, 0.6)'],
                    borderWidth: 1
                }]
            }
        });
    </script>

    <script>
    function generatePDF() {
        // 1. Select the element you want to print
        const element = document.getElementById('printableArea');

        // 2. Configure the settings
        const opt = {
            margin:       0.5,
            filename:     'Inventory_Report_<?= date("Y-m-d") ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 }, // Higher scale = better resolution
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' } // Landscape fits 3 columns better
        };

        // 3. Generate and Save
        html2pdf().set(opt).from(element).save();
    }
    </script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>