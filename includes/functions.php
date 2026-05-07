<?php
/* includes/functions.php — fungsi-fungsi pembantu */

/**
 * Format angka ke rupiah: 12000 → "Rp 12.000"
 */
function rupiah(int $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Inisial nama untuk avatar: "Budi Santoso" → "BS"
 */
function inisial(string $nama): string {
    $kata = explode(' ', trim($nama));
    if (count($kata) >= 2) {
        return strtoupper(substr($kata[0], 0, 1) . substr($kata[1], 0, 1));
    }
    return strtoupper(substr($kata[0], 0, 2));
}

/**
 * Badge HTML untuk status cucian
 */
function badge_status_cucian(string $status): string {
    $map = [
        'Selesai' => 'badge-selesai',
        'Proses'  => 'badge-proses',
        'Baru'    => 'badge-baru',
    ];
    $cls = $map[$status] ?? 'badge-info';
    return "<span class=\"badge $cls\">$status</span>";
}

/**
 * Badge HTML untuk status pembayaran
 */
function badge_status_bayar(string $status): string {
    $cls = ($status === 'Lunas') ? 'badge-lunas' : 'badge-belum';
    return "<span class=\"badge $cls\">$status</span>";
}

/**
 * Format tanggal ke Indonesia: 2026-03-01 → "1 Mar 2026"
 */
function tgl_indo(string $tgl): string {
    if (!$tgl || $tgl === '0000-00-00') return '-';
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    [$y, $m, $d] = explode('-', $tgl);
    return (int)$d . ' ' . $bulan[(int)$m] . ' ' . $y;
}

/**
 * Redirect dengan pesan flash session
 */
function redirect(string $url, string $tipe = '', string $pesan = ''): void {
    if ($tipe && $pesan) {
        $_SESSION['flash'] = ['type' => $tipe, 'msg' => $pesan];
    }
    header("Location: $url");
    exit;
}

/**
 * Tampilkan dan hapus pesan flash
 */
function flash(): string {
    if (isset($_SESSION['flash'])) {
        $f   = $_SESSION['flash'];
        $cls = $f['type'] === 'success' ? 'alert-success' : 'alert-danger';
        $ico = $f['type'] === 'success' ? 'ti-circle-check' : 'ti-alert-circle';
        unset($_SESSION['flash']);
        return "<div class=\"alert $cls\"><i class=\"ti $ico\"></i> {$f['msg']}</div>";
    }
    return '';
}

/**
 * Escape output untuk mencegah XSS
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
