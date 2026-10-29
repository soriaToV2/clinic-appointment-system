<?php
session_start();
require "functions.php";

$usernameErr = null;
$passwordErr = null;
$loginErr = null;

if (isset($_POST['login'])) {

    $username = $_POST["username"];
    $password = $_POST["password"];

    if (!empty($username) && !empty($password)) {
        $login = loginUser($username, $password);

        $loginErr = $login;

        if ($login == 'Clinic Staff') {
            header("Location: admin/admin.php");
            exit();
        } elseif ($login == 'Patient') {
            header("Location: patient/patient.php");
            exit();
        }
    } else {
        if (empty($username) && empty($password)) {
            $loginErr = "Pls Enter Both Fields";
        } elseif (empty($password)) {
            $passwordErr = "Password is Empty!";
        } elseif (empty($username)) {
            $usernameErr = "Username is Empty!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Clinic Appointment System</title>
    <style>
        html,
        body {
            overflow: hidden;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="plp-branding">
            <div class="plp-logo-container">
                <img src="images/PLP LOGO.png" alt="PLP Logo" class="plp-logo">
            </div>
            <h2 class="school-name">Pamantasan ng Lungsod ng Pasig</h2>
            <div class="system-name">Clinic Appointment System</div>
        </div>

        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Login to your clinic account</p>
        </div>

        <form id="loginform" class="auth-form" action="index.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" class="form-control">
                <?php errors($usernameErr); ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" placeholder="Enter your password" class="form-control">
                    <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
                </div>
                <?php errors($passwordErr); ?>
            </div>

            <?php errors($loginErr); ?>

            <button type="submit" name="login" class="btn login-btn">Sign In</button>

            <div class="auth-footer">
                <p>Don't have an account? <a href="signupIDcheck.php">Register</a></p>
                <a href="forgotPass.php" class="forgot-password">Forgot password?</a>
            </div>
        </form>
    </div>
</body>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            // Toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // Toggle the eye icon
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    });
</script>

</html>