<?php
session_start();
require "functions.php";

$idErr = $id = '';
if (isset($_POST['idCheck'])) {
    $id = $_POST["id"];

    if(empty($id)) {
        $idErr = "Please enter an ID";
    }
    $idErr = assignError($id, "Please enter an ID", 1);

    $query = "select * from users where user_id = '$id' ";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $idErr = "You already have an Account! Please login.";
    }

    $query2 = "select * from enrolled_users where university_id = '$id' ";
    $result2 = mysqli_query($con, $query2);
    
    if (mysqli_num_rows($result2) == 0) {
        $idErr = "Your ID is not registered yet! Pls contact clinic admin.";
    }

    if(empty($idErr)) {
        $row = mysqli_fetch_assoc($result2);
        $_SESSION['id'] = $row['university_id'];
        $_SESSION['first_name'] = $row['first_name'];
        $_SESSION['last_name'] = $row['last_name'];
        $_SESSION['status'] = $row['status'];
        header("Location: signup.php");
        exit();
    }

}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Sign Up</title>
    <link rel="stylesheet" href="signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>

<body>
    <div class="auth-container">
        <div class="plp-branding">
            <div class="plp-logo-container">
                <img src="images/PLP LOGO.png" alt="University Logo" class="plp-logo">
            </div>
            <h2 class="school">Pamantasan ng Lungsod ng Pasig</h2>
            <div class="system">Clinic Appointment System</div>
        </div>

        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Sign up for a clinic account</p>
        </div>

        <form action="signupIDcheck.php" method="post" class="auth-form">
            <div class="form-group">
                <label>Enter your ID</label>
                <input type="text" name="id" value="<?php $id?>" class="form-control">

                <?php errors($idErr)?>
            </div>

            <button type="submit" class="btn login-btn" name="idCheck">Enter</button>

            <div class="auth-footer">
                <p>Already have an account? <a href="index.php">Login</a></p>
            </div>
        </form>

    </div>

</body>

</html>