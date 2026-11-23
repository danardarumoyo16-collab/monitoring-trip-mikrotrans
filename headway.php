<?php
// Ambil variabel filter rute & periode tanggal dari GET
$rute_selected    = isset($_GET['rute']) ? $_GET['rute'] : 'JAK80';
$periode_selected = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m-d');

// Penentuan Status Tipe Trip (Single Trip vs Two-Way Trip)
$is_single_trip = true; // Set default Single Trip sesuai acuan rute JAK80
$trip_type_label = $is_single_trip ? "Single Trip" : "2 Arah / Two-Way Trip";

$tabel_kiri_data  = [];
$tabel_kanan_data = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db  = mysqli_real_escape_string($koneksi, $periode_selected);
    $rute_db = mysqli_real_escape_string($koneksi, $rute_selected);

    // Query Tabel Kiri (Arah Pergi / Looping Awal)
    $q_kiri = mysqli_query($koneksi, "SELECT do.tanggal, do.jam_berangkat, b.kode_bus, do.headway, do.type_trip FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db' AND (do.arah = 'PERGI' OR do.arah IS NULL) ORDER BY do.jam_berangkat ASC");
    if ($q_kiri) {
        while ($row = mysqli_fetch_assoc($q_kiri)) {
            $tabel_kiri_data[] = [
                'tanggal'   => date('d/m/Y', strtotime($row['tanggal'])),
                'berangkat' => date('H:i', strtotime($row['jam_berangkat'])),
                'nomor_bus' => $row['kode_bus'],
                'hdway'     => $row['headway'],
                'type'      => $row['type_trip'] ?: 1
            ];
        }
    }

    // Query Tabel Kanan (Arah Pulang / Tujuan Kedua)
    $q_kanan = mysqli_query($koneksi, "SELECT do.tanggal, do.jam_berangkat, do.jam_tiba, b.kode_bus, do.waktu_tempuh, do.headway, do.type_trip FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db' AND do.arah = 'PULANG' ORDER BY do.jam_berangkat ASC");
    if ($q_kanan) {
        while ($row = mysqli_fetch_assoc($q_kanan)) {
            $tabel_kanan_data[] = [
                'tanggal'   => date('d/m/Y', strtotime($row['tanggal'])),
                'berangkat' => date('H:i', strtotime($row['jam_berangkat'])),
                'tiba'      => $row['jam_tiba'] ? date('H:i', strtotime($row['jam_tiba'])) : '',
                'nomor_bus' => $row['kode_bus'],
                'tempuh'    => $row['waktu_tempuh'],
                'hdway'     => $row['headway'],
                'type'      => $row['type_trip'] ?: 1
            ];
        }
    }
}

// Fallback Data jika Database offline / kosong
if (empty($tabel_kiri_data)) {
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));
    
    $tabel_kiri_data = [
        ['tanggal' => $tgl_fmt, 'berangkat' => '07:35', 'nomor_bus' => 'KWK-220001', 'hdway' => 11, 'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '07:46', 'nomor_bus' => 'KWK-220002', 'hdway' => 9,  'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '07:55', 'nomor_bus' => 'KWK-220003', 'hdway' => 36, 'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '08:31', 'nomor_bus' => 'KWK-220004', 'hdway' => 9,  'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '08:40', 'nomor_bus' => 'KWK-220005', 'hdway' => 8,  'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '08:48', 'nomor_bus' => 'KWK-220006', 'hdway' => 12, 'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '09:00', 'nomor_bus' => 'KWK-220007', 'hdway' => 15, 'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '09:15', 'nomor_bus' => 'KWK-220008', 'hdway' => 16, 'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '09:31', 'nomor_bus' => 'KWK-220009', 'hdway' => 15, 'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '09:46', 'nomor_bus' => 'KWK-220010', 'hdway' => 15, 'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '10:01', 'nomor_bus' => 'KWK-220011', 'hdway' => 16, 'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '10:17', 'nomor_bus' => 'KWK-220012', 'hdway' => 14, 'type' => 1],
        ['tanggal' => $tgl_fmt, 'berangkat' => '10:31', 'nomor_bus' => 'KWK-220013', 'hdway' => 15, 'type' => 1],
    ];

    // Jika single trip, tabel kanan tetap kosong atau berisi data trip 2 arah jika diaktifkan
    $tabel_kanan_data = [];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriodeHeadway" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="headway">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodeHeadway').submit()" 
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
                    form="formPeriodeHeadway"
                    onchange="document.getElementById('formPeriodeHeadway').submit()"
                    class="bg-slate-950/80 text-slate-200 border border-slate-800 hover:border-slate-700 focus:border-blue-500 px-3 py-1 rounded-lg font-medium focus:outline-none cursor-pointer text-xs transition shadow-inner"
                >
                    <option value="JAK80" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK80' ? 'selected' : ''; ?>>JAK80 RAWA BUAYA - RAWA KOMPENI</option>
                    <option value="JAK01" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK01' ? 'selected' : ''; ?>>JAK01 TANJUNG PRIOK - PLUMPANG</option>
                    <option value="JAK02" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK02' ? 'selected' : ''; ?>>JAK02 KAMPUNG RAMBUTAN - PASAR MINGGU</option>
                    <option value="JAK10" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK10' ? 'selected' : ''; ?>>JAK10 TANAH ABANG - KEBAYORAN LAMA</option>
                </select>
                <span class="font-bold text-white uppercase ml-1"><?php echo htmlspecialchars($rute_selected); ?></span>
            </div>

            <button type="button" onclick="document.getElementById('formPeriodeHeadway').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                cek
            </button>

            <!-- Status Tipe Trip -->
            <span class="text-slate-200 font-medium bg-slate-950/60 px-3 py-1 rounded-lg border border-slate-800/80 ml-2">
                <?php echo $trip_type_label; ?>
            </span>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportHeadwayCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- MAIN CONTENT 2 TABEL DUA ARAH / SINGLE TRIP -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- TABEL KIRI (KAMPUNG/LOKASI PERGI - CONTOH: JAK80_RWBUAYA) -->
        <div class="lg:col-span-6 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="p-2 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                <span class="text-xs font-bold text-white tracking-wide"><?php echo htmlspecialchars($rute_selected); ?>_RWBUAYA</span>
                <span class="text-[10px] bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded font-mono">Arah 1</span>
            </div>
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Berangkat</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2 text-right">Hdway</th>
                            <th class="px-2.5 py-2 text-center">Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kiri_data as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['berangkat']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold"><?php echo $row['nomor_bus']; ?></td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-200"><?php echo $row['hdway']; ?></td>
                            <td class="px-2.5 py-1.5 text-center font-mono text-slate-300"><?php echo $row['type']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL KANAN (KAMPUNG/LOKASI PULANG - CONTOH: JAK80_KOMPENI) -->
        <div class="lg:col-span-6 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="p-2 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                <span class="text-xs font-bold text-white tracking-wide"><?php echo htmlspecialchars($rute_selected); ?>_KOMPENI</span>
                <span class="text-[10px] bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded font-mono">Arah 2</span>
            </div>
            <div class="flex-1 overflow-auto no-scrollbar">
                <?php if (!empty($tabel_kanan_data)): ?>
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Berangkat</th>
                            <th class="px-2.5 py-2">Tiba</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2 text-right">Tempuh</th>
                            <th class="px-2.5 py-2 text-right">Hdway</th>
                            <th class="px-2.5 py-2 text-center">Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kanan_data as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['berangkat']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['tiba']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold"><?php echo $row['nomor_bus']; ?></td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-300"><?php echo $row['tempuh']; ?></td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-200"><?php echo $row['hdway']; ?></td>
                            <td class="px-2.5 py-1.5 text-center font-mono text-slate-300"><?php echo $row['type']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <!-- TAMPILAN JIKA TRIP MODE SINGLE TRIP / KANAN KOSONG -->
                <div class="flex-1 flex flex-col items-center justify-center p-6 text-center text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 8v4l3 3"></path>
                    </svg>
                    <span class="text-xs font-medium text-slate-400">Single Trip (1 Pengendapan / Looping)</span>
                    <span class="text-[11px] text-slate-600 mt-1">Data trip terpusat di tabel utama sebelah kiri.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- FOOTER STATUS BAR REALTIME -->
    <div class="bg-slate-900/90 px-4 py-1.5 rounded-xl flex justify-between items-center text-[11px] font-mono border border-slate-800 shrink-0 text-slate-400">
        <div class="flex items-center gap-3">
            <span id="liveTimestampHeadway" class="text-blue-400 font-semibold">--</span>
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

<!-- SCRIPT LIVE CLOCK & EXPORT CSV HEADWAY -->
<script>
function updateLiveTimestampHeadway() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampHeadway');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampHeadway, 1000);
updateLiveTimestampHeadway();

function exportHeadwayCSV() {
    let csv = [];
    csv.push("--- REKAP HEADWAY ---");
    csv.push("Rute," + "<?php echo $rute_selected; ?>");
    csv.push("Periode," + "<?php echo $periode_selected; ?>");
    csv.push("Tipe Trip," + "<?php echo $trip_type_label; ?>");
    csv.push("");
    
    csv.push("--- TABEL ARAH 1 ---");
    csv.push("TANGGAL,BERANGKAT,NOMOR BUS,HDWAY,TYPE");
    <?php foreach ($tabel_kiri_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    <?php if (!empty($tabel_kanan_data)): ?>
    csv.push("");
    csv.push("--- TABEL ARAH 2 ---");
    csv.push("TANGGAL,BERANGKAT,TIBA,NOMOR BUS,TEMPUH,HDWAY,TYPE");
    <?php foreach ($tabel_kanan_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>
    <?php endif; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "Headway_<?php echo $rute_selected; ?>_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>