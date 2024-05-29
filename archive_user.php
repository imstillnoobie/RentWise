<?php
require 'db.php';
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'];
$stmt = $conn->prepare("SELECT active FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$new_status = $user['active'] ? 0 : 1;
$update_stmt = $conn->prepare("UPDATE users SET active = ? WHERE id = ?");
$update_stmt->bind_param("ii", $new_status, $user_id);

if ($update_stmt->execute()) {
    header("Location: admin_dashboard.php");
} else {
    echo "Error: " . $update_stmt->error;
}
?>
