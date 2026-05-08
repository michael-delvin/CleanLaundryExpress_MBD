<?php
/* layanan/index.php — Daftar Layanan */
session_start();
require_once '../koneksi.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
only_admin();

$active_page = 'layanan';
$base_path   = '../';

/* ── Hapus layanan ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id = (int) $_POST['hapus_id'];
    // Cek apakah layanan digunakan di detail_transaksi
    $cek = mysqli_query($conn, "SELECT COUNT(*) AS n FROM detail_transaksi WHERE id_layanan = $id");
    $row_cek = mysqli_fetch_assoc($cek);
    if ($row_cek['n'] > 0) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Layanan tidak dapat dihapus karena sudah digunakan dalam transaksi.'];
    } else {
        mysqli_query($conn, "DELETE FROM layanan WHERE id_layanan = $id");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Layanan berhasil dihapus.'];
    }
    header('Location: index.php');
    exit;
}

/* ── Pencarian ── */
$search = trim($_GET['q'] ?? '');
$where  = '';
if ($search !== '') {
    $s     = mysqli_real_escape_string($conn, $search);
    $where = "WHERE nama_layanan LIKE '%$s%' OR satuan LIKE '%$s%'";
}

/* ── Data layanan + jumlah pemakaian ── */
$q = mysqli_query($conn, "
    SELECT l.id_layanan, l.nama_layanan, l.harga, l.satuan, l.waktu,
           COUNT(dt.id_detail) AS jumlah_pakai
    FROM layanan l
    LEFT JOIN detail_transaksi dt ON l.id_layanan = dt.id_layanan
    $where
    GROUP BY l.id_layanan
    ORDER BY l.id_layanan ASC
");

$total = mysqli_query($conn, "SELECT COUNT(*) AS n FROM layanan $where");
$total_row = mysqli_fetch_assoc($total);
$total_layanan = (int)$total_row['n'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Layanan — Clean Express Laundry</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>

<div class="main-wrapper">

    <header class="topbar">
        <div>
            <div class="topbar-title">Layanan</div>
            <div class="topbar-sub">Master Data · <?= $total_layanan ?> layanan tersedia</div>
        </div>
        <div class="topbar-right">
            <a href="tambah.php" class="btn btn-primary">
                <i class="ti ti-plus"></i> Tambah Layanan
            </a>
        </div>
    </header>

    <main class="page-content">

        <?= flash() ?>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Daftar Layanan</span>
                <!-- Search -->
                <form method="GET" style="display:flex;gap:8px;align-items:center;">
                    <div style="position:relative;">
                        <i class="ti ti-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:14px;"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                               placeholder="Cari nama layanan…"
                               style="padding:7px 12px 7px 32px;border:1px solid var(--gray-200);border-radius:8px;font-size:13px;width:220px;outline:none;color:var(--gray-800);">
                    </div>
                    <button type="submit" class="btn btn-outline btn-sm">Cari</button>
                    <?php if ($search): ?>
                        <a href="index.php" class="btn btn-outline btn-sm">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Nama Layanan</th>
                            <th>Harga</th>
                            <th>Satuan</th>
                            <th>Estimasi Waktu</th>
                            <th style="text-align:center;">Digunakan</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($q)):
                    ?>
                        <tr>
                            <td class="text-muted text-sm"><?= $no++ ?></td>
                            <td>
                                <div class="flex gap-8" style="align-items:center;">
                                    <div class="layanan-icon">
                                        <i class="ti ti-wash"></i>
                                    </div>
                                    <span class="fw-600"><?= e($row['nama_layanan']) ?></span>
                                </div>
                            </td>
                            <td class="fw-600" style="color:var(--teal-600);"><?= rupiah((int)$row['harga']) ?></td>
                            <td>
                                <span class="badge badge-info">/ <?= e($row['satuan']) ?></span>
                            </td>
                            <td class="text-muted text-sm">
                                <i class="ti ti-clock" style="font-size:12px;margin-right:4px;"></i>
                                <?= e($row['waktu'] ?: '-') ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($row['jumlah_pakai'] > 0): ?>
                                    <span class="badge badge-selesai"><?= $row['jumlah_pakai'] ?>x terpakai</span>
                                <?php else: ?>
                                    <span class="text-muted text-sm">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <div class="flex gap-8" style="justify-content:center;">
                                    <a href="edit.php?id=<?= $row['id_layanan'] ?>"
                                       class="btn btn-outline btn-sm"
                                       title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <form method="POST" onsubmit="return konfirmasiHapus(this)">
                                        <input type="hidden" name="hapus_id" value="<?= $row['id_layanan'] ?>">
                                        <button type="submit" class="btn btn-sm"
                                                style="background:#FCEBEB;color:#A32D2D;border:1px solid #f5c6c6;"
                                                title="Hapus">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if ($total_layanan === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400);">
                                <i class="ti ti-ironing" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                <?= $search ? 'Tidak ada layanan yang cocok dengan pencarian.' : 'Belum ada data layanan.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<style>
.layanan-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: var(--teal-50);
    color: var(--teal-600);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}
</style>

<script>
function konfirmasiHapus(form) {
    return confirm('Hapus layanan ini? Tindakan tidak dapat dibatalkan.');
}
</script>
</body>
</html>