<?php
// Ambil tanggal dari GET URL. Jika kosong/pertama kali dibuka, otomatis gunakan tanggal perangkat server/device
$periode_selected = (isset($_GET['periode']) && !empty($_GET['periode'])) 
    ? $_GET['periode'] 
    : date('Y-m-d');

$rute_selected    = isset($_GET['rute']) ? $_GET['rute'] : 'JAK1';
$petugas_nip      = isset($_GET['petugas']) ? $_GET['petugas'] : '107030';
$petugas_nama     = "107030,DANAR DARUMOYO";

$tabel_manual_do  = [];

// Jika database terhubung, ambil data dinamis dari MySQL
if (isset($koneksi) && $koneksi) {
    $tgl_db  = mysqli_real_escape_string($koneksi, $periode_selected);
    $rute_db = mysqli_real_escape_string($koneksi, $rute_selected);

    $q_mdo = mysqli_query($koneksi, "SELECT do.tanggal, do.lokasi_awal, do.kode_do, b.kode_bus, do.jam_mulai, do.jam_akhir, do.kode_trip, r.nama_layanan, do.odometer, p.nama_pramudi, pt.nama_petugas, do.reff FROM driving_order do JOIN rute r ON do.id_rute = r.id_rute JOIN bus b ON do.id_bus = b.id_bus JOIN pramudi p ON do.id_pramudi = p.id_pramudi JOIN petugas pt ON do.id_petugas = pt.id_petugas WHERE DATE(do.tanggal) = '$tgl_db' AND r.kode_rute = '$rute_db' AND do.is_manual = 1 ORDER BY do.jam_mulai DESC");
    if ($q_mdo) {
        while ($row = mysqli_fetch_assoc($q_mdo)) {
            $tabel_manual_do[] = [
                'tanggal'      => date('d/m/Y', strtotime($row['tanggal'])),
                'lokasi'       => $row['lokasi_awal'],
                'kode_do'      => $row['kode_do'],
                'nomor_bus'    => $row['kode_bus'],
                'mulai'        => date('H:i', strtotime($row['jam_mulai'])),
                'akhir'        => $row['jam_akhir'] ? date('H:i', strtotime($row['jam_akhir'])) : '00:00',
                'trip_id'      => $row['kode_trip'],
                'rute_layanan' => $row['nama_layanan'],
                'odometer'     => $row['odometer'],
                'pramudi'      => $row['nama_pramudi'],
                'petugas'      => $row['nama_petugas'],
                'reff'         => $row['reff'] ?: '-'
            ];
        }
    }
}
?>

<div class="flex-1 flex flex-col gap-3 min-h-0">
    
    <!-- BARIS PERIODE TANGGAL DI ATAS -->
    <form method="GET" action="" id="formPeriodeManualDO" class="flex items-center gap-2 text-xs shrink-0 relative z-30">
        <input type="hidden" name="page" value="manual_do">
        <span class="text-slate-300 font-medium">Periode</span>
        <input 
            type="date" 
            name="periode" 
            id="inputPeriode"
            value="<?php echo $periode_selected; ?>" 
            onchange="document.getElementById('formPeriodeManualDO').submit()" 
            onclick="try{ this.showPicker(); }catch(e){}"
            class="bg-slate-900 text-white border border-slate-700 px-3 py-1 rounded-lg focus:outline-none cursor-pointer shadow-sm [color-scheme:dark]"
        >
    </form>

    <!-- FORM INPUT MANUAL DO CARD -->
    <div class="bg-slate-900/90 p-3 rounded-xl border border-slate-800 text-xs shadow-md shrink-0 flex flex-col gap-3">
        
        <!-- Header Baris Rute & Petugas -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-2 border-b border-slate-800/80">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-slate-300 font-medium">Rute</span>
                <select 
                    name="rute" 
                    form="formPeriodeManualDO"
                    onchange="document.getElementById('formPeriodeManualDO').submit()"
                    class="bg-slate-950/80 text-slate-200 border border-slate-800 hover:border-slate-700 focus:border-blue-500 px-3 py-1 rounded-lg font-medium focus:outline-none cursor-pointer text-xs transition shadow-inner min-w-[220px]"
                >
                    <option value="JAK1" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK1' ? 'selected' : ''; ?>>JAK1 TANJUNG PRIOK - PLUMPANG</option>
                    <option value="JAK2" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK2' ? 'selected' : ''; ?>>JAK2 KAMPUNG RAMBUTAN - PASAR MINGGU</option>
                    <option value="JAK7" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK7' ? 'selected' : ''; ?>>JAK7 TAWAKAL - TANAH ABANG</option>
                    <option value="JAK80" class="bg-slate-900 text-white" <?php echo $rute_selected == 'JAK80' ? 'selected' : ''; ?>>JAK80 RAWA BUAYA - RAWA KOMPENI</option>
                </select>
                <span class="font-bold text-white uppercase"><?php echo htmlspecialchars($rute_selected); ?></span>

                <div class="flex items-center gap-2 ml-4">
                    <span class="text-slate-300 font-medium">Petugas</span>
                    <input 
                        type="text" 
                        name="petugas" 
                        form="formPeriodeManualDO"
                        value="<?php echo htmlspecialchars($petugas_nip); ?>" 
                        class="bg-slate-950/80 text-white border border-slate-800 px-3 py-1 rounded-lg w-28 focus:outline-none font-mono text-xs"
                    >
                </div>
            </div>

            <button type="button" onclick="resetFormManualDO()" class="px-5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                batal
            </button>
        </div>

        <!-- Area Form Input Grid -->
        <form id="formInputManualDO" onsubmit="submitManualDO(event)" class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-center">
            
            <!-- Kolom Kiri Input Form -->
            <div class="lg:col-span-10 grid grid-cols-1 md:grid-cols-12 gap-2.5 items-center">
                
                <!-- Baris 1: Nomor Bus, Tanggal, Lokasi -->
                <div class="md:col-span-4 flex items-center gap-2">
                    <span class="text-slate-400 w-20 shrink-0">Nomor Bus</span>
                    <select id="inputNomorBus" required class="flex-1 bg-slate-950/80 text-slate-200 border border-slate-800 rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:border-blue-500">
                        <option value="">-- Pilih Bus --</option>
                        <option value="KLM-540">KLM-540</option>
                        <option value="KLM-541">KLM-541</option>
                        <option value="KLM-542">KLM-542</option>
                        <option value="KLM-543">KLM-543</option>
                        <option value="KLM-544">KLM-544</option>
                        <option value="KLM-545">KLM-545</option>
                        <option value="KLM-551">KLM-551</option>
                        <option value="KLM-552">KLM-552</option>
                        <option value="KLM-2056">KLM-2056</option>
                    </select>
                </div>

                <div class="md:col-span-4 flex items-center gap-2">
                    <span class="text-slate-400 w-16 shrink-0 text-right">Tanggal</span>
                    <input 
                        type="date" 
                        id="inputTanggalDO"
                        value="<?php echo $periode_selected; ?>" 
                        onclick="try{ this.showPicker(); }catch(e){}"
                        class="flex-1 bg-slate-950/80 text-white border border-slate-800 rounded-lg px-2.5 py-1 text-xs focus:outline-none [color-scheme:dark]"
                    >
                </div>

                <div class="md:col-span-4 flex items-center gap-2">
                    <span class="text-slate-400 w-16 shrink-0 text-right">Lokasi</span>
                    <select id="inputLokasi" required class="flex-1 bg-slate-950/80 text-slate-200 border border-slate-800 rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:border-blue-500">
                        <option value="">-- Pilih Lokasi --</option>
                        <option value="JAK1_TJPRIOK">JAK1_TJPRIOK</option>
                        <option value="JAK1_PLUMPANG">JAK1_PLUMPANG</option>
                        <option value="TANAHABANG">TANAHABANG</option>
                        <option value="TAWAKAL">TAWAKAL</option>
                    </select>
                </div>

                <!-- Baris 2: Waktu Awal/Akhir, Pramudi -->
                <div class="md:col-span-4 flex items-center gap-2">
                    <span class="text-slate-400 w-20 shrink-0">Awal</span>
                    <input type="time" id="inputWaktuAwal" required class="w-20 bg-slate-950/80 text-white border border-slate-800 rounded-lg px-2 py-1 text-center font-mono text-xs focus:outline-none [color-scheme:dark]">
                    <span class="text-slate-400 ml-2">Akhir</span>
                    <input type="time" id="inputWaktuAkhir" class="w-20 bg-slate-950/80 text-white border border-slate-800 rounded-lg px-2 py-1 text-center font-mono text-xs focus:outline-none [color-scheme:dark]">
                </div>

                <div class="md:col-span-8 flex items-center gap-2">
                    <span class="text-slate-400 w-16 shrink-0 text-right">Pramudi</span>
                    <input type="text" id="inputPramudi" value="0 / pramudi" placeholder="NIP / Nama Pramudi" class="flex-1 bg-slate-950/80 text-white border border-slate-800 rounded-lg px-2.5 py-1 text-xs focus:outline-none font-medium">
                </div>

                <!-- Baris 3: Odometer -->
                <div class="md:col-span-4 flex items-center gap-2">
                    <span class="text-slate-400 w-20 shrink-0">Odometer</span>
                    <input type="number" id="inputOdometer" value="0" class="flex-1 bg-slate-950/80 text-white border border-slate-800 rounded-lg px-2.5 py-1 text-xs font-mono focus:outline-none">
                </div>

                <div class="md:col-span-8"></div>

                <!-- Baris 4: Trip Id & Info Petugas -->
                <div class="md:col-span-6 flex items-center gap-2">
                    <span class="text-slate-400 w-20 shrink-0">Trip Id</span>
                    <select id="inputTripId" class="flex-1 bg-slate-950/80 text-slate-200 border border-slate-800 rounded-lg px-2.5 py-1 text-xs focus:outline-none font-mono">
                        <option value="">-- Pilih Trip Id --</option>
                        <option value="JAK1.L01">JAK1.L01</option>
                        <option value="JAK1.L02">JAK1.L02</option>
                        <option value="JAK1.L03">JAK1.L03</option>
                    </select>
                </div>

                <div class="md:col-span-6 flex items-center justify-end">
                    <span class="text-slate-300 font-mono font-medium"><?php echo $petugas_nama; ?></span>
                </div>

            </div>

            <!-- Kolom Kanan Tombol Action (Clear & Tambah) -->
            <div class="lg:col-span-2 flex flex-col gap-2 justify-center items-stretch pl-2 border-l border-slate-800/80">
                <button type="button" onclick="clearFormManualDO()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                    clear
                </button>
                <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium border border-slate-700 transition shadow-sm">
                    tambah
                </button>
            </div>

        </form>

    </div>

    <!-- MAIN CONTENT TABEL LOG MANUAL DO -->
    <div class="bg-slate-900 rounded-xl border border-slate-800/80 flex-1 flex flex-col overflow-hidden shadow-md min-h-0">
        <div class="flex-1 overflow-auto no-scrollbar">
            <table class="w-full text-xs text-left text-slate-300 whitespace-nowrap">
                <thead class="text-[11px] bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800 z-10">
                    <tr>
                        <th class="px-2.5 py-2">Tanggal</th>
                        <th class="px-2.5 py-2">Lokasi</th>
                        <th class="px-2.5 py-2">Kode DO</th>
                        <th class="px-2.5 py-2">Nomor Bus</th>
                        <th class="px-2.5 py-2">Mulai</th>
                        <th class="px-2.5 py-2">Akhir</th>
                        <th class="px-2.5 py-2">Trip Id</th>
                        <th class="px-2.5 py-2">Rute Layanan</th>
                        <th class="px-2.5 py-2 text-right">Odometer</th>
                        <th class="px-2.5 py-2">Pramudi</th>
                        <th class="px-2.5 py-2">Petugas</th>
                        <th class="px-2.5 py-2">Reff</th>
                    </tr>
                </thead>
                <tbody id="tbodyManualDO" class="divide-y divide-slate-800/60 text-[11px]">
                    <?php if (!empty($tabel_manual_do)): ?>
                        <?php foreach ($tabel_manual_do as $row): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-2.5 py-1.5 text-slate-400 font-mono"><?php echo $row['tanggal']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['lokasi']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['kode_do']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold"><?php echo $row['nomor_bus']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['mulai']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['akhir']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-300 font-mono"><?php echo $row['trip_id']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-400"><?php echo $row['rute_layanan']; ?></td>
                            <td class="px-2.5 py-1.5 text-right font-mono text-slate-300"><?php echo $row['odometer']; ?></td>
                            <td class="px-2.5 py-1.5 text-white font-medium"><?php echo $row['pramudi']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-400"><?php echo $row['petugas']; ?></td>
                            <td class="px-2.5 py-1.5 text-slate-400"><?php echo $row['reff']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- TAMPILAN HEADER BARIS KOSONG SESUAI ACUAN GAMBAR -->
                        <tr>
                            <td colspan="12" class="px-2.5 py-6 text-center text-slate-500 italic">
                                Belum ada entri Manual DO untuk rute dan tanggal ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FOOTER STATUS BAR REALTIME -->
    <div class="bg-slate-900/90 px-4 py-1.5 rounded-xl flex justify-between items-center text-[11px] font-mono border border-slate-800 shrink-0 text-slate-400">
        <div class="flex items-center gap-3">
            <span id="liveTimestampManualDO" class="text-blue-400 font-semibold">--</span>
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

<!-- SCRIPT HANDLER MANUAL DO, LIVE CLOCK & CLEAR FORM -->
<script>
// Sinkronkan input tanggal kalender dengan device user saat pertama dibuka
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

// Clear Form Input
function clearFormManualDO() {
    document.getElementById('inputNomorBus').value = '';
    document.getElementById('inputLokasi').value = '';
    document.getElementById('inputWaktuAwal').value = '';
    document.getElementById('inputWaktuAkhir').value = '';
    document.getElementById('inputPramudi').value = '0 / pramudi';
    document.getElementById('inputOdometer').value = '0';
    document.getElementById('inputTripId').value = '';
}

function resetFormManualDO() {
    clearFormManualDO();
    document.getElementById('formPeriodeManualDO').submit();
}

// Handler Submit Tambah Manual DO
function submitManualDO(e) {
    e.preventDefault();
    
    const nomorBus  = document.getElementById('inputNomorBus').value;
    const tanggal   = document.getElementById('inputTanggalDO').value;
    const lokasi    = document.getElementById('inputLokasi').value;
    const waktuAwal = document.getElementById('inputWaktuAwal').value;
    const waktuAkhir= document.getElementById('inputWaktuAkhir').value || '00:00';
    const pramudi   = document.getElementById('inputPramudi').value;
    const odometer  = document.getElementById('inputOdometer').value;
    const tripId    = document.getElementById('inputTripId').value;

    if (!nomorBus || !lokasi || !waktuAwal) {
        alert('Mohon lengkapi Nomor Bus, Lokasi, dan Waktu Awal!');
        return;
    }

    // Format Tanggal Display (DD/MM/YYYY)
    const tglParts = tanggal.split('-');
    const tglDisplay = `${tglParts[2]}/${tglParts[1]}/${tglParts[0]}`;
    
    // Generate Random Kode DO Dummy
    const kodeDO = `${nomorBus.replace('-', '')}${Math.random().toString(36).substring(2, 8).toUpperCase()}`;

    const tbody = document.getElementById('tbodyManualDO');
    
    // Hapus baris kosong jika ada
    if (tbody.innerText.includes('Belum ada entri')) {
        tbody.innerHTML = '';
    }

    // Tambah Baris Baru ke Tabel
    const newRow = `
        <tr class="hover:bg-slate-800/40 transition">
            <td class="px-2.5 py-1.5 text-slate-400 font-mono">${tglDisplay}</td>
            <td class="px-2.5 py-1.5 text-slate-300 font-mono">${lokasi}</td>
            <td class="px-2.5 py-1.5 text-slate-300 font-mono">${kodeDO}</td>
            <td class="px-2.5 py-1.5 text-slate-200 font-mono font-semibold">${nomorBus}</td>
            <td class="px-2.5 py-1.5 text-slate-300 font-mono">${waktuAwal}</td>
            <td class="px-2.5 py-1.5 text-slate-300 font-mono">${waktuAkhir}</td>
            <td class="px-2.5 py-1.5 text-slate-300 font-mono">${tripId || '-'}</td>
            <td class="px-2.5 py-1.5 text-slate-400">TJ PRIOK - PLUMPANG</td>
            <td class="px-2.5 py-1.5 text-right font-mono text-slate-300">${odometer}</td>
            <td class="px-2.5 py-1.5 text-white font-medium">${pramudi}</td>
            <td class="px-2.5 py-1.5 text-slate-400">107030, DANAR DARUMOYO</td>
            <td class="px-2.5 py-1.5 text-slate-400">MANUAL</td>
        </tr>
    `;

    tbody.insertAdjacentHTML('afterbegin', newRow);
    clearFormManualDO();
    alert('Data Manual DO berhasil ditambahkan!');
}

// Live Clock Realtime
function updateLiveTimestampManualDO() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const elem = document.getElementById('liveTimestampManualDO');
    if (elem) {
        elem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

setInterval(updateLiveTimestampManualDO, 1000);
updateLiveTimestampManualDO();
</script>