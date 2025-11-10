<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT nama_lengkap FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
$conn->close();

$nama_terenkripsi = $user_data['nama_lengkap'];

if (empty($nama_terenkripsi)) {
    header("Location: isi_profil.php");
    exit;
}

$nama_asli = dbDecryptAES128($nama_terenkripsi, DB_ENCRYPTION_KEY);
if ($nama_asli === false) {
    $nama_asli = $username; 
}


$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current)
{
    return $current === $file ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Aplikasi Kriptografi</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/fitur.css">
    
    <style>
        .hero-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .hero-section p {
            font-size: 1.1rem;
            color: #7f8c8d;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .feature-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
            padding: 35px 30px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(0,0,0,.15);
            border-color: rgba(52, 152, 219, 0.3);
        }
        
        .feature-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2.8rem;
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
        }
        
        .feature-card:nth-child(2) .feature-icon {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3);
        }
        
        .feature-card:nth-child(3) .feature-icon {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #7f8c8d;
            line-height: 1.7;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }
        
        .feature-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .feature-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
            color: white;
        }
        
        .feature-card:nth-child(2) .feature-btn {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
        
        .feature-card:nth-child(2) .feature-btn:hover {
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }
        
        .feature-card:nth-child(3) .feature-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .feature-card:nth-child(3) .feature-btn:hover {
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
        
        .info-highlights {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 50px;
        }
        
        .highlight-box {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,.06);
            border-left: 4px solid #3498db;
        }
        
        .highlight-box:nth-child(2) {
            border-left-color: #27ae60;
        }
        
        .highlight-box:nth-child(3) {
            border-left-color: #f39c12;
        }
        
        .highlight-box:nth-child(4) {
            border-left-color: #e74c3c;
        }
        
        .highlight-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .highlight-box h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .highlight-box p {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin: 0;
            line-height: 1.6;
        }
        
        .getting-started {
            background: #e3f2fd;
            border-radius: 15px;
            padding: 35px;
            margin-top: 50px;
            border: 1px solid #bbdefb;
        }
        
        .getting-started h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1565c0;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .getting-started-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .step-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #1976d2;
        }
        
        .step-number {
            display: inline-block;
            width: 35px;
            height: 35px;
            background: #1976d2;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .step-box h5 {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .step-box p {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin: 0;
            line-height: 1.6;
        }
    </style>
</head>

<body>

    <header>
        <div class="header-inner">
            <div class="welcome-text">
                <a href="dashboard.php">
                    <h5>WELCOME <br><span>(<?= htmlspecialchars($nama_asli); ?>)</span></h5>
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

        <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'login'): ?>
            <div class="alert alert-success alert-dismissible fade show text-center fw-semibold" role="alert">
                <strong>Berhasil Login!</strong> Selamat datang, <?= htmlspecialchars($nama_asli); ?>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_GET['pesan']) && $_GET['pesan'] == 'profil_disimpan'): ?>
             <div class="alert alert-success alert-dismissible fade show text-center fw-semibold" role="alert">
                <strong>Profil Disimpan!</strong> Selamat datang di Aplikasi Kriptografi, <?= htmlspecialchars($nama_asli); ?>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="hero-section">
            <h1>Selamat Datang, <?= htmlspecialchars($nama_asli); ?>!</h1>
            <p>Amankan data Anda dengan teknologi enkripsi modern dan steganografi. Lindungi informasi penting dengan mudah, cepat, dan aman.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🖼️</div>
                <h3>Steganografi</h3>
                <p>Sembunyikan pesan rahasia di dalam gambar tanpa menimbulkan kecurigaan. Teknik menyisipkan data ke dalam media digital dengan aman.</p>
                <a href="steganografi.php" class="feature-btn">Mulai Sekarang</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>Super Enkripsi</h3>
                <p>Enkripsi berlapis untuk keamanan maksimal. Kombinasi algoritma kriptografi untuk perlindungan data yang lebih kuat dan terpercaya.</p>
                <a href="super_enkripsi.php" class="feature-btn">Mulai Sekarang</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📁</div>
                <h3>Enkripsi File</h3>
                <p>Enkripsi berbagai jenis file dan dokumen penting. Lindungi data Anda dengan password yang aman dan sistem enkripsi terjamin.</p>
                <a href="enkripsi_file.php" class="feature-btn">Mulai Sekarang</a>
            </div>
        </div>

        <div class="info-highlights">
            <div class="highlight-box">
                <div class="highlight-icon">⚡</div>
                <h4>Cepat & Efisien</h4>
                <p>Proses enkripsi dan dekripsi yang cepat tanpa mengurangi kualitas keamanan data Anda.</p>
            </div>

            <div class="highlight-box">
                <div class="highlight-icon">🛡️</div>
                <h4>Keamanan Tinggi</h4>
                <p>Menggunakan algoritma kriptografi standar industri untuk melindungi data dengan maksimal.</p>
            </div>

            <div class="highlight-box">
                <div class="highlight-icon">💡</div>
                <h4>Mudah Digunakan</h4>
                <p>Interface yang user-friendly memudahkan siapa saja untuk mengamankan data mereka.</p>
            </div>

            <div class="highlight-box">
                <div class="highlight-icon">🔒</div>
                <h4>100% Private</h4>
                <p>Data Anda tetap private dan aman. Kami tidak menyimpan data pribadi Anda di server.</p>
            </div>
        </div>

        <div class="getting-started">
            <h3>Cara Memulai</h3>
            <div class="getting-started-content">
                <div class="step-box">
                    <span class="step-number">1</span>
                    <h5>Pilih Fitur</h5>
                    <p>Pilih salah satu fitur yang sesuai dengan kebutuhan Anda: Steganografi, Super Enkripsi, atau Enkripsi File.</p>
                </div>

                <div class="step-box">
                    <span class="step-number">2</span>
                    <h5>Upload Data</h5>
                    <p>Upload file atau masukkan teks yang ingin Anda enkripsi atau sembunyikan dengan aman.</p>
                </div>

                <div class="step-box">
                    <span class="step-number">3</span>
                    <h5>Proses & Download</h5>
                    <p>Klik tombol proses, tunggu beberapa saat, dan download hasil enkripsi atau steganografi Anda.</p>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (isset($_GET['pesan'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    const alert = document.querySelector('.alert');
                    if (alert) {
                        new bootstrap.Alert(alert).close();
                    }
                }, 3000);
            });
        <?php endif; ?>
    </script>
</body>

</html>