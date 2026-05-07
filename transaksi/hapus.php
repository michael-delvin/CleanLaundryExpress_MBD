<?php
/* transaksi/hapus.php */
session_start();
require_once '../koneksi.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

/* Hanya admin yang boleh menghapus transaksi */
if (!is_admin()) {
    redirect('index.php', 'danger', 'Akses ditolak. Hanya admin yang dapat menghapus transaksi.');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('index.php', 'danger', 'ID tidak valid.');

/* Hapus detail dulu (FK), lalu header */
mysqli_query($conn, "DELETE FROM detail_transaksi WHERE id_transaksi = $id");
mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = $id");

redirect('index.php', 'success', "Transaksi #$id berhasil dihapus.");