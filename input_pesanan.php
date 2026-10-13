<?php
session_start();

// 1. Proteksi Halaman (Wajib Login)
if (!isset($_SESSION['login'])) {
    header("Location: login.php?msg=belum_login");
    exit();
}

// 2. Hubungkan ke Database
if (file_exists(__DIR__ . '/../conn/koneksi.php')) {
    include(__DIR__ . '/../conn/koneksi.php');
} elseif (file_exists(__DIR__ . '/conn/koneksi.php')) {
    include(__DIR__ . '/conn/koneksi.php');
} elseif (file_exists(__DIR__ . '/../koneksi.php')) {
    include(__DIR__ . '/../koneksi.php');
} else {
    include 'koneksi.php';
}

// Matikan exception otomatis mysqli agar tidak memicu Fatal Error saat beda kolom
mysqli_report(MYSQLI_REPORT_OFF);

// Folder gambar menu
$upload_dir = __DIR__ . '/uploads_img/';

// PROSES SIMPAN PESANAN
if (isset($_POST['simpan_pesanan'])) {
    $nama_pembeli = mysqli_real_escape_string($conn, $_POST['nama_pembeli']);
    $meja = mysqli_real_escape_string($conn, $_POST['meja']);
    $tgl_pembelian = date('Y-m-d H:i:s');
    $status_pembayaran = "Belum Bayar";

    $total_harga = 0;
    $detail_pesanan_arr = [];

    if (isset($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $id_menu => $qty) {
            $qty = (int)$qty;
            if ($qty > 0) {
                $q_menu = mysqli_query($conn, "SELECT nama_menu, harga FROM menu WHERE id = $id_menu");
                if ($data_m = mysqli_fetch_assoc($q_menu)) {
                    $subtotal = $data_m['harga'] * $qty;
                    $total_harga += $subtotal;
                    $detail_pesanan_arr[] = $data_m['nama_menu'] . " (" . $qty . "x)";
                }
            }
        }
    }

    if ($total_harga > 0) {
        $detail_pesanan = mysqli_real_escape_string($conn, implode(", ", $detail_pesanan_arr));

        // Percobaan 1: Coba simpan ke kolom 'no_meja'
        $q1 = "INSERT INTO pembelian (nama_pembeli, no_meja, detail_pesanan, total_harga, status_pembayaran, tanggal_pembelian) 
               VALUES ('$nama_pembeli', '$meja', '$detail_pesanan', '$total_harga', '$status_pembayaran', '$tgl_pembelian')";
        $exec = mysqli_query($conn, $q1);

        // Percobaan 2: Jika 'no_meja' tidak ada, coba pakai kolom 'meja'
        if (!$exec) {
            $q2 = "INSERT INTO pembelian (nama_pembeli, meja, detail_pesanan, total_harga, status_pembayaran, tanggal_pembelian) 
                   VALUES ('$nama_pembeli', '$meja', '$detail_pesanan', '$total_harga', '$status_pembayaran', '$tgl_pembelian')";
            $exec = mysqli_query($conn, $q2);
        }

        // Percobaan 3: Jika masih gagal, simpan tanpa kolom meja
        if (!$exec) {
            $q3 = "INSERT INTO pembelian (nama_pembeli, detail_pesanan, total_harga, status_pembayaran, tanggal_pembelian) 
                   VALUES ('$nama_pembeli', '$detail_pesanan', '$total_harga', '$status_pembayaran', '$tgl_pembelian')";
            mysqli_query($conn, $q3);
        }

        header("Location: index.php?msg=sukses_pesan");
        exit();
    } else {
        $error_msg = "Pilih minimal 1 menu makanan/minuman dengan jumlah yang benar!";
    }
}

$nama_user = $_SESSION['username'] ?? $_SESSION['nama_lengkap'] ?? 'fkm';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pesanan - kasirku</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-[#f1f5f9] text-gray-800 flex min-h-screen font-sans">

    <!-- SIDEBAR LEFT -->
    <aside class="w-64 bg-[#1e3a8a] text-white flex flex-col justify-between p-6 flex-shrink-0 min-h-screen">
        <div>
            <div class="mb-10 text-center">
                <h1 class="text-3xl font-extrabold tracking-wide mb-1 text-white">kasirku</h1>
                <p class="text-sm text-gray-200">Hallo : <span class="font-medium"><?php echo htmlspecialchars($nama_user); ?></span></p>
            </div>

            <nav class="space-y-3">
                <a href="index.php" class="block py-2.5 px-5 rounded-lg text-gray-200 hover:bg-[#1d4ed8] hover:text-white transition">
                    Beranda
                </a>
                <a href="menu.php" class="block py-2.5 px-5 rounded-lg text-gray-200 hover:bg-[#1d4ed8] hover:text-white transition">
                    Menu
                </a>
                <a href="input_pesanan.php" class="block py-3 px-5 rounded-lg bg-[#2563eb] text-white font-semibold shadow-md">
                    input pesanan
                </a>
                <a href="pembayaran.php" class="block py-2.5 px-5 rounded-lg text-gray-200 hover:bg-[#1d4ed8] hover:text-white transition">
                    Pembayaran
                </a>
            </nav>
        </div>

        <div>
            <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" 
               class="block w-full py-3 text-center bg-[#ff3b30] hover:bg-red-600 text-white font-bold rounded-lg transition shadow-md">
                Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-8 w-full overflow-y-auto">

        <?php if (isset($error_msg)): ?>
            <div class="mb-6 p-4 rounded-md bg-red-100 border-l-4 border-red-500 text-red-700 font-medium text-sm shadow-sm">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="input_pesanan.php" method="POST">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- INFORMASI PEMBELI & KASIR -->
                <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-blue-600 h-fit">
                    <h3 class="text-xl font-bold mb-5 text-gray-800">Detail Pelanggan</h3>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Pembeli</label>
                        <input type="text" name="nama_pembeli" required placeholder="Masukkan nama pelanggan..."
                            class="border rounded-lg w-full py-2.5 px-3 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nomor / Nama Meja</label>
                        <input type="text" name="meja" required placeholder="Cth: Meja 05 / Takeaway"
                            class="border rounded-lg w-full py-2.5 px-3 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition">
                    </div>

                    <button type="submit" name="simpan_pesanan" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg w-full transition shadow-md cursor-pointer text-center block">
                        Proses Pesanan
                    </button>
                </div>

                <!-- PILIH MENU MASAKAN / MINUMAN -->
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border-t-4 border-blue-600">
                    <h3 class="text-xl font-bold mb-5 text-gray-800">Pilih Menu Pesanan</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[550px] overflow-y-auto pr-1">
                        <?php
                        $get_menu = mysqli_query($conn, "SELECT * FROM menu ORDER BY nama_menu ASC");
                        if ($get_menu && mysqli_num_rows($get_menu) > 0):
                            while ($m = mysqli_fetch_assoc($get_menu)):
                        ?>
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl hover:border-blue-400 bg-gray-50 hover:bg-blue-50/40 transition">
                                <?php if (!empty($m['gambar']) && file_exists($upload_dir . $m['gambar'])): ?>
                                    <img src="uploads_img/<?php echo $m['gambar']; ?>" class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center text-[10px] text-gray-400 font-bold">NO IMG</div>
                                <?php endif; ?>

                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-800 text-sm truncate"><?php echo htmlspecialchars($m['nama_menu']); ?></h4>
                                    <p class="text-blue-600 font-bold text-xs mt-0.5">Rp <?php echo number_format($m['harga'], 0, ',', '.'); ?></p>
                                    
                                    <div class="mt-2 flex items-center gap-2">
                                        <label class="text-xs text-gray-500 font-semibold">Qty:</label>
                                        <input type="number" name="items[<?php echo $m['id']; ?>]" min="0" value="0"
                                            class="w-16 border rounded-md py-1 px-2 text-center text-xs bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <div class="col-span-2 text-center py-10 text-gray-400">
                                Belum ada menu yang tersedia. Silakan isi daftar menu terlebih dahulu di halaman Menu.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </form>
    </main>

</body>
</html>