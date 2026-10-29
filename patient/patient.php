<?php
session_start();

if (!isset($_SESSION['role']) && $_SESSION['role'] !== 'Patient') {
    header("Location: ../index.php");
}
$patient_id = $_SESSION['user_id'];
include "../connection.php";
include "nav.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cssStyles/homepage.css">
</head>

<body>

    <div class="main-content">
        <div class="header-container">
            <div class="welcome-message">
                <h1>Welcome back, <span class="highlight"><?php echo $_SESSION['lastname']; ?></span>!</h1>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Upcoming Appointment</h3>
                    <?php
                    $upcomingQuery = "SELECT appointment_date, time_block_id 
                    FROM appointments 
                    WHERE patient_id = '$patient_id' 
                    AND status = 'Approved'
                    AND appointment_date >= CURDATE()
                    ORDER BY appointment_date ASC
                    LIMIT 1";

                    $upcomingResult = mysqli_query($con, $upcomingQuery);

                    if (mysqli_num_rows($upcomingResult) > 0) {
                        $upcoming = mysqli_fetch_assoc($upcomingResult);
                        $date = date('M j, Y', strtotime($upcoming['appointment_date']));

                        $timeBlockId = $upcoming['time_block_id'];
                        $timeQuery = "SELECT start_time FROM time_blocks WHERE time_block_id = $timeBlockId";
                        $timeResult = mysqli_query($con, $timeQuery);
                        $time = mysqli_fetch_assoc($timeResult)['start_time'];
                        $formattedTime = date('h:i A', strtotime($time));

                        echo "<p>$date at $formattedTime</p>";
                    } else {
                        echo "<p>No upcoming appointments</p>";
                    }
                    ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-file-medical"></i>
                </div>
                <div class="stat-info">
                    <h3>Recent Visits</h3>
                    <?php
                    $recentQuery = "SELECT COUNT(*) as count 
                    FROM appointments 
                    WHERE patient_id = '$patient_id' 
                    AND status = 'Completed'
                    ORDER BY appointment_date DESC
                    LIMIT 5";

                    $recentResult = mysqli_fetch_assoc(mysqli_query($con, $recentQuery));
                    $count = $recentResult['count'];

                    if ($count > 0) {
                        echo "<p>$count recent visits</p>";
                    } else {
                        echo "<p>No recent visits</p>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="section-title">
            <h2>Quick Actions</h2>
            <div class="divider"></div>
        </div>

        <div class="quick-actions">
            <div class="action-card" onclick="location.href='history.php'">
                <div class="card-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3>Appointment History</h3>
                <p>Review your past and upcoming visits</p>
                <div class="card-hover">
                    <span>View details <i class="fas fa-arrow-right"></i></span>
                </div>
            </div>

            <div class="action-card" onclick="location.href='appointment.php'">
                <div class="card-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h3>Schedule Appointment</h3>
                <p>Book a new consultation</p>
                <div class="card-hover">
                    <span>Schedule now <i class="fas fa-arrow-right"></i></span>
                </div>
            </div>

            <div class="action-card" onclick="location.href='information.php'">
                <div class="card-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <h3>Update Profile</h3>
                <p>Manage your personal information</p>
                <div class="card-hover">
                    <span>Update info <i class="fas fa-arrow-right"></i></span>
                </div>
            </div>
        </div>

        <div class="section-title">
            <h2>Recent Activity</h2>
            <div class="divider"></div>
        </div>

        <div class="recent-activity">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Service</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>

                    <?php

                    $query = "select a.appointment_date, tb.start_time, tb.end_time, s.service_name, a.status 
                      from appointments a
                      join time_blocks tb on a.time_block_id = tb.time_block_id
                      join clinic_services s on a.services = s.service_id
                      where a.patient_id = '$patient_id'
                      order by a.appointment_date desc, tb.start_time desc
                      limit 5";

                    $result = mysqli_query($con, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $formattedDate = date('M j, Y', strtotime($row['appointment_date']));
                            $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));

                            $statusClass = '';
                            switch ($row['status']) {
                                case 'Completed':
                                    $statusClass = 'status-completed';
                                    break;
                                case 'Approved':
                                    $statusClass = 'status-approved';
                                    break;
                                case 'Pending':
                                    $statusClass = 'status-pending';
                                    break;
                                case 'Cancelled':
                                    $statusClass = 'status-cancelled';
                                    break;
                                case 'Reschedule':
                                    $statusClass = 'status-reschedule';
                                    break;
                                case 'Followup':
                                    $statusClass = 'status-followup';
                                    break;
                            }
                    ?>
                            <tr>
                                <td><?php echo $formattedDate; ?></td>
                                <td><?php echo $formattedTime; ?></td>
                                <td><?php echo $row['service_name']; ?></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                            </tr>
                        <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="4" class="no-activity">No recent activity found</td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section-title">
            <h2>Announcements</h2>
            <div class="divider"></div>
        </div>

        <div class="announcements-container">
            <h3>Clinic Closure Notices</h3>

            <?php
            $unavailabilityQuery = "SELECT * FROM clinic_unavailability 
                           WHERE unavailable_date >= CURDATE()
                           ORDER BY unavailable_date ASC";
            $unavailabilityResult = mysqli_query($con, $unavailabilityQuery);

            if (mysqli_num_rows($unavailabilityResult) > 0) {
                while ($row = mysqli_fetch_assoc($unavailabilityResult)) {
                    $formattedDate = date('F j, Y (l)', strtotime($row['unavailable_date']));
            ?>
                    <div class="announcement-card">
                        <div class="announcement-icon bg-warning">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div class="announcement-content">
                            <h3>Clinic Closed on <?php echo $formattedDate; ?></h3>
                            <p><?php echo $row['reason']; ?></p>
                            <p class="announcement-date">Please schedule your appointments accordingly.</p>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<div class="no-announcements">No upcoming clinic closures announced.</div>';
            }
            ?>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.action-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>