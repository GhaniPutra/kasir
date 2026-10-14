<?php
session_start(); // Memulai sesi agar status login dan data pengguna dapat dibaca.

if (!isset($_SESSION['login'])) { // Pastikan hanya pengguna yang sudah login dapat membuka dashboard.
    header("Location: login.php?msg=belum_login"); // Arahkan pengguna yang belum login ke halaman login.
    exit(); // Hentikan proses agar isi dashboard tidak ikut dikirim.
}

include __DIR__ . '/conn/koneksi.php'; // Membuka koneksi ke database menggunakan file konfigurasi project.

// Ambil empat angka ringkasan dashboard dalam satu query.
$summary_result = $conn->query("
    SELECT
        -- Jumlah nilai transaksi yang sudah lunas pada tanggal hari ini.
        (SELECT COALESCE(SUM(total_tagihan), 0)
         FROM pembelian
         WHERE tanggal_pembelian = CURDATE() AND status_bayar = 'Lunas') AS omzet_hari_ini,
        -- Jumlah semua pesanan yang dicatat hari ini, termasuk yang belum lunas.
        (SELECT COUNT(*)
         FROM pembelian
         WHERE tanggal_pembelian = CURDATE()) AS transaksi_hari_ini,
        -- Jumlah pesanan yang status pembayarannya masih belum lunas.
        (SELECT COUNT(*)
         FROM pembelian
         WHERE status_bayar = 'Belum') AS belum_lunas,
        -- Jumlah produk yang tersimpan pada tabel menu.
        (SELECT COUNT(*) FROM menu) AS jumlah_menu
");
// Ubah satu baris hasil query menjadi array asosiatif agar mudah dipakai di tampilan.
$summary = $summary_result->fetch_assoc();

// Ambil paling banyak lima transaksi, dimulai dari transaksi dengan ID terbaru.
$recent_transactions = $conn->query("
    SELECT id, nama_pembeli, tanggal_pembelian, total_tagihan, status_bayar
    FROM pembelian
    ORDER BY id DESC
    LIMIT 5
");

// Amankan nama pengguna untuk ditampilkan sebagai teks HTML; gunakan "Kasir" jika sesi kosong.
$username = htmlspecialchars($_SESSION['username'] ?? 'Kasir');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Tetapkan encoding agar teks Indonesia tampil dengan benar. -->
    <meta charset="UTF-8">
    <!-- Sesuaikan skala halaman dengan lebar perangkat, terutama ponsel. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Judul yang tampil pada tab browser. -->
    <title>Dashboard - Kasirku</title>
    <!-- Muat Tailwind CSS dari CDN untuk mengatur gaya halaman. -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<!-- Atur warna, jenis huruf, dan tinggi minimum halaman. -->
<body class="min-h-screen bg-[#f8f6f3] font-sans text-gray-800">
    <!-- Bungkus navigasi samping dan konten utama dalam tata letak fleksibel. -->
    <div class="flex min-h-screen">
        <!-- Tampilkan navigasi bersama dari file sidenav.php. -->
        <?php include __DIR__ . '/sidenav.php'; ?>

        <!-- Area utama dashboard; min-w-0 mencegah tabel melebar keluar layar. -->
        <main class="min-w-0 flex-1 p-5 sm:p-8">
            <div class="mb-8 rounded-xl bg-[#4a3321] px-6 py-3 text-white shadow-md">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-bold tracking-wider">BERANDA</span>
                    </div>
                    <div>
                        <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="rounded-full bg-amber-100 px-5 py-1.5 text-xs font-bold text-[#4a3321] shadow transition hover:bg-white">
                            logout
                        </a>
                    </div>
                </div>
            </div>

            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold font-serif text-[#4a3321]">mira bakery</h1>
                <p class="mt-1 text-xs italic text-[#a07855]">Balanced sweetness, undeniable softness</p>
            </div>

            <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-[#9a7658]">Mira Bakery · Beranda</p>
                    <h2 class="mt-1 text-3xl font-bold text-[#4a3321]">Beranda</h2>
                    <p class="mt-1 text-sm text-gray-500">Ringkasan aktivitas toko hari ini.</p>
                </div>
                <a href="input_pesanan.php" class="inline-flex items-center justify-center rounded-lg bg-[#8b5a2b] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#6c4420]">
                    + Buat pesanan
                </a>
            </header>

            <!-- Tampilkan empat kartu ringkasan dalam grid yang menyesuaikan ukuran layar. -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan toko">
                <!-- Kartu omzet berisi total tagihan lunas hari ini. -->
                <article class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Omzet hari ini</p>
                    <!-- Format angka sebagai rupiah dengan pemisah ribuan gaya Indonesia. -->
                    <p class="mt-3 text-2xl font-bold text-[#4a3321]">Rp <?= number_format((int) $summary['omzet_hari_ini'], 0, ',', '.'); ?></p>
                    <p class="mt-2 text-xs text-gray-400">Dari transaksi yang sudah lunas</p>
                </article>
                <!-- Kartu jumlah pesanan yang masuk pada hari ini. -->
                <article class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Transaksi hari ini</p>
                    <p class="mt-3 text-2xl font-bold text-[#4a3321]"><?= number_format((int) $summary['transaksi_hari_ini'], 0, ',', '.'); ?></p>
                    <p class="mt-2 text-xs text-gray-400">Pesanan tercatat hari ini</p>
                </article>
                <!-- Kartu jumlah pesanan yang pembayaran-nya belum lunas. -->
                <article class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Belum lunas</p>
                    <p class="mt-3 text-2xl font-bold text-[#4a3321]"><?= number_format((int) $summary['belum_lunas'], 0, ',', '.'); ?></p>
                    <p class="mt-2 text-xs text-gray-400">Perlu ditindaklanjuti</p>
                </article>
                <!-- Kartu jumlah produk yang terdaftar di dalam katalog menu. -->
                <article class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Menu tersedia</p>
                    <p class="mt-3 text-2xl font-bold text-[#4a3321]"><?= number_format((int) $summary['jumlah_menu'], 0, ',', '.'); ?></p>
                    <p class="mt-2 text-xs text-gray-400">Produk dalam katalog</p>
                </article>
            </section>

            <!-- Susun tabel transaksi dan tautan akses cepat secara berdampingan pada layar lebar. -->
            <section class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
                <!-- Panel tabel memakai dua dari tiga kolom pada layar ekstra lebar. -->
                <div class="overflow-hidden rounded-xl border border-amber-100 bg-white shadow-sm xl:col-span-2">
                    <!-- Judul tabel dan nama pengguna yang sedang masuk. -->
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="font-bold text-[#4a3321]">Transaksi terbaru</h2>
                            <p class="mt-1 text-xs text-gray-500">Lima pesanan terakhir yang tercatat</p>
                        </div>
                        <span class="text-sm font-medium text-[#9a7658]"><?= $username; ?></span>
                    </div>
                    <!-- Bungkus tabel agar tetap dapat digulir pada layar kecil. -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <!-- Kepala tabel menjelaskan informasi di setiap kolom. -->
                            <thead class="bg-[#fcf8f3] text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-5 py-3 font-semibold sm:px-6">Pembeli</th>
                                    <th class="px-5 py-3 font-semibold">Tanggal</th>
                                    <th class="px-5 py-3 font-semibold">Total</th>
                                    <th class="px-5 py-3 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <!-- Isi tabel dibuat dari hasil query lima transaksi terbaru. -->
                            <tbody class="divide-y divide-gray-100">
                                <!-- Tampilkan baris transaksi hanya jika query menemukan data. -->
                                <?php if ($recent_transactions->num_rows > 0): ?>
                                    <!-- Ambil satu transaksi pada setiap putaran perulangan. -->
                                    <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                                        <tr>
                                            <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-800 sm:px-6">
                                                <!-- Escape nama pembeli untuk mencegah teks dianggap sebagai HTML. -->
                                                <?= htmlspecialchars($transaction['nama_pembeli']); ?>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 text-gray-500">
                                                <!-- Ubah tanggal database menjadi format yang mudah dibaca. -->
                                                <?= date('d M Y', strtotime($transaction['tanggal_pembelian'])); ?>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-700">
                                                <!-- Tampilkan total tagihan dengan format angka rupiah. -->
                                                Rp <?= number_format((int) $transaction['total_tagihan'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4">
                                                <!-- Beri warna hijau untuk lunas dan warna kuning untuk belum lunas. -->
                                                <?php if ($transaction['status_bayar'] === 'Lunas'): ?>
                                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Lunas</span>
                                                <?php else: ?>
                                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Belum lunas</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <!-- Lanjutkan sampai semua baris hasil query ditampilkan. -->
                                    <?php endwhile; ?>
                                <!-- Tampilkan pesan pengganti jika tabel transaksi masih kosong. -->
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-400">Belum ada transaksi.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Panel navigasi menuju fungsi yang sering dipakai kasir. -->
                <aside class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-[#4a3321]">Akses cepat</h2>
                    <p class="mt-1 text-xs text-gray-500">Kelola aktivitas Mira Bakery</p>
                    <div class="mt-5 space-y-3">
                        <!-- Tautan untuk membuka form input pesanan. -->
                        <a href="input_pesanan.php" class="flex items-center justify-between rounded-lg bg-[#fcf8f3] px-4 py-3 text-sm font-medium text-[#6c4420] transition hover:bg-amber-100">
                            Input pesanan <span aria-hidden="true">→</span>
                        </a>
                        <!-- Tautan untuk melihat atau mengelola daftar menu. -->
                        <a href="menu.php" class="flex items-center justify-between rounded-lg bg-[#fcf8f3] px-4 py-3 text-sm font-medium text-[#6c4420] transition hover:bg-amber-100">
                            Kelola menu <span aria-hidden="true">→</span>
                        </a>
                        <!-- Tautan untuk membuka halaman pembayaran. -->
                        <a href="pembayaran.php" class="flex items-center justify-between rounded-lg bg-[#fcf8f3] px-4 py-3 text-sm font-medium text-[#6c4420] transition hover:bg-amber-100">
                            Pembayaran <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </aside>
            </section>
        </main>
    </div>
</body>
</html>
