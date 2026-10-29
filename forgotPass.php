<?php
require "functions.php";

$err = $usernameErr = $emailErr = null;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['sendcode'])) {
    date_default_timezone_set('Asia/Manila');
    $username = $_POST['username'];
    $email = $_POST['email'];

    $usernameErr = assignError($username, "Pls enter your username", 0);
    $emailErr = assignError($email, "Pls enter your email", 0);

    if(!empty($username) && !empty($email)){
        $query = "select * from users where username = '$username' and email = '$email'";
        $result = mysqli_query($con, $query);
        $data = mysqli_fetch_assoc($result);

        if (mysqli_num_rows($result) > 0) {
            session_start();
            $_SESSION['username'] = $data['username'];

            $verificationCode = rand(100000, 999999);
            $_SESSION['verification_code'] = $verificationCode;
            $_SESSION['codeTime'] = time() + 300;
            $expirationTimeFormat = date('F j, Y h:i A', $_SESSION['codeTime']);

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom(MAIL_USERNAME, 'PLP Clinic');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Verification Code';
                $mail->Body = 'Your verification code is: <b>' . $verificationCode . '</b>' . "<br><br>This is only valid for ". $expirationTimeFormat . "<br><br>Please do not share this code with anyone.";

                $mail->send();
                header("Location: requestCode.php");
            } catch (Exception $e) {
                $err = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $err = "USERNAME OR EMAIL DOES NOT EXIST IN OUR SYSTEM";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="forgotpass.css">
</head>

<body>
    <div class="form-container">
        <div class="logo-container">
            <img src="images/PLP LOGO.png" alt="Clinic Logo" class="logo">
        </div>

        <h1 class="form-title">Forgot Password</h1>
        <form action="forgotPass.php" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email">

                <?php
                errors($emailErr);
                ?>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username">

                <?php
                errors($usernameErr);
                ?>
            </div>

            <?php
            errors($err);
            ?>

            <button type="submit" name="sendcode" class="btn">SEND CODE</button>

            <a href="index.php" class="back-link">Back to Login</a>
        </form>
    </div>
</body>

</html>