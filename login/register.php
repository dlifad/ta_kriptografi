<?php
require_once '../config.php';
$token = generateToken();

$pesan = "";
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] === 'gagal') $pesan = "Registrasi gagal! Pastikan password kuat & username belum digunakan.";
    if ($_GET['pesan'] === 'gagal_hash') $pesan = "Registrasi gagal! Server tidak dikonfigurasi untuk Argon2i.";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Register | Aplikasi Kriptografi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="bg">
        <div class="login-container">
            <h2><span class="highlight">Silahkan Membuat</span><br>Akun Baru</h2>
            <?php if ($pesan): ?><div class="error-box"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>

            <form action="prosesregister.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $token ?>">
                <label>Username</label>
                <input type="text" name="username" placeholder="Username" required>
                <label>Password</label>
                <input type="password" name="password" placeholder="Password (Min 8 char, 1 angka, 1 simbol)" required>
                <button type="submit" class="btn-login">REGISTER</button>
            </form>
            <p class="register-text">Sudah punya akun? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>

</html>