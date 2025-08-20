<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';

// Pastikan user sudah login
if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Simpan role dan ID user yang login
$currentUserId = $_SESSION['user']['id'];
$currentUserRole = strtolower($_SESSION['user']['role']);

// Ambil ID file yang akan dihapus
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Siapkan prepared statement untuk melakukan soft delete
    $query = "UPDATE uploads SET is_deleted = 1 WHERE id = ?";
    $params = "i";

    // Untuk user biasa, hanya boleh soft-delete file miliknya sendiri
    if ($currentUserRole === 'user') {
        $query .= " AND user_id = ?";
        $params .= "i";
    }

    $stmt = $conn->prepare($query);

    if ($stmt) {
        if ($currentUserRole === 'user') {
            $stmt->bind_param($params, $id, $currentUserId);
        } else {
            $stmt->bind_param($params, $id);
        }
        
        // Cek jika execute() berhasil
        if ($stmt->execute()) {
            // Berhasil, Anda bisa menyimpan pesan sukses di session jika perlu
            $_SESSION['message'] = "File berhasil dihapus.";
        } else {
            // Gagal, simpan pesan error
            $_SESSION['error'] = "Gagal menghapus file: " . $stmt->error;
            error_log("Failed to execute soft delete: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Gagal menyiapkan query.";
        error_log("Failed to prepare statement for soft delete: " . $conn->error);
    }
} else {
    $_SESSION['error'] = "ID file tidak valid.";
}

// Redirect kembali ke dashboard sesuai role
if ($currentUserRole === 'admin') {
    header("Location: admin_dashboard.php");
} else {
    header("Location: user_dashboard.php");
}
exit;
?>