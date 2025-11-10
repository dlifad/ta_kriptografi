<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_kripto');

define('CHACHA20_KEY', 'abcdefghijklmnopqrstuvwxyz123456');
define('CHACHA20_METHOD', 'chacha20');

define('DB_ENCRYPTION_KEY', 'Th1sIsMyS3cr3tK1');
define('AES128_GCM_METHOD', 'aes-128-gcm');

class SecurityConfig {
    const MIN_PASSWORD_LENGTH = 8;
    const PASSWORD_REQUIRES_NUMBERS = true;
    const PASSWORD_REQUIRES_SPECIAL_CHARS = true;

    public static function secureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); 
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function validatePassword($password) {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) return false;
        if (self::PASSWORD_REQUIRES_NUMBERS && !preg_match('/[0-9]/', $password)) return false;
        if (self::PASSWORD_REQUIRES_SPECIAL_CHARS && !preg_match('/[^A-Za-z0-9]/', $password)) return false;
        return true;
    }

    public static function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}

SecurityConfig::secureSession();

// CSRF Token
function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    return $conn;
}

function railFenceEncrypt($text, $rails = 3) {
    if ($rails <= 1) return $text;
    $fence = array_fill(0, $rails, '');
    $rail = 0; $direction = 1;
    for ($i = 0; $i < strlen($text); $i++) {
        $fence[$rail] .= $text[$i];
        $rail += $direction;
        if ($rail == 0 || $rail == $rails - 1) $direction *= -1;
    }
    return implode('', $fence);
}
function railFenceDecrypt($cipher, $rails = 3) {
    if ($rails <= 1) return $cipher;
    $fence = array_fill(0, $rails, array_fill(0, strlen($cipher), ''));
    $rail = 0; $direction = 1;
    for ($i = 0; $i < strlen($cipher); $i++) {
        $fence[$rail][$i] = '*';
        $rail += $direction;
        if ($rail == 0 || $rail == $rails - 1) $direction *= -1;
    }
    $index = 0;
    for ($r = 0; $r < $rails; $r++) {
        for ($c = 0; $c < strlen($cipher); $c++) {
            if ($fence[$r][$c] == '*' && $index < strlen($cipher)) {
                $fence[$r][$c] = $cipher[$index++];
            }
        }
    }
    $result = ''; $rail = 0; $direction = 1;
    for ($i = 0; $i < strlen($cipher); $i++) {
        $result .= $fence[$rail][$i];
        $rail += $direction;
        if ($rail == 0 || $rail == $rails - 1) $direction *= -1;
    }
    return $result;
}

function chacha20Encrypt($plaintext, $key = CHACHA20_KEY) {
    $nonce12 = random_bytes(12);
    $counter4 = pack('V', 0);
    $iv16 = $nonce12 . $counter4;

    $cipher_raw = openssl_encrypt($plaintext, 'chacha20', $key, OPENSSL_RAW_DATA, $iv16);
    return base64_encode($nonce12 . $cipher_raw);
}

function chacha20Decrypt($ciphertext, $key = CHACHA20_KEY) {
    $data = base64_decode($ciphertext);
    if ($data === false || strlen($data) < 13) return false;

    $nonce12   = substr($data, 0, 12);
    $cipher_raw = substr($data, 12);

    $counter4 = pack('V', 0);
    $iv16 = $nonce12 . $counter4;

    return openssl_decrypt($cipher_raw, 'chacha20', $key, OPENSSL_RAW_DATA, $iv16);
}


function superEncrypt($text, $rails = 3) {
    $railEncrypted = railFenceEncrypt($text, $rails);
    return chacha20Encrypt($railEncrypted);
}

function superDecrypt($cipher, $rails = 3) {
    $chachaDecrypted = chacha20Decrypt($cipher);
    if ($chachaDecrypted === false) return false;
    return railFenceDecrypt($chachaDecrypted, $rails);
}

function fileEncryptAES256($data, $password) {
    $salt = random_bytes(16);
    $key = hash_pbkdf2("sha256", $password, $salt, 10000, 32, true);
    
    $iv = random_bytes(16);

    $encrypted = openssl_encrypt($data, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv);
    
    return base64_encode($salt . $iv . $encrypted);
}

function fileDecryptAES256($encrypted_data, $password) {
    $data = base64_decode($encrypted_data);
    if ($data === false || strlen($data) < 32) return false;
    
    $salt = substr($data, 0, 16);
    $iv = substr($data, 16, 16);
    $encrypted = substr($data, 32);
    
    $key = hash_pbkdf2("sha256", $password, $salt, 10000, 32, true);
    
    return openssl_decrypt($encrypted, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Mengenkripsi data kolom DB menggunakan AES-128-GCM (Mode AEAD).
 */
function dbEncryptAES128($plaintext, $key = DB_ENCRYPTION_KEY) {
    if (mb_strlen($key, '8bit') !== 16) {
        throw new Exception("Kunci harus tepat 16 byte untuk AES-128.");
    }
    
    $iv_len = openssl_cipher_iv_length(AES128_GCM_METHOD); // 12 bytes
    $iv = random_bytes($iv_len);
    $tag = ""; // Diisi oleh openssl_encrypt
    
    $ciphertext = openssl_encrypt(
        $plaintext,
        AES128_GCM_METHOD,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag // Tag autentikasi (penting untuk GCM)
    );
    
    // Gabungkan iv + ciphertext + tag untuk disimpan
    return base64_encode($iv . $ciphertext . $tag);
}

/**
 * Mendekripsi data kolom DB (AES-128-GCM).
 */
function dbDecryptAES128($encrypted_data, $key = DB_ENCRYPTION_KEY) {
    if (mb_strlen($key, '8bit') !== 16) {
        throw new Exception("Kunci harus tepat 16 byte untuk AES-128.");
    }
    
    $data = base64_decode($encrypted_data);
    if ($data === false) return false;
    
    $iv_len = openssl_cipher_iv_length(AES128_GCM_METHOD); // 12 bytes
    $tag_len = 16; // GCM tag
    
    if (strlen($data) < $iv_len + $tag_len) return false;
    
    $iv = substr($data, 0, $iv_len);
    $ciphertext = substr($data, $iv_len, -$tag_len);
    $tag = substr($data, -$tag_len);
    
    $plaintext = openssl_decrypt(
        $ciphertext,
        AES128_GCM_METHOD,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag // Verifikasi tag
    );
    
    return $plaintext; // Akan return false jika tag tidak cocok
}

?>