<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$property_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$rent_date = date('Y-m-d');

$stmt = $conn->prepare("UPDATE properties SET rented_by = ?, rent_date = ? WHERE id = ? AND rented_by IS NULL");
$stmt->bind_param("isi", $user_id, $rent_date, $property_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo "Property rented successfully!";
    header("Location: user_dashboard.php");
} else {
    echo "Error: Unable to rent property. It might have already been rented.";
}
?>
