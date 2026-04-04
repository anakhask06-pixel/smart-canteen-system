<?php
/**
 * Run this file ONCE to create your admin account.
 * Visit: http://localhost/canteen/generate_admin.php
 * Then DELETE this file immediately after use.
 */

include 'db.php';

$username = 'admin';
$plain_password = 'admin123'; // Change this before running!

$hashed = password_hash($plain_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?) ON DUPLICATE KEY UPDATE password=?");
$stmt->bind_param("sss", $username, $hashed, $hashed);

if ($stmt->execute()) {
    echo "✅ Admin account created/updated successfully!<br>";
    echo "Username: <b>$username</b><br>";
    echo "Password: <b>$plain_password</b><br><br>";
    echo "<b style='color:red'>⚠️ DELETE this file now!</b>";
} else {
    echo "❌ Error: " . $conn->error;
}
?>      