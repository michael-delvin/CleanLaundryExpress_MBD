<?php
/* includes/sidebar.php
   Sidebar navigasi — include di setiap halaman.
   Variabel $active_page harus di-set sebelum include,
   misalnya: $active_page = 'dashboard';
*/
if (!isset($active_page)) $active_page = '';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="<?= $base_path ?? '' ?>assets/css/style.css">

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="ti ti-wash"></i></div>
        <div>
            <div class="logo-text">Clean Express</div>
            <div class="logo-sub">Laundry POS</div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="sidebar-section-label">Menu Utama</div>

        <a href="<?= $base_path ?? '' ?>dashboard.php"
           class="nav-link <?= $active_page === 'dashboard' ? 'active' : '' ?>">
            <i class="ti ti-layout-dashboard"></i> Dashboard
        </a>

        <a href="<?= $base_path ?? '' ?>transaksi/tambah.php"
           class="nav-link <?= $active_page === 'transaksi_tambah' ? 'active' : '' ?>">
            <i class="ti ti-plus"></i> Transaksi Baru
        </a>

        <a href="<?= $base_path ?? '' ?>transaksi/index.php"
           class="nav-link <?= $active_page === 'transaksi' ? 'active' : '' ?>">
            <i class="ti ti-receipt"></i> Daftar Transaksi
        </a>

        <div class="sidebar-section-label" style="margin-top:8px;">Master Data</div>

        <a href="<?= $base_path ?? '' ?>pelanggan/index.php"
           class="nav-link <?= $active_page === 'pelanggan' ? 'active' : '' ?>">
            <i class="ti ti-users"></i> Pelanggan
        </a>

        <?php if (is_admin()): ?>
        <a href="<?= $base_path ?? '' ?>layanan/index.php"
           class="nav-link <?= $active_page === 'layanan' ? 'active' : '' ?>">
            <i class="ti ti-ironing"></i> Layanan
        </a>

        <a href="<?= $base_path ?? '' ?>karyawan/index.php"
           class="nav-link <?= $active_page === 'karyawan' ? 'active' : '' ?>">
            <i class="ti ti-id-badge"></i> Karyawan
        </a>
        <?php endif; ?>

        <?php if (is_admin()): ?>
        <div class="sidebar-section-label" style="margin-top:8px;">Laporan</div>

        <a href="<?= $base_path ?? '' ?>laporan/index.php"
           class="nav-link <?= $active_page === 'laporan' ? 'active' : '' ?>">
            <i class="ti ti-report-money"></i> Laporan Keuangan
        </a>
        <?php endif; ?>
    </nav>

</aside>