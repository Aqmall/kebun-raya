<?php
session_start();
require 'config.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $token  = bin2hex(random_bytes(16)); // 32 heksa
        $expiry = date('Y-m-d H:i:s', time() + 3600); // +1 jam

        $upd = $conn->prepare("UPDATE users SET reset_token=?, token_expiry=? WHERE id=?");
        $upd->bind_param("ssi", $token, $expiry, $user['id']);
        $upd->execute();

        // Tanpa email, tampilkan link di layar
        $reset_link = "http://localhost/kebun_raya/reset_password.php?token=" . $token;
        $msg = "Link reset password (berlaku 1 jam): <br><a href='$reset_link' target='_blank'>$reset_link</a>";
    } else {
        $msg = "Username tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Lupa Password</title>
<style>
    body {
        background-image: url('cibodas.png');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        margin: 0;
        font-family: Arial, sans-serif;
    }
    .container {
        max-width: 380px;
        margin: 100px auto;
        padding: 25px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0,0,0,0.4);
        text-align: center;
    }
    h2 {
        margin-bottom: 20px;
        color: #333;
    }
    input[type="text"] {
        width: 90%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #aaa;
        border-radius: 5px;
    }
    button {
        width: 95%;
        padding: 10px;
        background: #0066cc;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
    }
    button:hover {
        background: #004999;
    }
    .message {
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
        text-align: center;
        word-break: break-word;
        background: rgba(0,0,0,0.35);
        color: #fff;
    }
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
    .login-link a {
        color: #0066cc;
        text-decoration: none;
    }
    .login-link a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="container">
    <h2>Lupa Password</h2>
    <?php if (!empty($msg)) : ?>
        <div class="message"><?= $msg ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <input type="text" name="username" placeholder="Username" required autofocus>
        <button type="submit">Buat Link Reset</button>
    </form>
    <p class="login-link" style="margin-top:12px;">
        <a href="login.php">Kembali ke Login</a>
    </p>
</div>

<div class="footer-text-bg">
    General Affair Kebun Raya Cibodas
</div>

</body>
</html>
