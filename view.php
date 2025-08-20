<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID file tidak valid.");
}

$id = intval($_GET['id']);

// Ambil data file
$stmt = $conn->prepare("SELECT u.*, us.username 
                        FROM uploads u 
                        JOIN users us ON u.user_id = us.id 
                        WHERE u.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("File tidak ditemukan.");
}

$file = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<title>Detail File</title>
<style>
    body { font-family: Arial, sans-serif; background:#f9f9f9; color:#333; padding:20px; }
    .container { max-width: 700px; margin: auto; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
    h2 { text-align:center; margin-bottom: 20px; }
    .preview-img { max-width: 100%; height: auto; border-radius: 4px; }
    .preview-video { max-width: 100%; border-radius: 4px; }
    .btn { background-color: #4CAF50; color: white; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; margin-top: 10px; }
    .btn:hover { background-color: #45a049; }
</style>
</head>
<body>
<div class="container">
    <h2>Detail File</h2>

    <p><strong>ID:</strong> <?= htmlspecialchars($file['id']) ?></p>
    <p><strong>Uploader:</strong> <?= htmlspecialchars($file['username']) ?></p>
    <p><strong>Deskripsi:</strong><br><?= nl2br(htmlspecialchars($file['description'])) ?></p>
    <p><strong>Jenis:</strong> <?= htmlspecialchars($file['filetype']) ?></p>
    <p><strong>Ukuran:</strong> <?= number_format($file['filesize']) ?> B</p>
    <p><strong>Waktu Upload:</strong> <?= htmlspecialchars($file['uploaded_at']) ?></p>

    <p><strong>Preview:</strong></p>
    <?php if ($file['filetype'] === 'image'): ?>
        <img class="preview-img" src="uploads/<?= htmlspecialchars($file['filename']) ?>" alt="Foto">
    <?php elseif ($file['filetype'] === 'video'): ?>
        <video class="preview-video" controls>
            <source src="uploads/<?= htmlspecialchars($file['filename']) ?>" type="video/mp4">
        </video>
    <?php else: ?>
        <a href="uploads/<?= htmlspecialchars($file['filename']) ?>" target="_blank">Download File</a>
    <?php endif; ?>

    <br><br>
    <a href="admin_dashboard.php" class="btn">Kembali</a>
</div>
</body>
</html>
