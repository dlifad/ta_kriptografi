<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}

$token = generateToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Profil Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .profile-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,.1);
            padding: 40px;
            width: 100%;
            max-width: 600px;
        }
        .profile-card h1 {
            font-weight: 700;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
        }
        .profile-card p {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #34495e;
        }
    </style>
</head>
<body>
    <div class="profile-card">
        <h1>Lengkapi Profil Anda</h1>
        <p>Untuk memulai sesi pembelajaran, silakan lengkapi data diri Anda.</p>

        <form action="simpan_profil.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token); ?>">

            <div class="mb-3">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
            </div>

            <div class="mb-3">
                <label for="peran" class="form-label">Peran Anda</label>
                <select class="form-select" id="peran" name="peran" required>
                    <option value="" disabled selected>-- Pilih Peran --</option>
                    <option value="Mahasiswa">Mahasiswa</option>
                    <option value="Pengajar">Pengajar</option>
                    <option value="Developer">Developer</option>
                    <option value="Hobi / Umum">Hobi / Umum</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="tujuan" class="form-label">Tujuan Penggunaan Aplikasi</label>
                <textarea class="form-control" id="tujuan" name="tujuan" rows="3" placeholder="Contoh: Untuk tugas akhir mata kuliah Kriptografi" required></textarea>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg" style="background-color: #3498db; border: none; font-weight: 600;">Simpan Profil & Mulai</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>