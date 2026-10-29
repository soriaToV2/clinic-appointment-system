<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../vendor/setasign/fpdf/fpdf.php');
include "../connection.php";

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$service_filter = isset($_GET['service_filter']) ? $_GET['service_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$id_filter = isset($_GET['id_filter']) ? $_GET['id_filter'] : '';
$staff_filter = isset($_GET['staff_filter']) ? $_GET['staff_filter'] : '';

$query = "select * from processed_appointments_view where 1=1";

if (!empty($start_date) && !empty($end_date)) {
    $query .= " and appointment_date between '$start_date' and '$end_date'";
} elseif (!empty($start_date)) {
    $query .= " and appointment_date >= '$start_date'";
} elseif (!empty($end_date)) {
    $query .= " and appointment_date <= '$end_date'";
}

if (!empty($service_filter)) {
    $query .= " and service_name = '$service_filter'";
}

if (!empty($status_filter)) {
    $query .= " and appointment_status = '$status_filter'";
}

if (!empty($id_filter)) {
    $query .= " and patient_id like '%$id_filter%'";
}

if (!empty($staff_filter)) {
    $query .= " and staff_name like '%$staff_filter%'";
}

if (isset($_POST['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="appointments_report_' . date('Y-m-d') . '.xls"');

    $export_query = $query;
    $result = mysqli_query($con, $export_query);

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta name=ProgId content=Excel.Sheet>';
    echo '<meta name=Generator content="Microsoft Excel 11">';
    echo '<style>';
    echo 'td { mso-number-format:\\@; }'; 
    echo '</style>';
    echo '</head>';
    echo '<body>';

    // Report Header
    echo '<table border="0" cellpadding="0" cellspacing="0" width="100%">';
    echo '<tr><td colspan="8" style="font-size:18px;font-weight:bold;text-align:center;padding:10px;">APPOINTMENTS REPORT</td></tr>';
    echo '<tr><td colspan="8" style="font-size:12px;text-align:center;padding-bottom:15px;">Generated on: ' . date('M d, Y') . '</td></tr>';
    echo '</table>';

    // Data Table
    echo '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="border-collapse:collapse;">';

    // Table Header
    echo '<tr style="background-color:#23325a;color:white;font-weight:bold;">';
    echo '<th style="width:80px;">ID</th>';
    echo '<th style="width:150px;">Patient Name</th>';
    echo '<th style="width:150px;">Assigned Staff</th>';
    echo '<th style="width:100px;">Patient Status</th>';
    echo '<th style="width:100px;">Date</th>';
    echo '<th style="width:120px;">Time</th>';
    echo '<th style="width:150px;">Service</th>';
    echo '<th style="width:100px;">Status</th>';
    echo '</tr>';

    // Data Rows 
    $rowCount = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
        $formattedDate = date('M d, Y', strtotime($row['appointment_date']));

        $bgColor = ($rowCount % 2 == 0) ? '#ffffff' : '#f2f2f2';

        echo '<tr style="background-color:' . $bgColor . ';">';
        echo '<td>' . $row['patient_id'] . '</td>';
        echo '<td>' . $row['patient_name'] . '</td>';
        echo '<td>' . ($row['staff_name'] === null ? 'N/A' : $row['staff_name']) . '</td>';
        echo '<td>' . $row['patient_status'] . '</td>';
        echo '<td>' . $formattedDate . '</td>';
        echo '<td>' . $formattedTime . '</td>';
        echo '<td>' . $row['service_name'] . '</td>';
        echo '<td>' . $row['appointment_status'] . '</td>';
        echo '</tr>';

        $rowCount++;
    }

    echo '</table>';
    echo '</body></html>';
    exit();
}

if (isset($_POST['export_pdf'])) {
    $result = mysqli_query($con, $query);

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();

    $pdf->SetFillColor(57, 106, 177); 
    $pdf->Rect(0, 0, 297, 30, 'F'); 

    $pdf->Image('../images/PLP LOGO.png', 10, 5, 20);

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetY(10); 
    $pdf->Cell(0, 10, 'APPOINTMENTS REPORT', 0, 1, 'C');

    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(30); 
    $pdf->Cell(0, 5, 'Alkalde Jose St.', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Kapasigan, Pasig City. Philippines', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated on: ' . date('M d, Y'), 0, 1, 'C');
    $pdf->Ln(8);

    $pdf->SetFont('Arial', '', 10);
    $filters = "";
    if (!empty($start_date)) $filters .= "From $start_date ";
    if (!empty($end_date)) $filters .= "To $end_date ";
    if (!empty($service_filter)) $filters .= "| Service: $service_filter ";
    if (!empty($status_filter)) $filters .= "| Status: $status_filter ";
    if (!empty($id_filter)) $filters .= "| Patient ID: $id_filter ";
    if (!empty($staff_filter)) $filters .= "| Staff: $staff_filter ";

    $pdf->SetDrawColor(180, 180, 180);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->MultiCell(0, 8, $filters, 1, 'L', true);
    $pdf->Ln(8);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->Cell(20, 10, 'ID', 1, 0, 'C', true);
    $pdf->Cell(43, 10, 'Patient Name', 1, 0, 'C', true);
    $pdf->Cell(43, 10, 'Assigned Staff', 1, 0, 'C', true);
    $pdf->Cell(33, 10, 'Patient Status', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Date', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Time', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'Service', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Status', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(255, 255, 255);
    if (mysqli_num_rows($result) > 0) {
        $fill = false;
        while ($row = mysqli_fetch_assoc($result)) {
            $fill = !$fill;
            $fillColor = $fill ? array(245, 245, 245) : array(255, 255, 255);
            $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);

            $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
            $formattedDate = date('M d, Y', strtotime($row['appointment_date']));

            $pdf->Cell(20, 10, $row['patient_id'], 1, 0, 'C', $fill);
            $pdf->Cell(43, 10, $row['patient_name'], 1, 0, 'L', $fill);
            $pdf->Cell(43, 10, $row['staff_name'] === null ? 'N/A' : $row['staff_name'], 1, 0, 'L', $fill);
            $pdf->Cell(33, 10, $row['patient_status'], 1, 0, 'C', $fill);
            $pdf->Cell(30, 10, $formattedDate, 1, 0, 'C', $fill);
            $pdf->Cell(40, 10, $formattedTime, 1, 0, 'C', $fill);
            $pdf->Cell(45, 10, $row['service_name'], 1, 0, 'L', $fill);
            $pdf->Cell(25, 10, $row['appointment_status'], 1, 1, 'C', $fill);
        }
    } else {
        $pdf->Cell(0, 10, 'No appointments found with the current filters', 1, 1, 'C');
    }

    $pdf->SetY(-15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Page ' . $pdf->PageNo(), 0, 0, 'C');

    $pdf->Output('D', 'appointments_report_' . date('Y-m-d') . '.pdf');
    exit();
}


$staff_query = "select distinct staff_name from processed_appointments_view where staff_name is not null";
$staff_result = mysqli_query($con, $staff_query);

include "nav.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cssStyles/report.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Appointment Reports</title>
</head>

<body>
    <div class="main-container">
        <div class="header-container">
            <h1><i class="fas fa-file"></i> Reports</h1>
            <div class="export-buttons">
                <form method="post" style="display: inline;">
                    <button type="submit" name="export_excel" class="export-btn excel-btn">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                </form>
                <form method="post" style="display: inline;">
                    <button type="submit" name="export_pdf" class="export-btn pdf-btn">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                </form>
            </div>
        </div>

        <div class="filter-container">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="id_filter">Patient ID</label>
                        <input type="text" id="id_filter" name="id_filter" placeholder="Enter patient ID" value="<?php echo $id_filter; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="staff_filter">Assigned Staff</label>
                        <select id="staff_filter" name="staff_filter">
                            <option value="">All Staff</option>
                            <?php while ($staff = mysqli_fetch_assoc($staff_result)): ?>
                                <option value="<?php echo $staff['staff_name']; ?>"
                                    <?php if ($staff_filter == $staff['staff_name']) echo 'selected'; ?>>
                                    <?php echo $staff['staff_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>


                    <div class="filter-group">
                        <label for="service_filter">Service</label>
                        <select id="service_filter" name="service_filter">
                            <option value="">All Services</option>
                            <?php
                            $services_query = "select * from clinic_services";
                            $services_result = mysqli_query($con, $services_query);
                            while ($service = mysqli_fetch_assoc($services_result)) {
                            ?>
                                <option value="<?php echo $service['service_name']; ?>"
                                    <?php if ($service_filter == $service['service_name']) echo 'selected'; ?>>
                                    <?php echo $service['service_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status_filter">Status</label>
                        <select id="status_filter" name="status_filter">
                            <option value="">All Statuses</option>
                            <option value="Reschedule">Reschedule</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Completed">Completed</option>
                            <option value="Followup">Followup</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="filter-btn">Apply Filters</button>
                    <a href="?" class="reset-btn">Reset Filters</a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient Name</th>
                        <th>Assigned Staff</th>
                        <th>Patient Status</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Service</th>
                        <th>Appointment Status</th>
                        <th>Status Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = mysqli_query($con, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $formattedTime = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
                            $formattedDate = date('M d, Y', strtotime($row['appointment_date']));
                    ?>
                            <tr>
                                <td><?php echo $row['patient_id']; ?></td>
                                <td><?php echo $row['patient_name']; ?></td>
                                <td><?php echo $row['staff_name'] === null ? 'N/A' : $row['staff_name']; ?></td>
                                <td><?php echo $row['patient_status']; ?></td>
                                <td><?php echo $formattedDate; ?></td>
                                <td><?php echo $formattedTime; ?></td>
                                <td><?php echo $row['service_name']; ?></td>
                                <td><?php echo $row['appointment_status']; ?></td>
                                <td><?php echo $row['status_reason'] === null ? 'N/A' : $row['status_reason']; ?></td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='9' style='text-align: center;'>No appointments found with the current filters</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
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