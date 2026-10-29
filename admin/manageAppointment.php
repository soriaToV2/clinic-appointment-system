<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

include "../functions.php";

$patientID = $staffID = $date = $time = $service = $status = $reason = $Appresult = "";
$patientIDerr = $staffIDerr = $dateErr = $timeErr = $serviceErr = $statusErr = "";

if (isset($_POST['submit'])) {
    $patientID = $_POST['patientID'];
    $staffID = $_POST['staffID'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service = $_POST['service'];
    $status = $_POST['status'];
    $reason = $_POST['reason'];
    $reason1 = empty($reason) ? null : $reason;

    $appointmentDate = strtotime($date);
    $currentDate = strtotime(date('Y-m-d'));

    if ($appointmentDate < $currentDate) {
        $Appresult = "Cannot create appointment in the past";
    }

    $patientIDerr = assignError($patientID, "Please select a patient", 0);
    $staffIDerr = assignError($staffID, "Please select a staff member", 0);
    $dateErr = assignError($date, "Please select a date", 0);
    $timeErr = assignError($time, "Please select a time", 0);
    $serviceErr = assignError($service, "Please select a service", 0);
    $statusErr = assignError($status, "Please select a status", 0);

    if (empty($patientIDerr) && empty($staffIDerr) && empty($dateErr) && empty($timeErr) && empty($serviceErr) && empty($statusErr) && empty($Appresult)) {
        $Appresult = insertAppointment($patientID, $staffID, $date, $time, $service, $status, $reason1);
        if ($Appresult === true) {
            $_SESSION['success'] = "Appointment Booked Successfully";
            header("Location: manageAppointment.php");
            exit();
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

$approveErr = $approveStaffIDerr = "";

if (isset($_POST['approve'])) {
    $appointmentID = $_POST['appointment_id'];
    $appointmentDate = $_POST['appointment_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $staffID = $_POST['staff'];

    $approveStaffIDerr = assignError($staffID, "Please select a staff member", 0);

    $query = "select * from staff_unavailability where unavailable_date = '$appointmentDate' and staff_id = '$staffID'";
    if (mysqli_num_rows(mysqli_query($con, $query)) > 0) {
        $data = mysqli_fetch_assoc(mysqli_query($con, $query));
        $approveErr = "This staff member is unavailable on this date because of " . $data['reason'] . "!";
    }

    $dayOfWeek = date('l', strtotime($appointmentDate));
    $scheduleQuery = "SELECT * FROM staff_schedules 
                     WHERE staff_id = '$staffID' 
                     AND day_of_week = '$dayOfWeek'
                     AND start_time <= '$startTime'
                     AND end_time >= '$endTime'";

    if (mysqli_num_rows(mysqli_query($con, $scheduleQuery)) == 0) {
        $approveErr = "This staff member is not scheduled to work during the selected time!";
    }

    if (empty($staffIDerr) && empty($approveErr)) {
        mysqli_begin_transaction($con);
        $query = "update appointments set staff_id = '$staffID', status = 'Approved' where appointment_id = $appointmentID";
        if (mysqli_query($con, $query)) {
            mysqli_commit($con);
            $_SESSION['success'] = "Appointment approved successfully!";
            header("Location: manageAppointment.php");
            exit();
        } else {
            mysqli_rollback($con);

            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

$cancelErr = "";

if (isset($_POST['cancel'])) {
    $appointmentID = $_POST['appointment_id'];
    $reason = $_POST['reason'];

    $cancelErr = assignError($reason, "Please provide a reason", 0);

    if (empty($cancelErr)) {
        mysqli_begin_transaction($con);
        $query = "update appointments set status = 'Cancelled', status_reason = '$reason' where appointment_id = $appointmentID";
        $result = mysqli_query($con, $query);
        if ($result) {
            mysqli_commit($con);
            $_SESSION['success'] = "Appointment Cancelled successfully!";
            header("Location: manageAppointment.php");
            exit();
        } else {
            mysqli_rollback($con);
            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

$reschedDateErr = $reschedReasonErr = "";
if (isset($_POST['resched'])) {
    $appointmentID = $_POST['appointment_id'];
    $newDate = $_POST['date'];
    $reschedDate = strtotime($newDate);
    $currentDate = strtotime(date('Y-m-d'));
    $reschedreason = $_POST['reason'];

    $reschedDateErr = assignError($newDate, "Please select a date", 0);
    $reschedReasonErr = assignError($reschedreason, "Please provide a reason", 0);

    if (!empty($newDate)) {
        if ($reschedDate < $currentDate) {
            $reschedDateErr = "Date in the past or in the present cant be set";
        }
    }
    $formattedDate = date('M d, Y', strtotime($newDate)) . ' (' . date('l', strtotime($newDate)) . ')';
    $reason = "<b>Suggested Date</b>: " . $formattedDate . "<br><b>Reason</b>: " . $reschedreason;

    if (empty($reschedDateErr) && empty($reschedReasonErr)) {
        mysqli_begin_transaction($con);
        $query = "update appointments set status = 'Reschedule', status_reason = '$reason' where appointment_id = $appointmentID";
        if (mysqli_query($con, $query)) {
            mysqli_commit($con);
            $_SESSION['success'] = "Appointment Rescheduled successfully!";
            header("Location: manageAppointment.php");
            exit();
        } else {
            mysqli_rollback($con);
            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

$bulkCourse = $bulkSection = $bulkStaff = $bulkDate = $bulkTime = $bulkService = $bulkStatus = $bulkReason = $bulkResult = "";
$bulkCourseErr = $bulkSectionErr = $bulkStaffErr = $bulkDateErr = $bulkTimeErr = $bulkServiceErr = $bulkStatusErr = $bulkReasonErr = "";
if (isset($_POST['bulk_submit'])) {
    $bulkCourse = $_POST['bulk_course'];
    $bulkSection = $_POST['bulk_section'];
    $bulkStaff = $_POST['bulk_staff'];
    $bulkDate = $_POST['bulk_date'];
    $bulkTime = $_POST['bulk_time'];
    $bulkService = $_POST['bulk_service'];
    $bulkStatus = $_POST['bulk_status'];
    $bulkReason = $_POST['bulk_reason'];

    $bulkCourseErr = assignError($bulkCourse, "Please select a course", 0);
    $bulkSectionErr = assignError($bulkSection, "Please select a section", 0);
    $bulkStaffErr = assignError($bulkStaff, "Please select a staff member", 0);
    $bulkDateErr = assignError($bulkDate, "Please select a date", 0);
    $bulkTimeErr = assignError($bulkTime, "Please select a time", 0);
    $bulkServiceErr = assignError($bulkService, "Please select a service", 0);
    $bulkStatusErr = assignError($bulkStatus, "Please select a status", 0);
    $bulkReasonErr = assignError($bulkReason, "Please provide a reason", 0);

    if (empty($bulkCourseErr) && empty($bulkSectionErr) && empty($bulkStaffErr) && empty($bulkDateErr) && empty($bulkTimeErr) && empty($bulkServiceErr) && empty($bulkStatusErr) && empty($bulkReasonErr)) {
        $bulkReason = "Required: " . $bulkReason;

        $query = "select patient_id from patients p 
                 join enrolled_users e on p.patient_id = e.university_id
                 where e.course = '$bulkCourse' and e.section = '$bulkSection'";
        $result = mysqli_query($con, $query);

        $successCount = 0;
        $errorCount = 0;

        mysqli_begin_transaction($con);

        while ($row = mysqli_fetch_assoc($result)) {
            $patientID = $row['patient_id'];
            try {
                $insertQuery = "CALL insert_appointment('$patientID', '$bulkStaff', '$bulkDate', '$bulkTime', '$bulkService', '$bulkStatus', '$bulkReason', 1)";
                $insertResult = mysqli_query($con, $insertQuery);

                if ($insertResult) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }

        if ($errorCount === 0 && $successCount > 0) {
            mysqli_commit($con);
            $_SESSION['success'] = "Successfully created $successCount appointments!";
        } else {
            mysqli_rollback($con);
            $_SESSION['error'] = "Created $successCount appointments, but failed to create $errorCount.";
        }

        header("Location: manageAppointment.php");
        exit();
    }
}

if (isset($_POST['bulk_cancel_submit'])) {
    $bulkCourse = $_POST['bulk_cancel_course'];
    $bulkSection = $_POST['bulk_cancel_section'];
    $bulkDate = $_POST['bulk_cancel_date'];
    $bulkReason = $_POST['bulk_cancel_reason'];

    mysqli_begin_transaction($con);

    try {
        $updateQuery = "UPDATE appointments a
                       JOIN enrolled_users e ON a.patient_id = e.university_id
                       SET a.status = 'Cancelled', 
                           a.status_reason = CONCAT('$bulkCourse $bulkSection appointments are cancelled: ', ?)
                       WHERE e.course = ?
                       AND e.section = ?
                       AND a.appointment_date = ?
                       AND a.is_bulk = 1
                       AND a.status IN ('Pending', 'Approved', 'Followup')";

        $stmt = mysqli_prepare($con, $updateQuery);
        mysqli_stmt_bind_param($stmt, "ssss", $bulkReason, $bulkCourse, $bulkSection, $bulkDate);
        mysqli_stmt_execute($stmt);
        $affectedRows = mysqli_stmt_affected_rows($stmt);

        if ($affectedRows > 0) {
            mysqli_commit($con);
            $_SESSION['success'] = "Successfully cancelled $affectedRows bulk appointments for $bulkCourse $bulkSection on $bulkDate";
        } else {
            $_SESSION['error'] = "No matching bulk appointments found to cancel";
        }
    } catch (Exception $e) {
        mysqli_rollback($con);
        $_SESSION['error'] = "Error cancelling bulk appointments: " . $e->getMessage();
    }

    header("Location: manageAppointment.php");
    exit();
}

if (isset($_GET['cancel'])) {
    $patientID = $staffID = $date = $time = $service = $status = $reason = $Appresult = "";
    $patientIDerr = $staffIDerr = $dateErr = $timeErr = $serviceErr = $statusErr = "";


    $reschedErr = "";
    $cancelErr = "";

    $approveErr = $approveStaffIDerr = "";
}

$bulkQuery = "SELECT 
    a.appointment_id, 
    a.patient_id, 
    p.first_name, 
    p.last_name, 
    e.course, 
    e.section, 
    a.appointment_date, 
    t.start_time, 
    t.end_time, 
    cs.service_name,
    a.status,
    a.status_reason
FROM appointments a
JOIN patients p ON a.patient_id = p.patient_id
JOIN enrolled_users e ON p.patient_id = e.university_id
JOIN time_blocks t ON a.time_block_id = t.time_block_id
JOIN clinic_services cs ON a.services = cs.service_id
WHERE a.status_reason LIKE '%Required: %'
GROUP BY e.course, e.section, a.appointment_date, t.start_time, t.end_time, cs.service_name
ORDER BY a.appointment_date, t.start_time";
$bulkResult = mysqli_query($con, $bulkQuery);

include "nav.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cssStyles/manageAppointment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <title>Document</title>
</head>

<body>
    <div class="container">
        <h1>
            <i class="fas fa-calendar-check"></i>
            Manage Appointments
        </h1>

        <div class="button-group">
            <button onclick="showCreateAppointment()" id="createAppointmentBtn" class="btn">
                <i class="fas fa-plus"></i> Create Appointment
            </button>
            <button onclick="showBulkAppointment()" id="createAppointmentBtn" class="btn">
                <i class="fas fa-plus"></i> Create Bulk Appointment
            </button>

        </div>

        <h2>
            <i class="fas fa-clock"></i>
            Pending Appointments
        </h2>

        <div class="table-container pending-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient Name</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Service</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT a.appointment_id, p.patient_id, p.first_name, p.last_name, p.status as patient_status, 
                              a.appointment_date, t.start_time, t.end_time, cs.service_name
                              FROM appointments a
                              JOIN patients p ON a.patient_id = p.patient_id
                              JOIN time_blocks t ON a.time_block_id = t.time_block_id
                              JOIN clinic_services cs ON a.services = cs.service_id
                              WHERE a.status = 'Pending'
                              ORDER BY a.appointment_date, t.start_time";

                    $result = mysqli_query($con, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                            <tr>
                                <td><?php echo $row['patient_id']; ?></td>
                                <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                <td><?php echo $row['patient_status']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['appointment_date'])) . ' (' . date('l', strtotime($row['appointment_date'])) . ')'; ?></td>
                                <td><?php echo date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])); ?></td>
                                <td><?php echo $row['service_name']; ?></td>
                                <td class="actions">
                                    <button class="btn approve" title="Approve" onclick="openApprove('<?php echo $row['appointment_id']; ?>', '<?php echo $row['appointment_date']; ?>', '<?php echo $row['start_time']; ?>', '<?php echo $row['end_time']; ?>')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn cancel" title="Cancel" onclick="openCancel('<?php echo $row['appointment_id']; ?>')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button class="btn reschedule" title="Reschedule" onclick="openResched('<?php echo $row['appointment_id']; ?>')">
                                        <i class="fas fa-calendar-alt"></i>
                                    </button>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center;'>No pending appointments found</td></tr>";
                    }

                    ?>
                </tbody>
            </table>
        </div>

        <h2>
            <i class="fas fa-clock"></i>
            Bulk Appointments

            <button onclick="openBulkCancel()" class="btn cancel" style="float: right;">
                <i class="fas fa-times"></i> Cancel Bulk Appointments
            </button>
        </h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Service</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($bulkResult) > 0) {
                        while ($row = mysqli_fetch_assoc($bulkResult)) {
                            $formattedDate = date('M d, Y', strtotime($row['appointment_date'])) . ' (' . date('l', strtotime($row['appointment_date'])) . ')';
                            $formattedTime = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
                    ?>
                            <tr>
                                <td><?php echo $row['course']; ?></td>
                                <td><?php echo $row['section']; ?></td>
                                <td><?php echo $formattedDate; ?></td>
                                <td><?php echo $formattedTime; ?></td>
                                <td><?php echo $row['service_name']; ?></td>
                                <td><?php echo $row['status']; ?></td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center;'>No bulk appointments found</td></tr>";
                    }
                    ?>

                </tbody>
            </table>
        </div>
    </div>

    <dialog id="createAppointment">
        <h1>Create New Appointment</h1>

        <form action="manageAppointment.php" method="POST">
            <div class="form-group">
                <label>Patient ID:</label>
                <input type="text" name="patientID">
            </div>

            <?php errors($patientIDerr); ?>


            <div class="form-group">
                <label>Staff:</label>
                <select name="staffID">
                    <option value="">Select a Staff Member</option>
                    <?php
                    $query = "select * from clinic_staff";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                    ?><option value="<?php echo $row['staff_id']; ?>"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></option><?php
                                                                                                                                        }
                                                                                                                                            ?>
                </select>
            </div>

            <?php errors($staffIDerr); ?>

            <div class="form-group">
                <label>Appointment Date:</label>
                <input type="date" name="date" min="<?php echo date('Y-m-d'); ?>">

                <?php errors($dateErr); ?>
            </div>

            <div class="form-group">
                <label>Appointment Time:</label>
                <select name="time">
                    <option value="">Select a Time Slot</option>
                    <?php
                    $query = "select * from time_blocks where is_active = 1";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                    ?><option value="<?php echo $row['time_block_id']; ?>"><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></option><?php
                                                                                                                                                                                                }
                                                                                                                                                                                                    ?>
                </select>
                <?php errors($timeErr); ?>
            </div>
            <div class="form-group">
                <label>Service:</label>
                <select name="service">
                    <option value="">Select a Service</option>
                    <?php
                    $query = "select * from clinic_services where is_active = 1";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                    ?><option value="<?php echo $row['service_id']; ?>"><?php echo $row['service_name']; ?></option><?php
                                                                                                                }
                                                                                                                    ?>
                </select>
                <?php errors($serviceErr); ?>
            </div>
            <div class="form-group">
                <label>Status:</label>
                <select name="status">
                    <option value="">Select Appointment Status</option>
                    <option value="Approved">Approved</option>
                    <option value="Followup">Follow-up</option>
                </select>
                <?php errors($statusErr); ?>
            </div>

            <div class="form-group">
                <label>Appointment Reason:</label>
                <input type="text" name="reason">
            </div>

            <?php
            if ($Appresult !== true) {
                errors($Appresult);
            }
            ?>

            <div class="button-group">
                <input type="submit" name="submit" value="Create Appointment">
                <button onclick="closeAppointment()" value="Cancel" onclick="window.location.href = 'manageAppointment.php?cancel=1';" class="btn">Cancel</button>
            </div>
        </form>
    </dialog>

    <dialog id="approve">
        <h2>Approve Appointment</h2>
        <form action="manageAppointment.php" method="POST">
            <input type="hidden" name="appointment_id" id="appointment_id">
            <input type="hidden" name="appointment_date" id="appointment_date">
            <input type="hidden" name="start_time" id="start_time">
            <input type="hidden" name="end_time" id="end_time">

            <div id="allSchedulesData" style="display:none;">
                <?php
                $query = "SELECT staff_id, day_of_week, start_time, end_time FROM staff_schedules";
                $result = mysqli_query($con, $query);
                $allSchedules = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $allSchedules[] = $row;
                }
                echo htmlspecialchars(json_encode($allSchedules));
                ?>
            </div>

            <div class="form-group">
                <label>Assign Staff:</label>
                <select name="staff" id="staffSelect" onchange="showStaffSchedule(this.value)">
                    <option value="">Select a Staff Member</option>
                    <?php
                    $query = "SELECT cs.staff_id, cs.first_name, cs.last_name, cs.specialty 
                        FROM clinic_staff cs
                        JOIN users u ON cs.staff_id = u.user_id
                        WHERE u.is_active = 1";
                    $result = mysqli_query($con, $query);

                    while ($row = mysqli_fetch_assoc($result)) {
                    ?><option value="<?php echo $row['staff_id']; ?>"><?php echo $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['specialty'] . ')'; ?></option><?php
                                                                                                                                                                        }
                                                                                                                                                                            ?>
                </select>
                <?php errors($approveStaffIDerr) ?>

                <div id="staffScheduleDisplay" style="margin-top: 15px; display: none;">
                    <h4>Staff Schedule:</h4>
                    <table id="scheduleTable" class="schedule-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <?php errors($approveErr) ?>

            <div class="button-group">
                <input type="submit" name="approve" value="Approve">
                <button onclick="closeApprove()" class="btn" onclick="window.location.href = 'manageAppointment.php?cancel=1';">Cancel</button>
            </div>
        </form>
    </dialog>

    <dialog id="cancel">
        <h2>Cancel Appointment</h2>

        <form action="manageAppointment.php" method="POST">
            <input type="hidden" name="appointment_id" id="cancel_appointment_id">

            <div class="form-group">
                <label>Provide Reason:</label>
                <input type="text" name="reason">
            </div>

            <?php errors($cancelErr) ?>

            <div class="button-group">
                <input type="submit" name="cancel" value="Cancel">
                <button onclick="closeCancel()" class="btn" onclick="window.location.href = 'manageAppointment.php?cancel=1';">Close</button>
            </div>
        </form>
    </dialog>

    <dialog id="resched">
        <h2>Resched Appointment</h2>

        <form action="manageAppointment.php" method="POST">
            <input type="hidden" name="appointment_id" id="resched_appointment_id">

            <div class="form-group">
                <label>Suggest Date:</label>
                <input type="date" name="date" min="<?php echo date('Y-m-d'); ?>">
                <?php errors($reschedDateErr) ?>
            </div>

            <div class="form-group">
                <label>Reason:</label>
                <input type="text" name="reason">
                <?php errors($reschedReasonErr) ?>
            </div>

            <div class="button-group">
                <input type="submit" name="resched" value="Resched">
                <button onclick="closeResched()" class="btn" onclick="window.location.href = 'manageAppointment.php?cancel=1';">Cancel</button>
            </div>
        </form>
    </dialog>

    <dialog id="bulkAppointment">
        <h2>Bulk Appointments</h2>
        <form action="manageAppointment.php" method="POST">
            <div class="form-group">
                <label>Select a Course:</label>
                <select name="bulk_course" id="bulk_course">
                    <option value="">Select Course</option>
                    <?php
                    $query = "SELECT DISTINCT course FROM enrolled_users WHERE course IS NOT NULL AND course != ''";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . $row['course'] . '">' . $row['course'] . '</option>';
                    }
                    ?>
                </select>
                <?php errors($bulkCourseErr) ?>
            </div>

            <div class="form-group">
                <label>Select a Section:</label>
                <select name="bulk_section" id="bulk_section">
                    <option value="">Select Section</option>
                    <?php
                    $query = "SELECT DISTINCT section FROM enrolled_users WHERE section IS NOT NULL AND section != ''";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . $row['section'] . '">' . $row['section'] . '</option>';
                    }
                    ?>
                </select>

                <?php errors($bulkSectionErr) ?>
            </div>

            <div class="form-group">
                <label>Assign Staff:</label>
                <select name="bulk_staff">
                    <option value="">Select a Staff Member</option>
                    <?php
                    $query = "SELECT cs.staff_id, cs.first_name, cs.last_name, cs.specialty 
                          FROM clinic_staff cs
                          JOIN users u ON cs.staff_id = u.user_id
                          WHERE u.is_active = 1";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . $row['staff_id'] . '">' . $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['specialty'] . ')</option>';
                    }
                    ?>
                </select>

                <?php errors($bulkStaffErr) ?>
            </div>

            <div class="form-group">
                <label>Appointment Date:</label>
                <input type="date" name="bulk_date" min="<?php echo date('Y-m-d'); ?>">

                <?php errors($bulkDateErr) ?>
            </div>

            <div class="form-group">
                <label>Appointment Time:</label>
                <select name="bulk_time">
                    <option value="">Select a Time Slot</option>
                    <?php
                    $query = "SELECT * FROM time_blocks WHERE is_active = 1";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . $row['time_block_id'] . '">' . date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) . '</option>';
                    }
                    ?>
                </select>

                <?php errors($bulkTimeErr) ?>
            </div>

            <div class="form-group">
                <label>Service:</label>
                <select name="bulk_service">
                    <option value="">Select a Service</option>
                    <?php
                    $query = "SELECT * FROM clinic_services WHERE is_active = 1";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . $row['service_id'] . '">' . $row['service_name'] . '</option>';
                    }
                    ?>
                </select>

                <?php errors($bulkServiceErr) ?>
            </div>

            <div class="form-group">
                <label>Status:</label>
                <select name="bulk_status">
                    <option value="">Select Appointment Status</option>
                    <option value="Approved">Approved</option>
                    <option value="Followup">Follow-up</option>
                </select>

                <?php errors($bulkStatusErr) ?>
            </div>

            <div class="form-group">
                <label>Appointment Reason:</label>
                <input type="text" name="bulk_reason">

                <?php errors($bulkReasonErr) ?>
            </div>

            <div class="button-group">
                <input type="submit" name="bulk_submit" value="Create Bulk Appointments">
                <button type="button" onclick="window.location.href = 'manageAppointment.php?cancel=1';" class="btn">Cancel</button>
            </div>
        </form>
    </dialog>

    <dialog id="bulkCancel">
        <h2>Cancel Bulk Appointments</h2>
        <form action="manageAppointment.php" method="POST">
            <div class="form-group">
                <label>Select a Course:</label>
                <select name="bulk_cancel_course" id="bulk_cancel_course" required>
                    <option value="">Select Course</option>
                    <?php
                    $query = "SELECT DISTINCT course FROM enrolled_users 
                         WHERE course IS NOT NULL AND course != '' 
                         AND status = 'Student'";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . $row['course'] . '">' . $row['course'] . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Select a Section:</label>
                <select name="bulk_cancel_section" id="bulk_cancel_section" required>
                    <option value="">Select Section</option>
                    <?php
                    $query = "SELECT DISTINCT section FROM enrolled_users 
                         WHERE section IS NOT NULL AND section != '' 
                         AND status = 'Student'";
                    $result = mysqli_query($con, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . $row['section'] . '">' . $row['section'] . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Select Appointment Date:</label>
                <input type="date" name="bulk_cancel_date" required>
            </div>

            <div class="form-group">
                <label>Reason for Cancellation:</label>
                <input type="text" name="bulk_cancel_reason" required>
            </div>

            <div class="button-group">
                <input type="submit" name="bulk_cancel_submit" value="Cancel Bulk Appointments">
                <button type="button" onclick="closeBulkCancel()" class="btn">Close</button>
            </div>
        </form>
    </dialog>

    <?php
    if (isset($_SESSION['success'])) {
    ?>
        <script>
            swal({
                title: "Success",
                text: "<?php echo $_SESSION['success']; ?>",
                icon: "success",
                button: "OK"
            }).then(() => {
                window.location.href = "manageAppointment.php";
            })
        </script>
    <?php
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error'])) {
    ?>
        <script>
            swal({
                title: "Error",
                text: "<?php echo $_SESSION['error']; ?>",
                icon: "error",
                button: "OK"
            }).then(() => {
                window.location.href = "manageAppointment.php";
            });
        </script>
    <?php
        unset($_SESSION['error']);
    }
    ?>

    <script>
        const dialog = document.getElementById("createAppointment")
        const approveDialog = document.getElementById("approve")
        const cancelDialog = document.getElementById("cancel")
        const reschedDialog = document.getElementById("resched")
        const bulkDialog = document.getElementById("bulkAppointment")

        function showCreateAppointment() {
            dialog.showModal()
        }

        function openApprove(id, date, startTime, endTime) {
            document.getElementById('appointment_id').value = id;
            document.getElementById('appointment_date').value = date;
            document.getElementById('start_time').value = startTime;
            document.getElementById('end_time').value = endTime;
            approveDialog.showModal();
        }

        function openCancel(id) {
            document.getElementById('cancel_appointment_id').value = id;
            cancelDialog.showModal();
        }

        function openResched(id) {
            document.getElementById('resched_appointment_id').value = id;
            reschedDialog.showModal();
        }

        function showBulkAppointment() {
            bulkDialog.showModal();
        }

        const bulkCancelDialog = document.getElementById("bulkCancel");

        function openBulkCancel() {
            bulkCancelDialog.showModal();
        }

        function closeBulkCancel() {
            bulkCancelDialog.close();
        }

        window.onload = function() {
            <?php
            if (!empty($approveErr) || !empty($approveStaffIDerr)) {
            ?>approveDialog.showModal();
            <?php
            }
            if (!empty($cancelErr)) {
            ?>cancelDialog.showModal();
            <?php
            }
            if (!empty($reschedDateErr) || !empty($reschedReasonErr)) {
            ?>reschedDialog.showModal();
            <?php
            }
            if (!empty($patientIDerr) || !empty($dateErr) || !empty($timeErr) || !empty($serviceErr) || !empty($statusErr) || !empty($Appresult)) {
            ?>dialog.showModal();
            <?php
            }

            if (!empty($bulkCourseErr) || !empty($bulkSectionErr) || !empty($bulkStaffErr) || !empty($bulkDateErr) || !empty($bulkTimeErr) || !empty($bulkServiceErr) || !empty($bulkStatusErr) || !empty($bulkAppresult) || !empty($bulkReasonErr)) {
            ?>bulkDialog.showModal();
            <?php
            }

            if (isset($_POST['bulk_cancel'])) {
            ?>bulkCancelDialog.showModal();
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

        function showStaffSchedule(staffId) {
            const scheduleDisplay = document.getElementById('staffScheduleDisplay');
            const scheduleBody = document.getElementById('scheduleBody');

            if (!staffId) {
                scheduleDisplay.style.display = 'none';
                return;
            }

            const allSchedules = JSON.parse(document.getElementById('allSchedulesData').textContent);

            const staffSchedules = allSchedules.filter(schedule => schedule.staff_id === staffId);

            scheduleBody.innerHTML = '';

            if (staffSchedules.length === 0) {
                scheduleBody.innerHTML = '<tr><td colspan="3">No schedule found for this staff member</td></tr>';
            } else {
                const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                staffSchedules.sort((a, b) => dayOrder.indexOf(a.day_of_week) - dayOrder.indexOf(b.day_of_week));

                staffSchedules.forEach(schedule => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                <td>${schedule.day_of_week}</td>
                <td>${formatTime(schedule.start_time)}</td>
                <td>${formatTime(schedule.end_time)}</td>
            `;
                    scheduleBody.appendChild(row);
                });
            }

            scheduleDisplay.style.display = 'block';
        }

        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }
    </script>
</body>

</html>