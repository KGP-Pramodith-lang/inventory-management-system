<?php
session_start();

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../connection/refunds.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

ims_ensure_refunds_table($pdo);

$role = (string)($_SESSION['role'] ?? '');
$isAdmin = $role === 'admin';

$back_href = "../index.php";
if ($role !== '') {
    $back_href = $isAdmin ? '../dashboard_m.php' : '../dashboard_s.php';
}

$ok = (string)($_GET['ok'] ?? '');
$err = (string)($_GET['err'] ?? '');

// Bills for request form (recent first)
$bills = [];
try {
    $bills = $pdo->query('SELECT id, customer_name, total_amount, bill_date FROM bills ORDER BY bill_date DESC LIMIT 100')->fetchAll();
} catch (Throwable $e) {
    $bills = [];
}

// Refund list (admins see all; staff see their own)
$refunds = [];
try {
    if ($isAdmin) {
        $stmt = $pdo->query(
            "SELECT r.*, b.bill_date, b.customer_name
             FROM refunds r
             JOIN bills b ON b.id = r.bill_id
             ORDER BY r.requested_at DESC"
        );
        $refunds = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare(
            "SELECT r.*, b.bill_date, b.customer_name
             FROM refunds r
             JOIN bills b ON b.id = r.bill_id
             WHERE r.requested_by = ?
             ORDER BY r.requested_at DESC"
        );
        $stmt->execute([(string)($_SESSION['user'] ?? '')]);
        $refunds = $stmt->fetchAll();
    }
} catch (Throwable $e) {
    $refunds = [];
}

function badge_class(string $status): string
{
    $status = strtolower(trim($status));
    if ($status === 'approved') return 'text-bg-success';
    if ($status === 'rejected') return 'text-bg-danger';
    return 'text-bg-warning';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Refund Management</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .page-container {
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
    <div class="page-container">
        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= htmlspecialchars($back_href) ?>" class="btn btn-secondary">&larr; Dashboard</a>
                <h1 class="page-title">Refund Management</h1>
            </div>
            <a href="sales_history.php" class="btn btn-outline-primary">Sales History</a>
        </div>

        <?php if ($ok): ?>
            <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-header header-purple">Create Refund Request</div>
                    <div class="card-body">
                        <form method="POST" action="../logics/refund_request.php">
                            <div class="mb-3">
                                <label class="form-label">Select Bill</label>
                                <select name="bill_id" class="form-select" required>
                                    <option value="" selected disabled>Choose bill...</option>
                                    <?php foreach ($bills as $b): ?>
                                        <option value="<?= (int)$b['id'] ?>">
                                            Bill #<?= (int)$b['id'] ?> — <?= date('d M Y, h:i A', strtotime((string)$b['bill_date'])) ?> — Rs. <?= number_format((float)$b['total_amount'], 2) ?>
                                            <?= ($b['customer_name'] ?? '') ? (' — ' . $b['customer_name']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <textarea name="reason" class="form-control" rows="4" placeholder="Explain why this refund is needed" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-success w-100">Submit Refund Request</button>
                            <div class="form-text mt-2">
                                This creates a full-refund request for the selected bill.
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <span>Refund Requests</span>
                        <span class="badge text-bg-light text-dark"><?= $isAdmin ? 'All requests' : 'My requests' ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!$refunds): ?>
                            <div class="alert alert-info mb-0">No refund requests found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Bill</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Requested</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($refunds as $r): ?>
                                            <?php
                                                $status = (string)($r['status'] ?? 'pending');
                                                $requestedAt = (string)($r['requested_at'] ?? '');
                                                $billDate = (string)($r['bill_date'] ?? '');
                                                $reason = (string)($r['reason'] ?? '');
                                                $reviewNote = (string)($r['review_note'] ?? '');
                                            ?>
                                            <tr>
                                                <td>#<?= (int)$r['id'] ?></td>
                                                <td>
                                                    <div class="fw-semibold">Bill #<?= (int)$r['bill_id'] ?></div>
                                                    <div class="text-muted small"><?= $billDate ? date('d M Y, h:i A', strtotime($billDate)) : '' ?></div>
                                                    <?php if (!empty($r['customer_name'])): ?>
                                                        <div class="text-muted small">Customer: <?= htmlspecialchars((string)$r['customer_name']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-semibold">Rs. <?= number_format((float)($r['refund_amount'] ?? 0), 2) ?></td>
                                                <td>
                                                    <span class="badge <?= badge_class($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                                                    <?php if ($reviewNote): ?>
                                                        <div class="text-muted small mt-1">Note: <?= htmlspecialchars($reviewNote) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <?= $requestedAt ? date('d M Y, h:i A', strtotime($requestedAt)) : '' ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        by <?= htmlspecialchars((string)($r['requested_by'] ?? '')) ?>
                                                        <?php if (!empty($r['requested_role'])): ?>
                                                            (<?= htmlspecialchars((string)$r['requested_role']) ?>)
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="small mt-2">
                                                        <div class="text-muted">Reason</div>
                                                        <div><?= nl2br(htmlspecialchars($reason)) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($isAdmin && strtolower($status) === 'pending'): ?>
                                                        <form method="POST" action="../logics/refund_action.php" class="d-grid gap-2">
                                                            <input type="hidden" name="refund_id" value="<?= (int)$r['id'] ?>" />
                                                            <input type="text" class="form-control form-control-sm" name="note" placeholder="Optional note" />
                                                            <button class="btn btn-sm btn-success" name="action" value="approve">Approve</button>
                                                            <button class="btn btn-sm btn-outline-danger" name="action" value="reject">Reject</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <?php if (!empty($r['reviewed_by']) || !empty($r['reviewed_at'])): ?>
                                                            <div class="text-muted small">
                                                                Reviewed by <?= htmlspecialchars((string)($r['reviewed_by'] ?? '')) ?>
                                                                <?php if (!empty($r['reviewed_at'])): ?>
                                                                    on <?= date('d M Y, h:i A', strtotime((string)$r['reviewed_at'])) ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="text-muted small">No actions available.</div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php require_once __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
