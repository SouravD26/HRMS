<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_name = $stmt->get_result()->fetch_assoc()['name'] ?? 'Admin';
$stmt->close();

// Same dashboard as super admin; sections and links are limited by this admin's rights
include('_dashboard_view.php');
