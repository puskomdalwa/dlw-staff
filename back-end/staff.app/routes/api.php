<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\ProdiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FakultasController;
use App\Http\Controllers\Api\SuratKeteranganLulusMataKuliahController;
use App\Http\Controllers\Api\MahasiswaController;
use App\Http\Controllers\Api\SuratKeteranganAdministrasiKeuanganController;
use App\Http\Controllers\Api\SuratKeteranganUjianKomprehensifDiniyahController;
use App\Http\Controllers\Api\SuratKeteranganTasmaKknPplController;
use App\Http\Controllers\Api\SuratKeteranganQismulAmanController;
use App\Http\Controllers\Api\SuratKeteranganAktifMahasiswaController;
use App\Http\Controllers\Api\SuratPernyataanVerifikasiNilaiController;
use App\Http\Controllers\Api\SuratKeteranganController;
use App\Http\Controllers\Api\SuratTugasController;
use App\Http\Controllers\Api\SuratKeteranganTransferController;
use App\Http\Controllers\Api\SuratIzinPenelitianController;
use App\Http\Controllers\Api\HasilRapatController;
use App\Http\Controllers\Api\TandaTanganController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SuratKeteranganKknController;
use App\Http\Controllers\Api\SuratKeteranganPplController;
use App\Http\Controllers\Api\TemplateIjazahController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\JenisSuratController;
use App\Http\Controllers\Api\SuratKeteranganSpmController;
use App\Http\Controllers\Api\SuratKeteranganDaftarS2Controller;
use App\Http\Controllers\Api\SuratKeteranganSudahSidangController;
use App\Http\Controllers\Api\TranskipController;
use App\Http\Controllers\Api\KategoriBerkasController;
use App\Http\Controllers\Api\BerkasController;
use App\Http\Controllers\Api\SuratKeteranganCutiController;
use App\Http\Controllers\Api\InstitusiController;
use App\Http\Controllers\Api\SuratKetetapanDosenController;
use App\Http\Controllers\Api\JadwalSidangController;
use App\Http\Controllers\Api\MahasiswaSidangController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [LoginController::class, 'login'])->name('api.login');
Route::get('/profile', [LoginController::class, 'profile'])->middleware('auth:sanctum');
Route::put('/profile', [LoginController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::post('/logout', [LoginController::class, 'logout']);
Route::get('/foto/{filename}', [LoginController::class, 'serveFoto'])->where('filename', '.*');


Route::get('/get-level', [LoginController::class, 'getLevel'])->middleware('auth:sanctum');
Route::get('/data-users', [LoginController::class, 'dataUsers'])->middleware('auth:sanctum');
Route::get('/data-users/{id}', [LoginController::class, 'show'])->middleware('auth:sanctum');
Route::put('/data-users/{id}', [LoginController::class, 'update'])->middleware('auth:sanctum');
Route::post('/data-users', [LoginController::class, 'store'])->middleware('auth:sanctum');
Route::delete('/data-users/{id}', [LoginController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/get-prodi', [LoginController::class, 'getProdi'])->middleware('auth:sanctum');
Route::get('/get-all-prodi', [LoginController::class, 'getProdi'])->middleware('auth:sanctum');
Route::get('/get-all-tanda-tangan', [LoginController::class, 'getTandaTangan'])->middleware('auth:sanctum');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth:sanctum');
Route::get('/dashboard/cards', [DashboardController::class, 'cards'])->middleware('auth:sanctum');

// Prodi Routes
Route::get('/prodi', [ProdiController::class, 'index'])->middleware('auth:sanctum');
Route::get('/prodi/{id}', [ProdiController::class, 'show'])->middleware('auth:sanctum');
Route::post('/prodi', [ProdiController::class, 'store'])->middleware('auth:sanctum');
Route::put('/prodi/{id}', [ProdiController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/prodi/{id}', [ProdiController::class, 'destroy'])->middleware('auth:sanctum');

Route::get('/fakultas', [FakultasController::class, 'index'])->middleware('auth:sanctum');
Route::get('/fakultas/{id}', [FakultasController::class, 'show'])->middleware('auth:sanctum');
Route::post('/fakultas', [FakultasController::class, 'store'])->middleware('auth:sanctum');
Route::put('/fakultas/{id}', [FakultasController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/fakultas/{id}', [FakultasController::class, 'destroy'])->middleware('auth:sanctum');

Route::get('/sklmk', [SuratKeteranganLulusMataKuliahController::class, 'index'])->middleware('auth:sanctum');
Route::get('/sklmk/{id}', [SuratKeteranganLulusMataKuliahController::class, 'show'])->middleware('auth:sanctum');
Route::post('/sklmk', [SuratKeteranganLulusMataKuliahController::class, 'store'])->middleware('auth:sanctum');
Route::put('/sklmk/{id}', [SuratKeteranganLulusMataKuliahController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/sklmk/{id}', [SuratKeteranganLulusMataKuliahController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/get-prodi', [SuratKeteranganLulusMataKuliahController::class, 'getProdi'])->middleware('auth:sanctum');
// Route::get('/get-mhs', [MahasiswaController::class, 'search'])->middleware('auth:sanctum');
// Route::get('/get-mhss/{search}', [MahasiswaController::class, 'search']);

// Mahasiswa CRUD Routes
Route::get('/mahasiswa', [MahasiswaController::class, 'index'])->middleware('auth:sanctum');
Route::get('/mahasiswa/{id}', [MahasiswaController::class, 'show'])->middleware('auth:sanctum');
Route::post('/mahasiswa', [MahasiswaController::class, 'store'])->middleware('auth:sanctum');
Route::put('/mahasiswa/{id}', [MahasiswaController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/mahasiswa/{id}', [MahasiswaController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/mahasiswa/import', [MahasiswaController::class, 'import'])->middleware('auth:sanctum');
Route::get('/get-mhs', [MahasiswaController::class, 'search'])->middleware('auth:sanctum');
Route::get('/download-pdf/{id}', [SuratKeteranganLulusMataKuliahController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan Ujian Komprehensif Diniyah Routes
Route::get('/skukd', [SuratKeteranganUjianKomprehensifDiniyahController::class, 'index'])->middleware('auth:sanctum');
Route::get('/skukd/{id}', [SuratKeteranganUjianKomprehensifDiniyahController::class, 'show'])->middleware('auth:sanctum');
Route::post('/skukd', [SuratKeteranganUjianKomprehensifDiniyahController::class, 'store'])->middleware('auth:sanctum');
Route::put('/skukd/{id}', [SuratKeteranganUjianKomprehensifDiniyahController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/skukd/{id}', [SuratKeteranganUjianKomprehensifDiniyahController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/skukd/download-pdf/{id}', [SuratKeteranganUjianKomprehensifDiniyahController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan Administrasi Keuangan Routes
Route::get('/skak', [SuratKeteranganAdministrasiKeuanganController::class, 'index'])->middleware('auth:sanctum');
Route::get('/skak/{id}', [SuratKeteranganAdministrasiKeuanganController::class, 'show'])->middleware('auth:sanctum');
Route::post('/skak', [SuratKeteranganAdministrasiKeuanganController::class, 'store'])->middleware('auth:sanctum');
Route::put('/skak/{id}', [SuratKeteranganAdministrasiKeuanganController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/skak/{id}', [SuratKeteranganAdministrasiKeuanganController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/skak/download-pdf/{id}', [SuratKeteranganAdministrasiKeuanganController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan TASMA, KKN, PPL Routes
Route::get('/sktkp', [SuratKeteranganTasmaKknPplController::class, 'index'])->middleware('auth:sanctum');
Route::get('/sktkp/{id}', [SuratKeteranganTasmaKknPplController::class, 'show'])->middleware('auth:sanctum');
Route::post('/sktkp', [SuratKeteranganTasmaKknPplController::class, 'store'])->middleware('auth:sanctum');
Route::put('/sktkp/{id}', [SuratKeteranganTasmaKknPplController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/sktkp/{id}', [SuratKeteranganTasmaKknPplController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/sktkp/download-pdf/{id}', [SuratKeteranganTasmaKknPplController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan Qismul Aman Routes
Route::get('/skqa', [SuratKeteranganQismulAmanController::class, 'index'])->middleware('auth:sanctum');
Route::get('/skqa/{id}', [SuratKeteranganQismulAmanController::class, 'show'])->middleware('auth:sanctum');
Route::post('/skqa', [SuratKeteranganQismulAmanController::class, 'store'])->middleware('auth:sanctum');
Route::put('/skqa/{id}', [SuratKeteranganQismulAmanController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/skqa/{id}', [SuratKeteranganQismulAmanController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/skqa/download-pdf/{id}', [SuratKeteranganQismulAmanController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan Aktif Mahasiswa Routes
Route::get('/skam', [SuratKeteranganAktifMahasiswaController::class, 'index'])->middleware('auth:sanctum');
Route::get('/skam/{id}', [SuratKeteranganAktifMahasiswaController::class, 'show'])->middleware('auth:sanctum');
Route::post('/skam', [SuratKeteranganAktifMahasiswaController::class, 'store'])->middleware('auth:sanctum');
Route::put('/skam/{id}', [SuratKeteranganAktifMahasiswaController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/skam/{id}', [SuratKeteranganAktifMahasiswaController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/skam/download-pdf/{id}', [SuratKeteranganAktifMahasiswaController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan SPM Routes
Route::get('/spm', [SuratKeteranganSpmController::class, 'index'])->middleware('auth:sanctum');
Route::get('/spm/{id}', [SuratKeteranganSpmController::class, 'show'])->middleware('auth:sanctum');
Route::post('/spm', [SuratKeteranganSpmController::class, 'store'])->middleware('auth:sanctum');
Route::put('/spm/{id}', [SuratKeteranganSpmController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/spm/{id}', [SuratKeteranganSpmController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/spm/download-pdf/{id}', [SuratKeteranganSpmController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan Daftar S2 Routes
Route::get('/skds2', [SuratKeteranganDaftarS2Controller::class, 'index'])->middleware('auth:sanctum');
Route::get('/skds2/{id}', [SuratKeteranganDaftarS2Controller::class, 'show'])->middleware('auth:sanctum');
Route::post('/skds2', [SuratKeteranganDaftarS2Controller::class, 'store'])->middleware('auth:sanctum');
Route::put('/skds2/{id}', [SuratKeteranganDaftarS2Controller::class, 'update'])->middleware('auth:sanctum');
Route::delete('/skds2/{id}', [SuratKeteranganDaftarS2Controller::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/skds2/download-pdf/{id}', [SuratKeteranganDaftarS2Controller::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan Sudah Sidang Routes
Route::get('/skss', [SuratKeteranganSudahSidangController::class, 'index'])->middleware('auth:sanctum');
Route::get('/skss/{id}', [SuratKeteranganSudahSidangController::class, 'show'])->middleware('auth:sanctum');
Route::post('/skss', [SuratKeteranganSudahSidangController::class, 'store'])->middleware('auth:sanctum');
Route::put('/skss/{id}', [SuratKeteranganSudahSidangController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/skss/{id}', [SuratKeteranganSudahSidangController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/skss/download-pdf/{id}', [SuratKeteranganSudahSidangController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan Cuti Routes
Route::get('/skc', [SuratKeteranganCutiController::class, 'index'])->middleware('auth:sanctum');
Route::get('/skc/{id}', [SuratKeteranganCutiController::class, 'show'])->middleware('auth:sanctum');
Route::post('/skc', [SuratKeteranganCutiController::class, 'store'])->middleware('auth:sanctum');
Route::put('/skc/{id}', [SuratKeteranganCutiController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/skc/{id}', [SuratKeteranganCutiController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/skc/download-pdf/{id}', [SuratKeteranganCutiController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Pernyataan Verifikasi Nilai Routes
Route::get('/spvn', [SuratPernyataanVerifikasiNilaiController::class, 'index'])->middleware('auth:sanctum');
Route::get('/spvn/{id}', [SuratPernyataanVerifikasiNilaiController::class, 'show'])->middleware('auth:sanctum');
Route::post('/spvn', [SuratPernyataanVerifikasiNilaiController::class, 'store'])->middleware('auth:sanctum');
Route::put('/spvn/{id}', [SuratPernyataanVerifikasiNilaiController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/spvn/{id}', [SuratPernyataanVerifikasiNilaiController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/spvn/download-pdf/{id}', [SuratPernyataanVerifikasiNilaiController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan Routes
Route::get('/surat-keterangan', [SuratKeteranganController::class, 'index'])->middleware('auth:sanctum');
Route::get('/surat-keterangan/{id}', [SuratKeteranganController::class, 'show'])->middleware('auth:sanctum');
Route::post('/surat-keterangan', [SuratKeteranganController::class, 'store'])->middleware('auth:sanctum');
Route::put('/surat-keterangan/{id}', [SuratKeteranganController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/surat-keterangan/{id}', [SuratKeteranganController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/surat-keterangan/download-pdf/{id}', [SuratKeteranganController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Tugas Routes
Route::get('/surat-tugas', [SuratTugasController::class, 'index'])->middleware('auth:sanctum');
Route::get('/surat-tugas/{id}', [SuratTugasController::class, 'show'])->middleware('auth:sanctum');
Route::post('/surat-tugas', [SuratTugasController::class, 'store'])->middleware('auth:sanctum');
Route::put('/surat-tugas/{id}', [SuratTugasController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/surat-tugas/{id}', [SuratTugasController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/surat-tugas/download-pdf/{id}', [SuratTugasController::class, 'downloadPdf'])->middleware('auth:sanctum');
Route::get('/get-dosen', [SuratTugasController::class, 'getDosen'])->middleware('auth:sanctum');

// Surat Keterangan Transfer Routes
Route::get('/surat-keterangan-transfer', [SuratKeteranganTransferController::class, 'index'])->middleware('auth:sanctum');
Route::get('/surat-keterangan-transfer/{id}', [SuratKeteranganTransferController::class, 'show'])->middleware('auth:sanctum');
Route::post('/surat-keterangan-transfer', [SuratKeteranganTransferController::class, 'store'])->middleware('auth:sanctum');
Route::put('/surat-keterangan-transfer/{id}', [SuratKeteranganTransferController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/surat-keterangan-transfer/{id}', [SuratKeteranganTransferController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/surat-keterangan-transfer/download-pdf/{id}', [SuratKeteranganTransferController::class, 'downloadPdf'])->middleware('auth:sanctum');
Route::get('/get-th-akademik', [SuratKeteranganTransferController::class, 'getThAkademik'])->middleware('auth:sanctum');

// Surat Izin Penelitian Routes
Route::get('/surat-izin-penelitian', [SuratIzinPenelitianController::class, 'index'])->middleware('auth:sanctum');
Route::get('/surat-izin-penelitian/{id}', [SuratIzinPenelitianController::class, 'show'])->middleware('auth:sanctum');
Route::post('/surat-izin-penelitian', [SuratIzinPenelitianController::class, 'store'])->middleware('auth:sanctum');
Route::put('/surat-izin-penelitian/{id}', [SuratIzinPenelitianController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/surat-izin-penelitian/{id}', [SuratIzinPenelitianController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/surat-izin-penelitian/download-pdf/{id}', [SuratIzinPenelitianController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Hasil Rapat Routes
Route::get('/hasil-rapat', [HasilRapatController::class, 'index'])->middleware('auth:sanctum');
Route::get('/hasil-rapat/download-pdf/{id}', [HasilRapatController::class, 'downloadPdf'])->middleware('auth:sanctum');
Route::get('/hasil-rapat/{id}', [HasilRapatController::class, 'show'])->middleware('auth:sanctum');
Route::post('/hasil-rapat', [HasilRapatController::class, 'store'])->middleware('auth:sanctum');
Route::put('/hasil-rapat/{id}', [HasilRapatController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/hasil-rapat/{id}', [HasilRapatController::class, 'destroy'])->middleware('auth:sanctum');

// Tanda Tangan Routes
Route::get('/tanda-tangan', [TandaTanganController::class, 'index'])->middleware('auth:sanctum');
Route::get('/tanda-tangan/{id}', [TandaTanganController::class, 'show'])->middleware('auth:sanctum');
Route::post('/tanda-tangan', [TandaTanganController::class, 'store'])->middleware('auth:sanctum');
Route::put('/tanda-tangan/{id}', [TandaTanganController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/tanda-tangan/{id}', [TandaTanganController::class, 'destroy'])->middleware('auth:sanctum');

// Surat Keterangan KKN Routes
Route::get('/skk', [SuratKeteranganKknController::class, 'index'])->middleware('auth:sanctum');
Route::get('/skk/{id}', [SuratKeteranganKknController::class, 'show'])->middleware('auth:sanctum');
Route::post('/skk', [SuratKeteranganKknController::class, 'store'])->middleware('auth:sanctum');
Route::put('/skk/{id}', [SuratKeteranganKknController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/skk/{id}', [SuratKeteranganKknController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/skk/download-pdf/{id}', [SuratKeteranganKknController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Surat Keterangan PPL Routes
Route::get('/skp', [SuratKeteranganPplController::class, 'index'])->middleware('auth:sanctum');
Route::get('/skp/{id}', [SuratKeteranganPplController::class, 'show'])->middleware('auth:sanctum');
Route::post('/skp', [SuratKeteranganPplController::class, 'store'])->middleware('auth:sanctum');
Route::put('/skp/{id}', [SuratKeteranganPplController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/skp/{id}', [SuratKeteranganPplController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/skp/download-pdf/{id}', [SuratKeteranganPplController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Template Ijazah Routes
Route::get('/template-ijazah', [TemplateIjazahController::class, 'index'])->middleware('auth:sanctum');
Route::get('/template-ijazah/{id}', [TemplateIjazahController::class, 'show'])->middleware('auth:sanctum');
Route::post('/template-ijazah', [TemplateIjazahController::class, 'store'])->middleware('auth:sanctum');
Route::put('/template-ijazah/{id}', [TemplateIjazahController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/template-ijazah/{id}', [TemplateIjazahController::class, 'destroy'])->middleware('auth:sanctum');

// Batch Routes
Route::get('/batch', [BatchController::class, 'index'])->middleware('auth:sanctum');
Route::get('/batch/{id}', [BatchController::class, 'show'])->middleware('auth:sanctum');
Route::post('/batch', [BatchController::class, 'store'])->middleware('auth:sanctum');
Route::put('/batch/{id}', [BatchController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/batch/{id}', [BatchController::class, 'destroy'])->middleware('auth:sanctum');

// Print Ijazah

Route::get('/print-ijazah/batch/{batch_id}', [\App\Http\Controllers\Api\PrintIjazahController::class, 'getBatchPrint'])->middleware('auth:sanctum');

// Jenis Surat Routes
Route::get('/jenis-surat', [JenisSuratController::class, 'index'])->middleware('auth:sanctum');
Route::get('/jenis-surat/{id}', [JenisSuratController::class, 'show'])->middleware('auth:sanctum');
Route::post('/jenis-surat', [JenisSuratController::class, 'store'])->middleware('auth:sanctum');
Route::put('/jenis-surat/{id}', [JenisSuratController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/jenis-surat/{id}', [JenisSuratController::class, 'destroy'])->middleware('auth:sanctum');

// Kategori Berkas Routes
Route::get('/kategori-berkas', [KategoriBerkasController::class, 'index'])->middleware('auth:sanctum');
Route::get('/kategori-berkas/{id}', [KategoriBerkasController::class, 'show'])->middleware('auth:sanctum');
Route::post('/kategori-berkas', [KategoriBerkasController::class, 'store'])->middleware('auth:sanctum');
Route::put('/kategori-berkas/{id}', [KategoriBerkasController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/kategori-berkas/{id}', [KategoriBerkasController::class, 'destroy'])->middleware('auth:sanctum');

// Berkas Routes
Route::get('/berkas', [BerkasController::class, 'index'])->middleware('auth:sanctum');
Route::get('/berkas/{id}', [BerkasController::class, 'show'])->middleware('auth:sanctum');
Route::post('/berkas', [BerkasController::class, 'store'])->middleware('auth:sanctum');
Route::match(['put', 'post'], '/berkas/{id}', [BerkasController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/berkas/{id}', [BerkasController::class, 'destroy'])->middleware('auth:sanctum');

// Institusi Routes
Route::get('/institusi', [InstitusiController::class, 'index'])->middleware('auth:sanctum');
Route::get('/institusi/{id}', [InstitusiController::class, 'show'])->middleware('auth:sanctum');
Route::post('/institusi', [InstitusiController::class, 'store'])->middleware('auth:sanctum');
Route::match(['put', 'post'], '/institusi/{id}', [InstitusiController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/institusi/{id}', [InstitusiController::class, 'destroy'])->middleware('auth:sanctum');

// Surat Ketetapan Dosen Routes
Route::get('/surat-ketetapan-dosen', [SuratKetetapanDosenController::class, 'index'])->middleware('auth:sanctum');
Route::get('/surat-ketetapan-dosen/{id}', [SuratKetetapanDosenController::class, 'show'])->middleware('auth:sanctum');
Route::post('/surat-ketetapan-dosen', [SuratKetetapanDosenController::class, 'store'])->middleware('auth:sanctum');
Route::put('/surat-ketetapan-dosen/{id}', [SuratKetetapanDosenController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/surat-ketetapan-dosen/{id}', [SuratKetetapanDosenController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/surat-ketetapan-dosen/download-pdf/{id}', [SuratKetetapanDosenController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Jadwal Sidang Routes
Route::get('/jadwal-sidang', [JadwalSidangController::class, 'index'])->middleware('auth:sanctum');
Route::get('/jadwal-sidang/mahasiswa', [JadwalSidangController::class, 'getMahasiswa'])->middleware('auth:sanctum');
Route::get('/jadwal-sidang/{id}', [JadwalSidangController::class, 'show'])->middleware('auth:sanctum');
Route::post('/jadwal-sidang', [JadwalSidangController::class, 'store'])->middleware('auth:sanctum');
Route::put('/jadwal-sidang/{id}', [JadwalSidangController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/jadwal-sidang/{id}', [JadwalSidangController::class, 'destroy'])->middleware('auth:sanctum');

// Mahasiswa Sidang Routes
Route::get('/mahasiswa-sidang', [MahasiswaSidangController::class, 'index'])->middleware('auth:sanctum');
Route::get('/mahasiswa-sidang/pengajuan-judul', [MahasiswaSidangController::class, 'getPengajuanJudul'])->middleware('auth:sanctum');
Route::get('/mahasiswa-sidang/{id}', [MahasiswaSidangController::class, 'show'])->middleware('auth:sanctum');
Route::post('/mahasiswa-sidang', [MahasiswaSidangController::class, 'store'])->middleware('auth:sanctum');
Route::put('/mahasiswa-sidang/{id}', [MahasiswaSidangController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/mahasiswa-sidang/{id}', [MahasiswaSidangController::class, 'destroy'])->middleware('auth:sanctum');

// Setting Jabatan Routes
Route::get('/setting-jabatan', [\App\Http\Controllers\Api\SettingJabatanController::class, 'index'])->middleware('auth:sanctum');
Route::get('/setting-jabatan/{id}', [\App\Http\Controllers\Api\SettingJabatanController::class, 'show'])->middleware('auth:sanctum');
Route::post('/setting-jabatan', [\App\Http\Controllers\Api\SettingJabatanController::class, 'store'])->middleware('auth:sanctum');
Route::put('/setting-jabatan/{id}', [\App\Http\Controllers\Api\SettingJabatanController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/setting-jabatan/{id}', [\App\Http\Controllers\Api\SettingJabatanController::class, 'destroy'])->middleware('auth:sanctum');

// SK6 Routes
Route::get('/sk6/integration-config', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'integrationConfig'])->middleware('auth:sanctum');
Route::post('/sk6/integration-config/regenerate', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'regenerateApiKey'])->middleware('auth:sanctum');
Route::get('/sk6/signer-defaults', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'getSignerDefaults'])->middleware('auth:sanctum');
Route::post('/sk6/signer-defaults', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'updateSignerDefaults'])->middleware('auth:sanctum');
Route::get('/sk6', [\App\Http\Controllers\Api\Sk6Controller::class, 'index'])->middleware('auth:sanctum');
Route::get('/sk6/{id}', [\App\Http\Controllers\Api\Sk6Controller::class, 'show'])->middleware('auth:sanctum');
Route::post('/sk6', [\App\Http\Controllers\Api\Sk6Controller::class, 'store'])->middleware('auth:sanctum');
Route::put('/sk6/{id}', [\App\Http\Controllers\Api\Sk6Controller::class, 'update'])->middleware('auth:sanctum');
Route::delete('/sk6/{id}', [\App\Http\Controllers\Api\Sk6Controller::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/sk6/download-pdf/{id}', [\App\Http\Controllers\Api\Sk6Controller::class, 'downloadPdf'])->middleware('auth:sanctum');
Route::get('/sk6/{id}/signatures', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'timeline'])->middleware('auth:sanctum');
Route::post('/sk6/{id}/signatures/{stepKey}/confirm', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'confirm'])->middleware('auth:sanctum');
Route::post('/sk6/{id}/signatures/{stepKey}/revoke', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'revoke'])->middleware('auth:sanctum');

// SK6 QR Verification (Public)
Route::get('/sk6/verify/{token}', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'verifyJson']);

// SK6 External Website Integration API (API Key Auth)
Route::prefix('external/sk6')->middleware('sk6.apikey')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'externalIndex']);
    Route::get('/by-nim/{nim}', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'externalShowByNim']);
    Route::post('/by-nim/{nim}/sign', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'externalSignByNim']);
    Route::get('/{id}', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'externalShow']);
    Route::post('/{id}/sign', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'externalSign']);
    Route::post('/{id}/revoke', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'externalRevoke']);
});

// File Manager Routes
Route::get('/file-manager/list', [\App\Http\Controllers\Api\FileManagerController::class, 'index'])->middleware('auth:sanctum');
Route::get('/file-manager/download-zip', [\App\Http\Controllers\Api\FileManagerController::class, 'downloadZip'])->middleware('auth:sanctum');

// Transkip Routes
Route::get('/transkip', [TranskipController::class, 'index'])->middleware('auth:sanctum');
Route::get('/transkip/predikat', [TranskipController::class, 'getPredikat'])->middleware('auth:sanctum');
Route::get('/transkip/{id}', [TranskipController::class, 'show'])->middleware('auth:sanctum');
Route::get('/transkip/{id}/nilai', [TranskipController::class, 'getNilai'])->middleware('auth:sanctum');
Route::get('/transkip/{id}/nilai-pddikti', [TranskipController::class, 'getNilaiPDIKTI'])->middleware('auth:sanctum');
Route::get('/transkip/{id}/pddikti', [TranskipController::class, 'getNilaiPDIKTI'])->middleware('auth:sanctum');
Route::post('/transkip/{id}/nilai/refresh', [TranskipController::class, 'refreshNilai'])->middleware('auth:sanctum');
Route::post('/transkip', [TranskipController::class, 'store'])->middleware('auth:sanctum');
Route::post('/transkip/nilai', [TranskipController::class, 'storeNilai'])->middleware('auth:sanctum');

// Pengumuman Akademik Routes
Route::get('/pengumuman-akademik', [\App\Http\Controllers\Api\PengumumanAkademikController::class, 'index'])->middleware('auth:sanctum');
Route::get('/pengumuman-akademik/{id}', [\App\Http\Controllers\Api\PengumumanAkademikController::class, 'show'])->middleware('auth:sanctum');
Route::post('/pengumuman-akademik', [\App\Http\Controllers\Api\PengumumanAkademikController::class, 'store'])->middleware('auth:sanctum');
Route::put('/pengumuman-akademik/{id}', [\App\Http\Controllers\Api\PengumumanAkademikController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/pengumuman-akademik/{id}', [\App\Http\Controllers\Api\PengumumanAkademikController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/pengumuman-akademik/download-pdf/{id}', [\App\Http\Controllers\Api\PengumumanAkademikController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Form Pengambilan Ijazah Routes (Admin)
Route::get('/form-pengambilan-ijazah/integration-config', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'integrationConfig'])->middleware('auth:sanctum');
Route::post('/form-pengambilan-ijazah/integration-config/regenerate', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'regenerateApiKey'])->middleware('auth:sanctum');
Route::get('/form-pengambilan-ijazah/signer-defaults', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'getSignerDefaults'])->middleware('auth:sanctum');
Route::post('/form-pengambilan-ijazah/signer-defaults', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'updateSignerDefaults'])->middleware('auth:sanctum');
Route::get('/form-pengambilan-ijazah', [\App\Http\Controllers\Api\FormPengambilanIjazahController::class, 'index'])->middleware('auth:sanctum');
Route::post('/form-pengambilan-ijazah/fix-gender-batch', [\App\Http\Controllers\Api\FormPengambilanIjazahController::class, 'fixGenderBatch'])->middleware('auth:sanctum');
Route::get('/form-pengambilan-ijazah/public-setting', [\App\Http\Controllers\Api\PublicFormPengambilanIjazahController::class, 'getAdminSetting'])->middleware('auth:sanctum');
Route::post('/form-pengambilan-ijazah/public-setting', [\App\Http\Controllers\Api\PublicFormPengambilanIjazahController::class, 'updateAdminSetting'])->middleware('auth:sanctum');
Route::get('/form-pengambilan-ijazah/download-pdf/{id}', [\App\Http\Controllers\Api\FormPengambilanIjazahController::class, 'downloadPdf'])->middleware('auth:sanctum');
Route::get('/form-pengambilan-ijazah/{id}/signatures', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'timeline'])->middleware('auth:sanctum');
Route::post('/form-pengambilan-ijazah/{id}/signatures/{stepKey}/confirm', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'confirm'])->middleware('auth:sanctum');
Route::post('/form-pengambilan-ijazah/{id}/signatures/{stepKey}/revoke', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'revoke'])->middleware('auth:sanctum');
Route::get('/form-pengambilan-ijazah/{id}', [\App\Http\Controllers\Api\FormPengambilanIjazahController::class, 'show'])->middleware('auth:sanctum');
Route::post('/form-pengambilan-ijazah', [\App\Http\Controllers\Api\FormPengambilanIjazahController::class, 'store'])->middleware('auth:sanctum');
Route::put('/form-pengambilan-ijazah/{id}', [\App\Http\Controllers\Api\FormPengambilanIjazahController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/form-pengambilan-ijazah/{id}', [\App\Http\Controllers\Api\FormPengambilanIjazahController::class, 'destroy'])->middleware('auth:sanctum');

// Form Pengambilan Ijazah QR Verification (Public)
Route::get('/form-pengambilan-ijazah/verify/{token}', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'verifyJson']);

// Form Pengambilan Ijazah External Website Integration API (API Key Auth)
Route::prefix('external/form-pengambilan-ijazah')->middleware('pengambilan_ijazah.apikey')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'externalIndex']);
    Route::get('/by-nim/{nim}', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'externalShowByNim']);
    Route::post('/by-nim/{nim}/sign', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'externalSignByNim']);
    Route::get('/{id}', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'externalShow']);
    Route::post('/{id}/sign', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'externalSign']);
    Route::post('/{id}/revoke', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'externalRevoke']);
});

// Form Pengambilan Ijazah Routes (Public Tanpa Login)
Route::prefix('public/form-pengambilan-ijazah')->group(function () {
    Route::get('/check/{slug?}', [\App\Http\Controllers\Api\PublicFormPengambilanIjazahController::class, 'checkPublicForm']);
    Route::get('/get-mhs', [\App\Http\Controllers\Api\PublicFormPengambilanIjazahController::class, 'searchMahasiswaPublic']);
    Route::get('/get-dosen', [\App\Http\Controllers\Api\PublicFormPengambilanIjazahController::class, 'searchDosenPublic']);
    Route::post('/submit/{slug?}', [\App\Http\Controllers\Api\PublicFormPengambilanIjazahController::class, 'submitPublic']);
    Route::get('/download/{id}', [\App\Http\Controllers\Api\PublicFormPengambilanIjazahController::class, 'downloadPdfPublic']);
});

// Pengajuan Judul Skripsi Routes
Route::get('/pengajuan-judul', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'index'])->middleware('auth:sanctum');
Route::match(['get', 'post'], '/pengajuan-judul/check-similarity', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'checkSimilarity'])->middleware('auth:sanctum');
Route::get('/pengajuan-judul/public-setting', [\App\Http\Controllers\Api\PublicFormPengajuanJudulController::class, 'getAdminSetting'])->middleware('auth:sanctum');
Route::get('/pengajuan-judul/by-nim/{nim}', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'getByNim'])->middleware('auth:sanctum');
Route::post('/pengajuan-judul/public-setting', [\App\Http\Controllers\Api\PublicFormPengajuanJudulController::class, 'updateAdminSetting'])->middleware('auth:sanctum');
Route::get('/pengajuan-judul/sudah-sidang', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'getSudahSidang'])->middleware('auth:sanctum');
Route::post('/pengajuan-judul/sudah-sidang', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'updateStatusSidang'])->middleware('auth:sanctum');
Route::get('/pengajuan-judul/{id}', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'show'])->middleware('auth:sanctum');
Route::post('/pengajuan-judul', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'store'])->middleware('auth:sanctum');
Route::put('/pengajuan-judul/{id}', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/pengajuan-judul/{id}', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/pengajuan-judul/download-pdf/{id}', [\App\Http\Controllers\Api\PengajuanJudulController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Daftar Sidang Skripsi Routes
Route::get('/daftar-sidang-skripsi/public-setting', [\App\Http\Controllers\Api\PublicFormDaftarSidangSkripsiController::class, 'getAdminSetting'])->middleware('auth:sanctum');
Route::post('/daftar-sidang-skripsi/public-setting', [\App\Http\Controllers\Api\PublicFormDaftarSidangSkripsiController::class, 'updateAdminSetting'])->middleware('auth:sanctum');
Route::prefix('daftar-sidang-skripsi')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\DaftarSidangSkripsiController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\DaftarSidangSkripsiController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\DaftarSidangSkripsiController::class, 'show']);
    Route::match(['put', 'post'], '/{id}', [\App\Http\Controllers\Api\DaftarSidangSkripsiController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\DaftarSidangSkripsiController::class, 'destroy']);
    Route::post('/{id}/upload-dokumen', [\App\Http\Controllers\Api\DaftarSidangSkripsiController::class, 'uploadDokumen']);
    Route::delete('/dokumen/{dokumenId}', [\App\Http\Controllers\Api\DaftarSidangSkripsiController::class, 'deleteDokumen']);
});

// Form Daftar Sidang Skripsi Routes (Public Tanpa Login)
Route::prefix('public/daftar-sidang-skripsi')->group(function () {
    Route::get('/check/{slug?}', [\App\Http\Controllers\Api\PublicFormDaftarSidangSkripsiController::class, 'checkPublicForm']);
    Route::get('/get-mhs', [\App\Http\Controllers\Api\PublicFormDaftarSidangSkripsiController::class, 'searchMahasiswaPublic']);
    Route::get('/get-judul/{nim}', [\App\Http\Controllers\Api\PublicFormDaftarSidangSkripsiController::class, 'getJudulByNimPublic']);
    Route::get('/get-dosen', [\App\Http\Controllers\Api\PublicFormDaftarSidangSkripsiController::class, 'searchDosenPublic']);
    Route::post('/submit/{slug?}', [\App\Http\Controllers\Api\PublicFormDaftarSidangSkripsiController::class, 'submitPublic']);
});

// SK Yudisium Routes (Public Tanpa Login)
Route::match(['get', 'post'], '/sk-yudisium/all', [\App\Http\Controllers\Api\SkYudisiumController::class, 'all']);
Route::match(['get', 'post'], '/sk-yudisium/all-data', [\App\Http\Controllers\Api\SkYudisiumController::class, 'all']);
Route::match(['get', 'post'], '/sk-yudisium/by-nim/{nim?}', [\App\Http\Controllers\Api\SkYudisiumController::class, 'getNomorSuratByNim']);
Route::match(['get', 'post'], '/sk-yudisium/nomor-surat/{nim?}', [\App\Http\Controllers\Api\SkYudisiumController::class, 'getNomorSuratByNim']);
Route::match(['get', 'post'], '/public/sk-yudisium/all', [\App\Http\Controllers\Api\SkYudisiumController::class, 'all']);
Route::match(['get', 'post'], '/public/sk-yudisium/all-data', [\App\Http\Controllers\Api\SkYudisiumController::class, 'all']);
Route::match(['get', 'post'], '/public/sk-yudisium/by-nim/{nim?}', [\App\Http\Controllers\Api\SkYudisiumController::class, 'getNomorSuratByNim']);
Route::match(['get', 'post'], '/public/sk-yudisium/nomor-surat/{nim?}', [\App\Http\Controllers\Api\SkYudisiumController::class, 'getNomorSuratByNim']);

// SK Yudisium Routes (Admin / Auth)
Route::get('/sk-yudisium', [\App\Http\Controllers\Api\SkYudisiumController::class, 'index'])->middleware('auth:sanctum');
Route::post('/sk-yudisium/import', [\App\Http\Controllers\Api\SkYudisiumController::class, 'import'])->middleware('auth:sanctum');
Route::get('/sk-yudisium/{id}/mahasiswa', [\App\Http\Controllers\Api\SkYudisiumController::class, 'getMahasiswa'])->middleware('auth:sanctum');
Route::get('/sk-yudisium/{id}', [\App\Http\Controllers\Api\SkYudisiumController::class, 'show'])->middleware('auth:sanctum');
Route::post('/sk-yudisium', [\App\Http\Controllers\Api\SkYudisiumController::class, 'store'])->middleware('auth:sanctum');
Route::put('/sk-yudisium/{id}', [\App\Http\Controllers\Api\SkYudisiumController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/sk-yudisium/{id}', [\App\Http\Controllers\Api\SkYudisiumController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/sk-yudisium/download-pdf/{id}', [\App\Http\Controllers\Api\SkYudisiumController::class, 'downloadPdf'])->middleware('auth:sanctum');

// Form Pengajuan Judul Routes (Public Tanpa Login)
Route::prefix('public/pengajuan-judul')->group(function () {
    Route::get('/check/{slug?}', [\App\Http\Controllers\Api\PublicFormPengajuanJudulController::class, 'checkPublicForm']);
    Route::match(['get', 'post'], '/check-similarity', [\App\Http\Controllers\Api\PublicFormPengajuanJudulController::class, 'checkSimilarityPublic']);
    Route::get('/get-mhs', [\App\Http\Controllers\Api\PublicFormPengajuanJudulController::class, 'searchMahasiswaPublic']);
    Route::get('/get-dosen', [\App\Http\Controllers\Api\PublicFormPengajuanJudulController::class, 'searchDosenPublic']);
    Route::post('/submit/{slug?}', [\App\Http\Controllers\Api\PublicFormPengajuanJudulController::class, 'submitPublic']);
    Route::get('/download/{id}', [\App\Http\Controllers\Api\PublicFormPengajuanJudulController::class, 'downloadPdfPublic']);
});

Route::get('/run-migration-temp', function () {
    $tables = [
        'surat_tugas',
        'surat_izin_penelitian',
        'surat_keterangan',
        'surat_keterangan_aktif_mahasiswa',
        'surat_keterangan_lulus_mata_kuliah',
        'surat_keterangan_ujian_komprehensif_diniyah',
        'surat_keterangan_administrasi_keuangan',
        'surat_keterangan_tasma_kkn_ppl',
        'surat_keterangan_kkn',
        'surat_keterangan_ppl',
        'surat_keterangan_qismul_aman',
        'surat_keterangan_spm',
        'surat_keterangan_daftar_s2',
        'surat_keterangan_transfer',
        'surat_pernyataan_verifikasi_nilai',
        'hasil_rapat',
        'pengumuman_akademik',
        'sk_yudisium',
        'surat_keterangan_6',
    ];

    $executed = [];
    foreach ($tables as $t) {
        if (\Illuminate\Support\Facades\Schema::hasTable($t)) {
            \Illuminate\Support\Facades\Schema::table($t, function (\Illuminate\Database\Schema\Blueprint $table) use ($t, &$executed) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'ketua')) {
                    $table->string('ketua')->nullable();
                    $executed[] = "Added 'ketua' to {$t}";
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn($t, 'nidn')) {
                    $table->string('nidn')->nullable();
                    $executed[] = "Added 'nidn' to {$t}";
                }
            });
        }
    }

    try {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE fakultas MODIFY tanda_tangan_id VARCHAR(255) NULL;');
    } catch (\Throwable $e) {}

    if (\Illuminate\Support\Facades\Schema::hasTable('daftar_sidang_skripsi')) {
        \Illuminate\Support\Facades\Schema::table('daftar_sidang_skripsi', function (\Illuminate\Database\Schema\Blueprint $table) use (&$executed) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('daftar_sidang_skripsi', 'dospem1')) {
                $table->dropColumn('dospem1');
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('daftar_sidang_skripsi', 'dospem2')) {
                $table->dropColumn('dospem2');
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('daftar_sidang_skripsi', 'pembimbing1')) {
                $table->string('pembimbing1')->nullable();
                $executed[] = "Added 'pembimbing1' to daftar_sidang_skripsi";
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('daftar_sidang_skripsi', 'pembimbing2')) {
                $table->string('pembimbing2')->nullable();
                $executed[] = "Added 'pembimbing2' to daftar_sidang_skripsi";
            }
        });
    }

    return response()->json([
        'status' => true,
        'message' => 'Migrasi kolom ketua, nidn & dospem berhasil dijalankan!',
        'changes' => $executed
    ]);
});
