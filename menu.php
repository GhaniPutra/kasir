<?php
// 1. Proteksi Halaman (Wajib Login)
session_start();
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

// Folder penyimpanan gambar
$upload_dir = __DIR__ . '/uploads_img/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Variabel penampung mode edit
$edit_mode = false;
$id_edit = $nama_edit = $harga_edit = $deskripsi_edit = $gambar_edit = "";

// ==================== A. PROSES TAMBAH MENU ====================
if (isset($_POST['tambah_menu'])) {
    $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $harga = (int) $_POST['harga'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $filename = $_FILES['gambar']['name'];
    $tmp_name = $_FILES['gambar']['tmp_name'];

    if (!empty($filename)) {
        $new_filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $filename);
        move_uploaded_file($tmp_name, $upload_dir . $new_filename);
    } else {
        $new_filename = '';
    }

    $query = "INSERT INTO menu (nama_menu, harga, deskripsi, gambar) VALUES ('$nama_menu', '$harga', '$deskripsi', '$new_filename')";
    if (mysqli_query($conn, $query)) {
        header("Location: menu.php?msg=sukses_tambah");
        exit();
    }
}

// ==================== B. PROSES HAPUS MENU ====================
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    
    $get_img = mysqli_query($conn, "SELECT gambar FROM menu WHERE id = $id_hapus");
    $data_img = mysqli_fetch_assoc($get_img);
    
    if ($data_img && !empty($data_img['gambar']) && file_exists($upload_dir . $data_img['gambar'])) {
        unlink($upload_dir . $data_img['gambar']);
    }
    
    mysqli_query($conn, "DELETE FROM menu WHERE id = $id_hapus");
    header("Location: menu.php?msg=sukses_hapus");
    exit();
}

// ==================== C. PERSIAPAN EDIT MENU ====================
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id_edit = (int) $_GET['edit'];
    $result_edit = mysqli_query($conn, "SELECT * FROM menu WHERE id = $id_edit");
    
    if ($result_edit && mysqli_num_rows($result_edit) > 0) {
        $data_edit = mysqli_fetch_assoc($result_edit);
        $nama_edit = $data_edit['nama_menu'];
        $harga_edit = $data_edit['harga'];
        $deskripsi_edit = $data_edit['deskripsi'];
        $gambar_edit = $data_edit['gambar'];
    }
}

// ==================== D. PROSES UPDATE MENU ====================
if (isset($_POST['update_menu'])) {
    $id = (int) $_POST['id_menu'];
    $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $harga = (int) $_POST['harga'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $filename = $_FILES['gambar']['name'];
    $tmp_name = $_FILES['gambar']['tmp_name'];

    if (!empty($filename)) {
        $new_filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $filename);
        move_uploaded_file($tmp_name, $upload_dir . $new_filename);
        
        $gambar_lama = $_POST['gambar_lama'];
        if (!empty($gambar_lama) && file_exists($upload_dir . $gambar_lama)) {
            unlink($upload_dir . $gambar_lama);
        }
        $query = "UPDATE menu SET nama_menu='$nama_menu', harga='$harga', deskripsi='$deskripsi', gambar='$new_filename' WHERE id=$id";
    } else {
        $query = "UPDATE menu SET nama_menu='$nama_menu', harga='$harga', deskripsi='$deskripsi' WHERE id=$id";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: menu.php?msg=sukses_update");
        exit();
    }
}

$nama_user = $_SESSION['username'] ?? $_SESSION['nama_lengkap'] ?? 'fkm';

// Pencarian Menu Sederhana
$search = "";
if (isset($_GET['cari_menu']) && !empty($_GET['cari_menu'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['cari_menu']);
    $search = "WHERE nama_menu LIKE '%$keyword%' OR deskripsi LIKE '%$keyword%'";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Menu - kasirku</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-[#fcfbf9] text-gray-800 flex min-h-screen font-sans">

    <!-- AREA KIRI: Memanggil Sidenav Terpisah -->
    <?php 
    if (file_exists('sidenav.php')) {
        include 'sidenav.php'; 
    } elseif (file_exists('../sidenav.php')) {
        include '../sidenav.php';
    } else {
        include 'sidenav.php'; 
    }
    ?>

    <!-- AREA KANAN: Konten Utama -->
    <main class="flex-1 p-8 w-full overflow-y-auto">

        <!-- HEADER ATAS ALA HALAMAN BERANDA (MIRA BAKERY) -->
        <div class="bg-[#4a3321] text-white px-6 py-3 rounded-xl shadow-md mb-8 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <span class="font-bold tracking-wider text-sm">MENU</span>
            </div>
            <div>
                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="bg-amber-100 text-[#4a3321] px-5 py-1.5 rounded-full text-xs font-bold shadow hover:bg-white transition">
                    logout
                </a>
            </div>
        </div>

        <!-- LOGO & BRAND MIRA BAKERY DI TENGAH -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold font-serif text-[#4a3321]">mira bakery</h1>
            <p class="text-xs text-[#a07855] italic">Balanced sweetness, undeniable softness</p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 rounded-md bg-green-100 border-l-4 border-green-500 text-green-700 font-medium text-sm shadow-sm">
                <?php
                if ($_GET['msg'] == 'sukses_tambah') echo "Menu baru berhasil ditambahkan ke database!";
                if ($_GET['msg'] == 'sukses_update') echo "Data menu berhasil diperbarui!";
                if ($_GET['msg'] == 'sukses_hapus') echo "Menu beserta gambarnya berhasil dihapus dari sistem!";
                ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- KOLOM KIRI: FORM TAMBAH / EDIT MENU -->
            <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 <?php echo $edit_mode ? 'border-yellow-500' : 'border-[#8b5a2b]'; ?> h-fit sticky top-6">
                <h3 class="text-xl font-bold mb-5 text-gray-800">
                    <?php echo $edit_mode ? 'Edit Menu Makanan' : '+ Tambah Menu Baru'; ?>
                </h3>

                <form action="menu.php" method="POST" enctype="multipart/form-data">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="id_menu" value="<?php echo $id_edit; ?>">
                        <input type="hidden" name="gambar_lama" value="<?php echo $gambar_edit; ?>">
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Menu</label>
                        <input type="text" name="nama_menu" required value="<?php echo htmlspecialchars($nama_edit); ?>"
                            class="border rounded-lg w-full py-2.5 px-3 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b5a2b] text-sm transition"
                            placeholder="Cth: Nasi Goreng Spesial">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Harga (Rp)</label>
                        <input type="number" name="harga" required value="<?php echo $harga_edit; ?>"
                            class="border rounded-lg w-full py-2.5 px-3 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b5a2b] text-sm transition"
                            placeholder="25000">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Makanan</label>
                        <textarea name="deskripsi" rows="3"
                            class="border rounded-lg w-full py-2.5 px-3 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b5a2b] text-sm transition"
                            placeholder="Catatan komposisi atau porsi..."><?php echo htmlspecialchars($deskripsi_edit); ?></textarea>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Upload Gambar</label>
                        <?php if ($edit_mode && !empty($gambar_edit) && file_exists($upload_dir . $gambar_edit)): ?>
                            <div class="mb-3 flex items-center gap-3 p-2 bg-gray-50 border rounded-lg">
                                <img src="uploads_img/<?php echo $gambar_edit; ?>" class="w-12 h-12 object-cover rounded shadow-sm">
                                <span class="text-xs text-gray-500 leading-tight">Biarkan kosong jika<br>tidak ingin mengganti gambar.</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="gambar" accept="image/*"
                            class="border rounded-lg w-full py-2 px-3 text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#8b5a2b]">
                    </div>

                    <div class="flex gap-2">
                        <?php if ($edit_mode): ?>
                            <button type="submit" name="update_menu" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2.5 px-4 rounded-lg w-full transition shadow-sm cursor-pointer">Update</button>
                            <a href="menu.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2.5 px-4 rounded-lg text-center transition shadow-sm">Batal</a>
                        <?php else: ?>
                            <button type="submit" name="tambah_menu" class="bg-[#8b5a2b] hover:bg-[#6c4420] text-white font-bold py-2.5 px-4 rounded-lg w-full transition shadow-sm cursor-pointer">Simpan Menu</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- KOLOM KANAN: TABEL DAFTAR MENU -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border-t-4 border-[#8b5a2b]">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-3">
                    <h3 class="text-xl font-bold text-gray-800">Daftar Menu Tersedia</h3>

                    <!-- Form Pencarian Menu -->
                    <form action="" method="get" class="flex gap-2 w-full sm:w-auto">
                        <input type="text" name="cari_menu" value="<?php echo isset($_GET['cari_menu']) ? htmlspecialchars($_GET['cari_menu']) : ''; ?>"
                            placeholder="Cari menu..." class="border border-gray-300 px-3 py-1.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b5a2b]">
                        <button type="submit" class="bg-[#8b5a2b] text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-[#6c4420]">Cari</button>
                        <?php if (isset($_GET['cari_menu'])): ?>
                            <a href="menu.php" class="bg-gray-400 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-gray-500">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Kontainer Tabel Maksimal Tinggi 530px dengan Scroll -->
                <div class="overflow-x-auto overflow-y-auto max-h-[530px] rounded-lg border border-gray-200">
                    <table class="min-w-full bg-white text-sm">
                        <thead class="bg-[#fdf8f6] text-[#8b5a2b] border-b sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="py-3 px-4 text-left font-semibold bg-[#fdf8f6]">Gambar</th>
                                <th class="py-3 px-4 text-left font-semibold bg-[#fdf8f6]">Menu & Deskripsi</th>
                                <th class="py-3 px-4 text-right font-semibold bg-[#fdf8f6]">Harga</th>
                                <th class="py-3 px-4 text-center font-semibold bg-[#fdf8f6]">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $query_menu = mysqli_query($conn, "SELECT * FROM menu $search ORDER BY id DESC");
                            if ($query_menu && mysqli_num_rows($query_menu) > 0):
                                while ($row = mysqli_fetch_assoc($query_menu)):
                            ?>
                                <tr class="hover:bg-amber-50/40 transition">
                                    <td class="py-3 px-4">
                                        <?php if (!empty($row['gambar']) && file_exists($upload_dir . $row['gambar'])): ?>
                                            <img src="uploads_img/<?php echo $row['gambar']; ?>" class="w-14 h-14 object-cover rounded-lg shadow-sm border border-gray-200">
                                        <?php else: ?>
                                            <div class="w-14 h-14 bg-gray-200 rounded-lg flex items-center justify-center text-[10px] text-gray-400 font-bold border border-gray-200">NO IMG</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-800 text-base"><?php echo htmlspecialchars($row['nama_menu']); ?></div>
                                        <div class="text-xs text-gray-500 mt-1 line-clamp-2" title="<?php echo htmlspecialchars($row['deskripsi']); ?>">
                                            <?php echo htmlspecialchars($row['deskripsi']); ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-[#8b5a2b] whitespace-nowrap">
                                        Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <a href="menu.php?edit=<?php echo $row['id']; ?>" class="inline-block bg-amber-100 text-[#8b5a2b] hover:bg-amber-200 px-3 py-1.5 rounded-md font-bold text-xs mr-2 transition">Edit</a>
                                        <a href="menu.php?hapus=<?php echo $row['id']; ?>" onclick="return confirm('Yakin ingin menghapus menu <?php echo addslashes($row['nama_menu']); ?>?')" class="inline-block bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded-md font-bold text-xs transition">Hapus</a>
                                    </td>
                                </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500 bg-gray-50">
                                        Belum ada data menu makanan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>