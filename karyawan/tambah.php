<?php
/* karyawan/tambah.php — Form Tambah Karyawan */
session_start();
require_once '../koneksi.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
only_admin();

$active_page = 'karyawan';
$base_path   = '../';

$errors = [];
$input  = [
    'nama_karyawan' => '',
    'username'      => '',
    'role'          => 'kasir',
    'no_telepon'    => '',
    'alamat'        => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['nama_karyawan'] = trim($_POST['nama_karyawan'] ?? '');
    $input['username']      = trim($_POST['username']      ?? '');
    $input['role']          = trim($_POST['role']          ?? 'kasir');
    $input['no_telepon']    = trim($_POST['no_telepon']    ?? '');
    $input['alamat']        = trim($_POST['alamat']        ?? '');
    $password               = trim($_POST['password']      ?? '');
    $password_konfirm       = trim($_POST['password_konfirm'] ?? '');

    /* Validasi nama */
    if ($input['nama_karyawan'] === '') {
        $errors['nama_karyawan'] = 'Nama karyawan wajib diisi.';
    } elseif (mb_strlen($input['nama_karyawan']) > 100) {
        $errors['nama_karyawan'] = 'Nama maksimal 100 karakter.';
    }

    /* Validasi username */
    if ($input['username'] === '') {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $input['username'])) {
        $errors['username'] = 'Username hanya boleh huruf, angka, underscore, 3–50 karakter.';
    } else {
        $u   = mysqli_real_escape_string($conn, $input['username']);
        $cek = mysqli_query($conn, "SELECT id_karyawan FROM karyawan WHERE username = '$u' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $errors['username'] = 'Username sudah digunakan.';
        }
    }

    /* Validasi password */
    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (mb_strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter.';
    } elseif ($password !== $password_konfirm) {
        $errors['password_konfirm'] = 'Konfirmasi password tidak cocok.';
    }

    /* Validasi role */
    if (!in_array($input['role'], ['admin', 'kasir'])) {
        $errors['role'] = 'Role tidak valid.';
    }

    /* Validasi no. telepon */
    if ($input['no_telepon'] !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $input['no_telepon'])) {
        $errors['no_telepon'] = 'Format nomor telepon tidak valid.';
    }

    /* Simpan jika valid */
    if (empty($errors)) {
        $nama       = mysqli_real_escape_string($conn, $input['nama_karyawan']);
        $username   = mysqli_real_escape_string($conn, $input['username']);
        $role       = mysqli_real_escape_string($conn, $input['role']);
        $hp         = mysqli_real_escape_string($conn, $input['no_telepon']);
        $alamat     = mysqli_real_escape_string($conn, $input['alamat']);
        $hash       = password_hash($password, PASSWORD_BCRYPT);
        $hash_esc   = mysqli_real_escape_string($conn, $hash);

        mysqli_query($conn, "
            INSERT INTO karyawan (nama_karyawan, username, password, role, no_telepon, alamat)
            VALUES ('$nama', '$username', '$hash_esc', '$role', '$hp', '$alamat')
        ");

        $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'Karyawan <strong>' . e($input['nama_karyawan']) . '</strong> berhasil ditambahkan.'];
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
    <title>Tambah Karyawan — Clean Express Laundry</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>

<div class="main-wrapper">

    <header class="topbar">
        <div>
            <div class="topbar-title">Tambah Karyawan</div>
            <div class="topbar-sub">
                <a href="index.php" style="color:var(--gray-500);text-decoration:none;">Karyawan</a>
                &nbsp;/&nbsp; Tambah Baru
            </div>
        </div>
    </header>

    <main class="page-content">

        <div class="card" style="max-width:600px;">
            <div class="card-header">
                <span class="card-title">Data Karyawan Baru</span>
            </div>
            <div class="card-body">

                <form method="POST" novalidate>

                    <!-- Nama Karyawan -->
                    <div class="form-group">
                        <label class="form-label" for="nama_karyawan">
                            Nama Karyawan <span style="color:#A32D2D;">*</span>
                        </label>
                        <input type="text"
                               id="nama_karyawan"
                               name="nama_karyawan"
                               class="form-control <?= isset($errors['nama_karyawan']) ? 'is-invalid' : '' ?>"
                               value="<?= e($input['nama_karyawan']) ?>"
                               placeholder="Contoh: Budi Santoso"
                               maxlength="100"
                               autofocus>
                        <?php if (isset($errors['nama_karyawan'])): ?>
                            <div class="form-error"><?= $errors['nama_karyawan'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Username & Role -->
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="username">
                                Username <span style="color:#A32D2D;">*</span>
                            </label>
                            <input type="text"
                                   id="username"
                                   name="username"
                                   class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($input['username']) ?>"
                                   placeholder="Contoh: budi123"
                                   maxlength="50">
                            <?php if (isset($errors['username'])): ?>
                                <div class="form-error"><?= $errors['username'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="role">
                                Role <span style="color:#A32D2D;">*</span>
                            </label>
                            <select id="role" name="role"
                                    class="form-control <?= isset($errors['role']) ? 'is-invalid' : '' ?>">
                                <option value="kasir" <?= $input['role'] === 'kasir' ? 'selected' : '' ?>>Kasir</option>
                                <option value="admin" <?= $input['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <?php if (isset($errors['role'])): ?>
                                <div class="form-error"><?= $errors['role'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="password">
                                Password <span style="color:#A32D2D;">*</span>
                            </label>
                            <div style="position:relative;">
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       placeholder="Min. 6 karakter"
                                       maxlength="100">
                                <button type="button" onclick="togglePwd('password', this)"
                                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-500);">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="form-error"><?= $errors['password'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password_konfirm">
                                Konfirmasi Password <span style="color:#A32D2D;">*</span>
                            </label>
                            <div style="position:relative;">
                                <input type="password"
                                       id="password_konfirm"
                                       name="password_konfirm"
                                       class="form-control <?= isset($errors['password_konfirm']) ? 'is-invalid' : '' ?>"
                                       placeholder="Ulangi password"
                                       maxlength="100">
                                <button type="button" onclick="togglePwd('password_konfirm', this)"
                                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-500);">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password_konfirm'])): ?>
                                <div class="form-error"><?= $errors['password_konfirm'] ?></div>
                            <?php endif; ?>
                        </div>
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
                                  maxlength="100"><?= e($input['alamat']) ?></textarea>
                    </div>

                    <div class="flex gap-8" style="margin-top:24px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i> Simpan Karyawan
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
textarea.form-control { resize: vertical; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
</style>

<script>
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ti ti-eye-off';
    } else {
        input.type = 'password';
        icon.className = 'ti ti-eye';
    }
}
</script>
</body>
</html>