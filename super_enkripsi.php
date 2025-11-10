<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$conn = getDBConnection();

$message = '';
$encrypted_message_display = '';
$decrypted_message = '';
$error_decrypt = '';

// Fungsi aktif link
$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current)
{
    return $current === $file ? 'active' : '';
}

// Proses Enkripsi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['encrypt'])) {
    $title = $_POST['judul'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    $rails = intval($_POST['rails'] ?? 3);

    if (!empty($title) && !empty($pesan)) {

        // Lakukan Super Enkripsi (Rail Fence + ChaCha20)
        $encrypted_message = superEncrypt($pesan, $rails);
        $encrypted_message_display = $encrypted_message;

        // Simpan ke database (Sesuai struktur baru)
        $stmt = $conn->prepare("INSERT INTO encrypted_messages (user_id, title, encrypted_message, rails, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param('issi', $user_id, $title, $encrypted_message, $rails);

        if ($stmt->execute()) {
            $message = "Pesan berhasil dienkripsi";
        } else {
            $message = "Gagal menyimpan: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Judul dan Pesan harus diisi!";
    }
}

// Proses Dekripsi (Tab Dekripsi)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['decrypt'])) {
    $cipher_text = $_POST['cipher_text'] ?? '';
    $rails_decrypt = intval($_POST['rails_decrypt'] ?? 3);

    if (!empty($cipher_text)) {
        $result = superDecrypt($cipher_text, $rails_decrypt);

        if ($result !== false) {
            $decrypted_message = $result;
        } else {
            $error_decrypt = "Dekripsi gagal! Pastikan ciphertext dan jumlah rails benar.";
        }
    } else {
        $error_decrypt = "Ciphertext tidak boleh kosong!";
    }
}

// Ambil daftar pesan
$stmt_list = $conn->prepare("SELECT id, title, encrypted_message, rails, created_at FROM encrypted_messages WHERE user_id = ? ORDER BY created_at DESC");
$stmt_list->bind_param('i', $user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Enkripsi</title>
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
            <h1>SUPER ENKRIPSI</h1>
            <p>Rail Fence Cipher + ChaCha20 Stream Cipher</p>
        </div>

        <div class="content-card">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="encrypt-tab" data-bs-toggle="tab" data-bs-target="#encrypt-pane" type="button" role="tab">🔒 Enkripsi</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="decrypt-tab" data-bs-toggle="tab" data-bs-target="#decrypt-pane" type="button" role="tab">🔓 Dekripsi</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="encrypt-pane" role="tabpanel" style="padding-top: 25px;">
                    <?php if (!empty($message) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['encrypt'])): ?>
                        <div class="alert <?= strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?= htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <h4 style="font-size: 16px; font-weight: 600;">Enkripsi Pesan Baru</h4>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Judul Pesan</label>
                            <input type="text" name="judul" class="form-control" required placeholder="Masukkan judul pesan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Isi Pesan</label>
                            <textarea name="pesan" id="pesan" class="form-control" required placeholder="Masukkan pesan yang akan dienkripsi..." oninput="updateCharCount()"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Rails</label>
                            <select name="rails" class="form-select">
                                <?php for ($i = 3; $i <= 10; $i++): ?>
                                    <option value="<?= $i; ?>"><?= $i; ?> Rails</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" name="encrypt" class="btn-custom w-100">Enkripsi</button>
                    </form>

                    <?php if (!empty($encrypted_message_display)): ?>
                        <div class="result-box result-super">
                            <strong>Hasil Super Enkripsi</strong>
                            <div class="result-text"><?= htmlspecialchars($encrypted_message_display); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="decrypt-pane" role="tabpanel" style="padding-top: 25px;">
                    <?php if (!empty($error_decrypt) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['decrypt'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error_decrypt); ?>
                        </div>
                    <?php endif; ?>

                    <h4 style="font-size: 16px; font-weight: 600;">Dekripsi Pesan</h4>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Ciphertext</label>
                            <textarea name="cipher_text" class="form-control" required placeholder="Masukkan teks yang akan didekripsi..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Rails</label>
                            <select name="rails_decrypt" class="form-select">
                                <?php for ($i = 3; $i <= 10; $i++): ?>
                                    <option value="<?= $i; ?>"><?= $i; ?> Rails</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" name="decrypt" class="btn-success-custom btn-lg w-100">Dekripsi</button>
                    </form>

                    <?php if (!empty($decrypted_message)): ?>
                        <div class="result-box result-super">
                            <strong>✅ Hasil Dekripsi:</strong>
                            <div class="result-text plaintext">
                                <?= nl2br(htmlspecialchars($decrypted_message)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h4 class="mb-4">Pesan Tersimpan Anda</h4>
            <?php if ($result_list->num_rows > 0): ?>
                <table class="file-table table table-hover">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Ciphertext (Klik untuk copy)</th>
                            <th>Rails</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']); ?></td>
                                <td style="font-family: monospace; font-size: 12px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <span title="Klik untuk menyalin" style="cursor: pointer;"
                                        onclick="copyToClipboard('<?= htmlspecialchars($row['encrypted_message']); ?>', this)">
                                        <?= htmlspecialchars($row['encrypted_message']); ?>
                                    </span>
                                </td>
                                <td><?= $row['rails']; ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Belum ada pesan terenkripsi.</div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCharCount() {
            const text = document.getElementById('pesan');
            document.getElementById('charCount').textContent = text.value.length;
        }

        function copyToClipboard(text, element) {
            navigator.clipboard.writeText(text).then(function() {
                const originalText = element.innerHTML;
                element.innerHTML = "✅ Tersalin!";
                setTimeout(() => {
                    element.innerHTML = originalText;
                }, 1500);
            }, function(err) {
                console.error('Gagal menyalin: ', err);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            let activeTab = '<?php echo (isset($_POST['decrypt'])) ? "#decrypt-tab" : "#encrypt-tab"; ?>';
            let tab = document.querySelector(activeTab);
            if (tab) {
                new bootstrap.Tab(tab).show();
            }
        });
    </script>
</body>

</html>