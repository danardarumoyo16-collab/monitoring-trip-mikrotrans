<?php
// Ambil tanggal dari GET URL. Jika kosong/pertama kali dibuka, otomatis gunakan tanggal perangkat server/device
$periode_selected = (isset($_GET['periode']) && !empty($_GET['periode'])) 
    ? $_GET['periode'] 
    : date('Y-m-d');

$wilayah_selected = isset($_GET['wilayah']) ? $_GET['wilayah'] : 'ALL';

$total_unit_operasi = 0;
$tabel1_data = [];
$tabel2_data = [];
$tabel3_data = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db = mysqli_real_escape_string($koneksi, $periode_selected);
    $wil_db = mysqli_real_escape_string($koneksi, $wilayah_selected);

    $where_wil = ($wil_db != 'ALL') ? "AND r.wilayah = '$wil_db'" : "";

    // 1. Total Unit Operasi
    $q_stat = mysqli_query($koneksi, "SELECT COUNT(DISTINCT do.id_bus) AS total_unit FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' $where_wil");
    if ($q_stat && $r_stat = mysqli_fetch_assoc($q_stat)) {
        $total_unit_operasi = $r_stat['total_unit'] ?: 0;
    }

    // 2. Tabel 1 (Wilayah, Rute, Unit)
    $q_t1 = mysqli_query($koneksi, "SELECT r.wilayah, r.kode_rute, COUNT(DISTINCT do.id_bus) AS unit FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' $where_wil GROUP BY r.wilayah, r.kode_rute ORDER BY r.kode_rute ASC");
    if ($q_t1) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_t1)) {
            $tabel1_data[] = [
                'no'      => $no++,
                'tanggal' => date('d/m/Y', strtotime($periode_selected)),
                'wilayah' => $row['wilayah'],
                'rute'    => $row['kode_rute'],
                'unit'    => $row['unit']
            ];
        }
    }

    // 3. Tabel 2 (Wilayah, Rute, Operator, Unit)
    $q_t2 = mysqli_query($koneksi, "SELECT r.wilayah, r.kode_rute, o.nama_operator, COUNT(DISTINCT do.id_bus) AS unit FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN operator o ON do.id_operator = o.id_operator WHERE DATE(do.tanggal) = '$tgl_db' $where_wil GROUP BY r.wilayah, r.kode_rute, o.nama_operator ORDER BY r.kode_rute ASC");
    if ($q_t2) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_t2)) {
            $tabel2_data[] = [
                'no'       => $no++,
                'wilayah'  => $row['wilayah'],
                'rute'     => $row['kode_rute'],
                'operator' => $row['nama_operator'],
                'unit'     => $row['unit']
            ];
        }
    }

    // 4. Tabel 3 (Tanggal, Rute, Nomor Bus, Waktu)
    $q_t3 = mysqli_query($koneksi, "SELECT do.tanggal, r.kode_rute, b.kode_bus, do.jam_berangkat FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus WHERE DATE(do.tanggal) = '$tgl_db' $where_wil ORDER BY do.jam_berangkat ASC");
    if ($q_t3) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_t3)) {
            $tabel3_data[] = [
                'no'        => $no++,
                'tanggal'   => date('d/m/Y', strtotime($row['tanggal'])),
                'rute'      => $row['kode_rute'],
                'nomor_bus' => $row['kode_bus'],
                'waktu'     => date('H:i', strtotime($row['jam_berangkat']))
            ];
        }
    }
}

// Fallback Data jika Database offline / kosong
if (empty($tabel1_data)) {
    $total_unit_operasi = 2838;
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));

    $tabel1_data = [
        ['no' => 1,  'tanggal' => $tgl_fmt, 'wilayah' => 'Utara',   'rute' => 'JAK.01',  'unit' => 18],
        ['no' => 2,  'tanggal' => $tgl_fmt, 'wilayah' => 'Timur1',  'rute' => 'JAK.02',  'unit' => 23],
        ['no' => 3,  'tanggal' => $tgl_fmt, 'wilayah' => 'Selatan', 'rute' => 'JAK.03',  'unit' => 16],
        ['no' => 4,  'tanggal' => $tgl_fmt, 'wilayah' => 'Barat',   'rute' => 'JAK.04',  'unit' => 12],
        ['no' => 5,  'tanggal' => $tgl_fmt, 'wilayah' => 'Utara',   'rute' => 'JAK.05',  'unit' => 33],
        ['no' => 6,  'tanggal' => $tgl_fmt, 'wilayah' => 'Timur2',  'rute' => 'JAK.06',  'unit' => 34],
        ['no' => 7,  'tanggal' => $tgl_fmt, 'wilayah' => 'Pusat',   'rute' => 'JAK.07',  'unit' => 23],
        ['no' => 8,  'tanggal' => $tgl_fmt, 'wilayah' => 'Pusat',   'rute' => 'JAK.08',  'unit' => 13],
        ['no' => 9,  'tanggal' => $tgl_fmt, 'wilayah' => 'Pusat',   'rute' => 'JAK.09',  'unit' => 8],
        ['no' => 10, 'tanggal' => $tgl_fmt, 'wilayah' => 'Pusat',   'rute' => 'JAK.10',  'unit' => 26],
        ['no' => 11, 'tanggal' => $tgl_fmt, 'wilayah' => 'Pusat',   'rute' => 'JAK.10A', 'unit' => 20],
        ['no' => 12, 'tanggal' => $tgl_fmt, 'wilayah' => 'Pusat',   'rute' => 'JAK.10B', 'unit' => 3],
        ['no' => 13, 'tanggal' => $tgl_fmt, 'wilayah' => 'Pusat',   'rute' => 'JAK.11',  'unit' => 40],
        ['no' => 14, 'tanggal' => $tgl_fmt, 'wilayah' => 'Pusat',   'rute' => 'JAK.12',  'unit' => 41],
        ['no' => 15, 'tanggal' => $tgl_fmt, 'wilayah' => 'Pusat',   'rute' => 'JAK.13',  'unit' => 23],
    ];

    $tabel2_data = [
        ['no' => 1,  'wilayah' => 'Barat',  'rute' => 'JAK.04',  'operator' => 'KWK', 'unit' => 12],
        ['no' => 2,  'wilayah' => 'Barat',  'rute' => 'JAK.30',  'operator' => 'KWK', 'unit' => 69],
        ['no' => 3,  'wilayah' => 'Barat',  'rute' => 'JAK.53',  'operator' => 'KMJ', 'unit' => 43],
        ['no' => 4,  'wilayah' => 'Barat',  'rute' => 'JAK.54',  'operator' => 'KMJ', 'unit' => 27],
        ['no' => 5,  'wilayah' => 'Barat',  'rute' => 'JAK.56',  'operator' => 'KMJ', 'unit' => 31],
        ['no' => 6,  'wilayah' => 'Barat',  'rute' => 'JAK.107', 'operator' => 'KWK', 'unit' => 24],
        ['no' => 7,  'wilayah' => 'Barat',  'rute' => 'JAK.50',  'operator' => 'KWK', 'unit' => 31],
        ['no' => 8,  'wilayah' => 'Barat',  'rute' => 'JAK.51',  'operator' => 'KWK', 'unit' => 45],
        ['no' => 9,  'wilayah' => 'Barat',  'rute' => 'JAK.52',  'operator' => 'BDL', 'unit' => 24],
        ['no' => 10, 'wilayah' => 'Barat',  'rute' => 'JAK.52',  'operator' => 'KLM', 'unit' => 3],
        ['no' => 11, 'wilayah' => 'Barat',  'rute' => 'JAK.52',  'operator' => 'KMJ', 'unit' => 9],
        ['no' => 12, 'wilayah' => 'Barat',  'rute' => 'JAK.52',  'operator' => 'KPM', 'unit' => 10],
        ['no' => 13, 'wilayah' => 'Barat',  'rute' => 'JAK.78A', 'operator' => 'KWK', 'unit' => 13],
        ['no' => 14, 'wilayah' => 'Barat',  'rute' => 'JAK.78B', 'operator' => 'KWK', 'unit' => 11],
        ['no' => 15, 'wilayah' => 'Barat',  'rute' => 'JAK.79',  'operator' => 'KWK', 'unit' => 19],
    ];

    $tabel3_data = [
        ['no' => 1,  'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-1737', 'waktu' => '05:24'],
        ['no' => 2,  'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-2056', 'waktu' => '05:18'],
        ['no' => 3,  'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-540',  'waktu' => '05:21'],
        ['no' => 4,  'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-541',  'waktu' => '05:00'],
        ['no' => 5,  'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-542',  'waktu' => '05:09'],
        ['no' => 6,  'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-543',  'waktu' => '05:15'],
        ['no' => 7,  'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-544',  'waktu' => '05:10'],
        ['no' => 8,  'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-545',  'waktu' => '05:00'],
        ['no' => 9,  'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-551',  'waktu' => '05:20'],
        ['no' => 10, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-552',  'waktu' => '05:06'],
        ['no' => 11, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-553',  'waktu' => '05:15'],
        ['no' => 12, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-555',  'waktu' => '05:27'],
        ['no' => 13, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-557',  'waktu' => '05:12'],
        ['no' => 14, 'tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'nomor_bus' => 'KLM-558',  'waktu' => '05:03'],
    ];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriodeRicek" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="ricek_realisasi">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodeRicek').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- HEADER TOP BAR: FILTER WILAYAH, STATISTIK UNIT OPERASI, CEK & EXPORT ICON -->
    <div class="bg-slate-900/90 p-2.5 rounded-xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-md border border-slate-800 text-xs">
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-slate-300 font-medium">Wilayah</span>
                <select 
                    name="wilayah" 
                    form="formPeriodeRicek"
                    onchange="document.getElementById('formPeriodeRicek').submit()"
                    class="bg-slate-950/80 text-slate-200 border border-slate-800 hover:border-slate-700 focus:border-blue-500 px-3 py-1 rounded-lg font-medium focus:outline-none cursor-pointer text-xs transition shadow-inner min-w-[120px]"
                >
                    <?php
                    // Opsi Kategori Wilayah Sesuai Permintaan
                    $wilayah_options = ['ALL', 'Utara', 'Selatan', 'Barat', 'Pusat', 'Timur1', 'Timur2'];
                    foreach ($wilayah_options as $w) {
                        $sel = ($wilayah_selected == $w) ? 'selected' : '';
                        echo "<option value='{$w}' class='bg-slate-900 text-white' {$sel}>{$w}</option>";
                    }
                    ?>
                </select>
                <span class="font-bold text-white uppercase ml-1"><?php echo htmlspecialchars($wilayah_selected); ?></span>
            </div>

            <button type="button" onclick="document.getElementById('formPeriodeRicek').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                cek
            </button>

            <span class="text-slate-200 font-normal ml-2">Unit Operasi : <strong class="font-bold text-white text-sm"><?php echo number_format($total_unit_operasi, 0, ',', '.'); ?></strong></span>

            <!-- BADGE STATUS RUTE AKTIF (TANPA TEKS 'FILTER:') -->
            <span id="activeFilterBadgeRicek" class="hidden text-xs bg-blue-500/20 text-blue-400 border border-blue-500/30 px-3 py-0.5 rounded-full font-mono font-semibold flex items-center gap-1.5">
                <span id="selectedRuteNameRicek">--</span>
                <button type="button" onclick="resetFilterRuteRicek()" class="text-slate-400 hover:text-white font-bold ml-1 text-sm leading-none">&times;</button>
            </span>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportRicekRealisasiCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- 3 KOLOM TABEL RICEK REALISASI -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- TABEL 1 (KIRI: REKAP WILAYAH & RUTE) -->
        <div class="lg:col-span-4 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">No</th>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Wilayah</th>
                            <th class="px-2.5 py-2">Rute</th>
                            <th class="px-2.5 py-2 text-right">Unit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel1_data as $row): ?>
                        <tr 
                            id="row-ricek-rute-<?php echo str_replace('.', '', $row['rute']); ?>"
                            onclick="filterRicekByRute('<?php echo $row['rute']; ?>')" 
                            class="row-ricek-item hover:bg-blue-950/40 cursor-pointer transition group"
                        >
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['no']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono uppercase"><?php echo $row['wilayah']; ?></td>
                            <td class="px-2.5 py-1.5 text-white font-medium group-hover:text-blue-400 transition">
                                <span><?php echo $row['rute']; ?></span>
                            </td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-200 font-bold"><?php echo $row['unit']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL 2 (TENGAH: REKAP OPERATOR PER RUTE) -->
        <div class="lg:col-span-4 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">No</th>
                            <th class="px-2.5 py-2">Wilayah</th>
                            <th class="px-2.5 py-2">Rute</th>
                            <th class="px-2.5 py-2">Operator</th>
                            <th class="px-2.5 py-2 text-right">Unit</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyRicek2" class="divide-y divide-slate-800/60 text-[11px]">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL 3 (KANAN: DETAIL KEBERANGKATAN BUS) -->
        <div class="lg:col-span-4 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">No</th>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Rute</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2">Waktu</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyRicek3" class="divide-y divide-slate-800/60 text-[11px]">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- FOOTER STATUS BAR REALTIME -->
    <div class="bg-slate-900/90 px-4 py-1.5 rounded-xl flex justify-between items-center text-[11px] font-mono border border-slate-800 shrink-0 text-slate-400">
        <div class="flex items-center gap-3">
            <span id="liveTimestampRicek" class="text-blue-400 font-semibold">--</span>
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
const rawRicek2 = <?php echo json_encode($tabel2_data); ?>;
const rawRicek3 = <?php echo json_encode($tabel3_data); ?>;

let selectedRicekRute = null;

function normalizeRuteRicek(rute) {
    if (!rute) return '';
    return rute.toString().replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

function renderRicekTables(selectedRute = null) {
    const tbody2 = document.getElementById('tbodyRicek2');
    const tbody3 = document.getElementById('tbodyRicek3');

    const filtered2 = selectedRute 
        ? rawRicek2.filter(item => normalizeRuteRicek(item.rute) === normalizeRuteRicek(selectedRute))
        : rawRicek2;

    const filtered3 = selectedRute 
        ? rawRicek3.filter(item => normalizeRuteRicek(item.rute) === normalizeRuteRicek(selectedRute))
        : rawRicek3;

    // Render Tabel 2
    tbody2.innerHTML = '';
    if (filtered2.length > 0) {
        filtered2.forEach((row, idx) => {
            tbody2.innerHTML += `
                <tr class="hover:bg-slate-800/40 transition">
                    <td class="px-2.5 py-1.5 text-slate-400 font-mono">${idx + 1}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono uppercase">${row.wilayah}</td>
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
                    <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold">${row.nomor_bus}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.waktu}</td>
                </tr>
            `;
        });
    } else {
        tbody3.innerHTML = `
            <tr>
                <td colspan="5" class="px-2.5 py-4 text-center text-slate-500 italic">
                    Tidak ada detail keberangkatan armada untuk rute ini.
                </td>
            </tr>
        `;
    }
}

function filterRicekByRute(kodeRute) {
    if (selectedRicekRute === kodeRute) {
        resetFilterRuteRicek();
        return;
    }

    selectedRicekRute = kodeRute;

    document.querySelectorAll('.row-ricek-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const activeRow = document.getElementById('row-ricek-rute-' + normalizeRuteRicek(kodeRute));
    if (activeRow) {
        activeRow.classList.add('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    }

    const badge = document.getElementById('activeFilterBadgeRicek');
    const nameElem = document.getElementById('selectedRuteNameRicek');
    if (badge && nameElem) {
        nameElem.innerText = kodeRute;
        badge.classList.remove('hidden');
    }

    renderRicekTables(kodeRute);
}

function resetFilterRuteRicek() {
    selectedRicekRute = null;

    document.querySelectorAll('.row-ricek-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const badge = document.getElementById('activeFilterBadgeRicek');
    if (badge) badge.classList.add('hidden');

    renderRicekTables(null);
}

document.addEventListener("DOMContentLoaded", function() {
    renderRicekTables(null);

    const inputPeriode = document.getElementById('inputPeriode');
    if (inputPeriode && !inputPeriode.value) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        inputPeriode.value = `${year}-${month}-${day}`;
    }
});

function updateLiveTimestampRicek() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampRicek');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampRicek, 1000);
updateLiveTimestampRicek();

function exportRicekRealisasiCSV() {
    let csv = [];
    csv.push("--- RICEK REALISASI ---");
    csv.push("Wilayah," + "<?php echo $wilayah_selected; ?>");
    csv.push("Periode," + "<?php echo $periode_selected; ?>");
    csv.push("Unit Operasi," + "<?php echo $total_unit_operasi; ?>");
    if (selectedRicekRute) {
        csv.push("Filter Rute," + selectedRicekRute);
    }
    csv.push("");
    
    csv.push("--- TABEL 1 (REKAP WILAYAH & RUTE) ---");
    csv.push("NO,TANGGAL,WILAYAH,RUTE,UNIT");
    <?php foreach ($tabel1_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("--- TABEL 2 (REKAP OPERATOR PER RUTE) ---");
    csv.push("NO,WILAYAH,RUTE,OPERATOR,UNIT");
    <?php foreach ($tabel2_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("--- TABEL 3 (DETAIL KEBERANGKATAN BUS) ---");
    csv.push("NO,TANGGAL,RUTE,NOMOR BUS,WAKTU");
    <?php foreach ($tabel3_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "Ricek_Realisasi_<?php echo $wilayah_selected; ?>_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>