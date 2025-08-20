<?php
session_start();
require 'config.php';

$message_type = '';
$message_content = '';

// Tangani pesan dari sesi
if (isset($_SESSION['message_content'])) {
    $message_content = $_SESSION['message_content'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message_content'], $_SESSION['message_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message_type = 'error';
        $message_content = 'Lengkapi semua kolom.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();

            if (!password_verify($password, $user['password'])) {
                $message_type = 'error';
                $message_content = 'Password salah.';
            } 
            elseif ($user['role'] === 'user') {
                // Cek status user
                if ($user['status'] === 'pending') {
                    $message_type = 'error';
                    $message_content = 'Akun Anda masih menunggu persetujuan admin.';
                } elseif ($user['status'] === 'rejected') {
                    $message_type = 'error';
                    $message_content = 'Akun Anda telah ditolak oleh admin.';
                } else { // approved
                    $_SESSION['user'] = $user;
                    header("Location: user_dashboard.php");
                    exit;
                }
            } 
            elseif ($user['role'] === 'admin') {
                $_SESSION['user'] = $user;
                header("Location: admin_dashboard.php");
                exit;
            } else {
                $message_type = 'error';
                $message_content = 'Role tidak dikenali.';
            }
        } else {
            $message_type = 'error';
            $message_content = 'Username tidak ditemukan.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login</title>
<style>
body { 
    background-image:url('cibodas.png'); 
    background-size:cover; 
    background-position:center; 
    margin:0; 
    font-family:Arial,sans-serif; 
}
.login-container { 
    max-width:380px; 
    margin:100px auto; 
    padding:25px; 
    background:rgba(255,255,255,0.9); 
    border-radius:10px; 
    box-shadow:0 0 15px rgba(0,0,0,0.4); 
    text-align:center; 
}
h2 { margin-bottom:20px; color:#333; }
input[type="text"], input[type="password"] { 
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
.register-link { margin-top:15px; }
.register-link a { color:#0066cc; text-decoration:none; }
.register-link a:hover { text-decoration:underline; }
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

<div class="login-container">
    <h2>Login Akun</h2>
    <?php if ($message_content): ?>
        <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message_content) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <input type="text" name="username" placeholder="Username" required autofocus>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <div class="register-link">
    <a href="register.php">Daftar Akun</a> | 
    <a href="forgot_password.php">Lupa Password?</a>
</div>

</div>

<div class="footer-text-bg">
    General Affair Kebun Raya Cibodas
</div>

</body>
</html>
