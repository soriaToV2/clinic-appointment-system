<?php
session_start();
require "functions.php";

$studID = $_SESSION['id'];
$firstname = $_SESSION['first_name'];
$lastname = $_SESSION['last_name'];
$status = $_SESSION['status'];


$username = $middlename = $age = $gender = $department = $contactnum = $email = $password = $confirmPassword = '';

$usernameErr = $studIDErr = $firstnameErr = $lastnameErr = $ageErr = $genderErr = $statusErr = $departmentErr = $contactnumErr = $emailErr = $passwordErr = $confirmPasswordErr = $password_match = null;

$registration = null;

if (isset($_POST['signup'])) {
    $username = $_POST["username"];
    $studID = $_POST["studid"];
    $firstname = $_POST["firstName"];
    $middlename = $_POST["middleName"];
    $lastname = $_POST["lastName"];
    $age = $_POST["age"];
    $gender = $_POST["genders"];
    $status = $_POST["status"];
    $department = $_POST["department"];
    $contactnum = $_POST["contactNum"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmPassword"];

    $usernameErr = assignError($username, "Please enter a username", 0);
    $studIDErr = assignError($studID, "Please enter an ID", 1);
    $firstnameErr = assignError($firstname, "Please enter your first name", 0);
    $lastnameErr = assignError($lastname, "Please enter your last name", 0);
    $ageErr = assignError($age, "Please enter your age", 2);
    $genderErr = assignError($gender, "Please select your gender", 0);
    $statusErr = assignError($status, "Please select your status", 0);
    $departmentErr = assignError($department, "Please select your department", 0);
    $contactnumErr = assignError($contactnum, "", 3);
    $emailErr = assignError($email, "Please enter your email", 4);
    $passwordErr = assignError($password, "Please enter your password", 5);
    $confirmPasswordErr = assignError($confirmPassword, "Please confirm your password", 0);

    if ($password != $confirmPassword) {
        $password_match = "Passwords do not match!";
    }
    if (
        empty($usernameErr) && empty($studIDErr) && empty($firstnameErr) && empty($lastnameErr) &&
        empty($ageErr) && empty($genderErr) && empty($statusErr) && empty($departmentErr) && empty($emailErr) && empty($passwordErr) && empty($confirmPasswordErr) && empty($password_match)
    ) {
        $registration = regUserPatient($username, $studID, $firstname, $middlename, $lastname, $age, $gender, $status, $department, $contactnum, $email, $password, "Patient");

        if ($registration === true) {
            $success = "You have successfully created your account!";
        } else {
            $error = $registration;
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

        <form action="signup.php" method="post" class="auth-form">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo $username ?>" class="form-control">
                <?php errors($usernameErr); ?>
            </div>

            <div class="form-group">
                <label>Your ID</label>
                <input type="text" name="studid" value="<?php echo $studID ?>" class="form-control">
                <?php errors($studIDErr); ?>
            </div>

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstName" value="<?php echo $firstname ?>" class="form-control">
                <?php errors($firstnameErr); ?>
            </div>

            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="middleName" value="<?php echo $middlename ?>" class="form-control">
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastName" value="<?php echo $lastname ?>" class="form-control">
                <?php errors($lastnameErr); ?>
            </div>

            <div class="form-group">
                <label>Age</label>
                <input type="number" name="age" value="<?php echo $age ?>" class="form-control">
                <?php errors($ageErr); ?>
            </div>

            <div class="form-group">
                <label>Gender</label>
                <select name="genders" class="form-control">
                    <option value="">Select your Gender</option>
                    <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>MALE</option>
                    <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>FEMALE</option>
                </select>
                <?php errors($genderErr); ?>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">Select your Status</option>
                    <option value="Student" <?php echo ($status == 'Student') ? 'selected' : ''; ?>>STUDENT</option>
                    <option value="Faculty" <?php echo ($status == 'Faculty') ? 'selected' : ''; ?>>FACULTY</option>
                    <option value="School Staffs" <?php echo ($status == 'School Staffs') ? 'selected' : ''; ?>>SCHOOL STAFFS</option>
                </select>
                <?php errors($statusErr); ?>
            </div>

            <div class="form-group">
                <label>Department</label>
                <select name="department" class="form-control">
                    <option value="">Select your Department</option>
                    <option value="CCS" <?php echo ($department == 'CCS') ? 'selected' : ''; ?>>College of Computer Studies (CCS)</option>
                    <option value="CAS" <?php echo ($department == 'CAS') ? 'selected' : ''; ?>>College of Arts and Sciences (CAS)</option>
                    <option value="COED" <?php echo ($department == 'COED') ? 'selected' : ''; ?>>College of Education (COED)</option>
                    <option value="CON" <?php echo ($department == 'CON') ? 'selected' : ''; ?>>College of Nursing (CON)</option>
                    <option value="CIHM" <?php echo ($department == 'CIHM') ? 'selected' : ''; ?>>College of International Hospitality Management (CIHM)</option>
                    <option value="COEN" <?php echo ($department == 'COEN') ? 'selected' : ''; ?>>College of Engineering (COE)</option>
                    <option value="CBA" <?php echo ($department == 'CBA') ? 'selected' : ''; ?>>College of Business and Accountancy (CBA)</option>
                    <option value="STAFFS" <?php echo ($department == 'STAFFS') ? 'selected' : ''; ?>>SCHOOL STAFFS</option>
                </select>
                <?php errors($departmentErr); ?>
            </div>

            <div class="form-group">
                <label>Contact No.</label>
                <input type="text" name="contactNum" value="<?php echo $contactnum ?>" class="form-control">
                <?php errors($contactnumErr); ?>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $email ?>" class="form-control">
                <?php errors($emailErr); ?>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="password" class="form-control">
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('password')"></i>
                </div>
                <?php errors($passwordErr); ?>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="password-container">
                    <input type="password" name="confirmPassword" id="confirmPassword" class="form-control">
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('confirmPassword')"></i>
                </div>
                <?php errors($confirmPasswordErr); ?>
            </div>

            <button type="submit" class="btn login-btn" name="signup">Sign Up</button>

            <div class="auth-footer">
                <p>Already have an account? <a href="index.php">Login</a></p>
            </div>
        </form>

        <?php
        if (isset($success)) {
        ?>
            <script>
                swal({
                    title: "Success",
                    text: "<?php echo $success; ?>",
                    icon: "success",
                    button: "OK"
                }).then(() => {
                    window.location.href = "index.php";
                })
            </script>
        <?php
        }
        if (isset($error)) {
        ?>
            <script>
                swal({
                    title: "Error",
                    text: "<?php echo $error; ?>",
                    icon: "error",
                    button: "OK"
                })
            </script>
        <?php
        }
        ?>

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