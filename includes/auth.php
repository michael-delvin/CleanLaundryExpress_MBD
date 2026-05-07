<?php
/* includes/auth.php
 * Sertakan file ini di SETIAP halaman yang butuh login.
 * Penggunaan:
 *   require_once 'includes/auth.php';          // hanya cek login
 *   require_once 'includes/auth.php'; only_admin(); // wajib admin
 */
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
/* ── Cek apakah sudah login ── */
function cek_login(): void {
    if (empty($_SESSION['user_id'])) {
        /* Simpan URL tujuan agar bisa redirect setelah login */
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . base_url() . 'login.php');
        exit;
    }
}
 
/* ── Wajib role admin, kasir akan ditolak ── */
function only_admin(): void {
    cek_login();
    if ($_SESSION['user_role'] !== 'admin') {
        /* Tampilkan halaman 403 sederhana */
        http_response_code(403);
        include dirname(__DIR__) . '/includes/403.php';
        exit;
    }
}
 
/* ── Helper: role saat ini ── */
function is_admin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}
 
function is_kasir(): bool {
    return ($_SESSION['user_role'] ?? '') === 'kasir';
}
 
/* ── Helper: nama karyawan login ── */
function nama_user(): string {
    return $_SESSION['user_nama'] ?? 'Pengguna';
}
 
/* ── Helper: base URL (deteksi subfolder) ── */
function base_url(): string {
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    /* Naik ke root project (folder laundry) */
    $parts  = explode('/', trim($script, '/'));
    /* Ambil segmen pertama sebagai base (misal: /laundry/) */
    $base   = '/' . ($parts[0] ?? '') . '/';
    return $base;
}
 
/* Langsung cek login saat file ini di-include */
cek_login();