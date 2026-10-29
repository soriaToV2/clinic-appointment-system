<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cssStyles/systemSettings.css">
    <link rel="stylesheet" href="cssStyles/darkMode.css">
    <link rel="stylesheet" href="cssStyles/nav.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>System Settings</title>
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="main-content">
        <div class="settings-container">
            <h1>System Settings</h1>

            <div class="settings-grid">
                <a href="clinicAvailability.php" class="settings-item">
                    <i class="fas fa-calendar-times"></i>
                    <span>Clinic Unavailability</span>
                </a>
                <a href="clinicServices.php" class="settings-item">
                    <i class="fas fa-procedures"></i> 
                    <span>Clinic Services</span>
                </a>
                <a href="createStaff.php" class="settings-item">
                    <i class="fas fa-user-plus"></i>
                    <span>User Management</span>
                </a>
                <a href="manageSchedule.php" class="settings-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Staff Schedules</span>
                </a>
                <a href="timeBlock.php" class="settings-item">
                    <i class="fas fa-clock"></i>
                    <span>Time Slots</span>
                </a>
                <a href="backupRestore.php" class="settings-item">
                    <i class="fas fa-database"></i>
                    <span>Backup/Restore</span>
                </a>
                <a href="chooseAvatar.php" class="settings-item">
                    <i class="fas fa-user-circle"></i>
                    <span>Change Avatar</span>
                </a>
                <div class="settings-item">
                    <i class="fas fa-moon"></i>
                    <span>Dark Mode</span>
                    <button id="darkModeButton" class="dark-mode-button">Enable</button>
                </div>
            </div>
        </div>
    </div>

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