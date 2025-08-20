<?php
session_start();
require 'config.php';

// Pastikan user sudah login
if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Ambil data user
$user = $_SESSION['user'];
$user_id = (int)$user['id'];
$success_message = '';
$error_message = '';

// Proses upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploaded_count = 0;
    $error_files = [];

    foreach ($_FILES['file']['name'] as $i => $name) {
        if ($_FILES['file']['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . basename($name);
            $target = 'uploads/' . $fileName;
            $filetype = $_POST['filetype'];
            $description = $_POST['description'] ?? '';

            if ($filetype !== 'image' && $filetype !== 'video') {
                $filetype = 'other';
            }

            if (move_uploaded_file($_FILES['file']['tmp_name'][$i], $target)) {
                $stmt = $conn->prepare("INSERT INTO uploads (user_id, filename, filetype, filesize, description, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('issis', $user_id, $fileName, $filetype, $_FILES['file']['size'][$i], $description);
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
        $success_message = "Berhasil mengunggah $uploaded_count file.";
    }
    if (!empty($error_files)) {
        $error_message = "Gagal mengunggah file: " . implode(', ', $error_files);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard User</title>
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
.dashboard-container {
    max-width: 900px;
    margin: 50px auto;
    padding: 25px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.4);
}
h2, h3 { color: #333; text-align: center; }
a { text-decoration: none; color: #0066cc; }
#dropArea {
    border: 2px dashed #4CAF50;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    margin-bottom: 10px;
    background: linear-gradient(135deg, #f0fff0, #e6ffe6);
    transition: all 0.3s ease;
}
#dropArea.dragover { background: #d2ffd2; border-color: #2e7d32; }
.preview-container { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
.preview-item { position: relative; display: inline-block; }
.preview-item img, .preview-item video {
    max-width: 150px; max-height: 150px; border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1); border: 1px solid #ccc;
}
.preview-item button {
    position: absolute; top: 2px; right: 2px; background: rgba(255,0,0,0.8); color:#fff;
    border:none; border-radius:50%; width:24px; height:24px; cursor:pointer; font-weight:bold;
}
textarea, select, button {
    margin-top: 10px; display: block; width: 100%;
    padding: 8px; border-radius: 6px; border: 1px solid #ccc;
}
button.upload-btn {
    background: #0066cc; color: white; border: none;
    font-size: 16px; cursor: pointer;
}
button.upload-btn:hover { background: #004999; }
table { width: 100%; background: white; border-collapse: collapse; margin-top: 20px; }
table th, table td { padding: 8px; border: 1px solid #ccc; text-align: center; }
table th { background: #f0f0f0; }
.message.success { color: green; text-align: center; }
.message.error { color: red; text-align: center; }
.footer-text-bg {
    position: fixed;
    bottom: 20px;
    width: 100%;
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    color: white;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
}
.nav-links { margin-bottom: 20px; text-align: center; }
.nav-links a { margin: 0 10px; color: #0066cc; }
.nav-links a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="dashboard-container">
    <h2>Dashboard User</h2>
    <p style="text-align:center;">Selamat datang, <b><?= htmlspecialchars($user['username']) ?></b>!</p>

    <div class="nav-links">
        <a href="logout.php">Logout</a> |
        <a href="user_dashboard.php">Dashboard User</a>
        <?php if (strtolower($user['role']) === 'admin'): ?>
            | <a href="admin_dashboard.php">Dashboard Admin</a>
        <?php endif; ?>
    </div>

    <h3>Upload Media</h3>
    <?php if ($success_message): ?>
        <p class="message success"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <p class="message error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form id="uploadForm" method="POST" enctype="multipart/form-data">
        <div id="dropArea">
            <p>Tarik & letakkan file di sini atau klik untuk memilih</p>
            <input type="file" id="fileInput" name="file[]" accept=".jpg,.jpeg,.png,.gif,.mp4,.webm,.mov" hidden multiple>
            <div class="preview-container" id="previewContainer"></div>
        </div>
        <textarea name="description" placeholder="Keterangan (1 foto 1 keterangan)" required></textarea>
        <select name="filetype" required>
            <option value="image">Foto</option>
            <option value="video">Video</option>
        </select>
        <button type="submit" class="upload-btn">Upload</button>
    </form>

    <h3>Daftar Upload</h3>
    <table>
        <thead>
            <tr>
                <th>Preview</th>
                <th>Deskripsi</th>
                <th>Jenis</th>
                <th>Ukuran</th>
                <th>Waktu</th>
                <th>Lihat</th>
                <th>Edit</th>
            </tr>
        </thead>
        <tbody id="tableBodyUserUploads"></tbody>
    </table>
</div>

<div class="footer-text-bg">
    General Affair Kebun Raya Cibodas
</div>

<script>
// Drag-drop & preview
const dropArea = document.getElementById("dropArea");
const fileInput = document.getElementById("fileInput");
const previewContainer = document.getElementById("previewContainer");
let selectedFiles = [];

dropArea.addEventListener("click", () => fileInput.click());
dropArea.addEventListener("dragover", (e) => { e.preventDefault(); dropArea.classList.add("dragover"); });
dropArea.addEventListener("dragleave", () => dropArea.classList.remove("dragover"));
dropArea.addEventListener("drop", (e) => {
    e.preventDefault();
    dropArea.classList.remove("dragover");
    selectedFiles = Array.from(e.dataTransfer.files);
    updateInputFiles();
    showPreview();
});
fileInput.addEventListener("change", () => {
    selectedFiles = Array.from(fileInput.files);
    showPreview();
});
function showPreview() {
    previewContainer.innerHTML = "";
    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const wrapper = document.createElement("div");
            wrapper.className = "preview-item";
            let el;
            if (file.type.startsWith("image/")) {
                el = document.createElement("img");
            } else if (file.type.startsWith("video/")) {
                el = document.createElement("video");
                el.controls = true;
            }
            el.src = e.target.result;
            wrapper.appendChild(el);
            const btn = document.createElement("button");
            btn.type = "button";
            btn.innerText = "×";
            btn.onclick = () => {
                selectedFiles.splice(index, 1);
                updateInputFiles();
                showPreview();
            };
            wrapper.appendChild(btn);
            previewContainer.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    });
}
function updateInputFiles() {
    const dt = new DataTransfer();
    selectedFiles.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
}

// Tabel daftar upload
const tableBody = document.getElementById('tableBodyUserUploads');
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
function renderTable(data) {
    tableBody.innerHTML = '';
    if (data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7">Anda belum mengunggah file.</td></tr>';
        return;
    }
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                ${row.filetype === 'image' ? 
                    `<img src="uploads/${row.filename}" style="max-width:100px;">` : 
                    `<video style="max-width:100px;" controls><source src="uploads/${row.filename}"></video>`}
            </td>
            <td>${row.description ? row.description.replace(/\n/g, '<br>') : ''}</td>
            <td>${row.filetype}</td>
            <td>${formatBytes(row.filesize)}</td>
            <td>${row.uploaded_at}</td>
            <td><a href="uploads/${row.filename}" target="_blank">Lihat</a></td>
            <td><a href="edit.php?id=${row.id}">Edit</a></td>
        `;
        tableBody.appendChild(tr);
    });
}
function fetchUserUploads() {
    fetch('fetch_user_uploads.php')
        .then(response => response.json())
        .then(data => renderTable(data))
        .catch(error => {
            console.error('Error fetching data:', error);
            tableBody.innerHTML = '<tr><td colspan="7">Gagal memuat data.</td></tr>';
        });
}
fetchUserUploads();
setInterval(fetchUserUploads, 5000);
</script>

</body>
</html>
