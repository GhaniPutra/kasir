<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-[#4a3321] text-white flex flex-col h-screen sticky top-0 shadow-[4px_0_15px_rgba(0,0,0,0.1)] flex-shrink-0">

    <div class="p-6 text-center border-b border-[#6c4420]">
        <h2 class="text-2xl font-extrabold tracking-wide">
            kasirku
        </h2>

        <p class="text-xs text-amber-200 mt-2">
            Hallo :
            <span class="font-bold text-white">
                <?php echo htmlspecialchars($_SESSION['username'] ?? 'kasir'); ?>
            </span>
        </p>
    </div>

    <nav class="flex-grow p-6 space-y-2 overflow-y-auto text-sm">

        <a href="index.php"
            class="block px-4 py-3 rounded transition <?php echo ($current_page == 'index.php') ? 'bg-[#8b5a2b] font-bold' : 'hover:bg-[#6c4420]'; ?>">
            Beranda
        </a>

         <a href="menu.php"
            class="block px-4 py-3 rounded transition <?php echo ($current_page == 'menu.php') ? 'bg-[#8b5a2b] font-bold' : 'hover:bg-[#6c4420]'; ?>">
            Menu
        </a>
        
        <a href="input_pesanan.php"
            class="block px-4 py-3 rounded transition <?php echo ($current_page == 'input_pesanan.php') ? 'bg-[#8b5a2b] font-bold' : 'hover:bg-[#6c4420]'; ?>">
            Input Pesanan
        </a>

        <a href="pembayaran.php"
            class="block px-4 py-3 rounded transition <?php echo ($current_page == 'pembayaran.php') ? 'bg-[#8b5a2b] font-bold' : 'hover:bg-[#6c4420]'; ?>">
            Pembayaran
        </a>

    </nav>

     <div class="p-4 border-t border-[#6c4420]">
        <a href="setting.php"
           class="block w-full text-center px-4 py-3 bg-[#8b5a2b] hover:bg-[#6c4420] text-white rounded font-bold transition shadow">
            Setting
        </a>
    </div>

</aside>