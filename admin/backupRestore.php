<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

include "../functions.php";

$alertType = '';
$alertTitle = '';
$alertText = '';

if (isset($_POST['backup'])) {
    backupDatabase($con);
    exit();
}

if (isset($_POST['restore']) && isset($_FILES['backup_file'])) {
    $result = restoreDatabase($con, $_FILES['backup_file']);
    if ($result === true) {
        $alertType = 'success';
        $alertTitle = 'Success';
        $alertText = 'Database restored successfully';
    } else {
        $alertType = 'error';
        $alertTitle = 'Error';
        $alertText = $result;
    }
}

include "nav.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cssStyles/backupRestore.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <title>Database Backup & Restore</title>
</head>

<body>
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Backup & Restore</h1>
        </div>

        <div class="backup-restore-container">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h2 class="card-title">Backup Database</h2>
                </div>
                <div class="card-body">
                    <p>Create a complete backup of your database. This will download an SQL file containing all your data.</p>
                </div>
                <div class="card-footer">
                    <form method="post">
                        <button type="submit" name="backup" class="btn btn-primary">
                            <i class="fas fa-download"></i> Backup Now
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-upload"></i>
                    </div>
                    <h2 class="card-title">Restore Database</h2>
                </div>
                <div class="card-body">
                    <p>Upload a previously created backup file to restore your database to a previous state.</p>
                    <div class="warning-text">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Warning: This will overwrite your current data!</span>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="post" enctype="multipart/form-data">
                        <input type="file" name="backup_file" class="file-input" accept=".sql" required>
                        <button type="submit" name="restore" class="btn btn-danger">
                            <i class="fas fa-history"></i> Restore Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    if ($alertType) {
    ?>
        <script>
            swal({
                title: "<?php echo $alertTitle; ?>",
                text: "<?php echo $alertText; ?>",
                icon: "<?php echo $alertType; ?>",
                button: "OK"
            }).then(() => {
                window.location.href = "backupRestore.php";
            });
        </script>
    <?php
    }
    ?>
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