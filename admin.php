<?php
include 'conn.php';
session_start();

// Initialize variables
$bill_records = [];
$selected_emp = '';
$from = '';
$to = '';
$total_billed = 0;
$credit_total = 0;
$total_payment_entries = 0;
$outstanding = 0;
$success_message = '';
$message = '';

// Load employee list
$employees = [];
$emp_stmt = sqlsrv_query($scalesConn, "SELECT EMPid, full_name FROM employees ORDER BY full_name ASC");
while ($emp = sqlsrv_fetch_array($emp_stmt, SQLSRV_FETCH_ASSOC)) {
    $employees[] = $emp;
}

// Fetch and calculate records
function fetchBillRecords($canteenConn, $emp_id, $from, $to) {
    $bill_records = [];
    $total_billed = 0;
    $credit_total = 0;
    $payment_total = 0;

    $query = "SELECT transaction_date, type, item_description, amount, payment_method 
              FROM canteen_records 
              WHERE emp_id = ? AND transaction_date BETWEEN ? AND ?";
    $params = [$emp_id, $from . ' 00:00:00', $to . ' 23:59:59'];
    $stmt = sqlsrv_prepare($canteenConn, $query, $params);
    sqlsrv_execute($stmt);

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $bill_records[] = $row;

        if (strtolower(trim($row['item_description'])) !== 'outstanding payment') {
            $total_billed += $row['amount'];
        }

        if (strtolower($row['payment_method']) === 'credit') {
            $credit_total += $row['amount'];
        }

        if (strtolower($row['type']) === 'payment') {
            $payment_total += $row['amount'];
        }
    }

    return [
        'records' => $bill_records,
        'total_billed' => $total_billed,
        'credit_total' => $credit_total,
        'payment_total' => $payment_total,
        'outstanding' => $credit_total - $payment_total
    ];
}

// Handle bill filter
if (isset($_POST['filter_bill'])) {
    $selected_emp = $_POST['employee'] ?? '';
    $from = $_POST['from_date'] ?? '';
    $to = $_POST['to_date'] ?? '';

    if ($selected_emp && $from && $to) {
        $result = fetchBillRecords($canteenConn, $selected_emp, $from, $to);
        $bill_records = $result['records'];
        $total_billed = $result['total_billed'];
        $credit_total = $result['credit_total'];
        $total_payment_entries = $result['payment_total'];
        $outstanding = $result['outstanding'];
    }
}

// Handle payment
if (isset($_POST['make_payment'])) {
    $selected_emp = $_POST['pay_emp'];
    $from = $_POST['from_date'];
    $to = $_POST['to_date'];
    $pay_amount = floatval($_POST['pay_amount']);

    if ($pay_amount > 0 && $selected_emp) {
        $datetime = date('Y-m-d H:i:s');
        $insert_sql = "INSERT INTO canteen_records (emp_id, item_type, type, item_description, amount, transaction_date, payment_method)
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$selected_emp, 'Payment', 'Payment', 'Outstanding payment', $pay_amount, $datetime, 'Cash'];
        $insert_stmt = sqlsrv_prepare($canteenConn, $insert_sql, $params);

        if (sqlsrv_execute($insert_stmt)) {
            $success_message = "Payment of LKR " . number_format($pay_amount, 2) . " received!";
            // Re-fetch records
            $result = fetchBillRecords($canteenConn, $selected_emp, $from, $to);
            $bill_records = $result['records'];
            $total_billed = $result['total_billed'];
            $credit_total = $result['credit_total'];
            $total_payment_entries = $result['payment_total'];
            $outstanding = $result['outstanding'];
        } else {
            $message = "<div class='alert alert-danger'>Payment failed.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Canteen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('bg.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif;
            padding: 40px;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        h2 { font-weight: 700; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-2 text-primary">Admin Dashboard</h2>
    <div class="mb-3 text-end">
        <a href="index.php" class="btn btn-outline-secondary">⬅ Back to Main Page</a>
    </div>

    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Search Employee</label>
            <input type="text" id="searchInput" class="form-control mb-2" placeholder="Search by name or ID...">
            <select name="employee" id="employee-select" class="form-select" required>
                <option value="">-- Choose --</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['EMPid'] ?>" data-search="<?= strtolower($emp['full_name'] . ' ' . $emp['EMPid']) ?>" <?= $selected_emp === $emp['EMPid'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['full_name']) . " ({$emp['EMPid']})" ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">From</label>
            <input type="date" name="from_date" value="<?= $from ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">To</label>
            <input type="date" name="to_date" value="<?= $to ?>" class="form-control" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" name="filter_bill" class="btn btn-primary w-100">View Bill</button>
        </div>
    </form>

    <?php if ($bill_records): ?>
        <h5>Bill Summary</h5>
        <div id="bill-section">
            <table class="table table-bordered">
                <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount (LKR)</th><th>Method</th></tr></thead>
                <tbody>
                <?php foreach ($bill_records as $row): ?>
                    <tr>
                        <td><?= isset($row['transaction_date']) && $row['transaction_date'] instanceof DateTime
                            ? $row['transaction_date']->format('Y-m-d H:i')
                            : 'Invalid Date' ?></td>
                        <td><?= htmlspecialchars($row['type']) ?></td>
                        <td><?= htmlspecialchars($row['item_description']) ?></td>
                        <td><?= number_format($row['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($row['payment_method']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row mb-3">
                <div class="col"><strong>Total Billed:</strong> LKR <?= number_format($total_billed, 2) ?></div>
                <div class="col"><strong>Total Credit:</strong> LKR <?= number_format($credit_total, 2) ?></div>
                <div class="col"><strong>Total Paid:</strong> LKR <?= number_format($total_payment_entries, 2) ?></div>
                <div class="col"><strong>Outstanding:</strong> <span class="text-danger">LKR <?= number_format($outstanding, 2) ?></span></div>
            </div>
        </div>

        <form method="POST" class="row g-3 mb-4">
            <input type="hidden" name="pay_emp" value="<?= htmlspecialchars($selected_emp) ?>">
            <input type="hidden" name="from_date" value="<?= htmlspecialchars($from) ?>">
            <input type="hidden" name="to_date" value="<?= htmlspecialchars($to) ?>">
            <div class="col-md-4">
                <label class="form-label">Pay Amount</label>
                <input type="number" name="pay_amount" step="0.01" max="<?= max(0, $outstanding) ?>" class="form-control" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="make_payment" class="btn btn-success w-100">Submit Payment</button>
            </div>
        </form>

        <div class="d-flex gap-2">
            <button class="btn btn-secondary" onclick="window.print()">🖨️ Print</button>
            <button class="btn btn-info text-white" onclick="exportTableToExcel()">📥 Export to Excel</button>
            <a class="btn btn-success" target="_blank" href="https://wa.me/?text=<?= urlencode("Employee: $selected_emp\nBilled: LKR $total_billed\nCredit: LKR $credit_total\nPaid: LKR $total_payment_entries\nOutstanding: LKR $outstanding") ?>">📲 Send via WhatsApp</a>
        </div>
    <?php elseif ($selected_emp): ?>
        <p class="text-muted">No records found for the selected period.</p>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-center">
                    <div class="modal-body p-5">
                        <h1 class="text-success fw-bold display-4"><?= $success_message ?></h1>
                        <h3 class="text-danger mt-3">Current Outstanding: LKR <?= number_format($outstanding, 2) ?></h3>
                        <button type="button" class="btn btn-primary mt-4" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('employee-select');
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            Array.from(select.options).forEach(option => {
                const searchText = option.getAttribute('data-search') || '';
                option.style.display = searchText.includes(filter) ? '' : 'none';
            });
        });

        <?php if ($success_message): ?>
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
        <?php endif; ?>
    });

    function exportTableToExcel() {
        let table = document.querySelector("table");
        let html = table.outerHTML.replace(/ /g, '%20');
        let a = document.createElement('a');
        a.href = 'data:application/vnd.ms-excel,' + html;
        a.download = 'bill_report.xls';
        a.click();
    }
</script>
</body>
</html>
