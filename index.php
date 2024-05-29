<?php
require 'db.php';
session_start();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        // Login form submitted
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $loginError = "Username and Password cannot be empty!";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && $password === $user['password']) {
                if ($user['verified'] == 1) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    if ($user['role'] == 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: user_dashboard.php");
                    }
                } else {
                    $loginError = "Account is not verified. Please wait for admin verification.";
                }
            } else {
                $loginError = "Invalid credentials or account is archived!";
            }
        }
    } elseif (isset($_POST['register'])) {
        // Registration form submitted
        $username = $_POST['username'];
        $password = $_POST['password'];
        $email = $_POST['email'];

        if (empty($username) || empty($password) || empty($email)) {
            $registerError = "All fields are required!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, verified) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $username, $password, $email);

            if ($stmt->execute()) {
                $_SESSION['temp_user_id'] = $conn->insert_id;
                header("Location: index.php?set_security_questions=1");
                exit();
            } else {
                $registerError = "Error: " . $stmt->error;
            }
        }
    } elseif (isset($_POST['set_security_questions'])) {
        // Security questions form submitted
        $userId = $_SESSION['temp_user_id'];
        $question1 = $_POST['question1'];
        $answer1 = $_POST['answer1'];
        $question2 = $_POST['question2'];
        $answer2 = $_POST['answer2'];
        $question3 = $_POST['question3'];
        $answer3 = $_POST['answer3'];

        if (empty($question1) || empty($answer1) || empty($question2) || empty($answer2) || empty($question3) || empty($answer3)) {
            $securityError = "All fields are required!";
        } else {
            $stmt = $conn->prepare("UPDATE users SET security_question_1 = ?, security_answer_1 = ?, security_question_2 = ?, security_answer_2 = ?, security_question_3 = ?, security_answer_3 = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $question1, $answer1, $question2, $answer2, $question3, $answer3, $userId);
            if ($stmt->execute()) {
                unset($_SESSION['temp_user_id']);
                $securitySuccess = "Security questions set successfully! Please wait for admin verification.";
            } else {
                $securityError = "Error: " . $stmt->error;
            }
        }
    } elseif (isset($_POST['forgot_password'])) {
        // Forgot password form submitted
        $username = $_POST['username'];

        if (empty($username)) {
            $forgotPasswordError = "Username cannot be empty!";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_user_questions'] = [
                    'question1' => $user['security_question_1'],
                    'question2' => $user['security_question_2'],
                    'question3' => $user['security_question_3']
                ];
                header("Location: index.php?verify_security_questions=1");
                exit();
            } else {
                $forgotPasswordError = "Username not found!";
            }
        }
    } elseif (isset($_POST['verify_security_questions'])) {
        // Verify security questions form submitted
        $userId = $_SESSION['reset_user_id'];
        $answer1 = $_POST['answer1'];
        $answer2 = $_POST['answer2'];
        $answer3 = $_POST['answer3'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND security_answer_1 = ? AND security_answer_2 = ? AND security_answer_3 = ?");
        $stmt->bind_param("isss", $userId, $answer1, $answer2, $answer3);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            header("Location: index.php?reset_password=1");
            exit();
        } else {
            $securityError = "Incorrect answers to security questions!";
        }
    } elseif (isset($_POST['reset_password'])) {
        // Reset password form submitted
        $userId = $_SESSION['reset_user_id'];
        $newPassword = $_POST['new_password'];

        if (empty($newPassword)) {
            $resetPasswordError = "Password cannot be empty!";
        } else {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $newPassword, $userId);
            if ($stmt->execute()) {
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_user_questions']);
                $resetPasswordSuccess = "Password reset successfully! You can now log in.";
            } else {
                $resetPasswordError = "Error: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RentWise</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f9;
            font-family: 'Oswald', sans-serif;
        }
        .container {
            margin-top: 50px;
            max-width: 600px;
        }
        .card {
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #343a40;
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px;
        }
        .btn-custom {
            background-color: #17a2b8;
            color: white;
        }
        .btn-custom:hover {
            background-color: #138496;
        }
        .alert {
            margin-bottom: 20px;
        }
        .nav-link {
            cursor: pointer;
        }
        .navbar-brand img {
            height: 40px; /* Adjust the size as needed */
        }
        .navbar-brand,
    h2,
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
        <a class="navbar-brand" href="#">RentWise</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                </li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <h2 class="text-center">Welcome to RentWise</h2>
        <ul class="nav nav-pills nav-justified mb-3">
            <li class="nav-item">
                <a class="nav-link active" id="login-tab">Login</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="register-tab">Register</a>
            </li>
        </ul>
        <div class="card" id="login-card">
            <div class="card-header text-center">Login</div>
            <?php if (isset($loginError)) echo '<div class="alert alert-danger">' . $loginError . '</div>'; ?>
            <form method="post">
                <div class="form-group">
                    <label for="login-username">Username</label>
                    <input type="text" class="form-control" id="login-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" class="form-control" id="login-password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn btn-custom btn-block">Login</button>
            </form>
            <p class="text-center mt-3"><a href="#" id="forgot-password-link">Forgot Password?</a></p>
        </div>

        <div class="card" id="register-card" style="display: none;">
            <div class="card-header text-center">Register</div>
            <?php if (isset($registerError)) echo '<div class="alert alert-danger">' . $registerError . '</div>'; ?>
            <form method="post">
                <div class="form-group">
                    <label for="register-username">Username</label>
                    <input type="text" class="form-control" id="register-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" class="form-control" id="register-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input type="email" class="form-control" id="register-email" name="email" required>
                </div>
                <button type="submit" name="register" class="btn btn-custom btn-block">Register</button>
            </form>
        </div>

        <?php if (isset($_GET['set_security_questions']) && isset($_SESSION['temp_user_id'])): ?>
        <div class="card">
            <div class="card-header text-center">Set Security Questions</div>
            <?php if (isset($securityError)) echo '<div class="alert alert-danger">' . $securityError . '</div>'; ?>
            <?php if (isset($securitySuccess)) echo '<div class="alert alert-success">' . $securitySuccess . '</div>'; ?>
            <form method="post">
                <div class="form-group">
                    <label for="question1">Security Question 1</label>
                    <input type="text" class="form-control" id="question1" name="question1" required>
                </div>
                <div class="form-group">
                    <label for="answer1">Answer</label>
                    <input type="text" class="form-control" id="answer1" name="answer1" required>
                </div>
                <div class="form-group">
                    <label for="question2">Security Question 2</label>
                    <input type="text" class="form-control" id="question2" name="question2" required>
                </div>
                <div class="form-group">
                    <label for="answer2">Answer</label>
                    <input type="text" class="form-control" id="answer2" name="answer2" required>
                </div>
                <div class="form-group">
                    <label for="question3">Security Question 3</label>
                    <input type="text" class="form-control" id="question3" name="question3" required>
                </div>
                <div class="form-group">
                    <label for="answer3">Answer</label>
                    <input type="text" class="form-control" id="answer3" name="answer3" required>
                </div>
                <button type="submit" name="set_security_questions" class="btn btn-custom btn-block">Submit</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['verify_security_questions']) && isset($_SESSION['reset_user_id'])): ?>
        <div class="card">
            <div class="card-header text-center">Verify Security Questions</div>
            <?php if (isset($securityError)) echo '<div class="alert alert-danger">' . $securityError . '</div>'; ?>
            <form method="post">
                <div class="form-group">
                    <label for="answer1"><?php echo $_SESSION['reset_user_questions']['question1']; ?></label>
                    <input type="text" class="form-control" id="answer1" name="answer1" required>
                </div>
                <div class="form-group">
                    <label for="answer2"><?php echo $_SESSION['reset_user_questions']['question2']; ?></label>
                    <input type="text" class="form-control" id="answer2" name="answer2" required>
                </div>
                <div class="form-group">
                    <label for="answer3"><?php echo $_SESSION['reset_user_questions']['question3']; ?></label>
                    <input type="text" class="form-control" id="answer3" name="answer3" required>
                </div>
                <button type="submit" name="verify_security_questions" class="btn btn-custom btn-block">Submit</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['reset_password']) && isset($_SESSION['reset_user_id'])): ?>
        <div class="card">
            <div class="card-header text-center">Reset Password</div>
            <?php if (isset($resetPasswordError)) echo '<div class="alert alert-danger">' . $resetPasswordError . '</div>'; ?>
            <?php if (isset($resetPasswordSuccess)) echo '<div class="alert alert-success">' . $resetPasswordSuccess . '</div>'; ?>
            <form method="post">
                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" class="form-control" id="new-password" name="new_password" required>
                </div>
                <button type="submit" name="reset_password" class="btn btn-custom btn-block">Reset Password</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card" id="forgot-password-card" style="display: none;">
            <div class="card-header text-center">Forgot Password</div>
            <?php if (isset($forgotPasswordError)) echo '<div class="alert alert-danger">' . $forgotPasswordError . '</div>'; ?>
            <form method="post">
                <div class="form-group">
                    <label for="forgot-username">Username</label>
                    <input type="text" class="form-control" id="forgot-username" name="username" required>
                </div>
                <button type="submit" name="forgot_password" class="btn btn-custom btn-block">Submit</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('login-tab').addEventListener('click', function() {
            document.getElementById('login-card').style.display = 'block';
            document.getElementById('register-card').style.display = 'none';
            document.getElementById('forgot-password-card').style.display = 'none';
            this.classList.add('active');
            document.getElementById('register-tab').classList.remove('active');
        });

        document.getElementById('register-tab').addEventListener('click', function() {
            document.getElementById('login-card').style.display = 'none';
            document.getElementById('register-card').style.display = 'block';
            document.getElementById('forgot-password-card').style.display = 'none';
            this.classList.add('active');
            document.getElementById('login-tab').classList.remove('active');
        });

        document.getElementById('forgot-password-link').addEventListener('click', function() {
            document.getElementById('login-card').style.display = 'none';
            document.getElementById('register-card').style.display = 'none';
            document.getElementById('forgot-password-card').style.display = 'block';
            document.getElementById('login-tab').classList.remove('active');
            document.getElementById('register-tab').classList.remove('active');
        });
    </script>
</body>
</html>
