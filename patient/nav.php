<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cssStyles/nav.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Clinic Navigation</title>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-content">
            <div class="user-info">
                <div class="user-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2><?php echo strtoupper($_SESSION['lastname']); ?></h2>
            </div>

            <nav class="nav-links">
                <ul>
                    <li><a href="patient.php"><i class="fas fa-home"></i> HOME</a></li>
                    <li><a href="history.php"><i class="fas fa-history"></i> YOUR HISTORY</a></li>
                    <li><a href="appointment.php"><i class="fas fa-calendar-check"></i> APPOINTMENT</a></li>
                    <li><a href="information.php"><i class="fas fa-user-edit"></i> YOUR INFORMATION</a></li>
                </ul>
            </nav>
        </div>

        <div class="logout-container">
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
        </div>
    </div>
</body>

</html>