<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Fungsi cek autentikasi
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header("Location: ../auth/login.php?error=unauthorized");
        exit;
    }
}

// Cek autentikasi
checkAdminAuth();

// Fungsi ambil data stasiun
function getApprovalStasiunData($koneksi, $search = '', $status_filter = '') {
    try {
        $query = "SELECT sp.*, m.nama_mitra, m.email as email_mitra, m.no_telepon as telepon_mitra
                  FROM stasiun_pengisian sp
                  LEFT JOIN mitra m ON sp.id_mitra = m.id_mitra
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (sp.id_stasiun LIKE :search1 
                        OR sp.nama_stasiun LIKE :search2 
                        OR m.nama_mitra LIKE :search3)";
            $search_param = "%$search%";
            $params['search1'] = $search_param;
            $params['search2'] = $search_param;
            $params['search3'] = $search_param;
        }

        if (!empty($status_filter)) {
            $query .= " AND sp.status = :status";
            $params['status'] = $status_filter;
        }

        $query .= " ORDER BY sp.created_at DESC";

        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching stasiun data: " . $e->getMessage());
        return [];
    }
}

// Fungsi statistik
function getApprovalStasiunStatistic($koneksi) {
    try {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'disetujui' => 0,
            'ditolak' => 0
        ];

        // Total stasiun
        $stmt = $koneksi->query("SELECT COUNT(*) FROM stasiun_pengisian");
        $stats['total'] = $stmt->fetchColumn();

        // Pending
        $stmt = $koneksi->query("SELECT COUNT(*) FROM stasiun_pengisian WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetchColumn();

        // Disetujui
        $stmt = $koneksi->query("SELECT COUNT(*) FROM stasiun_pengisian WHERE status = 'disetujui'");
        $stats['disetujui'] = $stmt->fetchColumn();

        // Ditolak
        $stmt = $koneksi->query("SELECT COUNT(*) FROM stasiun_pengisian WHERE status = 'ditolak'");
        $stats['ditolak'] = $stmt->fetchColumn();

        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching stats: " . $e->getMessage());
        return ['total' => 0, 'pending' => 0, 'disetujui' => 0, 'ditolak' => 0];
    }
}

// Fungsi get detail stasiun
function getStasiunDetail($koneksi, $id_stasiun) {
    try {
        $stmt = $koneksi->prepare("
            SELECT sp.*, m.nama_mitra, m.email as email_mitra, m.no_telepon as telepon_mitra, m.alamat as alamat_mitra,
                   a.nama as approved_by_name
            FROM stasiun_pengisian sp
            LEFT JOIN mitra m ON sp.id_mitra = m.id_mitra
            LEFT JOIN admin a ON sp.approved_by = a.id_admin
            WHERE sp.id_stasiun = ?
        ");
        $stmt->execute([$id_stasiun]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching stasiun detail: " . $e->getMessage());
        return null;
    }
}

// Ambil data
$nama_admin = $_SESSION['nama'];
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$stasiunList = getApprovalStasiunData($koneksi, $search, $status_filter);
$stats = getApprovalStasiunStatistic($koneksi);

// Cek jika ada request detail
$detail = null;
if (isset($_GET['detail'])) {
    $detail = getStasiunDetail($koneksi, $_GET['detail']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Stasiun Pengisian - Admin E-Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/alert.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php tampilkan_alert(); ?>

    <!-- Page Header -->
    <div class="top-bar">
        <h5><i class="fas fa-charging-station me-2"></i>Approval Stasiun Pengisian</h5>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-charging-station"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total']); ?></h3>
                    <p>Total Stasiun</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['pending']); ?></h3>
                    <p>Menunggu Review</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['disetujui']); ?></h3>
                    <p>Disetujui</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['ditolak']); ?></h3>
                    <p>Ditolak</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Cari nama stasiun, ID, atau nama mitra..." 
                        value="<?= htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="disetujui" <?= $status_filter == 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                        <option value="ditolak" <?= $status_filter == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Cari
                    </button>
                    <a href="approval_stasiun.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Stasiun</th>
                            <th>Mitra</th>
                            <th>Alamat</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stasiunList)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada data stasiun</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($stasiunList as $stasiun): ?>
                        <tr>
                            <td><code>#<?= $stasiun['id_stasiun']; ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($stasiun['nama_stasiun']); ?></strong>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    <?= htmlspecialchars($stasiun['nama_mitra'] ?? 'N/A'); ?>
                                </small>
                            </td>
                            <td>
                                <small><?= htmlspecialchars(substr($stasiun['alamat'], 0, 30)); ?>...</small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $stasiun['kapasitas']; ?> Unit</span>
                            </td>
                            <td>
                                <?php
                                $badge_class = match($stasiun['status']) {
                                    'pending' => 'warning',
                                    'disetujui' => 'success',
                                    'ditolak' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badge_class; ?>">
                                    <?= ucfirst($stasiun['status']); ?>
                                </span>
                            </td>
                            <td>
                                <small><?= date('d/m/Y', strtotime($stasiun['created_at'])); ?></small>
                            </td>
                            <td class="text-center">
                                <button 
                                    class="btn btn-sm btn-info" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#detailModal<?= $stasiun['id_stasiun']; ?>"
                                    title="Detail"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($stasiun['status'] == 'pending'): ?>
                                <button 
                                    type="button" 
                                    class="btn btn-sm btn-success" 
                                    onclick="showApproveModal(<?= $stasiun['id_stasiun']; ?>, '<?= htmlspecialchars($stasiun['nama_stasiun']); ?>')"
                                    title="Setujui"
                                >
                                    <i class="fas fa-check"></i>
                                </button>
                                <button 
                                    type="button" 
                                    class="btn btn-sm btn-danger"
                                    onclick="showRejectModal(<?= $stasiun['id_stasiun']; ?>, '<?= htmlspecialchars($stasiun['nama_stasiun']); ?>')"
                                    title="Tolak"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Detail Modal -->
                        <div class="modal fade" id="detailModal<?= $stasiun['id_stasiun']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-charging-station me-2"></i>Detail Stasiun #<?= $stasiun['id_stasiun']; ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="fw-bold text-primary mb-3">
                                                    <i class="fas fa-info-circle me-1"></i>Informasi Stasiun
                                                </h6>
                                                <table class="table table-sm">
                                                    <tr><th width="40%">Nama Stasiun</th><td><?= htmlspecialchars($stasiun['nama_stasiun']); ?></td></tr>
                                                    <tr><th>Alamat</th><td><?= htmlspecialchars($stasiun['alamat']); ?></td></tr>
                                                    <tr><th>Kapasitas</th><td><?= $stasiun['kapasitas']; ?> Unit</td></tr>
                                                    <tr><th>Koordinat</th><td><?= $stasiun['latitude']; ?>, <?= $stasiun['longitude']; ?></td></tr>
                                                    <tr>
                                                        <th>Status Operasional</th>
                                                        <td>
                                                            <?php
                                                            $op_class = match($stasiun['status_operasional']) {
                                                                'aktif' => 'success',
                                                                'nonaktif' => 'secondary',
                                                                'maintenance' => 'warning',
                                                                default => 'secondary'
                                                            };
                                                            ?>
                                                            <span class="badge bg-<?= $op_class; ?>"><?= ucfirst($stasiun['status_operasional']); ?></span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Status Approval</th>
                                                        <td>
                                                            <span class="badge bg-<?= $badge_class; ?>"><?= ucfirst($stasiun['status']); ?></span>
                                                        </td>
                                                    </tr>
                                                    <tr><th>Dibuat</th><td><?= date('d M Y H:i', strtotime($stasiun['created_at'])); ?></td></tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="fw-bold text-primary mb-3">
                                                    <i class="fas fa-user me-1"></i>Informasi Mitra
                                                </h6>
                                                <table class="table table-sm">
                                                    <tr><th width="40%">Nama Mitra</th><td><?= htmlspecialchars($stasiun['nama_mitra'] ?? '-'); ?></td></tr>
                                                    <tr><th>Email</th><td><?= htmlspecialchars($stasiun['email_mitra'] ?? '-'); ?></td></tr>
                                                    <tr><th>Telepon</th><td><?= htmlspecialchars($stasiun['telepon_mitra'] ?? '-'); ?></td></tr>
                                                </table>

                                                <?php if ($stasiun['latitude'] && $stasiun['longitude']): ?>
                                                <h6 class="fw-bold text-primary mb-3 mt-4">
                                                    <i class="fas fa-map-marker-alt me-1"></i>Lokasi
                                                </h6>
                                                <div class="ratio ratio-16x9">
                                                    <iframe src="https://maps.google.com/maps?q=<?= $stasiun['latitude']; ?>,<?= $stasiun['longitude']; ?>&z=15&output=embed" 
                                                            allowfullscreen loading="lazy"></iframe>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        <?php if ($stasiun['status'] == 'pending'): ?>
                                        <button type="button" class="btn btn-success" 
                                                onclick="showApproveModal(<?= $stasiun['id_stasiun']; ?>, '<?= htmlspecialchars($stasiun['nama_stasiun']); ?>')">
                                            <i class="fas fa-check me-1"></i>Setujui
                                        </button>
                                        <button type="button" class="btn btn-danger"
                                                onclick="showRejectModal(<?= $stasiun['id_stasiun']; ?>, '<?= htmlspecialchars($stasiun['nama_stasiun']); ?>')">
                                            <i class="fas fa-times me-1"></i>Tolak
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Approve -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="proses_approval_stasiun.php" method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id_stasiun" id="approve_id_stasiun">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Setujui Stasiun</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menyetujui stasiun:</p>
                    <h5 id="approve_nama_stasiun" class="text-primary"></h5>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Setelah disetujui, stasiun akan muncul di aplikasi pengguna.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Ya, Setujui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="proses_approval_stasiun.php" method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id_stasiun" id="reject_id_stasiun">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Tolak Stasiun</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menolak stasiun:</p>
                    <h5 id="reject_nama_stasiun" class="text-danger"></h5>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan_penolakan" rows="3" required
                                  placeholder="Jelaskan alasan penolakan..."></textarea>
                        <div class="form-text">Alasan ini akan dikirim ke mitra</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Ya, Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../js/clean-url.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showApproveModal(id, nama) {
    document.getElementById('approve_id_stasiun').value = id;
    document.getElementById('approve_nama_stasiun').textContent = nama;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function showRejectModal(id, nama) {
    document.getElementById('reject_id_stasiun').value = id;
    document.getElementById('reject_nama_stasiun').textContent = nama;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

</body>
</html>