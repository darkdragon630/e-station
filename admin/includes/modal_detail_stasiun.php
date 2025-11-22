<!-- Modal Detail Stasiun -->
<div class="modal fade" id="detailModal<?php echo $stasiun['id_stasiun']; ?>" tabindex="-1" aria-labelledby="detailModalLabel<?php echo $stasiun['id_stasiun']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailModalLabel<?php echo $stasiun['id_stasiun']; ?>">
                    <i class="fas fa-charging-station me-2"></i>
                    Detail Stasiun Pengisian
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Kolom Kiri - Informasi Stasiun -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold text-primary">
                                    <i class="fas fa-info-circle me-2"></i>Informasi Stasiun
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">ID Stasiun</label>
                                    <p class="mb-0 fw-semibold">
                                        <code class="bg-light px-2 py-1 rounded">#<?php echo htmlspecialchars($stasiun['id_stasiun']); ?></code>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Nama Stasiun</label>
                                    <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($stasiun['nama_stasiun']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Alamat Lengkap</label>
                                    <p class="mb-0">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                        <?php echo htmlspecialchars($stasiun['alamat']); ?>
                                    </p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small mb-1">Kapasitas</label>
                                        <p class="mb-0">
                                            <span class="badge bg-info fs-6">
                                                <i class="fas fa-bolt me-1"></i><?php echo $stasiun['kapasitas']; ?> Unit
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small mb-1">Status Operasional</label>
                                        <p class="mb-0">
                                            <?php
                                            $op_badge = match($stasiun['status_operasional']) {
                                                'aktif' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'Aktif'],
                                                'nonaktif' => ['class' => 'secondary', 'icon' => 'pause-circle', 'text' => 'Non-Aktif'],
                                                'maintenance' => ['class' => 'warning', 'icon' => 'tools', 'text' => 'Maintenance'],
                                                default => ['class' => 'secondary', 'icon' => 'question-circle', 'text' => 'Unknown']
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $op_badge['class']; ?> fs-6">
                                                <i class="fas fa-<?php echo $op_badge['icon']; ?> me-1"></i>
                                                <?php echo $op_badge['text']; ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Koordinat Lokasi</label>
                                    <p class="mb-0">
                                        <i class="fas fa-globe text-primary me-1"></i>
                                        <small class="font-monospace">
                                            Lat: <?php echo $stasiun['latitude']; ?>, Long: <?php echo $stasiun['longitude']; ?>
                                        </small>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Status Approval</label>
                                    <p class="mb-0">
                                        <?php
                                        $status_badge = match($stasiun['status']) {
                                            'pending' => ['class' => 'warning', 'icon' => 'clock', 'text' => 'Menunggu Review'],
                                            'disetujui' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'Disetujui'],
                                            'ditolak' => ['class' => 'danger', 'icon' => 'times-circle', 'text' => 'Ditolak'],
                                            default => ['class' => 'secondary', 'icon' => 'question-circle', 'text' => ucfirst($stasiun['status'])]
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $status_badge['class']; ?> fs-6">
                                            <i class="fas fa-<?php echo $status_badge['icon']; ?> me-1"></i>
                                            <?php echo $status_badge['text']; ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted small mb-1">
                                            <i class="fas fa-calendar-plus me-1"></i>Tanggal Dibuat
                                        </label>
                                        <p class="mb-0 small"><?php echo date('d F Y, H:i', strtotime($stasiun['created_at'])); ?> WIB</p>
                                    </div>
                                    
                                    <?php if (!empty($stasiun['approved_at'])): ?>
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted small mb-1">
                                            <i class="fas fa-calendar-check me-1"></i>Tanggal Diproses
                                        </label>
                                        <p class="mb-0 small"><?php echo date('d F Y, H:i', strtotime($stasiun['approved_at'])); ?> WIB</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($stasiun['approved_by_name'])): ?>
                                <div class="mb-2">
                                    <label class="text-muted small mb-1">
                                        <i class="fas fa-user-shield me-1"></i>Diproses Oleh
                                    </label>
                                    <p class="mb-0 small fw-semibold"><?php echo htmlspecialchars($stasiun['approved_by_name']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($stasiun['alasan_penolakan'])): ?>
                                <div class="alert alert-danger mt-3 mb-0">
                                    <label class="text-danger small mb-1 fw-bold">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Alasan Penolakan
                                    </label>
                                    <p class="mb-0 small"><?php echo htmlspecialchars($stasiun['alasan_penolakan']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kolom Kanan - Informasi Mitra & Peta -->
                    <div class="col-lg-6 mb-4">
                        <!-- Informasi Mitra -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold text-primary">
                                    <i class="fas fa-user-tie me-2"></i>Informasi Mitra
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Nama Mitra</label>
                                    <p class="mb-0 fw-semibold">
                                        <i class="fas fa-building text-primary me-1"></i>
                                        <?php echo htmlspecialchars($stasiun['nama_mitra'] ?? 'Tidak tersedia'); ?>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Email</label>
                                    <p class="mb-0">
                                        <i class="fas fa-envelope text-info me-1"></i>
                                        <?php if (!empty($stasiun['email_mitra'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($stasiun['email_mitra']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($stasiun['email_mitra']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">No. Telepon</label>
                                    <p class="mb-0">
                                        <i class="fas fa-phone text-success me-1"></i>
                                        <?php if (!empty($stasiun['telepon_mitra'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($stasiun['telepon_mitra']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($stasiun['telepon_mitra']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <?php if (!empty($stasiun['alamat_mitra'])): ?>
                                <div class="mb-0">
                                    <label class="text-muted small mb-1">Alamat Mitra</label>
                                    <p class="mb-0">
                                        <i class="fas fa-home text-warning me-1"></i>
                                        <?php echo htmlspecialchars($stasiun['alamat_mitra']); ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Peta Lokasi with Leaflet -->
                        <?php if (!empty($stasiun['latitude']) && !empty($stasiun['longitude'])): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold text-primary">
                                    <i class="fas fa-map-marked-alt me-2"></i>Lokasi di Peta
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div id="stationMap<?php echo $stasiun['id_stasiun']; ?>" style="height: 300px; border-radius: 0;"></div>
                                <div class="p-2 bg-light text-center">
                                    <a href="https://www.google.com/maps?q=<?php echo $stasiun['latitude']; ?>,<?php echo $stasiun['longitude']; ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i>
                                        Buka di Google Maps
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <script>
                        // Initialize map when modal is shown
                        document.getElementById('detailModal<?php echo $stasiun['id_stasiun']; ?>').addEventListener('shown.bs.modal', function() {
                            const mapId = 'stationMap<?php echo $stasiun['id_stasiun']; ?>';
                            const lat = <?php echo $stasiun['latitude']; ?>;
                            const lng = <?php echo $stasiun['longitude']; ?>;
                            
                            // Check if map already initialized
                            if (document.getElementById(mapId)._leaflet_id) {
                                return;
                            }
                            
                            // Create map
                            const stationMap = L.map(mapId).setView([lat, lng], 16);
                            
                            // Add tile layer
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap',
                                maxZoom: 19
                            }).addTo(stationMap);
                            
                            // Create custom marker icon
                            const stationIcon = L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41]
                            });
                            
                            // Add marker
                            const marker = L.marker([lat, lng], {icon: stationIcon}).addTo(stationMap);
                            marker.bindPopup(`
                                <div style="min-width: 200px;">
                                    <strong style="color: #2563eb; font-size: 1.1em;">
                                        <i class="fas fa-charging-station"></i> <?php echo htmlspecialchars($stasiun['nama_stasiun']); ?>
                                    </strong>
                                    <hr style="margin: 8px 0;">
                                    <div style="font-size: 0.9em; line-height: 1.6;">
                                        <strong>📍 Alamat:</strong><br>
                                        <span style="color: #475569;"><?php echo htmlspecialchars($stasiun['alamat']); ?></span>
                                        <br><br>
                                        <strong>🎯 Koordinat:</strong><br>
                                        <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.85em;">
                                            ${lat.toFixed(6)}, ${lng.toFixed(6)}
                                        </code>
                                        <br><br>
                                        <strong>⚡ Kapasitas:</strong><br>
                                        <span style="color: #0ea5e9; font-weight: 700;"><?php echo $stasiun['kapasitas']; ?> Unit</span>
                                    </div>
                                </div>
                            `).openPopup();
                            
                            // Add accuracy circle for visual context
                            L.circle([lat, lng], {
                                radius: 50,
                                color: '#22c55e',
                                fillColor: '#22c55e',
                                fillOpacity: 0.1,
                                weight: 2,
                                dashArray: '5, 5'
                            }).addTo(stationMap);
                            
                            // Fix map rendering issue in modal
                            setTimeout(() => {
                                stationMap.invalidateSize();
                            }, 100);
                        });
                        </script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tutup
                </button>
                
                <?php if ($stasiun['status'] === 'pending'): ?>
                    <button 
                        type="button" 
                        class="btn btn-success" 
                        onclick="showApproveModal(<?php echo $stasiun['id_stasiun']; ?>, '<?php echo htmlspecialchars($stasiun['nama_stasiun'], ENT_QUOTES); ?>')"
                        data-bs-dismiss="modal"
                    >
                        <i class="fas fa-check-circle me-1"></i>Setujui Stasiun
                    </button>
                    <button 
                        type="button" 
                        class="btn btn-danger"
                        onclick="showRejectModal(<?php echo $stasiun['id_stasiun']; ?>, '<?php echo htmlspecialchars($stasiun['nama_stasiun'], ENT_QUOTES); ?>')"
                        data-bs-dismiss="modal"
                    >
                        <i class="fas fa-times-circle me-1"></i>Tolak Stasiun
                    </button>
                <?php elseif ($stasiun['status'] === 'disetujui'): ?>
                    <span class="badge bg-success fs-6 py-2">
                        <i class="fas fa-check-circle me-1"></i>Stasiun Telah Disetujui
                    </span>
                <?php elseif ($stasiun['status'] === 'ditolak'): ?>
                    <span class="badge bg-danger fs-6 py-2">
                        <i class="fas fa-times-circle me-1"></i>Stasiun Telah Ditolak
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>