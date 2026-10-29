<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
}
include "../functions.php";

$startTimeErr = $endTimeErr = $capacityErr = $genErr = $capacityEdit = "";

if (isset($_POST['submit'])) {
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $capacity = $_POST['capacity'];

    $startTimeErr = assignError($startTime, "Pls enter start time", 0);
    $endTimeErr = assignError($endTime, "Pls enter end time", 0);
    $capacityErr = assignError($capacity, "Pls enter capacity", 0);

    $query = "select * from time_blocks where start_time = '$startTime' and end_time = '$endTime'";
    if (mysqli_num_rows(mysqli_query($con, $query)) > 0) {
        $genErr = "Time block already exists";
    }

    if (empty($startTimeErr) && empty($endTimeErr) && empty($capacityErr) && empty($genErr)) {
        mysqli_begin_transaction($con);
        $query = "insert into time_blocks(start_time, end_time, capacity) values('$startTime', '$endTime', '$capacity')";
        if (mysqli_query($con, $query)) {
            mysqli_commit($con);
            $_SESSION['success'] = "Time block added successfully!";
            header("Location: timeBlock.php");
            exit();
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($con);
            mysqli_rollback($con);
        }
    }
}

if (isset($_GET['disable'])) {
    $timeblockID = (int) $_GET['disable'];
    mysqli_begin_transaction($con);
    $query = "update time_blocks set is_active = not is_active where time_block_id = '$timeblockID'";
    if (mysqli_query($con, $query)) {
        mysqli_commit($con);
    } else {
        mysqli_rollback($con);
    }
    unset($_GET['disable']);
    header("Location: timeBlock.php");
    exit();
}

if (isset($_GET['cancel'])) {
    $startTimeErr = $endTimeErr = $capacityErr = $genErr = $capacityEdit = "";
}

if (isset($_POST['update'])) {
    $time_block_id = $_POST['time_block_id'];
    $capacity = $_POST['capacity'];

    $capacityEdit = assignError($capacity, "Pls enter capacity", 0);

    if (empty($capacityEdit)) {
        mysqli_begin_transaction($con);
        $query = "update time_blocks set capacity = '$capacity' where time_block_id = '$time_block_id'";
        if (mysqli_query($con, $query)) {
            mysqli_commit($con);
            $_SESSION['success'] = "Time block updated successfully!";
            header("Location: timeBlock.php");
            exit();
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($con);
            mysqli_rollback($con);
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
    <title>Time Block Management</title>
    <link rel="stylesheet" href="cssStyles/timeBlock.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

</head>

<body>
    <div class="main-content">
        <h1><i class="fas fa-clock"></i> Manage Time Slots</h1>

        <div class="timeblocks-container">
            <div class="add-btn-container">
                <button class="add-btn" onclick="showForm()">
                    <i class="fas fa-plus"></i> Add New Time Slot
                </button>
            </div>

            <div class="timeblocks-table">
                <table>
                    <thead>
                        <tr>
                            <th>Time Block</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "select * from time_blocks order by is_active desc";
                        $result = mysqli_query($con, $query);

                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                                <tr>
                                    <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                                    <td><?php echo $row['capacity']; ?></td>
                                    <td><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                    <td>
                                        <button class="action-btn <?php echo $row['is_active'] ? 'btn-disable' : 'btn-enable'; ?>" onclick="window.location.href='timeBlock.php?disable=<?php echo $row['time_block_id']; ?>'"><?php echo $row['is_active'] ? 'Disable' : 'Enable'; ?></button>

                                        <button class="action-btn" onclick="openEdit('<?php echo $row['time_block_id']; ?>')"><i class="fas fa-edit"></i> Edit</button>
                                    </td>
                                </tr>
                            <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="5" class="no-blocks">No time blocks available</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <dialog id="timeblockForm">
        <div class="dialog-container">
            <h2>Add New Time Block</h2>
            <form action="timeBlock.php" method="POST">
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time">
                </div>

                <?php
                ?>
                <div class="error_message"><?php echo errors($startTimeErr); ?></div>
                <?php
                ?>

                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time">
                </div>

                <?php
                ?>
                <div class="error_message"><?php echo errors($endTimeErr); ?></div>
                <?php
                ?>

                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" min="1">
                </div>

                <?php
                ?>
                <div class="error_message"><?php echo errors($capacityErr); ?></div>
                <?php
                ?>

                <?php
                ?>
                <div class="error_message"><?php echo errors($genErr); ?></div>
                <?php
                ?>

                <div class="button-group">
                    <button type="submit" name="submit" class="submit-btn">Add Time Block</button>
                    <button type="button" class="cancel-btn" onclick="window.location.href='timeBlock.php?cancel=1'; closeForm()">Cancel</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="showEditDialog">
        <div class="dialog-container">
            <h2>Edit Time Slot Capacity</h2>
            <form action="timeBlock.php" method="post">
                <input type="hidden" name="time_block_id" id="time_block_id">

                <div class="form-group">
                    <label>Capacity:</label>
                    <input type="number" name="capacity">

                    <div class="error_message"><?php echo errors($capacityEdit); ?></div>
                </div>

                <div class="button-group">
                    <input type="submit" class="submit-btn" value="Update" name="update">
                    <button type="button" class="cancel-btn" onclick="window.location.href='timeBlock.php?cancel=1'; closeEditForm()">Cancel</button>
                </div>
            </form>
        </div>

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
            })
        </script>
    <?php
        unset($_SESSION['error']);
    }
    ?>

    <script>
        const dialog = document.getElementById("timeblockForm");
        const dialog2 = document.getElementById("showEditDialog");

        function showForm() {
            dialog.showModal();
        }

        function closeForm() {
            dialog.close();
        }

        function openEdit(id) {
            document.getElementById('time_block_id').value = id;
            dialog2.showModal();
        }

        function closeEditForm() {
            dialog2.close();
        }

        window.onload = function() {
            <?php
            if (!empty($startTimeErr) || !empty($endTimeErr) || !empty($capacityErr) || !empty($genErr)) {
            ?>
                dialog.showModal();
            <?php
            }

            if (!empty($capacityEdit)) {
            ?>
                dialog2.showModal();
            <?php
            }
            ?>
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