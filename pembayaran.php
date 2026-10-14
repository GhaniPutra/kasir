<?php
// Mulai sesi agar halaman dapat memeriksa status login pengguna.
session_start();

// Periksa apakah pengguna sudah berhasil login.
if (!isset($_SESSION['login'])) {
    // Arahkan pengguna yang belum login ke halaman login.
    header("Location: login.php?msg=belum_login");
    // Hentikan eksekusi agar data pembayaran tidak ditampilkan.
    exit();
}

// Muat koneksi database yang digunakan seluruh halaman.
include __DIR__ . '/conn/koneksi.php';

// Hitung ringkasan seluruh transaksi, jumlah lunas, belum lunas, dan total terbayar.
$ringkasan = $conn->query("
    -- Hitung jumlah semua baris transaksi.
    SELECT
        COUNT(*) AS jumlah_transaksi,
        -- Hitung transaksi dengan status pembayaran lunas.
        SUM(status_bayar = 'Lunas') AS jumlah_lunas,
        -- Hitung transaksi yang belum lunas.
        SUM(status_bayar = 'Belum') AS jumlah_belum_lunas,
        -- Jumlahkan tagihan yang statusnya sudah lunas; hasil 0 jika belum ada pembayaran.
        COALESCE(SUM(CASE WHEN status_bayar = 'Lunas' THEN total_tagihan ELSE 0 END), 0) AS total_terbayar
    -- Ambil ringkasan dari tabel transaksi pembelian.
    FROM pembelian
-- Ambil hasil ringkasan sebagai array dengan nama kolom sebagai kunci.
")->fetch_assoc();

// Ambil kolom yang dibutuhkan untuk menampilkan riwayat pembayaran.
$transaksi = $conn->query("
    -- Pilih data transaksi, nilai pembayaran, dan informasi kembalian.
    SELECT id, nama_pembeli, no_meja, tanggal_pembelian, total_tagihan,
           status_bayar, uang_bayar, kembalian
    -- Baca data dari tabel pembelian.
    FROM pembelian
    -- Urutkan tanggal terbaru terlebih dahulu, lalu urutkan ID terbaru.
    ORDER BY tanggal_pembelian DESC, id DESC
");
?>

<!-- Mulai dokumen HTML dengan bahasa utama Indonesia. -->
<!DOCTYPE html>
<html lang="id">
<!-- Bagian kepala berisi metadata dan pengaturan tampilan halaman. -->
<head>
    <!-- Gunakan UTF-8 untuk mendukung karakter dan teks Indonesia. -->
    <meta charset="UTF-8">
    <!-- Atur lebar halaman agar sesuai dengan ukuran layar perangkat. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tetapkan judul yang muncul pada tab browser. -->
    <title>Riwayat Pembayaran - Kasirku</title>
    <!-- Muat Tailwind CSS dari CDN untuk kelas-kelas tampilan halaman. -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<!-- Tutup bagian kepala dokumen. -->
</head>
<!-- Atur warna latar, tinggi halaman, jenis huruf, dan warna teks dasar. -->
<body class="min-h-screen bg-[#f8f6f3] font-sans text-gray-800">
    <!-- Gunakan flexbox untuk menempatkan navigasi dan konten berdampingan. -->
    <div class="flex min-h-screen">
        <!-- Tampilkan menu navigasi yang digunakan bersama halaman lain. -->
        <?php include __DIR__ . '/sidenav.php'; ?>

        <!-- Area utama halaman riwayat pembayaran. -->
        <main class="min-w-0 flex-1 p-5 sm:p-8">
            <div class="mb-8 rounded-xl bg-[#4a3321] px-6 py-3 text-white shadow-md">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-bold tracking-wider">PEMBAYARAN</span>
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

            <header class="mb-8">
                <p class="text-sm font-medium text-[#9a7658]">Mira Bakery · Pembayaran</p>
                <h2 class="mt-1 text-3xl font-bold text-[#4a3321]">Riwayat pembayaran</h2>
                <p class="mt-1 text-sm text-gray-500">Daftar transaksi dan status pembayarannya.</p>
            </header>

            <!-- Buat tiga kartu ringkasan yang menyesuaikan lebar layar. -->
            <section class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3" aria-label="Ringkasan pembayaran">
                <!-- Kartu untuk menunjukkan banyaknya semua transaksi. -->
                <article class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                    <!-- Nama informasi pada kartu. -->
                    <p class="text-sm text-gray-500">Total transaksi</p>
                    <!-- Tampilkan jumlah transaksi dengan pemisah ribuan Indonesia. -->
                    <p class="mt-2 text-2xl font-bold text-[#4a3321]"><?= number_format((int) $ringkasan['jumlah_transaksi'], 0, ',', '.'); ?></p>
                <!-- Tutup kartu jumlah transaksi. -->
                </article>
                <!-- Kartu untuk menunjukkan transaksi lunas dan jumlah uangnya. -->
                <article class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                    <!-- Nama informasi pada kartu pembayaran lunas. -->
                    <p class="text-sm text-gray-500">Transaksi lunas</p>
                    <!-- Tampilkan jumlah transaksi yang sudah lunas. -->
                    <p class="mt-2 text-2xl font-bold text-green-700"><?= number_format((int) $ringkasan['jumlah_lunas'], 0, ',', '.'); ?></p>
                    <!-- Tampilkan akumulasi tagihan dari transaksi lunas dalam format rupiah. -->
                    <p class="mt-1 text-xs text-gray-400">Total terbayar Rp <?= number_format((int) $ringkasan['total_terbayar'], 0, ',', '.'); ?></p>
                <!-- Tutup kartu transaksi lunas. -->
                </article>
                <!-- Kartu untuk menunjukkan transaksi yang belum lunas. -->
                <article class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                    <!-- Nama informasi pada kartu pembayaran tertunda. -->
                    <p class="text-sm text-gray-500">Belum lunas</p>
                    <!-- Tampilkan jumlah transaksi yang belum lunas. -->
                    <p class="mt-2 text-2xl font-bold text-amber-700"><?= number_format((int) $ringkasan['jumlah_belum_lunas'], 0, ',', '.'); ?></p>
                <!-- Tutup kartu transaksi belum lunas. -->
                </article>
            <!-- Tutup bagian kartu ringkasan. -->
            </section>

            <!-- Panel utama yang berisi tabel seluruh transaksi. -->
            <section class="overflow-hidden rounded-xl border border-amber-100 bg-white shadow-sm">
                <!-- Judul panel riwayat. -->
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                    <!-- Nama tabel riwayat transaksi. -->
                    <h2 class="font-bold text-[#4a3321]">Semua transaksi</h2>
                <!-- Tutup judul panel. -->
                </div>
                <!-- Izinkan tabel digulir secara horizontal pada layar kecil. -->
                <div class="overflow-x-auto">
                    <!-- Buat tabel dengan teks rata kiri dan ukuran tulisan kecil. -->
                    <table class="w-full text-left text-sm">
                        <!-- Bagian kepala tabel berisi nama tiap kolom. -->
                        <thead class="bg-[#fcf8f3] text-xs uppercase tracking-wide text-gray-500">
                            <!-- Mulai baris judul kolom. -->
                            <tr>
                                <!-- Kolom urutan transaksi dalam tampilan. -->
                                <th class="px-5 py-3 font-semibold sm:px-6">No.</th>
                                <!-- Kolom nama pembeli. -->
                                <th class="px-5 py-3 font-semibold">Pembeli</th>
                                <!-- Kolom nomor meja pembeli. -->
                                <th class="px-5 py-3 font-semibold">Meja</th>
                                <!-- Kolom tanggal pembelian. -->
                                <th class="px-5 py-3 font-semibold">Tanggal</th>
                                <!-- Kolom jumlah tagihan transaksi. -->
                                <th class="px-5 py-3 font-semibold">Tagihan</th>
                                <!-- Kolom jumlah uang yang dibayarkan. -->
                                <th class="px-5 py-3 font-semibold">Dibayar</th>
                                <!-- Kolom uang kembalian. -->
                                <th class="px-5 py-3 font-semibold">Kembalian</th>
                                <!-- Kolom status pembayaran. -->
                                <th class="px-5 py-3 font-semibold">Status</th>
                            <!-- Tutup baris judul tabel. -->
                            </tr>
                        <!-- Tutup bagian kepala tabel. -->
                        </thead>
                        <!-- Bagian isi tabel menampilkan data transaksi dari database. -->
                        <tbody class="divide-y divide-gray-100">
                            <!-- Periksa apakah query menemukan sedikitnya satu transaksi. -->
                            <?php if ($transaksi->num_rows > 0): ?>
                                <!-- Mulai nomor urut yang ditampilkan pada kolom pertama. -->
                                <?php $nomor = 1; ?>
                                <!-- Baca setiap transaksi satu per satu dari hasil query. -->
                                <?php while ($baris = $transaksi->fetch_assoc()): ?>
                                    <!-- Mulai baris untuk satu transaksi. -->
                                    <tr>
                                        <!-- Tampilkan nomor urut lalu naikkan untuk baris berikutnya. -->
                                        <td class="px-5 py-4 text-gray-500 sm:px-6"><?= $nomor++; ?></td>
                                        <!-- Tampilkan nama pembeli setelah mengamankan teks untuk HTML. -->
                                        <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-800"><?= htmlspecialchars($baris['nama_pembeli']); ?></td>
                                        <!-- Tampilkan nomor meja sebagai bilangan bulat. -->
                                        <td class="px-5 py-4 text-gray-600"><?= (int) $baris['no_meja']; ?></td>
                                        <!-- Ubah tanggal database ke format hari-bulan-tahun. -->
                                        <td class="whitespace-nowrap px-5 py-4 text-gray-600"><?= date('d-m-Y', strtotime($baris['tanggal_pembelian'])); ?></td>
                                        <!-- Tampilkan total tagihan dengan format rupiah. -->
                                        <td class="whitespace-nowrap px-5 py-4 font-medium">Rp <?= number_format((int) $baris['total_tagihan'], 0, ',', '.'); ?></td>
                                        <!-- Mulai kolom uang pembayaran. -->
                                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                            <!-- Tampilkan tanda strip jika uang bayar belum dicatat, selain itu tampilkan rupiah. -->
                                            <?= $baris['uang_bayar'] === null ? '-' : 'Rp ' . number_format((int) $baris['uang_bayar'], 0, ',', '.'); ?>
                                        <!-- Tutup kolom uang pembayaran. -->
                                        </td>
                                        <!-- Mulai kolom uang kembalian. -->
                                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                            <!-- Tampilkan tanda strip jika kembalian belum ada, selain itu tampilkan rupiah. -->
                                            <?= $baris['kembalian'] === null ? '-' : 'Rp ' . number_format((int) $baris['kembalian'], 0, ',', '.'); ?>
                                        <!-- Tutup kolom uang kembalian. -->
                                        </td>
                                        <!-- Mulai kolom status pembayaran. -->
                                        <td class="whitespace-nowrap px-5 py-4">
                                            <!-- Tampilkan label hijau saat status transaksi lunas. -->
                                            <?php if ($baris['status_bayar'] === 'Lunas'): ?>
                                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Lunas</span>
                                            <!-- Jika bukan lunas, tampilkan label kuning belum lunas. -->
                                            <?php else: ?>
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Belum lunas</span>
                                            <!-- Akhiri percabangan status pembayaran. -->
                                            <?php endif; ?>
                                        <!-- Tutup kolom status pembayaran. -->
                                        </td>
                                    <!-- Tutup baris transaksi saat ini. -->
                                    </tr>
                                <!-- Ulangi sampai semua transaksi selesai ditampilkan. -->
                                <?php endwhile; ?>
                            <!-- Jika tidak ada transaksi, tampilkan pesan kosong. -->
                            <?php else: ?>
                                <!-- Buat satu baris pesan yang mencakup seluruh delapan kolom tabel. -->
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">Belum ada riwayat transaksi.</td>
                                <!-- Tutup baris pesan kosong. -->
                                </tr>
                            <!-- Akhiri pemeriksaan ketersediaan data transaksi. -->
                            <?php endif; ?>
                        <!-- Tutup bagian isi tabel. -->
                        </tbody>
                    <!-- Tutup tabel riwayat pembayaran. -->
                    </table>
                <!-- Tutup pembungkus gulir tabel. -->
                </div>
            <!-- Tutup panel riwayat transaksi. -->
            </section>
        <!-- Tutup area konten utama. -->
        </main>
    <!-- Tutup pembungkus navigasi dan konten. -->
    </div>
<!-- Tutup isi halaman. -->
</body>
<!-- Tutup dokumen HTML. -->
</html>
