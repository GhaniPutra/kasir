<?php
session_start();

include 'conn/koneksi.php';
$pesan_eror = "";
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    // Diubah dari tabel 'user' menjadi 'users' agar cocok dengan database registrasi
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);

        $_SESSION['login'] = true;
        $_SESSION['id'] = $user_data['id'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['password'] = $user_data['password'];

        header("Location: index.php");
        exit;
    } else {
        $pesan_eror = "Username atau Password SALAH!!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login-CoffeeShop-MIAOU</title>
    <link href="src/output.css" rel="stylesheet">
    <style>
        .bg-fira-brown {
            background-color: #8C583E;
        }

        .bg-fira-input {
            background-color: #72442C;
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex items-center justify-center p-4">
    <div class="flex w-full max-w-4xl h-550px bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Sisi Kiri: Form Login -->
        <div class="w-1/2 bg-fira-brown p-10 flex flex-col justify-center text-white">
            <div class="text-center mb-6">
                <div class="mb-2 flex justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-widest uppercase">LOGIN</h2>
            </div>

            <?php if (!empty($pesan_eror)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-2 mb-3 text-xs font-medium" role="alert">
                    <?php echo $pesan_eror; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'belum_login'): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-2 mb-3 text-xs font-medium" role="alert">Akses ditolak. Anda harus login terlebih dahulu!</div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-3">
                <div>
                    <label for="username" class="block text-xs font-medium text-gray-200 mb-1">Username:</label>
                    <input type="text" name="username" id="username" required class="w-full rounded-md bg-fira-input border border-transparent px-3 py-2 text-xs text-white focus:outline-none focus:ring-1 focus:ring-white">
                </div>
                <div>
                    <label for="password" class="block text-xs font-medium text-gray-200 mb-1">Password:</label>
                    <input type="password" name="password" id="password" required class="w-full rounded-md bg-fira-input border border-transparent px-3 py-2 text-xs text-white focus:outline-none focus:ring-1 focus:ring-white">
                </div>
                <div class="pt-1">
                    <button type="submit" name="login" class="w-full bg-white text-gray-800 font-bold py-2 px-4 rounded-full hover:bg-gray-100 transition duration-200 text-xs tracking-wider shadow">LOGIN</button>
                </div>
            </form>

            <div class="mt-4 text-center text-xs text-gray-200">
                Belum punya akun? <a href="register.php" class="underline font-semibold hover:text-white">daftar sekarang</a>
            </div>
        </div>

        <!-- Sisi Kanan: Ilustrasi / Branding -->
        <div class="w-1/2 bg-gray-50 flex items-center justify-center relative p-6 border-l border-gray-200" style="background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 16px 16px;">
            <div class="w-52 h-52 bg-fira-brown rounded-full flex flex-col items-center justify-center shadow-2xl border-4 border-white text-center z-10">
                <svg class="w-10 h-10 text-white mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <span class="text-white text-base font-serif italic">mira</span>
                <span class="text-white text-xs font-bold tracking-widest uppercase">bakery</span>
            </div>
        </div>
    </div>
</body>

</html>