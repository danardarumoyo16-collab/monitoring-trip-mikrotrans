<?php
session_start();

if (file_exists('koneksi.php')) {
    include 'koneksi.php';
}

// 1. CEK SESSION LOGIN (Murni membaca data user yang sedang masuk)
if (!isset($_SESSION['user_name'])) {
    // Opsional: header("Location: login.php"); exit();
}

$user_name  = isset($_SESSION['user_name'])  ? $_SESSION['user_name']  : 'Nama User';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'user@transjakarta.co.id';
$user_role  = isset($_SESSION['user_role'])  ? $_SESSION['user_role']  : 'Staf';

// 2. PEMETAAN IKON BADGE SESUAI ROLE LOGIN
$role_icons = [
    'Super Admin'      => 'fa-solid fa-user-shield',
    'Admin'            => 'fa-solid fa-user-gear',
    'Staf'             => 'fa-solid fa-user-check',
    'Staf Operasional' => 'fa-solid fa-user-check',
];
$current_role_icon = isset($role_icons[$user_role]) ? $role_icons[$user_role] : 'fa-regular fa-user';

// Routing Halaman (Default ke 'dashboard')
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Pemetaan Judul & Ikon Header Berdasarkan Halaman
$page_config = [
    'dashboard'       => ['title' => 'Dashboard Analytics', 'icon' => 'fa-solid fa-chart-pie'],
    'ricek_realisasi' => ['title' => 'Ricek Realisasi',     'icon' => 'fa-solid fa-list-check'],
    'unit_operasi'    => ['title' => 'Unit Operasi',        'icon' => 'fa-solid fa-bus'],
    'validasi_trip'   => ['title' => 'Validasi Trip',       'icon' => 'fa-solid fa-square-check'],
    'headway'         => ['title' => 'Headway',             'icon' => 'fa-solid fa-clock'],
    'kesesuaian'      => ['title' => 'Kesesuaian',          'icon' => 'fa-solid fa-sliders'],
    'cari_bus'        => ['title' => 'Cari Nomor Bus',      'icon' => 'fa-solid fa-magnifying-glass'],
    'manual_do'       => ['title' => 'Manual DO',           'icon' => 'fa-solid fa-file-pen'],
    'update_trip'     => ['title' => 'Update Trip',         'icon' => 'fa-solid fa-arrows-rotate'],
    'update_lokasi'   => ['title' => 'Update Lokasi',       'icon' => 'fa-solid fa-location-dot'],
    'sebaran'         => ['title' => 'Sebaran',             'icon' => 'fa-solid fa-map-location-dot'],
    'list_petugas'    => ['title' => 'List Petugas',        'icon' => 'fa-solid fa-users'],
    'list_operator'   => ['title' => 'List Operator',       'icon' => 'fa-solid fa-building'],
];

$current_config = isset($page_config[$page]) ? $page_config[$page] : $page_config['dashboard'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_config['title']; ?> - Monitoring Mikrotrans</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-950 text-slate-100 font-sans h-screen w-screen overflow-hidden flex items-center justify-center p-1.5">

    <div class="w-full h-full bg-[#060b19] rounded-2xl shadow-2xl shadow-black/80 flex overflow-hidden relative">

        <!-- SIDEBAR NAVIGASI -->
        <aside class="w-20 bg-[#092265] shrink-0 select-none h-full relative z-50 border-r border-blue-900/50 flex flex-col justify-between items-center pt-1.5 pb-2 overflow-visible">
            
            <!-- HEADER LOGO ATAS (IKON BINTANG TETAP POSISI ATAS) -->
            <div class="w-full flex flex-col items-center text-center shrink-0 pt-0 mb-0">
                <div class="w-8 h-8 flex items-center justify-center text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20"></path>
                        <path d="M2 12h20"></path>
                        <path d="m4.93 4.93 14.14 14.14"></path>
                        <path d="m4.93 19.07 14.14-14.14"></path>
                    </svg>
                </div>
                <!-- GARIS PEMBATAS ATAS -->
                <div class="w-7 h-[2px] bg-white/70 rounded-full mt-0.5 mb-0.5"></div>
            </div>

            <!-- NAVIGASI MENU (DIBERI PT-2 AGAR IKON MENU TURUN DARI IKON BINTANG) -->
            <nav class="flex-1 w-full flex flex-col items-center justify-evenly pt-2 pb-1 min-h-0 overflow-hidden">
                <?php
                $menu_items = [
                    'dashboard'       => ['icon' => 'fa-chart-pie',         'title' => 'Dashboard Analytics'],
                    'ricek_realisasi' => ['icon' => 'fa-list-check',        'title' => 'Ricek Realisasi'],
                    'unit_operasi'    => ['icon' => 'fa-bus',               'title' => 'Unit Operasi'],
                    'validasi_trip'   => ['icon' => 'fa-square-check',      'title' => 'Validasi Trip'],
                    'headway'         => ['icon' => 'fa-clock',             'title' => 'Headway'],
                    'kesesuaian'      => ['icon' => 'fa-sliders',           'title' => 'Kesesuaian'],
                    'cari_bus'        => ['icon' => 'fa-magnifying-glass',  'title' => 'Cari Nomor Bus'],
                    'manual_do'       => ['icon' => 'fa-file-pen',          'title' => 'Manual DO'],
                    'update_trip'     => ['icon' => 'fa-arrows-rotate',     'title' => 'Update Trip'],
                    'update_lokasi'   => ['icon' => 'fa-location-dot',      'title' => 'Update Lokasi'],
                    'sebaran'         => ['icon' => 'fa-map-location-dot',  'title' => 'Sebaran'],
                    'list_petugas'    => ['icon' => 'fa-users',             'title' => 'List Petugas'],
                    'list_operator'   => ['icon' => 'fa-building',          'title' => 'List Operator'],
                ];

                foreach ($menu_items as $key => $item) {
                    $activeClass = ($page == $key) ? 'active' : '';
                    echo "<div class='w-full flex justify-center items-center shrink-0'>
                            <a href='index.php?page={$key}' title='{$item['title']}' class='nav-btn {$activeClass} w-8 h-8 text-white hover:bg-white/15 flex items-center justify-center text-sm transition-colors duration-150 rounded-lg'>
                                <i class='fa-solid {$item['icon']}'></i>
                            </a>
                          </div>";
                }
                ?>
            </nav>

            <!-- FOOTER PROFIL USER & POPUP LOGOUT -->
            <div class="w-full flex justify-center items-center shrink-0 pt-1 relative z-50">
                
                <!-- Tombol Trigger Ikon User -->
                <button 
                    type="button"
                    onclick="toggleUserMenu(event)"
                    id="userMenuBtn"
                    title="<?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)"
                    class="w-8 h-8 rounded-full border border-blue-400/50 bg-blue-500/10 hover:bg-blue-600/30 active:scale-95 flex items-center justify-center text-white text-xs transition duration-200 focus:outline-none shadow-sm cursor-pointer"
                >
                    <i class="<?php echo $current_role_icon; ?> pointer-events-none"></i>
                </button>

                <!-- POPUP CARD PROFIL LOGOUT -->
                <div 
                    id="userMenuCard" 
                    class="hidden absolute left-16 bottom-0 w-[280px] bg-[#0a2569] border border-blue-900/80 rounded-2xl shadow-2xl p-5 z-[999] transition-all duration-200 flex flex-col gap-1.5"
                >
                    <!-- Role Badge User -->
                    <div class="flex items-center justify-between mb-1">
                        <span class="px-2.5 py-0.5 rounded-md bg-blue-500/20 text-blue-300 text-[10px] font-bold tracking-wider uppercase border border-blue-400/30">
                            <?php echo htmlspecialchars($user_role); ?>
                        </span>
                    </div>

                    <!-- Nama User Dinamis -->
                    <h3 class="text-lg font-extrabold text-white tracking-wide truncate">
                        <?php echo htmlspecialchars($user_name); ?>
                    </h3>
                    
                    <!-- Email User Dinamis -->
                    <p class="text-xs text-slate-200 font-normal opacity-90 truncate mb-3">
                        <?php echo htmlspecialchars($user_email); ?>
                    </p>
                    
                    <!-- Tombol Keluar (Logout) -->
                    <a 
                        href="logout.php" 
                        class="w-fit flex items-center gap-2 text-red-500 hover:text-red-400 font-semibold text-sm transition duration-150 group cursor-pointer"
                    >
                        <i class="fa-solid fa-arrow-right-from-bracket text-base transform group-hover:-translate-x-0.5 transition"></i>
                        <span>Keluar</span>
                    </a>
                </div>

            </div>

        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 flex flex-col min-w-0 bg-slate-950 h-full overflow-hidden">
            <header class="h-[80px] bg-slate-950 px-6 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-600/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-lg shadow-md">
                        <i class="<?php echo $current_config['icon']; ?>"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white tracking-wide"><?php echo $current_config['title']; ?></h2>
                        <p class="text-xs text-slate-400"></p>
                    </div>
                </div>
            </header>

            <div class="flex-1 p-5 flex flex-col gap-5 overflow-y-auto no-scrollbar min-h-0">
                <?php
                $filePath = "{$page}.php";

                if (file_exists($filePath)) {
                    include $filePath;
                } else {
                    echo "
                    <div class='flex-1 bg-slate-900/80 p-8 rounded-2xl border border-slate-800 text-center flex flex-col items-center justify-center min-h-[300px]'>
                        <div class='w-14 h-14 rounded-2xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-2xl mb-4 border border-blue-500/20'>
                            <i class='fa-solid fa-file-circle-plus'></i>
                        </div>
                        <h3 class='text-sm font-bold text-white uppercase tracking-wider mb-1'>Halaman {$current_config['title']}</h3>
                        <p class='text-xs text-slate-400 max-w-md mb-4'>
                            File belum ditemukan: <code class='text-blue-400 bg-slate-950 px-2 py-1 rounded border border-slate-800 font-mono'>{$filePath}</code>
                        </p>
                        <span class='text-[11px] text-slate-500'>Silakan buat file <strong>{$page}.php</strong> langsung di folder utama.</span>
                    </div>";
                }
                ?>
            </div>
        </main>

    </div>

    <!-- SCRIPT TOGGLE POPUP PROFIL -->
    <script>
        function toggleUserMenu(event) {
            if (event) event.stopPropagation();
            const menuCard = document.getElementById('userMenuCard');
            if (menuCard) {
                menuCard.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', function(event) {
            const btn = document.getElementById('userMenuBtn');
            const card = document.getElementById('userMenuCard');
            if (btn && card && !btn.contains(event.target) && !card.contains(event.target)) {
                card.classList.add('hidden');
            }
        });
    </script>
    <script src="script.js"></script>
</body>
</html>