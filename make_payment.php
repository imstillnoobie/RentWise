<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['property_id']) && isset($_GET['price'])) {
    $property_id = $_GET['property_id'];
    $amount = $_GET['price'];
    $user_id = $_SESSION['user_id'];
    $payment_date = date('Y-m-d');

    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (user_id, property_id, amount, payment_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iids", $user_id, $property_id, $amount, $payment_date);

    if ($stmt->execute()) {
        echo "Payment successful!";
        header("Location: user_dashboard.php");
    } else {
        echo "Error: " . $stmt->error;
    }
} else {
    echo "Invalid request.";
}
?>
