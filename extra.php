<?php
include 'conn.php'; // includes $scalesConn and $canteenConn

session_start();
$message = '';
$emp_info = null;
$showModal = false;
$popupData = [
    'name' => '',
    'item' => '',
    'amount' => ''
];

if (isset($_POST['barcode_submit']) && !empty($_POST['emp_id_scan'])) {
    $scanned_emp_id = trim($_POST['emp_id_scan']);

    $stmt = sqlsrv_prepare($scalesConn, "SELECT EMPid, full_name, department, photo FROM employees WHERE EMPid = ?", [$scanned_emp_id]);
    if ($stmt && sqlsrv_execute($stmt)) {
        $emp_info = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$emp_info) {
            $message = "<div class='alert alert-danger'>Employee not found for ID: " . htmlspecialchars($scanned_emp_id) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Error fetching employee: " . print_r(sqlsrv_errors(), true) . "</div>";
    }
}

if (isset($_POST['submit_extras']) && !empty($_POST['emp_id_hidden'])) {
    date_default_timezone_set('Asia/Colombo');
    $emp_id = $_POST['emp_id_hidden'];
    $desc = trim($_POST['other_desc'] ?? '');
    $amount = floatval($_POST['other_amount'] ?? 0);
    $method = $_POST['other_method'] ?? 'Cash';
    $transaction_datetime = date('Y-m-d H:i:s');

    if (!empty($desc) && $amount > 0) {
        $insert_sql = "INSERT INTO canteen_records 
            (emp_id, item_type, type, item_description, amount, transaction_date, payment_method) 
            VALUES (?, 'Other', 'Other', ?, ?, ?, ?)";
        $insert_params = [$emp_id, $desc, $amount, $transaction_datetime, $method];

        $stmt = sqlsrv_prepare($canteenConn, $insert_sql, $insert_params);
        if ($stmt && sqlsrv_execute($stmt)) {
            // Reload employee info
            $stmt = sqlsrv_prepare($scalesConn, "SELECT EMPid, full_name, department, photo FROM employees WHERE EMPid = ?", [$emp_id]);
            if ($stmt && sqlsrv_execute($stmt)) {
                $emp_info = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            }

            $showModal = true;
            $popupData = [
                'name' => $emp_info['full_name'] ?? '',
                'item' => $desc,
                'amount' => number_format($amount, 2)
            ];
        } else {
            $message = "<div class='alert alert-danger'>Insert error: " . print_r(sqlsrv_errors(), true) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Please provide valid description and amount.</div>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Extras - Canteen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('bg.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .emp-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4 text-primary">Enter Extra Items</h2>

    <?php if ($message): ?>
        <div class="mb-3"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (!$emp_info): ?>
        <form method="POST" class="mb-4">
            <input type="text" name="emp_id_scan" class="form-control form-control-lg text-center mb-3" placeholder="Scan or Enter Employee ID" required autofocus>
            <input type="hidden" name="barcode_submit" value="1">
            <button type="submit" class="btn btn-primary w-100">Lookup Employee</button>
        </form>
    <?php else: ?>
        <div class="emp-info-card mb-4">
            <img src="../gritires/uploads/<?php echo htmlspecialchars($emp_info['photo']); ?>"
                 onerror="this.onerror=null;this.src='https://placehold.co/150x150?text=No+Image';"
                 class="emp-image" alt="Employee">
            <h5 class="mt-2"><?php echo htmlspecialchars($emp_info['full_name']); ?></h5>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($emp_info['EMPid']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($emp_info['department']); ?></p>
        </div>

        <form method="POST">
            <input type="hidden" name="emp_id_hidden" value="<?php echo htmlspecialchars($emp_info['EMPid']); ?>">
            <div class="mb-3 text-start">
                <label class="form-label">Item Description</label>
                <textarea name="other_desc" class="form-control" required></textarea>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Amount</label>
                <input type="number" name="other_amount" class="form-control" step="0.01" required>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Payment Method</label>
                <select name="other_method" class="form-select">
                    <option value="Cash">Cash</option>
                    <option value="Credit">Credit</option>
                </select>
            </div>
            <button type="submit" name="submit_extras" class="btn btn-primary w-100">Submit Extra</button>
            <a href="extra.php" class="btn btn-secondary w-100 mt-2">Scan Another</a>
            <a href="index.php" class="btn btn-outline-dark w-100 mt-2">Back to Main</a>
        </form>
    <?php endif; ?>

    <?php if ($showModal): ?>
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="successModalLabel">Saved Successfully</h5>
          </div>
          <div class="modal-body text-start">
            <p><strong><?php echo htmlspecialchars($popupData['name']); ?></strong></p>
            <p>Item: <?php echo htmlspecialchars($popupData['item']); ?></p>
            <p>Amount: LKR <?php echo htmlspecialchars($popupData['amount']); ?></p>
            <hr>
            <p>The entry has been saved. Redirecting to home...</p>
          </div>
        </div>
      </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
            setTimeout(() => {
                window.location.href = "index.php";
            }, 4000);
        });
    </script>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
