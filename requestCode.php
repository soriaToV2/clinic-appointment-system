<?php
session_start();

if (empty($_SESSION['username'])) {
    header("location: index.php");
}

require "functions.php";

$enteredCodeErr = $err = null;

if (isset($_POST['verify'])) {
    $enteredCode = $_POST['verifycode'];

    $enteredCodeErr = assignError($enteredCode, "Pls enter the verification code sent via email", 0);

    if (!empty($enteredCode)) {
        if (time() > $_SESSION['codeTime']) {
            $err = "Verification code has expired. Please request a new one.";
        } elseif ($enteredCode != $_SESSION['verification_code']) {
            $err = "Invalid verification code. Please try again.";
        } else {
            header("Location: resetPassword.php");
            exit();
        }
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code</title>
    <link rel="stylesheet" href="forgotpass.css">
</head>

<body>
    <div class="form-container">
        <h1 class="form-title">Verify Code</h1>
        <form action="requestCode.php" method="post">
            <div class="form-group">
                <label for="verifycode">Enter Verification Code:</label>
                <input type="text" id="verifycode" name="verifycode">

                <?php
                errors($enteredCodeErr);
                ?>
            </div>

            <?php
            errors($err);
            ?>

            <button type="submit" name="verify" class="btn">VERIFY CODE</button>

            <a href="forgotPass.php" class="back-link">Back to Forgot Password</a>
        </form>
    </div>
</body>

</html>