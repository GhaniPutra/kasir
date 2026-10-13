<?php
session_start();

// 1. Cek Sesi Login (Jika BELUM login, lempar ke login.php)
if (!isset($_SESSION['login'])) {
    header("Location: login.php?msg=belum_login");
    exit();
}

// 2. Load File Koneksi Database
if (file_exists(__DIR__ . '/../conn/koneksi.php')) {
    include __DIR__ . '/../conn/koneksi.php';
} elseif (file_exists(__DIR__ . '/conn/koneksi.php')) {
    include __DIR__ . '/conn/koneksi.php';
} else {
    $conn = new mysqli("localhost", "root", "", "db_latkasir");
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
}

$where_clause = "";
$order_clause = "ORDER BY id DESC";

// Filter Tanggal
if (isset($_GET['tgl_cari']) && !empty($_GET['tgl_cari'])) {
    $tgl = $conn->real_escape_string($_GET['tgl_cari']);
    $where_clause = "WHERE tanggal_pembelian='$tgl'";
}

// Filter Sorting
if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'nama_asc') {
        $order_clause = "ORDER BY nama ASC";
    } elseif ($_GET['sort'] == 'tgl_asc') {
        $order_clause = "ORDER BY tanggal_pembelian ASC";
    } elseif ($_GET['sort'] == 'tgl_desc' || $_GET['sort'] == 'terbaru') {
        $order_clause = "ORDER BY tanggal_pembelian DESC";
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Kasirku</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-[#fcfbf9] text-gray-800 flex min-h-screen font-sans">

    <!-- PANGGIL FILE SIDENAV ANDA (Atau paste kode sidenav Anda di sini jika tidak pakai include) -->
    <?php 
    if (file_exists('sidenav.php')) {
        include 'sidenav.php'; 
    } elseif (file_exists('../sidenav.php')) {
        include '../sidenav.php';
    } else {
        // Fallback jika file sidenav ada di folder lain, silakan sesuaikan
        include 'sidenav.php'; 
    }
    ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-8 overflow-y-auto">

        <!-- HEADER ATAS (HOME & LOGOUT) -->
        <div class="bg-[#4a3321] text-white px-8 py-5 rounded-xl shadow-md mb-8 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <span class="font-bold tracking-wider text-sm">HOME</span>
            </div>
        </div>

        <!-- LOGO & BRAND MIRA BAKERY DI TENGAH -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold font-serif text-[#4a3321]">mira bakery</h1>
            <p class="text-xs text-[#a07855] italic">Balanced sweetness, undeniable softness</p>
        </div>

        <!-- GRID ITEM MENU / PRODUK -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <!-- Item 1 -->
            <div class="bg-[#f5e6d3] p-4 rounded-xl shadow-sm flex items-center space-x-4 border border-[#e6d0b5]">
                <div class="w-16 h-16 bg-amber-200 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center font-bold text-xs text-[#4a3321]">DONUTT</div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">DONUTT</h3>
                    <p class="text-xs text-gray-600 font-medium mb-1">Rp. 30.000</p>
                    <a href="input_pesanan.php" class="inline-block text-xs bg-[#8b5a2b] text-white px-2.5 py-1 rounded shadow-sm hover:bg-[#6c4420]">Shop Now</a>
                </div>
            </div>

            <!-- Item 2 -->
            <div class="bg-[#f5e6d3] p-4 rounded-xl shadow-sm flex items-center space-x-4 border border-[#e6d0b5]">
                <div class="w-16 h-16 bg-amber-200 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center font-bold text-xs text-[#4a3321]">COOKIES</div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">COOKIES</h3>
                    <p class="text-xs text-gray-600 font-medium mb-1">Rp. 20.000</p>
                    <a href="input_pesanan.php" class="inline-block text-xs bg-[#8b5a2b] text-white px-2.5 py-1 rounded shadow-sm hover:bg-[#6c4420]">Shop Now</a>
                </div>
            </div>

            <!-- Item 3 -->
            <div class="bg-[#f5e6d3] p-4 rounded-xl shadow-sm flex items-center space-x-4 border border-[#e6d0b5]">
                <div class="w-16 h-16 bg-amber-200 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center font-bold text-xs text-[#4a3321]">CUPCAKE</div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Cup Cake</h3>
                    <p class="text-xs text-gray-600 font-medium mb-1">Rp. 13.000</p>
                    <a href="input_pesanan.php" class="inline-block text-xs bg-[#8b5a2b] text-white px-2.5 py-1 rounded shadow-sm hover:bg-[#6c4420]">Shop Now</a>
                </div>
            </div>

            <!-- Item 4 -->
            <div class="bg-[#f5e6d3] p-4 rounded-xl shadow-sm flex items-center space-x-4 border border-[#e6d0b5]">
                <div class="w-16 h-16 bg-amber-200 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center font-bold text-xs text-[#4a3321]">ROLL</div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">CAKE ROLL'S</h3>
                    <p class="text-xs text-gray-600 font-medium mb-1">Rp. 35.000</p>
                    <a href="input_pesanan.php" class="inline-block text-xs bg-[#8b5a2b] text-white px-2.5 py-1 rounded shadow-sm hover:bg-[#6c4420]">Shop Now</a>
                </div>
            </div>

            <!-- Item 5 -->
            <div class="bg-[#f5e6d3] p-4 rounded-xl shadow-sm flex items-center space-x-4 border border-[#e6d0b5]">
                <div class="w-16 h-16 bg-amber-200 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center font-bold text-xs text-[#4a3321]">BUNG</div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Bunggeo'P</h3>
                    <p class="text-xs text-gray-600 font-medium mb-1">Rp. 18.000</p>
                    <a href="input_pesanan.php" class="inline-block text-xs bg-[#8b5a2b] text-white px-2.5 py-1 rounded shadow-sm hover:bg-[#6c4420]">Shop Now</a>
                </div>
            </div>

            <!-- Item 6 -->
            <div class="bg-[#f5e6d3] p-4 rounded-xl shadow-sm flex items-center space-x-4 border border-[#e6d0b5]">
                <div class="w-16 h-16 bg-amber-200 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center font-bold text-xs text-[#4a3321]">BD</div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">B'D Cake</h3>
                    <p class="text-xs text-gray-600 font-medium mb-1">Rp. 55.000</p>
                    <a href="input_pesanan.php" class="inline-block text-xs bg-[#8b5a2b] text-white px-2.5 py-1 rounded shadow-sm hover:bg-[#6c4420]">Shop Now</a>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sukses peasan'): ?>
            <div class="mb-6 p-4 rounded-md bg-green-100 border-l-4 border-green-500 text-green-700 font-medium text-sm">
                Pesanan berhasil dicatat
            </div>
        <?php endif; ?>

        <!-- CARD WRAPPER RIWAYAT TRANSAKSI (DI BAWAH MENU) -->
        <div class="bg-white p-8 rounded-xl shadow-sm border-t-4 border-[#8b5a2b]">

            <!-- Card Header & Filter -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        Riwayat transaksi
                    </h2>
                    <p class="text-sm text-gray-400 mt-1">
                        laporan pencatatan pembelian dan status pembayaran
                    </p>
                </div>

                <!-- Form Filter -->
                <form action="" method="get" class="flex flex-wrap items-center gap-2 bg-[#fdf8f6] p-3 rounded-xl border border-amber-100">
                    <div>
                        <input
                            type="date"
                            name="tgl_cari"
                            value="<?php echo isset($_GET['tgl_cari']) ? htmlspecialchars($_GET['tgl_cari']) : ''; ?>"
                            class="border border-gray-300 p-2 rounded-lg text-sm bg-white focus:ring-2 focus:ring-[#8b5a2b] focus:outline-none text-gray-600">
                    </div>

                    <div>
                        <select
                            name="sort"
                            class="border border-gray-300 p-2 rounded-lg text-sm bg-white focus:ring-2 focus:ring-[#8b5a2b] focus:outline-none text-gray-600">
                            <option value="terbaru" <?php if (!isset($_GET['sort']) || $_GET['sort'] == 'terbaru') echo 'selected'; ?>>
                                Urutkan: terbaru
                            </option>
                            <option value="nama_asc" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'nama_asc') echo 'selected'; ?>>
                                Urutkan: nama pembeli (A-Z)
                            </option>
                            <option value="tgl_asc" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'tgl_asc') echo 'selected'; ?>>
                                Urutkan: tanggal lama
                            </option>
                            <option value="tgl_desc" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'tgl_desc') echo 'selected'; ?>>
                                Urutkan: tanggal terbaru
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="bg-[#8b5a2b] hover:bg-[#6c4420] text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
                        Filter
                    </button>

                    <?php if (isset($_GET['tgl_cari']) || isset($_GET['sort'])): ?>
                        <a href="index.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table Section -->
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full bg-white text-sm">
                    <thead class="bg-[#fdf8f6] text-[#8b5a2b] border-b border-gray-200">
                        <tr>
                            <th class="py-3.5 px-6 text-left font-bold">No</th>
                            <th class="py-3.5 px-6 text-left font-bold">Nama Pembeli</th>
                            <th class="py-3.5 px-6 text-left font-bold">Tanggal Pembelian</th>
                            <th class="py-3.5 px-6 text-left font-bold">Total Pembelian</th>
                            <th class="py-3.5 px-6 text-left font-bold">Status Pembayaran</th>
                            <th class="py-3.5 px-6 text-center font-bold">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <?php
                        $query_pembelian = "SELECT * FROM pembelian $where_clause $order_clause";
                        $result_pembelian = $conn->query($query_pembelian);

                        $no = 1;
                        if ($result_pembelian && $result_pembelian->num_rows > 0):
                            while ($row = $result_pembelian->fetch_assoc()):
                        ?>
                            <tr class="hover:bg-amber-50/40 transition">
                                <td class="py-4 px-6 text-gray-700"><?php echo $no++; ?></td>
                                <td class="py-4 px-6 font-medium text-gray-900"><?php echo htmlspecialchars($row['nama_pembeli'] ?? $row['nama'] ?? '-'); ?></td>
                                <td class="py-4 px-6 text-gray-600"><?php echo isset($row['tanggal_pembelian']) ? date('d-m-Y', strtotime($row['tanggal_pembelian'])) : '-'; ?></td>
                                <td class="py-4 px-6 font-medium text-gray-800">Rp <?php echo number_format($row['total_harga'] ?? $row['total'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="py-4 px-6">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        <?php echo htmlspecialchars($row['status_pembayaran'] ?? 'Lunas'); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <a href="detail_transaksi.php?id=<?php echo $row['id']; ?>" class="text-[#8b5a2b] hover:underline font-semibold">Detail</a>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    Belum ada data transaksi yang tercatat.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

</body>
</html>