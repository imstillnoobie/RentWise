<?php
require 'db.php';
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $class = $_POST['class'];
    $available = isset($_POST['available']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO properties (name, description, price, class, available) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsi", $name, $description, $price, $class, $available);

    if ($stmt->execute()) {
        echo "Property added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<form method="post">
    Name: <input type="text" name="name"><br>
    Description: <textarea name="description"></textarea><br>
    Price: <input type="text" name="price"><br>
    Class: 
    <select name="class">
        <option value="ClassA">ClassA</option>
        <option value="ClassB">ClassB</option>
        <option value="ClassC">ClassC</option>
        <option value="ClassD">ClassD</option>
        <option value="ClassA2">ClassA2</option>
        <option value="ClassB2">ClassB2</option>
        <option value="ClassC2">ClassC2</option>
        <option value="ClassD2">ClassD2</option>
    </select><br>
    Available: <input type="checkbox" name="available"><br>
    <button type="submit">Add Property</button>
</form>
