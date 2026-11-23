<?php
$wilayah_selected = isset($_GET['wilayah']) ? $_GET['wilayah'] : '';
$periode_selected = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m-d');
?>

<div class="flex flex-col gap-5">
    <form method="GET" action="" class="bg-slate-900 p-4 rounded-2xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-lg border border-slate-800/80">
        <input type="hidden" name="page" value="dashboard">
        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2 bg-slate-950 px-3.5 py-2 rounded-xl text-xs border border-slate-800/80">
                <i class="fa-regular fa-calendar text-slate-400"></i>
                <label for="periode" class="text-slate-400">Periode:</label>
                <input type="date" name="periode" value="<?php echo $periode_selected; ?>" class="bg-transparent text-white focus:outline-none cursor-pointer">
            </div>

            <div class="flex items-center gap-2 bg-slate-950 px-3.5 py-2 rounded-xl text-xs border border-slate-800/80">
                <i class="fa-solid fa-map-pin text-slate-400"></i>
                <label for="wilayah" class="text-slate-400">Wilayah:</label>
                <select name="wilayah" class="bg-transparent text-white focus:outline-none cursor-pointer">
                    <option value="" class="bg-slate-900">Semua Wilayah</option>
                    <option value="Jakarta Pusat" class="bg-slate-900" <?php echo $wilayah_selected == 'Jakarta Pusat' ? 'selected' : ''; ?>>Jakarta Pusat</option>
                    <option value="Jakarta Utara" class="bg-slate-900" <?php echo $wilayah_selected == 'Jakarta Utara' ? 'selected' : ''; ?>>Jakarta Utara</option>
                    <option value="Jakarta Barat" class="bg-slate-900" <?php echo $wilayah_selected == 'Jakarta Barat' ? 'selected' : ''; ?>>Jakarta Barat</option>
                    <option value="Jakarta Selatan" class="bg-slate-900" <?php echo $wilayah_selected == 'Jakarta Selatan' ? 'selected' : ''; ?>>Jakarta Selatan</option>
                    <option value="Jakarta Timur" class="bg-slate-900" <?php echo $wilayah_selected == 'Jakarta Timur' ? 'selected' : ''; ?>>Jakarta Timur</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-semibold transition flex items-center gap-2 shadow-lg shadow-blue-600/20">
                <i class="fa-solid fa-filter"></i> Apply Filter
            </button>
        </div>

        <button type="button" onclick="exportData()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition flex items-center gap-2 border border-slate-700">
            <i class="fa-solid fa-file-excel text-emerald-400"></i> Export Summary
        </button>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-slate-900 rounded-2xl p-4.5 flex items-center justify-between shadow-md border border-slate-800/80">
            <div>
                <p class="text-xs text-slate-400 font-medium">Total Driving Order (DO)</p>
                <h3 class="text-2xl font-bold text-white mt-1">156 <span class="text-xs font-normal text-slate-400">DO</span></h3>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
        </div>

        <div class="bg-slate-900 rounded-2xl p-4.5 flex items-center justify-between shadow-md border border-slate-800/80">
            <div>
                <p class="text-xs text-slate-400 font-medium">Trip Berjalan (Aktif)</p>
                <h3 class="text-2xl font-bold text-emerald-400 mt-1">42 <span class="text-xs font-normal text-slate-400">Petugas</span></h3>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl">
                <i class="fa-solid fa-route"></i>
            </div>
        </div>

        <div class="bg-slate-900 rounded-2xl p-4.5 flex items-center justify-between shadow-md border border-slate-800/80">
            <div>
                <p class="text-xs text-slate-400 font-medium">Realisasi Target Trip</p>
                <h3 class="text-2xl font-bold text-amber-400 mt-1">94.8%</h3>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>

        <div class="bg-slate-900 rounded-2xl p-4.5 flex items-center justify-between shadow-md border border-slate-800/80">
            <div>
                <p class="text-xs text-slate-400 font-medium">Pengemudi On-Duty</p>
                <h3 class="text-2xl font-bold text-indigo-400 mt-1">88 <span class="text-xs font-normal text-slate-400">Orang</span></h3>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl">
                <i class="fa-solid fa-id-card"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="md:col-span-2 bg-slate-900 p-5 rounded-2xl flex flex-col justify-between shadow-md border border-slate-800/80">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Grafik Realisasi Trip Per Wilayah</h3>
                <span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-lg border border-emerald-500/20">Live API</span>
            </div>
            <div class="h-56">
                <canvas id="tripChart"></canvas>
            </div>
        </div>

        <div class="bg-slate-900 p-5 rounded-2xl flex flex-col justify-between shadow-md border border-slate-800/80">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Status Operasional DO</h3>
            </div>
            <div class="h-56 flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>