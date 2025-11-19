<?php
/**
 * Test file untuk debug Wasmer deployment
 * Akses: https://e-station.dev.wasmer.app/test.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/plain');

echo "=== E-STATION DEPLOYMENT TEST ===\n\n";

// 1. PHP Version
echo "1. PHP Version: " . PHP_VERSION . "\n\n";

// 2. File System
echo "2. File System Check:\n";
$files_to_check = [
    'config/php_config.php',
    'config/koneksi.php',
    'auth/send_verification_email.php',
    'auth/process_register_pengendara.php',
    'vendor/autoload.php',
    '.env'
];

foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path) ? '✓ EXISTS' : '✗ NOT FOUND';
    echo "   {$file}: {$exists}\n";
}

// 3. Environment Variables
echo "\n3. Environment Variables:\n";
$env_vars = [
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'BREVO_SMTP_HOST',
    'BREVO_SMTP_USER',
    'APP_URL'
];

foreach ($env_vars as $var) {
    $value = getenv($var) ?: $_ENV[$var] ?? 'NOT SET';
    $masked = ($value === 'NOT SET') ? $value : substr($value, 0, 10) . '...';
    echo "   {$var}: {$masked}\n";
}

// 4. Composer Autoload
echo "\n4. Composer Check:\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "   ✓ Composer autoload loaded\n";
    
    // Check PHPMailer
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "   ✓ PHPMailer available\n";
    } else {
        echo "   ✗ PHPMailer NOT available\n";
    }
    
    // Check Dotenv
    if (class_exists('Dotenv\Dotenv')) {
        echo "   ✓ Dotenv available\n";
    } else {
        echo "   ✗ Dotenv NOT available\n";
    }
} else {
    echo "   ✗ vendor/autoload.php NOT FOUND\n";
}

// 5. Database Connection Test
echo "\n5. Database Connection:\n";
if (file_exists(__DIR__ . '/config/koneksi.php')) {
    try {
        require_once __DIR__ . '/config/koneksi.php';
        if (isset($koneksi)) {
            echo "   ✓ Database connected\n";
            
            // Test query
            $stmt = $koneksi->query("SELECT 1");
            if ($stmt) {
                echo "   ✓ Query test passed\n";
            }
        } else {
            echo "   ✗ Database connection failed\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ koneksi.php not found\n";
}

// 6. Writable directories
echo "\n6. Writable Directories:\n";
$dirs_to_check = [
    __DIR__,
    __DIR__ . '/uploads',
    sys_get_temp_dir()
];

foreach ($dirs_to_check as $dir) {
    $writable = is_writable($dir) ? '✓ WRITABLE' : '✗ NOT WRITABLE';
    echo "   {$dir}: {$writable}\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>