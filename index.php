<?php
session_start();

// If user is NOT logged in → go to login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// If logged in → redirect based on role
if ($_SESSION['role'] === 'admin') {
    header("Location: dashboard_m.php");
} else {
    header("Location: dashboard_s.php");
}
exit();
?>