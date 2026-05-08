<?php
/* laporan/index.php — Laporan keuangan */
session_start();
require_once '../koneksi.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
only_admin();

$active_page = 'laporan';
$base_path   = '../';

/* ── Rentang tanggal ── */
$dari  = $_GET['dari']  ?? date('Y-m-01');        // awal bulan ini
$sampai= $_GET['sampai']?? date('Y-m-d');

$dari_esc   = mysqli_real_escape_string($conn, $dari);
$sampai_esc = mysqli_real_escape_string($conn, $sampai);

/* ── Ringkasan periode ── */
$q_sum = mysqli_query($conn, "
    SELECT
        COUNT(*)                                     AS total_nota,
        COALESCE(SUM(total_harga),0)                 AS total_omzet,
        COALESCE(SUM(CASE WHEN status_pembayaran='Lunas' THEN total_harga END),0) AS terbayar,
        COALESCE(SUM(CASE WHEN status_pembayaran='Belum Lunas' THEN total_harga END),0) AS piutang
    FROM transaksi
    WHERE tanggal_masuk BETWEEN '$dari_esc' AND '$sampai_esc'
");
$sum = mysqli_fetch_assoc($q_sum);

/* ── Pendapatan per layanan ── */
$q_lay = mysqli_query($conn, "
    SELECT l.nama_layanan,
           SUM(dt.jumlah)    AS total_unit,
           SUM(dt.subtotal)  AS total_pendapatan
    FROM detail_transaksi dt
    JOIN layanan l ON dt.id_layanan = l.id_layanan
    JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
    WHERE t.tanggal_masuk BETWEEN '$dari_esc' AND '$sampai_esc'
    GROUP BY l.id_layanan
    ORDER BY total_pendapatan DESC
");

/* ── Data grafik harian ── */
$q_grafik = mysqli_query($conn, "
    SELECT DATE_FORMAT(tanggal_masuk,'%d %b') AS hari,
           SUM(CASE WHEN status_pembayaran='Lunas' THEN total_harga ELSE 0 END) AS total
    FROM transaksi
    WHERE tanggal_masuk BETWEEN '$dari_esc' AND '$sampai_esc'
    GROUP BY tanggal_masuk
    ORDER BY tanggal_masuk
");
$g_labels = $g_values = [];
while ($gr = mysqli_fetch_assoc($q_grafik)) {
    $g_labels[] = $gr['hari'];
    $g_values[] = (int)$gr['total'];
}

/* ── Daftar transaksi ── */
$q_txn = mysqli_query($conn, "
    SELECT t.*, p.nama_pelanggan, m.nama_metode
    FROM transaksi t
    JOIN pelanggan p         ON t.id_pelanggan = p.id_pelanggan
    JOIN metode_pembayaran m ON t.id_metode    = m.id_metode
    WHERE t.tanggal_masuk BETWEEN '$dari_esc' AND '$sampai_esc'
    ORDER BY t.tanggal_masuk DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan — Clean Express</title>
    <?php include '../includes/sidebar.php'; ?>
    <style>
    @media print {
        .sidebar, .topbar, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
    }
    </style>
</head>
<body>

<div class="main-wrapper">

    <header class="topbar no-print">
        <div>
            <div class="topbar-title">Laporan Keuangan</div>
            <div class="topbar-sub"><?= tgl_indo($dari) ?> — <?= tgl_indo($sampai) ?></div>
        </div>
        <div class="topbar-right">
            <button onclick="window.print()" class="btn btn-outline">
                <i class="ti ti-printer"></i> Cetak
            </button>
        </div>
    </header>

    <main class="page-content">

        <!-- Filter tanggal -->
        <form method="GET" class="card mb-24 no-print">
            <div class="card-body" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <div>
                    <label class="form-label">Dari</label>
                    <input type="date" name="dari" class="form-control" value="<?= e($dari) ?>">
                </div>
                <div>
                    <label class="form-label">Sampai</label>
                    <input type="date" name="sampai" class="form-control" value="<?= e($sampai) ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter"></i> Tampilkan</button>
                <!-- Shortcut -->
                <a href="?dari=<?=date('Y-m-d')?>&sampai=<?=date('Y-m-d')?>" class="btn btn-outline btn-sm">Hari ini</a>
                <a href="?dari=<?=date('Y-m-01')?>&sampai=<?=date('Y-m-d')?>" class="btn btn-outline btn-sm">Bulan ini</a>
                <a href="?dari=<?=date('Y-m-d',strtotime('-30 days'))?>&sampai=<?=date('Y-m-d')?>" class="btn btn-outline btn-sm">30 Hari</a>
            </div>
        </form>

        <!-- Metrik -->
        <div class="metrics-grid mb-24">
            <div class="metric-card">
                <div class="metric-icon" style="background:#E1F5EE;color:#1D9E75;"><i class="ti ti-cash"></i></div>
                <div class="metric-label">Total Omzet</div>
                <div class="metric-value"><?= rupiah((int)$sum['total_omzet']) ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background:#E1F5EE;color:#1D9E75;"><i class="ti ti-circle-check"></i></div>
                <div class="metric-label">Sudah Terbayar</div>
                <div class="metric-value"><?= rupiah((int)$sum['terbayar']) ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background:#FCEBEB;color:#A32D2D;"><i class="ti ti-clock"></i></div>
                <div class="metric-label">Piutang</div>
                <div class="metric-value" style="color:#A32D2D;"><?= rupiah((int)$sum['piutang']) ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background:#E6F1FB;color:#185FA5;"><i class="ti ti-receipt"></i></div>
                <div class="metric-label">Jumlah Nota</div>
                <div class="metric-value"><?= (int)$sum['total_nota'] ?></div>
            </div>
        </div>

        <!-- Grafik -->
        <?php if (!empty($g_labels)): ?>
        <div class="card mb-24">
            <div class="card-header"><span class="card-title">Grafik Pendapatan Harian</span></div>
            <div class="card-body">
                <div style="position:relative;width:100%;height:250px;">
                    <canvas id="chartLaporan" role="img" aria-label="Grafik pendapatan harian laporan"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Per layanan + transaksi -->
        <div class="grid-2" style="align-items:start;">

            <div class="card">
                <div class="card-header"><span class="card-title">Pendapatan per Layanan</span></div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Layanan</th>
                                <th style="text-align:right;">Unit</th>
                                <th style="text-align:right;">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $total_omzet_lay = 0;
                        while ($lay = mysqli_fetch_assoc($q_lay)):
                            $total_omzet_lay += $lay['total_pendapatan'];
                        ?>
                            <tr>
                                <td><?= e($lay['nama_layanan']) ?></td>
                                <td style="text-align:right;"><?= $lay['total_unit'] ?></td>
                                <td style="text-align:right;" class="fw-600"><?= rupiah((int)$lay['total_pendapatan']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                            <tr style="background:var(--gray-50);border-top:2px solid var(--gray-200);">
                                <td class="fw-600">Total</td>
                                <td></td>
                                <td style="text-align:right;" class="fw-600" style="color:#1D9E75;"><?= rupiah($total_omzet_lay) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-title">Rincian Transaksi</span></div>
                <div class="table-wrap" style="max-height:400px;overflow-y:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pelanggan</th>
                                <th>Tgl</th>
                                <th style="text-align:right;">Total</th>
                                <th>Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($txn = mysqli_fetch_assoc($q_txn)): ?>
                            <tr>
                                <td class="fw-600">#<?= $txn['id_transaksi'] ?></td>
                                <td><?= e($txn['nama_pelanggan']) ?></td>
                                <td class="text-muted text-sm"><?= tgl_indo($txn['tanggal_masuk']) ?></td>
                                <td style="text-align:right;" class="fw-600"><?= rupiah((int)$txn['total_harga']) ?></td>
                                <td><?= badge_status_bayar($txn['status_pembayaran']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
<?php if (!empty($g_labels)): ?>
new Chart(document.getElementById('chartLaporan'), {
    type: 'line',
    data: {
        labels: <?= json_encode($g_labels) ?>,
        datasets: [{
            label: 'Pendapatan',
            data: <?= json_encode($g_values) ?>,
            borderColor: '#1D9E75',
            backgroundColor: 'rgba(29,158,117,.08)',
            borderWidth: 2,
            fill: true,
            tension: 0.3,
            pointBackgroundColor: '#1D9E75',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID') } }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { grid: { color: 'rgba(0,0,0,0.05)' },
                 ticks: { font: { size: 11 }, callback: v => 'Rp '+(v/1000).toFixed(0)+'rb' } }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>