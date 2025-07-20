<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../signin_admin.php');
    exit();
}

include("../../db_config.php");

// Get receipt ID from URL
$receipt_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (empty($receipt_id)) {
    die("No receipt ID provided");
}

// Fetch receipt data
$query = "SELECT r.*, p.first_name, p.last_name, p.dob, p.gender, 
                 s.first_name AS staff_first_name, s.last_name AS staff_last_name
          FROM receipt r
          JOIN patient p ON r.patient_id = p.patient_id
          JOIN staff s ON r.staff_id = s.staff_id
          WHERE r.receipt_id = '$receipt_id'";

$result = mysqli_query($conn, $query);
$receipt = mysqli_fetch_assoc($result);

if (!$receipt) {
    die("Receipt not found");
}

// Fetch associated services
$services_query = "SELECT st.service_name, st.service_fee 
                   FROM receipt_service_type rst
                   JOIN service_type st ON rst.service_type_id = st.service_type_id
                   WHERE rst.receipt_id = '$receipt_id'";
$services_result = mysqli_query($conn, $services_query);
$services = [];
while ($row = mysqli_fetch_assoc($services_result)) {
    $services[] = $row;
}

// Format date
$receipt_date = date('d/m/Y', strtotime($receipt['date']));
$dob = date('d/m/Y', strtotime($receipt['dob']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Receipt</title>
    <style>
        body {
            font-family: "Phetsarath", sans-serif;
            margin: 0;
            padding: 20px;
            color: #000;
            line-height: 1.5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
        }

        .header {
            margin-bottom: 20px;
            position: relative;
        }

        .header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: bold;
            text-align: left;
        }

        .header h1.english {
            font-weight: normal;
            margin-bottom: 30px;
        }

        .header h2 {
            font-size: 22px;
            margin: 5px 0;
            font-weight: bold;
            text-align: center;
        }

        .header h2.english {
            font-weight: normal;
        }

        .header-info {
            right: 0;
            top: 60px;
            text-align: right;
            font-size: 14px;
            margin-bottom: 50px;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            width: 230px;
            font-weight: bold;
        }

        .info-value {
            flex: 1;
            border-bottom: 1px dotted #000;
            padding-bottom: 2px;
        }

        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }

        .services-table th {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .services-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        .services-table td:last-child {
            text-align: right;
        }

        .total-section {
            margin-top: 20px;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
        }

        .remark-section {
            margin-top: 20px;
        }

        .remark-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .remark-content {
            border: 1px solid #000;
            min-height: 100px;
            padding: 10px;
            margin-top: 5px;
        }

        .staff-section {
            margin-top: 40px;
            text-align: right;
        }

        .staff-name {
            margin-top: 30px;
            text-decoration: underline;
        }

        .no-print {
            display: none;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }

            .container {
                padding: 0;
            }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Phetsarath:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>ສູນປິ່ນປົວ ແລະ ຮັກສາສຸຂະພາບຕາ</h1>
            <h1 class="english">Ophthalmology Center</h1>
            <h2>ໃບແຈ້ງຊຳລະ</h2>
            <h2 class="english">Receipt</h2>
            <div class="header-info">
                <div>ເລກທີ/No: <?php echo htmlspecialchars($receipt['receipt_id']); ?></div>
                <div>ວັນທີ່/Date: <?php echo $receipt_date; ?></div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-row">
                <div class="info-label">ລະຫັດຄົນເຈັບ/ID:</div>
                <div class="info-value"><?php echo htmlspecialchars($receipt['patient_id']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">ຊື່-ນາມສະກຸນ/Name-surname:</div>
                <div class="info-value"><?php echo htmlspecialchars($receipt['first_name'] . ' ' . $receipt['last_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">ວັນເດືອນປີເກີດ/Date of birth:</div>
                <div class="info-value"><?php echo $dob; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">ເພດ/Sex:</div>
                <div class="info-value"><?php echo htmlspecialchars($receipt['gender']); ?></div>
            </div>
        </div>

        <table class="services-table">
            <tr>
                <th>ລຳດັບ (N.o)</th>
                <th>ລາຍການ (Description)</th>
                <th>ຈຳນວນເງິນ (Amount)</th>
            </tr>
            <?php foreach ($services as $index => $service): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                    <td><?php echo number_format($service['service_fee'], 0); ?> LAK</td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($services)): ?>
                <tr>
                    <td colspan="3">ບໍ່ມີຂໍ້ມູນ/No data</td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="total-section">
            <div>ຍອດລວມ/Grand Total: <?php echo number_format($receipt['total_amount'], 0); ?> LAK</div>
        </div>

        <div class="remark-section">
            <div class="info-label">ໝາຍເຫດ/Remark:</div>
            <div class="remark-content"><?php echo nl2br(htmlspecialchars($receipt['remark'])); ?></div>
        </div>

        <div class="staff-section">
            <div>ຮັບເງິນໂດຍ/Received by</div>
            <div class="staff-name"><?php echo htmlspecialchars($receipt['staff_first_name'] . ' ' . $receipt['staff_last_name']); ?></div>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>