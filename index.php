<?php
include 'conn.php';
session_start();

$message = '';
$emp_info = null;
$scanned_emp_id = $_SESSION['scanned_emp_id'] ?? null;

if (isset($_POST['barcode_scan_submit']) && !empty($_POST['emp_id_scan'])) {
    $scanned_emp_id = trim($_POST['emp_id_scan']);

    try {
        $stmt = sqlsrv_prepare($scalesConn, "SELECT EMPid, full_name, department, photo FROM employees WHERE EMPid = ?", [$scanned_emp_id]);
        if (!$stmt) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }
        sqlsrv_execute($stmt);
        $raw_emp_info = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($raw_emp_info) {
            $emp_info = [
                'emp_id' => $raw_emp_info['EMPid'],
                'emp_name' => $raw_emp_info['full_name'],
                'department' => $raw_emp_info['department'],
                'emp_image' => $raw_emp_info['photo']
            ];
            $_SESSION['emp_info'] = $emp_info;
            $_SESSION['scanned_emp_id'] = $scanned_emp_id;
        } else {
            $message = "<div class='alert alert-danger'>Employee ID '" . htmlspecialchars($scanned_emp_id) . "' not found.</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error fetching employee: " . $e->getMessage() . "</div>";
    }
}

if (isset($_POST['select_payment']) && in_array($_POST['payment_method'], ['Cash', 'Credit']) && isset($_SESSION['emp_info'])) {
    $payment_method = $_POST['payment_method'];
    $emp_info = $_SESSION['emp_info'];
    $scanned_emp_id = $emp_info['emp_id'];

    date_default_timezone_set('Asia/Colombo');
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    $transaction_datetime = date('Y-m-d H:i:s');

    $meal_type = null;
    $meal_amount = 0;

    if ($current_time >= '06:00:00' && $current_time <= '10:30:00') {
        $meal_type = 'Breakfast';
        $meal_amount = 20;
    } elseif ($current_time >= '11:00:00' && $current_time <= '15:00:00') {
        $meal_type = 'Lunch';
        $meal_amount = 40;
    } elseif ($current_time >= '17:00:00' && $current_time <= '21:00:00') {
        $meal_type = 'Dinner';
        $meal_amount = 0;
    }

    if ($meal_type) {
        try {
            $check_sql = "SELECT COUNT(*) as count FROM canteen_records WHERE emp_id = ? AND type = ? AND CAST(transaction_date AS DATE) = ?";
            $check_stmt = sqlsrv_prepare($canteenConn, $check_sql, [$scanned_emp_id, $meal_type, $current_date]);
            if (!$check_stmt) throw new Exception(print_r(sqlsrv_errors(), true));

            sqlsrv_execute($check_stmt);
            $row = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);
            $already_taken = $row['count'] ?? 0;

            if ($already_taken > 0) {
                $message = "<div class='alert alert-warning'>" . htmlspecialchars($emp_info['emp_name']) . " has already taken $meal_type today.</div>";
            } else {
                $insert_sql = "INSERT INTO canteen_records (emp_id, item_type, type, item_description, amount, transaction_date, payment_method)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = sqlsrv_prepare($canteenConn, $insert_sql, [
                    $scanned_emp_id,
                    $meal_type,
                    $meal_type,
                    "$meal_type recorded",
                    $meal_amount,
                    $transaction_datetime,
                    $payment_method
                ]);

                if (!$insert_stmt) throw new Exception(print_r(sqlsrv_errors(), true));
                sqlsrv_execute($insert_stmt);

                $display_amount = $meal_amount == 0 ? "FREE" : "LKR $meal_amount";
                $message = "<div class='alert alert-success'>$meal_type saved for " . htmlspecialchars($emp_info['emp_name']) . " with amount $display_amount via <strong>$payment_method</strong>.</div>";

                unset($_SESSION['scanned_emp_id']);
                unset($_SESSION['emp_info']);
                $emp_info = null;
                $scanned_emp_id = null;

                $autoRedirect = true;
            }
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Transaction failed: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Meal hours over. No transaction recorded.</div>";
    }
}
?>

<!-- The rest of the HTML stays unchanged -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Canteen Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
    background-image: url('bg.jpeg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    min-height: 100vh;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
}
        .container {
            background: #fff;
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
        .btn-lg {
            padding: 20px;
            font-size: 1.5rem;
            width: 100%;
            margin-bottom: 15px;
        }
		
		.animated-btn {
  background-color: #28a745;
  border: none;
  font-size: 1.8rem;          /* Much bigger text */
  padding: 1rem 2rem;         /* Bigger button size */
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border-radius: 0.75rem;     /* Rounded edges */
}

.animated-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(40, 167, 69, 0.6);
  text-decoration: none;
}


    </style>
</head>
<body>

<div class="container">
    <h2 class="mb-4 text-primary">Canteen Management System</h2>
	<a href="admin.php" class="btn btn-success animated-btn position-absolute top-0 start-0 m-3">Admin Dash</a>




    <?php if (!$emp_info): ?>
        <form method="POST" action="">
            <input type="text" name="emp_id_scan" class="form-control form-control-lg text-center mb-3" placeholder="Scan or Enter Employee ID" autofocus required>
            <input type="hidden" name="barcode_scan_submit" value="1">
            <button type="submit" class="btn btn-primary w-100">Scan</button>
        </form>
        <p class="mt-3 text-muted">Enter ID manually or scan barcode.</p>
		<a href="extra.php" class="btn btn-success btn-lg mt-3">+ Add Extras</a>

    <?php else: ?>
        <div class="emp-info-card">
            <h4>Employee Details</h4>
            <img src="../gritires/uploads/<?php echo htmlspecialchars($emp_info['emp_image']); ?>" 
                 onerror="this.onerror=null;this.src='https://placehold.co/150x150?text=No+Image';"
                 alt="Employee Image" class="emp-image">
            <h5 class="mt-2"><?php echo htmlspecialchars($emp_info['emp_name']); ?></h5>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($emp_info['emp_id']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($emp_info['department']); ?></p>
        </div>

        <form method="POST" class="mt-4">
            <input type="hidden" name="select_payment" value="1">
            <button type="submit" name="payment_method" value="Cash" class="btn btn-success btn-lg">CASH</button>
            <button type="submit" name="payment_method" value="Credit" class="btn btn-warning btn-lg">CREDIT</button>
        </form>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
    <!-- Modal Trigger -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();
            <?php if (!empty($autoRedirect)): ?>
            setTimeout(() => {
                window.location.href = "index.php";
            }, 4000);
            <?php endif; ?>
        });
    </script>

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="messageModalLabel">Transaction Notice</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-start">
            <?php echo $message; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
