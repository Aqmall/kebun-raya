<?php
// File: fetch_user_uploads.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';

// Pastikan user sudah login
if (empty($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

// Ambil data upload HANYA untuk user yang sedang login
$sql = "SELECT * FROM uploads WHERE user_id=? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$uploads = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $uploads[] = $row;
    }
}

// Keluarkan data dalam format JSON
header('Content-Type: application/json');
echo json_encode($uploads);
?>