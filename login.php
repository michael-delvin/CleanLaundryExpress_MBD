<?php
/* login.php */
session_start();
 
/* Sudah login? Langsung redirect */
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
 
require_once 'koneksi.php';
 
$error = '';
$username_input = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = trim($_POST['username'] ?? '');
    $password_input = $_POST['password'] ?? '';
 
    if ($username_input === '' || $password_input === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $u   = mysqli_real_escape_string($conn, $username_input);
        $row = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id_karyawan, nama_karyawan, username, password, role
             FROM karyawan
             WHERE username = '$u'
             LIMIT 1"
        ));
 
        if ($row && password_verify($password_input, $row['password'])) {
            /* Login berhasil */
            session_regenerate_id(true);
            $_SESSION['user_id']   = $row['id_karyawan'];
            $_SESSION['user_nama'] = $row['nama_karyawan'];
            $_SESSION['user_role'] = $row['role'];
 
            $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Username atau password salah.';
            /* Delay kecil untuk cegah brute-force */
            sleep(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Clean Express Laundry</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
 
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #F4F6F8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
 
        .login-wrap {
            width: 100%;
            max-width: 400px;
        }
 
        /* Logo / Brand */
        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-brand-icon {
            width: 56px;
            height: 56px;
            background: #1D9E75;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .login-brand-icon i { font-size: 28px; color: #fff; }
        .login-brand-name {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.2;
        }
        .login-brand-sub {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
 
        /* Card */
        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        .login-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }
        .login-sub {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 24px;
        }
 
        /* Alert error */
        .alert-error {
            background: #FCEBEB;
            color: #A32D2D;
            border: 1px solid #f5c6c6;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
 
        /* Form */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
            pointer-events: none;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px 10px 38px;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            color: #111827;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            font-family: inherit;
        }
        .form-control:focus {
            border-color: #1D9E75;
            box-shadow: 0 0 0 3px rgba(29,158,117,.12);
        }
 
        /* Toggle password */
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            font-size: 16px;
            padding: 0;
            line-height: 1;
        }
        .toggle-pw:hover { color: #374151; }
 
        /* Submit button */
        .btn-login {
            width: 100%;
            padding: 11px;
            background: #1D9E75;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background .15s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }
        .btn-login:hover  { background: #178a64; }
        .btn-login:active { transform: scale(.98); }
 
        /* Role badges */
        .role-info {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .role-badge {
            flex: 1;
            background: #F4F6F8;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12px;
            color: #6b7280;
        }
        .role-badge strong {
            display: block;
            color: #374151;
            margin-bottom: 2px;
            font-size: 13px;
        }
    </style>
</head>
<body>
 
<div class="login-wrap">
 
    <!-- Brand -->
    <div class="login-brand">
        <div class="login-brand-icon">
            <i class="ti ti-ripple"></i>
        </div>
        <div class="login-brand-name">Clean Express Laundry</div>
        <div class="login-brand-sub">Sistem Point of Sale</div>
    </div>
 
    <!-- Card -->
    <div class="login-card">
        <div class="login-title">Selamat datang 👋</div>
        <div class="login-sub">Masuk untuk melanjutkan ke sistem</div>
 
        <?php if ($error): ?>
            <div class="alert-error">
                <i class="ti ti-alert-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
 
        <form method="POST" novalidate>
 
            <!-- Username -->
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-wrap">
                    <i class="ti ti-user"></i>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-control"
                           value="<?= htmlspecialchars($username_input) ?>"
                           placeholder="Masukkan username"
                           autocomplete="username"
                           autofocus>
                </div>
            </div>
 
            <!-- Password -->
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <i class="ti ti-lock"></i>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           placeholder="Masukkan password"
                           autocomplete="current-password"
                           style="padding-right:40px;">
                    <button type="button" class="toggle-pw" onclick="togglePassword()" title="Tampilkan/sembunyikan">
                        <i class="ti ti-eye" id="ico-eye"></i>
                    </button>
                </div>
            </div>
 
            <button type="submit" class="btn-login">
                <i class="ti ti-login"></i> Masuk
            </button>
 
        </form>
 
        <!-- Info role -->
        <div class="role-info">
            <div class="role-badge">
                <strong>🛡️ Admin</strong>
                Akses penuh ke semua fitur
            </div>
            <div class="role-badge">
                <strong>🧾 Kasir</strong>
                Transaksi &amp; tambah pelanggan
            </div>
        </div>
    </div>
 
</div>
 
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('ico-eye');
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