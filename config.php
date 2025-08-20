<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "kebun";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

// Force timezone PHP
date_default_timezone_set('Asia/Jakarta');
