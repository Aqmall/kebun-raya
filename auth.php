<?php
// Mulai session di semua halaman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika session user kosong, berarti belum login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Jika ingin membatasi role di halaman tertentu, bisa pakai:
// if ($_SESSION['user']['role'] !== 'admin') { ... }
// if ($_SESSION['user']['role'] !== 'user') { ... }
