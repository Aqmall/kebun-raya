<?php
session_start();
require 'config.php';

$message_type = '';
$message_content = '';

if (isset($_SESSION['message_content'])) {
    $message_content = $_SESSION['message_content'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message_content'], $_SESSION['message_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message_content'] = 'Lengkapi semua kolom.';
        header("Location: register.php");
        exit;
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Status default = pending
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, 'user', 'pending')");
            $stmt->bind_param("ss", $username, $hash);
            $stmt->execute();

            $_SESSION['message_type'] = 'success';
            $_SESSION['message_content'] = 'Registrasi berhasil! Menunggu persetujuan admin.';
            header("Location: register.php");
            exit;

        } catch (mysqli_sql_exception $e) {
            $_SESSION['message_type'] = 'error';
            if ($e->getCode() == 1062) {
                $_SESSION['message_content'] = 'Username sudah digunakan.';
            } else {
                $_SESSION['message_content'] = 'Terjadi kesalahan saat pendaftaran: ' . $e->getMessage();
            }
            header("Location: register.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar</title>
<style>
    body { background-image: url('cibodas.png'); background-size: cover; background-position: center; margin:0; font-family: Arial, sans-serif; }
    .register-container { max-width: 380px; margin:100px auto; padding:25px; background: rgba(255,255,255,0.9); border-radius:10px; box-shadow: 0 0 15px rgba(0,0,0,0.4); text-align:center; }
    h2 { margin-bottom:20px; color:#333; }
    input[type="text"], input[type="password"] { width:90%; padding:10px; margin:10px 0; border:1px solid #aaa; border-radius:5px; }
    button { width:95%; padding:10px; background:#0066cc; color:white; border:none; border-radius:5px; font-size:16px; cursor:pointer; }
    button:hover { background:#004999; }
    .message { padding:10px; margin-bottom:10px; border-radius:5px; text-align:center; }
    .success { background-color:#d4edda; color:#155724; }
    .error { background-color:#f8d7da; color:#721c24; }
    .footer-text-bg { position: fixed; bottom: 20px; width:100%; text-align:center; font-size:20px; font-weight:bold; color:white; text-shadow:2px 2px 4px rgba(0,0,0,0.8);}
    .login-link { margin-top:15px; }
    .login-link a { color:#0066cc; text-decoration:none; }
    .login-link a:hover { text-decoration:underline; }
</style>
</head>
<body>

<div class="register-container">
    <h2>Daftar Akun</h2>
    <?php if ($message_content): ?>
        <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message_content) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <input type="text" name="username" placeholder="Username" required autofocus>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Daftar</button>
    </form>
    <div class="login-link">
        <a href="login.php">Kembali ke Login</a>
    </div>
</div>

<div class="footer-text-bg">
    General Affair Kebun Raya Cibodas
</div>

</body>
</html>
