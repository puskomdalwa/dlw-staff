<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\LogSurat;
use App\Models\NoSurat;
use App\Models\PengumumanAkademik;
use App\Models\SettingJabatan;
use App\Models\TandaTangan;
use App\Services\GoogleDrive;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PengumumanAkademikController extends Controller
{
    public function index(Request $request)
    {
        $query = PengumumanAkademik::with('tandaTangan');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->orWhere('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('judul', 'like', "%{$search}%")
                    ->orWhere('kepada', 'like', "%{$search}%")
                    ->orWhere('isi', 'like', "%{$search}%");
            });
        }

        $auth = Auth::user()->jenis_kelamin;
        if (!empty($auth) && in_array($auth, ['L', 'P'])) {
            $query->where(function ($q) use ($auth) {
                $q->where('jenis_kelamin', $auth)
                    ->orWhereNull('jenis_kelamin');
            });
        }

        $query->orderBy(
            $request->input('sortBy', 'id'),
            $request->input('sortType', 'desc')
        );

        $data = $query->paginate($request->input('limit', 10));

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'no_surat' => 'required|string|max:255|unique:nomor,nomor',
                'judul' => 'nullable|string|max:255',
                'kepada' => 'nullable|string|max:255',
                'isi' => 'required|string',
                'tempat' => 'nullable|string|max:100',
                'tanggal' => 'required|date',
                'tanda_tangan_id' => 'nullable|exists:tanda_tangan,id',
                'petanda_tangan' => 'nullable|in:ya,tidak,stempel',
                'ketua' => 'nullable|string|max:255',
                'nidn' => 'nullable|string|max:255',
            ], [
                'no_surat.unique' => 'Nomor surat sudah terpakai',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $no_surat = $validate['no_surat'];
            $formattedNoSurat = SuratService::formatNomorSurat('PA', $no_surat, $validate['tanggal'], null);
            if (!$formattedNoSurat || $formattedNoSurat == $no_surat) {
                $formattedNoSurat = SuratService::NoPengumumanAkademik($no_surat);
            }

            $settingBaak = SettingJabatan::where('kunci_jabatan', 'BAAK')->first();
            $tandaTanganId = !empty($validate['tanda_tangan_id'])
                ? $validate['tanda_tangan_id']
                : ($settingBaak ? $settingBaak->tanda_tangan_id : null);

            $pengumuman = new PengumumanAkademik();
            $pengumuman->nomor_surat = $formattedNoSurat;
            $pengumuman->judul = $validate['judul'] ?? null;
            $pengumuman->kepada = !empty($validate['kepada']) ? $validate['kepada'] : null;
            $pengumuman->isi = $validate['isi'];
            $pengumuman->tempat = $validate['tempat'] ?? 'Pasuruan';
            $pengumuman->tanggal = $validate['tanggal'];
            $pengumuman->tanda_tangan_id = $tandaTanganId;
            $pengumuman->petanda_tangan = $validate['petanda_tangan'] ?? 'tidak';
            $pengumuman->user_id = Auth::user()->id;
            $pengumuman->jenis_kelamin = Auth::user()->jenis_kelamin;
            $pengumuman->status = 'pending';
            
            $signer = \App\Services\SuratService::resolveSigner('PA', [
                'prodi_id' => null,
                'tanda_tangan_id' => $validate['tanda_tangan_id'] ?? null,
                'ketua' => $validate['ketua'] ?? null,
                'nidn' => $validate['nidn'] ?? ($validate['nidn_kepala'] ?? null),
            ]);
            $pengumuman->ketua = $signer['ketua'];
            $pengumuman->nidn = $signer['nidn'];
            $pengumuman->save();

            // Record ke NoSurat
            $nomorObj = new NoSurat();
            $nomorObj->nomor = $no_surat;
            $nomorObj->user_id = Auth::user()->id;
            $nomorObj->save();

            // Record ke LogSurat
            $log = new LogSurat();
            $log->nomor = $no_surat;
            $log->nomor_surat = $formattedNoSurat;
            $log->nama_surat = 'Pengumuman Akademik';
            $log->user_id = Auth::user()->id;
            $log->save();

            // Generate PDF lokal
            $this->generateAndSavePdf($pengumuman);

            // Dispatch Google Drive upload
            UploudSuratToDrive::dispatch($pengumuman->id, 'Pengumuman Akademik', 'BAAK', PengumumanAkademik::class);

            return response()->json([
                'status' => true,
                'message' => 'Surat Pengumuman Akademik berhasil ditambahkan',
                'data' => $pengumuman
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan Pengumuman Akademik: ' . $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $pengumuman = PengumumanAkademik::with('tandaTangan')->find($id);

        if (!$pengumuman) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        // Ambil nomor murni untuk kebutuhan form edit
        $nomorStr = $pengumuman->nomor_surat;
        $noSuratOnly = $nomorStr;
        if ($nomorStr) {
            $parts = explode('/', $nomorStr);
            $firstPart = $parts[0];
            if (str_contains($firstPart, '-')) {
                $noSuratOnly = trim(explode('-', $firstPart)[1] ?? $firstPart);
            }
        }
        $pengumuman->no_surat = $noSuratOnly;

        return response()->json([
            'status' => true,
            'data' => $pengumuman,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $pengumuman = PengumumanAkademik::find($id);
            if (!$pengumuman) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $validator = Validator::make($request->all(), [
                'no_surat' => 'required|string|max:255',
                'judul' => 'nullable|string|max:255',
                'kepada' => 'nullable|string|max:255',
                'isi' => 'required|string',
                'tempat' => 'nullable|string|max:100',
                'tanggal' => 'required|date',
                'tanda_tangan_id' => 'nullable|exists:tanda_tangan,id',
                'petanda_tangan' => 'nullable|in:ya,tidak,stempel',
                'ketua' => 'nullable|string|max:255',
                'nidn' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $no_surat = $validate['no_surat'];
            $formattedNoSurat = SuratService::formatNomorSurat('PA', $no_surat, $validate['tanggal'], null);
            if (!$formattedNoSurat || $formattedNoSurat == $no_surat) {
                $formattedNoSurat = SuratService::NoPengumumanAkademik($no_surat);
            }

            // Hapus file lama jika ada
            $oldLocalPath = $pengumuman->local_path;
            $oldDriveFileId = $pengumuman->drive_file_id;

            $settingBaak = SettingJabatan::where('kunci_jabatan', 'BAAK')->first();
            $tandaTanganId = !empty($validate['tanda_tangan_id'])
                ? $validate['tanda_tangan_id']
                : ($settingBaak ? $settingBaak->tanda_tangan_id : $pengumuman->tanda_tangan_id);

            $pengumuman->nomor_surat = $formattedNoSurat;
            $pengumuman->judul = $validate['judul'] ?? null;
            $pengumuman->kepada = !empty($validate['kepada']) ? $validate['kepada'] : null;
            $pengumuman->isi = $validate['isi'];
            $pengumuman->tempat = $validate['tempat'] ?? 'Pasuruan';
            $pengumuman->tanggal = $validate['tanggal'];
            $pengumuman->tanda_tangan_id = $tandaTanganId;
            $pengumuman->petanda_tangan = $validate['petanda_tangan'] ?? 'tidak';
            $pengumuman->drive_file_id = null;
            $pengumuman->drive_link = null;
            $pengumuman->status = 'pending';
            
            $signer = \App\Services\SuratService::resolveSigner('PA', [
                'prodi_id' => null,
                'tanda_tangan_id' => $validate['tanda_tangan_id'] ?? $pengumuman->tanda_tangan_id ?? null,
                'ketua' => $validate['ketua'] ?? $pengumuman->ketua ?? null,
                'nidn' => $validate['nidn'] ?? ($validate['nidn_kepala'] ?? $pengumuman->nidn ?? null),
            ]);
            $pengumuman->ketua = $signer['ketua'];
            $pengumuman->nidn = $signer['nidn'];
            $pengumuman->save();

            if (!empty($oldDriveFileId)) {
                GoogleDrive::deleteFile($oldDriveFileId);
            }
            if (!empty($oldLocalPath) && file_exists($oldLocalPath)) {
                @unlink($oldLocalPath);
            }

            // Regenerate PDF lokal
            $this->generateAndSavePdf($pengumuman);

            // Re-dispatch upload ke drive
            UploudSuratToDrive::dispatch($pengumuman->id, 'Pengumuman Akademik', 'BAAK', PengumumanAkademik::class);

            return response()->json([
                'status' => true,
                'message' => 'Surat Pengumuman Akademik berhasil diupdate',
                'data' => $pengumuman
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate Pengumuman Akademik: ' . $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pengumuman = PengumumanAkademik::find($id);
            if (!$pengumuman) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            if (!empty($pengumuman->local_path) && file_exists($pengumuman->local_path)) {
                @unlink($pengumuman->local_path);
            }

            if (!empty($pengumuman->drive_file_id)) {
                GoogleDrive::deleteFile($pengumuman->drive_file_id);
            }

            $pengumuman->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data: ' . $th->getMessage()
            ], 500);
        }
    }

    public function downloadPdf($id)
    {
        try {
            $pengumuman = PengumumanAkademik::find($id);
            if (!$pengumuman) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            // Ambil penandatangan dari setting_jabatan where kunci_jabatan = BAAK jika belum diset
            $settingBaak = SettingJabatan::where('kunci_jabatan', 'BAAK')->first();
            if ($settingBaak && $settingBaak->tanda_tangan_id) {
                if (empty($pengumuman->tanda_tangan_id) || $pengumuman->tanda_tangan_id != $settingBaak->tanda_tangan_id) {
                    $pengumuman->tanda_tangan_id = $settingBaak->tanda_tangan_id;
                    $pengumuman->save();

                    // Hapus file lokal lama agar di-regenerate dengan tanda tangan BAAK yang sesuai
                    if (!empty($pengumuman->local_path) && file_exists($pengumuman->local_path)) {
                        @unlink($pengumuman->local_path);
                        $pengumuman->local_path = null;
                    }
                }
            }

            $viewPath = resource_path('views/pdf/pengumuman_akademik.blade.php');
            $viewMtime = file_exists($viewPath) ? filemtime($viewPath) : 0;
            $pdfMtime = (!empty($pengumuman->local_path) && file_exists($pengumuman->local_path)) ? filemtime($pengumuman->local_path) : 0;

            if (empty($pengumuman->local_path) || !file_exists($pengumuman->local_path) || $viewMtime > $pdfMtime) {
                $this->generateAndSavePdf($pengumuman);
            }

            $fileName = basename($pengumuman->local_path);

            return response()->file($pengumuman->local_path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF']);
        }
    }

    private function generateAndSavePdf(PengumumanAkademik $pengumuman)
    {
        $pdfData = $this->buildPdfData($pengumuman);
        $pdf = Pdf::loadView('pdf.pengumuman_akademik', $pdfData)->setPaper('a4', 'portrait');

        $directory = base_path('../public_html/pdf/BAAK/Pengumuman Akademik');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $cleanNomor = preg_replace('/[^A-Za-z0-9]/', '_', $pengumuman->nomor_surat);
        $fileName = 'pengumuman_akademik_' . $cleanNomor . '_' . uniqid() . '.pdf';
        $path = $directory . '/' . $fileName;

        $pdf->save($path);
        $pengumuman->update(['local_path' => $path]);

        return $path;
    }

    private function buildPdfData(PengumumanAkademik $pengumuman)
    {
        $kopPath = base_path('../public_html/img/kop.jpg');
        $kopBase64 = SuratService::getBase64Image($kopPath);

        $stempelBase64 = '';
        $ttdBase64 = '';
        $namaPenandatangan = 'Kepala BAAK';
        $nidnPenandatangan = '';

        // Prioritas Tanda Tangan:
        // 1. Relasi tanda_tangan_id yang ada di pengumuman
        // 2. Setting jabatan 'BAAK'
        // 3. Fallback record pertama tanda_tangan
        $settingBaak = SettingJabatan::with('tandaTangan')
            ->where('kunci_jabatan', 'BAAK')
            ->first();

        $ttdRecord = null;
        if (!empty($pengumuman->tanda_tangan_id)) {
            $ttdRecord = TandaTangan::find($pengumuman->tanda_tangan_id);
        }

        if (!$ttdRecord && $settingBaak && $settingBaak->tandaTangan) {
            $ttdRecord = $settingBaak->tandaTangan;
        }

        if (!$ttdRecord) {
            $ttdRecord = TandaTangan::first();
        }

        if (!empty($pengumuman->ketua)) {
            $namaPenandatangan = $pengumuman->ketua;
            $nidnPenandatangan = $pengumuman->nidn ?? (($settingBaak && !empty($settingBaak->nidn)) ? $settingBaak->nidn : '');
        } elseif ($ttdRecord) {
            $namaPenandatangan = $ttdRecord->nama;
            $nidnPenandatangan = ($settingBaak && !empty($settingBaak->nidn)) ? $settingBaak->nidn : ($ttdRecord->nidn ?? '');
        }

        if ($ttdRecord) {

            if (in_array($pengumuman->petanda_tangan, ['ya'])) {
                $rawTtd = $ttdRecord->tdd ?? $ttdRecord->gambar;
                if (!empty($rawTtd)) {
                    if (str_starts_with($rawTtd, 'data:image')) {
                        $ttdBase64 = $rawTtd;
                    } else {
                        $ttdPath = base_path('../public_html/' . $rawTtd);
                        if (file_exists($ttdPath)) {
                            $ttdBase64 = SuratService::getBase64Image($ttdPath);
                        }
                    }
                }
            }
        }

        if (in_array($pengumuman->petanda_tangan, ['ya', 'stempel'])) {
            $stempelPath = base_path('../public_html/img/stempel.png');
            if (file_exists($stempelPath)) {
                $stempelBase64 = SuratService::getBase64Image($stempelPath, 'image/png');
            }
        }

        return [
            'kopBase64' => $kopBase64,
            'nomor' => $pengumuman->nomor_surat,
            'judul' => $pengumuman->judul,
            'kepada' => $pengumuman->kepada,
            'isi' => $pengumuman->isi,
            'tempat' => $pengumuman->tempat ?? 'Pasuruan',
            'tanggal' => SuratService::formatTanggalIndonesian($pengumuman->tanggal),
            'ketua' => $pengumuman->ketua,
            'nidn' => $pengumuman->nidn,
            'nama_penandatangan' => $namaPenandatangan,
            'nidn_penandatangan' => $nidnPenandatangan,
            'ttd' => $ttdBase64,
            'stempel' => $stempelBase64,
            'petanda_tangan' => $pengumuman->petanda_tangan ?? 'tidak'
        ];
    }
}
