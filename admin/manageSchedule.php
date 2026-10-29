<?php

session_start();
if ($_SESSION['role'] !== 'Clinic Staff') {
    header("Location: ../index.php");
    exit();
}

include "../functions.php";

if (isset($_POST['submit'])) {
    $staffID = $_POST['staff_id'];

    mysqli_begin_transaction($con);
    $success = true;

    try {
        $days = [
            'Monday' => ['mondayStart', 'mondayEnd'],
            'Tuesday' => ['tuesdayStart', 'tuesdayEnd'],
            'Wednesday' => ['wednesdayStart', 'wednesdayEnd'],
            'Thursday' => ['thursdayStart', 'thursdayEnd'],
            'Friday' => ['fridayStart', 'fridayEnd'],
            'Saturday' => ['saturdayStart', 'saturdayEnd']
        ];

        foreach ($days as $day => $times) {
            $startTime = $_POST[$times[0]];
            $endTime = $_POST[$times[1]];

            if (empty($startTime) && empty($endTime)) {
                continue;
            }

            if ($startTime >= $endTime) {
                throw new Exception("End time must be after start time for $day");
            }

            if (empty($err)) {
                $query = "insert into staff_schedules (staff_id, day_of_week, start_time, end_time) 
                     values ('$staffID', '$day', '$startTime', '$endTime')";

                if (!mysqli_query($con, $query)) {
                    throw new Exception("Error inserting schedule for $day: " . mysqli_error($con));
                }
            }
        }

        mysqli_commit($con);
        $_SESSION['success'] = "Schedule Added Successfully";
    } catch (Exception $e) {
        mysqli_rollback($con);
        $_SESSION['error'] = $e->getMessage();
        $success = false;
    }

    header("Location: manageSchedule.php");
    exit();
}

if (isset($_POST['edit'])) {
    $staffID = $_POST['staff_id'];

    mysqli_begin_transaction($con);
    $success = true;

    try {
        $deleteQuery = "delete from staff_schedules where staff_id = '$staffID'";
        if (!mysqli_query($con, $deleteQuery)) {
            throw new Exception("Error deleting existing schedules: " . mysqli_error($con));
        }

        $days = [
            'Monday' => ['mondayStart', 'mondayEnd'],
            'Tuesday' => ['tuesdayStart', 'tuesdayEnd'],
            'Wednesday' => ['wednesdayStart', 'wednesdayEnd'],
            'Thursday' => ['thursdayStart', 'thursdayEnd'],
            'Friday' => ['fridayStart', 'fridayEnd'],
            'Saturday' => ['saturdayStart', 'saturdayEnd']
        ];

        foreach ($days as $day => $times) {
            $startTime = $_POST[$times[0]];
            $endTime = $_POST[$times[1]];

            if (empty($startTime) && empty($endTime)) {
                continue;
            }

            if ($startTime >= $endTime) {
                throw new Exception("End time must be after start time for $day");
            }

            $query = "insert into staff_schedules (staff_id, day_of_week, start_time, end_time) 
                     values ('$staffID', '$day', '$startTime', '$endTime')";

            if (!mysqli_query($con, $query)) {
                throw new Exception("Error inserting schedule for $day: " . mysqli_error($con));
            }
        }

        mysqli_commit($con);
        $_SESSION['success'] = "Schedule updated successfully!";
    } catch (Exception $e) {
        mysqli_rollback($con);
        $_SESSION['error'] = $e->getMessage();
        $success = false;
    }

    header("Location: manageSchedule.php");
    exit();
}

if (isset($_GET['delete'])) {
    $unavailableID = (int) $_GET['delete'];
    mysqli_begin_transaction($con);
    $query = "delete from staff_unavailability where unavailability_id = '$unavailableID'";
    if (!mysqli_query($con, $query)) {
        mysqli_rollback($con);
        $_SESSION['error'] = "Error deleting data: " . mysqli_error($con);
    }
    mysqli_commit($con);
}

$staffIDErr = $dateErr = $reasonErr = "";
if (isset($_POST['submit_unavailable'])) {
    $staffID = $_POST['staff_id'];
    $date = $_POST['unavailable_date'];
    $reason = $_POST['reason'];
    $handleOption = $_POST['handle_appointments'] ?? 'cancel';
    $transferStaffID = $_POST['transfer_staff_id'] ?? null;

    $staffIDErr = assignError($staffID, "Please select a staff member", 0);
    $dateErr = assignError($date, "Please select a date", 0);
    $reasonErr = assignError($reason, "Please enter a reason", 0);

    if ($handleOption === 'transfer' && empty($transferStaffID)) {
        $_SESSION['error'] = "Please select a staff member to transfer appointments to";
        header("Location: manageSchedule.php");
        exit();
    }

    if (empty($staffIDErr) && empty($dateErr) && empty($reasonErr)) {
        mysqli_begin_transaction($con);
        $query = "insert into staff_unavailability (staff_id, unavailable_date, reason) 
                  values ('$staffID', '$date', '$reason')";

        if (!mysqli_query($con, $query)) {
            mysqli_rollback($con);
            $_SESSION['error'] = "Error inserting data: " . mysqli_error($con);
        } else {
            if ($handleOption === 'cancel') {
                $queryCancel = "update appointments 
                               set status = 'Cancelled', 
                                status_reason = CONCAT('Staff unavailable because he/she is : ', '$reason')
                               WHERE staff_id = '$staffID' 
                               AND appointment_date = '$date'
                               AND status IN ('Approved', 'Followup')";
                mysqli_query($con, $queryCancel);
                $_SESSION['success'] = "Staff unavailability set. All affected appointments have been cancelled.";
            } else {
                // Transfer appointments
                $queryTransfer = "update appointments 
                                set staff_id = '$transferStaffID',
                                    status_reason = CONCAT('Reassigned from ', '$staffID', ' due to unavailability')
                                WHERE staff_id = '$staffID' 
                                AND appointment_date = '$date'
                                AND status IN ('Approved', 'Followup')";
                mysqli_query($con, $queryTransfer);
                $_SESSION['success'] = "Staff unavailability set. All affected appointments have been transferred to " .
                    getStaffName($transferStaffID) . ".";
            }

            mysqli_commit($con);
        }
        header("Location: manageSchedule.php");
        exit();
    }
}

function getStaffName($staffID)
{
    global $con;
    $query = "SELECT first_name, last_name FROM clinic_staff WHERE staff_id = '$staffID'";
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['first_name'] . ' ' . $row['last_name'];
    }
    return "another staff";
}

$staffData = [];
$staffQuery = "select u.user_id, u.is_active, cs.staff_id, cs.first_name, cs.last_name, cs.specialty 
                from clinic_staff as cs
                join users u
                where cs.staff_id = u.user_id and u.is_active = 1";
$staffResult = mysqli_query($con, $staffQuery);
while ($row = mysqli_fetch_assoc($staffResult)) {
    $staffData[$row['staff_id']] = $row;
}

$scheduleData = [];
$schedQuery = "select staff_id, day_of_week, start_time, end_time from staff_schedules";
$schedResult = mysqli_query($con, $schedQuery);
while ($row = mysqli_fetch_assoc($schedResult)) {
    if (!isset($scheduleData[$row['staff_id']])) {
        $scheduleData[$row['staff_id']] = [];
    }
    $scheduleData[$row['staff_id']][strtolower($row['day_of_week'])] = [
        'start' => $row['start_time'],
        'end' => $row['end_time']
    ];
}

$unavailabilityData = [];
$unavailQuery = "SELECT su.unavailability_id, su.staff_id, su.unavailable_date, su.reason, 
                cs.first_name, cs.last_name
                FROM staff_unavailability su
                JOIN clinic_staff cs ON su.staff_id = cs.staff_id
                JOIN users u ON cs.staff_id = u.user_id
                WHERE u.is_active = 1
                ORDER BY su.unavailable_date DESC";
$unavailResult = mysqli_query($con, $unavailQuery);
while ($row = mysqli_fetch_assoc($unavailResult)) {
    $unavailabilityData[] = $row;
}

include "nav.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff Schedules</title>
    <link rel="stylesheet" href="cssStyles/manageSched.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>

<body>
    <div class="main-content">
        <h1><i class="fas fa-calendar-alt"></i> Manage Staff Schedules</h1>
        <div class="button-group">
            <button class="primary-btn" onclick="showForm()">Set Schedule</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Staff Name</th>
                        <th>Specialty</th>
                        <th>Schedules</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $staffQuery = "select u.user_id, u.is_active, cs.staff_id, cs.first_name, cs.last_name, cs.specialty 
                    from clinic_staff as cs
                    join users u
                    where u.user_id = cs.staff_id and u.is_active = 1";
                    $staffResult = mysqli_query($con, $staffQuery);

                    while ($staffRow = mysqli_fetch_assoc($staffResult)) {
                    ?>
                        <tr>
                            <td><?php echo $staffRow['first_name'] . ' ' . $staffRow['last_name']; ?></td>
                            <td><?php echo $staffRow['specialty']; ?></td>
                            <td>
                                <?php
                                $staffId = $staffRow['staff_id'];
                                $staffSchedQuery = "SELECT day_of_week, start_time, end_time FROM staff_schedules WHERE staff_id = '$staffId' ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
                                $schedResult = mysqli_query($con, $staffSchedQuery);

                                if (mysqli_num_rows($schedResult)) {
                                    while ($schedRow = mysqli_fetch_assoc($schedResult)) {
                                ?>
                                        <div class="schedule-item">
                                            <div><span class="day"><?php echo $schedRow['day_of_week'] ?></span></div>
                                            <span class="time"><?php echo date('h:i A', strtotime($schedRow['start_time'])) . ' - ' . date('h:i A', strtotime($schedRow['end_time'])) ?></span>
                                        </div>
                                    <?php
                                    }
                                } else {
                                    ?><span class="no-schedule">No Schedule Set</span><?php
                                                                                    }
                                                                                        ?>
                            </td>
                            <td>
                                <?php
                                if (mysqli_num_rows($schedResult)) {
                                ?>
                                    <button class="primary-btn" onclick="openEdit('<?php echo $staffRow['staff_id']; ?>')"><i class="fas fa-edit"></i> Edit</button>
                                <?php
                                }
                                ?>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <br>
        <h2><i class="fas fa-user-clock"></i> Staff Unavailability</h2>
        <div class="button-group">
            <button class="primary-btn" onclick="showForm2()">Set Unavailable Date</button>
        </div>
        <div class="unavail-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Staff Name</th>
                        <th>Specialty</th>
                        <th>Unavailable Date</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($unavailabilityData) > 0) {
                        foreach ($unavailabilityData as $unavailable) {
                    ?>
                            <tr>
                                <td><?php echo $unavailable['first_name'] . ' ' . $unavailable['last_name']; ?></td>
                                <td><?php echo $staffData[$unavailable['staff_id']]['specialty']; ?></td>
                                <td><?php echo date('M d, Y (l)', strtotime($unavailable['unavailable_date'])); ?></td>
                                <td><?php echo $unavailable['reason']; ?></td>
                                <td>
                                    <button class="primary-btn" onclick="confirmDelete('<?php echo $unavailable['unavailability_id']; ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No unavailability records found</td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <dialog id="showSchedDialog">
        <div class="dialog-content">
            <h2>Set Staff Schedule</h2>
            <form action="manageSchedule.php" method="post">
                <div class="form-group">
                    <label for="staff_id">Select Staff Member</label>
                    <select name="staff_id" id="staff_id" required>
                        <option value="">-- Select Staff --</option>
                        <?php
                        $staffQuery = "select cs.staff_id, cs.first_name, cs.last_name, cs.specialty
                            from clinic_staff cs
                            left join staff_schedules ss ON cs.staff_id = ss.staff_id
                            where ss.schedule_id IS NULL";
                        $staffResult = mysqli_query($con, $staffQuery);

                        while ($row = mysqli_fetch_assoc($staffResult)) {
                        ?>
                            <option value="<?php echo $row['staff_id'] ?>">
                                <?php echo $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['specialty'] . ')' ?>
                            </option>
                        <?php
                        }
                        ?>
                    </select>
                </div>

                <?php
                $days = [
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday'
                ];

                foreach ($days as $day => $dayName) {
                ?>
                    <div class="day-schedule">
                        <h3><?php echo $dayName; ?></h3>
                        <div class="time-inputs">
                            <div>
                                <label>Start Time</label>
                                <input type="time" name="<?php echo $day . 'Start'; ?>">
                            </div>
                            <div>
                                <label>End Time</label>
                                <input type="time" name="<?php echo $day . 'End'; ?>">
                            </div>
                            <div>
                                <button type="button" onclick="resetTimeFields(this)">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>

                <div class="form-actions">
                    <button type="submit" name="submit" class="submit-btn">Save Schedule</button>
                    <button type="button" class="cancel-btn" onclick="closeForm(1)">Cancel</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="showEditDialog">
        <div class="dialog-content">
            <h2>Edit Staff Schedule</h2>
            <form action="manageSchedule.php" method="post">
                <input type="hidden" name="staff_id" id="edit_staff_id">
                <div class="form-group">
                    <label for="staff_id">Staff Member: </label>
                    <span id="staff_name"></span>
                </div>

                <?php
                foreach ($days as $dayKey => $dayName) {
                ?>
                    <div class="day-schedule">
                        <h3><?php echo $dayName; ?></h3>
                        <div class="time-inputs">
                            <div>
                                <label>Start Time</label>
                                <input type="time" name="<?php echo $dayKey; ?>Start" id="<?php echo $dayKey; ?>Start">
                            </div>
                            <div>
                                <label>End Time</label>
                                <input type="time" name="<?php echo $dayKey; ?>End" id="<?php echo $dayKey; ?>End">
                            </div>
                            <div>
                                <button type="button" onclick="resetTimeFields(this)">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>

                <div class="form-actions">
                    <button type="submit" name="edit" class="submit-btn">Save Schedule</button>
                    <button type="button" class="cancel-btn" onclick="closeEdit()">Cancel</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="showUnavailableDialog">
        <div class="dialog-content">
            <h2>Set Staff Unavailability</h2>
            <form action="manageSchedule.php" method="post">
                <div class="form-group">
                    <label for="unavailable_staff_id">Select Staff Member</label>
                    <select name="staff_id" id="unavailable_staff_id">
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($staffData as $id => $staff): ?>
                            <option value="<?php echo $id; ?>" <?php echo (isset($_POST['staff_id']) && $_POST['staff_id'] == $id) ? 'selected' : ''; ?>>
                                <?php echo $staff['first_name'] . ' ' . $staff['last_name'] . ' (' . $staff['specialty'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php errors($staffIDErr) ?>
                </div>

                <div class="form-group">
                    <label for="unavailable_date">Unavailable Date</label>
                    <input type="date" name="unavailable_date" id="unavailable_date"
                        min="<?php echo date('Y-m-d'); ?>"
                        value="<?php echo $_POST['unavailable_date'] ?? ''; ?>">
                    <?php errors($dateErr) ?>
                </div>

                <div class="form-group">
                    <label for="reason">Reason</label>
                    <input type="text" name="reason" id="reason" value="<?php echo $_POST['reason'] ?? ''; ?>">
                    <?php errors($reasonErr) ?>
                </div>

                <div class="form-group">
                    <label>Handle existing appointments:</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="handle_appointments" value="cancel" checked>
                            Cancel appointments
                        </label>
                        <label>
                            <input type="radio" name="handle_appointments" value="transfer">
                            Transfer to another staff
                        </label>
                    </div>
                </div>

                <div class="form-group" id="transfer-staff-container" style="display: none;">
                    <label for="transfer_staff_id">Transfer to:</label>
                    <select name="transfer_staff_id" id="transfer_staff_id">
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($staffData as $id => $staff):
                            if ($id != ($_POST['staff_id'] ?? '')): ?>
                                <option value="<?php echo $id; ?>" <?php echo (isset($_POST['transfer_staff_id']) && $_POST['transfer_staff_id'] == $id) ? 'selected' : ''; ?>>
                                    <?php echo $staff['first_name'] . ' ' . $staff['last_name'] . ' (' . $staff['specialty'] . ')'; ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_unavailable" class="submit-btn">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeForm2()">Cancel</button>
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
        const staffData = <?php echo json_encode($staffData); ?>;
        const scheduleData = <?php echo json_encode($scheduleData); ?>;

        const dialog1 = document.getElementById("showSchedDialog");
        const dialog2 = document.getElementById("showEditDialog");
        const dialog3 = document.getElementById("showUnavailableDialog");

        function showForm() {
            dialog1.showModal();
        }

        function closeForm() {
            dialog1.close();
        }

        function openEdit(id) {
            document.getElementById('edit_staff_id').value = id;
            document.getElementById('staff_name').textContent =
                staffData[id].first_name + ' '.staffData[id].last_name;

            const timeInputs = dialog2.querySelectorAll('input[type="time"]');
            timeInputs.forEach(input => {
                input.value = '';
            });

            if (scheduleData[id]) {
                const staffSchedule = scheduleData[id];
                for (const day in staffSchedule) {
                    document.getElementById(day + 'Start').value = staffSchedule[day].start;
                    document.getElementById(day + 'End').value = staffSchedule[day].end;
                }
            }

            dialog2.showModal();
        }

        function closeEdit() {
            dialog2.close();
        }

        function showForm2() {
            dialog3.showModal();
        }

        function closeForm2() {
            dialog3.close();
        }

        function confirmDelete(id) {
            swal({
                title: "Are you sure?",
                text: "This will delete the existing data.",
                icon: "warning",
                buttons: ["Cancel", "Yes"],
                dangerMode: true,
            }).then((confirm) => {
                if (confirm) {
                    swal("Success", "Successfully Deleted.", "success")
                        .then(() => {
                            window.location.href = `manageSchedule.php?delete=${id}`;
                        });
                }
            });
        }

        function resetTimeFields(button) {
            const timeInputsDiv = button.closest('.time-inputs');
            const timeInputs = timeInputsDiv.querySelectorAll('input[type="time"]');
            timeInputs.forEach(input => {
                input.value = '';
            });
            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const transferRadios = document.querySelectorAll('input[name="handle_appointments"]');
            const transferContainer = document.getElementById('transfer-staff-container');

            transferRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    transferContainer.style.display = this.value === 'transfer' ? 'block' : 'none';
                });
            });

            <?php
            if (!empty($staffIDErr) || !empty($dateErr) || !empty($reasonErr)) {
                echo 'dialog3.showModal();';
                echo 'document.querySelector(\'input[name="handle_appointments"][value="' . ($_POST['handle_appointments'] ?? 'cancel') . '"]\').checked = true;';
                if (($_POST['handle_appointments'] ?? '') === 'transfer') {
                    echo 'transferContainer.style.display = "block";';
                }
            }
            ?>
        });

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