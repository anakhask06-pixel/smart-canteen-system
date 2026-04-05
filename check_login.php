<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php"); exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    header("Location: login.php?err=1"); exit();
}

$stmt = $conn->prepare("SELECT * FROM admin WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

// Support both bcrypt hashed and plain text passwords (for legacy accounts)
$passwordOk = password_verify($password, $row['password']) || ($row['password'] === $password);
if ($row && $passwordOk) {
    $_SESSION['admin'] = $username;
    session_regenerate_id(true); // Prevent session fixation
    header("Location: admin.php");
} else {
    header("Location: login.php?err=1");
}
exit();
?>