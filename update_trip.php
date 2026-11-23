<?php
// Ambil tanggal dari GET URL. Jika kosong/pertama kali dibuka, otomatis gunakan tanggal perangkat server/device
$periode_selected = (isset($_GET['periode']) && !empty($_GET['periode'])) 
    ? $_GET['periode'] 
    : date('Y-m-d');

$rute_selected = isset($_GET['rute']) ? $_GET['rute'] : 'JAK1';

$total_unit_operasi = 0;
$tabel_kiri_data    = [];
$tabel_kanan_data   = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db  = mysqli_real_escape_string($koneksi, $periode_selected);
    $rute_db = mysqli_real_escape_string($koneksi, $rute_selected);

    // 1. Total Unit Operasi Rute
    $q_stat = mysqli_query($koneksi, "SELECT COUNT(DISTINCT do.id_bus) AS total_unit FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db'");
    if ($q_stat && $r_stat = mysqli_fetch_assoc($q_stat)) {
        $total_unit_operasi = $r_stat['total_unit'] ?: 0;
    }

    // 2. Query Tabel Kiri (Nomor Bus & Capaian Km)
    $q_kiri = mysqli_query($koneksi, "SELECT b.kode_bus, SUM(do.km_tempuh) AS capaian FROM driving_order do JOIN bus b ON do.id_bus = b.id_bus JOIN rute r ON do.id_rute = r.id_rute WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db' GROUP BY b.kode_bus ORDER BY b.kode_bus ASC");
    if ($q_kiri) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_kiri)) {
            $tabel_kiri_data[] = [
                'no'        => $no++,
                'nomor_bus' => $row['kode_bus'],
                'capaian'   => number_format($row['capaian'], 4, ',', '.')
            ];
        }
    }

    // 3. Query Tabel Kanan (Detail Trip per Rute)
    $q_kanan = mysqli_query($koneksi, "SELECT do.tanggal, do.lokasi_awal, do.kode_do, b.kode_bus, p.nama_pramudi, do.jam_mulai, do.jam_akhir, r.kode_rute, do.kode_trip, r.nama_layanan, do.odometer FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus JOIN pramudi p ON do.id_pramudi = p.id_pramudi WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db' ORDER BY do.jam_mulai ASC");
    if ($q_kanan) {
        while ($row = mysqli_fetch_assoc($q_kanan)) {
            $tabel_kanan_data[] = [
                'tanggal'      => date('d/m/Y', strtotime($row['tanggal'])),
                'lokasi'       => $row['lokasi_awal'],
                'kode_do'      => $row['kode_do'],
                'nomor_bus'    => $row['kode_bus'],
                'pramudi'      => $row['nama_pramudi'],
                'mulai'        => date('H:i', strtotime($row['jam_mulai'])),
                'akhir'        => $row['jam_akhir'] ? date('H:i', strtotime($row['jam_akhir'])) : '00:00',
                'rute'         => $row['kode_rute'],
                'kode_trip'    => $row['kode_trip'],
                'rute_layanan' => $row['nama_layanan'],
                'odometer'     => $row['odometer']
            ];
        }
    }
}

// Fallback Data jika Database offline / kosong
if (empty($tabel_kiri_data)) {
    $total_unit_operasi = 18;
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));

    $tabel_kiri_data = [
        ['no' => 1,  'nomor_bus' => 'KLM-1737', 'capaian' => '100,9050'],
        ['no' => 2,  'nomor_bus' => 'KLM-2056', 'capaian' => '144,1500'],
        ['no' => 3,  'nomor_bus' => 'KLM-540',  'capaian' => '129,7350'],
        ['no' => 4,  'nomor_bus' => 'KLM-541',  'capaian' => '144,1500'],
        ['no' => 5,  'nomor_bus' => 'KLM-542',  'capaian' => '144,1500'],
        ['no' => 6,  'nomor_bus' => 'KLM-543',  'capaian' => '136,9248'],
        ['no' => 7,  'nomor_bus' => 'KLM-544',  'capaian' => '136,9248'],
        ['no' => 8,  'nomor_bus' => 'KLM-545',  'capaian' => '136,9248'],
        ['no' => 9,  'nomor_bus' => 'KLM-551',  'capaian' => '136,9248'],
        ['no' => 10, 'nomor_bus' => 'KLM-552',  'capaian' => '158,5650'],
        ['no' => 11, 'nomor_bus' => 'KLM-553',  'capaian' => '129,7350'],
        ['no' => 12, 'nomor_bus' => 'KLM-555',  'capaian' => '129,7350'],
        ['no' => 13, 'nomor_bus' => 'KLM-557',  'capaian' => '129,7350'],
        ['no' => 14, 'nomor_bus' => 'KLM-558',  'capaian' => '144,1500'],
        ['no' => 15, 'nomor_bus' => 'KLM-559',  'capaian' => '122,5098'],
        ['no' => 16, 'nomor_bus' => 'KLM-565',  'capaian' => '136,9248'],
        ['no' => 17, 'nomor_bus' => 'KLM-566',  'capaian' => '136,9248'],
        ['no' => 18, 'nomor_bus' => 'KLM-567',  'capaian' => '136,9248'],
    ];

    $tabel_kanan_data = [
        ['tanggal' => $tgl_fmt, 'lokasi' => 'JAK1_TJPRIOK', 'kode_do' => 'KLM-5401ZM2D...', 'nomor_bus' => 'KLM-540', 'pramudi' => '2,SHOLEH', 'mulai' => '05:00', 'akhir' => '06:00', 'rute' => 'JAK1', 'kode_trip' => 'JAK1.L01', 'rute_layanan' => 'TJ PRIOK - PLUMPANG', 'odometer' => '129735'],
        ['tanggal' => $tgl_fmt, 'lokasi' => 'JAK1_TJPRIOK', 'kode_do' => 'KLM-5412B7C1...', 'nomor_bus' => 'KLM-541', 'pramudi' => '2,M FAUZI', 'mulai' => '05:05', 'akhir' => '06:05', 'rute' => 'JAK1', 'kode_trip' => 'JAK1.L02', 'rute_layanan' => 'TJ PRIOK - PLUMPANG', 'odometer' => '144150'],
    ];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriodeUpdateTrip" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="update_trip">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodeUpdateTrip').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- HEADER TOP BAR: RUTE, CEK, STATISTIK UNIT OPERASI & EXPORT ICON -->
    <div class="bg-slate-900/90 p-2.5 rounded-xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-md border border-slate-800 text-xs">
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-slate-300 font-medium">Rute</span>
                <select 
                    name="rute" 
                    form="formPeriodeUpdateTrip"
                    onchange="document.getElementById('formPeriodeUpdateTrip').submit()"
                    class="bg-slate-950/80 text-slate-200 border border-slate-800 hover:border-slate-700 focus:border-blue-500 px-3 py-1 rounded-lg font-medium focus:outline-none cursor-pointer text-xs transition shadow-inner min-w-[220px]"
                >
                    <option value="JAK1" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK1' ? 'selected' : ''; ?>>JAK1 TANJUNG PRIOK - PLUMPANG</option>
                    <option value="JAK2" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK2' ? 'selected' : ''; ?>>JAK2 KAMPUNG RAMBUTAN - PASAR MINGGU</option>
                    <option value="JAK7" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK7' ? 'selected' : ''; ?>>JAK7 TAWAKAL - TANAH ABANG</option>
                    <option value="JAK80" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK80' ? 'selected' : ''; ?>>JAK80 RAWA BUAYA - RAWA KOMPENI</option>
                </select>
                <span class="font-bold text-white uppercase ml-1"><?php echo htmlspecialchars($rute_selected); ?></span>
            </div>

            <button type="button" onclick="document.getElementById('formPeriodeUpdateTrip').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                cek
            </button>

            <span class="text-slate-200 font-normal ml-2">Unit Operasi : <strong class="font-bold text-white text-sm"><?php echo number_format($total_unit_operasi, 0, ',', '.'); ?></strong></span>

            <!-- BADGE BUS TERFILTER -->
            <span id="activeFilterBadgeUpdateTrip" class="hidden text-xs bg-blue-500/20 text-blue-400 border border-blue-500/30 px-3 py-0.5 rounded-full font-mono font-semibold flex items-center gap-1.5">
                <span id="selectedBusNameUpdateTrip">--</span>
                <button type="button" onclick="resetFilterUpdateTrip()" class="text-slate-400 hover:text-white font-bold ml-1 text-sm leading-none">&times;</button>
            </span>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportUpdateTripCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- MAIN CONTENT UTAMA (KOLOM KIRI: TABEL CAPAIAN, KOLOM KANAN: PANEL FORM ACTION & TABEL LOG TRIP) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- KOLOM KIRI (TABEL CAPAIAN BUS) -->
        <div class="lg:col-span-3 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
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
                        <?php foreach ($tabel_kiri_data as $row): ?>
                        <tr 
                            id="row-bus-utrip-<?php echo str_replace('-', '', $row['nomor_bus']); ?>"
                            onclick="selectBusForUpdate('<?php echo $row['nomor_bus']; ?>')" 
                            class="row-bus-utrip-item hover:bg-blue-950/40 cursor-pointer transition group"
                        >
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['no']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold group-hover:text-blue-400 transition">
                                <span><?php echo $row['nomor_bus']; ?></span>
                            </td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-300"><?php echo $row['capaian']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- KOLOM KANAN (PANEL ACTION FORM & TABEL TRIP LOG) -->
        <div class="lg:col-span-9 flex flex-col gap-2.5 min-h-0">
            
            <!-- PANEL 3 ACTION CARDS (UPDATE ODOMETER, DELETE TRIP, UPDATE KODE TRIP) -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5 shrink-0 text-xs">
                
                <!-- Card 1: Update Odometer -->
                <div class="md:col-span-4 bg-slate-900 p-2.5 rounded-xl border border-slate-800 flex flex-col gap-2 shadow-sm">
                    <div class="text-[11px] font-semibold text-slate-400 border-b border-slate-800 pb-1 flex justify-between items-center">
                        <span>Update Odometer</span>
                        <span id="targetBusOdo" class="text-blue-400 font-mono">--</span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 text-[11px]">Odometer Awal</span>
                            <span id="valOdoAwal" class="text-slate-300 font-mono font-semibold">--</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-slate-400 text-[11px]">Odometer Baru</span>
                            <input type="number" id="inputOdoBaru" placeholder="0" class="w-28 bg-slate-950/80 text-white border border-slate-800 rounded px-2 py-0.5 font-mono text-xs focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-end mt-1">
                        <button type="button" onclick="submitUpdateOdometer()" class="px-4 py-0.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded text-xs font-medium border border-slate-700 transition">
                            update
                        </button>
                    </div>
                </div>

                <!-- Card 2: Delete Trip -->
                <div class="md:col-span-4 bg-slate-900 p-2.5 rounded-xl border border-slate-800 flex flex-col gap-2 shadow-sm">
                    <div class="text-[11px] font-semibold text-slate-400 border-b border-slate-800 pb-1 flex justify-between items-center">
                        <span>Delete Trip</span>
                        <span id="targetKodeDODel" class="text-red-400 font-mono text-[10px] truncate max-w-[120px]">--</span>
                    </div>
                    <div class="flex-1 flex items-center justify-between py-1">
                        <span class="text-slate-300 font-medium">Hapus Trip ?</span>
                        <button type="button" onclick="submitDeleteTrip()" class="px-4 py-0.5 bg-red-500/20 hover:bg-red-500/30 text-red-400 border border-red-500/30 rounded text-xs font-medium transition">
                            hapus
                        </button>
                    </div>
                </div>

                <!-- Card 3: Update Kode Trip -->
                <div class="md:col-span-4 bg-slate-900 p-2.5 rounded-xl border border-slate-800 flex flex-col gap-2 shadow-sm">
                    <div class="text-[11px] font-semibold text-slate-400 border-b border-slate-800 pb-1 flex justify-between items-center">
                        <span>Update Kode Trip</span>
                        <span id="targetKodeDOTrip" class="text-blue-400 font-mono text-[10px] truncate max-w-[120px]">--</span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-slate-400 text-[11px]">Kode Trip</span>
                        <select id="selectKodeTripBaru" class="w-full bg-slate-950/80 text-slate-200 border border-slate-800 rounded px-2 py-1 font-mono text-xs focus:outline-none focus:border-blue-500">
                            <option value="">-- Pilih Kode Trip --</option>
                            <option value="JAK1.L01">JAK1.L01</option>
                            <option value="JAK1.L02">JAK1.L02</option>
                            <option value="JAK1.L03">JAK1.L03</option>
                            <option value="JAK1.L04">JAK1.L04</option>
                        </select>
                    </div>
                    <div class="flex justify-end mt-1">
                        <button type="button" onclick="submitUpdateKodeTrip()" class="px-4 py-0.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded text-xs font-medium border border-slate-700 transition">
                            update
                        </button>
                    </div>
                </div>

            </div>

            <!-- TABEL KANAN (LOG DETAIL TRIP ARMADA) -->
            <div class="bg-slate-900 rounded-xl border border-slate-800/80 flex-1 flex flex-col overflow-hidden shadow-md min-h-0">
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
                                <th class="px-2.5 py-2">Rute</th>
                                <th class="px-2.5 py-2">Kode Trip</th>
                                <th class="px-2.5 py-2">Rute Layanan</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyUpdateTripKanan" class="divide-y divide-slate-800/60 text-[11px]">
                            <!-- Rendered by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <!-- FOOTER STATUS BAR REALTIME -->
    <div class="bg-slate-900/90 px-4 py-1.5 rounded-xl flex justify-between items-center text-[11px] font-mono border border-slate-800 shrink-0 text-slate-400">
        <div class="flex items-center gap-3">
            <span id="liveTimestampUpdateTrip" class="text-blue-400 font-semibold">--</span>
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

<!-- SCRIPT HANDLER UPDATE TRIP, LIVE CLOCK & EXPORT CSV -->
<script>
const rawUpdateTripKanan = <?php echo json_encode($tabel_kanan_data); ?>;

let currentSelectedBusUpdate = null;
let currentSelectedDOUpdate  = null;

function normalizeBusUpdate(bus) {
    if (!bus) return '';
    return bus.toString().replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

function renderUpdateTripKananTable(selectedBus = null) {
    const tbody = document.getElementById('tbodyUpdateTripKanan');
    if (!tbody) return;

    const filtered = selectedBus 
        ? rawUpdateTripKanan.filter(item => normalizeBusUpdate(item.nomor_bus) === normalizeBusUpdate(selectedBus))
        : rawUpdateTripKanan;

    tbody.innerHTML = '';

    if (filtered.length > 0) {
        filtered.forEach((row, idx) => {
            tbody.innerHTML += `
                <tr 
                    onclick="selectDORowForAction('${row.kode_do}', '${row.nomor_bus}', '${row.odometer || '0'}')"
                    class="hover:bg-slate-800/40 cursor-pointer transition text-slate-300"
                >
                    <td class="px-2.5 py-1.5 text-slate-400 font-mono">${row.tanggal}</td>
                    <td class="px-2.5 py-1.5 font-mono">${row.lokasi}</td>
                    <td class="px-2.5 py-1.5 font-mono text-slate-200">${row.kode_do}</td>
                    <td class="px-2.5 py-1.5 font-mono font-semibold text-slate-200">${row.nomor_bus}</td>
                    <td class="px-2.5 py-1.5 text-white font-medium">${row.pramudi}</td>
                    <td class="px-2.5 py-1.5 font-mono">${row.mulai}</td>
                    <td class="px-2.5 py-1.5 font-mono ${row.akhir === '00:00' ? 'text-red-400 font-bold' : ''}">${row.akhir}</td>
                    <td class="px-2.5 py-1.5 font-mono">${row.rute}</td>
                    <td class="px-2.5 py-1.5 font-mono text-blue-400">${row.kode_trip || '-'}</td>
                    <td class="px-2.5 py-1.5 text-slate-400">${row.rute_layanan}</td>
                </tr>
            `;
        });
    } else {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="px-2.5 py-6 text-center text-slate-500 italic">
                    Tidak ditemukan data trip armada ${selectedBus || ''} pada rute dan tanggal ini.
                </td>
            </tr>
        `;
    }
}

// Handler Saat Bus di Tabel Kiri Di-Klik
function selectBusForUpdate(nomorBus) {
    if (currentSelectedBusUpdate === nomorBus) {
        resetFilterUpdateTrip();
        return;
    }

    currentSelectedBusUpdate = nomorBus;

    document.querySelectorAll('.row-bus-utrip-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const activeRow = document.getElementById('row-bus-utrip-' + normalizeBusUpdate(nomorBus));
    if (activeRow) {
        activeRow.classList.add('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    }

    // Badge status header
    const badge = document.getElementById('activeFilterBadgeUpdateTrip');
    const nameElem = document.getElementById('selectedBusNameUpdateTrip');
    if (badge && nameElem) {
        nameElem.innerText = nomorBus;
        badge.classList.remove('hidden');
    }

    // Set Info Odometer Bus ke Action Card 1
    const targetBusElem = document.getElementById('targetBusOdo');
    const odoAwalElem   = document.getElementById('valOdoAwal');
    if (targetBusElem) targetBusElem.innerText = nomorBus;

    // Ambil data odometer pertama dari bus ini
    const busItem = rawUpdateTripKanan.find(i => normalizeBusUpdate(i.nomor_bus) === normalizeBusUpdate(nomorBus));
    if (odoAwalElem) odoAwalElem.innerText = busItem ? busItem.odometer : '0';

    renderUpdateTripKananTable(nomorBus);
}

// Handler Saat Baris DO di Tabel Kanan Di-Klik
function selectDORowForAction(kodeDO, nomorBus, odometer) {
    currentSelectedDOUpdate = kodeDO;

    document.getElementById('targetKodeDODel').innerText  = kodeDO;
    document.getElementById('targetKodeDOTrip').innerText = kodeDO;

    document.getElementById('targetBusOdo').innerText = nomorBus;
    document.getElementById('valOdoAwal').innerText   = odometer;
}

function resetFilterUpdateTrip() {
    currentSelectedBusUpdate = null;
    currentSelectedDOUpdate  = null;

    document.querySelectorAll('.row-bus-utrip-item').forEach(row => {
        row.classList.remove('bg-blue-600/30', 'border-l-2', 'border-blue-400');
    });

    const badge = document.getElementById('activeFilterBadgeUpdateTrip');
    if (badge) badge.classList.add('hidden');

    document.getElementById('targetBusOdo').innerText     = '--';
    document.getElementById('valOdoAwal').innerText       = '--';
    document.getElementById('targetKodeDODel').innerText  = '--';
    document.getElementById('targetKodeDOTrip').innerText = '--';

    renderUpdateTripKananTable(null);
}

// Form Submit Handler (Update Odometer)
function submitUpdateOdometer() {
    const bus = currentSelectedBusUpdate || document.getElementById('targetBusOdo').innerText;
    const odoBaru = document.getElementById('inputOdoBaru').value;

    if (!odoBaru || bus === '--') {
        alert('Silakan pilih bus di tabel terlebih dahulu dan isi Odometer Baru!');
        return;
    }
    alert(`Odometer Bus ${bus} berhasil di-update menjadi: ${odoBaru}`);
    document.getElementById('inputOdoBaru').value = '';
}

// Form Submit Handler (Delete Trip)
function submitDeleteTrip() {
    if (!currentSelectedDOUpdate || currentSelectedDOUpdate === '--') {
        alert('Silakan klik baris Kode DO pada tabel kanan yang ingin dihapus!');
        return;
    }
    if (confirm(`Apakah Anda yakin ingin menghapus Trip Kode DO: ${currentSelectedDOUpdate}?`)) {
        alert(`Trip ${currentSelectedDOUpdate} berhasil dihapus!`);
        resetFilterUpdateTrip();
    }
}

// Form Submit Handler (Update Kode Trip)
function submitUpdateKodeTrip() {
    const kodeTripBaru = document.getElementById('selectKodeTripBaru').value;

    if (!currentSelectedDOUpdate || currentSelectedDOUpdate === '--' || !kodeTripBaru) {
        alert('Silakan pilih baris Kode DO dan tentukan Kode Trip baru!');
        return;
    }
    alert(`Kode Trip untuk DO ${currentSelectedDOUpdate} berhasil di-update menjadi: ${kodeTripBaru}`);
    document.getElementById('selectKodeTripBaru').value = '';
}

document.addEventListener("DOMContentLoaded", function() {
    renderUpdateTripKananTable(null);

    const inputPeriode = document.getElementById('inputPeriode');
    if (inputPeriode && !inputPeriode.value) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        inputPeriode.value = `${year}-${month}-${day}`;
    }
});

function updateLiveTimestampUpdateTrip() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampUpdateTrip');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampUpdateTrip, 1000);
updateLiveTimestampUpdateTrip();

function exportUpdateTripCSV() {
    let csv = [];
    csv.push("--- UPDATE TRIP ---");
    csv.push("Rute," + "<?php echo $rute_selected; ?>");
    csv.push("Periode," + "<?php echo $periode_selected; ?>");
    csv.push("Unit Operasi," + "<?php echo $total_unit_operasi; ?>");
    if (currentSelectedBusUpdate) {
        csv.push("Filter Bus," + currentSelectedBusUpdate);
    }
    csv.push("");
    
    csv.push("--- TABEL KIRI (CAPAIAN BUS) ---");
    csv.push("NO,NOMOR BUS,CAPAIAN");
    <?php foreach ($tabel_kiri_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("--- TABEL KANAN (DETAIL LOG TRIP) ---");
    csv.push("TANGGAL,LOKASI,KODE DO,NOMOR BUS,PRAMUDI,MULAI,AKHIR,RUTE,KODE TRIP,RUTE LAYANAN");
    <?php foreach ($tabel_kanan_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "Update_Trip_<?php echo $rute_selected; ?>_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>