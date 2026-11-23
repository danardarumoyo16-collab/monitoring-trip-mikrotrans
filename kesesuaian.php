<?php
// Tanggal dinamis dari URL atau sinkron perangkat user
$periode_selected = (isset($_GET['periode']) && !empty($_GET['periode'])) 
    ? $_GET['periode'] 
    : date('Y-m-d');

$rute_selected   = isset($_GET['rute']) ? $_GET['rute'] : 'JAK1';
$trip_type_label = "Looping Trip"; // Status default acuan

$tabel_kiri_data  = [];
$tabel_kanan_data = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db  = mysqli_real_escape_string($koneksi, $periode_selected);
    $rute_db = mysqli_real_escape_string($koneksi, $rute_selected);

    // Query Tabel Kiri (Arah 1)
    $q_kiri = mysqli_query($koneksi, "SELECT do.tanggal, b.kode_bus, do.jam_berangkat, do.created_at, do.time_diff, u.username FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus LEFT JOIN users u ON do.id_user = u.id_user WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db' AND (do.arah = 'PERGI' OR do.arah IS NULL) ORDER BY do.jam_berangkat ASC");
    if ($q_kiri) {
        while ($row = mysqli_fetch_assoc($q_kiri)) {
            $tabel_kiri_data[] = [
                'tanggal'   => date('d/m/Y', strtotime($row['tanggal'])),
                'nomor_bus' => $row['kode_bus'],
                'berangkat' => date('H:i', strtotime($row['jam_berangkat'])),
                'dibuat'    => $row['created_at'] ? date('H:i', strtotime($row['created_at'])) : '00:00',
                'time_diff' => $row['time_diff'] ?: 0,
                'user'      => $row['username'] ?: '100948,SLAMAT...'
            ];
        }
    }

    // Query Tabel Kanan (Arah 2)
    $q_kanan = mysqli_query($koneksi, "SELECT do.tanggal, b.kode_bus, do.jam_berangkat, do.created_at, do.time_diff, u.username FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus LEFT JOIN users u ON do.id_user = u.id_user WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db' AND do.arah = 'PULANG' ORDER BY do.jam_berangkat ASC");
    if ($q_kanan) {
        while ($row = mysqli_fetch_assoc($q_kanan)) {
            $tabel_kanan_data[] = [
                'tanggal'   => date('d/m/Y', strtotime($row['tanggal'])),
                'nomor_bus' => $row['kode_bus'],
                'berangkat' => date('H:i', strtotime($row['jam_berangkat'])),
                'dibuat'    => $row['created_at'] ? date('H:i', strtotime($row['created_at'])) : '00:00',
                'time_diff' => $row['time_diff'] ?: 0,
                'user'      => $row['username'] ?: '102437,SAPTO N...'
            ];
        }
    }
}

// Fallback Data jika Database offline / kosong
if (empty($tabel_kiri_data)) {
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));
    
    $tabel_kiri_data = [
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-541',  'berangkat' => '05:00', 'dibuat' => '05:00', 'time_diff' => 0, 'user' => '100948,SLAMAT...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-558',  'berangkat' => '05:02', 'dibuat' => '05:02', 'time_diff' => 0, 'user' => '100948,SLAMAT...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-568',  'berangkat' => '05:04', 'dibuat' => '05:04', 'time_diff' => 0, 'user' => '100948,SLAMAT...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-540',  'berangkat' => '05:07', 'dibuat' => '05:07', 'time_diff' => 0, 'user' => '100948,SLAMAT...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-552',  'berangkat' => '05:11', 'dibuat' => '05:11', 'time_diff' => 0, 'user' => '100948,SLAMAT...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-557',  'berangkat' => '05:16', 'dibuat' => '05:16', 'time_diff' => 0, 'user' => '100948,SLAMAT...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-553',  'berangkat' => '05:18', 'dibuat' => '05:18', 'time_diff' => 0, 'user' => '100948,SLAMAT...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-567',  'berangkat' => '05:21', 'dibuat' => '05:21', 'time_diff' => 0, 'user' => '100948,SLAMAT...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-565',  'berangkat' => '05:24', 'dibuat' => '05:24', 'time_diff' => 0, 'user' => '100948,SLAMAT...'],
    ];

    $tabel_kanan_data = [
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-551',  'berangkat' => '05:00', 'dibuat' => '00:00', 'time_diff' => 300, 'user' => '102437,SAPTO N...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-559',  'berangkat' => '05:05', 'dibuat' => '00:00', 'time_diff' => 305, 'user' => '102437,SAPTO N...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-543',  'berangkat' => '05:10', 'dibuat' => '00:00', 'time_diff' => 310, 'user' => '102437,SAPTO N...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-544',  'berangkat' => '05:15', 'dibuat' => '00:00', 'time_diff' => 315, 'user' => '102437,SAPTO N...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-545',  'berangkat' => '05:20', 'dibuat' => '00:00', 'time_diff' => 320, 'user' => '102437,SAPTO N...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-555',  'berangkat' => '05:25', 'dibuat' => '00:00', 'time_diff' => 325, 'user' => '102437,SAPTO N...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-542',  'berangkat' => '05:30', 'dibuat' => '00:00', 'time_diff' => 330, 'user' => '102437,SAPTO N...'],
        ['tanggal' => $tgl_fmt, 'nomor_bus' => 'KLM-2056', 'berangkat' => '05:35', 'dibuat' => '00:00', 'time_diff' => 335, 'user' => '102437,SAPTO N...'],
    ];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriodeKesesuaian" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="kesesuaian">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodeKesesuaian').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- HEADER TOP BAR: RUTE LAYANAN, TIPE TRIP, TOMBOL CEK & EXPORT ICON -->
    <div class="bg-slate-900/90 p-2.5 rounded-xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-md border border-slate-800 text-xs">
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-slate-300 font-medium">Rute Layanan</span>
                <select 
                    name="rute" 
                    form="formPeriodeKesesuaian"
                    onchange="document.getElementById('formPeriodeKesesuaian').submit()"
                    class="bg-slate-950/80 text-slate-200 border border-slate-800 hover:border-slate-700 focus:border-blue-500 px-3 py-1 rounded-lg font-medium focus:outline-none cursor-pointer text-xs transition shadow-inner"
                >
                    <option value="JAK1" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK1' ? 'selected' : ''; ?>>JAK1 TANJUNG PRIOK - PLUMPANG</option>
                    <option value="JAK80" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK80' ? 'selected' : ''; ?>>JAK80 RAWA BUAYA - RAWA KOMPENI</option>
                    <option value="JAK02" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK02' ? 'selected' : ''; ?>>JAK02 KAMPUNG RAMBUTAN - PASAR MINGGU</option>
                    <option value="JAK10" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK10' ? 'selected' : ''; ?>>JAK10 TANAH ABANG - KEBAYORAN LAMA</option>
                </select>
                <span class="font-bold text-white uppercase ml-1"><?php echo htmlspecialchars($rute_selected); ?></span>
            </div>

            <button type="button" onclick="document.getElementById('formPeriodeKesesuaian').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                cek
            </button>

            <!-- Status Tipe Trip -->
            <span class="text-slate-200 font-medium bg-slate-950/60 px-3 py-1 rounded-lg border border-slate-800/80 ml-2">
                <?php echo $trip_type_label; ?>
            </span>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportKesesuaianCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- MAIN CONTENT 2 TABEL KESESUAIAN -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- TABEL KIRI (JAK1_TJPRIOK) -->
        <div class="lg:col-span-6 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="p-2 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                <span class="text-xs font-bold text-white tracking-wide"><?php echo htmlspecialchars($rute_selected); ?>_TJPRIOK</span>
                <span class="text-[10px] bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded font-mono">Arah 1</span>
            </div>
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2">Berangkat</th>
                            <th class="px-2.5 py-2">Dibuat</th>
                            <th class="px-2.5 py-2 text-center">Time Diff</th>
                            <th class="px-2.5 py-2">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kiri_data as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold"><?php echo $row['nomor_bus']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['berangkat']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['dibuat']; ?></td>
                            <td class="px-2.5 py-1.5 text-center font-mono">
                                <?php if ($row['time_diff'] > 0): ?>
                                    <span class="bg-red-500/20 text-red-400 border border-red-500/30 px-2.5 py-0.5 rounded-full font-semibold inline-block">
                                        <?php echo $row['time_diff']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400"><?php echo $row['time_diff']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2.5 py-1.5 text-slate-400"><?php echo $row['user']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL KANAN (JAK1_PLUMPANG) -->
        <div class="lg:col-span-6 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="p-2 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                <span class="text-xs font-bold text-white tracking-wide"><?php echo htmlspecialchars($rute_selected); ?>_PLUMPANG</span>
                <span class="text-[10px] bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded font-mono">Arah 2</span>
            </div>
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2">Berangkat</th>
                            <th class="px-2.5 py-2">Dibuat</th>
                            <th class="px-2.5 py-2 text-center">Time Diff</th>
                            <th class="px-2.5 py-2">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kanan_data as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold"><?php echo $row['nomor_bus']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['berangkat']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['dibuat']; ?></td>
                            <td class="px-2.5 py-1.5 text-center font-mono">
                                <?php if ($row['time_diff'] > 0): ?>
                                    <!-- BADGE MERAH SMOOTH CARD SOFT -->
                                    <span class="bg-red-500/20 text-red-400 border border-red-500/30 px-2.5 py-0.5 rounded-full font-semibold inline-block">
                                        <?php echo $row['time_diff']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400"><?php echo $row['time_diff']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2.5 py-1.5 text-slate-400"><?php echo $row['user']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- FOOTER STATUS BAR REALTIME -->
    <div class="bg-slate-900/90 px-4 py-1.5 rounded-xl flex justify-between items-center text-[11px] font-mono border border-slate-800 shrink-0 text-slate-400">
        <div class="flex items-center gap-3">
            <span id="liveTimestampKesesuaian" class="text-blue-400 font-semibold">--</span>
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

<!-- SCRIPT LIVE CLOCK & EXPORT CSV KESESUAIAN -->
<script>
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

function updateLiveTimestampKesesuaian() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampKesesuaian');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampKesesuaian, 1000);
updateLiveTimestampKesesuaian();

function exportKesesuaianCSV() {
    let csv = [];
    csv.push("--- REKAP KESESUAIAN TRIP ---");
    csv.push("Rute," + "<?php echo $rute_selected; ?>");
    csv.push("Periode," + "<?php echo $periode_selected; ?>");
    csv.push("Tipe Trip," + "<?php echo $trip_type_label; ?>");
    csv.push("");
    
    csv.push("--- TABEL ARAH 1 ---");
    csv.push("TANGGAL,NOMOR BUS,BERANGKAT,DIBUAT,TIME DIFF,USER");
    <?php foreach ($tabel_kiri_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("--- TABEL ARAH 2 ---");
    csv.push("TANGGAL,NOMOR BUS,BERANGKAT,DIBUAT,TIME DIFF,USER");
    <?php foreach ($tabel_kanan_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "Kesesuaian_<?php echo $rute_selected; ?>_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>