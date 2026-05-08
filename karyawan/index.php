<?php
/* karyawan/index.php — Daftar Karyawan */
session_start();
require_once '../koneksi.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
only_admin(); // Hanya admin yang boleh akses

$active_page = 'karyawan';
$base_path   = '../';

/* ── Hapus karyawan ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id = (int) $_POST['hapus_id'];

    // Tidak boleh hapus diri sendiri
    if ($id === (int)$_SESSION['user_id']) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Anda tidak dapat menghapus akun Anda sendiri.'];
    } else {
        // Cek apakah karyawan punya transaksi
        $cek = mysqli_query($conn, "SELECT COUNT(*) AS n FROM transaksi WHERE id_karyawan = $id");
        $row_cek = mysqli_fetch_assoc($cek);
        if ($row_cek['n'] > 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Karyawan tidak dapat dihapus karena memiliki riwayat transaksi.'];
        } else {
            mysqli_query($conn, "DELETE FROM karyawan WHERE id_karyawan = $id");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Karyawan berhasil dihapus.'];
        }
    }
    header('Location: index.php');
    exit;
}

/* ── Pencarian ── */
$search = trim($_GET['q'] ?? '');
$where  = '';
if ($search !== '') {
    $s     = mysqli_real_escape_string($conn, $search);
    $where = "WHERE nama_karyawan LIKE '%$s%' OR username LIKE '%$s%' OR no_telepon LIKE '%$s%'";
}

/* ── Data karyawan + jumlah transaksi ── */
$q = mysqli_query($conn, "
    SELECT k.id_karyawan, k.nama_karyawan, k.username, k.role, k.no_telepon, k.alamat,
           COUNT(t.id_transaksi) AS jumlah_transaksi
    FROM karyawan k
    LEFT JOIN transaksi t ON k.id_karyawan = t.id_karyawan
    $where
    GROUP BY k.id_karyawan
    ORDER BY k.role ASC, k.nama_karyawan ASC
");

$total = mysqli_query($conn, "SELECT COUNT(*) AS n FROM karyawan $where");
$total_row = mysqli_fetch_assoc($total);
$total_karyawan = (int)$total_row['n'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Karyawan — Clean Express Laundry</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>

<div class="main-wrapper">

    <header class="topbar">
        <div>
            <div class="topbar-title">Karyawan</div>
            <div class="topbar-sub">Master Data · <?= $total_karyawan ?> karyawan terdaftar</div>
        </div>
        <div class="topbar-right">
            <a href="tambah.php" class="btn btn-primary">
                <i class="ti ti-user-plus"></i> Tambah Karyawan
            </a>
        </div>
    </header>

    <main class="page-content">

        <?= flash() ?>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Daftar Karyawan</span>
                <form method="GET" style="display:flex;gap:8px;align-items:center;">
                    <div style="position:relative;">
                        <i class="ti ti-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:14px;"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                               placeholder="Cari nama, username…"
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
                            <th>Nama Karyawan</th>
                            <th>Username</th>
                            <th>Role</th>
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
                        $isSelf = ((int)$row['id_karyawan'] === (int)$_SESSION['user_id']);
                    ?>
                        <tr <?= $isSelf ? 'style="background:var(--teal-50);"' : '' ?>>
                            <td class="text-muted text-sm"><?= $no++ ?></td>
                            <td>
                                <div class="flex gap-8" style="align-items:center;">
                                    <div class="avatar"><?= inisial($row['nama_karyawan']) ?></div>
                                    <div>
                                        <span class="fw-600"><?= e($row['nama_karyawan']) ?></span>
                                        <?php if ($isSelf): ?>
                                            <span style="font-size:10px;color:var(--teal-600);font-weight:600;margin-left:6px;">( Anda )</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted text-sm">
                                <i class="ti ti-at" style="font-size:12px;margin-right:4px;"></i>
                                <?= e($row['username']) ?>
                            </td>
                            <td>
                                <?php if ($row['role'] === 'admin'): ?>
                                    <span class="badge" style="background:#EDE9FE;color:#5B21B6;">
                                        <i class="ti ti-shield-check" style="font-size:11px;margin-right:3px;"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-info">
                                        <i class="ti ti-user" style="font-size:11px;margin-right:3px;"></i> Kasir
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted text-sm">
                                <i class="ti ti-phone" style="font-size:12px;margin-right:4px;"></i>
                                <?= e($row['no_telepon'] ?: '-') ?>
                            </td>
                            <td class="text-muted text-sm" style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= e($row['alamat'] ?: '-') ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($row['jumlah_transaksi'] > 0): ?>
                                    <span class="badge badge-selesai"><?= $row['jumlah_transaksi'] ?> transaksi</span>
                                <?php else: ?>
                                    <span class="text-muted text-sm">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <div class="flex gap-8" style="justify-content:center;">
                                    <a href="edit.php?id=<?= $row['id_karyawan'] ?>"
                                       class="btn btn-outline btn-sm"
                                       title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <?php if (!$isSelf): ?>
                                    <form method="POST" onsubmit="return konfirmasiHapus(this)">
                                        <input type="hidden" name="hapus_id" value="<?= $row['id_karyawan'] ?>">
                                        <button type="submit" class="btn btn-sm"
                                                style="background:#FCEBEB;color:#A32D2D;border:1px solid #f5c6c6;"
                                                title="Hapus">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-sm" disabled
                                            style="background:var(--gray-100);color:var(--gray-400);border:1px solid var(--gray-200);cursor:not-allowed;"
                                            title="Tidak dapat hapus akun sendiri">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if ($total_karyawan === 0): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400);">
                                <i class="ti ti-id-badge" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                <?= $search ? 'Tidak ada karyawan yang cocok dengan pencarian.' : 'Belum ada data karyawan.' ?>
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
    return confirm('Hapus karyawan ini? Tindakan tidak dapat dibatalkan.');
}
</script>
</body>
</html>