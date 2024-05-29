<?php
require 'db.php';
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$property_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $class = $_POST['class'];
    $available = isset($_POST['available']) ? 1 : 0;

    $update_stmt = $conn->prepare("UPDATE properties SET name = ?, description = ?, price = ?, class = ?, available = ? WHERE id = ?");
    $update_stmt->bind_param("ssdsii", $name, $description, $price, $class, $available, $property_id);

    if ($update_stmt->execute()) {
        header("Location: admin_dashboard.php");
    } else {
        echo "Error: " . $update_stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Modify Properties</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
        .form-control-label {
            font-weight: bold;
        }
        .form-control {
            border-radius: 0;
        }
        .btn-submit {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .btn-submit:hover {
            background-color: #138496;
        }
        .form-check-label {
            font-weight: normal;
        }
        .back-link {
            color: #17a2b8;
        }
        .back-link:hover {
            text-decoration: none;
            color: #138496;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Modify Property</h1>

        <form method="post">
            <div class="form-group">
                <label for="name" class="form-control-label">Name:</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-signature"></i></span>
                    </div>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($property['name']); ?>" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label for="description" class="form-control-label">Description:</label>
                <textarea id="description" name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($property['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="price" class="form-control-label">Price:</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                    </div>
                    <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($property['price']); ?>" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label for="class" class="form-control-label">Class:</label>
                <select id="class" name="class" class="form-control" required>
                    <option value="ClassA" <?php if ($property['class'] == 'ClassA') echo 'selected'; ?>>ClassA</option>
                    <option value="ClassB" <?php if ($property['class'] == 'ClassB') echo 'selected'; ?>>ClassB</option>
                    <option value="ClassC" <?php if ($property['class'] == 'ClassC') echo 'selected'; ?>>ClassC</option>
                    <option value="ClassD" <?php if ($property['class'] == 'ClassD') echo 'selected'; ?>>ClassD</option>
                    <option value="ClassA2" <?php if ($property['class'] == 'ClassA2') echo 'selected'; ?>>ClassA2</option>
                    <option value="ClassB2" <?php if ($property['class'] == 'ClassB2') echo 'selected'; ?>>ClassB2</option>
                    <option value="ClassC2" <?php if ($property['class'] == 'ClassC2') echo 'selected'; ?>>ClassC2</option>
                    <option value="ClassD2" <?php if ($property['class'] == 'ClassD2') echo 'selected'; ?>>ClassD2</option>
                </select>
            </div>
            <div class="form-check">
                <input type="checkbox" id="available" name="available" class="form-check-input" <?php echo $property['available'] ? 'checked' : ''; ?>>
                <label for="available" class="form-check-label">Available</label>
            </div>
            <br>
            <button type="submit" class="btn btn-submit">Modify Property <i class="fas fa-edit ml-1"></i></button>
        </form>

        <br><br>
        <a href="admin_dashboard.php" class="back-link"><i class="fas fa-chevron-left mr-1"></i> Back to Dashboard</a>
    </div>

    <!-- Bootstrap and Font Awesome JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
</body>
</html>
