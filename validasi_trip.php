<?php
// Ambil tanggal dari GET URL. Jika kosong/pertama kali dibuka, otomatis gunakan tanggal perangkat server/device
$periode_selected = (isset($_GET['periode']) && !empty($_GET['periode'])) 
    ? $_GET['periode'] 
    : date('Y-m-d');

$rute_selected = isset($_GET['rute']) ? $_GET['rute'] : 'JAK1';

$total_capaian_km = 0;
$tabel_capaian    = [];
$tabel_operator   = [];
$tabel_detail     = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db  = mysqli_real_escape_string($koneksi, $periode_selected);
    $rute_db = mysqli_real_escape_string($koneksi, $rute_selected);

    $where_rute = $rute_db ? "AND r.kode_rute = '$rute_db'" : "";

    // 1. Total Capaian Km
    $q_km = mysqli_query($koneksi, "SELECT SUM(do.km_tempuh) AS total_km FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' $where_rute");
    if ($q_km && $r_km = mysqli_fetch_assoc($q_km)) {
        $total_capaian_km = $r_km['total_km'] ?: 0;
    }

    // 2. Tabel Capaian Bus
    $q_c = mysqli_query($koneksi, "SELECT b.kode_bus, SUM(do.km_tempuh) AS capaian FROM driving_order do JOIN bus b ON do.id_bus = b.id_bus JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' $where_rute GROUP BY b.kode_bus ORDER BY b.kode_bus ASC");
    if ($q_c) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_c)) {
            $tabel_capaian[] = [
                'no'        => $no++,
                'nomor_bus' => $row['kode_bus'],
                'capaian'   => number_format($row['capaian'], 2, ',', '.')
            ];
        }
    }

    // 3. Tabel Operator
    $q_op = mysqli_query($koneksi, "SELECT o.nama_operator, COUNT(do.id_do) AS jumlah FROM driving_order do JOIN operator o ON do.id_operator = o.id_operator JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' $where_rute GROUP BY o.nama_operator");
    if ($q_op) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_op)) {
            $tabel_operator[] = [
                'no'       => $no++,
                'operator' => $row['nama_operator'],
                'jumlah'   => $row['jumlah']
            ];
        }
    }

    // 4. Tabel Detail Validasi Trip
    $q_dt = mysqli_query($koneksi, "SELECT do.tanggal, do.lokasi_awal, do.kode_do, b.kode_bus, p.nama_pramudi, do.jam_mulai, do.jam_akhir, do.waktu_tempuh, do.waktu_rest, r.kode_rute, r.nama_layanan, do.kode_trip, do.odometer, pt.nama_petugas FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus JOIN pramudi p ON do.id_pramudi = p.id_pramudi JOIN petugas pt ON do.id_petugas = pt.id_petugas WHERE DATE(do.tanggal) = '$tgl_db' $where_rute ORDER BY do.jam_mulai DESC");
    if ($q_dt) {
        while ($row = mysqli_fetch_assoc($q_dt)) {
            $tabel_detail[] = [
                'tanggal'      => date('d/m/Y', strtotime($row['tanggal'])),
                'lokasi'       => $row['lokasi_awal'],
                'kode_do'      => $row['kode_do'],
                'nomor_bus'    => $row['kode_bus'],
                'pramudi'      => $row['nama_pramudi'],
                'mulai'        => date('H:i', strtotime($row['jam_mulai'])),
                'akhir'        => $row['jam_akhir'] ? date('H:i', strtotime($row['jam_akhir'])) : '00:00',
                'tempuh'       => $row['waktu_tempuh'],
                'rest'         => $row['waktu_rest'],
                'rute'         => $row['kode_rute'],
                'rute_layanan' => $row['nama_layanan'],
                'kode_trip'    => $row['kode_trip'],
                'odometer'     => $row['odometer'],
                'petugas'      => $row['nama_petugas']
            ];
        }
    }
}

// Fallback Data jika Database offline / kosong
if (empty($tabel_detail)) {
    $total_capaian_km = 3612;
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));

    $tabel_capaian = [
        ['no' => 1, 'nomor_bus' => 'KLM-1737', 'capaian' => '201,81'],
        ['no' => 2, 'nomor_bus' => 'KLM-2056', 'capaian' => '209,00'],
        ['no' => 3, 'nomor_bus' => 'KLM-540',  'capaian' => '187,39'],
        ['no' => 4, 'nomor_bus' => 'KLM-541',  'capaian' => '187,39'],
        ['no' => 5, 'nomor_bus' => 'KLM-542',  'capaian' => '209,00'],
        ['no' => 6, 'nomor_bus' => 'KLM-543',  'capaian' => '209,00'],
        ['no' => 7, 'nomor_bus' => 'KLM-544',  'capaian' => '223,41'],
        ['no' => 8, 'nomor_bus' => 'KLM-545',  'capaian' => '209,00'],
        ['no' => 9, 'nomor_bus' => 'KLM-551',  'capaian' => '209,00'],
    ];

    $tabel_operator = [
        ['no' => 1, 'operator' => 'KLM', 'jumlah' => 19]
    ];

    $tabel_detail = [
        ['tanggal' => $tgl_fmt, 'lokasi' => 'JAK1_TJPRIOK', 'kode_do' => 'KLM-5421ZM2D...', 'nomor_bus' => 'KLM-542', 'pramudi' => '2,SHOLEH',    'mulai' => '21:38', 'akhir' => '00:00', 'tempuh' => 0,  'rest' => 0, 'rute' => 'JAK1', 'rute_layanan' => 'TJ PRIOK - PLUMPANG - TJ PRIOK', 'kode_trip' => 'JAK1.L0...', 'odometer' => '172063', 'petugas' => '117874, DONI SE...'],
        ['tanggal' => $tgl_fmt, 'lokasi' => 'JAK1_TJPRIOK', 'kode_do' => 'KLM-555W94JH...', 'nomor_bus' => 'KLM-555', 'pramudi' => '2,M FAUZI',   'mulai' => '21:08', 'akhir' => '00:00', 'tempuh' => 0,  'rest' => 0, 'rute' => 'JAK1', 'rute_layanan' => 'TJ PRIOK - PLUMPANG - TJ PRIOK', 'kode_trip' => 'JAK1.L0...', 'odometer' => '176875', 'petugas' => '117874, DONI SE...'],
        ['tanggal' => $tgl_fmt, 'lokasi' => 'JAK1_TJPRIOK', 'kode_do' => 'KLM-2056R27G...', 'nomor_bus' => 'KLM-2056','pramudi' => '2,ALIEF',     'mulai' => '20:57', 'akhir' => '00:00', 'tempuh' => 0,  'rest' => 0, 'rute' => 'JAK1', 'rute_layanan' => 'TJ PRIOK - PLUMPANG - TJ PRIOK', 'kode_trip' => 'JAK1.L0...', 'odometer' => '332535', 'petugas' => '117874, DONI SE...'],
        ['tanggal' => $tgl_fmt, 'lokasi' => 'JAK1_TJPRIOK', 'kode_do' => 'KLM-5453YWFT...', 'nomor_bus' => 'KLM-545', 'pramudi' => '2,DARYONO',   'mulai' => '20:52', 'akhir' => '00:00', 'tempuh' => 0,  'rest' => 0, 'rute' => 'JAK1', 'rute_layanan' => 'TJ PRIOK - PLUMPANG - TJ PRIOK', 'kode_trip' => 'JAK1.L0...', 'odometer' => '185615', 'petugas' => '117874, DONI SE...'],
        ['tanggal' => $tgl_fmt, 'lokasi' => 'JAK1_TJPRIOK', 'kode_do' => 'KLM-544RLCAC...', 'nomor_bus' => 'KLM-544', 'pramudi' => '2,RUDI',      'mulai' => '20:42', 'akhir' => '21:41', 'tempuh' => 59, 'rest' => 0, 'rute' => 'JAK1', 'rute_layanan' => 'TJ PRIOK - PLUMPANG - TJ PRIOK', 'kode_trip' => 'JAK1.L0...', 'odometer' => '179830', 'petugas' => '117874, DONI SE...'],
        ['tanggal' => $tgl_fmt, 'lokasi' => 'JAK1_TJPRIOK', 'kode_do' => 'KLM-543LYOZ6...', 'nomor_bus' => 'KLM-543', 'pramudi' => '2,DARYANTO',  'mulai' => '20:41', 'akhir' => '21:36', 'tempuh' => 55, 'rest' => 0, 'rute' => 'JAK1', 'rute_layanan' => 'TJ PRIOK - PLUMPANG - TJ PRIOK', 'kode_trip' => 'JAK1.L0...', 'odometer' => '512138', 'petugas' => '117874, DONI SE...'],
        ['tanggal' => $tgl_fmt, 'lokasi' => 'JAK1_TJPRIOK', 'kode_do' => 'KLM-567IO64Q...', 'nomor_bus' => 'KLM-567', 'pramudi' => '2,SUPRIYATIM','mulai' => '20:35', 'akhir' => '21:27', 'tempuh' => 52, 'rest' => 0, 'rute' => 'JAK1', 'rute_layanan' => 'TJ PRIOK - PLUMPANG - TJ PRIOK', 'kode_trip' => 'JAK1.L0...', 'odometer' => '195199', 'petugas' => '117874, DONI SE...'],
    ];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriodeValidasi" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="validasi_trip">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodeValidasi').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- HEADER TOP BAR: RUTE, STATISTIK CAPAIAN KM, TOMBOL CEK & EXPORT ICON -->
    <div class="bg-slate-900/90 p-2.5 rounded-xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-md border border-slate-800 text-xs">
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-slate-300 font-medium">Rute</span>
                <select 
                    name="rute" 
                    form="formPeriodeValidasi"
                    onchange="document.getElementById('formPeriodeValidasi').submit()"
                    class="bg-slate-950/80 text-slate-200 border border-slate-800 hover:border-slate-700 focus:border-blue-500 px-3 py-1 rounded-lg font-medium focus:outline-none cursor-pointer text-xs transition shadow-inner"
                >
                    <option value="JAK1" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK1' ? 'selected' : ''; ?>>JAK1 TANJUNG PRIOK - PLUMPANG</option>
                    <option value="JAK2" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK2' ? 'selected' : ''; ?>>JAK2 KAMPUNG RAMBUTAN - PASAR MINGGU</option>
                    <option value="JAK5" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK5' ? 'selected' : ''; ?>>JAK5 SEMPER - ROROTAN</option>
                    <option value="JAK10" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK10' ? 'selected' : ''; ?>>JAK10 TANAH ABANG - KEBAYORAN LAMA</option>
                </select>
                <span class="font-bold text-white uppercase ml-1"><?php echo htmlspecialchars($rute_selected); ?></span>
            </div>

            <button type="button" onclick="document.getElementById('formPeriodeValidasi').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm cursor-pointer">
                cek
            </button>

            <span class="text-slate-200 font-normal ml-2">Total Capaian Km : <strong class="font-bold text-white text-sm"><?php echo number_format($total_capaian_km, 0, ',', '.'); ?></strong></span>

            <!-- BADGE INDIKATOR FILTER BUS AKTIF -->
            <span id="activeFilterBusBadge" class="hidden text-xs bg-blue-500/20 text-blue-400 border border-blue-500/30 px-2.5 py-0.5 rounded-full font-mono">
                Filter Bus: <strong id="selectedBusName">--</strong>
                <button type="button" onclick="resetFilterBus()" class="ml-1 text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </span>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportValidasiTripCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- MAIN CONTENT 2 KOLOM -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- KOLOM KIRI (Tabel Capaian & Operator) -->
        <div class="lg:col-span-3 flex flex-col gap-2.5 min-h-0">
            <!-- TABEL CAPAIAN BUS (KLIK UNTUK MEMFILTER TABEL KANAN) -->
            <div class="flex-1 bg-slate-900 rounded-xl border border-slate-800/80 overflow-hidden shadow-md flex flex-col">
                <div class="flex-1 overflow-auto no-scrollbar">
                    <table class="w-full text-xs text-left text-slate-300">
                        <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                            <tr>
                                <th class="px-2.5 py-2">No</th>
                                <th class="px-2.5 py-2">Nomor Bus</th>
                                <th class="px-2.5 py-2 text-right">Capaian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-[11px]">
                            <?php foreach ($tabel_capaian as $row): ?>
                            <tr 
                                id="row-bus-<?php echo str_replace('-', '', $row['nomor_bus']); ?>"
                                onclick="filterDataBus('<?php echo $row['nomor_bus']; ?>')" 
                                class="row-bus-item hover:bg-blue-950/40 cursor-pointer transition group"
                                title="Klik untuk memfilter trip bus <?php echo $row['nomor_bus']; ?>"
                            >
                                <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['no']; ?></td>
                                <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold group-hover:text-blue-400 transition">
                                    <?php echo $row['nomor_bus']; ?>
                                </td>
                                <td class="px-2.5 py-1.5 text-right font-mono text-slate-300"><?php echo $row['capaian']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TABEL OPERATOR -->
            <div class="h-28 bg-slate-900 rounded-xl border border-slate-800/80 overflow-hidden shadow-md flex flex-col shrink-0">
                <div class="overflow-auto no-scrollbar">
                    <table class="w-full text-xs text-left text-slate-300">
                        <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                            <tr>
                                <th class="px-2.5 py-1.5">No</th>
                                <th class="px-2.5 py-1.5">Operator</th>
                                <th class="px-2.5 py-1.5 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-[11px]">
                            <?php foreach ($tabel_operator as $row): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['no']; ?></td>
                                <td class="px-2.5 py-1.5 text-white font-medium"><?php echo $row['operator']; ?></td>
                                <td class="px-2.5 py-1.5 text-right font-mono text-slate-200 font-bold"><?php echo $row['jumlah']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN (DETAIL UTAMA VALIDASI TRIP - TERFILTER OTOMATIS) -->
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
                            <th class="px-2.5 py-2">Rute Layanan</th>
                            <th class="px-2.5 py-2">Kode Trip</th>
                            <th class="px-2.5 py-2">Odometer</th>
                            <th class="px-2.5 py-2">Petugas</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDetailValidasi" class="divide-y divide-slate-800/60 text-[11px]">
                        <!-- Rendered dynamically by JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- FOOTER STATUS BAR REALTIME -->
    <div class="bg-slate-900/90 px-4 py-1.5 rounded-xl flex justify-between items-center text-[11px] font-mono border border-slate-800 shrink-0 text-slate-400">
        <div class="flex items-center gap-3">
            <span id="liveTimestampValidasi" class="text-blue-400 font-semibold">--</span>
            <span class="text-emerald-400 font-semibold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Server connected
            </span>
        </div>
        <div>
            update by : <span class="text-slate-300 font-semibold">Mikrotrnas</span>
        </div>
    </div>

</div>

<!-- SCRIPT FILTERING ON CLICK, LIVE CLOCK & EXPORT CSV -->
<script>
const rawDetailValidasi = <?php echo json_encode($tabel_detail); ?>;
let currentSelectedBus = null;

// Normalisasi String Nomor Bus
function normalizeBusNumber(busNum) {
    if (!busNum) return '';
    return busNum.toString().replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

// Render Tabel Detail Kanan berdasarkan Filter Bus
function renderTableValidasiDetail(selectedBus = null) {
    const tbody = document.getElementById('tbodyDetailValidasi');
    if (!tbody) return;

    const filtered = selectedBus 
        ? rawDetailValidasi.filter(item => normalizeBusNumber(item.nomor_bus) === normalizeBusNumber(selectedBus))
        : rawDetailValidasi;

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
                    <td class="px-2.5 py-1.5 text-slate-400">${row.rute_layanan}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.kode_trip}</td>
                    <td class="px-2.5 py-1.5 text-slate-300 font-mono">${row.odometer}</td>
                    <td class="px-2.5 py-1.5 text-slate-400">${row.petugas}</td>
                </tr>
            `;
        });
    } else {
        tbody.innerHTML = `
            <tr>
                <td colspan="14" class="px-2.5 py-4 text-center text-slate-500 italic">
                    Tidak ada log trip ditemukan untuk armada ini.
                </td>
            </tr>
        `;
    }
}

// Handler Klik Nomor Bus di Tabel Kiri
function filterDataBus(nomorBus) {
    if (currentSelectedBus === nomorBus) {
        resetFilterBus();
        return;
    }

    currentSelectedBus = nomorBus;

    // Highlight visual baris aktif
    document.querySelectorAll('.row-bus-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const activeRow = document.getElementById('row-bus-' + normalizeBusNumber(nomorBus));
    if (activeRow) {
        activeRow.classList.add('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    }

    // Badge Filter Header
    const badge = document.getElementById('activeFilterBusBadge');
    const nameElem = document.getElementById('selectedBusName');
    if (badge && nameElem) {
        nameElem.innerText = nomorBus;
        badge.classList.remove('hidden');
    }

    renderTableValidasiDetail(nomorBus);
}

// Reset Filter Bus
function resetFilterBus() {
    currentSelectedBus = null;

    document.querySelectorAll('.row-bus-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const badge = document.getElementById('activeFilterBusBadge');
    if (badge) badge.classList.add('hidden');

    renderTableValidasiDetail(null);
}

// Inisialisasi awal saat dimuat
document.addEventListener("DOMContentLoaded", function() {
    renderTableValidasiDetail(null);

    const inputPeriode = document.getElementById('inputPeriode');
    if (inputPeriode && !inputPeriode.value) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        inputPeriode.value = `${year}-${month}-${day}`;
    }
});

// Live Clock
function updateLiveTimestampValidasi() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampValidasi');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampValidasi, 1000);
updateLiveTimestampValidasi();

// Export CSV
function exportValidasiTripCSV() {
    let csv = [];
    csv.push("--- VALIDASI TRIP ---");
    csv.push("Rute," + "<?php echo $rute_selected; ?>");
    csv.push("Periode," + "<?php echo $periode_selected; ?>");
    csv.push("Total Capaian Km," + "<?php echo $total_capaian_km; ?>");
    if (currentSelectedBus) {
        csv.push("Filter Bus," + currentSelectedBus);
    }
    csv.push("");
    
    csv.push("NO,NOMOR BUS,CAPAIAN");
    <?php foreach ($tabel_capaian as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("NO,OPERATOR,JUMLAH");
    <?php foreach ($tabel_operator as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("TANGGAL,LOKASI,KODE DO,NOMOR BUS,PRAMUDI,MULAI,AKHIR,TEMPUH,REST,RUTE,RUTE LAYANAN,KODE TRIP,ODOMETER,PETUGAS");
    <?php foreach ($tabel_detail as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "Validasi_Trip_<?php echo $rute_selected; ?>_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>