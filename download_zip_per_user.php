<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$zipName = "uploads_per_user_" . date("Y-m-d_H-i-s") . ".zip";
$zip = new ZipArchive();
if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Gagal membuat file ZIP.");
}

// Ambil data upload dan user
$sql = "SELECT u.*, us.username FROM uploads u JOIN users us ON u.user_id = us.id ORDER BY us.username, u.uploaded_at DESC";
$result = $conn->query($sql);

$usersData = [];
while ($row = $result->fetch_assoc()) {
    $usersData[$row['username']][] = $row;
}

foreach ($usersData as $username => $uploads) {
    foreach ($uploads as $file) {
        $filePath = "uploads/" . $file['filename'];
        if (file_exists($filePath)) {
            // Masukkan file ke zip tanpa folder, prefix nama file dengan username supaya unik
            $zip->addFile($filePath, $username . "_" . $file['filename']);
        }
    }
}

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="'.$zipName.'"');
header('Content-Length: ' . filesize($zipName));
readfile($zipName);

unlink($zipName);
exit;
?>
