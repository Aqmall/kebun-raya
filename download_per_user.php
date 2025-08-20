<?php
require 'config.php';
require 'vendor/autoload.php'; // PHPWord autoload

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    die("User ID tidak valid");
}

$user_id = (int)$_GET['user_id'];

// Ambil username dari user_id
$stmtUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
if ($resUser->num_rows === 0) {
    die("User tidak ditemukan");
}
$userData = $resUser->fetch_assoc();
$username = $userData['username'];

// Ambil data upload user tersebut
$stmt = $conn->prepare("SELECT * FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$uploads = [];
while ($row = $result->fetch_assoc()) {
    $uploads[] = $row;
}

if (empty($uploads)) {
    die("User ini belum mengupload file apapun.");
}

$zipName = "uploads_" . $username . "_" . date("Y-m-d_H-i-s") . ".zip";
$zip = new ZipArchive();
if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Tidak bisa membuat file ZIP");
}

// Masukkan file upload
foreach ($uploads as $file) {
    $filePath = "uploads/" . $file['filename'];
    if (file_exists($filePath)) {
        $zip->addFile($filePath, $file['filename']);
    }
}

// Buat dokumen Word dengan PHPWord berisi data dan gambar (seperti contoh sebelumnya)
$phpWord = new PhpWord();
$section = $phpWord->addSection();

$section->addTitle("Data Upload untuk $username", 1);

foreach ($uploads as $file) {
    $section->addText("ID: {$file['id']}");
    $section->addText("Deskripsi: {$file['description']}");
    $section->addText("Jenis: {$file['filetype']}");
    $section->addText("Ukuran: {$file['filesize']} B");
    $section->addText("Tanggal: {$file['uploaded_at']}");

    if ($file['filetype'] === 'image') {
        $imagePath = "uploads/" . $file['filename'];
        if (file_exists($imagePath)) {
            $section->addImage($imagePath, ['width' => 400, 'wrappingStyle' => 'inline']);
        }
    } else {
        $section->addText("Video File: " . $file['filename']);
    }
    $section->addTextBreak(1);
}

$wordTempPath = tempnam(sys_get_temp_dir(), "word_") . ".docx";
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($wordTempPath);

$zip->addFile($wordTempPath, "data_upload.docx");
unlink($wordTempPath);

$zip->close();

// Download file ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="'.$zipName.'"');
header('Content-Length: ' . filesize($zipName));
readfile($zipName);

// Hapus file ZIP sementara
unlink($zipName);
exit;
