<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data upload beserta username
$sql = "SELECT u.*, us.username FROM uploads u JOIN users us ON u.user_id = us.id ORDER BY us.username, u.uploaded_at DESC";
$result = $conn->query($sql);

$uploads = [];
while ($row = $result->fetch_assoc()) {
    $uploads[] = $row;
}

// Mulai isi konten Word (HTML)
$wordContent = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">
<head><title>Data Upload</title></head><body>';

$currentUser = '';
foreach ($uploads as $file) {
    if ($currentUser !== $file['username']) {
        if ($currentUser !== '') {
            $wordContent .= '<hr>';
        }
        $currentUser = $file['username'];
        $wordContent .= '<h2>Data Upload untuk User: ' . htmlspecialchars($currentUser) . '</h2>';
    }
    
    $wordContent .= '<p><strong>ID:</strong> ' . $file['id'] . '<br>';
    $wordContent .= '<strong>File:</strong> ' . htmlspecialchars($file['filename']) . '<br>';
    $wordContent .= '<strong>Deskripsi:</strong> ' . nl2br(htmlspecialchars($file['description'])) . '<br>';
    $wordContent .= '<strong>Jenis:</strong> ' . $file['filetype'] . '<br>';
    $wordContent .= '<strong>Ukuran:</strong> ' . number_format($file['filesize']) . ' B<br>';
    $wordContent .= '<strong>Tanggal:</strong> ' . $file['uploaded_at'] . '</p>';

    // Tampilkan preview gambar/video dengan ukuran kecil
    $fileUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/uploads/' . $file['filename'];
    if ($file['filetype'] === 'image') {
        $wordContent .= '<p><img src="' . $fileUrl . '" style="max-width:150px; height:auto;"></p>';
    } elseif ($file['filetype'] === 'video') {
        // Video tidak bisa langsung diputar di Word, jadi tampilkan link saja
        $wordContent .= '<p><a href="' . $fileUrl . '" target="_blank">Klik untuk lihat video</a></p>';
    }
}

$wordContent .= '</body></html>';

// Header untuk download file .doc
header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=data_upload.doc");
header("Pragma: no-cache");
header("Expires: 0");

// Outputkan isi file
echo $wordContent;
exit;
?>
