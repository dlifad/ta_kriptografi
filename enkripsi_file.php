<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$conn = getDBConnection();

// Fungsi aktif link
$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current) {
    return $current === $file ? 'active' : '';
}

// Proses Enkripsi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'encrypt') {
    if (isset($_FILES['file']) && isset($_POST['password'])) {
        $file = $_FILES['file'];
        $password = $_POST['password'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $file_content = file_get_contents($file['tmp_name']);
            $filename = $file['name']; 
            $file_type = $file['type'];
            
            try {
                $encrypted_content = fileEncryptAES256($file_content, $password); 
                
                $stmt = $conn->prepare("INSERT INTO encrypted_files (user_id, filename, encrypted_content, file_type, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param('isss', $user_id, $filename, $encrypted_content, $file_type);
                
                if ($stmt->execute()) {
                    $success_encrypt = "File berhasil dienkripsi dan disimpan ke database!";
                } else {
                    $error_encrypt = "Gagal menyimpan ke database: " . $stmt->error;
                }
            } catch (Exception $e) {
                $error_encrypt = "Error enkripsi: ". $e->getMessage();
            }
        } else {
            $error_encrypt = "Error upload file!";
        }
    }
}

// Proses Dekripsi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'decrypt') {
    $file_id = $_POST['file_id'] ?? 0;
    $password = $_POST['password_decrypt'] ?? '';

    if ($file_id > 0 && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM encrypted_files WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $file_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($file = $result->fetch_assoc()) {
            try {
                $decrypted_content = fileDecryptAES256($file['encrypted_content'], $password);
                
                if ($decrypted_content !== false) {
                    header('Content-Type: ' . $file['file_type']);
                    header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
                    header('Content-Length: ' . strlen($decrypted_content));
                    echo $decrypted_content;
                    exit;
                } else {
                    $error_decrypt = "Password salah atau file corrupt!";
                }
            } catch (Exception $e) {
                $error_decrypt = "Error dekripsi: " . $e->getMessage();
            }
        } else {
            $error_decrypt = "File tidak ditemukan!";
        }
    }
}

// Proses Delete
if (isset($_GET['delete'])) {
    $file_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM encrypted_files WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $file_id, $user_id);
    if ($stmt->execute()) {
        header("Location: enkripsi_file.php?pesan=deleted");
        exit;
    }
}

$stmt = $conn->prepare("SELECT id, filename, file_type, created_at FROM encrypted_files WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$files = $stmt->get_result();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enkripsi File | AES-256</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/fitur.css">
</head>
<body>

    <header>
        <div class="header-inner">
            <div class="welcome-text">
                <a href="dashboard.php">
                    <h5>WELCOME <br><span>(<?= htmlspecialchars($username); ?>)</span></h5>
                </a>
            </div>
            <nav class="nav-center">
                <a href="steganografi.php" class="<?= is_active('steganografi.php', $current); ?>">STEGANOGRAFI</a>
                <a href="super_enkripsi.php" class="<?= is_active('super_enkripsi.php', $current); ?>">SUPER ENKRIPSI</a>
                <a href="enkripsi_file.php" class="<?= is_active('enkripsi_file.php', $current); ?>">ENKRIPSI FILE</a>
            </nav>
            <div class="logout-btn">
                <a href="login/logout.php" class="btn btn-light btn-sm px-3">LogOut</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="page-title">
            <h1>ENKRIPSI FILE</h1>
            <p>AES-256-CTR File Encryption & Decryption</p>
        </div>

        <div class="content-card">
            <?php if (isset($success_encrypt)): ?>
                <div class="alert alert-success"><?= $success_encrypt; ?></div>
            <?php endif; ?>
            <?php if (isset($error_encrypt)): ?>
                <div class="alert alert-danger"><?= $error_encrypt; ?></div>
            <?php endif; ?>

            <h4 class="mb-4">Upload & Enkripsi File</h4>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="encrypt">
                
                <div class="mb-3">
                    <label class="form-label">Pilih File</label>
                    <input type="file" class="form-control" name="file" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password Enkripsi</label>
                    <input type="password" class="form-control" name="password" placeholder="Masukkan password kuat" required>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100">Enkripsi</button>
            </form>
        </div>

        <div class="content-card">
            <h4 class="mb-4">File Terenkripsi Anda</h4>

            <?php if (isset($error_decrypt)): ?>
                <div class="alert alert-danger"><?= $error_decrypt; ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'deleted'): ?>
                <div class="alert alert-success">File berhasil dihapus!</div>
            <?php endif; ?>

            <?php if ($files->num_rows > 0): ?>
                <table class="file-table table table-hover">
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th>Tipe File</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($file = $files->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($file['filename']); ?></td>
                                <td><?= htmlspecialchars($file['file_type']); ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($file['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-success-custom btn-sm" onclick="showDecryptModal(<?= $file['id']; ?>, '<?= htmlspecialchars($file['filename']); ?>')">🔓 Dekripsi</button>
                                    <a href="?delete=<?= $file['id']; ?>" class="btn btn-danger-custom btn-sm" onclick="return confirm('Yakin hapus file ini?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Belum ada file terenkripsi.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="decryptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dekripsi File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="decrypt">
                        <input type="hidden" name="file_id" id="decrypt_file_id">
                        
                        <p>File: <strong id="decrypt_filename"></strong></p>
                        
                        <div class="mb-3">
                            <label class="form-label">Masukkan Password</label>
                            <input type="password" class="form-control" name="password_decrypt" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success-custom">Dekripsi & Download</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // (JS tidak berubah)
        function showDecryptModal(fileId, filename) {
            document.getElementById('decrypt_file_id').value = fileId;
            document.getElementById('decrypt_filename').textContent = filename;
            new bootstrap.Modal(document.getElementById('decryptModal')).show();
        }
    </script>
</body>
</html>