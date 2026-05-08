<?php
/* dashboard.php — Halaman utama POS Clean Express Laundry */
session_start();
require_once 'includes/auth.php';   /* ← Wajib login, redirect ke login.php jika belum */
require_once 'koneksi.php';
require_once 'includes/functions.php';
 
$active_page = 'dashboard';
$base_path   = '';
 
/* ── Parameter periode ── */
$periode = $_GET['periode'] ?? '30hari';
 
switch ($periode) {
    case 'hari':
        $where_tgl     = "WHERE t.tanggal_masuk = CURDATE()";
        $label_periode = 'Hari ini';
        break;
    case '3bulan':
        $where_tgl     = "WHERE t.tanggal_masuk >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        $label_periode = '3 Bulan Terakhir';
        break;
    default: /* 30hari */
        $where_tgl     = "WHERE t.tanggal_masuk >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $label_periode = '30 Hari Terakhir';
        $periode       = '30hari';
}
 
/* ── Metrik ringkasan ── */
$q_metrics = mysqli_query($conn, "
    SELECT
        COALESCE(SUM(CASE WHEN t.status_pembayaran='Lunas' THEN t.total_harga ELSE 0 END), 0)     AS total_pendapatan,
        COUNT(t.id_transaksi)                                                                      AS total_transaksi,
        COUNT(DISTINCT t.id_pelanggan)                                                             AS pelanggan_aktif,
        SUM(t.status_pembayaran = 'Belum Lunas')                                                   AS belum_lunas,
        COALESCE(SUM(CASE WHEN t.status_pembayaran='Belum Lunas' THEN t.total_harga ELSE 0 END),0) AS nominal_belum_lunas
    FROM transaksi t
    $where_tgl
");
$metrics = mysqli_fetch_assoc($q_metrics);
 
/* ── Grafik pendapatan harian (30 hari) — hanya Lunas ── */
$q_grafik = mysqli_query($conn, "
    SELECT
        DATE_FORMAT(t.tanggal_masuk, '%d %b') AS hari,
        t.tanggal_masuk                        AS tgl_raw,
        COALESCE(SUM(CASE WHEN t.status_pembayaran='Lunas' THEN t.total_harga ELSE 0 END), 0) AS total
    FROM transaksi t
    WHERE t.tanggal_masuk >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY t.tanggal_masuk
    ORDER BY t.tanggal_masuk ASC
");
$grafik_labels = [];
$grafik_values = [];
while ($row = mysqli_fetch_assoc($q_grafik)) {
    $grafik_labels[] = $row['hari'];
    $grafik_values[] = (int) $row['total'];
}
 
/* ── Status cucian (donut) ── */
/* FIX: tambah alias 't' agar $where_tgl (yang pakai t.tanggal_masuk) tidak error */
$q_status = mysqli_query($conn, "
    SELECT t.status_cucian, COUNT(*) AS jumlah
    FROM transaksi t
    $where_tgl
    GROUP BY t.status_cucian
");
$status_data = [];
while ($row = mysqli_fetch_assoc($q_status)) {
    $status_data[$row['status_cucian']] = (int) $row['jumlah'];
}
$s_selesai = $status_data['Selesai'] ?? 0;
$s_proses  = $status_data['Proses']  ?? 0;
$s_baru    = $status_data['Baru']    ?? 0;
 
/* ── Transaksi terbaru (5 data) ── */
$q_txn = mysqli_query($conn, "
    SELECT t.id_transaksi, t.tanggal_masuk, t.status_cucian, t.status_pembayaran,
           t.total_harga, p.nama_pelanggan,
           GROUP_CONCAT(l.nama_layanan ORDER BY l.id_layanan SEPARATOR ', ') AS layanan
    FROM transaksi t
    JOIN pelanggan p        ON t.id_pelanggan  = p.id_pelanggan
    JOIN detail_transaksi dt ON t.id_transaksi  = dt.id_transaksi
    JOIN layanan l           ON dt.id_layanan   = l.id_layanan
    GROUP BY t.id_transaksi
    ORDER BY t.tanggal_masuk DESC
    LIMIT 5
");
 
/* ── Layanan terpopuler ── */
$q_layanan = mysqli_query($conn, "
    SELECT l.nama_layanan,
           SUM(dt.jumlah) AS total_unit
    FROM detail_transaksi dt
    JOIN layanan l    ON dt.id_layanan  = l.id_layanan
    JOIN transaksi t  ON dt.id_transaksi = t.id_transaksi
    $where_tgl
    GROUP BY l.id_layanan
    ORDER BY total_unit DESC
    LIMIT 5
");
$layanan_rows = [];
while ($row = mysqli_fetch_assoc($q_layanan)) $layanan_rows[] = $row;
$max_unit = !empty($layanan_rows) ? $layanan_rows[0]['total_unit'] : 1;
 
$layanan_colors = ['#1D9E75','#378ADD','#EF9F27','#7F77DD','#D85A30'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Clean Express Laundry</title>
    <?php include 'includes/sidebar.php'; ?>
</head>
<body>
 
<div class="main-wrapper">
 
    <!-- Topbar -->
    <header class="topbar">
        <div>
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-sub"><?= $label_periode ?> · <?= date('d M Y') ?></div>
        </div>
        <div class="topbar-right">
            <!-- Pilih periode -->
            <div class="period-tabs">
                <a href="?periode=hari"
                   class="period-tab <?= $periode==='hari'   ? 'active' : '' ?>">Hari ini</a>
                <a href="?periode=30hari"
                   class="period-tab <?= $periode==='30hari' ? 'active' : '' ?>">30 Hari</a>
                <a href="?periode=3bulan"
                   class="period-tab <?= $periode==='3bulan' ? 'active' : '' ?>">3 Bulan</a>
            </div>
            <a href="transaksi/tambah.php" class="btn btn-primary">
                <i class="ti ti-plus"></i> Transaksi Baru
            </a>
            <!-- Info user & logout -->
            <div style="display:flex;align-items:center;gap:10px;padding-left:12px;border-left:1px solid var(--gray-200);">
                <div style="text-align:right;">
                    <div style="font-size:13px;font-weight:600;color:var(--gray-800);">
                        <?= e(nama_user()) ?>
                    </div>
                    <div style="font-size:11px;color:var(--gray-400);">
                        <?= is_admin() ? '🛡️ Admin' : '🧾 Kasir' ?>
                    </div>
                </div>
                <a href="logout.php"
                   title="Logout"
                   onclick="return confirm('Yakin ingin keluar?')"
                   style="display:inline-flex;align-items:center;justify-content:center;
                          width:34px;height:34px;border-radius:8px;
                          background:#FCEBEB;color:#A32D2D;
                          border:1px solid #f5c6c6;text-decoration:none;
                          transition:background .15s;"
                   onmouseover="this.style.background='#f5c6c6'"
                   onmouseout="this.style.background='#FCEBEB'">
                    <i class="ti ti-logout" style="font-size:16px;"></i>
                </a>
            </div>
        </div>
    </header>
 
    <main class="page-content">
 
        <?= flash() ?>
 
        <!-- ── Kartu metrik ── -->
        <div class="metrics-grid">
 
            <div class="metric-card">
                <div class="metric-icon" style="background:#E1F5EE;color:#1D9E75;">
                    <i class="ti ti-cash"></i>
                </div>
                <div class="metric-label">Pendapatan Lunas</div>
                <div class="metric-value"><?= rupiah((int)$metrics['total_pendapatan']) ?></div>
                <div class="metric-delta delta-up">
                    <i class="ti ti-trending-up" style="font-size:11px;"></i>
                    <?= $label_periode ?>
                </div>
            </div>
 
            <div class="metric-card">
                <div class="metric-icon" style="background:#E6F1FB;color:#185FA5;">
                    <i class="ti ti-receipt"></i>
                </div>
                <div class="metric-label">Total Transaksi</div>
                <div class="metric-value"><?= (int)$metrics['total_transaksi'] ?></div>
                <div class="metric-delta text-muted">nota masuk</div>
            </div>
 
            <div class="metric-card">
                <div class="metric-icon" style="background:#FAEEDA;color:#854F0B;">
                    <i class="ti ti-users"></i>
                </div>
                <div class="metric-label">Pelanggan Aktif</div>
                <div class="metric-value"><?= (int)$metrics['pelanggan_aktif'] ?></div>
                <div class="metric-delta text-muted">pelanggan unik</div>
            </div>
 
            <div class="metric-card">
                <div class="metric-icon" style="background:#FCEBEB;color:#A32D2D;">
                    <i class="ti ti-clock-exclamation"></i>
                </div>
                <div class="metric-label">Belum Lunas</div>
                <div class="metric-value" style="color:#A32D2D;">
                    <?= (int)$metrics['belum_lunas'] ?> nota
                </div>
                <div class="metric-delta delta-down">
                    <?= rupiah((int)$metrics['nominal_belum_lunas']) ?> tertunda
                </div>
            </div>
 
        </div>
 
        <!-- ── Grafik + Donut ── -->
        <div class="charts-row">
 
            <!-- Grafik pendapatan -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Pendapatan Harian</span>
                    <span class="badge badge-info">30 Hari Terakhir</span>
                </div>
                <div class="card-body">
                    <?php if (empty($grafik_labels)): ?>
                        <p class="text-muted text-sm" style="text-align:center;padding:40px 0;">
                            Belum ada data transaksi dalam 30 hari terakhir.
                        </p>
                    <?php else: ?>
                        <div style="position:relative;width:100%;height:240px;">
                            <canvas id="chartPendapatan"
                                    role="img"
                                    aria-label="Grafik pendapatan harian 30 hari terakhir">
                            </canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
 
            <!-- Donut status -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Status Cucian</span>
                    <span class="badge badge-info"><?= $label_periode ?></span>
                </div>
                <div class="card-body">
                    <div style="position:relative;width:100%;height:180px;">
                        <canvas id="chartStatus"
                                role="img"
                                aria-label="Donat persentase status cucian">
                        </canvas>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:16px;">
                        <div class="flex gap-8 text-sm">
                            <span style="width:10px;height:10px;border-radius:2px;background:#1D9E75;flex-shrink:0;display:inline-block;"></span>
                            <span>Selesai</span>
                            <span class="fw-600" style="margin-left:auto;"><?= $s_selesai ?> nota</span>
                        </div>
                        <div class="flex gap-8 text-sm">
                            <span style="width:10px;height:10px;border-radius:2px;background:#EF9F27;flex-shrink:0;display:inline-block;"></span>
                            <span>Proses</span>
                            <span class="fw-600" style="margin-left:auto;"><?= $s_proses ?> nota</span>
                        </div>
                        <div class="flex gap-8 text-sm">
                            <span style="width:10px;height:10px;border-radius:2px;background:#378ADD;flex-shrink:0;display:inline-block;"></span>
                            <span>Baru</span>
                            <span class="fw-600" style="margin-left:auto;"><?= $s_baru ?> nota</span>
                        </div>
                    </div>
                </div>
            </div>
 
        </div>
 
        <!-- ── Transaksi terbaru + Layanan populer ── -->
        <div class="grid-2">
 
            <!-- Transaksi terbaru -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Transaksi Terbaru</span>
                    <a href="transaksi/index.php" class="btn btn-outline btn-sm">
                        Lihat semua <i class="ti ti-arrow-right"></i>
                    </a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Pelanggan</th>
                                <th>Layanan</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_assoc($q_txn)): ?>
                            <tr>
                                <td>
                                    <div class="flex gap-8">
                                        <div class="avatar"><?= inisial($row['nama_pelanggan']) ?></div>
                                        <div>
                                            <div class="fw-600"><?= e($row['nama_pelanggan']) ?></div>
                                            <div class="text-muted text-sm"><?= tgl_indo($row['tanggal_masuk']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted text-sm" style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= e($row['layanan']) ?>
                                </td>
                                <td class="fw-600"><?= rupiah((int)$row['total_harga']) ?></td>
                                <td>
                                    <?= badge_status_cucian($row['status_cucian']) ?>
                                    <?= badge_status_bayar($row['status_pembayaran']) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
 
            <!-- Layanan terpopuler -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Layanan Terpopuler</span>
                    <span class="badge badge-info"><?= $label_periode ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($layanan_rows)): ?>
                        <p class="text-muted text-sm">Belum ada data.</p>
                    <?php else: ?>
                        <?php foreach ($layanan_rows as $i => $lrow): ?>
                        <div class="flex" style="margin-bottom:14px;">
                            <div style="width:130px;font-size:13px;color:var(--gray-800);flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= e($lrow['nama_layanan']) ?>
                            </div>
                            <div class="progress-wrap">
                                <div class="progress-bar"
                                     style="width:<?= round($lrow['total_unit']/$max_unit*100) ?>%;background:<?= $layanan_colors[$i] ?>;"></div>
                            </div>
                            <div style="font-size:12px;font-weight:600;color:var(--gray-700);min-width:28px;text-align:right;">
                                <?= $lrow['total_unit'] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
 
        </div>
 
    </main>
</div><!-- /.main-wrapper -->
 
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/* Data dari PHP */
const grafikLabels = <?= json_encode($grafik_labels) ?>;
const grafikValues = <?= json_encode($grafik_values) ?>;
const statusData   = [<?= $s_selesai ?>, <?= $s_proses ?>, <?= $s_baru ?>];
 
/* ── Grafik batang pendapatan ── */
if (grafikLabels.length > 0) {
    new Chart(document.getElementById('chartPendapatan'), {
        type: 'bar',
        data: {
            labels: grafikLabels,
            datasets: [{
                label: 'Pendapatan',
                data: grafikValues,
                backgroundColor: '#9FE1CB',
                borderColor: '#1D9E75',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 }, maxRotation: 45, autoSkip: true, maxTicksLimit: 12 }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        font: { size: 11 },
                        maxTicksLimit: 6,
                        callback: function(v) {
                            if (v === 0) return 'Rp 0';
                            if (v >= 1000000) return 'Rp ' + (v / 1000000).toLocaleString('id-ID', {maximumFractionDigits: 1}) + ' jt';
                            if (v >= 1000)    return 'Rp ' + (v / 1000).toLocaleString('id-ID', {maximumFractionDigits: 0}) + ' rb';
                            return 'Rp ' + v;
                        }
                    },
                    afterDataLimits: function(scale) {
                        const max = scale.max || 0;
                        let step;
                        if      (max <= 50000)   step = 10000;
                        else if (max <= 100000)  step = 20000;
                        else if (max <= 250000)  step = 50000;
                        else if (max <= 500000)  step = 100000;
                        else if (max <= 1000000) step = 200000;
                        else if (max <= 2500000) step = 500000;
                        else                     step = 1000000;
                        scale.max = Math.ceil((max * 1.1) / step) * step;
                        scale.min = 0;
                    }
                }
            }
        }
    });
}
 
/* ── Donut status cucian ── */
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Selesai', 'Proses', 'Baru'],
        datasets: [{
            data: statusData,
            backgroundColor: ['#1D9E75', '#EF9F27', '#378ADD'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' nota' }
            }
        }
    }
});
</script>
</body>
</html>