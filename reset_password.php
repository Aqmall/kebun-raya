<?php
session_start();
require 'config.php';

if (!isset($_GET['token'])) {
    die("Token tidak ditemukan.");
}
$token = $_GET['token'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new === '' || $confirm === '') {
        $error = "Isi password baru dan konfirmasi.";
    } elseif ($new !== $confirm) {
        $error = "Password baru dan konfirmasi tidak sama.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token=? AND token_expiry > NOW() LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();
            $hash = password_hash($new, PASSWORD_DEFAULT);

            $upd = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, token_expiry=NULL WHERE id=?");
            $upd->bind_param("si", $hash, $user['id']);
            if ($upd->execute()) {
                echo "<script>alert('Password berhasil direset, silakan login'); window.location='login.php';</script>";
                exit;
            } else {
                $error = "Gagal reset password.";
            }
        } else {
            $error = "Token tidak valid atau sudah kadaluarsa.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<style>
body { 
    background-image:url('cibodas.png'); 
    background-size:cover; 
    background-position:center; 
    margin:0; 
    font-family:Arial,sans-serif; 
}
.container { 
    max-width:380px; 
    margin:100px auto; 
    padding:25px; 
    background:rgba(255,255,255,0.9); 
    border-radius:10px; 
    box-shadow:0 0 15px rgba(0,0,0,0.4); 
    text-align:center; 
}
h2 { margin-bottom:20px; color:#333; }
input[type="password"] { 
    width:90%; 
    padding:10px; 
    margin:10px 0; 
    border:1px solid #aaa; 
    border-radius:5px; 
}
button { 
    width:95%; 
    padding:10px; 
    background:#0066cc; 
    color:white; 
    border:none; 
    border-radius:5px; 
    font-size:16px; 
    cursor:pointer; 
}
button:hover { background:#004999; }
.message { 
    padding:10px; 
    margin-bottom:10px; 
    border-radius:5px; 
    text-align:center; 
}
.success { background-color:#d4edda; color:#155724; }
.error { background-color:#f8d7da; color:#721c24; }
.footer-text-bg { 
    position: fixed; 
    bottom: 20px; 
    width:100%; 
    text-align:center; 
    font-size:20px; 
    font-weight:bold; 
    color:white; 
    text-shadow:2px 2px 4px rgba(0,0,0,0.8); 
}
</style>
</head>
<body>

<div class="container">
    <h2>Reset Password</h2>
    <?php if (!empty($error)) : ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <input type="password" name="new_password" placeholder="Password Baru" required>
        <input type="password" name="confirm_password" placeholder="Konfirmasi Password Baru" required>
        <button type="submit">Reset Password</button>
    </form>
    <div style="margin-top:15px;">
        <a href="login.php">Kembali ke Login</a>
    </div>
</div>

<div class="footer-text-bg">
    General Affair Kebun Raya Cibodas
</div>

</body>
</html>
