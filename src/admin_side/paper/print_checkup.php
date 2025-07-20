<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: signin_admin.php');
    exit();
}

include("../../db_config.php");

// Get checkup ID from URL
$checkup_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (empty($checkup_id)) {
    die("No checkup ID provided");
}

// Fetch checkup data
$query = "SELECT gc.*, p.first_name, p.last_name, p.dob, p.gender, 
                 s.first_name AS staff_first_name, s.last_name AS staff_last_name
          FROM general_checkup gc
          JOIN patient p ON gc.patient_id = p.patient_id
          JOIN staff s ON gc.staff_id = s.staff_id
          WHERE gc.checkup_id = '$checkup_id'";

$result = mysqli_query($conn, $query);
$checkup = mysqli_fetch_assoc($result);

if (!$checkup) {
    die("Checkup not found");
}

// Format date
$checkup_date = date('d/m/Y', strtotime($checkup['date']));
$dob = date('d/m/Y', strtotime($checkup['dob']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Checkup</title>
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

        .vital-signs {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }

        .vital-signs th {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .vital-signs td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
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
            <h2>ສະຫຼຸບຜົນກວດເບື້ອງຕົ້ນ</h2>
            <h2 class="english">General Checkup Paper</h2>
            <div class="header-info">
                <div>ໃບກວດເລກທີ/No: <?php echo htmlspecialchars($checkup['checkup_id']); ?></div>
                <div>ວັນທີ່/Date: <?php echo $checkup_date; ?></div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-row">
                <div class="info-label">ລະຫັດຄົນເຈັບ/ID:</div>
                <div class="info-value"><?php echo htmlspecialchars($checkup['patient_id']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">ຊື່-ນາມສະກຸນ/Name-surname:</div>
                <div class="info-value"><?php echo htmlspecialchars($checkup['first_name'] . ' ' . $checkup['last_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">ວັນເດືອນປີເກີດ/Date of birth:</div>
                <div class="info-value"><?php echo $dob; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">ເພດ/Sex:</div>
                <div class="info-value"><?php echo htmlspecialchars($checkup['gender']); ?></div>
            </div>
        </div>

        <table class="vital-signs">
            <tr>
                <th>ນໍ້າໜັກ/Weight (kg)</th>
                <th>ລວງສູງ/Height (cm)</th>
                <th>ອຸນຫະພູມ/Temperature (Celsius)</th>
                <th>ກໍາມະຈອນ/Pulse (min)</th>
                <th>ຄວາມດັນເລືອດ/Blood pressure (mmHg)</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($checkup['weight']); ?></td>
                <td><?php echo htmlspecialchars($checkup['height']); ?></td>
                <td><?php echo htmlspecialchars($checkup['temperature']); ?></td>
                <td><?php echo htmlspecialchars($checkup['pulse']); ?></td>
                <td><?php echo htmlspecialchars($checkup['blood_pressure']); ?></td>
            </tr>
        </table>

        <div class="remark-section">
            <div class="info-label">ລະລາຍອຽດ/Remark:</div>
            <div class="remark-content"><?php echo nl2br(htmlspecialchars($checkup['remark'])); ?></div>
        </div>

        <div class="staff-section">
            <div>ກວດໂດຍ/Staff</div>
            <div class="staff-name"><?php echo htmlspecialchars($checkup['staff_first_name'] . ' ' . $checkup['staff_last_name']); ?></div>
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