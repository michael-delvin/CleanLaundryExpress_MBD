<?php
/* pelanggan/edit.php — Form Edit Pelanggan */
session_start();
require_once '../koneksi.php';
require_once '../includes/functions.php';
 
$active_page = 'pelanggan';
$base_path   = '../';
 
/* Ambil data yang akan diedit */
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}
 
$q_pel = mysqli_query($conn, "SELECT * FROM pelanggan WHERE id_pelanggan = $id LIMIT 1");
if (mysqli_num_rows($q_pel) === 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Pelanggan tidak ditemukan.'];
    header('Location: index.php');
    exit;
}
$pelanggan = mysqli_fetch_assoc($q_pel);
 
$errors = [];
$input  = [
    'nama_pelanggan' => $pelanggan['nama_pelanggan'],
    'no_telepon'     => $pelanggan['no_telepon'],
    'alamat'         => $pelanggan['alamat'],
];
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['nama_pelanggan'] = trim($_POST['nama_pelanggan'] ?? '');
    $input['no_telepon']     = trim($_POST['no_telepon']     ?? '');
    $input['alamat']         = trim($_POST['alamat']         ?? '');
 
    /* Validasi */
    if ($input['nama_pelanggan'] === '') {
        $errors['nama_pelanggan'] = 'Nama pelanggan wajib diisi.';
    } elseif (mb_strlen($input['nama_pelanggan']) > 100) {
        $errors['nama_pelanggan'] = 'Nama maksimal 100 karakter.';
    }
 
    if ($input['no_telepon'] !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $input['no_telepon'])) {
        $errors['no_telepon'] = 'Format nomor telepon tidak valid.';
    }
 
    /* Cek duplikat nama (kecuali diri sendiri) */
    if (empty($errors['nama_pelanggan'])) {
        $n = mysqli_real_escape_string($conn, $input['nama_pelanggan']);
        $cek = mysqli_query($conn, "SELECT id_pelanggan FROM pelanggan WHERE nama_pelanggan = '$n' AND id_pelanggan != $id LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $errors['nama_pelanggan'] = 'Nama pelanggan sudah digunakan pelanggan lain.';
        }
    }
 
    /* Update jika valid */
    if (empty($errors)) {
        $nama   = mysqli_real_escape_string($conn, $input['nama_pelanggan']);
        $hp     = mysqli_real_escape_string($conn, $input['no_telepon']);
        $alamat = mysqli_real_escape_string($conn, $input['alamat']);
 
        mysqli_query($conn, "
            UPDATE pelanggan
            SET nama_pelanggan = '$nama',
                no_telepon     = '$hp',
                alamat         = '$alamat'
            WHERE id_pelanggan = $id
        ");
 
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data pelanggan <strong>' . e($input['nama_pelanggan']) . '</strong> berhasil diperbarui.'];
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
    <title>Edit Pelanggan — Clean Express Laundry</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>
 
<div class="main-wrapper">
 
    <header class="topbar">
        <div>
            <div class="topbar-title">Edit Pelanggan</div>
            <div class="topbar-sub">
                <a href="index.php" style="color:var(--gray-500);text-decoration:none;">Pelanggan</a>
                &nbsp;/&nbsp; <?= e($pelanggan['nama_pelanggan']) ?>
            </div>
        </div>
    </header>
 
    <main class="page-content">
 
        <div class="card" style="max-width:560px;">
            <div class="card-header">
                <span class="card-title">
                    <div class="flex gap-8" style="align-items:center;">
                        <div class="avatar"><?= inisial($pelanggan['nama_pelanggan']) ?></div>
                        Edit Data Pelanggan
                    </div>
                </span>
                <span class="text-muted text-sm">ID #<?= $id ?></span>
            </div>
            <div class="card-body">
 
                <form method="POST" novalidate>
 
                    <!-- Nama Pelanggan -->
                    <div class="form-group">
                        <label class="form-label" for="nama_pelanggan">
                            Nama Pelanggan <span style="color:#A32D2D;">*</span>
                        </label>
                        <input type="text"
                               id="nama_pelanggan"
                               name="nama_pelanggan"
                               class="form-control <?= isset($errors['nama_pelanggan']) ? 'is-invalid' : '' ?>"
                               value="<?= e($input['nama_pelanggan']) ?>"
                               placeholder="Contoh: Budi Santoso"
                               maxlength="100"
                               autofocus>
                        <?php if (isset($errors['nama_pelanggan'])): ?>
                            <div class="form-error"><?= $errors['nama_pelanggan'] ?></div>
                        <?php endif; ?>
                    </div>
 
                    <!-- No. Telepon -->
                    <div class="form-group">
                        <label class="form-label" for="no_telepon">No. Telepon</label>
                        <input type="text"
                               id="no_telepon"
                               name="no_telepon"
                               class="form-control <?= isset($errors['no_telepon']) ? 'is-invalid' : '' ?>"
                               value="<?= e($input['no_telepon']) ?>"
                               placeholder="Contoh: 081234567890"
                               maxlength="20">
                        <?php if (isset($errors['no_telepon'])): ?>
                            <div class="form-error"><?= $errors['no_telepon'] ?></div>
                        <?php endif; ?>
                    </div>
 
                    <!-- Alamat -->
                    <div class="form-group">
                        <label class="form-label" for="alamat">Alamat</label>
                        <textarea id="alamat"
                                  name="alamat"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Contoh: Jl. Merdeka No. 1, Pontianak"
                                  maxlength="255"><?= e($input['alamat']) ?></textarea>
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
}
.form-control:focus { border-color: #1D9E75; box-shadow: 0 0 0 3px rgba(29,158,117,.1); }
.form-control.is-invalid { border-color: #A32D2D; }
.form-error { font-size: 12px; color: #A32D2D; margin-top: 5px; }
textarea.form-control { resize: vertical; }
</style>
</body>
</html>