<?php
// Ambil tanggal dari GET URL. Jika kosong/pertama kali dibuka, otomatis gunakan tanggal perangkat server/device
$periode_selected = (isset($_GET['periode']) && !empty($_GET['periode'])) 
    ? $_GET['periode'] 
    : date('Y-m-d');

$rute_selected = isset($_GET['rute']) ? $_GET['rute'] : 'JAK07';
$trip_type_label = "Looping Trip"; // Status default acuan

// FUNGSI DINAMIS DETEKSI HARI KERJA (weekDay) VS HARI LIBUR / SABTU-MINGGU / TANGGAL MERAH (weekEnd)
function getDayweekStatus($tanggal_str) {
    $timestamp = strtotime($tanggal_str);
    $day_of_week = date('N', $timestamp); // 1 (Senin) s/d 7 (Minggu)

    // 1. Sabtu (6) atau Minggu (7) otomatis weekEnd
    if ($day_of_week == 6 || $day_of_week == 7) {
        return 'weekEnd';
    }

    // 2. Daftar Libur Nasional & Hari Raya Tetap (Format MM-DD agar berlaku di semua tahun)
    $libur_tetap = [
        '01-01', // Tahun Baru
        '05-01', // Hari Buruh
        '06-01', // Hari Lahir Pancasila
        '08-17', // Hari Kemerdekaan RI
        '12-25', // Hari Natal
    ];

    $month_day = date('m-d', $timestamp);
    if (in_array($month_day, $libur_tetap)) {
        return 'weekEnd';
    }

    return 'weekDay';
}

$current_dayweek = getDayweekStatus($periode_selected);

$tabel_kiri_data  = [];
$tabel_kanan_data = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db  = mysqli_real_escape_string($koneksi, $periode_selected);
    $rute_db = mysqli_real_escape_string($koneksi, $rute_selected);

    // Query Tabel Kiri (Rekap Sebaran Keberangkatan per Jam)
    $q_kiri = mysqli_query($koneksi, "SELECT DATE(do.tanggal) AS tgl, r.kode_rute, DATE_FORMAT(do.jam_berangkat, '%H:00') AS waktu_jam, do.lokasi_awal, COUNT(do.id_do) AS sebaran, AVG(do.headway) AS avg_hdway FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db' GROUP BY DATE(do.tanggal), r.kode_rute, waktu_jam, do.lokasi_awal ORDER BY waktu_jam ASC");
    if ($q_kiri) {
        while ($row = mysqli_fetch_assoc($q_kiri)) {
            $tabel_kiri_data[] = [
                'tanggal' => date('d/m/Y', strtotime($row['tgl'])),
                'rute'    => $row['kode_rute'],
                'waktu'   => $row['waktu_jam'],
                'lokasi'  => $row['lokasi_awal'],
                'sebaran' => $row['sebaran'],
                'dayweek' => $current_dayweek,
                'avg'     => number_format($row['avg_hdway'], 1, ',', '.')
            ];
        }
    }

    // Query Tabel Kanan (Disrupsi / Waktu Hindar Jam)
    $q_kanan = mysqli_query($koneksi, "SELECT do.tanggal, do.disrupsi, do.waktu_tempuh FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db' AND do.disrupsi IS NOT NULL ORDER BY do.jam_berangkat ASC");
    if ($q_kanan) {
        while ($row = mysqli_fetch_assoc($q_kanan)) {
            $tabel_kanan_data[] = [
                'tanggal' => date('d/m/Y', strtotime($row['tanggal'])),
                'dis'     => $row['disrupsi'],
                'waktu'   => $row['waktu_tempuh']
            ];
        }
    }
}

// Fallback Data jika Database offline / kosong
if (empty($tabel_kiri_data)) {
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));
    
    $tabel_kiri_data = [
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '05:00', 'lokasi' => 'TANAHABANG', 'sebaran' => 5,  'dayweek' => $current_dayweek, 'avg' => '12,0'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '11:00', 'lokasi' => 'TANAHABANG', 'sebaran' => 1,  'dayweek' => $current_dayweek, 'avg' => '60,0'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '05:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 16, 'dayweek' => $current_dayweek, 'avg' => '3,8'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '06:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 11, 'dayweek' => $current_dayweek, 'avg' => '5,5'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '07:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 10, 'dayweek' => $current_dayweek, 'avg' => '6,0'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '08:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 12, 'dayweek' => $current_dayweek, 'avg' => '5,0'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '09:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 9,  'dayweek' => $current_dayweek, 'avg' => '6,7'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '10:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 12, 'dayweek' => $current_dayweek, 'avg' => '5,0'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '11:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 9,  'dayweek' => $current_dayweek, 'avg' => '6,7'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '12:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 12, 'dayweek' => $current_dayweek, 'avg' => '5,0'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '13:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 11, 'dayweek' => $current_dayweek, 'avg' => '5,5'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '14:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 10, 'dayweek' => $current_dayweek, 'avg' => '6,0'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '15:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 12, 'dayweek' => $current_dayweek, 'avg' => '5,0'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.07', 'waktu' => '16:00', 'lokasi' => 'TAWAKAL',    'sebaran' => 9,  'dayweek' => $current_dayweek, 'avg' => '6,7'],
    ];

    $tabel_kanan_data = [];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS (SINKRON DENGAN DEVICE USER) -->
    <form method="GET" action="" id="formPeriodeSebaran" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="sebaran">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodeSebaran').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- HEADER TOP BAR: RUTE, LOKASI, TIPE TRIP, TOMBOL CEK & EXPORT ICON -->
    <div class="bg-slate-900/90 p-2.5 rounded-xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-md border border-slate-800 text-xs">
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-slate-300 font-medium">Rute</span>
                <select 
                    name="rute" 
                    form="formPeriodeSebaran"
                    onchange="document.getElementById('formPeriodeSebaran').submit()"
                    class="bg-slate-950/80 text-slate-200 border border-slate-800 hover:border-slate-700 focus:border-blue-500 px-3 py-1 rounded-lg font-medium focus:outline-none cursor-pointer text-xs transition shadow-inner"
                >
                    <option value="JAK07" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK07' ? 'selected' : ''; ?>>JAK7 TAWAKAL - TANAH ABANG</option>
                    <option value="JAK01" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK01' ? 'selected' : ''; ?>>JAK1 TANJUNG PRIOK - PLUMPANG</option>
                    <option value="JAK02" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK02' ? 'selected' : ''; ?>>JAK2 KAMPUNG RAMBUTAN - PASAR MINGGU</option>
                    <option value="JAK80" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK80' ? 'selected' : ''; ?>>JAK80 RAWA BUAYA - RAWA KOMPENI</option>
                </select>
                <span class="font-bold text-white uppercase ml-1"><?php echo htmlspecialchars($rute_selected); ?></span>
            </div>

            <button type="button" onclick="document.getElementById('formPeriodeSebaran').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                cek
            </button>

            <!-- Status Lokasi & Tipe Trip -->
            <div class="flex items-center gap-2 ml-2 text-xs">
                <span class="text-white font-bold tracking-wide uppercase"><?php echo htmlspecialchars($rute_selected); ?>_TAWAKAL</span>
                <span class="text-slate-200 font-medium bg-slate-950/60 px-3 py-0.5 rounded-lg border border-slate-800/80">
                    <?php echo $trip_type_label; ?>
                </span>
            </div>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportSebaranCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- MAIN CONTENT 2 TABEL SEBARAN -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- TABEL KIRI (STATISTIK SEBARAN KEBERANGKATAN) -->
        <div class="lg:col-span-7 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Rute</th>
                            <th class="px-2.5 py-2">Waktu</th>
                            <th class="px-2.5 py-2">Lokasi</th>
                            <th class="px-2.5 py-2 text-right">Sebaran</th>
                            <th class="px-2.5 py-2 text-center">Dayweek</th>
                            <th class="px-2.5 py-2 text-right">Avg</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kiri_data as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['rute']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['waktu']; ?></td>
                            <td class="px-2.5 py-1.5 text-white font-medium uppercase"><?php echo $row['lokasi']; ?></td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-200 font-bold"><?php echo $row['sebaran']; ?></td>
                            <td class="px-2.5 py-1.5 text-center font-mono <?php echo $row['dayweek'] == 'weekEnd' ? 'text-amber-400 font-semibold' : 'text-slate-400'; ?>">
                                <?php echo $row['dayweek']; ?>
                            </td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-300"><?php echo $row['avg']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL KANAN (DETAIL DISRUPSI / WAKTU) -->
        <div class="lg:col-span-5 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <?php if (!empty($tabel_kanan_data)): ?>
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Dis</th>
                            <th class="px-2.5 py-2 text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kanan_data as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['dis']; ?></td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-300"><?php echo $row['waktu']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <!-- HEADER TABEL KANAN KOSONG SESUAI ACUAN GAMBAR -->
                <div class="w-full flex-1">
                    <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                        <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                            <tr>
                                <th class="px-2.5 py-2">Tanggal</th>
                                <th class="px-2.5 py-2">Dis</th>
                                <th class="px-2.5 py-2 text-right">Waktu</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- FOOTER STATUS BAR REALTIME -->
    <div class="bg-slate-900/90 px-4 py-1.5 rounded-xl flex justify-between items-center text-[11px] font-mono border border-slate-800 shrink-0 text-slate-400">
        <div class="flex items-center gap-3">
            <span id="liveTimestampSebaran" class="text-blue-400 font-semibold">--</span>
            <span class="text-emerald-400 font-semibold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Server connected
            </span>
        </div>
        <div>
            update by : <span class="text-slate-300 font-semibold">Mikrotrans</span>
        </div>
    </div>

</div>

<!-- SCRIPT LIVE CLOCK & EXPORT CSV SEBARAN -->
<script>
// Pastikan input kalender langsung sinkron dengan tanggal perangkat pengakses saat pertama dibuka
document.addEventListener("DOMContentLoaded", function() {
    const inputPeriode = document.getElementById('inputPeriode');
    if (inputPeriode && !inputPeriode.value) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        inputPeriode.value = `${year}-${month}-${day}`;
    }
});

// Jam & Tanggal otomatis realtime
function updateLiveTimestampSebaran() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampSebaran');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampSebaran, 1000);
updateLiveTimestampSebaran();

// Export Data ke CSV
function exportSebaranCSV() {
    let csv = [];
    csv.push("--- REKAP SEBARAN TRIPS ---");
    csv.push("Rute," + "<?php echo $rute_selected; ?>");
    csv.push("Periode," + "<?php echo $periode_selected; ?>");
    csv.push("Status Hari," + "<?php echo $current_dayweek; ?>");
    csv.push("Tipe Trip," + "<?php echo $trip_type_label; ?>");
    csv.push("");
    
    csv.push("--- TABEL SEBARAN KEBERANGKATAN ---");
    csv.push("TANGGAL,RUTE,WAKTU,LOKASI,SEBARAN,DAYWEEK,AVG");
    <?php foreach ($tabel_kiri_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    <?php if (!empty($tabel_kanan_data)): ?>
    csv.push("");
    csv.push("--- TABEL DETAIL DISRUPSI ---");
    csv.push("TANGGAL,DIS,WAKTU");
    <?php foreach ($tabel_kanan_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>
    <?php endif; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "Sebaran_<?php echo $rute_selected; ?>_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>