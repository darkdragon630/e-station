<?php
/**
 * ===================================
 * Send Verification Email
 * FIXED: Compatible dengan Localhost & Wasmer
 * ===================================
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// ========================================
// LOAD ENVIRONMENT VARIABLES
// ========================================
// Di localhost: Load dari .env file
// Di Wasmer: Environment variables sudah di-set via secrets
// ========================================

$env_file = __DIR__ . '/../.env';

if (file_exists($env_file)) {
    // Localhost - Load dari .env file
    try {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
        error_log("✅ .env file loaded from: {$env_file}");
    } catch (Exception $e) {
        error_log("⚠️ Failed to load .env: " . $e->getMessage());
    }
} else {
    // Wasmer/Production - Environment variables sudah tersedia
    error_log("ℹ️ .env file not found, using system environment variables");
}

// Validate required environment variables
$required_vars = [
    'BREVO_SMTP_HOST',
    'BREVO_SMTP_USER',
    'BREVO_SMTP_PASS',
    'BREVO_FROM_EMAIL',
    'BREVO_FROM_NAME',
    'APP_URL'
];

foreach ($required_vars as $var) {
    if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
        error_log("❌ CRITICAL: Missing environment variable: {$var}");
        // Jangan throw exception, return false di function saja
    }
}

function sendVerificationEmail($email, $nama, $verification_token, $role) {
    // Validate environment variables
    if (empty($_ENV['BREVO_SMTP_HOST']) || empty($_ENV['BREVO_SMTP_USER'])) {
        error_log("❌ Cannot send email: Missing SMTP configuration");
        return false;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // ========================================
        // SECURE SMTP CONFIGURATION
        // ========================================
        $mail->isSMTP();
        $mail->Host = $_ENV['BREVO_SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['BREVO_SMTP_USER'];
        $mail->Password = $_ENV['BREVO_SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Debug (set 0 di production)
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP [$level]: $str");
        };
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $mail->Timeout = 60;
        $mail->CharSet = 'UTF-8';
        
        // From & To
        $mail->setFrom($_ENV['BREVO_FROM_EMAIL'], $_ENV['BREVO_FROM_NAME']);
        $mail->addAddress($email, $nama);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verifikasi Email - Registrasi ' . ucfirst($role) . ' E-Station';
        
        // Build verification link
        $app_url = rtrim($_ENV['APP_URL'], '/');
        $verification_link = "{$app_url}/auth/verify_email.php?token={$verification_token}&role={$role}";
        
        error_log("📧 Preparing email for: {$email} (role: {$role})");
        error_log("🔗 Verification link: {$verification_link}");
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 30px 20px; text-align: center; }
                    .header h1 { margin: 0; font-size: 24px; }
                    .content { padding: 40px 30px; }
                    .content h2 { color: #333; font-size: 20px; margin-top: 0; }
                    .content p { color: #666; line-height: 1.8; }
                    .button { display: inline-block; background: #4CAF50; color: white !important; padding: 15px 40px; text-decoration: none; border-radius: 5px; margin: 25px 0; font-weight: bold; text-align: center; }
                    .button:hover { background: #45a049; }
                    .link-box { background: #f9f9f9; padding: 15px; border: 1px solid #e0e0e0; border-radius: 5px; word-break: break-all; font-size: 13px; color: #666; margin: 20px 0; }
                    .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #e0e0e0; }
                    .note { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>⚡ E-Station</h1>
                        <p style='margin: 10px 0 0 0; font-size: 16px;'>Verifikasi Email Anda</p>
                    </div>
                    <div class='content'>
                        <h2>Halo, {$nama}! 👋</h2>
                        <p>Terima kasih telah mendaftar sebagai <strong>" . ucfirst($role) . "</strong> di E-Station.</p>
                        <p>Untuk menyelesaikan pendaftaran, silakan verifikasi email Anda dengan klik tombol di bawah:</p>
                        
                        <div style='text-align: center;'>
                            <a href='{$verification_link}' class='button'>✅ Verifikasi Email Saya</a>
                        </div>
                        
                        <p>Atau copy link berikut ke browser Anda:</p>
                        <div class='link-box'>{$verification_link}</div>
                        
                        <div class='note'>
                            <strong>⏰ Penting:</strong> Link ini berlaku selama 24 jam.
                        </div>
                        
                        <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;'>
                        
                        <p style='color: #999; font-size: 13px;'>
                            <em>Jika Anda tidak merasa mendaftar, abaikan email ini. Akun tidak akan dibuat tanpa verifikasi.</em>
                        </p>
                    </div>
                    <div class='footer'>
                        <p style='margin: 0;'>&copy; 2025 E-Station. All rights reserved.</p>
                        <p style='margin: 10px 0 0 0;'>Layanan Pengisian Kendaraan Listrik</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Halo {$nama},\n\nTerima kasih telah mendaftar sebagai " . ucfirst($role) . " di E-Station.\n\nSilakan verifikasi email Anda dengan mengunjungi link berikut:\n{$verification_link}\n\nLink berlaku selama 24 jam.\n\nJika Anda tidak merasa mendaftar, abaikan email ini.\n\n---\nE-Station\nLayanan Pengisian Kendaraan Listrik";
        
        // Send
        $start_time = microtime(true);
        error_log("📧 Sending email to: {$email} (role: {$role})");
        
        $mail->send();
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        error_log("✅ Email sent successfully to {$email} in {$duration}s");
        
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Email failed to {$email}: {$mail->ErrorInfo}");
        error_log("Exception: {$e->getMessage()}");
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    } finally {
        $mail->smtpClose();
    }
}
?>