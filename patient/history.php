<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

include "nav.php";
require "../connection.php";

$patientID = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History</title>
    <link rel="stylesheet" href="cssStyles/history.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="main-content">
        <div class="history-container">
            <h1><i class="fas fa-history"></i> APPOINTMENT HISTORY</h1>

            <div class="table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Assigned Staff</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT a.appointment_date, a.status,
                                 cs.first_name, cs.last_name, 
                                 sv.service_name,
                                 tb.start_time, tb.end_time
                                 FROM appointments a
                                 LEFT JOIN clinic_staff cs ON a.staff_id = cs.staff_id
                                 LEFT JOIN clinic_services sv ON a.services = sv.service_id
                                 LEFT JOIN time_blocks tb ON a.time_block_id = tb.time_block_id
                                 WHERE a.patient_id = '$patientID' 
                                 AND a.status = 'Completed'
                                 ORDER BY a.appointment_date DESC";
                        $result = mysqli_query($con, $query);

                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $formattedDate = date('M d, Y', strtotime($row['appointment_date']));
                                $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
                                $doctorName = ($row['first_name']) ? $row['first_name'] . ' ' . $row['last_name'] : 'Not specified';
                            ?>
                                <tr>
                                    <td><?php echo $formattedDate; ?></td>
                                    <td><?php echo $formattedTime; ?></td>
                                    <td><?php echo $doctorName; ?></td>
                                    <td><?php echo $row['service_name']; ?></td>
                                    <td><?php echo $row['status']; ?></td>

                                </tr>
                            <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="5" class="no-history">No completed appointments found</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>