<?php
session_start();

require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../connection/refunds.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../features/refunds.php?err=' . urlencode('Only admins can approve or reject refunds.'));
    exit();
}

ims_ensure_refunds_table($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../features/refunds.php');
    exit();
}

$refundId = (int)($_POST['refund_id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$note = trim((string)($_POST['note'] ?? ''));

if ($refundId < 1) {
    header('Location: ../features/refunds.php?err=' . urlencode('Invalid refund request.'));
    exit();
}

if (!in_array($action, ['approve', 'reject'], true)) {
    header('Location: ../features/refunds.php?err=' . urlencode('Invalid action.'));
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM refunds WHERE id = ? FOR UPDATE');
    $stmt->execute([$refundId]);
    $refund = $stmt->fetch();

    if (!$refund) {
        $pdo->rollBack();
        header('Location: ../features/refunds.php?err=' . urlencode('Refund request not found.'));
        exit();
    }

    if (($refund['status'] ?? '') !== 'pending') {
        $pdo->rollBack();
        header('Location: ../features/refunds.php?err=' . urlencode('This refund request is already processed.'));
        exit();
    }

    if ($action === 'approve') {
        $billId = (int)($refund['bill_id'] ?? 0);

        // Restock all items from the bill.
        $itemsStmt = $pdo->prepare('SELECT product_id, quantity FROM bill_items WHERE bill_id = ?');
        $itemsStmt->execute([$billId]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as $it) {
            $productId = (int)($it['product_id'] ?? 0);
            $qty = (int)($it['quantity'] ?? 0);
            if ($productId > 0 && $qty > 0) {
                $up = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
                $up->execute([$qty, $productId]);
            }
        }

        $upd = $pdo->prepare(
            "UPDATE refunds
             SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_note = ?
             WHERE id = ?"
        );
        $upd->execute([
            (string)($_SESSION['user'] ?? ''),
            ($note === '' ? null : $note),
            $refundId,
        ]);

        $pdo->commit();
        header('Location: ../features/refunds.php?ok=' . urlencode('Refund approved and stock restored.'));
        exit();
    }

    // reject
    $upd = $pdo->prepare(
        "UPDATE refunds
         SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_note = ?
         WHERE id = ?"
    );
    $upd->execute([
        (string)($_SESSION['user'] ?? ''),
        ($note === '' ? null : $note),
        $refundId,
    ]);

    $pdo->commit();
    header('Location: ../features/refunds.php?ok=' . urlencode('Refund rejected.'));
    exit();
} catch (Throwable $e) {
    try {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    } catch (Throwable $e2) {
        // ignore
    }
    header('Location: ../features/refunds.php?err=' . urlencode('Failed to process refund request.'));
    exit();
}
