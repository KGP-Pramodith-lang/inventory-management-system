<?php
session_start();

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../connection/refunds.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

ims_ensure_refunds_table($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../features/refunds.php');
    exit();
}

$billId = (int)($_POST['bill_id'] ?? 0);
$reason = trim((string)($_POST['reason'] ?? ''));

if ($billId < 1) {
    header('Location: ../features/refunds.php?err=' . urlencode('Please select a valid bill.'));
    exit();
}

if ($reason === '' || strlen($reason) < 3) {
    header('Location: ../features/refunds.php?err=' . urlencode('Please enter a refund reason (min 3 characters).'));
    exit();
}

try {
    // Bill must exist.
    $billStmt = $pdo->prepare('SELECT id, total_amount FROM bills WHERE id = ? LIMIT 1');
    $billStmt->execute([$billId]);
    $bill = $billStmt->fetch();
    if (!$bill) {
        header('Location: ../features/refunds.php?err=' . urlencode('Bill not found.'));
        exit();
    }

    // Prevent duplicate pending requests for same bill.
    $dupStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM refunds WHERE bill_id = ? AND status = 'pending'");
    $dupStmt->execute([$billId]);
    $dupCount = (int)($dupStmt->fetch()['c'] ?? 0);
    if ($dupCount > 0) {
        header('Location: ../features/refunds.php?err=' . urlencode('A pending refund request already exists for this bill.'));
        exit();
    }

    $refundAmount = (float)($bill['total_amount'] ?? 0);

    $insert = $pdo->prepare(
        'INSERT INTO refunds (bill_id, refund_amount, reason, status, requested_by, requested_role) VALUES (?, ?, ?, \'pending\', ?, ?)'
    );
    $insert->execute([
        $billId,
        $refundAmount,
        $reason,
        (string)($_SESSION['user'] ?? ''),
        (string)($_SESSION['role'] ?? ''),
    ]);

    header('Location: ../features/refunds.php?ok=' . urlencode('Refund request submitted.'));
    exit();
} catch (Throwable $e) {
    header('Location: ../features/refunds.php?err=' . urlencode('Failed to submit refund request.'));
    exit();
}
