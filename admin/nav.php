<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cssStyles/nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Clinic Navigation</title>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-content">
            <div class="user-info">
                <div class="user-icon">
                    <i id="adminAvatar" class="fas fa-user-circle"></i>
                </div>
                <h2><?php echo strtoupper($_SESSION['lastname']); ?></h2>
                <p>Clinic Staff</p>
            </div>

            <nav class="nav-links">
                <ul>
                    <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i> DASHBOARD</a></li>
                    <li class="dropdown">
                        <div class="dropdown-header">
                            <i class="fas fa-calendar-check"></i> APPOINTMENTS
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </div>
                        <ul class="dropdown-menu">
                            <li><a href="viewAppointment.php"><i class="fas fa-list"></i> View Appointments</a></li>
                            <li><a href="manageAppointment.php"><i class="fas fa-edit"></i> Manage Appointments</a></li>
                        </ul>
                    </li>
                    <li><a href="report.php"><i class="fas fa-chart-bar"></i> REPORTS</a></li>
                    <li><a href="systemSettings.php"><i class="fas fa-cogs"></i> SETTINGS</a></li>
                </ul>
            </nav>
        </div>

        <div class="logout-container">
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
        </div>
    </div>

    <script>
        const savedAvatar = localStorage.getItem('selectedAvatar');
        if (savedAvatar) {
            document.getElementById('adminAvatar').className = `fas ${savedAvatar}`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const dropdownHeaders = document.querySelectorAll('.dropdown-header');

            dropdownHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const dropdown = this.parentElement;
                    const dropdownMenu = this.nextElementSibling;
                    const icon = this.querySelector('.dropdown-icon');

                    document.querySelectorAll('.dropdown').forEach(d => {
                        if (d !== dropdown) {
                            d.classList.remove('active');
                            d.querySelector('.dropdown-menu').style.maxHeight = '0';
                            d.querySelector('.dropdown-icon').style.transform = 'rotate(0deg)';
                        }
                    });

                    dropdown.classList.toggle('active');

                    if (dropdown.classList.contains('active')) {
                        dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + 'px';
                        icon.style.transform = 'rotate(180deg)';
                    } else {
                        dropdownMenu.style.maxHeight = '0';
                        icon.style.transform = 'rotate(0deg)';
                    }
                });
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                        dropdown.querySelector('.dropdown-menu').style.maxHeight = '0';
                        dropdown.querySelector('.dropdown-icon').style.transform = 'rotate(0deg)';
                    });
                }
            });
        });
    </script>
</body>

</html>