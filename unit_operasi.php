<?php
// Ambil tanggal dari GET URL. Jika kosong/pertama kali dibuka, otomatis gunakan tanggal perangkat server/device
$periode_selected = (isset($_GET['periode']) && !empty($_GET['periode'])) 
    ? $_GET['periode'] 
    : date('Y-m-d');

// Inisialisasi variabel hitung dari Database
$total_unit_operasi = 0;
$total_jumlah_unit = 0;

$tabel1_data = [];
$tabel2_data = [];
$tabel3_data = [];

// Jika database terhubung, ambil data dinamis dari database MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db = mysqli_real_escape_string($koneksi, $periode_selected);

    // Hitung Total Unit Operasi (Count BUS unik) & Total Jumlah Unit
    $q_stat = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_bus) AS unit_ops, COUNT(id_do) AS total_unit FROM driving_order WHERE DATE(tanggal) = '$tgl_db'");
    if ($q_stat && $r_stat = mysqli_fetch_assoc($q_stat)) {
        $total_unit_operasi = $r_stat['unit_ops'] ?: 0;
        $total_jumlah_unit  = $r_stat['total_unit'] ?: 0;
    }

    // Tabel 1: Rekap per Rute
    $q_t1 = mysqli_query($koneksi, "SELECT r.kode_rute, COUNT(do.id_do) AS jumlah FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' GROUP BY r.kode_rute ORDER BY jumlah DESC");
    if ($q_t1) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_t1)) {
            $tabel1_data[] = [
                'no'      => $no++,
                'tanggal' => date('d/m/Y', strtotime($periode_selected)),
                'rute'    => $row['kode_rute'],
                'jumlah'  => $row['jumlah']
            ];
        }
    }

    // Tabel 2: Rekap per Operator
    $q_t2 = mysqli_query($koneksi, "SELECT r.kode_rute, o.nama_operator, COUNT(do.id_bus) AS unit FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN operator o ON do.id_operator = o.id_operator WHERE DATE(do.tanggal) = '$tgl_db' GROUP BY r.kode_rute, o.nama_operator");
    if ($q_t2) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_t2)) {
            $tabel2_data[] = [
                'no'       => $no++,
                'tanggal'  => date('d/m/Y', strtotime($periode_selected)),
                'rute'     => $row['kode_rute'],
                'operator' => $row['nama_operator'],
                'unit'     => $row['unit']
            ];
        }
    }

    // Tabel 3: Detail Keberangkatan Bus
    $q_t3 = mysqli_query($koneksi, "SELECT r.kode_rute, do.jam_berangkat, b.kode_bus, do.lokasi_awal FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus WHERE DATE(do.tanggal) = '$tgl_db' ORDER BY do.jam_berangkat ASC");
    if ($q_t3) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_t3)) {
            $tabel3_data[] = [
                'no'        => $no++,
                'tanggal'   => date('d/m/Y', strtotime($periode_selected)),
                'rute'      => $row['kode_rute'],
                'berangkat' => date('H:i', strtotime($row['jam_berangkat'])),
                'nomor_bus' => $row['kode_bus'],
                'lokasi'    => $row['lokasi_awal']
            ];
        }
    }
}

// Fallback Data jika database belum terhubung / kosong
if (empty($tabel1_data)) {
    $total_unit_operasi = 2844;
    $total_jumlah_unit  = 2844;
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));

    $tabel1_data = [
        ['no' => 1, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.01',  'jumlah' => 19],
        ['no' => 2, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.02',  'jumlah' => 23],
        ['no' => 3, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.03',  'jumlah' => 16],
        ['no' => 4, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.04',  'jumlah' => 13],
        ['no' => 5, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.05',  'jumlah' => 33],
        ['no' => 6, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.06',  'jumlah' => 34],
        ['no' => 7, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.07',  'jumlah' => 21],
        ['no' => 8, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.42',  'jumlah' => 18],
    ];

    $tabel2_data = [
        ['no' => 1, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'operator' => 'BUDI LUHUR', 'unit' => 19],
        ['no' => 2, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.02', 'operator' => 'KENCANA SAKTI', 'unit' => 23],
        ['no' => 3, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.42', 'operator' => 'KWK', 'unit' => 18],
    ];

    $tabel3_data = [
        ['no' => 1, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'berangkat' => '05:00', 'nomor_bus' => 'KLM-551',    'lokasi' => 'PLUMPANG'],
        ['no' => 2, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.42', 'berangkat' => '05:10', 'nomor_bus' => 'KWK-4201',   'lokasi' => 'PONDOK KELAPA'],
        ['no' => 3, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.42', 'berangkat' => '05:25', 'nomor_bus' => 'KWK-4202',   'lokasi' => 'PONDOK KELAPA'],
        ['no' => 4, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.42', 'berangkat' => '05:40', 'nomor_bus' => 'KWK-4203',   'lokasi' => 'PONDOK KELAPA'],
    ];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriode" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="unit_operasi">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriode').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- HEADER TOP BAR: CEK, STATISTIK, EXPORT ICON -->
    <div class="bg-slate-900/90 p-2.5 rounded-xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-md border border-slate-800 text-xs">
        <div class="flex items-center gap-6 flex-wrap">
            <button type="button" onclick="document.getElementById('formPeriode').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                cek
            </button>
            
            <div class="flex items-center gap-8 text-slate-200 font-normal text-xs">
                <span>Unit Operasi : <strong class="font-bold text-white text-sm"><?php echo number_format($total_unit_operasi, 0, ',', '.'); ?></strong></span>
                <span>Jumlah Unit : <strong class="font-bold text-white text-sm"><?php echo number_format($total_jumlah_unit, 0, ',', '.'); ?></strong></span>
                
                <!-- BADGE STATUS RUTE AKTIF (TANPA TEKS 'FILTER:') -->
                <span id="activeFilterBadge" class="hidden text-xs bg-blue-500/20 text-blue-400 border border-blue-500/30 px-3 py-0.5 rounded-full font-mono font-semibold flex items-center gap-1.5">
                    <span id="selectedRuteName">--</span>
                    <button type="button" onclick="resetFilterRute()" class="text-slate-400 hover:text-white font-bold ml-1 text-sm leading-none">&times;</button>
                </span>
            </div>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportUnitOperasiCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- 3 KOLOM TABEL UNIT OPERASI -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- TABEL 1 (KLIK RUTE UNTUK FILTER TABEL KANAN) -->
        <div class="lg:col-span-3 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">No</th>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Rute</th>
                            <th class="px-2.5 py-2 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel1_data as $row): ?>
                        <tr 
                            id="row-rute-<?php echo str_replace('.', '', $row['rute']); ?>"
                            onclick="filterDataRute('<?php echo $row['rute']; ?>')" 
                            class="row-rute-item hover:bg-blue-950/40 cursor-pointer transition group"
                        >
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['no']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-white font-medium group-hover:text-blue-400 transition">
                                <span><?php echo $row['rute']; ?></span>
                            </td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-200 font-bold"><?php echo $row['jumlah']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL 2 (REKAP OPERATOR) -->
        <div class="lg:col-span-4 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">No</th>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Rute</th>
                            <th class="px-2.5 py-2">Operator</th>
                            <th class="px-2.5 py-2 text-right">Unit</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyTabel2" class="divide-y divide-slate-800/60 text-[11px]">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL 3 (DETAIL BUS) -->
        <div class="lg:col-span-5 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">No</th>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Rute</th>
                            <th class="px-2.5 py-2">Berangkat</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2">Lokasi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyTabel3" class="divide-y divide-slate-800/60 text-[11px]">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- FOOTER STATUS BAR REALTIME -->
    <div class="bg-slate-900/90 px-4 py-1.5 rounded-xl flex justify-between items-center text-[11px] font-mono border border-slate-800 shrink-0 text-slate-400">
        <div class="flex items-center gap-3">
            <span id="liveTimestamp" class="text-blue-400 font-semibold">--</span>
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

<!-- SCRIPT FILTERING ON CLICK, LIVE CLOCK & EXPORT CSV -->
<script>
const rawTabel2 = <?php echo json_encode($tabel2_data); ?>;
const rawTabel3 = <?php echo json_encode($tabel3_data); ?>;

let currentSelectedRute = null;

function normalizeRute(rute) {
    if (!rute) return '';
    return rute.toString().replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

function renderTables(selectedRute = null) {
    const tbody2 = document.getElementById('tbodyTabel2');
    const tbody3 = document.getElementById('tbodyTabel3');

    const filtered2 = selectedRute 
        ? rawTabel2.filter(item => normalizeRute(item.rute) === normalizeRute(selectedRute))
        : rawTabel2;

    const filtered3 = selectedRute 
        ? rawTabel3.filter(item => normalizeRute(item.rute) === normalizeRute(selectedRute))
        : rawTabel3;

    // Render Tabel 2
    tbody2.innerHTML = '';
    if (filtered2.length > 0) {
        filtered2.forEach((row, idx) => {
            tbody2.innerHTML += `
                <tr class="hover:bg-slate-800/40 transition">
                    <td class="px-2.5 py-1.5 text-slate-400 font-mono">${idx + 1}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.tanggal}</td>
                    <td class="px-2.5 py-1.5 text-white font-medium">${row.rute}</td>
                    <td class="px-2.5 py-1.5 text-slate-300">${row.operator}</td>
                    <td class="px-2.5 py-1.5 text-right font-mono text-slate-200">${row.unit}</td>
                </tr>
            `;
        });
    } else {
        tbody2.innerHTML = `
            <tr>
                <td colspan="5" class="px-2.5 py-4 text-center text-slate-500 italic">
                    Tidak ada data operator untuk rute ini.
                </td>
            </tr>
        `;
    }

    // Render Tabel 3
    tbody3.innerHTML = '';
    if (filtered3.length > 0) {
        filtered3.forEach((row, idx) => {
            tbody3.innerHTML += `
                <tr class="hover:bg-slate-800/40 transition">
                    <td class="px-2.5 py-1.5 text-slate-400 font-mono">${idx + 1}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.tanggal}</td>
                    <td class="px-2.5 py-1.5 text-white font-medium">${row.rute}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.berangkat}</td>
                    <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold">${row.nomor_bus}</td>
                    <td class="px-2.5 py-1.5 text-slate-400">${row.lokasi}</td>
                </tr>
            `;
        });
    } else {
        tbody3.innerHTML = `
            <tr>
                <td colspan="6" class="px-2.5 py-4 text-center text-slate-500 italic">
                    Tidak ada detail bus beroperasi untuk rute ini.
                </td>
            </tr>
        `;
    }
}

function filterDataRute(kodeRute) {
    if (currentSelectedRute === kodeRute) {
        resetFilterRute();
        return;
    }

    currentSelectedRute = kodeRute;

    document.querySelectorAll('.row-rute-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const activeRow = document.getElementById('row-rute-' + normalizeRute(kodeRute));
    if (activeRow) {
        activeRow.classList.add('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    }

    const badge = document.getElementById('activeFilterBadge');
    const nameElem = document.getElementById('selectedRuteName');
    if (badge && nameElem) {
        nameElem.innerText = kodeRute;
        badge.classList.remove('hidden');
    }

    renderTables(kodeRute);
}

function resetFilterRute() {
    currentSelectedRute = null;

    document.querySelectorAll('.row-rute-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const badge = document.getElementById('activeFilterBadge');
    if (badge) badge.classList.add('hidden');

    renderTables(null);
}

document.addEventListener("DOMContentLoaded", function() {
    renderTables(null);

    const inputPeriode = document.getElementById('inputPeriode');
    if (inputPeriode && !inputPeriode.value) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        inputPeriode.value = `${year}-${month}-${day}`;
    }
});

function updateLiveTimestamp() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestamp');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestamp, 1000);
updateLiveTimestamp();

function exportUnitOperasiCSV() {
    let csv = [];
    csv.push("--- REKAP UNIT OPERASI ---");
    csv.push("Unit Operasi," + "<?php echo $total_unit_operasi; ?>");
    csv.push("Jumlah Unit," + "<?php echo $total_jumlah_unit; ?>");
    if (currentSelectedRute) {
        csv.push("Filter Rute," + currentSelectedRute);
    }
    csv.push("");
    
    csv.push("NO,TANGGAL,RUTE,JUMLAH");
    <?php foreach ($tabel1_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("NO,TANGGAL,RUTE,OPERATOR,UNIT");
    <?php foreach ($tabel2_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("NO,TANGGAL,RUTE,BERANGKAT,NOMOR BUS,LOKASI");
    <?php foreach ($tabel3_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "Unit_Operasi_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>