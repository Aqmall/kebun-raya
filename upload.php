<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';

// Pastikan user sudah login
if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Tentukan link dashboard berdasarkan role
$dashboard_link = (strtolower($_SESSION['user']['role']) === 'admin') 
    ? 'admin_dashboard.php' 
    : 'user_dashboard.php';

// Proses upload
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploaded_count = 0; 
    $error_files = [];

    foreach ($_FILES['file']['name'] as $i => $name) {
        if ($_FILES['file']['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . basename($name);
            $target = 'uploads/' . $fileName;

            $fileType = $_POST['filetype'][$i] ?? 'other'; 
            $description = $_POST['description'][$i] ?? ''; 

            if ($fileType !== 'image' && $fileType !== 'video') {
                $fileType = 'other';
            }

            if (move_uploaded_file($_FILES['file']['tmp_name'][$i], $target)) {
                $stmt = $conn->prepare("INSERT INTO uploads (user_id, filename, filetype, filesize, description, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('issis', $_SESSION['user']['id'], $fileName, $fileType, $_FILES['file']['size'][$i], $description);
                if ($stmt->execute()) {
                    $uploaded_count++;
                } else {
                    $error_files[] = $name . ' (Database Error)';
                }
            } else {
                $error_files[] = $name . ' (Upload Failed)';
            }
        } else {
            if ($_FILES['file']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $error_files[] = $name . ' (Error Code: ' . $_FILES['file']['error'][$i] . ')';
            }
        }
    }
    
    if ($uploaded_count > 0) {
        $success = "Berhasil mengunggah $uploaded_count file."; 
    }
    if (!empty($error_files)) {
        $error = "Gagal mengunggah file: " . implode(', ', $error_files);
    }

    // Redirect setelah upload
    if ($success) $_SESSION['upload_success'] = $success;
    if ($error) $_SESSION['upload_error'] = $error;
    header("Location: " . $dashboard_link);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Upload File</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        font-family: Arial, sans-serif;
        background-image: url('cibodas.png');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        margin: 0;
        padding: 20px;
    }
    .card {
        background: rgba(255, 255, 255, 0.9); /* biar card agak transparan */
    }
    .footer-text-bg {
    position: fixed;
    bottom: 10px;
    width: 100%;
    text-align: center;
    font-size: 14px;
    font-weight: normal;
    color: white;
}

</style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white text-center">
            <h4>📤 Upload File</h4>
        </div>
        <div class="card-body">
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

            <form method="post" enctype="multipart/form-data">
                <div id="fileInputs">
                    <div class="row mb-3 file-row">
                        <div class="col-md-4">
                            <input type="file" name="file[]" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="description[]" class="form-control" placeholder="Deskripsi file">
                        </div>
                        <div class="col-md-4">
                            <select name="filetype[]" class="form-select">
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                                <option value="other" selected>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addFileInput()">+ Tambah File</button>
                <hr>
                <div class="d-flex justify-content-between">
                    <a href="<?= $dashboard_link ?>" class="btn btn-outline-secondary">⬅ Kembali</a>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addFileInput() {
    const div = document.createElement('div');
    div.className = 'row mb-3 file-row';
    div.innerHTML = `
        <div class="col-md-4">
            <input type="file" name="file[]" class="form-control" required>
        </div>
        <div class="col-md-4">
            <input type="text" name="description[]" class="form-control" placeholder="Deskripsi file">
        </div>
        <div class="col-md-4">
            <select name="filetype[]" class="form-select">
                <option value="image">Image</option>
                <option value="video">Video</option>
                <option value="other" selected>Other</option>
            </select>
        </div>
    `;
    document.getElementById('fileInputs').appendChild(div);
}
</script>
<!-- Footer -->
<div class="footer-text-bg text-center mt-4">
    General Affair Kebun Raya Cibodas
</div>
</body>
</html>
