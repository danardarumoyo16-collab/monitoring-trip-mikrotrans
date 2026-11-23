<?php
// Ambil tanggal dari GET URL. Jika kosong/pertama kali dibuka, otomatis gunakan tanggal perangkat server/device
$periode_selected = (isset($_GET['periode']) && !empty($_GET['periode'])) 
    ? $_GET['periode'] 
    : date('Y-m-d');

$bus_selected = isset($_GET['nomor_bus']) ? $_GET['nomor_bus'] : 'BDL-075';

$total_unit_operasi = 0;
$tabel_kiri_data    = [];
$tabel_kanan_data   = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db = mysqli_real_escape_string($koneksi, $periode_selected);
    $bus_db = mysqli_real_escape_string($koneksi, $bus_selected);

    // 1. Hitung Total Unit Operasi
    $q_stat = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_bus) AS total_unit FROM driving_order WHERE DATE(tanggal) = '$tgl_db'");
    if ($q_stat && $r_stat = mysqli_fetch_assoc($q_stat)) {
        $total_unit_operasi = $r_stat['total_unit'] ?: 0;
    }

    // 2. Query Tabel Kiri (Rekap Capaian Km per Bus)
    $q_kiri = mysqli_query($koneksi, "SELECT b.kode_bus, DATE(do.tanggal) AS tgl, SUM(do.km_tempuh) AS km FROM driving_order do JOIN bus b ON do.id_bus = b.id_bus WHERE DATE(do.tanggal) = '$tgl_db' GROUP BY b.kode_bus, DATE(do.tanggal) ORDER BY b.kode_bus ASC");
    if ($q_kiri) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_kiri)) {
            $tabel_kiri_data[] = [
                'no'        => $no++,
                'nomor_bus' => $row['kode_bus'],
                'tanggal'   => date('d/m/Y', strtotime($row['tgl'])),
                'km'        => number_format($row['km'], 2, ',', '.')
            ];
        }
    }

    // 3. Query Tabel Kanan (Detail Log DO Armada Terpilih)
    $where_bus = $bus_db ? "AND b.kode_bus = '$bus_db'" : "";
    $q_kanan = mysqli_query($koneksi, "SELECT do.tanggal, do.lokasi_awal, do.kode_do, b.kode_bus, p.nama_pramudi, do.jam_mulai, do.jam_akhir, do.waktu_tempuh, do.waktu_rest, r.kode_rute FROM driving_order do JOIN bus b ON do.id_bus = b.id_bus JOIN pramudi p ON do.id_pramudi = p.id_pramudi JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' $where_bus ORDER BY do.jam_mulai ASC");
    if ($q_kanan) {
        while ($row = mysqli_fetch_assoc($q_kanan)) {
            $tabel_kanan_data[] = [
                'tanggal'   => date('d/m/Y', strtotime($row['tanggal'])),
                'lokasi'    => $row['lokasi_awal'],
                'kode_do'   => $row['kode_do'],
                'nomor_bus' => $row['kode_bus'],
                'pramudi'   => $row['nama_pramudi'],
                'mulai'     => date('H:i', strtotime($row['jam_mulai'])),
                'akhir'     => $row['jam_akhir'] ? date('H:i', strtotime($row['jam_akhir'])) : '00:00',
                'tempuh'    => $row['waktu_tempuh'],
                'rest'      => $row['waktu_rest'],
                'rute'      => $row['kode_rute']
            ];
        }
    }
}

// Fallback Data jika Database offline / kosong
if (empty($tabel_kiri_data)) {
    $total_unit_operasi = 2855;
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));

    $tabel_kiri_data = [
        ['no' => 1, 'nomor_bus' => 'BDL-075',  'tanggal' => $tgl_fmt, 'km' => '210,50'],
        ['no' => 2, 'nomor_bus' => 'KLM-540',  'tanggal' => $tgl_fmt, 'km' => '187,39'],
        ['no' => 3, 'nomor_bus' => 'KLM-541',  'tanggal' => $tgl_fmt, 'km' => '195,20'],
        ['no' => 4, 'nomor_bus' => 'KLM-542',  'tanggal' => $tgl_fmt, 'km' => '209,00'],
        ['no' => 5, 'nomor_bus' => 'KWK-4201', 'tanggal' => $tgl_fmt, 'km' => '178,40'],
    ];

    $tabel_kanan_data = [
        ['tanggal' => $tgl_fmt, 'lokasi' => 'BARAT2_KALIDERES', 'kode_do' => 'BDL0751A9X...', 'nomor_bus' => 'BDL-075', 'pramudi' => '1,SUTRISNO', 'mulai' => '05:15', 'akhir' => '06:12', 'tempuh' => 57, 'rest' => 10, 'rute' => 'JAK.52'],
        ['tanggal' => $tgl_fmt, 'lokasi' => 'BARAT2_KALIDERES', 'kode_do' => 'BDL0752B3K...', 'nomor_bus' => 'BDL-075', 'pramudi' => '1,SUTRISNO', 'mulai' => '06:22', 'akhir' => '07:20', 'tempuh' => 58, 'rest' => 15, 'rute' => 'JAK.52'],
        ['tanggal' => $tgl_fmt, 'lokasi' => 'BARAT2_KALIDERES', 'kode_do' => 'BDL0753C7L...', 'nomor_bus' => 'BDL-075', 'pramudi' => '2,HENDRA',   'mulai' => '13:00', 'akhir' => '14:05', 'tempuh' => 65, 'rest' => 12, 'rute' => 'JAK.52'],
    ];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriodeCariBus" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="cari_nomor_bus">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodeCariBus').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- HEADER TOP BAR: CEK, STATISTIK UNIT OPERASI, DROPDOWN BUS, TOMBOL RICEK & EXPORT ICON -->
    <div class="bg-slate-900/90 p-2.5 rounded-xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-md border border-slate-800 text-xs">
        <div class="flex items-center gap-4 flex-wrap">
            <button type="button" onclick="document.getElementById('formPeriodeCariBus').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                cek
            </button>

            <span class="text-slate-200 font-normal ml-2">Unit Operasi : <strong class="font-bold text-white text-sm"><?php echo number_format($total_unit_operasi, 0, ',', '.'); ?></strong></span>

            <div class="flex items-center gap-2 ml-4">
                <span class="text-slate-300 font-medium">Nomor Bus</span>
                <select 
                    name="nomor_bus" 
                    form="formPeriodeCariBus"
                    onchange="document.getElementById('formPeriodeCariBus').submit()"
                    class="bg-slate-950/80 text-slate-200 border border-slate-800 hover:border-slate-700 focus:border-blue-500 px-3 py-1 rounded-lg font-medium focus:outline-none cursor-pointer text-xs transition shadow-inner min-w-[150px]"
                >
                    <?php
                    $bus_options = ['BDL-075', 'KLM-540', 'KLM-541', 'KLM-542', 'KLM-543', 'KLM-544', 'KLM-545', 'KLM-551', 'KLM-552', 'KLM-2056', 'KWK-4201'];
                    foreach ($bus_options as $b) {
                        $sel = ($bus_selected == $b) ? 'selected' : '';
                        echo "<option value='{$b}' class='bg-slate-900 text-white' {$sel}>{$b}</option>";
                    }
                    ?>
                </select>
                <span class="font-bold text-white uppercase font-mono ml-1"><?php echo htmlspecialchars($bus_selected); ?></span>
            </div>

            <button type="button" onclick="document.getElementById('formPeriodeCariBus').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm ml-2">
                ricek
            </button>

            <!-- BADGE BUS AKTIF TERFILTER -->
            <span id="activeFilterBadgeCariBus" class="hidden text-xs bg-blue-500/20 text-blue-400 border border-blue-500/30 px-3 py-0.5 rounded-full font-mono font-semibold flex items-center gap-1.5">
                <span id="selectedBusNameCari">--</span>
                <button type="button" onclick="resetFilterCariBus()" class="text-slate-400 hover:text-white font-bold ml-1 text-sm leading-none">&times;</button>
            </span>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportCariBusCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- MAIN CONTENT 2 TABEL CARI BUS -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- TABEL KIRI (REKAP CAPAIAN KM BUS) -->
        <div class="lg:col-span-3 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">No</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2 text-right">Km</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kiri_data as $row): ?>
                        <tr 
                            id="row-bus-cari-<?php echo str_replace('-', '', $row['nomor_bus']); ?>"
                            onclick="filterCariBusItem('<?php echo $row['nomor_bus']; ?>')" 
                            class="row-bus-cari-item hover:bg-blue-950/40 cursor-pointer transition group"
                        >
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['no']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold group-hover:text-blue-400 transition">
                                <span><?php echo $row['nomor_bus']; ?></span>
                            </td>
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-200 font-bold"><?php echo $row['km']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL KANAN (DETAIL LOG TRIP & DO ARMADA) -->
        <div class="lg:col-span-9 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Lokasi</th>
                            <th class="px-2.5 py-2">Kode DO</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2">Pramudi</th>
                            <th class="px-2.5 py-2">Mulai</th>
                            <th class="px-2.5 py-2">Akhir</th>
                            <th class="px-2.5 py-2 text-right">Tempuh</th>
                            <th class="px-2.5 py-2 text-right">Rest</th>
                            <th class="px-2.5 py-2">Rute</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyCariBusKanan" class="divide-y divide-slate-800/60 text-[11px]">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- FOOTER STATUS BAR REALTIME -->
    <div class="bg-slate-900/90 px-4 py-1.5 rounded-xl flex justify-between items-center text-[11px] font-mono border border-slate-800 shrink-0 text-slate-400">
        <div class="flex items-center gap-3">
            <span id="liveTimestampCariBus" class="text-blue-400 font-semibold">--</span>
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

<!-- SCRIPT INTERAKTIF FILTER, LIVE CLOCK & EXPORT CSV -->
<script>
const rawCariBusKanan = <?php echo json_encode($tabel_kanan_data); ?>;
let selectedBusCariItem = null;

function normalizeBusName(bus) {
    if (!bus) return '';
    return bus.toString().replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

function renderCariBusKananTable(selectedBus = null) {
    const tbody = document.getElementById('tbodyCariBusKanan');
    if (!tbody) return;

    const filtered = selectedBus 
        ? rawCariBusKanan.filter(item => normalizeBusName(item.nomor_bus) === normalizeBusName(selectedBus))
        : rawCariBusKanan;

    tbody.innerHTML = '';

    if (filtered.length > 0) {
        filtered.forEach(row => {
            tbody.innerHTML += `
                <tr class="hover:bg-slate-800/40 transition">
                    <td class="px-2.5 py-1.5 text-slate-400 font-mono">${row.tanggal}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.lokasi}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.kode_do}</td>
                    <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold">${row.nomor_bus}</td>
                    <td class="px-2.5 py-1.5 text-white font-medium">${row.pramudi}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.mulai}</td>
                    <td class="px-2.5 py-1.5 font-mono ${row.akhir === '00:00' ? 'text-red-400 font-bold' : 'text-slate-300'}">${row.akhir}</td>
                    <td class="px-2.5 py-1.5 text-right font-mono text-slate-300">${row.tempuh}</td>
                    <td class="px-2.5 py-1.5 text-right font-mono text-slate-300">${row.rest}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.rute}</td>
                </tr>
            `;
        });
    } else {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="px-2.5 py-6 text-center text-slate-500 italic">
                    Tidak ditemukan detail log trip untuk armada bus ${selectedBus || ''} pada tanggal ini.
                </td>
            </tr>
        `;
    }
}

function filterCariBusItem(nomorBus) {
    if (selectedBusCariItem === nomorBus) {
        resetFilterCariBus();
        return;
    }

    selectedBusCariItem = nomorBus;

    document.querySelectorAll('.row-bus-cari-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const activeRow = document.getElementById('row-bus-cari-' + normalizeBusName(nomorBus));
    if (activeRow) {
        activeRow.classList.add('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    }

    const badge = document.getElementById('activeFilterBadgeCariBus');
    const nameElem = document.getElementById('selectedBusNameCari');
    if (badge && nameElem) {
        nameElem.innerText = nomorBus;
        badge.classList.remove('hidden');
    }

    renderCariBusKananTable(nomorBus);
}

function resetFilterCariBus() {
    selectedBusCariItem = null;

    document.querySelectorAll('.row-bus-cari-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const badge = document.getElementById('activeFilterBadgeCariBus');
    if (badge) badge.classList.add('hidden');

    renderCariBusKananTable(null);
}

document.addEventListener("DOMContentLoaded", function() {
    renderCariBusKananTable(null);

    const inputPeriode = document.getElementById('inputPeriode');
    if (inputPeriode && !inputPeriode.value) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        inputPeriode.value = `${year}-${month}-${day}`;
    }
});

function updateLiveTimestampCariBus() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampCariBus');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampCariBus, 1000);
updateLiveTimestampCariBus();

function exportCariBusCSV() {
    let csv = [];
    csv.push("--- CARI NOMOR BUS ---");
    csv.push("Nomor Bus Terpilih," + "<?php echo $bus_selected; ?>");
    csv.push("Periode," + "<?php echo $periode_selected; ?>");
    csv.push("Unit Operasi," + "<?php echo $total_unit_operasi; ?>");
    if (selectedBusCariItem) {
        csv.push("Filter Bus Item," + selectedBusCariItem);
    }
    csv.push("");
    
    csv.push("--- TABEL KIRI (REKAP CAPAIAN KM BUS) ---");
    csv.push("NO,NOMOR BUS,TANGGAL,KM");
    <?php foreach ($tabel_kiri_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("--- TABEL KANAN (DETAIL LOG TRIP BUS) ---");
    csv.push("TANGGAL,LOKASI,KODE DO,NOMOR BUS,PRAMUDI,MULAI,AKHIR,TEMPUH,REST,RUTE");
    <?php foreach ($tabel_kanan_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "Cari_Bus_<?php echo $bus_selected; ?>_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>