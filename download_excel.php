<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Query semua data upload beserta username
$sql = "SELECT u.*, us.username FROM uploads u JOIN users us ON u.user_id = us.id ORDER BY us.username, u.uploaded_at DESC";
$result = $conn->query($sql);

$excelContent = "Username\tID\tFilename\tDeskripsi\tJenis\tUkuran\tTanggal\n";
while ($row = $result->fetch_assoc()) {
    $excelContent .= "{$row['username']}\t{$row['id']}\t{$row['filename']}\t{$row['description']}\t{$row['filetype']}\t{$row['filesize']}\t{$row['uploaded_at']}\n";
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=data_upload.xls");
echo $excelContent;
exit;
?>
