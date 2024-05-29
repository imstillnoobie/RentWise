<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Rent Button
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rent_property_id'])) {
    $property_id = $_POST['rent_property_id'];
    $stmt = $conn->prepare("INSERT INTO rentals (property_id, user_id, rent_date) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $property_id, $user_id);
    $stmt->execute();

    $update_stmt = $conn->prepare("UPDATE properties SET rented_by = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $user_id, $property_id);
    $update_stmt->execute();
}

// Handle Cancel Rent Button
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_rent_id'])) {
    $rental_id = $_POST['cancel_rent_id'];
    $reason = $_POST['cancel_reason'];

    $stmt = $conn->prepare("INSERT INTO cancellation_requests (rental_id, user_id, reason, request_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $rental_id, $user_id, $reason);
    $stmt->execute();
}

// Fetch Available Properties
$stmt = $conn->prepare("SELECT * FROM properties WHERE available = 1 AND rented_by IS NULL");
$stmt->execute();
$available_properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Rented Properties for the current user
$stmt = $conn->prepare("SELECT p.*, r.id AS rental_id, r.payment_status FROM properties p JOIN rentals r ON p.id = r.property_id WHERE r.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rented_properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Payment History
$stmt = $conn->prepare("SELECT p.name, p.class, p.price, r.rent_date, r.payment_date FROM properties p JOIN rentals r ON p.id = r.property_id WHERE r.user_id = ? AND r.payment_status = 'Paid'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_profile = $stmt->get_result()->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .card-body {
            padding: 20px;
        }
        .btn-custom {
            background-color: #17a2b8;
            color: white;
            margin-top: 10px;
        }
        .btn-custom:hover {
            background-color: #138496;
        }
        .navbar {
            margin-bottom: 50px;
        }
        .cancel-form {
            display: none;
            margin-top: 10px;
        }
        .property-title {
            font-size: 1.25rem;
            font-weight: bold;
        }
        .property-subtitle {
            font-size: 1rem;
            font-weight: 500;
        }
        .property-price {
            font-size: 1.5rem;
            color: #28a745;
        }
        .property-description {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .section-title {
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .btn-danger {
            margin-top: 10px;
        }
        .btn-warning {
            margin-top: 10px;
        }
        .navbar-brand img {
            height: 40px; /* Adjust the size as needed */
        }
        .navbar-brand,
        h2,
        h1,
        .nav-pills {
            font-family: 'Oswald', sans-serif;
        }
    </style>
    <script>
        function showCancelForm(id) {
            document.getElementById('cancel-form-' + id).style.display = 'block';
        }
    </script>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">
        <img src="images/logo.png" alt="Logo">
    </a>
    <a class="navbar-brand" href="#">RentWise</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
<div class="container">
    <h1 class="mb-4">User Dashboard</h1>
    
    <!-- User Profile Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="section-title">User Profile</h2>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user_profile['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_profile['email']); ?></p>
            <p><strong>Member Since:</strong> <?php echo htmlspecialchars($user_profile['created_at']); ?></p>
        </div>
    </div>

    <h2 class="section-title">Available Properties</h2>
    <div class="row">
        <?php foreach ($available_properties as $property): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="property-title"><?php echo htmlspecialchars($property['name']); ?></h5>
                        <h6 class="property-subtitle text-muted"><?php echo htmlspecialchars($property['class']); ?></h6>
                        <p class="property-description"><?php echo htmlspecialchars($property['description']); ?></p>
                        <p class="property-price">$<?php echo htmlspecialchars($property['price']); ?></p>
                        <form method="post" style="display:inline;">
                            <button type="submit" class="btn btn-custom" name="rent_property_id" value="<?php echo $property['id']; ?>">
                                <i class="fas fa-sign-in-alt"></i> Rent
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title mt-5">Rented Properties</h2>
    <div class="row">
        <?php foreach ($rented_properties as $property): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="property-title"><?php echo htmlspecialchars($property['name']); ?></h5>
                        <h6 class="property-subtitle text-muted"><?php echo htmlspecialchars($property['class']); ?></h6>
                        <p class="property-description"><?php echo htmlspecialchars($property['description']); ?></p>
                        <p class="property-price">$<?php echo htmlspecialchars($property['price']); ?></p>
                        <p class="card-text">Status: <?php echo htmlspecialchars($property['payment_status']); ?></p>
                        <?php if ($property['payment_status'] == 'Unpaid'): ?>
                            <form method="post" action="pay_rent.php" style="display:inline;">
                            <button type="submit" class="btn btn-custom" name="rental_id" value="<?php echo $property['rental_id']; ?>">
                                       <i class="fas fa-dollar-sign"></i> Pay
                                   </button>
                               </form>
                           <?php endif; ?>
                           <button class="btn btn-danger" onclick="showCancelForm(<?php echo $property['rental_id']; ?>)">
                               <i class="fas fa-times-circle"></i> Cancel Rent
                           </button>
                           <form id="cancel-form-<?php echo $property['rental_id']; ?>" class="cancel-form" method="post">
                               <div class="form-group">
                                   <label for="cancel_reason">Reason for cancellation:</label>
                                   <textarea name="cancel_reason" class="form-control" required></textarea>
                                   <input type="hidden" name="cancel_rent_id" value="<?php echo $property['rental_id']; ?>">
                               </div>
                               <button type="submit" class="btn btn-warning">
                                   <i class="fas fa-paper-plane"></i> Submit Cancellation
                               </button>
                           </form>
                       </div>
                   </div>
               </div>
           <?php endforeach; ?>
       </div>

       <h2 class="section-title mt-5">Payment History</h2>
       <div class="row">
           <?php foreach ($payment_history as $history): ?>
               <div class="col-md-4">
                   <div class="card">
                       <div class="card-body">
                           <h5 class="property-title"><?php echo htmlspecialchars($history['name']); ?></h5>
                           <h6 class="property-subtitle text-muted"><?php echo htmlspecialchars($history['class']); ?></h6>
                           <p class="property-price">$<?php echo htmlspecialchars($history['price']); ?></p>
                           <p class="card-text">Rented on: <?php echo htmlspecialchars($history['rent_date']); ?></p>
                           <p class="card-text">Paid on: <?php echo htmlspecialchars($history['payment_date']); ?></p>
                       </div>
                   </div>
               </div>
           <?php endforeach; ?>
       </div>
   </div>

   <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
   <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
