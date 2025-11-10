<?php
require_once 'config.php';

// Hanya izinkan jika pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// Hanya izinkan metode POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Metode tidak diizinkan.");
}

// Verifikasi CSRF Token
if (!isset($_POST['csrf_token']) || !verifyToken($_POST['csrf_token'])) {
    die("Token CSRF tidak valid. Silakan coba lagi.");
}

// Ambil data dari formulir dan sanitasi
$nama_lengkap = SecurityConfig::sanitizeInput($_POST['nama_lengkap']);
$peran = SecurityConfig::sanitizeInput($_POST['peran']);
$tujuan = SecurityConfig::sanitizeInput($_POST['tujuan']);
$user_id = $_SESSION['user_id'];

// Validasi sederhana
if (empty($nama_lengkap) || empty($peran) || empty($tujuan)) {
    die("Semua data wajib diisi.");
}

// ===================================
// PROSES ENKRIPSI AES-128
// ===================================
$nama_terenkripsi = dbEncryptAES128($nama_lengkap, DB_ENCRYPTION_KEY);
$peran_terenkripsi = dbEncryptAES128($peran, DB_ENCRYPTION_KEY);
$tujuan_terenkripsi = dbEncryptAES128($tujuan, DB_ENCRYPTION_KEY);

// Simpan ke database
$conn = getDBConnection();
$stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, peran = ?, tujuan = ? WHERE id = ?");
$stmt->bind_param("sssi", $nama_terenkripsi, $peran_terenkripsi, $tujuan_terenkripsi, $user_id);

if ($stmt->execute()) {
    // Berhasil! Arahkan kembali ke dashboard
    header("Location: dashboard.php?pesan=profil_disimpan");
    exit();
} else {
    // Gagal
    echo "Terjadi kesalahan saat menyimpan profil: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>