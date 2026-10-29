<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
}

include "../functions.php";

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
        } else {
            mysqli_rollback($con);
            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

$reschedErr = "";

if (isset($_POST['resched'])) {
    $appointmentID = $_POST['appointment_id'];
    $newDate = $_POST['date'];
    $reschedDate = strtotime($newDate);
    $currentDate = strtotime(date('Y-m-d'));

    if ($reschedDate <= $currentDate) {
        $reschedErr = "Date in the past or in the present cant be set";
    }

    $formattedDate = date('M d, Y', strtotime($newDate)) . ' (' . date('l', strtotime($newDate)) . ')';
    $reason = "Rescheduled on " . $formattedDate;

    if (empty($newDate)) {
        $reschedErr = "Please select a date";
    }
    if (empty($reschedErr)) {
        mysqli_begin_transaction($con);
        $query = "update appointments set status = 'Reschedule', status_reason = '$reason' where appointment_id = $appointmentID";
        if (mysqli_query($con, $query)) {
            mysqli_commit($con);
            $_SESSION['success'] = "Appointment Rescheduled successfully!";
        } else {
            mysqli_rollback($con);
            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

include "nav.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="cssStyles/viewAppointment.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>

<body>
    <div class="container">
        <div class="assignAppointment" id="assignedAppointment">
            <h1>
                <i class="fas fa-calendar-check"></i>
                Your Appointments
            </h1>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>Patient Status</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th class="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $adminID = $_SESSION['user_id'];
                        $query = "SELECT 
                                    a.appointment_id, a.patient_id,
                                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                                    p.status AS patient_status,
                                    a.appointment_date,
                                    t.start_time,
                                    t.end_time,
                                    s.service_name,
                                    a.status AS appointment_status
                                FROM appointments a
                                JOIN patients p ON a.patient_id = p.patient_id
                                JOIN clinic_services s ON a.services = s.service_id
                                LEFT JOIN time_blocks t ON a.time_block_id = t.time_block_id
                                WHERE a.staff_id = '$adminID' and a.status in ('Approved', 'Followup')
                                order by a.appointment_date asc";

                        $result = mysqli_query($con, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
                            $formattedDate = date('M d, Y', strtotime($row['appointment_date']));
                        ?>
                            <tr>
                                <td><?php echo $row['patient_id']; ?></td>
                                <td><?php echo $row['patient_name']; ?></td>
                                <td><?php echo $row['patient_status']; ?></td>
                                <td><?php echo $formattedDate; ?></td>
                                <td><?php echo $formattedTime; ?></td>
                                <td><?php echo $row['service_name']; ?></td>
                                <td><span class="status-badge <?php echo strtolower($row['appointment_status']); ?>"><?php echo $row['appointment_status']; ?></span></td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <button class="action-btn cancel-btn" data-tooltip="Cancel" onclick="showCancel('<?php echo $row['appointment_id']; ?>')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button class="action-btn reschedule-btn" data-tooltip="Reschedule" onclick="showResched('<?php echo $row['appointment_id']; ?>')">
                                            <i class="fas fa-calendar-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="viewAppointment">
            <h1>
                <i class="fas fa-users"></i>
                Others Appointments
            </h1>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>Assigned Staff</th>
                            <th>Patient Status</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $otherAppointmentsQuery = "SELECT 
                                                    a.patient_id,
                                                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                                                    CONCAT(cs.first_name, ' ', cs.last_name) AS staff_name,
                                                    p.status AS patient_status,
                                                    a.appointment_date,
                                                    t.start_time,
                                                    t.end_time,
                                                    s.service_name,
                                                    a.status AS appointment_status
                                                FROM appointments a
                                                JOIN patients p ON a.patient_id = p.patient_id
                                                JOIN clinic_staff cs ON a.staff_id = cs.staff_id
                                                JOIN clinic_services s ON a.services = s.service_id
                                                LEFT JOIN time_blocks t ON a.time_block_id = t.time_block_id
                                                WHERE a.staff_id != '$adminID' 
                                                AND a.status IN ('Approved', 'Followup')
                                                ORDER BY a.appointment_date asc";

                        $otherResult = mysqli_query($con, $otherAppointmentsQuery);

                        while ($row = mysqli_fetch_assoc($otherResult)) {
                            $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
                            $formattedDate = date('M d, Y', strtotime($row['appointment_date']));
                            $statusClass = strtolower($row['appointment_status']);
                        ?>
                            <tr>
                                <td><?php echo $row['patient_id']; ?></td>
                                <td><?php echo $row['patient_name']; ?></td>
                                <td><?php echo $row['staff_name']; ?></td>
                                <td><?php echo $row['patient_status']; ?></td>
                                <td><?php echo $formattedDate; ?></td>
                                <td><?php echo $formattedTime; ?></td>
                                <td><?php echo $row['service_name']; ?></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['appointment_status']; ?></span></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <dialog id="cancel">
        <h2>Cancel Appointment</h2>
        <form action="viewAppointment.php" method="post">
            <input type="hidden" name="appointment_id" id="cancel_appointment_id">
            <div class="form-group">
                <label>Provide Reason:</label>
                <input type="text" name="reason">
            </div>

            <?php errors($cancelErr) ?>

            <div class="button-group">
                <input type="submit" name="cancel" value="Cancel">
                <button type="button" onclick="closeCancel()" class="btn">Close</button>
            </div>
        </form>
    </dialog>

    <dialog id="resched">
        <h2>Resched Appointment</h2>

        <form action="viewAppointment.php" method="POST">
            <input type="hidden" name="appointment_id" id="resched_appointment_id">

            <div class="form-group">
                <label>Suggest Date:</label>
                <input type="date" name="date">
            </div>

            <?php errors($reschedErr) ?>

            <div class="button-group">
                <input type="submit" name="resched" value="Resched">
                <button type="button" onclick="closeResched()" class="btn">Cancel</button>
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
                window.location.href = "viewAppointment.php";
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
                window.location.href = "viewAppointment.php";
            });
        </script>
    <?php
        unset($_SESSION['error']);
    }
    ?>

    <script>
        const cancelDialog = document.getElementById("cancel");
        const reschedDialog = document.getElementById("resched");

        function showCancel(id) {
            document.getElementById('cancel_appointment_id').value = id;
            cancelDialog.showModal();
        }

        function showResched(id) {
            document.getElementById('resched_appointment_id').value = id;
            reschedDialog.showModal();
        }

        function closeCancel() {
            cancelDialog.close();
        }

        function closeResched() {
            reschedDialog.close();
        }

        window.onload = function() {
            <?php
            if (!empty($cancelErr)) {
            ?>cancelDialog.showModal();
            <?php
            }
            if (!empty($reschedErr)) {
            ?>reschedDialog.showModal();
        <?php
            }
        ?>
        }


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
    </script>
</body>

</html>