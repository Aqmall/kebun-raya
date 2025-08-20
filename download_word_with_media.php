<?php
session_start();
require 'config.php';

// Cek admin login (opsional)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data upload + username
$sql = "SELECT u.*, us.username FROM uploads u JOIN users us ON u.user_id = us.id ORDER BY u.uploaded_at DESC";
$result = $conn->query($sql);

$uploads = [];
while ($row = $result->fetch_assoc()) {
    $uploads[] = $row;
}

// Mulai buat konten Word (format HTML)
$wordContent = '<html><head><meta charset="UTF-8"></head><body>';
$wordContent .= '<h1>Data Upload Semua User</h1>';

foreach ($uploads as $file) {
    $wordContent .= '<hr>';
    $wordContent .= '<p><strong>User:</strong> ' . htmlspecialchars($file['username']) . '</p>';
    $wordContent .= '<p><strong>ID:</strong> ' . htmlspecialchars($file['id']) . '</p>';
    $wordContent .= '<p><strong>File:</strong> ' . htmlspecialchars($file['filename']) . '</p>';
    $wordContent .= '<p><strong>Deskripsi:</strong> ' . nl2br(htmlspecialchars($file['description'])) . '</p>';
    $wordContent .= '<p><strong>Jenis:</strong> ' . htmlspecialchars($file['filetype']) . '</p>';
    $wordContent .= '<p><strong>Ukuran:</strong> ' . number_format($file['filesize']) . ' B</p>';
    $wordContent .= '<p><strong>Tanggal:</strong> ' . htmlspecialchars($file['uploaded_at']) . '</p>';

    // Jika gambar, tampilkan gambar di Word
    if ($file['filetype'] === 'image') {
        // Gunakan path relatif agar Word bisa baca dari server via URL
        $imageUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/uploads/' . $file['filename'];
        $wordContent .= '<p><img src="' . $imageUrl . '" style="max-width:300px; height:auto;"></p>';
    }

    // Jika video, tampilkan link saja
    if ($file['filetype'] === 'video') {
        $videoUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/uploads/' . $file['filename'];
        $wordContent .= '<p><a href="' . $videoUrl . '" target="_blank">Klik untuk lihat video</a></p>';
    }
}

$wordContent .= '</body></html>';

// Nama file hasil download
$fileName = "data_uploads_" . date("Ymd_His") . ".doc";

// Header untuk download file Word
header("Content-Type: application/msword");
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output isi Word (HTML)
echo $wordContent;
exit;
?>
