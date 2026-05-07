<?php
/* transaksi/edit.php — Update status transaksi */
require_once '../koneksi.php';
require_once '../includes/auth.php';   /* auth.php sudah handle session_start() */
require_once '../includes/functions.php';

$active_page = 'transaksi';
$base_path   = '../';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('index.php', 'danger', 'Transaksi tidak ditemukan.');

/* ── Ambil data transaksi dari DB (selalu duluan sebelum proses POST) ── */
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

/* ── Proses POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Status cucian: boleh diubah oleh semua role */
    $status_cucian = trim($_POST['status_cucian'] ?? '');

    /* Validasi nilai status cucian */
    if (!in_array($status_cucian, ['Baru', 'Proses', 'Selesai'])) {
        redirect("edit.php?id=$id", 'danger', 'Status cucian tidak valid.');
    }

    /* Status pembayaran & tanggal diambil: semua role boleh ubah */
    $status_bayar = trim($_POST['status_pembayaran'] ?? '');
    $tgl_raw      = !empty($_POST['tanggal_diambil']) ? $_POST['tanggal_diambil'] : null;

    if (!in_array($status_bayar, ['Lunas', 'Belum Lunas'])) {
        redirect("edit.php?id=$id", 'danger', 'Status pembayaran tidak valid.');
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE transaksi
         SET status_cucian = ?, status_pembayaran = ?, tanggal_diambil = ?
         WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmt, 'sssi',
        $status_cucian, $status_bayar, $tgl_raw, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

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

        <!-- ── Kolom kiri: info & detail layanan ── -->
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

        <!-- ── Kolom kanan: form update status ── -->
        <div class="card">
            <div class="card-header"><span class="card-title">Update Status</span></div>
            <div class="card-body">
                <form method="POST" action="edit.php?id=<?= $id ?>">

                    <!-- Status Cucian: semua role bisa ubah -->
                    <div class="form-group">
                        <label class="form-label">
                            Status Cucian <span style="color:red;">*</span>
                        </label>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;">
                            <?php
                            $statuses = [
                                'Baru'    => ['icon' => 'ti-clock',         'color' => '#6B7280', 'rgb' => '107,114,128'],
                                'Proses'  => ['icon' => 'ti-wash-dryclean', 'color' => '#D97706', 'rgb' => '217,119,6'],
                                'Selesai' => ['icon' => 'ti-circle-check',  'color' => '#1D9E75', 'rgb' => '29,158,117'],
                            ];
                            foreach ($statuses as $val => $cfg):
                                $checked = $txn['status_cucian'] === $val;
                            ?>
                            <label class="status-card"
                                   data-color="<?= $cfg['color'] ?>"
                                   data-rgb="<?= $cfg['rgb'] ?>"
                                   style="
                                       flex:1;min-width:90px;cursor:pointer;
                                       border:2px solid <?= $checked ? $cfg['color'] : 'var(--gray-200)' ?>;
                                       border-radius:10px;padding:12px 10px;text-align:center;
                                       background:<?= $checked ? 'rgba('.$cfg['rgb'].',.09)' : '#fff' ?>;
                                       transition:all .15s;
                                   ">
                                <input type="radio" name="status_cucian" value="<?= $val ?>"
                                       <?= $checked ? 'checked' : '' ?>
                                       style="display:none;">
                                <i class="ti <?= $cfg['icon'] ?>"
                                   style="font-size:22px;color:<?= $cfg['color'] ?>;display:block;margin-bottom:4px;"></i>
                                <span style="font-size:13px;font-weight:600;color:<?= $cfg['color'] ?>;"><?= $val ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Status Pembayaran: semua role bisa ubah -->
                    <div class="form-group" style="margin-top:8px;">
                        <label class="form-label">Status Pembayaran</label>
                        <select name="status_pembayaran" class="form-control">
                            <option value="Belum Lunas" <?= $txn['status_pembayaran']==='Belum Lunas'?'selected':'' ?>>Belum Lunas</option>
                            <option value="Lunas"       <?= $txn['status_pembayaran']==='Lunas'      ?'selected':'' ?>>Lunas</option>
                        </select>
                    </div>

                    <!-- Tanggal Diambil: semua role bisa ubah -->
                    <div class="form-group">
                        <label class="form-label">Tanggal Diambil</label>
                        <input type="date" name="tanggal_diambil" class="form-control"
                               value="<?= e($txn['tanggal_diambil'] ?? '') ?>">
                        <span class="text-muted text-sm" style="margin-top:4px;display:block;">
                            Isi jika cucian sudah diambil pelanggan.
                        </span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                        <i class="ti ti-device-floppy"></i> Simpan Perubahan
                    </button>

                </form>
            </div>
        </div>

    </div>
    </main>
</div>

<style>
.form-group  { margin-bottom:16px; }
.form-label  { display:block;font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:6px; }
.form-control {
    width:100%;padding:9px 12px;border:1.5px solid var(--gray-200);
    border-radius:8px;font-size:14px;color:var(--gray-800);outline:none;
    transition:border-color .15s;box-sizing:border-box;font-family:inherit;
    background:#fff;
}
.form-control:focus { border-color:#1D9E75;box-shadow:0 0 0 3px rgba(29,158,117,.1); }
.status-card:hover { filter:brightness(.97); }
</style>

<script>
function highlightStatus() {
    document.querySelectorAll('.status-card').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        const color = label.dataset.color;
        const rgb   = label.dataset.rgb;
        if (radio.checked) {
            label.style.borderColor = color;
            label.style.background  = `rgba(${rgb},.09)`;
        } else {
            label.style.borderColor = 'var(--gray-200)';
            label.style.background  = '#fff';
        }
    });
}

document.querySelectorAll('input[name="status_cucian"]').forEach(r => {
    r.addEventListener('change', highlightStatus);
});

highlightStatus();
</script>
</body>
</html>