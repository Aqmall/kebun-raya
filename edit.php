<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';

// Pastikan user sudah login sebagai admin
if (empty($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: admin_dashboard.php"); exit; }

// Ambil data file
$res = $conn->query("SELECT * FROM uploads WHERE id=$id");
if (!$res || $res->num_rows === 0) {
    die("File tidak ditemukan.");
}
$fileData = $res->fetch_assoc();

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'] ?? '';

    if (!empty($_FILES['file']['name'])) {
        $file = $_FILES['file'];
        $newFileName = time() . '_' . basename($file['name']);
        $target = 'uploads/' . $newFileName;

        $fileType = '';
        if (preg_match('/image/i', $file['type'])) $fileType = 'image';
        elseif (preg_match('/video/i', $file['type'])) $fileType = 'video';
        else $fileType = 'other';

        if (move_uploaded_file($file['tmp_name'], $target)) {
            if (file_exists('uploads/' . $fileData['filename'])) {
                unlink('uploads/' . $fileData['filename']);
            }
            $stmt = $conn->prepare("UPDATE uploads SET filename=?, filetype=?, filesize=?, description=? WHERE id=?");
            $stmt->bind_param('ssisi', $newFileName, $fileType, $file['size'], $description, $id);
            if ($stmt->execute()) {
                $success = "File dan deskripsi berhasil diperbarui!";
                $fileData['filename'] = $newFileName;
                $fileData['filetype'] = $fileType;
                $fileData['filesize'] = $file['size'];
                $fileData['description'] = $description;
            } else {
                $error = "Gagal update database: " . $stmt->error;
            }
        } else {
            $error = "Gagal mengupload file baru.";
        }
    } else {
        $stmt = $conn->prepare("UPDATE uploads SET description=? WHERE id=?");
        $stmt->bind_param('si', $description, $id);
        if ($stmt->execute()) {
            $success = "Deskripsi berhasil diperbarui!";
            $fileData['description'] = $description;
        } else {
            $error = "Gagal update database: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Edit File</title>
<style>
:root{
    --bg:#0b1320;--glass:rgba(255,255,255,.14);--glass-2:rgba(255,255,255,.22);
    --text:#eaf0ff;--muted:#c9d3ff;--brand:#2ecc71;--brand-2:#27ae60;
    --danger:#e74c3c;--warn:#f39c12;--info:#3498db;--card-radius:18px;
    --btn-radius:10px;--shadow:0 10px 30px rgba(0,0,0,.25);--shadow-soft:0 6px 18px rgba(0,0,0,.15);
    --border:1px solid rgba(255,255,255,.18);--backdrop:blur(10px);
}
*{box-sizing:border-box;}
body{height:100%;margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial,"Noto Sans","Helvetica Neue",sans-serif;color:var(--text);background:var(--bg);line-height:1.5;}
.bg-wrap{position:fixed;inset:0;background:linear-gradient(180deg,rgba(7,10,22,.85) 0%,rgba(7,10,22,.6) 100%),url('images/bg.jpg') center/cover no-repeat;z-index:-1;}
.container{max-width:800px;margin:40px auto 80px;padding:0 18px;}
.card{background:linear-gradient(180deg,var(--glass),var(--glass-2));border:var(--border);border-radius:var(--card-radius);box-shadow:var(--shadow);backdrop-filter:var(--backdrop);padding:20px;}
h2{margin-top:0;}
input, textarea{width:100%;padding:10px;border-radius:8px;border:var(--border);background:rgba(255,255,255,.1);color:var(--text);outline:none;margin-bottom:15px;}
button{padding:10px 14px;border-radius:var(--btn-radius);background:linear-gradient(135deg,var(--brand),var(--brand-2));color:#fff;border:none;cursor:pointer;font-weight:600;}
button:hover{opacity:.9;}
.success{color:#2ecc71;margin-bottom:15px;}
.error{color:#e74c3c;margin-bottom:15px;}
a{color:var(--info);text-decoration:none;}
a:hover{text-decoration:underline;}
.preview-img{max-width:200px;max-height:150px;margin-bottom:15px;border-radius:10px;border:1px solid rgba(255,255,255,.2);}
.preview-video{max-width:300px;max-height:200px;margin-bottom:15px;border-radius:10px;border:1px solid rgba(255,255,255,.2);}
</style>
</head>
<body>
<div class="bg-wrap" aria-hidden="true"></div>
<div class="container">
    <div class="card">
        <h2>Edit File</h2>
        <?php if($success) echo "<p class='success'>$success</p>"; ?>
        <?php if($error) echo "<p class='error'>$error</p>"; ?>

        <p>Nama File Lama: <?= htmlspecialchars($fileData['filename']) ?></p>
        <p>Jenis File: <?= htmlspecialchars($fileData['filetype']) ?></p>

        <?php if($fileData['filetype'] === 'image'): ?>
            <img class="preview-img" src="uploads/<?= htmlspecialchars($fileData['filename']) ?>" alt="Preview">
        <?php elseif($fileData['filetype'] === 'video'): ?>
            <video class="preview-video" controls>
                <source src="uploads/<?= htmlspecialchars($fileData['filename']) ?>">
                Browser Anda tidak mendukung video.
            </video>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            Upload file baru (opsional):
            <input type="file" name="file">
            Deskripsi:
            <textarea name="description" rows="3"><?= htmlspecialchars($fileData['description']) ?></textarea>
            <button type="submit">Simpan Perubahan</button>
        </form>
        <br>
        <a href="admin_dashboard.php">⬅ Kembali ke Dashboard</a>
    </div>
</div>
</body>
</html>
