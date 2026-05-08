<?php
/* karyawan/edit.php — Form Edit Karyawan */
session_start();
require_once '../koneksi.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
only_admin();

$active_page = 'karyawan';
$base_path   = '../';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$q_kar = mysqli_query($conn, "SELECT * FROM karyawan WHERE id_karyawan = $id LIMIT 1");
if (mysqli_num_rows($q_kar) === 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Karyawan tidak ditemukan.'];
    header('Location: index.php');
    exit;
}
$karyawan = mysqli_fetch_assoc($q_kar);

$errors = [];
$input  = [
    'nama_karyawan' => $karyawan['nama_karyawan'],
    'username'      => $karyawan['username'],
    'role'          => $karyawan['role'],
    'no_telepon'    => $karyawan['no_telepon'],
    'alamat'        => $karyawan['alamat'],
];

$isSelf = ((int)$id === (int)$_SESSION['user_id']);

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
        $cek = mysqli_query($conn, "SELECT id_karyawan FROM karyawan WHERE username = '$u' AND id_karyawan != $id LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $errors['username'] = 'Username sudah digunakan karyawan lain.';
        }
    }

    /* Validasi password (opsional saat edit) */
    if ($password !== '') {
        if (mb_strlen($password) < 6) {
            $errors['password'] = 'Password minimal 6 karakter.';
        } elseif ($password !== $password_konfirm) {
            $errors['password_konfirm'] = 'Konfirmasi password tidak cocok.';
        }
    }

    /* Validasi role: jangan downgrade diri sendiri */
    if (!in_array($input['role'], ['admin', 'kasir'])) {
        $errors['role'] = 'Role tidak valid.';
    }
    if ($isSelf && $input['role'] !== 'admin') {
        $errors['role'] = 'Anda tidak dapat mengubah role akun Anda sendiri.';
    }

    /* Validasi no. telepon */
    if ($input['no_telepon'] !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $input['no_telepon'])) {
        $errors['no_telepon'] = 'Format nomor telepon tidak valid.';
    }

    if (empty($errors)) {
        $nama     = mysqli_real_escape_string($conn, $input['nama_karyawan']);
        $username = mysqli_real_escape_string($conn, $input['username']);
        $role     = mysqli_real_escape_string($conn, $input['role']);
        $hp       = mysqli_real_escape_string($conn, $input['no_telepon']);
        $alamat   = mysqli_real_escape_string($conn, $input['alamat']);

        if ($password !== '') {
            $hash     = password_hash($password, PASSWORD_BCRYPT);
            $hash_esc = mysqli_real_escape_string($conn, $hash);
            $pwd_sql  = ", password = '$hash_esc'";
        } else {
            $pwd_sql = '';
        }

        mysqli_query($conn, "
            UPDATE karyawan
            SET nama_karyawan = '$nama',
                username      = '$username',
                role          = '$role',
                no_telepon    = '$hp',
                alamat        = '$alamat'
                $pwd_sql
            WHERE id_karyawan = $id
        ");

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data karyawan <strong>' . e($input['nama_karyawan']) . '</strong> berhasil diperbarui.'];
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
    <title>Edit Karyawan — Clean Express Laundry</title>
    <?php include '../includes/sidebar.php'; ?>
</head>
<body>

<div class="main-wrapper">

    <header class="topbar">
        <div>
            <div class="topbar-title">Edit Karyawan</div>
            <div class="topbar-sub">
                <a href="index.php" style="color:var(--gray-500);text-decoration:none;">Karyawan</a>
                &nbsp;/&nbsp; <?= e($karyawan['nama_karyawan']) ?>
            </div>
        </div>
    </header>

    <main class="page-content">

        <div class="card" style="max-width:600px;">
            <div class="card-header">
                <span class="card-title">
                    <div class="flex gap-8" style="align-items:center;">
                        <div class="avatar"><?= inisial($karyawan['nama_karyawan']) ?></div>
                        Edit Data Karyawan
                    </div>
                </span>
                <span class="text-muted text-sm">ID #<?= $id ?></span>
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
                                   maxlength="50">
                            <?php if (isset($errors['username'])): ?>
                                <div class="form-error"><?= $errors['username'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="role">Role</label>
                            <select id="role" name="role"
                                    class="form-control <?= isset($errors['role']) ? 'is-invalid' : '' ?>"
                                    <?= $isSelf ? 'disabled' : '' ?>>
                                <option value="kasir" <?= $input['role'] === 'kasir' ? 'selected' : '' ?>>Kasir</option>
                                <option value="admin" <?= $input['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <?php if ($isSelf): ?>
                                <input type="hidden" name="role" value="admin">
                                <div class="form-hint">Role tidak dapat diubah untuk akun sendiri.</div>
                            <?php endif; ?>
                            <?php if (isset($errors['role'])): ?>
                                <div class="form-error"><?= $errors['role'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Reset Password (opsional) -->
                    <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:16px;margin-bottom:18px;">
                        <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:12px;">
                            <i class="ti ti-lock" style="margin-right:4px;"></i>
                            Reset Password <span style="font-weight:400;color:var(--gray-400);">(kosongkan jika tidak ingin mengubah)</span>
                        </div>
                        <div class="grid-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" for="password">Password Baru</label>
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

                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" for="password_konfirm">Konfirmasi Password</label>
                                <div style="position:relative;">
                                    <input type="password"
                                           id="password_konfirm"
                                           name="password_konfirm"
                                           class="form-control <?= isset($errors['password_konfirm']) ? 'is-invalid' : '' ?>"
                                           placeholder="Ulangi password baru"
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
.form-hint  { font-size: 11px; color: var(--gray-400); margin-top: 4px; }
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