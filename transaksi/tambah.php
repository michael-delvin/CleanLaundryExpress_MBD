<?php
/* transaksi/tambah.php — Form input transaksi baru */
session_start();
require_once '../koneksi.php';
require_once '../includes/functions.php';

$active_page = 'transaksi_tambah';
$base_path   = '../';

/* ── Data untuk dropdown ── */
$pelanggan_list = mysqli_query($conn, "SELECT id_pelanggan, nama_pelanggan, no_telepon FROM pelanggan ORDER BY nama_pelanggan");
$karyawan_list  = mysqli_query($conn, "SELECT id_karyawan, nama_karyawan FROM karyawan ORDER BY nama_karyawan");
$layanan_list   = mysqli_query($conn, "SELECT * FROM layanan ORDER BY nama_layanan");
$metode_list    = mysqli_query($conn, "SELECT * FROM metode_pembayaran");

/* ── Kumpulkan layanan ke array (untuk JS) ── */
$layanan_arr = [];
mysqli_data_seek($layanan_list, 0);
while ($l = mysqli_fetch_assoc($layanan_list)) $layanan_arr[] = $l;
mysqli_data_seek($layanan_list, 0);

/* ── Proses POST ── */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pelanggan  = (int)($_POST['id_pelanggan']      ?? 0);
    $id_karyawan   = (int)($_POST['id_karyawan']       ?? 0);
    $id_metode     = (int)($_POST['id_metode']         ?? 0);
    $tgl_masuk     = trim($_POST['tanggal_masuk']      ?? '');
    $status_cucian = 'Baru';
    $status_bayar  = trim($_POST['status_pembayaran']  ?? 'Belum Lunas');

    /* Detail layanan — filter dulu yang id-nya 0 / kosong */
    $layanan_ids_raw = $_POST['layanan_id'] ?? [];
    $jumlah_arr_raw  = $_POST['jumlah']     ?? [];

    /* Pasangkan id & jumlah, buang baris yang layanannya belum dipilih */
    $baris_valid = [];
    foreach ($layanan_ids_raw as $idx => $id_lay) {
        $id_lay = (int)$id_lay;
        if ($id_lay <= 0) continue;          // ← skip "-- Pilih --"
        $jumlah = max(0.5, (float)($jumlah_arr_raw[$idx] ?? 1));
        $baris_valid[] = ['id_layanan' => $id_lay, 'jumlah' => $jumlah];
    }

    /* ── Validasi ── */
    if (!$id_pelanggan) {
        $error = 'Pelanggan wajib dipilih.';
    } elseif (!$id_karyawan) {
        $error = 'Karyawan wajib dipilih.';
    } elseif (!$id_metode) {
        $error = 'Metode pembayaran wajib dipilih.';
    } elseif ($tgl_masuk === '') {
        $error = 'Tanggal masuk wajib diisi.';
    } elseif (empty($baris_valid)) {
        $error = 'Pilih minimal satu layanan sebelum menyimpan transaksi.';
    } else {

        /* ── Hitung total & estimasi selesai ── */
        $total_harga  = 0;
        $max_hari     = 0;
        $detail_items = [];

        foreach ($baris_valid as $b) {
            $id_lay = $b['id_layanan'];
            $jumlah = $b['jumlah'];

            $ql  = mysqli_query($conn, "SELECT harga, waktu FROM layanan WHERE id_layanan = $id_lay");
            $lay = mysqli_fetch_assoc($ql);
            if (!$lay) continue;

            $harga    = (int)$lay['harga'];
            $subtotal = (int)round($jumlah * $harga);
            $total_harga += $subtotal;

            preg_match('/\d+/', $lay['waktu'], $m);
            $hari = (int)($m[0] ?? 1);
            if ($hari > $max_hari) $max_hari = $hari;

            $detail_items[] = [
                'id_layanan'      => $id_lay,
                'jumlah'          => $jumlah,
                'subtotal'        => $subtotal,
                'harga_waktu_itu' => $harga,
            ];
        }

        if (empty($detail_items)) {
            $error = 'Data layanan tidak valid. Pastikan layanan yang dipilih masih tersedia.';
        } else {
            $estimasi = date('Y-m-d', strtotime("$tgl_masuk +$max_hari days"));

            /* ID transaksi berikutnya */
            $res_id = mysqli_query($conn, "SELECT COALESCE(MAX(id_transaksi),0)+1 AS next_id FROM transaksi");
            $id_txn = (int)mysqli_fetch_assoc($res_id)['next_id'];

            /* ── Simpan ke DB dalam satu transaksi ── */
            mysqli_begin_transaction($conn);
            try {
                /* INSERT transaksi */
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO transaksi
                     (id_transaksi, id_pelanggan, id_karyawan, id_metode,
                      tanggal_masuk, estimasi_waktu_selesai,
                      status_cucian, total_harga, status_pembayaran)
                     VALUES (?,?,?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt, 'iiiiissss',
                    $id_txn, $id_pelanggan, $id_karyawan, $id_metode,
                    $tgl_masuk, $estimasi, $status_cucian,
                    $total_harga, $status_bayar);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                /* INSERT detail_transaksi */
                $res_det = mysqli_query($conn,
                    "SELECT COALESCE(MAX(id_detail),0)+1 AS next_id FROM detail_transaksi");
                $id_det = (int)mysqli_fetch_assoc($res_det)['next_id'];

                foreach ($detail_items as $d) {
                    $stmt2 = mysqli_prepare($conn,
                        "INSERT INTO detail_transaksi
                         (id_detail, id_transaksi, id_layanan,
                          jumlah, subtotal, harga_waktu_itu)
                         VALUES (?,?,?,?,?,?)");
                    mysqli_stmt_bind_param($stmt2, 'iiiiii',
                        $id_det, $id_txn, $d['id_layanan'],
                        $d['jumlah'], $d['subtotal'], $d['harga_waktu_itu']);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);
                    $id_det++;
                }

                mysqli_commit($conn);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => "Transaksi <strong>#$id_txn</strong> berhasil disimpan!"
                ];
                header('Location: ../transaksi/index.php');
                exit;

            } catch (Exception $ex) {
                mysqli_rollback($conn);
                $error = 'Gagal menyimpan transaksi: ' . $ex->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Baru — Clean Express</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>

<div class="main-wrapper">

    <header class="topbar">
        <div>
            <div class="topbar-title">Transaksi Baru</div>
            <div class="topbar-sub">Input pesanan laundry pelanggan</div>
        </div>
        <div class="topbar-right">
            <a href="../transaksi/index.php" class="btn btn-outline">
                <i class="ti ti-arrow-left"></i> Kembali
            </a>
        </div>
    </header>

    <main class="page-content">

        <?php if ($error): ?>
            <div class="alert alert-danger" style="display:flex;align-items:center;gap:8px;
                 background:#FCEBEB;color:#A32D2D;border:1px solid #f5c6c6;
                 border-radius:10px;padding:12px 16px;margin-bottom:16px;">
                <i class="ti ti-alert-circle"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="formTransaksi" onsubmit="return validasiForm()">
        <div class="grid-2" style="align-items:start;">

            <!-- ── Kolom kiri: info transaksi ── -->
            <div style="display:flex;flex-direction:column;gap:16px;">

                <div class="card">
                    <div class="card-header"><span class="card-title">Informasi Transaksi</span></div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:0;">

                        <!-- Pelanggan -->
                        <div class="form-group">
                            <label class="form-label">Pelanggan <span style="color:red;">*</span></label>
                            <select name="id_pelanggan" class="form-control" required>
                                <option value="">-- Pilih pelanggan --</option>
                                <?php while ($p = mysqli_fetch_assoc($pelanggan_list)): ?>
                                <option value="<?= $p['id_pelanggan'] ?>"
                                    <?= (($_POST['id_pelanggan'] ?? '') == $p['id_pelanggan']) ? 'selected' : '' ?>>
                                    <?= e($p['nama_pelanggan']) ?> · <?= e($p['no_telepon'] ?? '-') ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Karyawan -->
                        <div class="form-group">
                            <label class="form-label">Karyawan (Kasir) <span style="color:red;">*</span></label>
                            <select name="id_karyawan" class="form-control" required>
                                <option value="">-- Pilih karyawan --</option>
                                <?php while ($k = mysqli_fetch_assoc($karyawan_list)): ?>
                                <option value="<?= $k['id_karyawan'] ?>"
                                    <?= (($_POST['id_karyawan'] ?? '') == $k['id_karyawan']) ? 'selected' : '' ?>>
                                    <?= e($k['nama_karyawan']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Tanggal Masuk -->
                        <div class="form-group">
                            <label class="form-label">Tanggal Masuk <span style="color:red;">*</span></label>
                            <input type="date" name="tanggal_masuk" class="form-control"
                                   value="<?= htmlspecialchars($_POST['tanggal_masuk'] ?? date('Y-m-d')) ?>"
                                   required>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="form-group">
                            <label class="form-label">Metode Pembayaran <span style="color:red;">*</span></label>
                            <select name="id_metode" class="form-control" required>
                                <option value="">-- Pilih metode --</option>
                                <?php while ($m = mysqli_fetch_assoc($metode_list)): ?>
                                <option value="<?= $m['id_metode'] ?>"
                                    <?= (($_POST['id_metode'] ?? '') == $m['id_metode']) ? 'selected' : '' ?>>
                                    <?= e($m['nama_metode']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Status Pembayaran -->
                        <div class="form-group">
                            <label class="form-label">Status Pembayaran <span style="color:red;">*</span></label>
                            <select name="status_pembayaran" class="form-control" required>
                                <option value="Belum Lunas" <?= (($_POST['status_pembayaran'] ?? 'Belum Lunas')==='Belum Lunas')?'selected':'' ?>>Belum Lunas</option>
                                <option value="Lunas"       <?= (($_POST['status_pembayaran'] ?? '')==='Lunas')?'selected':'' ?>>Lunas</option>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- Ringkasan -->
                <div class="card">
                    <div class="card-header"><span class="card-title">Ringkasan</span></div>
                    <div class="card-body">
                        <div class="flex-between" style="margin-bottom:8px;">
                            <span class="text-muted text-sm">Estimasi selesai</span>
                            <span class="fw-600 text-sm" id="info-estimasi">—</span>
                        </div>
                        <div class="flex-between" style="margin-bottom:16px;">
                            <span class="text-muted text-sm">Total layanan</span>
                            <span class="fw-600 text-sm" id="info-item">0 layanan</span>
                        </div>
                        <div style="border-top:1px solid var(--gray-200);padding-top:14px;" class="flex-between">
                            <span style="font-size:15px;font-weight:600;">Total Harga</span>
                            <span style="font-size:20px;font-weight:700;color:#1D9E75;" id="info-total">Rp 0</span>
                        </div>
                        <button type="submit" class="btn btn-primary"
                                style="width:100%;margin-top:16px;justify-content:center;">
                            <i class="ti ti-device-floppy"></i> Simpan Transaksi
                        </button>
                    </div>
                </div>

            </div>

            <!-- ── Kolom kanan: detail layanan ── -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Detail Layanan</span>
                    <button type="button" class="btn btn-outline btn-sm" onclick="tambahBaris()">
                        <i class="ti ti-plus"></i> Tambah Layanan
                    </button>
                </div>
                <div class="card-body">

                    <div style="display:grid;grid-template-columns:1fr 90px 90px 80px 32px;
                                gap:8px;margin-bottom:8px;">
                        <div class="text-muted text-sm fw-600">Layanan</div>
                        <div class="text-muted text-sm fw-600 text-right">Harga/sat.</div>
                        <div class="text-muted text-sm fw-600 text-right">Jumlah</div>
                        <div class="text-muted text-sm fw-600 text-right">Subtotal</div>
                        <div></div>
                    </div>

                    <div id="layanan-container"></div>

                    <p class="text-muted text-sm" id="empty-msg"
                       style="text-align:center;padding:24px 0;display:none;">
                        Belum ada layanan. Klik <strong>+ Tambah Layanan</strong> untuk mulai.
                    </p>

                    <!-- Pesan error layanan (muncul via JS) -->
                    <div id="layanan-error"
                         style="display:none;color:#A32D2D;font-size:13px;
                                margin-top:8px;padding:8px 12px;
                                background:#FCEBEB;border-radius:6px;">
                        <i class="ti ti-alert-circle"></i>
                        Pilih minimal satu layanan sebelum menyimpan.
                    </div>

                </div>
            </div>

        </div>
        </form>

    </main>
</div>

<style>
.form-group  { margin-bottom:16px; }
.form-label  { display:block;font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:6px; }
.form-control{
    width:100%;padding:9px 12px;border:1.5px solid var(--gray-200);
    border-radius:8px;font-size:14px;color:var(--gray-800);outline:none;
    transition:border-color .15s;box-sizing:border-box;font-family:inherit;
    background:#fff;
}
.form-control:focus { border-color:#1D9E75;box-shadow:0 0 0 3px rgba(29,158,117,.1); }
.form-control.is-invalid { border-color:#A32D2D; }
</style>

<script>
const layananData = <?= json_encode($layanan_arr) ?>;

function rupiah(n) {
    return 'Rp ' + parseInt(n).toLocaleString('id-ID');
}

function tambahBaris() {
    document.getElementById('empty-msg').style.display   = 'none';
    document.getElementById('layanan-error').style.display = 'none';

    const idx  = Date.now();
    const opts = layananData.map(l =>
        `<option value="${l.id_layanan}"
                 data-harga="${l.harga}"
                 data-waktu="${l.waktu}"
                 data-satuan="${l.satuan}">
           ${l.nama_layanan} (${l.satuan})
         </option>`
    ).join('');

    const row = document.createElement('div');
    row.id = 'row-' + idx;
    row.style.cssText = `
        display:grid;
        grid-template-columns:1fr 90px 90px 80px 32px;
        gap:8px;align-items:center;
        margin-bottom:10px;padding-bottom:10px;
        border-bottom:1px solid var(--gray-100);
    `;
    row.innerHTML = `
        <select name="layanan_id[]" class="form-control" onchange="hitungBaris(this)">
            <option value="">-- Pilih layanan --</option>
            ${opts}
        </select>
        <div style="text-align:right;font-size:13px;font-weight:600;" class="harga-label">—</div>
        <input type="number" name="jumlah[]" value="1" min="0.5" step="0.5"
               class="form-control" style="text-align:right;"
               oninput="hitungBaris(this)">
        <div style="text-align:right;font-size:13px;font-weight:600;color:#1D9E75;"
             class="subtotal-label">Rp 0</div>
        <button type="button" onclick="hapusBaris('row-${idx}')"
                style="background:none;border:none;cursor:pointer;
                       color:var(--gray-500);font-size:18px;padding:0;"
                title="Hapus baris">
            <i class="ti ti-x"></i>
        </button>
    `;
    document.getElementById('layanan-container').appendChild(row);
    updateRingkasan();
}

function hitungBaris(el) {
    const row      = el.closest('div[id^="row-"]');
    const select   = row.querySelector('select');
    const opt      = select.options[select.selectedIndex];
    const harga    = parseInt(opt.dataset.harga)  || 0;
    const waktu    = opt.dataset.waktu || '';
    const jumlah   = parseFloat(row.querySelector('input[name="jumlah[]"]').value) || 0;
    const subtotal = harga * jumlah;

    row.querySelector('.harga-label').textContent    = harga ? rupiah(harga) : '—';
    row.querySelector('.subtotal-label').textContent = rupiah(subtotal);
    updateRingkasan();
}

function hapusBaris(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
    const rows = document.getElementById('layanan-container').children;
    document.getElementById('empty-msg').style.display = rows.length === 0 ? 'block' : 'none';
    updateRingkasan();
}

function updateRingkasan() {
    const rows  = document.querySelectorAll('#layanan-container div[id^="row-"]');
    let total   = 0;
    let maxHari = 0;
    let items   = 0;

    rows.forEach(row => {
        const select = row.querySelector('select');
        const opt    = select.options[select.selectedIndex];
        const harga  = parseInt(opt.dataset.harga) || 0;
        const waktu  = opt.dataset.waktu || '';
        const jumlah = parseFloat(row.querySelector('input').value) || 0;
        if (harga && jumlah) {
            total  += harga * jumlah;
            items  += 1;
            const match = waktu.match(/\d+/);
            const hari  = match ? parseInt(match[0]) : 1;
            if (hari > maxHari) maxHari = hari;
        }
    });

    document.getElementById('info-total').textContent = rupiah(total);
    document.getElementById('info-item').textContent  = items + ' layanan';

    if (maxHari > 0) {
        const tglMasuk = document.querySelector('input[name="tanggal_masuk"]').value;
        if (tglMasuk) {
            const d = new Date(tglMasuk);
            d.setDate(d.getDate() + maxHari);
            document.getElementById('info-estimasi').textContent =
                d.toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' });
        }
    } else {
        document.getElementById('info-estimasi').textContent = '—';
    }
}

/* ── Validasi sebelum submit ── */
function validasiForm() {
    const rows = document.querySelectorAll('#layanan-container div[id^="row-"]');

    /* Hitung baris yang sudah dipilih layanannya */
    let dipilih = 0;
    rows.forEach(row => {
        const select = row.querySelector('select');
        if (parseInt(select.value) > 0) dipilih++;
    });

    if (dipilih === 0) {
        /* Tampilkan pesan error di area layanan */
        const errEl = document.getElementById('layanan-error');
        errEl.style.display = 'block';
        errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false; /* Batalkan submit */
    }

    document.getElementById('layanan-error').style.display = 'none';
    return true; /* Lanjutkan submit */
}

/* Update estimasi saat tanggal masuk berubah */
document.querySelector('input[name="tanggal_masuk"]')
    .addEventListener('change', updateRingkasan);

/* Tampilkan empty-msg awal & tambah satu baris default */
document.getElementById('empty-msg').style.display = 'block';
tambahBaris();
</script>
</body>
</html>