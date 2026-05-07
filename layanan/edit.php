<?php
/* layanan/edit.php — Form Edit Layanan */
session_start();
require_once '../koneksi.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
only_admin();

$active_page = 'layanan';
$base_path   = '../';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$q_lay = mysqli_query($conn, "SELECT * FROM layanan WHERE id_layanan = $id LIMIT 1");
if (mysqli_num_rows($q_lay) === 0) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Layanan tidak ditemukan.'];
    header('Location: index.php');
    exit;
}
$layanan = mysqli_fetch_assoc($q_lay);

$errors = [];
$input  = [
    'nama_layanan' => $layanan['nama_layanan'],
    'harga'        => $layanan['harga'],
    'satuan'       => $layanan['satuan'],
    'waktu'        => $layanan['waktu'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['nama_layanan'] = trim($_POST['nama_layanan'] ?? '');
    $input['harga']        = trim($_POST['harga']        ?? '');
    $input['satuan']       = trim($_POST['satuan']       ?? '');
    $input['waktu']        = trim($_POST['waktu']        ?? '');

    /* Validasi */
    if ($input['nama_layanan'] === '') {
        $errors['nama_layanan'] = 'Nama layanan wajib diisi.';
    } elseif (mb_strlen($input['nama_layanan']) > 100) {
        $errors['nama_layanan'] = 'Nama layanan maksimal 100 karakter.';
    }

    if ($input['harga'] === '') {
        $errors['harga'] = 'Harga wajib diisi.';
    } elseif (!is_numeric($input['harga']) || (int)$input['harga'] < 0) {
        $errors['harga'] = 'Harga harus berupa angka positif.';
    }

    if ($input['satuan'] === '') {
        $errors['satuan'] = 'Satuan wajib diisi.';
    }

    /* Cek duplikat nama (kecuali diri sendiri) */
    if (empty($errors['nama_layanan'])) {
        $n   = mysqli_real_escape_string($conn, $input['nama_layanan']);
        $cek = mysqli_query($conn, "SELECT id_layanan FROM layanan WHERE nama_layanan = '$n' AND id_layanan != $id LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $errors['nama_layanan'] = 'Nama layanan sudah digunakan layanan lain.';
        }
    }

    if (empty($errors)) {
        $nama   = mysqli_real_escape_string($conn, $input['nama_layanan']);
        $harga  = (int)$input['harga'];
        $satuan = mysqli_real_escape_string($conn, $input['satuan']);
        $waktu  = mysqli_real_escape_string($conn, $input['waktu']);

        mysqli_query($conn, "
            UPDATE layanan
            SET nama_layanan = '$nama',
                harga        = $harga,
                satuan       = '$satuan',
                waktu        = '$waktu'
            WHERE id_layanan = $id
        ");

        $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'Layanan <strong>' . e($input['nama_layanan']) . '</strong> berhasil diperbarui.'];
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Layanan — Clean Express Laundry</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>

<div class="main-wrapper">

    <header class="topbar">
        <div>
            <div class="topbar-title">Edit Layanan</div>
            <div class="topbar-sub">
                <a href="index.php" style="color:var(--gray-500);text-decoration:none;">Layanan</a>
                &nbsp;/&nbsp; <?= e($layanan['nama_layanan']) ?>
            </div>
        </div>
    </header>

    <main class="page-content">

        <div class="card" style="max-width:560px;">
            <div class="card-header">
                <span class="card-title">Edit Data Layanan</span>
                <span class="text-muted text-sm">ID #<?= $id ?></span>
            </div>
            <div class="card-body">

                <form method="POST" novalidate>

                    <!-- Nama Layanan -->
                    <div class="form-group">
                        <label class="form-label" for="nama_layanan">
                            Nama Layanan <span style="color:#A32D2D;">*</span>
                        </label>
                        <input type="text"
                               id="nama_layanan"
                               name="nama_layanan"
                               class="form-control <?= isset($errors['nama_layanan']) ? 'is-invalid' : '' ?>"
                               value="<?= e($input['nama_layanan']) ?>"
                               placeholder="Contoh: Cuci Setrika Reguler"
                               maxlength="100"
                               autofocus>
                        <?php if (isset($errors['nama_layanan'])): ?>
                            <div class="form-error"><?= $errors['nama_layanan'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Harga -->
                    <div class="form-group">
                        <label class="form-label" for="harga">
                            Harga (Rp) <span style="color:#A32D2D;">*</span>
                        </label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;color:var(--gray-500);font-weight:600;">Rp</span>
                            <input type="number"
                                   id="harga"
                                   name="harga"
                                   class="form-control <?= isset($errors['harga']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($input['harga']) ?>"
                                   placeholder="0"
                                   min="0"
                                   style="padding-left:36px;">
                        </div>
                        <?php if (isset($errors['harga'])): ?>
                            <div class="form-error"><?= $errors['harga'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Satuan & Waktu -->
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="satuan">
                                Satuan <span style="color:#A32D2D;">*</span>
                            </label>
                            <select id="satuan" name="satuan"
                                    class="form-control <?= isset($errors['satuan']) ? 'is-invalid' : '' ?>">
                                <option value="Kg"  <?= $input['satuan'] === 'Kg'  ? 'selected' : '' ?>>Kg</option>
                                <option value="Pcs" <?= $input['satuan'] === 'Pcs' ? 'selected' : '' ?>>Pcs</option>
                                <option value="Item"<?= $input['satuan'] === 'Item'? 'selected' : '' ?>>Item</option>
                            </select>
                            <?php if (isset($errors['satuan'])): ?>
                                <div class="form-error"><?= $errors['satuan'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="waktu">Estimasi Waktu</label>
                            <input type="text"
                                   id="waktu"
                                   name="waktu"
                                   class="form-control"
                                   value="<?= e($input['waktu']) ?>"
                                   placeholder="Contoh: 3 Hari"
                                   maxlength="10">
                        </div>
                    </div>

                    <div class="flex gap-8" style="margin-top:24px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i> Simpan Perubahan
                        </button>
                        <a href="index.php" class="btn btn-outline">Batal</a>
                    </div>

                </form>
            </div>
        </div>

    </main>
</div>

<style>
.form-group { margin-bottom: 18px; }
.form-label { display:block; font-size:13px; font-weight:600; color:var(--gray-700); margin-bottom:6px; }
.form-control {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 14px;
    color: var(--gray-800);
    outline: none;
    transition: border-color .15s;
    box-sizing: border-box;
    font-family: inherit;
    background: #fff;
}
.form-control:focus { border-color: #1D9E75; box-shadow: 0 0 0 3px rgba(29,158,117,.1); }
.form-control.is-invalid { border-color: #A32D2D; }
.form-error { font-size: 12px; color: #A32D2D; margin-top: 5px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
</style>
</body>
</html>