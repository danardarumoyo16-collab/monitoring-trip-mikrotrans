<?php
// Ambil variabel filter periode tanggal dari GET
$periode_selected = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m-d');
$petugas_selected = isset($_GET['nik']) ? $_GET['nik'] : '';

$jumlah_petugas   = 0;
$tabel_kiri_data  = [];
$tabel_kanan_data = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db = mysqli_real_escape_string($koneksi, $periode_selected);

    // Hitung Total Jumlah Petugas yang Bertugas / Terdaftar
    $q_stat = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_petugas) AS total_petugas FROM petugas");
    if ($q_stat && $r_stat = mysqli_fetch_assoc($q_stat)) {
        $jumlah_petugas = $r_stat['total_petugas'] ?: 0;
    }

    // Query Tabel Kiri (Master List Petugas)
    $q_kiri = mysqli_query($koneksi, "SELECT nik, nama_petugas FROM petugas ORDER BY nama_petugas ASC");
    if ($q_kiri) {
        $no = 1;
        while ($row = mysqli_fetch_assoc($q_kiri)) {
            $tabel_kiri_data[] = [
                'no'      => $no++,
                'nik'     => $row['nik'],
                'petugas' => $row['nama_petugas']
            ];
        }
    }

    // Query Tabel Kanan (Detail Log Activity Petugas)
    $where_nik = $petugas_selected ? "AND pt.nik = '" . mysqli_real_escape_string($koneksi, $petugas_selected) . "'" : "";
    $q_kanan = mysqli_query($koneksi, "SELECT do.tanggal, r.kode_rute, do.lokasi_awal, b.kode_bus, do.jam_berangkat, do.jam_tiba, do.created_at, r.nama_layanan, do.odometer FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus JOIN petugas pt ON do.id_petugas = pt.id_petugas WHERE DATE(do.tanggal) = '$tgl_db' $where_nik ORDER BY do.jam_berangkat ASC");
    if ($q_kanan) {
        while ($row = mysqli_fetch_assoc($q_kanan)) {
            $tabel_kanan_data[] = [
                'tanggal'         => date('d/m/Y', strtotime($row['tanggal'])),
                'rute'            => $row['kode_rute'],
                'lokasi'          => $row['lokasi_awal'],
                'nomor_bus'       => $row['kode_bus'],
                'berangkat'       => date('H:i', strtotime($row['jam_berangkat'])),
                'tiba'            => $row['jam_tiba'] ? date('H:i', strtotime($row['jam_tiba'])) : '00:00',
                'dibuat'          => $row['created_at'] ? date('H:i', strtotime($row['created_at'])) : '00:00',
                'nama_perjalanan' => $row['nama_layanan'],
                'odometer'        => $row['odometer']
            ];
        }
    }
}

// Fallback Data jika Database offline / kosong
if (empty($tabel_kiri_data)) {
    $jumlah_petugas = 246;
    $tgl_fmt = date('d/m/Y', strtotime($periode_selected));
    
    $tabel_kiri_data = [
        ['no' => 18, 'nik' => '106865', 'petugas' => 'AHMAD JABIDI'],
        ['no' => 19, 'nik' => '115410', 'petugas' => 'AHMAD JUNAIDI'],
        ['no' => 20, 'nik' => '100590', 'petugas' => 'AHMAD JUNARY'],
        ['no' => 21, 'nik' => '102977', 'petugas' => 'AHMAD LANANG FEMBR...'],
        ['no' => 22, 'nik' => '104325', 'petugas' => 'AHMAD ROFIUDDIN'],
        ['no' => 23, 'nik' => '102276', 'petugas' => 'AJAT SUDRAJAT'],
        ['no' => 24, 'nik' => '106652', 'petugas' => 'AJI LUNGGUT'],
        ['no' => 25, 'nik' => '105128', 'petugas' => 'AKHMAD ZULKARNAIN'],
        ['no' => 26, 'nik' => '102304', 'petugas' => 'ALI DWI PURNOMO'],
        ['no' => 27, 'nik' => '100904', 'petugas' => 'AMRIUS'],
        ['no' => 28, 'nik' => '102169', 'petugas' => 'ANDI'],
        ['no' => 29, 'nik' => '102144', 'petugas' => 'ANDI'],
        ['no' => 30, 'nik' => '102633', 'petugas' => 'ANDI RACHMAT'],
        ['no' => 31, 'nik' => '106295', 'petugas' => 'ANGGA ESA RAKSA'],
    ];

    $tabel_kanan_data = [
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'lokasi' => 'PLUMPANG',   'nomor_bus' => 'KLM-541',  'berangkat' => '05:00', 'tiba' => '06:10', 'dibuat' => '05:00', 'nama_perjalanan' => 'TJ PRIOK - PLUMPANG', 'odometer' => '172050'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.01', 'lokasi' => 'TJ PRIOK',   'nomor_bus' => 'KLM-558',  'berangkat' => '05:02', 'tiba' => '06:15', 'dibuat' => '05:02', 'nama_perjalanan' => 'TJ PRIOK - PLUMPANG', 'odometer' => '189200'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.02', 'lokasi' => 'KP RAMBUTAN','nomor_bus' => 'KLM-568',  'berangkat' => '05:04', 'tiba' => '06:22', 'dibuat' => '05:04', 'nama_perjalanan' => 'KP RAMBUTAN - PS MINGGU', 'odometer' => '142100'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.02', 'lokasi' => 'PS MINGGU',  'nomor_bus' => 'KLM-540',  'berangkat' => '05:07', 'tiba' => '06:30', 'dibuat' => '05:07', 'nama_perjalanan' => 'KP RAMBUTAN - PS MINGGU', 'odometer' => '156400'],
        ['tanggal' => $tgl_fmt, 'rute' => 'JAK.05', 'lokasi' => 'SEMPER',     'nomor_bus' => 'KLM-552',  'berangkat' => '05:11', 'tiba' => '06:35', 'dibuat' => '05:11', 'nama_perjalanan' => 'SEMPER - ROROTAN', 'odometer' => '210350'],
    ];
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriodePetugas" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="list_petugas">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodePetugas').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- HEADER TOP BAR: CEK, JUMLAH PETUGAS & EXPORT ICON -->
    <div class="bg-slate-900/90 p-2.5 rounded-xl flex flex-wrap justify-between items-center gap-3 shrink-0 shadow-md border border-slate-800 text-xs">
        <div class="flex items-center gap-6 flex-wrap">
            <button type="button" onclick="document.getElementById('formPeriodePetugas').submit()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                cek
            </button>
            
            <span class="text-slate-200 font-normal">Jumlah Petugas : <strong class="font-bold text-white text-sm"><?php echo number_format($jumlah_petugas, 0, ',', '.'); ?></strong></span>
        </div>

        <!-- Tombol Icon Export Download -->
        <button type="button" onclick="exportListPetugasCSV()" title="Export Data" class="p-2 bg-blue-400 hover:bg-blue-300 text-slate-950 rounded-xl transition shadow-md flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </button>
    </div>

    <!-- MAIN CONTENT 2 TABEL (KIRI MASTER PETUGAS, KANAN LOG DETAIL TRIP) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        <!-- TABEL KIRI: NO | NIK | PETUGAS -->
        <div class="lg:col-span-3 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">No</th>
                            <th class="px-2.5 py-2">Nik</th>
                            <th class="px-2.5 py-2">Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kiri_data as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition cursor-pointer">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['no']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['nik']; ?></td>
                            <td class="px-2.5 py-1.5 text-white font-medium uppercase"><?php echo $row['petugas']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL KANAN: TANGGAL | RUTE | LOKASI | NOMOR BUS | BERANGKAT | TIBA | DIBUAT | NAMA PERJALANAN | ODOMETER -->
        <div class="lg:col-span-9 bg-slate-900 rounded-xl border border-slate-800/80 flex flex-col overflow-hidden shadow-md">
            <div class="flex-1 overflow-auto no-scrollbar">
                <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                    <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                        <tr>
                            <th class="px-2.5 py-2">Tanggal</th>
                            <th class="px-2.5 py-2">Rute</th>
                            <th class="px-2.5 py-2">Lokasi</th>
                            <th class="px-2.5 py-2">Nomor Bus</th>
                            <th class="px-2.5 py-2">Berangkat</th>
                            <th class="px-2.5 py-2">Tiba</th>
                            <th class="px-2.5 py-2">Dibuat</th>
                            <th class="px-2.5 py-2">Nama Perjalanan</th>
                            <th class="px-2.5 py-2">Odometer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-[11px]">
                        <?php foreach ($tabel_kanan_data as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['rute']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['lokasi']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold"><?php echo $row['nomor_bus']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['berangkat']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['tiba']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['dibuat']; ?></td>
                            <td class="px-2.5 py-1.5 text-white font-medium"><?php echo $row['nama_perjalanan']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['odometer']; ?></td>
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
            <span id="liveTimestampPetugas" class="text-blue-400 font-semibold">--</span>
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

<!-- SCRIPT LIVE CLOCK & EXPORT CSV LIST PETUGAS -->
<script>
function updateLiveTimestampPetugas() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampPetugas');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampPetugas, 1000);
updateLiveTimestampPetugas();

function exportListPetugasCSV() {
    let csv = [];
    csv.push("--- LIST PETUGAS ---");
    csv.push("Jumlah Petugas," + "<?php echo $jumlah_petugas; ?>");
    csv.push("Periode," + "<?php echo $periode_selected; ?>");
    csv.push("");
    
    csv.push("--- DAFTAR MASTER PETUGAS ---");
    csv.push("NO,NIK,PETUGAS");
    <?php foreach ($tabel_kiri_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    csv.push("");
    csv.push("--- LOG AKTIVITAS TRIP PETUGAS ---");
    csv.push("TANGGAL,RUTE,LOKASI,NOMOR BUS,BERANGKAT,TIBA,DIBUAT,NAMA PERJALANAN,ODOMETER");
    <?php foreach ($tabel_kanan_data as $r): ?>
    csv.push("<?php echo implode(',', $r); ?>");
    <?php endforeach; ?>

    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = "List_Petugas_<?php echo $periode_selected; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>