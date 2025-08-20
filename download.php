<?php
require 'config.php';

if (!isset($_GET['type']) || !in_array($_GET['type'], ['word', 'excel'])) {
    die("Jenis file tidak valid.");
}

$type = $_GET['type'];

// Ambil data upload
$sql = "SELECT u.*, us.username 
        FROM uploads u 
        JOIN users us ON u.user_id = us.id 
        ORDER BY us.username, u.uploaded_at DESC";
$result = $conn->query($sql);

if ($type == 'excel') {
    // Download Excel sederhana
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"data_upload.xls\"");
    echo "Username\tID\tFilename\tDeskripsi\tJenis\tUkuran\tTanggal\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['username']}\t{$row['id']}\t{$row['filename']}\t{$row['description']}\t{$row['filetype']}\t{$row['filesize']}\t{$row['uploaded_at']}\n";
    }
    exit;
}

if ($type == 'word') {
    // Download Word dengan foto
    header("Content-Type: application/vnd.ms-word");
    header("Content-Disposition: attachment; filename=\"data_upload.doc\"");
    echo "<html><body>";
    echo "<h2>Data Upload</h2>";
    while ($row = $result->fetch_assoc()) {
        echo "<p><strong>User:</strong> {$row['username']}</p>";
        echo "<p><strong>ID:</strong> {$row['id']}</p>";
        echo "<p><strong>Deskripsi:</strong> {$row['description']}</p>";
        echo "<p><strong>Jenis:</strong> {$row['filetype']}</p>";
        echo "<p><strong>Ukuran:</strong> {$row['filesize']} byte</p>";
        echo "<p><strong>Tanggal:</strong> {$row['uploaded_at']}</p>";
        
        $filePath = "uploads/" . $row['filename'];
        if (file_exists($filePath) && in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
            $imgData = base64_encode(file_get_contents($filePath));
            $mimeType = mime_content_type($filePath);
            echo "<p><img src='data:$mimeType;base64,$imgData' width='200'></p>";
        } else {
            echo "<p><em>File bukan gambar (tidak dapat ditampilkan di Word)</em></p>";
        }
        echo "<hr>";
    }
    echo "</body></html>";
    exit;
}
?>
