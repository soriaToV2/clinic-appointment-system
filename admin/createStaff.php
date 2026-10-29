<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
}
include "../functions.php";

if (isset($_POST['import'])) {
    if ($_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['import_file']['tmp_name'];
        $fileType = pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION);

        if ($fileType == 'csv') {
            require_once '../vendor/autoload.php';

            try {
                $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
                $spreadsheet = $reader->load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                $successCount = 0;
                $errorCount = 0;
                $errors = [];

                array_shift($rows);

                mysqli_begin_transaction($con);

                foreach ($rows as $rowIndex => $row) {
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    $university_id = trim($row[0] ?? '');
                    $first_name = trim($row[1] ?? '');
                    $last_name = trim($row[2] ?? '');
                    $role = trim($row[5] ?? '');
                    $status_text = trim($row[6] ?? '');
                    $is_active = strtolower($status_text) == 'active' ? 1 : 0;

                    if (empty($university_id) || empty($first_name) || empty($last_name) || empty($role) || empty($status_text)) {
                        $errorCount++;
                        $errors[] = "Skipped row " . ($rowIndex + 2) . ": Missing required fields";
                        continue;
                    }

                    $course = isset($row[3]) ? trim($row[3]) : null;
                    $section = isset($row[4]) ? trim($row[4]) : null;

                    $existing = mysqli_query($con, "SELECT * FROM enrolled_users WHERE university_id = '$university_id'");

                    if (mysqli_num_rows($existing) > 0) {
                        $query1 = "UPDATE enrolled_users SET 
                                  first_name = '$first_name',
                                  last_name = '$last_name',
                                  course = " . ($course ? "'$course'" : "NULL") . ",
                                  section = " . ($section ? "'$section'" : "NULL") . ",
                                  status = '$role',
                                  is_active = '$is_active'
                                  WHERE university_id = '$university_id'";

                        $query2 = "UPDATE users SET 
                                  is_active = '$is_active'
                                  WHERE user_id = '$university_id'";

                        $result1 = mysqli_query($con, $query1);
                        $result2 = mysqli_query($con, $query2);

                        if ($result1 && $result2) {
                            $successCount++;
                        } else {
                            $errorCount++;
                            $errors[] = "Error updating row " . ($rowIndex + 2) . ": " . mysqli_error($con);
                            mysqli_rollback($con);
                            continue;
                        }
                    } else {
                        $query = "INSERT INTO enrolled_users (university_id, first_name, last_name, course, section, status, is_active)
                                 VALUES ('$university_id', '$first_name', '$last_name', " .
                            ($course ? "'$course'" : "NULL") . ", " .
                            ($section ? "'$section'" : "NULL") . ", '$role', $is_active)";

                        if (mysqli_query($con, $query)) {
                            $successCount++;
                        } else {
                            $errorCount++;
                            $errors[] = "Error inserting row " . ($rowIndex + 2) . ": " . mysqli_error($con);
                            mysqli_rollback($con);
                            continue;
                        }
                    }
                }

                if ($errorCount == 0 || $successCount > 0) {
                    mysqli_commit($con);
                    $importResult = [
                        'success' => true,
                        'message' => "Successfully imported $successCount records." .
                            ($errorCount > 0 ? " Skipped $errorCount invalid records." : "")
                    ];
                    if ($errorCount > 0) {
                        $importResult['errors'] = $errors;
                    }
                } else {
                    mysqli_rollback($con);
                    $importResult = [
                        'success' => false,
                        'message' => "Import failed. All records were skipped due to errors.",
                        'errors' => $errors
                    ];
                }
            } catch (Exception $e) {
                mysqli_rollback($con);
                $importResult = ['success' => false, 'message' => "Error processing file: " . $e->getMessage()];
            }
        } else {
            $importResult = ['success' => false, 'message' => "Invalid file type. Please upload a CSV file."];
        }
    } else {
        $importResult = ['success' => false, 'message' => "Error uploading file."];
    }
}

if (isset($_POST['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=enrolled_users_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    fputcsv($output, ['University ID', 'First Name', 'Last Name', 'Course', 'Section', 'Status', 'Active']);

    $query = "SELECT * FROM enrolled_users";
    $result = mysqli_query($con, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['university_id'],
            $row['first_name'],
            $row['last_name'],
            $row['course'],
            $row['section'],
            $row['status'],
            $row['is_active'] ? 'Active' : 'Inactive'
        ]);
    }

    fclose($output);
    exit();
}

if (isset($_GET['download_template'])) {
    require_once '../vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'University ID');
    $sheet->setCellValue('B1', 'First Name');
    $sheet->setCellValue('C1', 'Last Name');
    $sheet->setCellValue('D1', 'Course');
    $sheet->setCellValue('E1', 'Section');
    $sheet->setCellValue('F1', 'Status');
    $sheet->setCellValue('G1', 'Active');

    // Set example data
    $sheet->setCellValue('A2', '25-00110');
    $sheet->setCellValue('B2', 'John');
    $sheet->setCellValue('C2', 'Doe');
    $sheet->setCellValue('F2', 'Faculty');
    $sheet->setCellValue('G2', 'Active');

    $sheet->setCellValue('A3', '25-00111');
    $sheet->setCellValue('B3', 'Nick');
    $sheet->setCellValue('C3', 'Hernandez');
    $sheet->setCellValue('F3', 'School Staff');
    $sheet->setCellValue('G3', 'Active');

    $sheet->setCellValue('A4', '25-00112');
    $sheet->setCellValue('B4', 'William');
    $sheet->setCellValue('C4', 'Anderson');
    $sheet->setCellValue('F4', 'Clinic Staff');
    $sheet->setCellValue('G4', 'Active');

    $sheet->setCellValue('A5', '25-00113');
    $sheet->setCellValue('B5', 'Aurora');
    $sheet->setCellValue('C5', 'Manuel');
    $sheet->setCellValue('D5', 'BSCS');
    $sheet->setCellValue('E5', '1B');
    $sheet->setCellValue('F5', 'Student');
    $sheet->setCellValue('G5', 'Active');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="enrolled_users_template.xlsx"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit();
}

$username = $id = $firstName = $lastName = $middleName = $specialty = $contactNum = $email = $password = $confirmPassword = $genErr = "";

$usernameErr = $idErr = $firstNameErr = $lastNameErr = $specialtyErr = $contactNumErr = $emailErr = $passwordErr = $confirmPasswordErr = $password_match = null;

if (isset($_POST['submit'])) { //creation of staff accounts
    $username = $_POST['username'];
    $id = $_POST['staffID'];
    $firstName = $_POST['firstname'];
    $lastName = $_POST['lastname'];
    $middleName = $_POST['middlename'];
    $specialty = $_POST['specialty'];
    $contactNum = $_POST['contactnum'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    $usernameErr = assignError($username, "Please enter a username", 0);
    $idErr = assignError($id, "Please enter an ID", 1);
    $firstNameErr = assignError($firstName, "Please enter your first name", 0);
    $lastNameErr = assignError($lastName, "Please enter your last name", 0);
    $specialtyErr = assignError($specialty, "Please select your specialty", 0);
    $contactNumErr = assignError($contactNum, "", 3);
    $emailErr = assignError($email, "Please enter your email", 4);
    $passwordErr = assignError($password, "Please enter your password", 5);
    $confirmPasswordErr = assignError($confirmPassword, "Please confirm your password", 0);

    if ($password != $confirmPassword) {
        $password_match = "Passwords do not match!";
    }
    if (
        empty($usernameErr) && empty($idErr) && empty($firstNameErr) && empty($lastNameErr) && empty($specialtyErr) && empty($contactNumErr) && empty($emailErr) && empty($passwordErr) && empty($confirmPasswordErr) && empty($password_match) && empty($genErr)
    ) {
        $genErr = regUserAdmin($id, $username, $password, $email, "Clinic Staff", $firstName, $middleName, $lastName, $specialty, $contactNum);

        if ($genErr === true) {
            $username = $id = $firstName = $lastName = $middleName = $specialty = $contactNum = $email = $password = $confirmPassword = "";
        }
    }
}

$enrolledID = $enrolledfirstname = $enrolledlastname = $enrolledCourse = $enrolledSection = $enrolledStatus = "";
$enrolledIDErr = $enrolledfirstnameErr = $enrolledlastnameErr = $enrolledCourseErr = $enrolledSectionErr = $enrolledStatusErr = $genEnrolledErr = "";
if (isset($_POST['enrolled'])) {//registering specific user
    $enrolledID = $_POST['enrolledID'];
    $enrolledfirstname = $_POST['enrolledfirstname'];
    $enrolledlastname = $_POST['enrolledlastname'];
    $enrolledCourse = $_POST['enrolledCourse'];
    $enrolledSection = $_POST['enrolledSection'];
    $enrolledStatus = $_POST['enrolledStatus'];

    $enrolledIDErr = assignError($enrolledID, "Please enter an ID", 1);
    $enrolledfirstnameErr = assignError($enrolledfirstname, "Please enter your first name", 0);
    $enrolledlastnameErr = assignError($enrolledlastname, "Please enter your last name", 0);
    $enrolledStatusErr = assignError($enrolledStatus, "Please select your status", 0);

    if (
        empty($enrolledIDErr) && empty($enrolledfirstnameErr) && empty($enrolledlastnameErr) && empty($enrolledCourseErr) && empty($enrolledSectionErr) && empty($enrolledStatusErr)
    ) {
        mysqli_begin_transaction($con);
        $enrollQuery = "insert into enrolled_users values('$enrolledID', '$enrolledfirstname', '$enrolledlastname', '$enrolledCourse', '$enrolledSection', '$enrolledStatus', '1')";

        if (mysqli_query($con, $enrollQuery)) {
            $genEnrolledErr = true;
            mysqli_commit($con);
        } else {
            $genEnrolledErr = "Error: " . $enrollQuery . "<br>" . mysqli_error($con);
            mysqli_rollback($con);
        }

        if ($genEnrolledErr === true) {
            $enrolledID = $enrolledfirstname = $enrolledlastname = $enrolledCourse = $enrolledSection = $enrolledStatus = "";
        }
    }
}

if (isset($_GET['disable'])) {
    $userID = $_GET['disable'];
    mysqli_begin_transaction($con);
    $query = "update users set is_active = not is_active where user_id = '$userID'";
    $query2 = "update enrolled_users set is_active = not is_active where university_id = '$userID'";
    if (mysqli_query($con, $query) && mysqli_query($con, $query2)) {
        mysqli_commit($con);
    } else {
        mysqli_rollback($con);
    }
    header("Location: createStaff.php");
    exit();
}

include "nav.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cssStyles/createStaff.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <title>Staff Management</title>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-users-cog"></i>
                User Management
            </h1>
        </div>

        <div class="import-export-section">
            <h2>Bulk Registration of Patients</h2>
            <div class="import-export-form">
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="import_file" accept=".csv, .xls, .xlsx" required>
                    <button type="submit" name="import" class="import-btn">
                        <i class="fas fa-file-import"></i> Import
                    </button>
                </form>

                <form method="post">
                    <button type="submit" name="export" class="export-btn">
                        <i class="fas fa-file-export"></i> Export
                    </button>
                </form>

                <a href="createStaff.php?download_template=1" class="download-template-btn">
                    <i class="fas fa-file-download"></i> Download Template
                </a>
            </div>

            <?php if (isset($importResult)): ?>
                <div class="import-result <?php echo $importResult['success'] ? 'import-success' : 'import-error'; ?>">
                    <?php echo $importResult['message']; ?>
                    <?php if (!$importResult['success'] && isset($importResult['errors'])): ?>
                        <ul>
                            <?php foreach ($importResult['errors'] as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-header">
            <h2>
                <i class="fas fa-users"></i>
                Registered Users
            </h2>
            <button class="create-btn" onclick="showDialogEnrolled()">
                <i class="fas fa-plus"></i> Register Patient
            </button>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>University ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "select * from enrolled_users order by university_id";
                    $result = mysqli_query($con, $query);

                    while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                        <tr>
                            <td><?php echo $row['university_id']; ?></td>
                            <td><?php echo $row['first_name']; ?></td>
                            <td><?php echo $row['last_name']; ?></td>
                            <td><?php echo empty($row['course']) ? 'N/A' : $row['course']; ?></td>
                            <td><?php echo empty($row['section']) ? 'N/A' : $row['section']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <button class="action-btn <?php echo $row['is_active'] ? 'disable-btn' : 'enable-btn'; ?>"
                                    onclick="window.location.href='createStaff.php?disable=<?php echo $row['university_id']; ?>'">
                                    <i class="fas <?php echo $row['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                    <?php echo $row['is_active'] ? 'Disable' : 'Enable'; ?>
                                </button>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="table-header">
            <h2>
                <i class="fas fa-user-md"></i>
                Clinic Employees
            </h2>
            <button class="create-btn" onclick="showDialog()">
                <i class="fas fa-plus"></i> Create Staff Account
            </button>
        </div>
        <div class="table-container">
            <table class="clinic-employees-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialty</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "select * from clinic_staff_view";
                    $result = mysqli_query($con, $query);

                    while ($row = mysqli_fetch_assoc($result)) {
                        $staffName = $row['first_name'] . ' ' . (!empty($row['middle_name']) ? $row['middle_name'] . ' ' : '') . $row['last_name'];
                    ?>
                        <tr>
                            <td><?php echo $row['staff_id']; ?></td>
                            <td><?php echo $staffName; ?></td>
                            <td><?php echo $row['specialty']; ?></td>
                            <td><?php echo $row['username']; ?></td>
                            <td class="email-cell"><?php echo $row['email']; ?></td>
                            <td><?php echo empty($row['contact_number']) ? 'Not set' : $row['contact_number']; ?></td>
                            <td><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <button class="action-btn <?php echo $row['is_active'] ? 'disable-btn' : 'enable-btn'; ?>"
                                    onclick="window.location.href='createStaff.php?disable=<?php echo $row['staff_id']; ?>'">
                                    <i class="fas <?php echo $row['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                    <?php echo $row['is_active'] ? 'Disable' : 'Enable'; ?>
                                </button>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <dialog id="registerUser">
            <div class="dialog-container">
                <h2>Register New Patient Account</h2>
                <form action="createStaff.php" method="post">
                    <div class="form-container">
                        <div class="form-group">
                            <label>University ID</label>
                            <input type="text" name="enrolledID" value="<?php echo $id ?>">
                            <?php errors($enrolledIDErr); ?>
                        </div>

                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="enrolledfirstname" value="<?php echo $firstName ?>">
                            <?php errors($enrolledfirstnameErr); ?>
                        </div>

                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="enrolledlastname" value="<?php echo $lastName ?>">
                            <?php errors($enrolledlastnameErr); ?>
                        </div>

                        <div class="form-group">
                            <label>Course</label>
                            <input type="text" name="enrolledCourse" value="<?php echo $lastName ?>">
                            <?php errors($enrolledCourseErr); ?>
                        </div>

                        <div class="form-group">
                            <label>Section</label>
                            <input type="text" name="enrolledSection" value="<?php echo $lastName ?>">
                            <?php errors($enrolledSectionErr); ?>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="enrolledStatus">
                                <option value="">Select your Status</option>
                                <option value="Student">Student</option>
                                <option value="Faculty">Faculty</option>
                                <option value="School Staff">School Staff</option>
                            </select>
                            <?php errors($enrolledStatusErr); ?>
                        </div>
                    </div>

                    <?php
                    if ($genEnrolledErr === true) {
                    ?>
                        <script>
                            swal({
                                title: "SUCCESS",
                                text: "You have successfully created an Account!",
                                icon: "success",
                                button: "OK"
                            }).then(() => {
                                window.location.href = "createStaff.php";
                            });
                        </script>
                    <?php
                    }
                    ?>

                    <div class="form-actions">
                        <button type="submit" name="enrolled" class="submit-btn">Create Account</button>
                        <button type="button" class="cancel-btn" onclick="closeDialogEnrolled()">Cancel</button>
                    </div>


                </form>
            </div>
        </dialog>

        <dialog id="createAccount">
            <div class="dialog-container">
                <h2>Create New Staff Account</h2>
                <form action="createStaff.php" method="post">
                    <div class="form-container">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo $username ?>">
                            <?php errors($usernameErr); ?>
                        </div>

                        <div class="form-group">
                            <label>Staff ID</label>
                            <input type="text" name="staffID" value="<?php echo $id ?>">
                            <?php errors($idErr); ?>
                        </div>

                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="firstname" value="<?php echo $firstName ?>">
                            <?php errors($firstNameErr); ?>
                        </div>

                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="lastname" value="<?php echo $lastName ?>">
                            <?php errors($lastNameErr); ?>
                        </div>

                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middlename">
                        </div>

                        <div class="form-group">
                            <label>Specialty</label>
                            <input type="text" name="specialty" value="<?php echo $specialty ?>">
                            <?php errors($specialtyErr); ?>
                        </div>

                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="tel" name="contactnum" value="<?php echo $contactNum ?>">
                            <?php errors($contactNumErr); ?>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo $email ?>">
                            <?php errors($emailErr); ?>
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password">
                            <?php errors($passwordErr); ?>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirmPassword">
                            <?php
                            errors($confirmPasswordErr);
                            errors($genErr);
                            errors($password_match);
                            ?>
                        </div>
                    </div>

                    <?php
                    if ($genErr === true) {
                    ?>
                        <script>
                            swal({
                                title: "SUCCESS",
                                text: "You have successfully created an Account!",
                                icon: "success",
                                button: "OK"
                            }).then(() => {
                                window.location.href = "createStaff.php";
                            });
                        </script>
                    <?php
                    }
                    ?>

                    <div class="form-actions">
                        <button type="submit" name="submit" class="submit-btn">Create Account</button>
                        <button type="button" class="cancel-btn" onclick="closeDialog()">Cancel</button>
                    </div>


                </form>
            </div>
        </dialog>
    </div>

    <script>
        const dialog = document.getElementById("createAccount");
        const dialog2 = document.getElementById("registerUser");

        function showDialog() {
            dialog.showModal();
        }

        function closeDialog() {
            dialog.close();
        }

        function showDialogEnrolled() {
            dialog2.showModal();
        }

        function closeDialogEnrolled() {
            dialog2.close();
        }
        window.onload = function() {
            <?php
            if (
                !empty($usernameErr) || !empty($idErr) || !empty($firstNameErr) || !empty($lastNameErr) || !empty($specialtyErr) || !empty($contactNumErr) || !empty($emailErr) || !empty($passwordErr) || !empty($confirmPasswordErr) || !empty($password_match) || (!empty($genErr) && $genErr !== true)
            ) {
            ?>dialog.showModal();
            <?php
            }

            if (!empty($enrolledIDErr) || !empty($enrolledfirstnameErr) || !empty($enrolledlastnameErr) || !empty($enrolledStatusErr) || (!empty($genEnrolledErr) && $genEnrolledErr !== true)) {
            ?>dialog2.showModal();
        <?php
            }
        ?>
        };
    </script>
    <script>
        const darkModeButton = document.getElementById('darkModeButton');

        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            darkModeButton.textContent = 'Disable';
        }

        darkModeButton.addEventListener('click', () => {
            if (document.body.classList.contains('dark-mode')) {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
                darkModeButton.textContent = 'Enable';
            } else {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
                darkModeButton.textContent = 'Disable';
            }
        });

        function toggleUserStatus(userId, enable) {
            if (confirm(`Are you sure you want to ${enable ? 'enable' : 'disable'} this user?`)) {
                window.location.href = `createStaff.php?disable=${userId}`;
            }
        }
    </script>
</body>

</html>