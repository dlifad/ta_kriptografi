<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$conn = getDBConnection();

$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current)
{
    return $current === $file ? 'active' : '';
}

/* ==============================
   Proses EMBED (LSB)
============================== */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'embed') {
    if (isset($_FILES['gambar']) && isset($_POST['pesan'])) {
        $gambar = $_FILES['gambar'];
        $pesan  = $_POST['pesan'];
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];

        if (in_array($gambar['type'], $allowed)) {
            $img = ($gambar['type'] == 'image/png')
                ? @imagecreatefrompng($gambar['tmp_name'])
                : @imagecreatefromjpeg($gambar['tmp_name']);

            if ($img) {
                $width  = imagesx($img);
                $height = imagesy($img);

                $pesan_full = $pesan . '###END###'; // Delimiter
                $pesan_binary = '';
                for ($i = 0; $i < strlen($pesan_full); $i++) {
                    $pesan_binary .= str_pad(decbin(ord($pesan_full[$i])), 8, '0', STR_PAD_LEFT);
                }

                $pesan_length = strlen($pesan_binary);
                $max_capacity = $width * $height * 3;

                if ($pesan_length <= $max_capacity) {
                    $index = 0;
                    for ($y = 0; $y < $height && $index < $pesan_length; $y++) {
                        for ($x = 0; $x < $width && $index < $pesan_length; $x++) {
                            $rgb = imagecolorat($img, $x, $y);
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;
                            if ($index < $pesan_length) {
                                $r = ($r & 0xFE) | intval($pesan_binary[$index]);
                                $index++;
                            }
                            if ($index < $pesan_length) {
                                $g = ($g & 0xFE) | intval($pesan_binary[$index]);
                                $index++;
                            }
                            if ($index < $pesan_length) {
                                $b = ($b & 0xFE) | intval($pesan_binary[$index]);
                                $index++;
                            }
                            $new_color = imagecolorallocate($img, $r, $g, $b);
                            imagesetpixel($img, $x, $y, $new_color);
                        }
                    }

                    $original_filename = SecurityConfig::sanitizeInput($gambar['name']);
                    $stego_filename = 'stego_' . $user_id . '_' . time() . '.png';
                    $output_dir = 'uploads/';
                    $output_path = $output_dir . $stego_filename;

                    if (!file_exists($output_dir)) {
                        mkdir($output_dir, 0777, true);
                    }

                    imagepng($img, $output_path);
                    imagedestroy($img);

                    $message_preview = substr($pesan, 0, 100);

                    $stmt = $conn->prepare("INSERT INTO steganografi (user_id, original_filename, stego_filename, stego_path, hidden_message_preview, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param('issss', $user_id, $original_filename, $stego_filename, $output_path, $message_preview);

                    if ($stmt->execute()) {

                        $stmt->close();
                        $conn->close();

                        header('Content-Description: File Transfer');
                        header('Content-Type: image/png');
                        header('Content-Disposition: attachment; filename="' . basename($stego_filename) . '"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($output_path));
                        flush();
                        readfile($output_path);
                        exit;

                    } else {
                        $error_embed = "Gagal menyimpan ke database: " . $stmt->error;
                        unlink($output_path);
                    }
                    $stmt->close();
                } else {
                    $error_embed = "Pesan terlalu panjang untuk gambar ini!";
                }
            } else {
                $error_embed = "Gagal membaca gambar.";
            }
        } else {
            $error_embed = "Format file tidak didukung! Gunakan PNG/JPG.";
        }
    }
}

/* ==============================
   Proses EXTRACT (LSB)
============================== */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'extract') {
    if (isset($_FILES['gambar_extract'])) {
        $gambar  = $_FILES['gambar_extract'];
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];

        if (in_array($gambar['type'], $allowed)) {
            $img = ($gambar['type'] == 'image/png')
                ? @imagecreatefrompng($gambar['tmp_name'])
                : @imagecreatefromjpeg($gambar['tmp_name']);

            if ($img) {
                $width  = imagesx($img);
                $height = imagesy($img);
                $binary_data = '';
                $extracted_message = '';
                $found = false;

                // --- LOOP EFISIEN BARU ---
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        $rgb = imagecolorat($img, $x, $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF; // <-- BUG sebelumnya juga diperbaiki di sini

                        // Ambil LSB dari setiap channel
                        $binary_data .= ($r & 1);
                        $binary_data .= ($g & 1);
                        $binary_data .= ($b & 1);

                        // Jika sudah terkumpul 8 bit, proses menjadi karakter
                        if (strlen($binary_data) >= 8) {
                            $byte = substr($binary_data, 0, 8);
                            $binary_data = substr($binary_data, 8); // Hapus 8 bit yg sudah diproses
                            $char = chr(bindec($byte));
                            $extracted_message .= $char;

                            // Cek apakah delimiter ditemukan
                            if (strpos($extracted_message, '###END###') !== false) {
                                $extracted_message = str_replace('###END###', '', $extracted_message);
                                $found = true;
                                break; // Hentikan loop piksel (kolom)
                            }
                        }
                    }
                    if ($found) {
                        break;
                    }
                }
                // --- AKHIR LOOP EFISIEN ---

                imagedestroy($img);

                if ($found && !empty($extracted_message)) {
                    $success_extract = $extracted_message;
                } else {
                    $error_extract = "Tidak ada pesan yang ditemukan dalam gambar ini!";
                }
            } else {
                $error_extract = "Gagal membaca gambar.";
            }
        } else {
            $error_extract = "Format file tidak didukung! Gunakan PNG/JPG.";
        }
    }
}

// Ambil daftar file steganografi
$stmt_list = $conn->prepare("SELECT id, original_filename, stego_filename, stego_path, hidden_message_preview, created_at FROM steganografi WHERE user_id = ? ORDER BY created_at DESC");
$stmt_list->bind_param('i', $user_id);
$stmt_list->execute();
$stego_files = $stmt_list->get_result();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steganografi | LSB Method</title>

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
            <nav class="nav-center" role="navigation" aria-label="Main navigation">
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
            <h1>STEGANOGRAFI</h1>
            <p>LSB (Least Significant Bit) Steganography</p>
        </div>

        <div class="content-card">

            <ul class="nav nav-tabs" id="steganografiTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="sembunyikan-tab" data-bs-toggle="tab" data-bs-target="#sembunyikan" type="button" role="tab">
                        🔒 Sembunyikan Pesan
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ekstrak-tab" data-bs-toggle="tab" data-bs-target="#ekstrak" type="button" role="tab">
                        🔓 Ekstrak Pesan
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="steganografiTabContent" style="padding-top: 25px;">

                <div class="tab-pane fade show active" id="sembunyikan" role="tabpanel">

                    <?php if (isset($error_embed)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_embed); ?></div>
                    <?php endif; ?>

                    <h4 class="mb-4" style="font-size: 16px; font-weight: 600;">Sembunyikan Pesan dalam Gambar</h4>

                    <form action="" method="POST" enctype="multipart/form-data" id="embedForm">
                        <input type="hidden" name="action" value="embed">
                        <div class="mb-4">
                            <label class="form-label">Upload Gambar (PNG/JPG/JPEG)</label>
                            <input type="file" class="form-control" name="gambar" id="gambarInput" accept="image/png,image/jpeg,image/jpg" required onchange="previewImage(this, 'previewEmbed')">
                            <div id="fileNameEmbed" class="file-name"></div>
                        </div>
                        <div id="previewEmbed" class="preview-container" style="display:none;">
                            <label class="form-label">Preview Gambar</label>
                            <div class="text-center">
                                <img id="imgPreviewEmbed" src="" alt="Preview">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Pesan Rahasia</label>
                            <textarea class="form-control" name="pesan" id="pesanInput" rows="5" placeholder="Masukkan pesan yang ingin disembunyikan..." required oninput="countChars()"></textarea>

                        </div>
                        <button type="submit" class="btn btn-primary-custom w-100">Sembunyikan</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="ekstrak" role="tabpanel">

                    <h4 class="mb-4" style="font-size: 16px; font-weight: 600;">Ekstrak Pesan dari Gambar</h4>
                    <?php if (isset($error_extract)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_extract); ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data" id="extractForm">
                        <input type="hidden" name="action" value="extract">
                        <div class="mb-4">
                            <label class="form-label">Upload Gambar (PNG/JPG/JPEG)</label>
                            <input type="file" class="form-control" name="gambar_extract" id="gambarExtractInput" accept="image/png,image/jpeg,image/jpg" required onchange="previewImage(this, 'previewExtract')">
                            <div id="fileNameExtract" class="file-name"></div>
                        </div>
                        <div id="previewExtract" class="preview-container" style="display:none;">
                            <label class="form-label">Preview Gambar</label>
                            <div class="text-center">
                                <img id="imgPreviewExtract" src="" alt="Preview">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success-custom btn-lg w-100">Ekstrak Pesan</button>
                    </form>

                    <?php if (isset($success_extract)): ?>
                        <div class="result-box">
                            <h5>👁️ Pesan Berhasil Diekstrak</h5>
                            <div class="result-content">
                                <?= nl2br(htmlspecialchars($success_extract)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="content-card">
            <h4 class="mb-4">File Steganografi Tersimpan Anda</h4>
            <?php if ($stego_files->num_rows > 0): ?>
                <table class="file-table table table-hover">
                    <thead>
                        <tr>
                            <th>File Asli</th>
                            <th>File Stego (Klik untuk download)</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($file = $stego_files->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($file['original_filename']); ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($file['stego_path']); ?>" download>
                                        <?= htmlspecialchars($file['stego_filename']); ?>
                                    </a>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($file['created_at'])); ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($file['stego_path']); ?>" class="btn btn-success-custom" download>
                                        📥 Download
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Belum ada file steganografi yang disimpan.</div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // (JS tidak berubah)
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const img = document.getElementById('imgPreview' + previewId.charAt(7).toUpperCase() + previewId.slice(8));
            const fileName = document.getElementById('fileName' + previewId.charAt(7).toUpperCase() + previewId.slice(8));

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
                fileName.textContent = '📁 ' + input.files[0].name;
            }
        }

        function countChars() {
            const textarea = document.getElementById('pesanInput');
            const charCount = document.getElementById('charCount');
            if (textarea) {
                charCount.textContent = 'Karakter: ' + (textarea.value.length || 0);
            }
        }

        const pesanInput = document.getElementById('pesanInput');
        if (pesanInput) {
            pesanInput.addEventListener('input', countChars);
            countChars();
        }

        <?php if (isset($success_extract)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const extractTab = new bootstrap.Tab(document.getElementById('ekstrak-tab'));
                extractTab.show();
            });
        <?php endif; ?>
    </script>
</body>

</html>