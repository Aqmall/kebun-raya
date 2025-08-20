<?php
require 'config.php';
require_once(__DIR__ . '/lib/fpdf/fpdf.php'); // Pastikan path benar

// Buat folder temp jika belum ada
$tempDir = __DIR__ . '/temp_files/';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Query data upload dan user
$sql = "SELECT u.*, us.username 
        FROM uploads u 
        JOIN users us ON u.user_id = us.id 
        ORDER BY us.username, u.uploaded_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error);
}

$usersData = [];
while ($row = $result->fetch_assoc()) {
    $usersData[$row['username']][] = $row;
}

$zipName = $tempDir . "uploads_per_user_" . date("Y-m-d_H-i-s") . ".zip";
$zip = new ZipArchive();
if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Tidak bisa membuat file ZIP.");
}

$tempFiles = []; // untuk simpan semua file sementara agar nanti bisa dihapus

foreach ($usersData as $username => $uploads) {
    $userFolder = $username . "/";

    // 1. Masukkan file asli ke folder user
    foreach ($uploads as $file) {
        $filePath = __DIR__ . "/uploads/" . $file['filename'];
        if (file_exists($filePath)) {
            $zip->addFile($filePath, $userFolder . $file['filename']);
        }
    }

    // 2. Buat file Excel (.xls)
    $excelPath = $tempDir . "{$username}_data_upload.xls";
    $excelContent = "ID\tFilename\tDeskripsi\tJenis\tUkuran\tTanggal\n";
    foreach ($uploads as $file) {
        $excelContent .= "{$file['id']}\t{$file['filename']}\t{$file['description']}\t{$file['filetype']}\t{$file['filesize']}\t{$file['uploaded_at']}\n";
    }
    file_put_contents($excelPath, $excelContent);
    $zip->addFile($excelPath, $userFolder . "data_upload.xls");
    $tempFiles[] = $excelPath;

    // 3. Buat file Word (.doc)
    $wordPath = $tempDir . "{$username}_data_upload.doc";
    $wordContent = "Data Upload untuk $username\n\n";
    foreach ($uploads as $file) {
        $wordContent .= "ID: {$file['id']}\nFile: {$file['filename']}\nDeskripsi: {$file['description']}\nJenis: {$file['filetype']}\nUkuran: {$file['filesize']} bytes\nTanggal: {$file['uploaded_at']}\n\n";
    }
    file_put_contents($wordPath, $wordContent);
    $zip->addFile($wordPath, $userFolder . "data_upload.doc");
    $tempFiles[] = $wordPath;

    // 4. Buat file PDF
    $pdfPath = $tempDir . "{$username}_data_upload.pdf";
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, "Data Upload untuk $username", 0, 1);
    $pdf->SetFont('Arial', '', 10);
    foreach ($uploads as $file) {
        $pdf->MultiCell(0, 6, "ID: {$file['id']} - {$file['filename']} ({$file['filesize']} B)\nDeskripsi: {$file['description']}\nJenis: {$file['filetype']}\nTanggal: {$file['uploaded_at']}\n");
        $pdf->Ln(2);
    }
    $pdf->Output('F', $pdfPath);
    $zip->addFile($pdfPath, $userFolder . "data_upload.pdf");
    $tempFiles[] = $pdfPath;
}

$zip->close();

// Kirim file ZIP ke browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zipName) . '"');
header('Content-Length: ' . filesize($zipName));
readfile($zipName);

// Hapus file sementara
unlink($zipName);
foreach ($tempFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}
?>
