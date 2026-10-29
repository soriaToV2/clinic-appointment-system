<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Clinic Staff') {
    header("Location: ../index.php");
    exit();
}

include "../functions.php";

$dateErr = $reasonErr = $genErr = "";

if (isset($_POST['submit'])) {
    $date = $_POST['date'];
    $reason = $_POST['reason'];
    $appointmentDate = strtotime($date);
    $currentDate = strtotime(date('Y-m-d'));

    $dateErr = assignError($date, "Please select a date", 0);
    $reasonErr = assignError($reason, "Please enter a reason", 0);

    if ($appointmentDate < $currentDate) {
        $dateErr = "Date in the past cannot be set as unavailable";
    }

    if (empty($dateErr) && empty($reasonErr)) {
        $check = "select * from clinic_unavailability where unavailable_date = '$date'";
        $result = mysqli_query($con, $check);

        if (mysqli_num_rows($result) > 0) {
            $genErr = "The date you selected are already set as unavailable date!";
        } else {
            mysqli_begin_transaction($con);
            $insert = "insert into clinic_unavailability (unavailable_date, reason) values ('$date', '$reason')";
            $appointment = "update appointments set status = 'Cancelled', status_reason = '$reason' where status in ('Pending', 'Approved', 'Followup') and appointment_date = '$date' ";
            if (mysqli_query($con, $insert)) {
                mysqli_query($con, $appointment);
                mysqli_commit($con);
                $_SESSION['success'] = "Unavailable date added successfully!";
                header("Location: clinicAvailability.php");
                exit();
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($con);
                mysqli_rollback($con);
            }
        }
    }
}

if (isset($_POST['edit'])) {
    $date = $_POST['date'];
    $reason = $_POST['reason'];
    $query = "update clinic_unavailability set unavailable_date = '$date', reason = '$reason' where unavailable_date = '$date'";
    if (mysqli_query($con, $query)) {
        $_SESSION['success'] = "Unavailable date updated successfully!";
        header("Location: clinicAvailability.php");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($con);
    }
}


if (isset($_GET['delete'])) {
    $date = $_GET['delete'];
    $query = "delete from clinic_unavailability where unavailable_date = '$date'";
    mysqli_query($con, $query);
    header("Location: clinicAvailability.php");
    exit();
}
include "nav.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Availability Management</title>
    <link rel="stylesheet" href="cssStyles/clinicAvailability.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>

<body>
    <div class="main-content">
        <h1><i class="fas fa-calendar-times"></i> Clinic Unavailability Management</h1>
        <div class="availability-container">

            <div class="add-btn-container">
                <button class="add-btn" onclick="showUnavailableForm()">
                    <i class="fas fa-plus"></i> Add Unavailable Date
                </button>
            </div>

            <div class="unavailable-dates">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "select * from clinic_unavailability";
                        $result = mysqli_query($con, $query);

                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $formattedDate = date('M d, Y', strtotime($row['unavailable_date']));
                        ?>
                                <tr>
                                    <td><?php echo $formattedDate; ?></td>
                                    <td><?php echo $row['reason']; ?></td>
                                    <td>
                                        <button class="action-btn edit-btn" onclick="openEditModal('<?php echo $row['unavailable_date']; ?>', '<?php echo $row['reason']; ?>')"><i class="fas fa-edit"></i>Edit</button>
                                        <button class="action-btn delete-btn" onclick="window.location.href='clinicAvailability.php?delete=<?php echo $row['unavailable_date']; ?>'"><i class="fas fa-trash"></i>Delete</button>
                                    </td>
                                </tr>
                            <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="3" class="no-dates">No unavailable dates scheduled</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <dialog id="unavailableForm">
        <div class="dialog-container">
            <h2>Add Unavailable Date</h2>
            <form method="post" action="clinicAvailability.php">
                <div class="form-group">
                    <label for="date">Unavailable Date:</label>
                    <input type="date" id="date" name="date">
                    <?php errors($dateErr); ?>
                </div>

                <div class="form-group">
                    <label for="reason">Reason:</label>
                    <input type="text" id="reason" name="reason" placeholder="Enter reason">
                    <?php errors($reasonErr); ?>
                </div>

                <?php if (!empty($genErr)): ?>
                    <div class="error-message"><?php echo $genErr; ?></div>
                <?php endif; ?>

                <div class="button-group">
                    <button type="submit" name="submit" class="submit-btn">Add Date</button>
                    <button type="button" class="cancel-btn" onclick="closeUnavailableForm()">Cancel</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="editDialog">
        <div class="dialog-container">
            <h2>Edit Unavailable Date</h2>
            <form method="post" action="clinicAvailability.php">
                <div class="form-group">
                    <label for="date">Unavailable Date:</label>
                    <input type="date" id="edit_date" name="date">
                    <?php errors($dateErr); ?>
                </div>

                <div class="form-group">
                    <label for="reason">Reason:</label>
                    <input type="text" id="edit_reason" name="reason" placeholder="Enter reason">
                    <?php errors($reasonErr); ?>
                </div>

                <?php if (!empty($genErr)): ?>
                    <div class="error-message"><?php echo $genErr; ?></div>
                <?php endif; ?>

                <div class="button-group">
                    <button type="submit" name="edit" class="submit-btn">Edit</button>
                    <button type="button" class="cancel-btn" onclick="closeEditForm()">Cancel</button>

                </div>
            </form>
        </div>
    </dialog>

    <?php if (isset($_SESSION['success'])): ?>
        <script>
            swal({
                title: "Success",
                text: "<?php echo $_SESSION['success']; ?>",
                icon: "success",
                button: "OK"
            }).then(() => {
                window.location.href = "clinicAvailability.php";
            })
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <script>
            swal({
                title: "Error",
                text: "<?php echo $_SESSION['error']; ?>",
                icon: "error",
                button: "OK"
            }).then(() => {
                window.location.href = "clinicAvailability.php";
            });
        </script>
        <?php unset($_SESSION['error']);
        unset($_SESSION['success']); ?>
    <?php endif; ?>

    <script>
        const dialog = document.getElementById("unavailableForm");
        const editDialog = document.getElementById("editDialog");

        function showUnavailableForm() {
            dialog.showModal();
        }

        function closeUnavailableForm() {
            dialog.close();
        }

        function openEditModal(id, serviceName) {
            document.getElementById('edit_date').value = id;
            document.getElementById('edit_reason').value = serviceName;
            editDialog.showModal();
        }

        function closeEditForm() {
            editDialog.close();
        }

        window.onload = function() {
            <?php if (!empty($dateErr) || !empty($reasonErr) || !empty($genErr)): ?>
                dialog.showModal();
            <?php endif; ?>
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
    </script>
</body>

</html>