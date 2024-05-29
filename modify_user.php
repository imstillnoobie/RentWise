<?php
require 'db.php';
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $property_id = $_POST['property_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $class = $_POST['class'];
    $available = isset($_POST['available']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE properties SET name = ?, description = ?, price = ?, class = ?, available = ? WHERE id = ?");
    $stmt->bind_param("ssdsii", $name, $description, $price, $class, $available, $property_id);
    $stmt->execute();

    header("Location: admin_dashboard.php");
    exit();
}

// Fetch property details for the given property_id from the database
if (isset($_GET['id'])) {
    $property_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $property = $result->fetch_assoc();

    if (!$property) {
        // Property not found
        header("Location: admin_dashboard.php");
        exit();
    }
} else {
    // If property id is not provided in GET parameter, redirect back to admin dashboard
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Modify Users</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f9;
            font-family: 'Roboto', sans-serif;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            margin-bottom: 20px;
        }
        .btn-custom {
            background-color: #17a2b8;
            color: white;
        }
        .btn-custom:hover {
            background-color: #138496;
        }
        .navbar {
            margin-bottom: 50px;
        }
    </style>
</head>
<body>
    <h1>Modify Property</h1>

    <form method="post">
        <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property['id']); ?>">

        <label>Name:</label><br>
        <input type="text" name="name" value="<?php echo htmlspecialchars($property['name']); ?>" required><br><br>

        <label>Description:</label><br>
        <textarea name="description" rows="5" required><?php echo htmlspecialchars($property['description']); ?></textarea><br><br>

        <label>Price:</label><br>
        <input type="text" name="price" value="<?php echo htmlspecialchars($property['price']); ?>" required><br><br>

        <label>Class:</label><br>
        <select name="class" required>
            <option value="ClassA" <?php if ($property['class'] == 'ClassA') echo 'selected'; ?>>ClassA</option>
            <option value="ClassB" <?php if ($property['class'] == 'ClassB') echo 'selected'; ?>>ClassB</option>
            <option value="ClassC" <?php if ($property['class'] == 'ClassC') echo 'selected'; ?>>ClassC</option>
            <option value="ClassD" <?php if ($property['class'] == 'ClassD') echo 'selected'; ?>>ClassD</option>
            <option value="ClassA2" <?php if ($property['class'] == 'ClassA2') echo 'selected'; ?>>ClassA2</option>
            <option value="ClassB2" <?php if ($property['class'] == 'ClassB2') echo 'selected'; ?>>ClassB2</option>
            <option value="ClassC2" <?php if ($property['class'] == 'ClassC2') echo 'selected'; ?>>ClassC2</option>
            <option value="ClassD2" <?php if ($property['class'] == 'ClassD2') echo 'selected'; ?>>ClassD2</option>
        </select><br><br>

        <label>Available:</label>
        <input type="checkbox" name="available" <?php if ($property['available'] == 1) echo 'checked'; ?>><br><br>

        <button type="submit">Update Property</button>
    </form>

    <br><br>
    <a href="admin_dashboard.php">Back to Dashboard</a>
</body>
</html>
