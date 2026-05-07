<?php
/* includes/403.php — Tampil saat kasir akses halaman admin-only */
$base_path = $base_path ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak — Clean Express Laundry</title>
    <?php include dirname(__FILE__) . '/sidebar.php'; ?>
</head>
<body>
<div class="main-wrapper">
    <main class="page-content" style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
        <div style="text-align:center;">
            <div style="font-size:64px;margin-bottom:16px;">🔒</div>
            <div style="font-size:22px;font-weight:700;color:var(--gray-800);margin-bottom:8px;">Akses Ditolak</div>
            <div style="color:var(--gray-500);margin-bottom:24px;">
                Halaman ini hanya dapat diakses oleh <strong>Admin</strong>.
            </div>
            <a href="<?= $base_path ?>dashboard.php" class="btn btn-primary">
                <i class="ti ti-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </main>
</div>
</body>
</html>