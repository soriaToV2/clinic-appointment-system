<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

include "../functions.php";

$patientID = $_SESSION['user_id'];
$dateErr = $timeErr = $serviceErr = $genErr = "";
$showDialog = false;

if (isset($_POST['submit'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service = $_POST['service'];
    $appointmentDate = strtotime($date);
    $currentDate = strtotime(date('Y-m-d'));

    $dayOfWeek = date('w', $appointmentDate);

    $timeErr = assignError($time, "Please select a time", 0);
    $serviceErr = assignError($service, "Please select a service", 0);
    $dateErr = assignError($date, "Please select a date", 0);

    if ($appointmentDate < $currentDate) {
        $genErr = "Date in the past cannot be booked";
    } elseif ($appointmentDate == $currentDate) {
        $genErr = "Date today cannot be booked";
    } elseif ($dayOfWeek == 0) {
        $genErr = "Sundays cant be booked";
    }

    if (empty($timeErr) && empty($serviceErr) && empty($dateErr) && empty($genErr)) {
        $genErr = insertAppointment($patientID, null, $date, $time, $service, 'Pending', null);
        if ($genErr === true) {
            $_SESSION['success'] = "APPOINTMENT CREATED SUCCESSFULLY!";
            header("Location: appointment.php");
            exit();
        } else {
            $showDialog = true;
        }
    } else {
        $showDialog = true;
    }
}

$reasonErr = "";
if (isset($_POST['cancel'])) {
    $appointmentID = (int) $_POST['cancel_appointment_id'];
    $reason = $_POST['reason'];

    $reasonErr = assignError($reason, "Please provide a reason", 0);

    if (empty($reasonErr)) {
        mysqli_begin_transaction($con);
        $query = "update appointments set status = 'Cancelled', status_reason = '$reason' where appointment_id = $appointmentID";
        if (mysqli_query($con, $query)) {
            mysqli_commit($con);
            $_SESSION['success'] = "Appointment Cancelled successfully!";
            header("Location: appointment.php");
            exit();
        } else {
            mysqli_rollback($con);
            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

$reschedErr = $reschedTime = $reschedDate = "";
if (isset($_POST['resched'])) {
    $reschedID = (int) $_POST['resched_appointment_id'];
    $time = $_POST['reschedTime'];
    $date = $_POST['reschedDate'];
    $rescheduleDate = strtotime($date);
    $currDate = strtotime(date('Y-m-d'));

    $dayOfWeek = date('w', $rescheduleDate);

    $reschedTime = assignError($time, "Please select a time", 0);
    $reschedDate = assignError($date, "Please select a date", 0);

    if ($rescheduleDate < $currDate) {
        $reschedErr = "Date in the past cannot be booked";
    } elseif ($reschedDate == $currDate) {
        $reschedErr = "Date today cannot be booked";
    } elseif ($dayOfWeek == 0) {
        $reschedErr = "Sundays cant be booked";
    }

    if (empty($reschedTime) && empty($reschedErr) && empty($reschedDate)) {
        mysqli_begin_transaction($con);
        $query = "update appointments set appointment_date = '$date', time_block_id = '$time', status= 'Pending', status_reason = NULL where appointment_id = $reschedID";
        if (mysqli_query($con, $query)) {
            mysqli_commit($con);
            $_SESSION['success'] = "Appointment Rescheduled successfully!";
            header("Location: appointment.php");
            exit();
        } else {
            mysqli_rollback($con);
            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

if (isset($_GET['delete'])) {
    $appointmentId = (int) $_GET['delete'];
    $query = "delete from appointments where appointment_id = '$appointmentId'";
    mysqli_query($con, $query);
    header("Location: appointment.php");
    exit();
}

include "nav.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Appointment</title>
    <link rel="stylesheet" href="cssStyles/appointment.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>

<body>
    <div class="main-content" id="mainContent">
        <div class="appointment-container">
            <h1><i class="fas fa-calendar-check"></i> APPOINTMENT</h1>

            <div class="action-bar">
                <button class="create-btn" onclick="showCreateAppointment()">
                    <i class="fas fa-plus-circle"></i> Create New Appointment
                </button>
            </div>

            <div class="table-container">
                <h2>Your Appointments</h2>
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Status Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT a.appointment_id, a.appointment_date, a.status, 
                                cs.first_name, cs.last_name, 
                                sv.service_name,
                                tb.start_time, tb.end_time, a.status_reason
                                FROM appointments a
                                LEFT JOIN clinic_staff cs ON a.staff_id = cs.staff_id
                                LEFT JOIN clinic_services sv ON a.services = sv.service_id
                                LEFT JOIN time_blocks tb ON a.time_block_id = tb.time_block_id
                                WHERE a.patient_id = '$patientID' 
                                AND a.status IN ('Pending', 'Approved', 'Reschedule', 'Followup')
                                ORDER BY a.appointment_date asc";
                        $result = mysqli_query($con, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            $formattedDate = date('M d, Y', strtotime($row['appointment_date']));
                            $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' .
                                date('h:i A', strtotime($row['end_time']));
                            $doctorName = ($row['first_name']) ? $row['first_name'] . ' ' . $row['last_name'] : 'Not assigned';
                        ?>
                            <tr>
                                <td><?php echo $formattedDate; ?></td>
                                <td><?php echo $formattedTime; ?></td>
                                <td><?php echo $doctorName; ?></td>
                                <td><?php echo $row['service_name']; ?></td>
                                <td><span class="status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span></td>
                                <td><?php echo empty($row['status_reason']) ? 'N/A' : $row['status_reason']; ?></td>
                                <td>
                                    <?php

                                    if ($row['status'] === 'Pending') {
                                    ?><button onclick="deleteAppointment('<?php echo $row['appointment_id']; ?>')">Delete</button><?php
                                                                                                                                } elseif ($row['status'] === 'Reschedule') {
                                                                                                                                    ?><button onclick="openResched('<?php echo $row['appointment_id']; ?>')">Reschedule</button><?php
                                                                                                                                                                                                                            } elseif ($row['status'] === 'Approved') {
                                                                                                                                                                                                                                ?><button onclick="openCancel('<?php echo $row['appointment_id']; ?>')">Cancel</button><?php
                                                                                                                                                                                                                            }
                                                                                                                                                                                                                        ?>
                                </td>
                            </tr>
                        <?php
                        }

                        if (mysqli_num_rows($result) == 0) {
                        ?>
                            <tr>
                                <td colspan="6" class="no-appointments">No appointments found</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="appointment-container">

            <div class="table-container">
                <h2>Cancelled Appointments</h2>
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Status Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cancelledQuery = "SELECT a.appointment_id, a.appointment_date, a.status, 
                      cs.first_name, cs.last_name, 
                      sv.service_name,
                      tb.start_time, tb.end_time, a.status_reason
                      FROM appointments a
                      LEFT JOIN clinic_staff cs ON a.staff_id = cs.staff_id
                      LEFT JOIN clinic_services sv ON a.services = sv.service_id
                      LEFT JOIN time_blocks tb ON a.time_block_id = tb.time_block_id
                      WHERE a.patient_id = '$patientID' 
                      AND a.status = 'Cancelled'
                      ORDER BY a.appointment_date DESC";
                        $cancelledResult = mysqli_query($con, $cancelledQuery);

                        while ($row = mysqli_fetch_assoc($cancelledResult)) {
                            $formattedDate = date('M d, Y', strtotime($row['appointment_date']));
                            $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' .
                                date('h:i A', strtotime($row['end_time']));
                            $doctorName = ($row['first_name']) ? $row['first_name'] . ' ' . $row['last_name'] : 'Not assigned';
                        ?>
                            <tr>
                                <td><?php echo $formattedDate; ?></td>
                                <td><?php echo $formattedTime; ?></td>
                                <td><?php echo $doctorName; ?></td>
                                <td><?php echo $row['service_name']; ?></td>
                                <td><span class="status-cancelled"><?php echo $row['status']; ?></span></td>
                                <td><?php echo $row['status_reason']; ?></td>
                            </tr>
                        <?php
                        }

                        if (mysqli_num_rows($cancelledResult) == 0) {
                        ?>
                            <tr>
                                <td colspan="6" class="no-appointments">No cancelled appointments found</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>


    </div>

    <dialog id="createAppointment" class="dialog">
        <h2>CREATE AN APPOINTMENT</h2>
        <form action="appointment.php" method="post">
            <label for="appointment-date">SELECT A DATE</label>
            <input type="date" id="appointment-date" name="date">

            <?php
            errors($dateErr);
            ?>

            <label for="appointment-time">SELECT A TIME</label>
            <select id="appointment-time" name="time">
                <option value="">Select a time</option>
                <?php
                $query = "select * from time_blocks where is_active = 1";
                $result = mysqli_query($con, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                    $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
                ?><option value="<?php echo $row['time_block_id']; ?>"><?php echo $formattedTime; ?></option><?php
                                                                                                            }
                                                                                                                ?>
            </select>

            <?php
            errors($timeErr);
            ?>

            <label for="appointment-service">SELECT A SERVICE</label>
            <select id="appointment-service" name="service">
                <option value="">Select a Service</option>
                <?php
                $query = "select * from clinic_services where is_active = 1";
                $result = mysqli_query($con, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                ?><option value="<?php echo $row['service_id']; ?>"><?php echo $row['service_name']; ?></option><?php
                                                                                                            }
                                                                                                                ?>
            </select>

            <?php
            errors($serviceErr);
            errors($genErr);
            ?>

            <div class="button-group">
                <button type="button" onclick="closeAppointment()">Cancel</button>
                <input type="submit" name="submit" value="Book Appointment">
            </div>
        </form>
    </dialog>

    <dialog id="cancelDialog">
        <h2>Cancel Appointment</h2>
        <form action="appointment.php" method="post">
            <input type="hidden" name="cancel_appointment_id" id="cancel_appointment_id">

            <label for="cancel-reason">Cancellation Reason</label>
            <textarea id="cancel-reason" name="reason" placeholder="Enter cancellation reason..." rows="4"></textarea>

            <?php errors($reasonErr) ?>

            <div class="button-group">
                <button type="button" onclick="closeCancel()">Close</button>
                <input type="submit" name="cancel" value="Cancel Appointment">
            </div>
        </form>
    </dialog>

    <dialog id="resched">
        <h2>Reschedule Appointment</h2>
        <form action="appointment.php" method="post">
            <input type="hidden" name="resched_appointment_id" id="resched_appointment_id">

            <label for="resched-date">Date</label>
            <input type="date" id="resched-date" name="reschedDate">

            <?php errors($reschedDate) ?>

            <label for="resched-time">Select A Time</label>
            <select id="resched-time" name="reschedTime">
                <option value="">Select a time</option>
                <?php
                $query = "select * from time_blocks where is_active = 1";
                $result = mysqli_query($con, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                    $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
                ?><option value="<?php echo $row['time_block_id']; ?>"><?php echo $formattedTime; ?></option><?php
                                                                                                            }
                                                                                                                ?>
            </select>

            <?php errors($reschedTime) ?>
            <?php errors($reschedErr) ?>

            <div class="button-group">
                <button type="button" onclick="closeResched()">Close</button>
                <input type="submit" value="Reschedule Appointment" name="resched">
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
            }).then(() => {
                window.location.href = "appointment.php";
            });
        </script>
    <?php
    }
    unset($_SESSION['success']);

    ?>

    <script>
        const mainContent = document.getElementById("mainContent");
        const dialog = document.getElementById("createAppointment");
        const cancelDialog = document.getElementById("cancelDialog");
        const reschedDialog = document.getElementById("resched");

        function showCreateAppointment() {
            dialog.showModal();
            mainContent.classList.add('blurred');
        }

        function closeAppointment() {
            dialog.close();
            mainContent.classList.remove('blurred');
        }

        dialog.addEventListener('close', function() {
            mainContent.classList.remove('blurred');
        });

        function openCancel(id) {
            document.getElementById('cancel_appointment_id').value = id;
            cancelDialog.showModal();
            mainContent.classList.add('blurred');
        }

        function closeCancel() {
            cancelDialog.close();
            mainContent.classList.remove('blurred');
        }

        cancelDialog.addEventListener('close', function() {
            mainContent.classList.remove('blurred');
        });

        function openResched(id) {
            document.getElementById('resched_appointment_id').value = id;
            reschedDialog.showModal();
            mainContent.classList.add('blurred');
        }

        function closeResched() {
            reschedDialog.close();
            mainContent.classList.remove('blurred');
        }

        reschedDialog.addEventListener('close', function() {
            mainContent.classList.remove('blurred');
        });

        window.onload = function() {
            <?php
            if ($showDialog) {
            ?>dialog.showModal();
            <?php
            }
            if (!empty($reasonErr)) {
            ?>cancelDialog.showModal();
            <?php
            }
            if (!empty($reschedErr) || !empty($reschedTime) || !empty($reschedDate)) {
            ?>reschedDialog.showModal();
        <?php
            }
        ?>
        };

        function deleteAppointment(appointmentId) {
            mainContent.classList.add('blurred');
            swal({
                title: "Are you sure?",
                text: "This will delete the existing appointment",
                icon: "warning",
                buttons: ["Cancel", "Yes"],
                dangerMode: true,
            }).then((confirm) => {
                mainContent.classList.remove('blurred');
                if (confirm) {
                    swal("Success", "Appointment marked as completed.", "success")
                        .then(() => {
                            window.location.href = `appointment.php?delete=${appointmentId}`;
                        });
                }
            });
        }
    </script>

</body>

</html>