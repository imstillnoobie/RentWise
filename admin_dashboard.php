<?php
require 'db.php';
session_start();

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['archive_property_id'])) {
        $property_id = $_POST['archive_property_id'];
        $stmt = $conn->prepare("UPDATE properties SET available = NOT available WHERE id = ?");
        $stmt->bind_param("i", $property_id);
        $stmt->execute();
    }
    if (isset($_POST['archive_user_id'])) {
        $user_id = $_POST['archive_user_id'];
        $stmt = $conn->prepare("UPDATE users SET active = NOT active WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    if (isset($_POST['verify_user_id'])) {
        $user_id = $_POST['verify_user_id'];
        $stmt = $conn->prepare("UPDATE users SET verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    if (isset($_POST['remove_rental_id'])) {
        $rental_id = $_POST['remove_rental_id'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // First, delete from cancellation_requests to avoid foreign key constraint issues
            $stmt = $conn->prepare("DELETE FROM cancellation_requests WHERE rental_id = ?");
            $stmt->bind_param("i", $rental_id);
            $stmt->execute();

            // Then, delete the rental itself
            $stmt = $conn->prepare("DELETE FROM rentals WHERE id = ?");
            $stmt->bind_param("i", $rental_id);
            $stmt->execute();

            // Finally, update properties to set rented_by to NULL
            $stmt = $conn->prepare("UPDATE properties p LEFT JOIN rentals r ON p.id = r.property_id SET p.rented_by = NULL WHERE r.id = ?");
            $stmt->bind_param("i", $rental_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }
    }
    if (isset($_POST['approve_cancel_id'])) {
        $request_id = $_POST['approve_cancel_id'];
        $stmt = $conn->prepare("SELECT rental_id FROM cancellation_requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $rental_id = $row['rental_id'];

            // Start transaction
            $conn->begin_transaction();

            try {
                // Delete from cancellation_requests first to prevent foreign key constraint issues
                $stmt = $conn->prepare("DELETE FROM cancellation_requests WHERE rental_id = ?");
                $stmt->bind_param("i", $rental_id);
                $stmt->execute();

                // Delete from rentals
                $stmt = $conn->prepare("DELETE FROM rentals WHERE id = ?");
                $stmt->bind_param("i", $rental_id);
                $stmt->execute();

                // Update properties to set rented_by to NULL
                $stmt = $conn->prepare("UPDATE properties SET rented_by = NULL WHERE id = (SELECT property_id FROM rentals WHERE id = ?)");
                $stmt->bind_param("i", $rental_id);
                $stmt->execute();

                // Update cancellation request status
                $stmt = $conn->prepare("UPDATE cancellation_requests SET status = 'Approved' WHERE id = ?");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();

                // Commit transaction
                $conn->commit();
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                throw $e;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .navbar {
            margin-bottom: 50px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #343a40;
            color: white;
        }
        .btn-primary, .btn-success, .btn-danger, .btn-warning {
            margin-left: 5px;
        }
        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .list-group-item.available {
            background-color: #d4edda; /* Light green */
        }
        .list-group-item.rented {
            background-color: #f8d7da; /* Light red */
        }
        .btn-custom {
            color: white;
        }
        .btn-custom.available {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-custom.rented {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-custom:hover {
            opacity: 0.85;
        }
        .navbar-brand img {
            height: 40px; /* Adjust the size as needed */
        }
        .navbar-brand,
    h2,
    h3,
    h5,
    .nav-pills {
        font-family: 'Oswald', sans-serif;
    }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">
            <img src="images/logo.png" alt="Logo">
        </a>
        <a class="navbar-brand" href="#">Admin Dashboard</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout <i class="fas fa-sign-out-alt"></i></a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Verify Users</h2>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM users WHERE verified = 0");
                    $stmt->execute();
                    $unverified_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    foreach ($unverified_users as $user) {
                        echo "<li class='list-group-item'>";
                        echo htmlspecialchars($user['username']) . " - " . htmlspecialchars($user['email']);
                        echo "<form method='post' class='d-inline'>";
                        echo "<button type='submit' name='verify_user_id' value='" . $user['id'] . "' class='btn btn-success btn-sm'>Verify <i class='fas fa-check'></i></button>";
                        echo "</form>";
                        echo "</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Manage Properties</h2>
            </div>
            <div class="card-body">
                <h3>Add New Property</h3>
                <form method="post" action="add_property.php">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <input type="text" name="name" placeholder="Name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <textarea name="description" placeholder="Description" class="form-control" required></textarea>
                        </div>
                        <div class="form-group col-md-2">
                            <input type="text" name="price" placeholder="Price" class="form-control" required>
                        </div>
                        <div class="form-group col-md-2">
                            <select name="class" class="form-control" required>
                                <option value="ClassA">ClassA</option>
                                <option value="ClassB">ClassB</option>
                                <option value="ClassC">ClassC</option>
                                <option value="ClassD">ClassD</option>
                                <option value="ClassE">ClassE</option>
                                <option value="ClassF">ClassF</option>
                            </select>
                        </div>
                        <div class="form-group col-md-1">
                            <button type="submit" class="btn btn-primary btn-block">Add <i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <hr>
                <h3>Modify Existing Properties</h3>
                <ul class="list-group">
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM properties");
                    $stmt->execute();
                    $properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    foreach ($properties as $property) {
                        $status_class = $property['rented_by'] ? 'rented' : 'available';
                        echo "<li class='list-group-item $status_class'>";
                        echo "<div>";
                        echo "<h5>" . htmlspecialchars($property['name']) . "</h5>";
                        echo "<p>" . htmlspecialchars($property['description']) . "</p>";
                        echo "<p>Price: $" . htmlspecialchars($property['price']) . " / Class: " . htmlspecialchars($property['class']) . "</p>";
                        echo "</div>";
                        echo "<form method='post' class='d-inline'>";
                        echo "<button type='submit' name='archive_property_id' value='" . $property['id'] . "' class='btn btn-custom $status_class'>" . ($property['available'] ? 'Archive' : 'Unarchive') . " <i class='fas fa-archive'></i></button>";
                        echo "</form>";
                        echo "</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
            <div class="card">
                <div class="card-header">
                    <h2>Rented Properties</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php
                        $stmt = $conn->prepare("SELECT r.id AS rental_id, p.name, p.class, p.price, r.rent_date, u.username, u.email 
                            FROM properties p 
                            JOIN rentals r ON p.id = r.property_id 
                            JOIN users u ON r.user_id = u.id 
                            WHERE p.rented_by IS NOT NULL");
                        $stmt->execute();
                        $rented_properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                        foreach ($rented_properties as $property) {
                            echo "<li class='list-group-item'>";
                            echo htmlspecialchars($property['name']) . " - " . htmlspecialchars($property['class']) . " - $" . htmlspecialchars($property['price']);
                            echo " (Rented on: " . htmlspecialchars($property['rent_date']) . ")";
                            echo " by " . htmlspecialchars($property['username']) . " (" . htmlspecialchars($property['email']) . ")";
                            echo "<form method='post' class='d-inline'><button type='submit' name='remove_rental_id' value='" . $property['rental_id'] . "' class='btn btn-danger btn-sm'>Remove <i class='fas fa-trash'></i></button></form>";
                            echo "</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Manage Users</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM users");
                        $stmt->execute();
                        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                        foreach ($users as $user) {
                            echo "<li class='list-group-item'>";
                            echo htmlspecialchars($user['username']) . " - " . htmlspecialchars($user['email']);
                            if ($user['id'] != $_SESSION['user_id']) {
                                echo "<form method='post' class='d-inline'>";
                                echo "<button type='submit' name='archive_user_id' value='" . $user['id'] . "' class='btn " . ($user['active'] ? "btn-danger" : "btn-success") . " btn-sm'>" . ($user['active'] ? "Archive" : "Activate") . " <i class='" . ($user['active'] ? "fas fa-archive" : "fas fa-check-circle") . "'></i></button>";
                                echo "</form>";
                            }
                            echo "</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Monitor Payments</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php
                        $stmt = $conn->prepare("SELECT rentals.id, properties.name AS property_name, users.username, rentals.payment_status 
                            FROM rentals 
                            JOIN properties ON rentals.property_id = properties.id 
                            JOIN users ON rentals.user_id = users.id");
                        $stmt->execute();
                        $rentals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                        foreach ($rentals as $rental) {
                            echo "<li class='list-group-item'>";
                            echo "Property: " . htmlspecialchars($rental['property_name']) . " - Rented by: " . htmlspecialchars($rental['username']) . " - Payment Status: " . htmlspecialchars($rental['payment_status']);
                            echo "</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Cancellation Requests</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php
                        $stmt = $conn->prepare("SELECT cr.*, p.name, p.class, p.price, u.username, u.email 
                            FROM cancellation_requests cr 
                            JOIN rentals r ON cr.rental_id = r.id 
                            JOIN properties p ON r.property_id = p.id 
                            JOIN users u ON cr.user_id = u.id 
                            WHERE cr.status = 'Pending'");
                        $stmt->execute();
                        $cancellation_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                        foreach ($cancellation_requests as $request) {
                            echo "<li class='list-group-item'>";
                            echo "Property: " . htmlspecialchars($request['name']) . " - Class: " . htmlspecialchars($request['class']) . " - $" . htmlspecialchars($request['price']);
                            echo " (Requested by: " . htmlspecialchars($request['username']) . " - " . htmlspecialchars($request['email']) . ")";
                            echo " Reason: " . htmlspecialchars($request['reason']);
                            echo "<form method='post' class='d-inline'>";
                            echo "<button type='submit' name='approve_cancel_id' value='" . $request['id'] . "' class='btn btn-success btn-sm'>Approve <i class='fas fa-check'></i></button>";
                            echo "</form>";
                            echo "</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
