<?php
// ===================================
// FIXED: Error 500 di Wasmer
// ===================================

// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Set '1' untuk debug
ini_set('log_errors', '1');

// Load PHP configuration (optional)
if (file_exists(__DIR__ . '/../config/php_config.php')) {
    require_once __DIR__ . '/../config/php_config.php';
}

session_start();

// Check required files
if (!file_exists(__DIR__ . "/../config/koneksi.php")) {
    error_log("CRITICAL: koneksi.php not found");
    die("Database configuration not found");
}

if (!file_exists(__DIR__ . "/send_verification_email.php")) {
    error_log("CRITICAL: send_verification_email.php not found");
    die("Email service not found");
}

require_once __DIR__ . "/../config/koneksi.php";
require_once __DIR__ . "/send_verification_email.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    try {
        // Validasi POST data exists
        if (!isset($_POST['nama']) || !isset($_POST['email']) || !isset($_POST['password'])) {
            throw new Exception("Missing required fields");
        }
        
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $no_telepon = isset($_POST['no_telepon']) ? trim($_POST['no_telepon']) : '';
        $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
        
        // Validasi input kosong
        if (empty($nama) || empty($email) || empty($password)) {
            header("Location: auth.php?error=empty_fields");
            exit();
        }
        
        // Validasi email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: auth.php?error=invalid_email");
            exit();
        }
        
        // ✅ VALIDASI PASSWORD REQUIREMENTS
        if (strlen($password) < 8 || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/[A-Z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            header("Location: auth.php?error=weak_password");
            exit();
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate token verifikasi (random 32 karakter)
        $verification_token = bin2hex(random_bytes(16));
        
        // Cek email sudah terdaftar atau belum
        $stmt = $koneksi->prepare("SELECT * FROM pengendara WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: auth.php?error=email_exists");
            exit();
        }

        // Cek nama pengendara sudah terdaftar atau belum
        $stmt = $koneksi->prepare("SELECT * FROM pengendara WHERE nama = ?");
        $stmt->execute([$nama]);

        if ($stmt->rowCount() > 0) {
            header("Location: auth.php?error=nama_pengendara_exists");
            exit();
        }

        // Insert ke database dengan status 'nonaktif' dan token_created_at
        $stmt = $koneksi->prepare("
            INSERT INTO pengendara 
            (nama, email, password, no_telepon, alamat, status_akun, verifikasi_token, token_created_at) 
            VALUES (?, ?, ?, ?, ?, 'nonaktif', ?, NOW())
        ");
        $stmt->execute([$nama, $email, $hashed_password, $no_telepon, $alamat, $verification_token]);
        
        $user_id = $koneksi->lastInsertId();
        
        // ===== KIRIM EMAIL VERIFIKASI =====
        $email_sent = sendVerificationEmail($email, $nama, $verification_token, 'pengendara');
        
        if ($email_sent) {
            // Email berhasil dikirim
            header("Location: auth.php?success=check_email");
            exit();
        } else {
            // Jika gagal kirim email, hapus pengendara dari database
            $stmt = $koneksi->prepare("DELETE FROM pengendara WHERE id_pengendara = ?");
            $stmt->execute([$user_id]);
            
            header("Location: auth.php?error=email_failed");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        header("Location: auth.php?error=database_error");
        exit();
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        header("Location: auth.php?error=server_error");
        exit();
    }
    
} else {
    header("Location: auth.php");
    exit();
}
?>