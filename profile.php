<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';

// Pastikan user sudah login sebagai admin
if (empty($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data admin dari session / database
$adminId = $_SESSION['user']['id'];
$res = $conn->query("SELECT * FROM users WHERE id=$adminId LIMIT 1");
if (!$res || $res->num_rows === 0) {
    die("Data admin tidak ditemukan.");
}
$admin = $res->fetch_assoc();

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';

    // Update username saja
    $stmt = $conn->prepare("UPDATE users SET username=? WHERE id=?");
    $stmt->bind_param('si', $username, $adminId);
    if ($stmt->execute()) {
        $success = "Profil berhasil diperbarui!";
        $_SESSION['user']['username'] = $username; // update session
        $admin['username'] = $username;
    } else {
        $error = "Gagal update database: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Profil Admin</title>
<style>
    body{font-family:sans-serif; background:#0b1320; color:#eaf0ff; padding:20px;}
    input{padding:6px; border-radius:6px; border:none; width:300px;}
    button{padding:8px 12px; border-radius:6px; background:#2ecc71; color:#fff; border:none; cursor:pointer;}
    .success{color:#2ecc71;}
    .error{color:#e74c3c;}
</style>
</head>
<body>
<h2>Profil Admin</h2>

<?php if($success) echo "<p class='success'>$success</p>"; ?>
<?php if($error) echo "<p class='error'>$error</p>"; ?>

<form method="post">
    Username:<br>
    <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>"><br><br>
    <button type="submit">Simpan Profil</button>
</form>

<br>
<a href="admin_dashboard.php">⬅ Kembali ke Dashboard</a>
</body>
</html>
