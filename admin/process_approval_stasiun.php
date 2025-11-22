<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek autentikasi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit;
}

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('approval_stasiun.php', 'error', 'Metode tidak valid!');
    exit;
}

// Ambil data dari form
$action = $_POST['action'] ?? '';
$id_stasiun = $_POST['id_stasiun'] ?? '';
$alasan_penolakan = $_POST['alasan_penolakan'] ?? '';
$id_admin = $_SESSION['user_id'];

// Validasi input
if (empty($action) || empty($id_stasiun)) {
    set_flash_message('approval_stasiun.php', 'error', 'Data tidak lengkap!');
    exit;
}

// Validasi action
if (!in_array($action, ['approve', 'reject'])) {
    set_flash_message('approval_stasiun.php', 'error', 'Aksi tidak valid!');
    exit;
}

// Validasi alasan penolakan jika reject
if ($action === 'reject' && empty(trim($alasan_penolakan))) {
    set_flash_message('approval_stasiun.php', 'error', 'Alasan penolakan harus diisi!');
    exit;
}

try {
    // Cek apakah stasiun ada dan statusnya pending
    $stmt = $koneksi->prepare("
        SELECT sp.*, m.email as email_mitra, m.nama_mitra 
        FROM stasiun_pengisian sp 
        LEFT JOIN mitra m ON sp.id_mitra = m.id_mitra 
        WHERE sp.id_stasiun = ?
    ");
    $stmt->execute([$id_stasiun]);
    $stasiun = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stasiun) {
        set_flash_message('approval_stasiun.php', 'error', 'Stasiun tidak ditemukan!');
        exit;
    }

    if ($stasiun['status'] !== 'pending') {
        set_flash_message('approval_stasiun.php', 'warning', 'Stasiun sudah diproses sebelumnya!');
        exit;
    }

    // Mulai transaksi
    $koneksi->beginTransaction();

    if ($action === 'approve') {
        // Approve stasiun
        $stmt = $koneksi->prepare("
            UPDATE stasiun_pengisian 
            SET status = 'disetujui',
                status_operasional = 'aktif',
                approved_by = ?,
                approved_at = NOW(),
                updated_at = NOW()
            WHERE id_stasiun = ?
        ");
        $stmt->execute([$id_admin, $id_stasiun]);

        // Kirim notifikasi ke mitra
        $judul_notif = "Stasiun Anda Disetujui! 🎉";
        $pesan_notif = "Selamat! Stasiun '{$stasiun['nama_stasiun']}' telah disetujui dan sekarang aktif di aplikasi pengguna.";
        
        $stmt = $koneksi->prepare("
            INSERT INTO notifikasi (id_penerima, tipe_penerima, judul, pesan, dikirim_pada)
            VALUES (?, 'mitra', ?, ?, NOW())
        ");
        $stmt->execute([$stasiun['id_mitra'], $judul_notif, $pesan_notif]);

        // Log aktivitas
        logActivity($koneksi, $id_admin, 'admin', 'APPROVE_STASIUN', 
            "Menyetujui stasiun: {$stasiun['nama_stasiun']} (ID: $id_stasiun)");

        $koneksi->commit();

        set_flash_message('approval_stasiun.php', 'success', 
            "Stasiun '{$stasiun['nama_stasiun']}' berhasil disetujui!");

    } else {
        // Reject stasiun
        $stmt = $koneksi->prepare("
            UPDATE stasiun_pengisian 
            SET status = 'ditolak',
                alasan_penolakan = ?,
                approved_by = ?,
                approved_at = NOW(),
                updated_at = NOW()
            WHERE id_stasiun = ?
        ");
        $stmt->execute([$alasan_penolakan, $id_admin, $id_stasiun]);

        // Kirim notifikasi ke mitra
        $judul_notif = "Pengajuan Stasiun Ditolak";
        $pesan_notif = "Maaf, stasiun '{$stasiun['nama_stasiun']}' ditolak.\n\nAlasan: $alasan_penolakan\n\nSilakan perbaiki dan ajukan ulang.";
        
        $stmt = $koneksi->prepare("
            INSERT INTO notifikasi (id_penerima, tipe_penerima, judul, pesan, dikirim_pada)
            VALUES (?, 'mitra', ?, ?, NOW())
        ");
        $stmt->execute([$stasiun['id_mitra'], $judul_notif, $pesan_notif]);

        // Log aktivitas
        logActivity($koneksi, $id_admin, 'admin', 'REJECT_STASIUN', 
            "Menolak stasiun: {$stasiun['nama_stasiun']} (ID: $id_stasiun). Alasan: $alasan_penolakan");

        $koneksi->commit();

        set_flash_message('approval_stasiun.php', 'success', 
            "Stasiun '{$stasiun['nama_stasiun']}' telah ditolak.");
    }

} catch (PDOException $e) {
    // Rollback jika error
    if ($koneksi->inTransaction()) {
        $koneksi->rollBack();
    }
    
    error_log("Error approval stasiun: " . $e->getMessage());
    set_error_handler('approval_stasiun.php', 'error', 
        'Terjadi kesalahan sistem. Silakan coba lagi.');
}

/**
 * Fungsi untuk log aktivitas admin
 */
function logActivity($koneksi, $id_user, $role, $action, $description) {
    try {
        // Cek apakah tabel log_aktivitas ada
        $stmt = $koneksi->prepare("
            INSERT INTO log_aktivitas (id_user, role, aksi, deskripsi, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $id_user,
            $role,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Jika tabel tidak ada atau error, abaikan saja
        error_log("Log activity error: " . $e->getMessage());
    }
}
?>