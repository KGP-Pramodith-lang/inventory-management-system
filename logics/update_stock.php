<?php
require 'db.php';

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == 'add') {
        $sql = "UPDATE products SET quantity = quantity + 1 WHERE id = ?";
    } elseif ($action == 'remove') {
        // Ensure we don't go below zero
        $sql = "UPDATE products SET quantity = GREATEST(0, quantity - 1) WHERE id = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}

// Send them back to the dashboard
header("Location: index.php");
exit();
?>