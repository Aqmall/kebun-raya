<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';

// Pastikan user sudah login dan role adalah admin
if (empty($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'admin') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

// Ambil data upload dari database
$sql = "SELECT u.*, us.username 
        FROM uploads u 
        JOIN users us ON u.user_id = us.id 
        ORDER BY u.uploaded_at DESC";
$result = $conn->query($sql);

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