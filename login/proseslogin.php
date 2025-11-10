<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyToken($token)) die('Token tidak valid!');

    $conn = getDBConnection();

    $username = SecurityConfig::sanitizeInput($_POST['username']);
    $password = SecurityConfig::sanitizeInput($_POST['password']);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND is_active=1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {

        if (password_needs_rehash($user['password'], PASSWORD_ARGON2I)) {
            $newHash = password_hash($password, PASSWORD_ARGON2I);
            $updateHash = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateHash->bind_param('si', $newHash, $user['id']);
            $updateHash->execute();
            $updateHash->close();
        }

        // Login sukses
        session_regenerate_id(true); 
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        $update = $conn->prepare("UPDATE users SET last_login=NOW() WHERE username=?");
        $update->bind_param('s', $username);
        $update->execute();
        $update->close();
        
        $conn->close();
        header('Location: ../dashboard.php?pesan=login');
        exit;
    } else {
        $conn->close();
        header('Location: login.php?pesan=gagal');
        exit;
    }
}
?>