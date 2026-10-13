<?php
// Mulai sesi agar akses halaman dapat dibatasi untuk pengguna yang sudah masuk.
session_start();

// Alihkan pengguna ke halaman masuk jika sesi autentikasi belum tersedia.
if (!isset($_SESSION['login'])) {
    header("Location: login.php?msg=belum_login");
    exit();
}

// Muat koneksi database dari lokasi proyek yang tersedia.
if (file_exists(__DIR__ . '/../conn/koneksi.php')) {
    include(__DIR__ . '/../conn/koneksi.php');
} elseif (file_exists(__DIR__ . '/conn/koneksi.php')) {
    include(__DIR__ . '/conn/koneksi.php');
} elseif (file_exists(__DIR__ . '/../koneksi.php')) {
    include(__DIR__ . '/../koneksi.php');
} else {
    include 'koneksi.php';
}

// Aktifkan exception MySQLi agar kegagalan transaksi dapat dibatalkan dan dilaporkan.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Tetapkan lokasi file gambar yang dipakai katalog menu.
$upload_dir = __DIR__ . '/uploads_img/';

// Siapkan pesan kesalahan dan nilai formulir yang dapat dipulihkan setelah validasi gagal.
$error_msg = '';
$nama_pembeli_form = '';
$meja_form = '';
$items_form = [];
// Atur status awal ke belum lunas agar pilihan pembayaran selalu memiliki nilai aman.
$status_bayar_form = 'Belum';
// Atur nominal awal ke nol agar transaksi tanpa pembayaran tidak mengandung nilai kosong.
$uang_bayar_form = '0';

// Proses pesanan hanya ketika formulir dikirim dengan tombol simpan.
if (isset($_POST['simpan_pesanan'])) {
    // Terima nama pelanggan sebagai teks saja, lalu rapikan spasi di kedua ujungnya.
    $nama_pembeli_form = is_string($_POST['nama_pembeli'] ?? null) ? trim($_POST['nama_pembeli']) : '';
    // Simpan nilai meja sebagai string dahulu agar format yang salah dapat ditolak.
    $meja_form = is_string($_POST['meja'] ?? null) ? trim($_POST['meja']) : '';
    // Ambil status pembayaran yang dipilih dan batasi pada nilai enum database.
    $status_bayar_form = is_string($_POST['status_bayar'] ?? null) ? $_POST['status_bayar'] : '';
    // Ambil nominal pembayaran sebagai teks agar format selain angka bulat dapat ditolak.
    $uang_bayar_form = is_string($_POST['uang_bayar'] ?? null) ? trim($_POST['uang_bayar']) : '';
    // Pertahankan jumlah item yang dimasukkan agar formulir tidak kosong setelah gagal.
    $items_form = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
    // Siapkan keranjang server yang hanya berisi ID menu dan jumlah yang lolos validasi.
    $keranjang = [];
    // Batasi setiap angka rupiah sesuai kapasitas kolom INT bertanda pada basis data.
    $batas_int = 2147483647;
    // Pastikan nama valid sebagai UTF-8 dan panjangnya tidak melampaui kolom VARCHAR(100).
    $nama_valid = preg_match('/\A.{1,100}\z/us', $nama_pembeli_form) === 1;

    // Tolak nama tidak valid atau nomor meja di luar rentang kolom INT positif.
    if (!$nama_valid || !preg_match('/^[0-9]+$/D', $meja_form) || (int) $meja_form < 1 || (float) $meja_form > $batas_int) {
        $error_msg = 'Nama pembeli wajib diisi maksimal 100 karakter dan nomor meja harus berupa bilangan positif.';
    }

    // Tolak status pembayaran yang tidak tersedia pada enum transaksi.
    if (!in_array($status_bayar_form, ['Belum', 'Lunas'], true)) {
        // Beri tahu kasir bahwa status yang dikirim tidak didukung oleh skema transaksi.
        $error_msg = 'Pilih status pembayaran yang tersedia.';
    }

    // Pastikan uang dibayar berupa bilangan bulat nonnegatif dalam batas kolom INT.
    if (!preg_match('/^[0-9]+$/D', $uang_bayar_form) || (float) $uang_bayar_form > $batas_int) {
        // Tolak nominal kosong, pecahan, negatif, atau melebihi kapasitas database.
        $error_msg = 'Uang yang dibayarkan harus berupa nominal rupiah yang valid.';
    }

    // Periksa jumlah setiap menu tanpa mempercayai nilai dari peramban.
    foreach ($items_form as $id_menu => $qty) {
        // Pastikan kunci menu dan jumlahnya berbentuk bilangan bulat positif atau nol.
        if (!preg_match('/^[1-9][0-9]*$/D', (string) $id_menu) || !is_scalar($qty) || !preg_match('/^[0-9]+$/D', (string) $qty) || (float) $qty > $batas_int) {
            $error_msg = 'Data menu atau jumlah pesanan tidak valid.';
            break;
        }

        // Abaikan menu dengan jumlah nol dan simpan jumlah positif ke keranjang.
        if ((int) $qty > 0) {
            $keranjang[(int) $id_menu] = (int) $qty;
        }
    }

    // Pastikan transaksi memiliki setidaknya satu item dengan jumlah yang dipesan.
    if ($error_msg === '' && $keranjang === []) {
        $error_msg = 'Pilih minimal satu menu dengan jumlah lebih dari nol.';
    }

    // Simpan transaksi beserta seluruh rincian secara atomik setelah semua input lolos validasi.
    if ($error_msg === '') {
        try {
            // Mulai transaksi agar data pesanan tidak tersimpan sebagian.
            $conn->begin_transaction();
            // Siapkan query harga menu agar ID kiriman tidak dapat menyisipkan SQL.
            $menu_stmt = $conn->prepare('SELECT nama_menu, harga FROM menu WHERE id = ?');
            // Siapkan query transaksi dan ambil tanggal langsung dari database untuk selaras dengan laporan harian.
            $pesanan_stmt = $conn->prepare("INSERT INTO pembelian (nama_pembeli, no_meja, tanggal_pembelian, total_tagihan, status_bayar, uang_bayar, kembalian) VALUES (?, ?, CURDATE(), ?, ?, ?, ?)");
            // Siapkan query rincian untuk menghubungkan setiap menu dengan transaksi baru.
            $detail_stmt = $conn->prepare('INSERT INTO detail_pembelian (pembelian_id, menu_id, jumlah, subtotal) VALUES (?, ?, ?, ?)');
            // Inisialisasi total tagihan berdasarkan harga resmi dari basis data.
            $total_tagihan = 0;
            // Inisialisasi rincian keranjang untuk disimpan setelah transaksi utama dibuat.
            $rincian = [];

            // Hitung ulang subtotal server-side menggunakan harga terkini dari basis data.
            foreach ($keranjang as $id_menu => $jumlah) {
                // Ikat ID menu sebagai parameter, bukan sebagai bagian dari query SQL.
                $menu_stmt->bind_param('i', $id_menu);
                // Jalankan pencarian untuk mendapatkan nama dan harga resmi menu.
                $menu_stmt->execute();
                // Ambil satu baris menu yang cocok dengan ID yang diminta.
                $menu = $menu_stmt->get_result()->fetch_assoc();

                // Tolak pesanan jika menu tidak lagi tersedia di katalog.
                if (!$menu) {
                    throw new DomainException('Salah satu menu tidak ditemukan. Muat ulang halaman dan coba lagi.');
                }

                // Konversi harga katalog menjadi bilangan bulat untuk perhitungan rupiah.
                $harga = (int) $menu['harga'];
                // Cegah subtotal melampaui kapasitas kolom INT pada tabel rincian.
                if ($harga < 0 || ($harga > 0 && $jumlah > intdiv($batas_int, $harga))) {
                    throw new DomainException('Jumlah pesanan terlalu besar untuk diproses.');
                }

                // Hitung subtotal berdasarkan jumlah dan harga dari basis data.
                $subtotal = $harga * $jumlah;
                // Cegah total seluruh keranjang melampaui kapasitas kolom INT.
                if ($total_tagihan > $batas_int - $subtotal) {
                    throw new DomainException('Total pesanan melebihi batas transaksi.');
                }

                // Tambahkan subtotal ke total tagihan dan simpan data rincian item.
                $total_tagihan += $subtotal;
                $rincian[] = ['id' => $id_menu, 'jumlah' => $jumlah, 'subtotal' => $subtotal];
            }

            // Tolak transaksi dengan total nol karena tidak membentuk tagihan POS yang sah.
            if ($total_tagihan < 1) {
                throw new DomainException('Total pesanan harus lebih dari Rp 0.');
            }

            // Ubah nilai pembayaran yang sudah tervalidasi menjadi integer untuk database.
            $uang_bayar = (int) $uang_bayar_form;
            // Pastikan pembayaran lunas mencukupi tagihan dan tentukan nilai kembalian.
            if ($status_bayar_form === 'Lunas' && $uang_bayar < $total_tagihan) {
                // Batalkan transaksi lunas jika uang diterima masih kurang dari tagihan.
                throw new DomainException('Uang dibayarkan belum cukup untuk melunasi pesanan.');
            }
            // Cegah status belum lunas jika uang yang diterima sudah menutup seluruh tagihan.
            if ($status_bayar_form === 'Belum' && $uang_bayar >= $total_tagihan) {
                // Minta kasir memilih status lunas jika pembayaran telah mencukupi.
                throw new DomainException('Ubah status menjadi Lunas karena uang yang diterima sudah mencukupi tagihan.');
            }
            // Hitung kembalian hanya untuk transaksi yang dipilih lunas.
            $kembalian = $status_bayar_form === 'Lunas' ? $uang_bayar - $total_tagihan : 0;

            // Ubah nomor meja yang telah divalidasi menjadi integer untuk kolom database.
            $meja = (int) $meja_form;
            // Ikat semua nilai transaksi tanpa tanggal karena tanggal diisi langsung oleh database.
            $pesanan_stmt->bind_param('siisii', $nama_pembeli_form, $meja, $total_tagihan, $status_bayar_form, $uang_bayar, $kembalian);
            // Simpan transaksi induk beserta total, status pembayaran, uang diterima, dan kembalian.
            $pesanan_stmt->execute();
            // Ambil ID pesanan untuk dipakai sebagai penghubung rincian.
            $pembelian_id = $conn->insert_id;

            // Simpan setiap item ke tabel detail_pembelian.
            foreach ($rincian as $item) {
                // Siapkan nilai rincian untuk parameter query detail.
                $id_menu = $item['id'];
                $jumlah = $item['jumlah'];
                $subtotal = $item['subtotal'];
                // Ikat seluruh nilai detail dengan tipe integer.
                $detail_stmt->bind_param('iiii', $pembelian_id, $id_menu, $jumlah, $subtotal);
                // Simpan detail item yang terhubung ke transaksi induk.
                $detail_stmt->execute();
            }

            // Tutup semua statement sebelum menyelesaikan transaksi basis data.
            $menu_stmt->close();
            $pesanan_stmt->close();
            $detail_stmt->close();
            // Pastikan transaksi induk dan semua detail tersimpan bersama.
            $conn->commit();
            // Buka riwayat pembayaran agar kasir dapat melihat transaksi yang baru dibuat.
            header('Location: input_pesanan.php?msg=sukses_pesan');
            // Hentikan output halaman setelah pengalihan berhasil dikirim.
            exit();
        } catch (Throwable $exception) {
            // Batalkan semua perubahan jika query mana pun gagal sebelum commit.
            $conn->rollback();
            // Catat penyebab teknis untuk administrator tanpa membocorkannya ke halaman.
            error_log($exception->getMessage());
            // Tampilkan pesan yang dapat ditindaklanjuti kepada kasir.
            $error_msg = $exception instanceof DomainException
                ? $exception->getMessage()
                : 'Pesanan gagal disimpan. Periksa koneksi dan struktur basis data, lalu coba lagi.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pesanan - Mira Bakery</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="min-h-screen bg-[#f8f6f3] font-sans text-gray-800">
    <div class="flex min-h-screen">
        <?php include __DIR__ . '/sidenav.php'; ?>

        <main class="min-w-0 flex-1 p-5 sm:p-8">
            <div class="mb-8 rounded-xl bg-[#4a3321] px-6 py-3 text-white shadow-md">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-bold tracking-wider">INPUT PESANAN</span>
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
                <p class="text-sm font-medium text-[#9a7658]">Mira Bakery · Pesanan</p>
                <h2 class="mt-1 text-3xl font-bold text-[#4a3321]">Input pesanan</h2>
                <p class="mt-1 text-sm text-gray-500">Isi data pelanggan dan pilih menu yang dipesan.</p>
            </header>

            <?php if (($_GET['msg'] ?? '') === 'sukses_pesan'): ?>
                <div class="mb-6 rounded-md border-l-4 border-green-500 bg-green-100 p-4 text-sm font-medium text-green-700 shadow-sm" role="status">
                    Pesanan berhasil disimpan dengan status pembayaran yang dipilih.
                </div>
            <?php endif; ?>

            <?php if ($error_msg !== ''): ?>
                <div class="mb-6 rounded-md border-l-4 border-red-500 bg-red-100 p-4 text-sm font-medium text-red-700 shadow-sm" role="alert">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form action="input_pesanan.php" method="POST">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <section class="h-fit rounded-xl border border-amber-100 border-t-4 border-t-[#8b5a2b] bg-white p-6 shadow-sm">
                        <h2 class="mb-5 text-xl font-bold text-[#4a3321]">Detail pelanggan</h2>

                        <div class="mb-4">
                            <label for="nama_pembeli" class="mb-2 block text-sm font-bold text-gray-700">Nama pembeli</label>
                            <input type="text" name="nama_pembeli" id="nama_pembeli" required maxlength="100" value="<?php echo htmlspecialchars($nama_pembeli_form, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama pelanggan..."
                                class="w-full rounded-lg border bg-gray-50 px-3 py-2.5 text-sm transition focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b5a2b]">
                        </div>

                        <div class="mb-6">
                            <label for="meja" class="mb-2 block text-sm font-bold text-gray-700">Nomor meja</label>
                            <input type="number" name="meja" id="meja" required min="1" max="2147483647" step="1" value="<?php echo htmlspecialchars($meja_form, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: 5"
                                class="w-full rounded-lg border bg-gray-50 px-3 py-2.5 text-sm transition focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b5a2b]">
                        </div>

                        <div class="mb-4">
                            <label for="status_bayar" class="mb-2 block text-sm font-bold text-gray-700">Status pembayaran</label>
                            <select name="status_bayar" id="status_bayar" required
                                class="w-full rounded-lg border bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b5a2b]">
                                <option value="Belum" <?php echo $status_bayar_form === 'Belum' ? 'selected' : ''; ?>>Belum lunas</option>
                                <option value="Lunas" <?php echo $status_bayar_form === 'Lunas' ? 'selected' : ''; ?>>Lunas</option>
                            </select>
                        </div>

                        <div class="mb-6">
                            <label for="uang_bayar" class="mb-2 block text-sm font-bold text-gray-700">Uang dibayarkan (Rp)</label>
                            <input type="number" name="uang_bayar" id="uang_bayar" required min="0" max="2147483647" step="1" value="<?php echo htmlspecialchars($uang_bayar_form, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: 50000"
                                class="w-full rounded-lg border bg-gray-50 px-3 py-2.5 text-sm transition focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8b5a2b]">
                            <p class="mt-1 text-xs text-gray-500">Untuk status belum lunas, masukkan pembayaran sebagian atau 0.</p>
                        </div>

                        <div class="mb-6 rounded-lg border border-amber-100 bg-[#fcf8f3] p-4" aria-live="polite">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Jumlah item</span>
                                <span id="jumlah-item" class="font-semibold text-[#4a3321]">0</span>
                            </div>
                            <div class="mt-3 flex justify-between border-t border-amber-100 pt-3">
                                <span class="font-bold text-[#4a3321]">Total</span>
                                <span id="total-tagihan" class="font-bold text-[#8b5a2b]">Rp 0</span>
                            </div>
                            <div class="mt-3 flex justify-between text-sm">
                                <span class="text-gray-600">Kembalian</span>
                                <span id="nilai-kembalian" class="font-semibold text-[#4a3321]">Rp 0</span>
                            </div>
                            <p id="validasi-pembayaran" class="mt-2 text-xs text-red-600" role="status"></p>
                        </div>

                        <button type="submit" name="simpan_pesanan" class="block w-full cursor-pointer rounded-lg bg-[#8b5a2b] px-4 py-3 text-center font-bold text-white shadow-sm transition hover:bg-[#6c4420]">
                            Simpan pesanan
                        </button>
                    </section>

                    <section class="rounded-xl border border-amber-100 border-t-4 border-t-[#8b5a2b] bg-white p-6 shadow-sm lg:col-span-2">
                        <h2 class="mb-5 text-xl font-bold text-[#4a3321]">Pilih menu pesanan</h2>

                        <div class="grid max-h-[550px] grid-cols-1 gap-4 overflow-y-auto pr-1 sm:grid-cols-2">
                            <?php
                            $get_menu = mysqli_query($conn, "SELECT * FROM menu ORDER BY nama_menu ASC");
                            if ($get_menu && mysqli_num_rows($get_menu) > 0):
                                while ($m = mysqli_fetch_assoc($get_menu)):
                                    $qty_id = 'qty_' . (int) $m['id'];
                            ?>
                                <article class="flex items-center gap-3 rounded-xl border border-amber-100 bg-[#fcf8f3] p-3 transition hover:border-[#c9a47e]">
                                    <?php if (!empty($m['gambar']) && file_exists($upload_dir . $m['gambar'])): ?>
                                        <img src="uploads_img/<?php echo htmlspecialchars($m['gambar']); ?>" alt="<?php echo htmlspecialchars($m['nama_menu']); ?>" class="h-16 w-16 rounded-lg border border-amber-100 object-cover">
                                    <?php else: ?>
                                        <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-amber-100 text-[10px] font-bold text-[#9a7658]" aria-label="Gambar tidak tersedia">NO IMG</div>
                                    <?php endif; ?>

                                    <div class="min-w-0 flex-1">
                                        <h3 class="truncate text-sm font-bold text-[#4a3321]"><?php echo htmlspecialchars($m['nama_menu']); ?></h3>
                                        <p class="mt-0.5 text-xs font-bold text-[#8b5a2b]">Rp <?php echo number_format($m['harga'], 0, ',', '.'); ?></p>

                                        <div class="mt-2 flex items-center gap-2">
                                            <label for="<?php echo $qty_id; ?>" class="text-xs font-semibold text-gray-500">Qty:</label>
                                            <?php
                                            // Pulihkan jumlah item lama jika formulir sebelumnya gagal divalidasi.
                                            $qty_form = $items_form[$m['id']] ?? '0';
                                            // Abaikan nilai bersarang agar atribut HTML hanya menerima teks sederhana.
                                            $qty_form = is_scalar($qty_form) ? (string) $qty_form : '0';
                                            ?>
                                            <input type="number" name="items[<?php echo (int) $m['id']; ?>]" id="<?php echo $qty_id; ?>" min="0" max="2147483647" step="1" value="<?php echo htmlspecialchars($qty_form, ENT_QUOTES, 'UTF-8'); ?>" data-harga="<?php echo (int) $m['harga']; ?>"
                                                class="w-16 rounded-md border bg-white px-2 py-1 text-center text-xs focus:outline-none focus:ring-2 focus:ring-[#8b5a2b]">
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Subtotal: <span class="subtotal-menu">Rp 0</span></p>
                                    </div>
                                </article>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <p class="col-span-full py-10 text-center text-sm text-gray-400">
                                    Belum ada menu yang tersedia. Silakan isi daftar menu terlebih dahulu di halaman Menu.
                                </p>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Siapkan pemformat rupiah dengan aturan angka Indonesia.
        const formatRupiah = new Intl.NumberFormat('id-ID');
        // Ambil semua input jumlah menu yang dapat mengubah nilai keranjang.
        const inputJumlahMenu = document.querySelectorAll('[data-harga]');
        // Ambil kontrol pembayaran yang dipakai untuk menampilkan kembalian.
        const pilihanStatus = document.getElementById('status_bayar');
        // Ambil input nominal yang diterima dari pelanggan.
        const inputUangBayar = document.getElementById('uang_bayar');
        // Ambil elemen halaman yang menampilkan nilai uang kembalian.
        const tampilanKembalian = document.getElementById('nilai-kembalian');
        // Ambil elemen halaman yang menampilkan peringatan aturan pembayaran.
        const pesanValidasiPembayaran = document.getElementById('validasi-pembayaran');
        // Simpan status total terakhir agar dapat dipakai validasi pembayaran.
        let totalKeranjang = 0;
        // Hitung ulang subtotal, jumlah item, dan total setiap kali jumlah berubah.
        function hitungKeranjang() {
            // Mulai ulang akumulator agar total selalu berasal dari nilai input terbaru.
            let jumlahItem = 0;
            let totalTagihan = 0;
            // Perbarui subtotal per menu lalu tambahkan nilainya ke ringkasan pesanan.
            inputJumlahMenu.forEach((input) => {
                // Baca jumlah dan harga dari elemen yang terkait dengan menu tersebut.
                const jumlah = Number(input.value);
                const harga = Number(input.dataset.harga);
                // Abaikan nilai sementara yang bukan bilangan bulat aman atau kurang dari nol.
                const jumlahValid = Number.isSafeInteger(jumlah) && jumlah >= 0 ? jumlah : 0;
                // Hitung subtotal menu dan ambil elemen tampilan subtotal pada kartu yang sama.
                const subtotal = harga * jumlahValid;
                const tampilanSubtotal = input.closest('article').querySelector('.subtotal-menu');
                // Tampilkan subtotal yang telah diformat sebagai mata uang rupiah.
                tampilanSubtotal.textContent = `Rp ${formatRupiah.format(subtotal)}`;
                // Tambahkan jumlah unit dan subtotal menu ke ringkasan keranjang.
                jumlahItem += jumlahValid;
                totalTagihan += subtotal;
            });
            // Tampilkan akumulasi jumlah semua unit yang dipilih.
            document.getElementById('jumlah-item').textContent = formatRupiah.format(jumlahItem);
            // Tampilkan total tagihan terkini dalam format rupiah.
            document.getElementById('total-tagihan').textContent = `Rp ${formatRupiah.format(totalTagihan)}`;
            // Simpan total terhitung dan perbarui kembalian serta validasi uang bayar.
            totalKeranjang = totalTagihan;
            hitungPembayaran();
        }
        // Hitung kembalian dan validasi nominal sesuai status pembayaran pilihan kasir.
        function hitungPembayaran() {
            // Ambil uang bayar hanya jika nilainya merupakan bilangan bulat aman.
            const uangBayar = Number(inputUangBayar.value);
            // Gunakan nol sementara jika angka kosong, pecahan, negatif, atau tidak aman.
            const uangValid = Number.isSafeInteger(uangBayar) && uangBayar >= 0 ? uangBayar : 0;
            // Tentukan aturan pembayaran berdasarkan status yang dipilih.
            const statusLunas = pilihanStatus.value === 'Lunas';
            // Tampilkan kembalian hanya untuk pembayaran lunas.
            const kembalian = statusLunas ? Math.max(uangValid - totalKeranjang, 0) : 0;
            // Perbarui ringkasan kembalian dengan format rupiah.
            tampilanKembalian.textContent = `Rp ${formatRupiah.format(kembalian)}`;
            // Wajibkan uang minimal sebesar tagihan ketika status dipilih lunas.
            inputUangBayar.min = statusLunas ? String(totalKeranjang) : '0';
            // Batasi pembayaran parsial agar status belum lunas tidak menutupi seluruh tagihan.
            inputUangBayar.max = statusLunas ? '2147483647' : String(Math.max(totalKeranjang - 1, 0));
            // Susun pesan yang menjelaskan bila nominal tidak sesuai status pilihan.
            let pesan = '';
            // Tandai pembayaran lunas yang nilainya belum mencapai jumlah tagihan.
            if (statusLunas && uangValid < totalKeranjang) {
                pesan = 'Uang dibayarkan harus minimal sebesar total tagihan.';
            // Tandai pembayaran belum lunas yang nilainya justru sudah menutup seluruh tagihan.
            } else if (!statusLunas && totalKeranjang > 0 && uangValid >= totalKeranjang) {
                pesan = 'Pilih status Lunas karena uang yang diterima sudah mencukupi tagihan.';
            }
            // Atur pesan validasi bawaan peramban agar pengiriman tidak lolos jika tidak sesuai.
            inputUangBayar.setCustomValidity(pesan);
            // Tampilkan pesan validasi pembayaran tepat di bawah ringkasan total.
            pesanValidasiPembayaran.textContent = pesan;
        }
        // Daftarkan pembaruan otomatis setiap kali kasir mengubah jumlah menu.
        inputJumlahMenu.forEach((input) => input.addEventListener('input', hitungKeranjang));
        // Perbarui kembalian saat jumlah uang yang diterima berubah.
        inputUangBayar.addEventListener('input', hitungPembayaran);
        // Perbarui aturan nominal saat status pembayaran diganti.
        pilihanStatus.addEventListener('change', hitungPembayaran);
        // Hitung nilai awal agar formulir yang dipulihkan menampilkan total dengan benar.
        hitungKeranjang();
    </script>
</body>
</html>