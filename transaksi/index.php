<?php
/* transaksi/index.php — Daftar semua transaksi */
session_start();
require_once '../koneksi.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$active_page = 'transaksi';
$base_path   = '../';

/* ── Filter & pencarian ── */
$search        = trim($_GET['q']    ?? '');
$filter_status = $_GET['status']   ?? '';
$filter_bayar  = $_GET['bayar']    ?? '';

$where = ["1=1"];

if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(p.nama_pelanggan LIKE '%$s%' OR t.id_transaksi LIKE '%$s%')";
}
if ($filter_status !== '') {
    $s = mysqli_real_escape_string($conn, $filter_status);
    $where[] = "t.status_cucian = '$s'";
}
if ($filter_bayar !== '') {
    $s = mysqli_real_escape_string($conn, $filter_bayar);
    $where[] = "t.status_pembayaran = '$s'";
}

$where_sql = implode(' AND ', $where);

$q = mysqli_query($conn, "
    SELECT t.*, p.nama_pelanggan, k.nama_karyawan, m.nama_metode
    FROM transaksi t
    JOIN pelanggan p         ON t.id_pelanggan = p.id_pelanggan
    JOIN karyawan k          ON t.id_karyawan  = k.id_karyawan
    JOIN metode_pembayaran m ON t.id_metode    = m.id_metode
    WHERE $where_sql
    ORDER BY t.tanggal_masuk DESC, t.id_transaksi DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Transaksi — Clean Express</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>

<div class="main-wrapper">

    <header class="topbar">
        <div>
            <div class="topbar-title">Daftar Transaksi</div>
            <div class="topbar-sub">Semua nota masuk</div>
        </div>
        <div class="topbar-right">
            <a href="tambah.php" class="btn btn-primary">
                <i class="ti ti-plus"></i> Transaksi Baru
            </a>
        </div>
    </header>

    <main class="page-content">

        <?= flash() ?>

        <!-- Filter bar -->
        <form method="GET" class="card" style="margin-bottom:20px;">
            <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div style="flex:1;min-width:180px;">
                    <label class="form-label">Cari pelanggan / ID</label>
                    <input type="text" name="q" class="form-control"
                           placeholder="Nama atau nomor transaksi..."
                           value="<?= e($search) ?>">
                </div>
                <div style="min-width:140px;">
                    <label class="form-label">Status Cucian</label>
                    <select name="status" class="form-control">
                        <option value="">Semua</option>
                        <option value="Baru"    <?= $filter_status==='Baru'    ?'selected':'' ?>>Baru</option>
                        <option value="Proses"  <?= $filter_status==='Proses'  ?'selected':'' ?>>Proses</option>
                        <option value="Selesai" <?= $filter_status==='Selesai' ?'selected':'' ?>>Selesai</option>
                    </select>
                </div>
                <div style="min-width:140px;">
                    <label class="form-label">Status Bayar</label>
                    <select name="bayar" class="form-control">
                        <option value="">Semua</option>
                        <option value="Lunas"       <?= $filter_bayar==='Lunas'       ?'selected':'' ?>>Lunas</option>
                        <option value="Belum Lunas" <?= $filter_bayar==='Belum Lunas' ?'selected':'' ?>>Belum Lunas</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-search"></i> Filter</button>
                    <a href="index.php" class="btn btn-outline"><i class="ti ti-x"></i> Reset</a>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Pelanggan</th>
                            <th>Kasir</th>
                            <th>Tgl Masuk</th>
                            <th>Estimasi Selesai</th>
                            <th>Total</th>
                            <th>Metode</th>
                            <th>Status Cucian</th>
                            <th>Status Bayar</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 0;
                    while ($row = mysqli_fetch_assoc($q)):
                        $no++;
                    ?>
                        <tr>
                            <td class="fw-600">#<?= $row['id_transaksi'] ?></td>
                            <td>
                                <div class="flex gap-8">
                                    <div class="avatar"><?= inisial($row['nama_pelanggan']) ?></div>
                                    <span><?= e($row['nama_pelanggan']) ?></span>
                                </div>
                            </td>
                            <td class="text-muted text-sm"><?= e($row['nama_karyawan']) ?></td>
                            <td><?= tgl_indo($row['tanggal_masuk']) ?></td>
                            <td><?= tgl_indo($row['estimasi_waktu_selesai'] ?? '') ?></td>
                            <td class="fw-600"><?= rupiah((int)$row['total_harga']) ?></td>
                            <td class="text-muted text-sm"><?= e($row['nama_metode']) ?></td>
                            <td><?= badge_status_cucian($row['status_cucian']) ?></td>
                            <td><?= badge_status_bayar($row['status_pembayaran']) ?></td>
                            <td style="text-align:center;white-space:nowrap;">
                                <!-- Edit: semua role boleh -->
                                <a href="edit.php?id=<?= $row['id_transaksi'] ?>"
                                   class="btn btn-outline btn-sm"
                                   title="Update Status">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <!-- Hapus: hanya admin -->
                                <?php if (is_admin()): ?>
                                <a href="hapus.php?id=<?= $row['id_transaksi'] ?>"
                                   class="btn btn-danger btn-sm"
                                   title="Hapus Transaksi"
                                   onclick="return confirm('Yakin hapus transaksi #<?= $row['id_transaksi'] ?>?')">
                                    <i class="ti ti-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($no === 0): ?>
                        <tr>
                            <td colspan="10" style="text-align:center;padding:40px;color:var(--gray-500);">
                                <i class="ti ti-inbox" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                Tidak ada transaksi ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>
</body>
</html>