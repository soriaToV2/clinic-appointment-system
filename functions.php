<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "connection.php";
require_once __DIR__ . "/mail_config.php";

function loginUser($username, $password)
{
    global $con;

    $query = "select * from users where username = '$username'";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if ($row['is_active'] == 0) {
            return "Account is inactive!";
        }

        if ($row['login_attempts'] > 2) {
            return "Account locked due to too many failed attempts. Use Forgot Password to reset your password.";
        }

        mysqli_begin_transaction($con);
        if (password_verify($password, $row['password'])) {
            $resetQuery = "update users set login_attempts = 0 where username = '$username'";
            mysqli_query($con, $resetQuery);
            mysqli_commit($con);

            if ($row['role'] == 'Patient') {
                $query2 = "select * from patients where patient_id = '" . $row['user_id'] . "'";
                $result2 = mysqli_fetch_assoc(mysqli_query($con, $query2));
            } else {
                $query2 = "select * from clinic_staff where staff_id = '" . $row['user_id'] . "'";
                $result2 = mysqli_fetch_assoc(mysqli_query($con, $query2));
            }

            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['lastname'] = $result2['last_name'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['username'] = $row['username'];

            return $_SESSION['role'];
        } else {
            $newAttempts = $row['login_attempts'] + 1;
            $updateQuery = "update users set login_attempts = $newAttempts where username = '$username'";
            mysqli_query($con, $updateQuery);

            mysqli_commit($con);

            return "Wrong password";
        }
    } else {
        return "User not found!";
    }
}

function regUserPatient($username, $studID, $firstname, $middlename, $lastname, $age, $genders, $status, $department, $contactnum, $email, $password, $role) {
    global $con;

    $staffCheck = "select * from enrolled_users where university_id = '$studID' and status = 'Clinic Staff'";
    $staffCheckResult = mysqli_query($con, $staffCheck);

    if (mysqli_num_rows($staffCheckResult) > 0) {
        return "Clinic staff cannot register through this page. Please contact administrator.";
    }

    $hashPass = password_hash($password, PASSWORD_DEFAULT);

    $usernameCheck = "select * from users where username = '$username'";
    $emailCheck = "select * from users where email = '$email'";
    $idCheck = "select * from users where user_id = '$studID'";
    $registeredCheck = "select * from enrolled_users where university_id = '$studID'";
    
    $usernameCheckResult = mysqli_query($con, $usernameCheck);
    $emailCheckResult = mysqli_query($con, $emailCheck);
    $idCheckResult = mysqli_query($con, $idCheck);
    $registeredCheckResult = mysqli_query($con, $registeredCheck);

    if (mysqli_num_rows($usernameCheckResult) > 0) {
        return "Username are already taken!";
    }
    if (mysqli_num_rows($emailCheckResult) > 0) {
        return "Email are already taken!";
    }
    if (mysqli_num_rows($idCheckResult) > 0) {
        return "ID are already taken!";
    }
    if (mysqli_num_rows($registeredCheckResult) == 0) {
        return "Your ID is not registered yet!";
    }

    try{
        mysqli_begin_transaction($con);
        $insertUser = "insert into users (user_id, username, password, email, role) values ('$studID','$username','$hashPass','$email','$role')";

        if (mysqli_query($con, $insertUser)) {
            $insertPatient = "insert into patients (patient_id, first_name, middle_name, last_name, age, gender, contact_number, department_code, status) values ('$studID','$firstname','$middlename','$lastname','$age','$genders','$contactnum','$department','$status')";
            $insertPatientResult = mysqli_query($con, $insertPatient);

            $update = "update enrolled_users set first_name = '$firstname', last_name = '$lastname', status = '$status' where university_id = '$studID'";
            $updateResult = mysqli_query($con, $update);

            mysqli_commit($con);
            if ($insertPatientResult && $updateResult) {
                return true;
            } else {
                mysqli_rollback($con);
                return "Failed to create patient record: " . mysqli_error($con);
            }
        } else {
            mysqli_rollback($con);
            return "Failed to create user account: " . mysqli_error($con);
        }
    }catch(Exception $e){
        mysqli_rollback($con);
        return $e->getMessage();
    }
}
function regUserAdmin($adminID, $username, $password, $email, $role, $firstname, $middlename, $lastname, $specialty, $contactNum) {
    global $con;

    $hashPass = password_hash($password, PASSWORD_DEFAULT);

    $usernameCheck = "select * from users where username = '$username'";
    $emailCheck = "select * from users where email = '$email'";
    $idCheck = "select * from users where user_id = '$adminID'";
    $registeredCheck = "select * from enrolled_users where university_id = '$adminID'";

    $usernameCheckResult = mysqli_query($con, $usernameCheck);
    $emailCheckResult = mysqli_query($con, $emailCheck);
    $idCheckResult = mysqli_query($con, $idCheck);
    $registeredCheckResult = mysqli_query($con, $registeredCheck);

    if (mysqli_num_rows($usernameCheckResult) > 0) {
        return "Username are already taken!";
    }
    if (mysqli_num_rows($emailCheckResult) > 0) {
        return "Email are already taken!";
    }
    if (mysqli_num_rows($idCheckResult) > 0) {
        return "ID are already taken!";
    }
    // if (mysqli_num_rows($registeredCheckResult) == 0) {
    //     return "Your ID is not registered yet!";
    // }

    try {
        mysqli_begin_transaction($con);

        $insertEnrolled = "insert into enrolled_users (university_id, first_name, last_name, status) values ('$adminID','$firstname','$lastname', '$role')";

        if(mysqli_query($con, $insertEnrolled)){
            $insertUser = "insert into users (user_id, username, password, email, role) values ('$adminID','$username','$hashPass','$email','$role')";

            if (mysqli_query($con, $insertUser)) {
                $insertPatient = "insert into clinic_staff (staff_id, first_name, middle_name, last_name, specialty, contact_number) values ('$adminID','$firstname','$middlename','$lastname','$specialty','$contactNum')";
                $insertPatientResult = mysqli_query($con, $insertPatient);

                if ($insertPatientResult) {
                    mysqli_commit($con);
                    return true;
                } else {
                    mysqli_rollback($con);
                    return "Failed to create patient record: " . mysqli_error($con);
                }
            
            }
        } else {
            mysqli_rollback($con);
            return "Failed to create user account: " . mysqli_error($con);
        }
    } catch (Exception $e) {
        mysqli_rollback($con);
        return $e->getMessage();
    }
}

function errors($err){
    if(!empty($err)){
        ?><div class="error-message"><?php echo $err; ?></div><?php
    }
}
function assignError($var, $message, $val)
{
    if (empty($var)) {
        return $message;
    }else{
        if ($val == 1) {//para sa ID's
            if (!preg_match('/^[0-9]{2}-[0-9]{5}$/', $var)) {
                return $message = "ID must be in XX-XXXXX format!";
            }
        } elseif ($val == 2) {//para sa age
            if (!preg_match('/^[0-9]{2}$/', $var)) {
                return $message = "Enter a valid age!";
            }
        } elseif ($val == 3) { //para sa contact number
            if (!preg_match('/^[0-9]{11}$/', $var)) {
                return $message = "Contact number must be 11 digits long in format of 09123456789!";
            }else{
                return null;
            }
        } elseif ($val == 4) { //para sa email
            if (!preg_match('/^[a-z]+_[a-z]+@plpasig\.edu\.ph$/i', $var)) {
                return "Only use university email which is in this format: surname_firstname@plpasig.edu.ph";
            }
        } elseif ($val == 5) { //para sa password
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/', $var)) {
                return $message = "Password must contain at least 1 uppercase and lowercase letter, 1 special character, 1 number and must be 8+ characters long!";
            }
        }
    }
    return "";
}

function insertAppointment($patientID, $staffID, $date, $time, $service, $status, $reason){
    global $con;

    $staffID = ($staffID === null) ? 'NULL' : "'$staffID'";
    $reason = ($reason === null) ? 'NULL' : "'$reason'";

    $query = "select * from clinic_unavailability where unavailable_date = '$date'";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return "Date cant be book due to ". $row['reason'];
    }

    $capacityCheck = "SELECT 
        tb.capacity, 
        COUNT(a.appointment_id) as current_bookings 
        FROM time_blocks tb
        LEFT JOIN appointments a ON tb.time_block_id = a.time_block_id 
            AND a.appointment_date = '$date' 
            AND a.status IN ('Pending', 'Approved', 'Reschedule', 'Followup')
        WHERE tb.time_block_id = '$time'
        GROUP BY tb.time_block_id";

    $capacityResult = mysqli_query($con, $capacityCheck);
    $capacityData = mysqli_fetch_assoc($capacityResult);

    if ($capacityData['current_bookings'] >= $capacityData['capacity']) {
        return "This time slot is already fully booked";
    }

    mysqli_begin_transaction($con);
    $insert = "call insert_appointment('$patientID', $staffID, '$date', '$time', '$service', '$status', $reason, '0')";
    if (mysqli_query($con, $insert)) {
        mysqli_commit($con);
        return true;
    } else {
        mysqli_rollback($con);
        return "Error: " . mysqli_error($con);
    }
}

function sendEmail($subject, $body, $email){

    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    $mail = new PHPMailer(true);

    try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom(MAIL_USERNAME, 'Clinic');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;

    $mail->send();
    header("Location: requestCode.php");
    } catch (Exception $e) {
    $err = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

}

function backupDatabase($con)
{
    $tables = array();
    $result = mysqli_query($con, "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    $return = '';

    foreach ($tables as $table) {
        $result = mysqli_query($con, "SELECT * FROM $table");
        $num_fields = mysqli_num_fields($result);

        $return .= "DROP TABLE IF EXISTS $table;";
        $row2 = mysqli_fetch_row(mysqli_query($con, "SHOW CREATE TABLE $table"));
        $return .= "\n\n" . $row2[1] . ";\n\n";

        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = mysqli_fetch_row($result)) {
                $return .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }

    $backup_file_name = 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $backup_file_name);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($return));
    ob_clean();
    flush();
    echo $return;
    exit;
}

function restoreDatabase($con, $file)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Error uploading file.";
    }

    $file_content = file_get_contents($file['tmp_name']);
    $file_content = preg_replace('/--.*?$|#.*?$|DELIMITER \$\$|DELIMITER ;/m', '', $file_content);
    $queries = array_filter(explode(';', $file_content), function ($query) {
        return trim($query) !== '';
    });

    mysqli_query($con, "SET FOREIGN_KEY_CHECKS=0");
    $errors = [];

    foreach ($queries as $query) {
        $query = trim($query);
        if (
            empty($query) ||
            stripos($query, 'CREATE VIEW') !== false ||
            stripos($query, 'CREATE ALGORITHM') !== false ||
            stripos($query, 'CREATE PROCEDURE') !== false ||
            stripos($query, 'CREATE FUNCTION') !== false ||
            stripos($query, 'CREATE TRIGGER') !== false ||
            stripos($query, 'CREATE EVENT') !== false
        ) {
            continue;
        }

        if (!mysqli_query($con, $query)) {
            $errors[] = mysqli_error($con);
        }
    }

    mysqli_query($con, "SET FOREIGN_KEY_CHECKS=1");

    return empty($errors) ? true : implode("\n", $errors);
}
