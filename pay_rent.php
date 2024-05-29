<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rental_id'])) {
    $rental_id = $_POST['rental_id'];

    // Update payment status in rentals table
    $stmt = $conn->prepare("UPDATE rentals SET payment_status = 'Paid' WHERE id = ?");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();

    // Redirect back to user dashboard
    header("Location: user_dashboard.php");
}
?>
