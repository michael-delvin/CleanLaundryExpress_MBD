<?php
/* pelanggan/tambah.php — Form Tambah Pelanggan */
session_start();
require_once '../koneksi.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
 
$active_page = 'pelanggan';
$base_path   = '../';
 
$errors = [];
$input  = ['nama_pelanggan' => '', 'no_telepon' => '', 'alamat' => ''];
 
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
 
    /* Cek duplikat nama */
    if (empty($errors['nama_pelanggan'])) {
        $n = mysqli_real_escape_string($conn, $input['nama_pelanggan']);
        $cek = mysqli_query($conn, "SELECT id_pelanggan FROM pelanggan WHERE nama_pelanggan = '$n' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $errors['nama_pelanggan'] = 'Nama pelanggan sudah terdaftar.';
        }
    }
 
    /* Simpan jika valid */
    if (empty($errors)) {
        $nama  = mysqli_real_escape_string($conn, $input['nama_pelanggan']);
        $hp    = mysqli_real_escape_string($conn, $input['no_telepon']);
        $alamat = mysqli_real_escape_string($conn, $input['alamat']);
 
        mysqli_query($conn, "
            INSERT INTO pelanggan (nama_pelanggan, no_telepon, alamat)
            VALUES ('$nama', '$hp', '$alamat')
        ");
 
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pelanggan <strong>' . e($input['nama_pelanggan']) . '</strong> berhasil ditambahkan.'];
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
    <title>Tambah Pelanggan — Clean Express Laundry</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>
 
<div class="main-wrapper">
 
    <header class="topbar">
        <div>
            <div class="topbar-title">Tambah Pelanggan</div>
            <div class="topbar-sub">
                <a href="index.php" style="color:var(--gray-500);text-decoration:none;">Pelanggan</a>
                &nbsp;/&nbsp; Tambah Baru
            </div>
        </div>
    </header>
 
    <main class="page-content">
 
        <div class="card" style="max-width:560px;">
            <div class="card-header">
                <span class="card-title">Data Pelanggan Baru</span>
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
                            <i class="ti ti-device-floppy"></i> Simpan Pelanggan
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