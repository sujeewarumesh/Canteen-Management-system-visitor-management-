<?php
include 'conn.php';
session_start();

$message = '';
date_default_timezone_set('Asia/Colombo');  // Set the timezone to Asia/Colombo

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_visitor'])) {
    $pass_id = $_POST['pass_id'] ?? null;
    $visitor_name = $_POST['visitor_name'] ?? null;
    $purpose_of_visit = $_POST['purpose_of_visit'] ?? null;

    if ($pass_id && $visitor_name) {
        try {
            // Get the current date and time in the Asia/Colombo timezone
            $current_date = date('Y-m-d');
            $current_time = date('H:i:s');
            $insert_sql = "INSERT INTO visitor_passes (pass_id, visitor_name, purpose_of_visit, date_of_visit, time_of_visit) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt = sqlsrv_prepare($canteenConn, $insert_sql, [
                $pass_id, $visitor_name, $purpose_of_visit, $current_date, $current_time
            ]);

            if (!$stmt) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }
            sqlsrv_execute($stmt);

            $message = "<div class='alert alert-success'>Visitor pass created for $visitor_name.</div>";
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Error creating visitor pass: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Please fill out all fields.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('bg.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;     
            justify-content: center; 
            padding: 15px; /* Prevents form from touching edges on small screens */
        }

        .container {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
        }

        .header-title {
            font-size: 2rem;
            color: #007bff;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-control-lg {
            font-size: 1rem;
        }

        .btn-lg {
            padding: 15px;
            font-size: 1.1rem;
        }

        .alert {
            font-size: 1rem;
            margin-top: 15px;
        }

        .input-field {
            margin-bottom: 1.2rem;
        }

        /* --- Mobile Responsiveness --- */
        @media (max-width: 768px) {
            .header-title {
                font-size: 1.5rem;
            }
            .btn-lg {
                font-size: 1rem;
                padding: 12px;
            }
            .form-control-lg {
                font-size: 0.95rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
                border-radius: 10px;
            }
            .header-title {
                font-size: 1.3rem;
            }
            .btn-lg {
                font-size: 0.95rem;
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="header-title">Create Visitor Pass</h2>

    <?php if (!empty($message)): ?>
        <div><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-field">
            <input type="text" name="pass_id" class="form-control form-control-lg" placeholder="Scan or Enter Visitor Pass ID" required>
        </div>
        <div class="input-field">
            <input type="text" name="visitor_name" class="form-control form-control-lg" placeholder="Enter Visitor's Name" required>
        </div>
        <div class="input-field">
            <input type="text" name="purpose_of_visit" class="form-control form-control-lg" placeholder="Enter Purpose of Visit">
        </div>
        <button type="submit" name="scan_visitor" class="btn btn-primary btn-lg w-100">Create Visitor Pass</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    window.onload = function () {
        const firstInput = document.querySelector('input[name="pass_id"]');
        if (firstInput) {
            firstInput.focus(); // Auto-focus on page load
        }

        // Keep focus on pass_id after form submission
        document.querySelector('form').addEventListener('submit', function () {
            setTimeout(() => {
                if (firstInput) firstInput.focus();
            }, 500);
        });
    };
</script>

</body>
</html>
