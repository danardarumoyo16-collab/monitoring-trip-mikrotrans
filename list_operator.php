<?php
// Ambil variabel filter operator & periode tanggal dari GET
$operator_selected = isset($_GET['operator']) ? $_GET['operator'] : 'KOPERASI WAHANA KALPIKA';
$periode_selected  = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m-d');

// Peta Singkatan Operator
$operator_code_map = [
    'BUDI LUHUR'               => 'BDL',
    'KENCANA SAKTI'            => 'KST',
    'KOLAMAS JAYA'             => 'KLM',
    'KOMIKA JAYA'              => 'KMJ',
    'KOMILET JAYA'             => 'KMJ',
    'KOPAMILET JAYA'           => 'KPM',
    'KOPERASI WAHANA KALPIKA'  => 'KWK',
    'LESTARISURYA GEMAPERSADA' => 'LSG',
    'PURIMAS JAYA'             => 'PRM',
    'PUSKOPAU'                 => 'PKP'
];

$kode_op = isset($operator_code_map[$operator_selected]) ? $operator_code_map[$operator_selected] : 'KWK';

$jumlah_bus_operasi = 0;
$tabel_kiri_atas  = [];
$tabel_kiri_bawah = [];
$tabel_kanan_data = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db = mysqli_real_escape_string($koneksi, $periode_selected);
    $op_db  = mysqli_real_escape_string($koneksi, $operator_selected);

    $where_op = $op_db ? "AND o.nama_operator = '$op_db'" : "";

    // 1. Hitung Jumlah Bus Operasi Unik
    $q_stat = mysqli_query($koneksi, "SELECT COUNT(DISTINCT do.id_bus) AS total_bus FROM driving_order do JOIN operator o ON do.id_operator = o.id_operator WHERE DATE(do.tanggal) = '$tgl_db' $where_op");
    if ($q_stat && $r_stat = mysqli_fetch_assoc($q_stat)) {
        $jumlah_bus_operasi = $r_stat['total_bus'] ?: 0;
    }

    // 2. Query Tabel Kiri Atas (Rekap Jumlah Bus per Rute)
    $q_t1 = mysqli_query($koneksi, "SELECT DATE(do.tanggal) AS tgl, r.kode_rute, COUNT(DISTINCT do.id_bus) AS jumlah FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN operator o ON do.id_operator = o.id_operator WHERE DATE(do.tanggal) = '$tgl_db' $where_op GROUP BY DATE(do.tanggal), r.kode_rute ORDER BY r.kode_rute ASC");
    if ($q_t1) {
        while ($row = mysqli_fetch_assoc($q_t1)) {
            $tabel_kiri_atas[] = [
                'tanggal' => date('d/m/Y', strtotime($row['tgl'])),
                'rute'    => $row['kode_rute'],
                'jumlah'  => $row['jumlah']
            ];
        }
    }

    // 3. Query Tabel Kiri Bawah (Detail List Bus per Rute)
    $q_t2 = mysqli_query($koneksi, "SELECT DISTINCT DATE(do.tanggal) AS tgl, r.kode_rute, b.kode_bus FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus JOIN operator o ON do.id_operator = o.id_operator WHERE DATE(do.tanggal) = '$tgl_db' $where_op ORDER BY r.kode_rute ASC, b.kode_bus ASC");
    if ($q_t2) {
        while ($row = mysqli_fetch_assoc($q_t2)) {
            $tabel_kiri_bawah[] = [
                'tanggal'   => date('d/m/Y', strtotime($row['tgl'])),
                'rute'      => $row['kode_rute'],
                'nomor_bus' => $row['kode_bus']
            ];
        }
    }

    // 4. Query Tabel Kanan (Detail Log Trip Operator)
    $q_kanan = mysqli_query($koneksi, "SELECT do.tanggal, do.jam_mulai, b.kode_bus, p.nama_pramudi, do.odometer, r.kode_rute, r.nama_layanan, pt.nama_petugas FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus JOIN operator o ON do.id_operator = o.id_operator JOIN pramudi p ON do.id_pramudi = p.id_pramudi JOIN petugas pt ON do.id_petugas = pt.id_petugas WHERE DATE(do.tanggal) = '$tgl_db' $where_op ORDER BY do.jam_mulai DESC");
    if ($q_kanan) {
        while ($row = mysqli_fetch_assoc($q_kanan)) {
            $tabel_kanan_data[] = [
                'tanggal'      => date('d/m/Y', strtotime($row['tanggal'])),
                'mulai'        => date('H:i', strtotime($row['jam_mulai'])),
                'nomor_bus'    => $row['kode_bus'],
                'pramudi'      => $row['nama_pramudi'],
                'odometer'     => $row['odometer'],
                'rute'         => $row['kode_rute'],
                'rute_layanan' => $row['nama_layanan'],
                'petugas'      => $row['nama_petugas']
            ];
        }
    }
}

// Fallback Data jika Database offline / kosong
if (empty($tabel_kiri_atas)) {
    $jumlah_bus_operasi = 1473;
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));
    
    $tabel_kiri_atas = [
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.113', 'jumlah' => 27],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.115', 'jumlah' => 29],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.117', 'jumlah' => 33],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.118', 'jumlah' => 28],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.120', 'jumlah' => 13],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.15',  'jumlah' => 56],
    ];

    $tabel_kiri_bawah = [
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.03', 'nomor_bus' => 'KWK-039'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.03', 'nomor_bus' => 'KWK-040'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.03', 'nomor_bus' => 'KWK-041'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.03', 'nomor_bus' => 'KWK-042'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.03', 'nomor_bus' => 'KWK-043'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.03', 'nomor_bus' => 'KWK-044'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.04', 'nomor_bus' => 'KWK-034'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.04', 'nomor_bus' => 'KWK-035'],
    ];

    $tabel_kanan_data = [];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriodeOperator" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="list_operator">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodeOperator').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- HEADER TOP BAR: DROPDOWN OPERATOR, KODE, CEK, JUMLAH BUS OPERASI & EXPORT ICON -->
    <div class="bg-slate-900/90 p-2.5 rounded-xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-md border border-slate-800 text-xs">
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-slate-300 font-medium">Operator</span>
                <select 
                    name="operator" 
                    form="formPeriodeOperator"
                    onchange="document.getElementById('formPeriodeOperator').submit()"
                    class="bg-slate-950/80 text-slate-200 border border-slate-800 hover:border-slate-700 focus:border-blue-500 px-3 py-1 rounded-lg font-medium focus:outline-none cursor-pointer uppercase shadow-inner text-xs transition min-w-[210px]"
                >
                    <?php
                    $operators = [
                        'BUDI LUHUR',
                        'KENCANA SAKTI',
                        'KOLAMAS JAYA',
                        'KOMIKA JAYA',
                        'KOMILET JAYA',
                        'KOPAMILET JAYA',
                        'KOPERASI WAHANA KALPIKA',
                        'LESTARISURYA GEMAPERSADA',
                        'PURIMAS JAYA',
                        'PUSKOPAU'
                    ];
                    foreach ($operators as $op) {
                        $sel = ($operator_selected == $op) ? 'selected' : '';
                        echo "<option value='{$op}' class='bg-slate-900 text-white' {$sel}>{$op}</option>";
                    }
                    ?>
                </select>
                <span class="font-bold text-white uppercase ml-1"><?php echo htmlspecialchars($kode_op); ?></span>
            </div>

            <button type="button" onclick="document.getElementById('formPeriodeOperator').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                cek
            </button>

            <span class="text-slate-200 font-normal ml-2">Jumlah Bus Operasi : <strong class="font-bold text-white text-sm"><?php echo number_format($jumlah_bus_operasi, 0, ',', '.'); ?></strong></span>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportListOperatorCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- MAIN CONTENT (KOLOM KIRI 2 TABEL STACK & KOLOM KANAN LOG TRIP) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- KOLOM KIRI (Dua Tabel Stack: Rekap Jumlah per Rute & List Nomor Bus) -->
        <div class="lg:col-span-3 flex flex-col gap-2.5 min-h-0">
            
            <!-- Tabel Kiri Atas: Tanggal | Rute | Jumlah -->
            <div class="h-44 bg-slate-900 rounded-xl border border-slate-800/80 overflow-hidden shadow-md flex flex-col shrink-0">
                <div class="overflow-auto no-scrollbar">
                    <table class="w-full text-xs text-left text-slate-300">
                        <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                            <tr>
                                <th class="px-2.5 py-1.5">Tanggal</th>
                                <th class="px-2.5 py-1.5">Rute</th>
                                <th class="px-2.5 py-1.5 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-[11px]">
                            <?php foreach ($tabel_kiri_atas as $row): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                                <td class="px-2.5 py-1.5 text-white font-medium"><?php echo $row['rute']; ?></td>
                                <td class="px-2.5 py-1.5 text-right font-mono text-slate-200 font-bold"><?php echo $row['jumlah']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Kiri Bawah: Tanggal | Rute | Nomor Bus -->
            <div class="flex-1 bg-slate-900 rounded-xl border border-slate-800/80 overflow-hidden shadow-md flex flex-col min-h-0">
                <div class="flex-1 overflow-auto no-scrollbar">
                    <table class="w-full text-xs text-left text-slate-300">
                        <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                            <tr>
                                <th class="px-2.5 py-1.5">Tanggal</th>
                                <th class="px-2.5 py-1.5">Rute</th>
                                <th class="px-2.5 py-1.5">Nomor Bus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-[11px]">
                            <?php foreach ($tabel_kiri_bawah as $row): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                                <td class="px-2.5 py-1.5 text-white font-medium"><?php echo $row['rute']; ?></td>
                                <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold"><?php echo $row['nomor_bus']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN: TANGGAL | MULAI | NOMOR BUS | PRAMUDI | ODOMETER | RUTE | RUTE LAYANAN | PETUGAS -->
        <div class="lg:col-span-9 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <?php if (!empty($tabel_kanan_data)): ?>
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Mulai</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2">Pramudi</th>
                            <th class="px-2.5 py-2">Odometer</th>
                            <th class="px-2.5 py-2">Rute</th>
                            <th class="px-2.5 py-2">Rute Layanan</th>
                            <th class="px-2.5 py-2">Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kanan_data as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['mulai']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold"><?php echo $row['nomor_bus']; ?></td>
                            <td class="px-2.5 py-1.5 text-white font-medium"><?php echo $row['pramudi']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['odometer']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['rute']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-400"><?php echo $row['rute_layanan']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-400"><?php echo $row['petugas']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <!-- HEADER TABEL KANAN KOSONG DENGAN PESAN (SESUAI ACUAN GAMBAR) -->
                <div class="w-full flex-1">
                    <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                        <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                            <tr>
                                <th class="px-2.5 py-2">Tanggal</th>
                                <th class="px-2.5 py-2">Mulai</th>
                                <th class="px-2.5 py-2">Nomor Bus</th>
                                <th class="px-2.5 py-2">Pramudi</th>
                                <th class="px-2.5 py-2">Odometer</th>
                                <th class="px-2.5 py-2">Rute</th>
                                <th class="px-2.5 py-2">Rute Layanan</th>
                                <th class="px-2.5 py-2">Petugas</th>
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
            <span id="liveTimestampOperator" class="text-blue-400 font-semibold">--</span>
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

<!-- SCRIPT LIVE CLOCK & EXPORT CSV LIST OPERATOR -->
<script>
function updateLiveTimestampOperator() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampOperator');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampOperator, 1000);
updateLiveTimestampOperator();

function exportListOperatorCSV() {
    let csv = [];
    csv.push("--- LIST OPERATOR ---");
    csv.push("Operator," + "<?php echo $operator_selected; ?> (" + "<?php echo $kode_op; ?>" + ")");
    csv.push("Jumlah Bus Operasi," + "<?php echo $jumlah_bus_operasi; ?>");
    csv.push("Periode," + "<?php echo $periode_selected; ?>");
    csv.push("");
    
    csv.push("--- REKAP JUMLAH BUS PER RUTE ---");
    csv.push("TANGGAL,RUTE,JUMLAH");
    <?php foreach ($tabel_kiri_atas as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("--- LIST NOMOR BUS PER RUTE ---");
    csv.push("TANGGAL,RUTE,NOMOR BUS");
    <?php foreach ($tabel_kiri_bawah as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    <?php if (!empty($tabel_kanan_data)): ?>
    csv.push("");
    csv.push("--- DETAIL LOG TRIP OPERATOR ---");
    csv.push("TANGGAL,MULAI,NOMOR BUS,PRAMUDI,ODOMETER,RUTE,RUTE LAYANAN,PETUGAS");
    <?php foreach ($tabel_kanan_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>
    <?php endif; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "List_Operator_<?php echo str_replace(' ', '_', $operator_selected); ?>_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>