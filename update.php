<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php"); exit();
}
include 'db.php';

$allowed_statuses = ['Pending', 'Ready'];

$order_group = isset($_POST['order_group']) ? (int)$_POST['order_group'] : 0;
$status = $_POST['status'] ?? '';

if (!$order_group || !in_array($status, $allowed_statuses)) {
    header("Location: admin.php"); exit();
}

$stmt = $conn->prepare("UPDATE orders SET status=? WHERE order_group=?");
$stmt->bind_param("si", $status, $order_group);
$stmt->execute();

header("Location: admin.php");
exit();
?>