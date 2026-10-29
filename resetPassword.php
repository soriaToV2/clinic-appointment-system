<?php
session_start();

if (empty($_SESSION['username'])) {
    header("location: index.php");
}

require "functions.php";

$err = $newPassErr = $confirmPassErr = null;

if (isset($_POST['resetPass'])) {
    $newPassword = $_POST['newPass'];
    $confirmPassword = $_POST['confirmPass'];

    $newPassErr = assignError($newPassword, "Pls make a new password!", 5);
    $confirmPassErr = assignError($confirmPassword, "Pls input a password!", 0);

    if (empty($newPassErr) && empty($confirmPassErr)) {
        if ($newPassword === $confirmPassword) {
            $username = $_SESSION['username'];
            $hashPass = password_hash($newPassword, PASSWORD_BCRYPT);

            mysqli_begin_transaction($con);
            $query = "update users set password = '$hashPass',  login_attempts = 0 where username = '$username'";
            mysqli_query($con, $query);
            mysqli_commit($con);

            $err = true;
            session_unset();
            session_destroy();
        } else {
            $err = "Passwords do not match.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="forgotpass.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>

<body>
    <div class="form-container">
        <h1 class="form-title">Reset Password</h1>
        <form action="resetPassword.php" method="post">
            <div class="form-group">
                <label for="newPass">New Password:</label>
                <div class="password-container">
                    <input type="password" id="newPass" name="newPass">
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('newPass')"></i>
                </div>
                <?php errors($newPassErr); ?>
            </div>

            <div class="form-group">
                <label for="confirmPass">Confirm Password:</label>
                <div class="password-container">
                    <input type="password" id="confirmPass" name="confirmPass">
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('confirmPass')"></i>
                </div>
                <?php errors($confirmPassErr); ?>
            </div>

            <?php
            if ($err === true) {
            ?>
                <script>
                    swal({
                        title: "SUCCESS",
                        text: "Your password has been reset successfully!",
                        icon: "success",
                        button: "OK"
                    }).then(() => {
                        window.location.href = "index.php";
                    });
                </script>
            <?php
            } else {
                errors($err);
            }
            ?>

            <button type="submit" name="resetPass" class="btn">RESET PASSWORD</button>
            <a href="index.php" class="back-link">Back to Login</a>
        </form>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const icon = passwordField.nextElementSibling;

            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            } else {
                passwordField.type = "password";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            }
        }
    </script>
</body>

</html>