<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
}

include "../functions.php";

$serviceErr = $genErr = "";
$service = "";

if (isset($_POST['submit'])) {
    $service = $_POST['service'];

    $serviceErr = assignError($service, "Please enter a service", 0);

    $query = "select * from clinic_services where service_name = '$service'";
    if (mysqli_num_rows(mysqli_query($con, $query)) > 0) {
        $genErr = "Service already exists";
    }

    if (empty($serviceErr) && empty($genErr)) {
        $insert = "insert into clinic_services (service_name) values ('$service')";
        if (mysqli_query($con, $insert)) {
            $_SESSION['success'] = "Service added successfully!";
            header("Location: clinicServices.php");
            exit();
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

if (isset($_POST['update'])) {
    $service_id = (int)$_POST['service_id'];
    $service = $_POST['service'];

    $serviceErr = assignError($service, "Please enter a service", 0);

    $query = "select * from clinic_services where service_name = '$service' AND service_id != $service_id";
    if (mysqli_num_rows(mysqli_query($con, $query)) > 0) {
        $genErr = "Service already exists";
    }

    if (empty($serviceErr) && empty($genErr)) {
        $update = "update clinic_services set service_name = '$service' where service_id = $service_id";
        if (mysqli_query($con, $update)) {
            $_SESSION['success'] = "Service updated successfully!";
            header("Location: clinicServices.php");
            exit();
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($con);
        }
    }
}

if (isset($_GET['disable'])) {
    $id = (int)$_GET['disable'];
    $query = "update clinic_services set is_active = not is_active where service_id = $id";
    mysqli_query($con, $query);
    header("Location: clinicServices.php");
    exit();
}

include "nav.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Services Management</title>
    <link rel="stylesheet" href="cssStyles/clinicServices.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>

<body>
    <div class="main-content">
        <h1><i class="fas fa-procedures"></i> Clinic Services Management</h1>

        <div class="services-container">
            <div class="add-btn-container">
                <button class="add-btn" onclick="showForm()">
                    <i class="fas fa-plus"></i> Add New Service
                </button>
            </div>

            <div class="services-table">
                <table>
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "select * from clinic_services order by is_active desc";
                        $result = mysqli_query($con, $query);

                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                                <tr>
                                    <td><?php echo $row['service_name']; ?></td>
                                    <td class="<?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </td>
                                    <td>
                                        <button class="action-btn <?php echo $row['is_active'] ? 'btn-disable' : 'btn-enable'; ?>"
                                            onclick="window.location.href='clinicServices.php?disable=<?php echo $row['service_id']; ?>'">
                                            <?php echo $row['is_active'] ? 'Disable' : 'Enable'; ?>
                                        </button>
                                        <button class="action-btn btn-edit"
                                            onclick="openEditModal(<?php echo $row['service_id']; ?>, '<?php echo $row['service_name']; ?>')">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="3" class="no-services">No services available</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <dialog id="serviceForm" class="dialog">
        <div class="dialog-container">
            <h2>Add New Service</h2>
            <form method="post" action="clinicServices.php">
                <div class="form-group">
                    <label for="service">Service Name:</label>
                    <input type="text" id="service" name="service" placeholder="Enter service name" value="<?php echo $service; ?>">
                    <?php errors($serviceErr); ?>
                </div>

                <?php if (!empty($genErr) && !isset($_POST['update'])): ?>
                    <div class="error-message"><?php echo $genErr; ?></div>
                <?php endif; ?>

                <div class="button-group">
                    <button type="submit" name="submit" class="submit-btn">Add Service</button>
                    <button type="button" class="cancel-btn" onclick="closeForm()">Cancel</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="editDialog" class="dialog">
        <div class="dialog-container">
            <h2>Edit Service</h2>
            <form method="post" action="clinicServices.php">
                <input type="hidden" name="service_id" id="edit_service_id">
                <div class="form-group">
                    <label for="edit_service">Service Name:</label>
                    <input type="text" id="edit_service" name="service" placeholder="Enter service name">
                    <?php errors($serviceErr); ?>
                </div>

                <?php if (!empty($genErr) && isset($_POST['update'])): ?>
                    <div class="error-message"><?php echo $genErr; ?></div>
                <?php endif; ?>

                <div class="button-group">
                    <button type="submit" name="update" class="submit-btn">Update Service</button>
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
                window.location.href = "clinicServices.php";
            });
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
                window.location.href = "clinicServices.php";
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <script>
        const dialog = document.getElementById("serviceForm");
        const editDialog = document.getElementById("editDialog");

        function showForm() {
            dialog.showModal();
        }

        function closeForm() {
            dialog.close();
        }

        function openEditModal(id, serviceName) {
            document.getElementById('edit_service_id').value = id;
            document.getElementById('edit_service').value = serviceName;
            editDialog.showModal();
        }

        function closeEditForm() {
            editDialog.close();
        }

        window.onload = function() {
            <?php if (!empty($serviceErr) || !empty($genErr)): ?>
                <?php if (isset($_POST['update'])): ?>
                    openEditModal(<?php echo $_POST['service_id']; ?>, '<?php echo htmlspecialchars($service, ENT_QUOTES); ?>');
                <?php else: ?>
                    showForm();
                <?php endif; ?>
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