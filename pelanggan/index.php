<?php
/* pelanggan/index.php — Daftar Pelanggan */
session_start();
require_once '../koneksi.php';
require_once '../includes/functions.php';
 
$active_page = 'pelanggan';
$base_path   = '../';
 
/* ── Hapus pelanggan ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id = (int) $_POST['hapus_id'];
    // Cek apakah pelanggan punya transaksi
    $cek = mysqli_query($conn, "SELECT COUNT(*) AS n FROM transaksi WHERE id_pelanggan = $id");
    $row_cek = mysqli_fetch_assoc($cek);
    if ($row_cek['n'] > 0) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Pelanggan tidak dapat dihapus karena memiliki riwayat transaksi.'];
    } else {
        mysqli_query($conn, "DELETE FROM pelanggan WHERE id_pelanggan = $id");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pelanggan berhasil dihapus.'];
    }
    header('Location: index.php');
    exit;
}
 
/* ── Pencarian ── */
$search = trim($_GET['q'] ?? '');
$where  = '';
if ($search !== '') {
    $s     = mysqli_real_escape_string($conn, $search);
    $where = "WHERE nama_pelanggan LIKE '%$s%' OR no_telepon LIKE '%$s%' OR alamat LIKE '%$s%'";
}
 
/* ── Data pelanggan + jumlah transaksi ── */
$q = mysqli_query($conn, "
    SELECT p.id_pelanggan, p.nama_pelanggan, p.no_telepon, p.alamat,
           COUNT(t.id_transaksi) AS jumlah_transaksi
    FROM pelanggan p
    LEFT JOIN transaksi t ON p.id_pelanggan = t.id_pelanggan
    $where
    GROUP BY p.id_pelanggan
    ORDER BY p.nama_pelanggan ASC
");
 
$total = mysqli_query($conn, "SELECT COUNT(*) AS n FROM pelanggan $where");
$total_row = mysqli_fetch_assoc($total);
$total_pelanggan = (int)$total_row['n'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pelanggan — Clean Express Laundry</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>
 
<div class="main-wrapper">
 
    <header class="topbar">
        <div>
            <div class="topbar-title">Pelanggan</div>
            <div class="topbar-sub">Master Data · <?= $total_pelanggan ?> pelanggan terdaftar</div>
        </div>
        <div class="topbar-right">
            <a href="tambah.php" class="btn btn-primary">
                <i class="ti ti-user-plus"></i> Tambah Pelanggan
            </a>
        </div>
    </header>
 
    <main class="page-content">
 
        <?= flash() ?>
 
        <div class="card">
            <div class="card-header">
                <span class="card-title">Daftar Pelanggan</span>
                <!-- Search -->
                <form method="GET" style="display:flex;gap:8px;align-items:center;">
                    <div style="position:relative;">
                        <i class="ti ti-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:14px;"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                               placeholder="Cari nama, no. HP, alamat…"
                               style="padding:7px 12px 7px 32px;border:1px solid var(--gray-200);border-radius:8px;font-size:13px;width:240px;outline:none;color:var(--gray-800);">
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
                            <th>Nama Pelanggan</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <th style="text-align:center;">Transaksi</th>
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
                                    <div class="avatar"><?= inisial($row['nama_pelanggan']) ?></div>
                                    <span class="fw-600"><?= e($row['nama_pelanggan']) ?></span>
                                </div>
                            </td>
                            <td class="text-muted text-sm">
                                <i class="ti ti-phone" style="font-size:12px;margin-right:4px;"></i>
                                <?= e($row['no_telepon'] ?: '-') ?>
                            </td>
                            <td class="text-muted text-sm" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= e($row['alamat'] ?: '-') ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($row['jumlah_transaksi'] > 0): ?>
                                    <span class="badge badge-info"><?= $row['jumlah_transaksi'] ?> transaksi</span>
                                <?php else: ?>
                                    <span class="text-muted text-sm">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <div class="flex gap-8" style="justify-content:center;">
                                    <a href="edit.php?id=<?= $row['id_pelanggan'] ?>"
                                       class="btn btn-outline btn-sm"
                                       title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <!-- Tombol hapus -->
                                    <form method="POST" onsubmit="return konfirmasiHapus(this)">
                                        <input type="hidden" name="hapus_id" value="<?= $row['id_pelanggan'] ?>">
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
 
                    <?php if ($total_pelanggan === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400);">
                                <i class="ti ti-users" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                <?= $search ? 'Tidak ada pelanggan yang cocok dengan pencarian.' : 'Belum ada data pelanggan.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
 
    </main>
</div>
 
<script>
function konfirmasiHapus(form) {
    return confirm('Hapus pelanggan ini? Tindakan tidak dapat dibatalkan.');
}
</script>
</body>
</html>