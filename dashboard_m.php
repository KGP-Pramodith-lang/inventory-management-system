<?php
session_start();

require_once __DIR__ . '/connection/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Dashboard metrics
$lowStockThreshold = 5;

try {
  $stmt = $pdo->query("SELECT COUNT(*) AS c FROM products");
  $total_products = (int)($stmt->fetch()['c'] ?? 0);

  $stmt = $pdo->query("SELECT COALESCE(SUM(quantity * price), 0) AS total_value FROM products");
  $inventory_value = (float)($stmt->fetch()['total_value'] ?? 0);

  $stmt = $pdo->query("SELECT COUNT(*) AS c FROM products WHERE quantity < {$lowStockThreshold}");
  $low_stock_count = (int)($stmt->fetch()['c'] ?? 0);

  $stmt = $pdo->query("SELECT name, sku, quantity FROM products WHERE quantity < {$lowStockThreshold} ORDER BY quantity ASC");
  $low_stock_items = $stmt->fetchAll();

  $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total_sales FROM bills WHERE DATE(bill_date) = CURDATE()");
  $todays_sales = (float)($stmt->fetch()['total_sales'] ?? 0);
} catch (Throwable $e) {
  $total_products = 0;
  $inventory_value = 0;
  $low_stock_count = 0;
  $low_stock_items = [];
  $todays_sales = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Main Menu - Inventory System</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="css/style.css">
  </head>

  <body class="container mt-5">
    <a href="settings.php" class="settings-btn" title="Settings">
      <i class="bi bi-gear"></i>
    </a>
    <a href="logout.php" class="logout-btn" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
    </a>
    <button type="button" class="chat-ai-btn" id="chatAiToggle" title="Chat with AI" aria-label="Chat with AI" aria-expanded="false">
      <i class="bi bi-chat-dots"></i>
    </button>

    <div class="text-center mb-5">
      <h2 class="fw-bold">Manager Dashboard</h2>
      <p class="text-muted">Select an option below to proceed</p>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12 col-md-6">
        <div class="stat-card p-4 d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted">Total Products</div>
            <div class="fw-semibold fs-4 text-primary"><?php echo number_format($total_products); ?></div>
          </div>
          <div class="stat-icon bg-primary-subtle text-primary">
            <i class="bi bi-box"></i>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="stat-card p-4 d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted">Low Stock Items</div>
            <div class="fw-semibold fs-4 text-danger"><?php echo number_format($low_stock_count); ?></div>
          </div>
          <div class="stat-icon bg-danger-subtle text-danger">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="stat-card p-4 d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted">Today's Sales</div>
            <div class="fw-semibold fs-4 text-success">Rs. <?php echo number_format($todays_sales, 2); ?></div>
          </div>
          <div class="stat-icon bg-success-subtle text-success">
            <i class="bi bi-currency-dollar"></i>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="stat-card p-4 d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted">Inventory Value</div>
            <div class="fw-semibold fs-4" style="color:#584cf4">Rs. <?php echo number_format($inventory_value, 2); ?></div>
          </div>
          <div class="stat-icon" style="background: rgba(88, 76, 244, 0.12); color:#584cf4">
            <i class="bi bi-graph-up"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="section-card p-4 mb-5">
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-triangle text-danger"></i>
        <h5 class="mb-0">Stock Alerts</h5>
      </div>

      <?php if (count($low_stock_items) === 0): ?>
        <div class="alert alert-success mb-0">All stock levels are healthy.</div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($low_stock_items as $item): ?>
            <div class="p-3 rounded-3 border border-danger-subtle bg-danger-subtle d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="text-muted">SKU: <?php echo htmlspecialchars($item['sku']); ?></div>
              </div>
              <div class="text-end">
                <div class="text-danger fw-semibold">Stock: <?php echo htmlspecialchars($item['quantity']); ?></div>
                <div class="text-muted">Reorder Level: <?php echo htmlspecialchars((string)$lowStockThreshold); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="row g-4 justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm menu-card">
          <div
            class="card-body text-center d-flex flex-column justify-content-center"
          >
            <h5 class="card-title mb-3">Point Of Sales</h5>
            <p class="text-muted small">Sell items to customers</p>
            <a href="features/sales.php" class="btn btn-outline-primary w-100 menu-btn mt-auto">
              💳 Go to POS
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm menu-card">
          <div
            class="card-body text-center d-flex flex-column justify-content-center"
          >
            <h5 class="card-title mb-3">Current Inventory</h5>
            <p class="text-muted small">View & Edit Stock Levels</p>
            <a
              href="features/inventory.php"
              class="btn btn-outline-primary w-100 menu-btn mt-auto"
            >
              📦 View Dashboard
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm menu-card">
          <div
            class="card-body text-center d-flex flex-column justify-content-center"
          >
            <h5 class="card-title mb-3">Purchase Stock</h5>
            <p class="text-muted small">Add items from Suppliers</p>
            <a
              href="features/purchase.php"
              class="btn btn-outline-primary w-100 menu-btn mt-auto"
            >
              🧾 Add New Stock
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm menu-card">
          <div
            class="card-body text-center d-flex flex-column justify-content-center"
          >
            <h5 class="card-title mb-3">Purchase History</h5>
            <p class="text-muted small">View past supplier orders</p>
            <a
              href="features/purchase_history.php"
              class="btn btn-outline-primary w-100 menu-btn mt-auto"
            >
              📚 Supplier Records
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm menu-card">
          <div
            class="card-body text-center d-flex flex-column justify-content-center"
          >
            <h5 class="card-title mb-3">Sales History</h5>
            <p class="text-muted small">View past customer bills</p>
            <a
              href="features/sales_history.php"
              class="btn btn-outline-primary w-100 menu-btn mt-auto"
            >
              📜 Sales Records
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm menu-card">
          <div
            class="card-body text-center d-flex flex-column justify-content-center"
          >
            <h5 class="card-title mb-3">Analytics</h5>
            <p class="text-muted small">Charts & Reports</p>
            <a
              href="features/analytics.php"
              class="btn btn-outline-primary w-100 menu-btn mt-auto"
            >
              📊 View Analytics
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm menu-card">
          <div
            class="card-body text-center d-flex flex-column justify-content-center"
          >
            <h5 class="card-title mb-3">Refund Management</h5>
            <p class="text-muted small">Manage refund requests</p>
            <a
                href="features/refunds.php"
              class="btn btn-outline-primary w-100 menu-btn mt-auto"
            >
              📜 Refund Management
            </a>
          </div>
        </div>
      </div>

    </div>

    <?php require_once __DIR__ . '/partials/ai_chat_widget.php'; ?>
    <?php require_once __DIR__ . '/partials/footer.php'; ?>
  </body>
</html>
