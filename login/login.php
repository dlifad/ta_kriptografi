<?php
require_once '../config.php';
$token = generateToken();
$pesan = ($_GET['pesan'] ?? '') === 'gagal' ? "Username atau password salah!" : "";

// Tambahkan pesan lain
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] === 'belum_login') $pesan = "Anda harus login untuk mengakses halaman tersebut!";
    if ($_GET['pesan'] === 'berhasil_reg') $pesan = "Registrasi berhasil! Silakan login.";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login | Aplikasi Kriptografi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="bg">
        <div class="login-container">
            <h2><span class="highlight">APLIKASI</span><br>KRIPTOGRAFI<br><span class="login-text">Login</span></h2>

            <?php if (!empty($pesan)): ?>
                <div class="error-box <?= (strpos($pesan, 'berhasil') !== false) ? 'success-box' : ''; ?>">
                    <?= htmlspecialchars($pesan) ?>
                </div>
            <?php endif; ?>

            <form action="proseslogin.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $token ?>">
                <label>Username</label>
                <input type="text" name="username" placeholder="Username" required>
                <label>Password</label>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn-login">LOGIN</button>
            </form>
            <p class="register-text">Belum punya akun? <a href="register.php">Register</a></p>
        </div>
    </div>
    <style>
        .error-box.success-box {
            background-color: rgba(39, 174, 96, 0.92);
            border-color: rgba(39, 174, 96, 0.95);
        }
    </style>
</body>

</html>