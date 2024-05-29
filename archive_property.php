<?php
require 'db.php';
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$property_id = $_GET['id'];
$stmt = $conn->prepare("SELECT available FROM properties WHERE id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();

$new_status = $property['available'] ? 0 : 1;
$update_stmt = $conn->prepare("UPDATE properties SET available = ? WHERE id = ?");
$update_stmt->bind_param("ii", $new_status, $property_id);

if ($update_stmt->execute()) {
    header("Location: admin_dashboard.php");
} else {
    echo "Error: " . $update_stmt->error;
}
?>
