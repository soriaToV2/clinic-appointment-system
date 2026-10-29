<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
}
include "../connection.php";

$today = date('Y-m-d');
$todaysAppointment = 0;
$pendingAppointment = 0;
$assignedAppointment = 0;
$staff_id = $_SESSION['user_id'];

$query = "select count(*) as count from appointments where appointment_date = '$today' and status in ('Approved', 'Followup')";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);
$todaysAppointment = $row['count'];

$query = "select count(*) as count from appointments where status = 'Pending'";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);
$pendingAppointment = $row['count'];

$query = "select count(*) as count from appointments where staff_id = '$staff_id' and appointment_date >= '$today' and status in ('Approved', 'Followup')";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);
$assignedAppointment = $row['count'];

if (isset($_GET['complete'])) {
    $appointmentId = (int) $_GET['complete'];
    $query = "update appointments set status = 'Completed' where appointment_id = '$appointmentId'";
    mysqli_query($con, $query);
    header("Location: admin.php#todays-appointments");
    exit();
}

if (isset($_POST['cancel_appointment'])) {
    $appointmentId = (int) $_POST['appointment_id'];
    $reason = $_POST['cancel_reason'];

    $query = "update appointments set status = 'Cancelled', status_reason = '$reason' where appointment_id = '$appointmentId'";

    mysqli_query($con, $query);
    $_SESSION['success'] = "Appointment cancelled successfully!";
    header("Location: admin.php#todays-appointments");
    exit();
}

include "nav.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cssStyles/dashboard.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <title>Clinic Dashboard</title>
</head>

<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <p>Welcome back, <?php echo $_SESSION['lastname']; ?>!</p>
        </div>

        <div class="summary-cards">
            <div class="card">
                <div class="card-icon" style="background-color: #3498db;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="card-info">
                    <h3>Today's Appointments</h3>
                    <p><?php echo $todaysAppointment; ?></p>
                </div>
                <a href="#todays-appointments" class="card-link">View All</a>
            </div>

            <div class="card">
                <div class="card-icon" style="background-color: #e74c3c;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-info">
                    <h3>Pending Appointments</h3>
                    <p><?php echo $pendingAppointment; ?></p>
                </div>
                <a href="manageAppointment.php" class="card-link">Manage</a>
            </div>

            <div class="card">
                <div class="card-icon" style="background-color: #2ecc71;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="card-info">
                    <h3>Your Assigned Appointments</h3>
                    <p><?php echo $assignedAppointment; ?></p>
                </div>
                <a href="viewAppointment.php#assignedAppointment" class="card-link">View Yours</a>
            </div>
        </div>

        <div class="content-section quick-actions">
            <div class="section-header">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div class="action-buttons-container">
                <div class="action-buttons-grid">
                    <a href="manageAppointment.php" class="action-btn">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Create New Appointment</span>
                    </a>
                    <a href="clinicAvailability.php" class="action-btn">
                        <i class="fas fa-calendar-times"></i>
                        <span>Set Clinic Unavailable Dates</span>
                    </a>
                    <a href="viewAppointment.php" class="action-btn">
                        <i class="fas fa-list"></i>
                        <span>View All Appointments</span>
                    </a>
                    <a href="timeBlock.php" class="action-btn">
                        <i class="fas fa-clock"></i>
                        <span>Manage Time Slots</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="appointments-section" id="todays-appointments">
            <h2><i class="fas fa-calendar-day"></i> Today's Appointments</h2>
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Assigned Staff</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "select 
                    a.appointment_id,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    cs.service_name,
                    CONCAT(s.first_name, ' ', s.last_name) as staff_name,
                    t.start_time, t.end_time,
                    a.appointment_date,
                    a.status
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.patient_id
                    JOIN clinic_services cs ON a.services = cs.service_id
                    LEFT JOIN clinic_staff s ON a.staff_id = s.staff_id
                    LEFT JOIN time_blocks t ON a.time_block_id = t.time_block_id
                    WHERE a.appointment_date = '$today'
                    AND a.status in ('Approved', 'Followup')
                    ORDER BY t.start_time ASC";
                    $result = mysqli_query($con, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
                    ?>
                            <tr>
                                <td><?php echo $row['patient_name']; ?></td>
                                <td><?php echo $row['service_name']; ?></td>
                                <td><?php echo $row['staff_name']; ?></td>
                                <td><?php echo $formattedTime; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-complete"
                                            onclick="confirmComplete(<?php echo $row['appointment_id']; ?>)">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                        <button class="btn-cancel"
                                            onclick="openCancelModal(<?php echo $row['appointment_id']; ?>)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: #7f8c8d;">
                                <i class="fas fa-calendar-check" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                No appointments scheduled for today
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <dialog id="cancelDialog">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Cancel Appointment</h3>
            </div>
            <form method="POST" action="admin.php">
                <div class="modal-body">
                    <input type="hidden" name="appointment_id" id="cancelAppointment_id">
                    <div class="form-group">
                        <label for="cancel_reason">
                            <i class="fas fa-comment-alt"></i> Provide the reason for cancellation:
                        </label>
                        <textarea name="cancel_reason" id="cancel_reason"
                            placeholder="Enter cancellation reason..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeCancelModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="submit" name="cancel_appointment" class="btn-primary">
                        <i class="fas fa-check"></i> Confirm Cancellation
                    </button>
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
                    window.location.href = "admin.php#todays-appointments";
                });
            </script>
        <?php
            unset($_SESSION['success']);
        }
        ?>

        <script>
            const cancelDialog = document.getElementById("cancelDialog");

            function openCancelModal(id) {
                document.getElementById('cancelAppointment_id').value = id;
                cancelDialog.showModal();
            }

            function closeCancelModal() {
                cancelDialog.close();
            }

            function confirmComplete(appointmentId) {
                swal({
                    title: "Are you sure?",
                    text: "This will mark the appointment as completed.",
                    icon: "info",
                    buttons: ["Cancel", "Yes"],
                    dangerMode: true,
                }).then((confirm) => {
                    if (confirm) {
                        swal("Success", "Appointment marked as completed.", "success")
                            .then(() => {
                                window.location.href = `admin.php?complete=${appointmentId}`;
                            });
                    }
                });
            }
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
        </script>
</body>

</html>