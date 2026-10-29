<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

include "nav.php";
require "../functions.php";

$patientID = $_SESSION['user_id'];
$query = "select * from patients where patient_id = '$patientID'";
$result = mysqli_query($con, $query);
$patientData = mysqli_fetch_assoc($result);

$patientData['age'];

$firstnameErr = $lastnameErr = $ageErr = $genderErr = $contactnoErr = $departmentErr = $statusErr = $genErr = "";
if (isset($_POST['submit'])) {
    $firstname = $_POST['firstName'];
    $lastname = $_POST['lastName'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $contactno = $_POST['contactNum'];
    $department = $_POST['department'];
    $status = $_POST['status'];

    $firstnameErr = assignError($firstname, "Please enter your first name", 0);
    if (empty($firstnameErr) && preg_match('/[0-9]/', $firstname)) {
        $firstnameErr = "First name cannot contain numbers";
    }

    $lastnameErr = assignError($lastname, "Please enter your last name", 0);
    if (empty($lastnameErr) && preg_match('/[0-9]/', $lastname)) {
        $lastnameErr = "Last name cannot contain numbers";
    }
    if (!empty($contactno) && !preg_match('/^[0-9]+$/', $contactno)) {
        $contactnoErr = "Contact number must contain only numbers";
    } else {
        $contactnoErr = "";
    }

    $ageErr = assignError($age, "Please enter your age", 0);
    $genderErr = assignError($gender, "Please enter your gender", 0);
    $departmentErr = assignError($department, "Please enter your department", 0);
    $statusErr = assignError($status, "Please enter your status", 0);

    if (empty($firstnameErr) && empty($lastnameErr) && empty($ageErr) && empty($genderErr) && empty($departmentErr) && empty($contactnoErr) && empty($statusErr) && empty($genErr)) {
        mysqli_begin_transaction($con);
        $query = "update patients set first_name = '$firstname', last_name = '$lastname', age = '$age', gender = '$gender', contact_number = '$contactno', department_code = '$department', status = '$status' where patient_id = '$patientID'";

        if (mysqli_query($con, $query)) {
            mysqli_commit($con);
            $_SESSION['success'] = "Information updated successfully";
            header("location: information.php");
            exit();
        } else {
            mysqli_rollback($con);
            $_SESSION['error'] = "Information update failed" . mysqli_error($con);
            header("location: information.php");
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
    <link rel="stylesheet" href="cssStyles/information.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <title>Your Information</title>
</head>

<body>
    <div class="main-content">
        <div class="information-container">
            <h1><i class="fas fa-user-circle"></i> Your Personal Information</h1>
            <div class="information-card">
                <div class="info-section">
                    <h2>Basic Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">First Name:</span>
                            <span class="info-value"><?php echo $patientData['first_name']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Name:</span>
                            <span class="info-value"><?php echo $patientData['last_name']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Age:</span>
                            <span class="info-value"><?php echo $patientData['age']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Gender:</span>
                            <span class="info-value"><?php echo $patientData['gender']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h2>Contact Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Contact Number:</span>
                            <span class="info-value"><?php echo empty($patientData['contact_number']) ? 'Not provided' : $patientData['contact_number']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h2>Institutional Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span class="info-value"><?php echo $patientData['status']; ?></span>
                        </div>
                        <?php if (!empty($patientData['department_code'])):
                            $deptQuery = "SELECT department_name FROM departments WHERE department_code = ?";
                            $stmt = $con->prepare($deptQuery);
                            $stmt->bind_param("s", $patientData['department_code']);
                            $stmt->execute();
                            $deptResult = $stmt->get_result();
                            $deptData = $deptResult->fetch_assoc();
                        ?>
                            <div class="info-item">
                                <span class="info-label">Department:</span>
                                <span class="info-value"><?php echo $deptData['department_name'] ?? 'Unknown'; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="action-buttons">
                    <button onclick="showEditInfo()" class="edit-btn"><i class="fas fa-edit"></i> Edit Information</a>
                </div>
            </div>
        </div>
    </div>

    <dialog id="editPatientInformation">
        <form action="information.php" method="post" class="edit-form">
            <h2>Edit Patient Information</h2>

            <div class="form-group">
                <label for="firstName">First Name</label>
                <input type="text" name="firstName" id="firstName" value="<?php echo $patientData['first_name'] ?>">

                <?php errors($firstnameErr) ?>
            </div>

            <div class="form-group">
                <label for="lastName">Last Name</label>
                <input type="text" name="lastName" id="lastName" value="<?php echo $patientData['last_name'] ?>">

                <?php errors($lastnameErr) ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" min="1" max="70" value="<?php echo $patientData['age'] ?>">

                    <?php errors($ageErr) ?>
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender">
                        <option value="">Select your Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="LGBTQIA+">LGBTQIA+</option>
                    </select>

                    <?php errors($genderErr) ?>
                </div>
            </div>

            <div class="form-group">
                <label for="contactNum">Contact Number</label>
                <input type="tel" name="contactNum" id="contactNum" placeholder="09XXXXXXXXX" value="<?php echo $patientData['contact_number'] ?>">

                <?php errors($contactnoErr) ?>
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <select name="department" id="department">
                    <option value="">Select your Department</option>
                    <option value="CCS">College of Computer Studies (CCS)</option>
                    <option value="CAS">College of Arts and Sciences (CAS)</option>
                    <option value="COED">College of Education (COED)</option>
                    <option value="CON">College of Nursing (CON)</option>
                    <option value="CIHM">College of International Hospitality Management (CIHM)</option>
                    <option value="COEN">College of Engineering (COE)</option>
                    <option value="CBA">College of Business and Accountancy (CBA)</option>
                    <option value="STAFFS">SCHOOL STAFFS</option>
                </select>

                <?php errors($departmentErr) ?>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">Select your Status</option>
                    <option value="Student">Student</option>
                    <option value="Faculty">Faculty</option>
                    <option value="School Staff">School Staff</option>
                </select>

                <?php errors($statusErr) ?>
            </div>

            <?php errors($genErr)?>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeEditInfo()">Cancel</button>
                <input type="submit" name="submit" value="Save" class="btn-submit">
            </div>
        </form>
    </dialog>

    <?php
    if (isset($_SESSION['error'])) {
    ?>
        <script>
            swal({
                title: "ERROR",
                text: "<?php echo $_SESSION['error']; ?>",
                icon: "error",
                button: "OK"
            });
        </script>
    <?php
    }
    unset($_SESSION['error']);

    if (isset($_SESSION['success'])) {
    ?>
        <script>
            swal({
                title: "SUCCESS",
                text: "<?php echo $_SESSION['success']; ?>",
                icon: "success",
                button: "OK"
            });
        </script>
    <?php
    }
    unset($_SESSION['success']);

    ?>
</body>

<script>
    const dialog = document.getElementById("editPatientInformation")

    function showEditInfo() {
        dialog.showModal()
    }

    function closeEditInfo() {
        dialog.close()
    }

    window.onload = function() {
        <?php
        if (!empty($firstnameErr) || !empty($lastnameErr) || !empty($ageErr) || !empty($genderErr) || !empty($departmentErr) || !empty($contactnoErr) || !empty($statusErr) || !empty($genErr)) {
        ?>dialog.showModal();
    <?php
        }
    ?>

    }
</script>

</html>