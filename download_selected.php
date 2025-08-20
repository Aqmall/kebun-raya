<?php
require 'config.php';

// ======================= TIDAK PERLU LOGIN =======================
// session_start();
// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
//     header("Location: login.php");
//     exit;
// }

if (!isset($_POST['selected_ids']) || empty($_POST['selected_ids'])) {
    die("Tidak ada file yang dipilih.");
}

$type = $_POST['type'] ?? 'word';
$ids = array_map('intval', $_POST['selected_ids']);
$idList = implode(',', $ids);

// Fungsi ambil data
function getUploads($conn, $idList) {
    $sql = "SELECT u.*, us.username 
            FROM uploads u 
            JOIN users us ON u.user_id = us.id 
            WHERE u.id IN ($idList)
            ORDER BY us.username, u.uploaded_at DESC";
    return $conn->query($sql);
}

// ====================== WORD EXPORT ======================
if ($type === 'word') {
    $result = getUploads($conn, $idList);

    $wordContent = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:w="urn:schemas-microsoft-com:office:word"
    xmlns="http://www.w3.org/TR/REC-html40">
    <head><title>Data Upload</title></head><body>';

    $currentUser = '';
    while ($file = $result->fetch_assoc()) {
        if ($currentUser !== $file['username']) {
            if ($currentUser !== '') $wordContent .= '<hr>';
            $currentUser = $file['username'];
            $wordContent .= '<h2>Data Upload untuk User: ' . htmlspecialchars($currentUser) . '</h2>';
        }

        $wordContent .= '<p><strong>ID:</strong> ' . $file['id'] . '<br>';
        $wordContent .= '<strong>File:</strong> ' . htmlspecialchars($file['filename']) . '<br>';
        $wordContent .= '<strong>Deskripsi:</strong> ' . nl2br(htmlspecialchars($file['description'])) . '<br>';
        $wordContent .= '<strong>Jenis:</strong> ' . $file['filetype'] . '<br>';
        $wordContent .= '<strong>Ukuran:</strong> ' . number_format($file['filesize']) . ' B<br>';
        $wordContent .= '<strong>Tanggal:</strong> ' . $file['uploaded_at'] . '</p>';

        $fileUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/uploads/' . $file['filename'];
        if ($file['filetype'] === 'image') {
            $wordContent .= '<p><img src="' . $fileUrl . '" style="max-width:150px; height:auto;"></p>';
        } elseif ($file['filetype'] === 'video') {
            $wordContent .= '<p><a href="' . $fileUrl . '" target="_blank">Klik untuk lihat video</a></p>';
        }
    }
    $wordContent .= '</body></html>';

    header("Content-type: application/vnd.ms-word");
    header("Content-Disposition: attachment; filename=data_upload_selected.doc");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $wordContent;
    exit;
}

// ====================== ZIP EXPORT ======================
if ($type === 'zip') {
    $result = getUploads($conn, $idList);
    $folder = 'uploads/';
    $zip = new ZipArchive();
    $zipName = 'selected_files_' . date('Ymd_His') . '.zip';

    if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
        while ($file = $result->fetch_assoc()) {
            $filePath = $folder . $file['filename'];
            if (file_exists($filePath)) $zip->addFile($filePath, $file['filename']);
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$zipName.'"');
        header('Content-Length: ' . filesize($zipName));
        readfile($zipName);
        unlink($zipName);
        exit;
    } else die("Gagal membuat ZIP.");
}

// ====================== EXCEL EXPORT ======================
if ($type === 'excel') {
    $result = getUploads($conn, $idList);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=data_upload_selected.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr>
            <th>ID</th>
            <th>Uploader</th>
            <th>File</th>
            <th>Deskripsi</th>
            <th>Jenis</th>
            <th>Ukuran (B)</th>
            <th>Tanggal Upload</th>
          </tr>";

    while ($file = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$file['id']}</td>";
        echo "<td>".htmlspecialchars($file['username'])."</td>";
        echo "<td>".htmlspecialchars($file['filename'])."</td>";
        echo "<td>".htmlspecialchars($file['description'])."</td>";
        echo "<td>{$file['filetype']}</td>";
        echo "<td>{$file['filesize']}</td>";
        echo "<td>{$file['uploaded_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}
?>
