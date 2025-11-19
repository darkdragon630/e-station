<?php
// ===================================
// DEBUG MODE - Tampilkan Error di Browser
// ===================================

// TEMPORARY: Set ini ke '1' untuk melihat error di browser
error_reporting(E_ALL);
ini_set('display_errors', '1'); // ⚠️ SET KE '0' SETELAH SELESAI DEBUG
ini_set('log_errors', '1');

// Load PHP configuration (optional)
if (file_exists(__DIR__ . '/../config/php_config.php')) {
    require_once __DIR__ . '/../config/php_config.php';
}

session_start();

echo "<h3>🔍 DEBUG MODE - Process Register Mitra</h3>";
echo "<hr>";

// Check required files
if (!file_exists(__DIR__ . "/../config/koneksi.php")) {
    die("❌ CRITICAL: koneksi.php not found");
}

if (!file_exists(__DIR__ . "/send_verification_email.php")) {
    die("❌ CRITICAL: send_verification_email.php not found");
}

require_once __DIR__ . "/../config/koneksi.php";
require_once __DIR__ . "/send_verification_email.php";

echo "✅ Files loaded successfully<br>";
echo "✅ Database connection: " . (isset($koneksi) ? "OK" : "FAILED") . "<br>";
echo "<hr>";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    try {
        echo "<h4>📝 POST Data Received:</h4>";
        echo "<pre>" . print_r($_POST, true) . "</pre>";
        echo "<hr>";
        
        // Validasi POST data exists
        if (!isset($_POST['nama_mitra']) || !isset($_POST['email']) || !isset($_POST['password'])) {
            throw new Exception("Missing required fields");
        }
        
        $nama = trim($_POST['nama_mitra']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $no_telepon = isset($_POST['no_telepon']) ? trim($_POST['no_telepon']) : '';
        $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
        
        echo "<h4>📊 Processed Data:</h4>";
        echo "Nama: {$nama}<br>";
        echo "Email: {$email}<br>";
        echo "No Telepon: " . ($no_telepon ?: '(kosong)') . "<br>";
        echo "Alamat: " . ($alamat ?: '(kosong)') . "<br>";
        echo "<hr>";
        
        // Validasi input kosong
        if (empty($nama) || empty($email) || empty($password)) {
            throw new Exception("Empty fields detected");
        }
        
        echo "✅ Validation passed<br>";
        
        // Validasi email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        echo "✅ Email format valid<br>";
        
        // ✅ VALIDASI PASSWORD REQUIREMENTS
        if (strlen($password) < 8 || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/[A-Z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            throw new Exception("Weak password");
        }
        
        echo "✅ Password strength validated<br>";
        echo "<hr>";
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate token verifikasi (random 32 karakter)
        $verification_token = bin2hex(random_bytes(16));
        
        echo "<h4>🔍 Checking Duplicates:</h4>";
        
        // Cek email sudah terdaftar atau belum
        $stmt = $koneksi->prepare("SELECT * FROM mitra WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already exists");
        }
        echo "✅ Email tidak duplikat<br>";

        // Cek nama mitra sudah terdaftar atau belum
        $stmt = $koneksi->prepare("SELECT * FROM mitra WHERE nama_mitra = ?");
        $stmt->execute([$nama]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Nama mitra already exists");
        }
        echo "✅ Nama mitra tidak duplikat<br>";
        echo "<hr>";
        
        echo "<h4>💾 Inserting to Database:</h4>";
        
        // Insert ke database dengan status 'pending'
        $sql = "INSERT INTO mitra 
                (nama_mitra, email, password, no_telepon, alamat, status, email_terverifikasi, verifikasi_token, token_created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', 0, ?, NOW())";
        
        echo "SQL: <pre>{$sql}</pre>";
        echo "Parameters: <pre>" . print_r([$nama, $email, '***HASHED***', $no_telepon, $alamat, $verification_token], true) . "</pre>";
        
        $stmt = $koneksi->prepare($sql);
        $result = $stmt->execute([$nama, $email, $hashed_password, $no_telepon, $alamat, $verification_token]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            echo "<div style='color: red;'>";
            echo "❌ Database insert failed!<br>";
            echo "Error Info: <pre>" . print_r($errorInfo, true) . "</pre>";
            echo "</div>";
            throw new Exception("Database insert failed: " . $errorInfo[2]);
        }
        
        $user_id = $koneksi->lastInsertId();
        
        echo "✅ Mitra registered successfully!<br>";
        echo "User ID: {$user_id}<br>";
        echo "<hr>";
        
        // ===== KIRIM EMAIL VERIFIKASI =====
        echo "<h4>📧 Sending Verification Email:</h4>";
        
        $email_sent = sendVerificationEmail($email, $nama, $verification_token, 'mitra');
        
        if ($email_sent) {
            echo "✅ Email sent successfully!<br>";
            echo "<div style='background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
            echo "<h3>✅ SUCCESS!</h3>";
            echo "<p>Registrasi berhasil! Email verifikasi telah dikirim ke {$email}</p>";
            echo "<a href='auth.php?success=check_email' class='btn btn-success'>Kembali ke Login</a>";
            echo "</div>";
        } else {
            echo "<div style='color: red;'>";
            echo "❌ Failed to send verification email!<br>";
            echo "Rolling back registration...<br>";
            echo "</div>";
            
            $stmt = $koneksi->prepare("DELETE FROM mitra WHERE id_mitra = ?");
            $stmt->execute([$user_id]);
            
            echo "✅ Rollback complete<br>";
            
            echo "<div style='background: #f8d7da; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
            echo "<h3>❌ EMAIL FAILED</h3>";
            echo "<p>Data berhasil disimpan tapi email gagal dikirim. Registrasi dibatalkan.</p>";
            echo "<a href='auth.php?error=email_failed' class='btn btn-danger'>Coba Lagi</a>";
            echo "</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; padding: 20px; margin: 20px 0; border-radius: 5px; color: #721c24;'>";
        echo "<h3>❌ DATABASE ERROR</h3>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
        echo "<details><summary>Stack Trace</summary><pre>" . $e->getTraceAsString() . "</pre></details>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 20px; margin: 20px 0; border-radius: 5px; color: #721c24;'>";
        echo "<h3>❌ GENERAL ERROR</h3>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<details><summary>Stack Trace</summary><pre>" . $e->getTraceAsString() . "</pre></details>";
        echo "</div>";
    }
    
} else {
    echo "❌ Request method is not POST<br>";
    echo "Redirecting to auth.php...<br>";
    echo "<meta http-equiv='refresh' content='2;url=auth.php'>";
}
?>
