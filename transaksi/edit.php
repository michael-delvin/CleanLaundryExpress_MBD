<?php
/* transaksi/edit.php — Update status cucian & pembayaran */
session_start();
require_once '../koneksi.php';
require_once '../includes/functions.php';

$active_page = 'transaksi';
$base_path   = '../';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('index.php', 'danger', 'Transaksi tidak ditemukan.');

$q = mysqli_query($conn, "
    SELECT t.*, p.nama_pelanggan, k.nama_karyawan, m.nama_metode
    FROM transaksi t
    JOIN pelanggan p         ON t.id_pelanggan = p.id_pelanggan
    JOIN karyawan k          ON t.id_karyawan  = k.id_karyawan
    JOIN metode_pembayaran m ON t.id_metode    = m.id_metode
    WHERE t.id_transaksi = $id
");
$txn = mysqli_fetch_assoc($q);
if (!$txn) redirect('index.php', 'danger', 'Transaksi tidak ditemukan.');

/* Detail layanan */
$q_det = mysqli_query($conn, "
    SELECT dt.*, l.nama_layanan, l.satuan
    FROM detail_transaksi dt
    JOIN layanan l ON dt.id_layanan = l.id_layanan
    WHERE dt.id_transaksi = $id
");

/* Proses POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_cucian = $_POST['status_cucian'];
    $status_bayar  = $_POST['status_pembayaran'];
    $tgl_diambil   = !empty($_POST['tanggal_diambil']) ? "'".$_POST['tanggal_diambil']."'" : 'NULL';

    $stmt = mysqli_prepare($conn,
        "UPDATE transaksi
         SET status_cucian=?, status_pembayaran=?, tanggal_diambil=?
         WHERE id_transaksi=?");
    $tgl_raw = !empty($_POST['tanggal_diambil']) ? $_POST['tanggal_diambil'] : null;
    mysqli_stmt_bind_param($stmt, 'sssi',
        $status_cucian, $status_bayar, $tgl_raw, $id);
    mysqli_stmt_execute($stmt);

    redirect('index.php', 'success', "Transaksi #$id berhasil diperbarui.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi #<?= $id ?> — Clean Express</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>

<div class="main-wrapper">

    <header class="topbar">
        <div>
            <div class="topbar-title">Edit Transaksi #<?= $id ?></div>
            <div class="topbar-sub"><?= e($txn['nama_pelanggan']) ?> · <?= tgl_indo($txn['tanggal_masuk']) ?></div>
        </div>
        <div class="topbar-right">
            <a href="index.php" class="btn btn-outline">
                <i class="ti ti-arrow-left"></i> Kembali
            </a>
        </div>
    </header>

    <main class="page-content">
    <div class="grid-2" style="align-items:start;">

        <!-- Info ringkasan -->
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
                <div class="card-header"><span class="card-title">Informasi Transaksi</span></div>
                <div class="card-body">
                    <table style="width:100%;font-size:13px;">
                        <tr><td class="text-muted" style="padding:6px 0;width:130px;">Pelanggan</td>
                            <td class="fw-600"><?= e($txn['nama_pelanggan']) ?></td></tr>
                        <tr><td class="text-muted" style="padding:6px 0;">Kasir</td>
                            <td><?= e($txn['nama_karyawan']) ?></td></tr>
                        <tr><td class="text-muted" style="padding:6px 0;">Metode</td>
                            <td><?= e($txn['nama_metode']) ?></td></tr>
                        <tr><td class="text-muted" style="padding:6px 0;">Tgl Masuk</td>
                            <td><?= tgl_indo($txn['tanggal_masuk']) ?></td></tr>
                        <tr><td class="text-muted" style="padding:6px 0;">Estimasi Selesai</td>
                            <td><?= tgl_indo($txn['estimasi_waktu_selesai'] ?? '') ?></td></tr>
                        <tr><td class="text-muted" style="padding:6px 0;">Total</td>
                            <td class="fw-600" style="font-size:16px;color:#1D9E75;"><?= rupiah((int)$txn['total_harga']) ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Detail layanan -->
            <div class="card">
                <div class="card-header"><span class="card-title">Detail Layanan</span></div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Layanan</th>
                                <th style="text-align:right;">Harga</th>
                                <th style="text-align:right;">Jml</th>
                                <th style="text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($det = mysqli_fetch_assoc($q_det)): ?>
                            <tr>
                                <td><?= e($det['nama_layanan']) ?>
                                    <span class="text-muted text-sm">/ <?= e($det['satuan']) ?></span></td>
                                <td style="text-align:right;"><?= rupiah((int)$det['harga_waktu_itu']) ?></td>
                                <td style="text-align:right;"><?= $det['jumlah'] ?></td>
                                <td style="text-align:right;" class="fw-600"><?= rupiah((int)$det['subtotal']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Form update status -->
        <div class="card">
            <div class="card-header"><span class="card-title">Update Status</span></div>
            <div class="card-body">
                <form method="POST" style="display:flex;flex-direction:column;gap:0;">

                    <div class="form-group">
                        <label class="form-label">Status Cucian</label>
                        <select name="status_cucian" class="form-control">
                            <option value="Baru"    <?= $txn['status_cucian']==='Baru'    ?'selected':'' ?>>Baru</option>
                            <option value="Proses"  <?= $txn['status_cucian']==='Proses'  ?'selected':'' ?>>Proses</option>
                            <option value="Selesai" <?= $txn['status_cucian']==='Selesai' ?'selected':'' ?>>Selesai</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Pembayaran</label>
                        <select name="status_pembayaran" class="form-control">
                            <option value="Belum Lunas" <?= $txn['status_pembayaran']==='Belum Lunas'?'selected':'' ?>>Belum Lunas</option>
                            <option value="Lunas"       <?= $txn['status_pembayaran']==='Lunas'      ?'selected':'' ?>>Lunas</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Diambil</label>
                        <input type="date" name="tanggal_diambil" class="form-control"
                               value="<?= e($txn['tanggal_diambil'] ?? '') ?>">
                        <span class="text-muted text-sm" style="margin-top:4px;display:block;">Isi jika cucian sudah diambil pelanggan.</span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="justify-content:center;">
                        <i class="ti ti-device-floppy"></i> Simpan Perubahan
                    </button>

                </form>
            </div>
        </div>

    </div>
    </main>
</div>
</body>
</html>
