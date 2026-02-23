<?php
$centralServer = '172.20.245.28\SQLEXPRESS';
$centralUID = 'sa';
$centralPWD = 'Abc123@#';

$canteen_dbname = "canteen_db";
$scales_dbname = "scales_gri";

// Create SQL Server connection for scales_gri
$scalesConn = sqlsrv_connect($centralServer, [
    "Database" => $scales_dbname,
    "Uid" => $centralUID,
    "PWD" => $centralPWD,
    "CharacterSet" => "UTF-8"
]);

if (!$scalesConn) {
    die("scales_gri DB connection failed: " . print_r(sqlsrv_errors(), true));
}

// Create SQL Server connection for canteen_db
$canteenConn = sqlsrv_connect($centralServer, [
    "Database" => $canteen_dbname,
    "Uid" => $centralUID,
    "PWD" => $centralPWD,
    "CharacterSet" => "UTF-8"
]);

if (!$canteenConn) {
    die("canteen_db DB connection failed: " . print_r(sqlsrv_errors(), true));
}
?>
