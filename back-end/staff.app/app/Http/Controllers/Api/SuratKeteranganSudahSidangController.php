<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\NoSurat;
use App\Models\Prodi;
use App\Models\SettingJabatan;
use App\Models\SuratKeteranganSudahSidang;
use App\Models\TandaTangan;
use App\Services\GoogleDrive;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SuratKeteranganSudahSidangController extends Controller
{
    public function index(Request $request)
    {
        try {
            $data = SuratKeteranganSudahSidang::leftJoin('prodi', 'prodi.id', '=', 'surat_keterangan_sudah_sidang.prodi_id');

            $data->select(
                'surat_keterangan_sudah_sidang.*',
                'prodi.nama as nama_prodi',
                'prodi.alias as alias_prodi'
            );

            if ($request->filled('prodi_id')) {
                $data->where('surat_keterangan_sudah_sidang.prodi_id', $request->prodi_id);
            }

            $login = Auth::user()->prodi;
            if ($login) {
                $data->where('surat_keterangan_sudah_sidang.prodi_id', $login->id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $data->where(function ($q) use ($search) {
                    $q->orWhere('surat_keterangan_sudah_sidang.nim', 'like', "%{$search}%")
                        ->orWhere('surat_keterangan_sudah_sidang.nama', 'like', "%{$search}%")
                        ->orWhere('surat_keterangan_sudah_sidang.prodi', 'like', "%{$search}%")
                        ->orWhere('surat_keterangan_sudah_sidang.nomor_surat', 'like', "%{$search}%");
                });
            }

            $authGender = Auth::user()->jenis_kelamin;
            if ($authGender === 'L') {
                $data->where('surat_keterangan_sudah_sidang.jenis_kelamin', 'L');
            } elseif ($authGender === 'P') {
                $data->where('surat_keterangan_sudah_sidang.jenis_kelamin', 'P');
            }

            $limit = $request->input('limit', 10);
            $sortBy = $request->input('sortBy', 'id');
            $sortType = $request->input('sortType', 'desc');

            $result = $data->orderBy("surat_keterangan_sudah_sidang.{$sortBy}", $sortType)->paginate($limit);

            return response()->json([
                'status' => true,
                'data' => $result,
                'message' => 'Data berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data: ' . $th->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'no_surat'       => 'required|string|max:255',
                'nama'           => 'required|string|max:255',
                'nim'            => 'required|string|max:255',
                'prodi'          => 'required|string|max:255',
                'prodi_id'       => 'nullable|exists:prodi,id',
                'tanggal_sidang' => 'required|date',
                'tanggal'        => 'required|date',
                'kepala'         => 'nullable|string|max:255',
                'nidn'           => 'nullable|string|max:255',
                'petanda_tangan' => 'nullable|in:ya,tidak,stempel',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            if (NoSurat::where('nomor', trim($validate['no_surat']))->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nomor surat sudah terpakai',
                    'errors' => ['no_surat' => ['Nomor surat sudah terpakai']]
                ], 422);
            }

            // Format nomor surat
            $formattedNoSurat = SuratService::formatNomorSurat(
                'SKSS',
                $validate['no_surat'],
                $validate['tanggal'],
                $validate['prodi_id'] ?? null
            );

            // Resolve signer (default BAAK: Kuzairi, M.Pd / NIY. 80283)
            $signer = SuratService::resolveSigner('SKSS', [
                'ketua' => $validate['kepala'] ?? null,
                'nidn'  => $validate['nidn'] ?? null,
                'prodi_id' => $validate['prodi_id'] ?? null,
            ]);

            $surat = new SuratKeteranganSudahSidang();
            $surat->nomor_surat   = $formattedNoSurat;
            $surat->nama          = $validate['nama'];
            $surat->nim           = $validate['nim'];
            $surat->prodi         = $validate['prodi'];
            $surat->prodi_id      = $validate['prodi_id'] ?? null;
            $surat->tanggal_sidang= $validate['tanggal_sidang'];
            $surat->tanggal       = $validate['tanggal'];
            $surat->kepala        = $signer['ketua'] ?? ($validate['kepala'] ?? 'Kuzairi, M.Pd');
            $surat->nidn          = $signer['nidn'] ?? ($validate['nidn'] ?? '80283');
            $surat->petanda_tangan= $validate['petanda_tangan'] ?? 'tidak';
            $surat->user_id       = Auth::user()->id;
            $surat->jenis_kelamin = Auth::user()->jenis_kelamin;
            $surat->status        = 'pending';

            // Dapatkan ID tanda tangan BAAK jika ada
            $settingBaak = SettingJabatan::where('kunci_jabatan', 'BAAK')->first();
            if ($settingBaak && $settingBaak->tanda_tangan_id) {
                $surat->tanda_tangan_id = $settingBaak->tanda_tangan_id;
            }

            $surat->save();

            // Simpan nomor ke tabel NoSurat
            $nomorTrack = new NoSurat();
            $nomorTrack->nomor = trim($validate['no_surat']);
            $nomorTrack->user_id = Auth::user()->id;
            $nomorTrack->save();

            // Generate PDF
            $pdfData = $this->buildPdfData($surat);
            $prodiFolder = 'BAAK';
            $directory = SuratService::resolvePdfDirectory($prodiFolder, 'SuratKeteranganSudahSidangController');

            $safeNim = SuratService::sanitizeFileSegment($surat->nim ?? null, (string) $surat->id);
            $fileName = 'surat_keterangan_sudah_sidang_' . $safeNim . '_' . uniqid() . '.pdf';
            $path = $directory . $fileName;

            $pdf = Pdf::loadView('pdf.surat_keterangan_sudah_sidang', $pdfData)->setPaper('a4', 'portrait');
            $pdf->save($path);

            $surat->local_path = $path;
            $surat->save();

            // Upload ke Google Drive via queue
            $nameTable = 'Surat Keterangan Sudah Sidang';
            UploudSuratToDrive::dispatch($surat->id, $nameTable, $prodiFolder, SuratKeteranganSudahSidang::class);

            return response()->json([
                'status' => true,
                'data' => $surat,
                'message' => 'Data berhasil ditambahkan'
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal ditambahkan: ' . $th->getMessage() . ' di baris ' . $th->getLine()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $surat = SuratKeteranganSudahSidang::leftJoin('prodi', 'prodi.id', '=', 'surat_keterangan_sudah_sidang.prodi_id')
                ->select(
                    'surat_keterangan_sudah_sidang.*',
                    'prodi.nama as nama_prodi',
                    'prodi.alias as alias_prodi'
                )
                ->where('surat_keterangan_sudah_sidang.id', $id)
                ->first();

            if (!$surat) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // Ekstrak no_surat mentah
            $nomorStr = $surat->nomor_surat;
            if ($nomorStr) {
                $parts = explode('/', $nomorStr);
                $firstPart = $parts[0];
                if (strpos($firstPart, '-') !== false) {
                    $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
                }
                $surat->no_surat = trim($firstPart);
            }

            return response()->json([
                'status' => true,
                'data' => $surat,
                'message' => 'Data berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data: ' . $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'no_surat' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id) {
                        $skss = SuratKeteranganSudahSidang::find($id);
                        if ($skss) {
                            $originalNoSurat = '';
                            $nomorStr = $skss->nomor_surat;
                            if ($nomorStr) {
                                $parts = explode('/', $nomorStr);
                                $firstPart = $parts[0];
                                if (strpos($firstPart, '-') !== false) {
                                    $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
                                }
                                $originalNoSurat = trim($firstPart);
                            }
                            if (trim((string)$value) !== trim((string)$originalNoSurat)) {
                                if (NoSurat::where('nomor', trim($value))->exists()) {
                                    $fail('Nomor surat sudah terpakai.');
                                }
                            }
                        }
                    }
                ],
                'nama'           => 'required|string|max:255',
                'nim'            => 'required|string|max:255',
                'prodi'          => 'required|string|max:255',
                'prodi_id'       => 'nullable|exists:prodi,id',
                'tanggal_sidang' => 'required|date',
                'tanggal'        => 'required|date',
                'kepala'         => 'nullable|string|max:255',
                'nidn'           => 'nullable|string|max:255',
                'petanda_tangan' => 'nullable|in:ya,tidak,stempel',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $surat = SuratKeteranganSudahSidang::find($id);
            if (!$surat) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // Update nomor surat di tabel NoSurat jika berubah
            $origNoSurat = '';
            if ($surat->nomor_surat) {
                $parts = explode('/', $surat->nomor_surat);
                $firstPart = $parts[0];
                if (strpos($firstPart, '-') !== false) {
                    $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
                }
                $origNoSurat = trim($firstPart);
            }
            if (trim((string)$validate['no_surat']) !== trim((string)$origNoSurat)) {
                if (!empty($origNoSurat)) {
                    NoSurat::where('nomor', $origNoSurat)->delete();
                }
                NoSurat::updateOrCreate(
                    ['nomor' => trim($validate['no_surat'])],
                    ['user_id' => Auth::user()->id]
                );
            }

            $oldDriveFileId = $surat->drive_file_id;
            $oldLocalPath   = $surat->local_path;

            $formattedNoSurat = SuratService::formatNomorSurat(
                'SKSS',
                $validate['no_surat'],
                $validate['tanggal'],
                $validate['prodi_id'] ?? null
            );

            $signer = SuratService::resolveSigner('SKSS', [
                'ketua' => $validate['kepala'] ?? null,
                'nidn'  => $validate['nidn'] ?? null,
                'prodi_id' => $validate['prodi_id'] ?? null,
            ]);

            $surat->nomor_surat   = $formattedNoSurat;
            $surat->nama          = $validate['nama'];
            $surat->nim           = $validate['nim'];
            $surat->prodi         = $validate['prodi'];
            $surat->prodi_id      = $validate['prodi_id'] ?? null;
            $surat->tanggal_sidang= $validate['tanggal_sidang'];
            $surat->tanggal       = $validate['tanggal'];
            $surat->kepala        = $signer['ketua'] ?? ($validate['kepala'] ?? 'Kuzairi, M.Pd');
            $surat->nidn          = $signer['nidn'] ?? ($validate['nidn'] ?? '80283');
            $surat->petanda_tangan= $validate['petanda_tangan'] ?? 'tidak';
            $surat->drive_file_id = null;
            $surat->drive_link    = null;
            $surat->status        = 'pending';
            $surat->save();

            // Hapus file lama jika ada
            if (!empty($oldDriveFileId)) {
                GoogleDrive::deleteFile($oldDriveFileId);
            }
            if (!empty($oldLocalPath) && file_exists($oldLocalPath)) {
                @unlink($oldLocalPath);
            }

            // Re-generate PDF
            $pdfData = $this->buildPdfData($surat);
            $prodiFolder = 'BAAK';
            $directory = SuratService::resolvePdfDirectory($prodiFolder, 'SuratKeteranganSudahSidangController');

            $safeNim = SuratService::sanitizeFileSegment($surat->nim ?? null, (string) $surat->id);
            $fileName = 'surat_keterangan_sudah_sidang_' . $safeNim . '_' . uniqid() . '.pdf';
            $path = $directory . $fileName;

            $pdf = Pdf::loadView('pdf.surat_keterangan_sudah_sidang', $pdfData)->setPaper('a4', 'portrait');
            $pdf->save($path);

            $surat->local_path = $path;
            $surat->save();

            // Re-dispatch Google Drive upload
            $nameTable = 'Surat Keterangan Sudah Sidang';
            UploudSuratToDrive::dispatch($surat->id, $nameTable, $prodiFolder, SuratKeteranganSudahSidang::class);

            return response()->json([
                'status' => true,
                'data' => $surat,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal diupdate: ' . $th->getMessage() . ' di baris ' . $th->getLine()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $surat = SuratKeteranganSudahSidang::find($id);
            if (!$surat) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if (!empty($surat->drive_file_id)) {
                GoogleDrive::deleteFile($surat->drive_file_id);
            }
            if (!empty($surat->local_path) && file_exists($surat->local_path)) {
                @unlink($surat->local_path);
            }

            // Hapus dari NoSurat jika ada
            if ($surat->nomor_surat) {
                $parts = explode('/', $surat->nomor_surat);
                $firstPart = $parts[0];
                if (strpos($firstPart, '-') !== false) {
                    $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
                }
                $rawNo = trim($firstPart);
                if (!empty($rawNo)) {
                    NoSurat::where('nomor', $rawNo)->delete();
                }
            }

            $surat->delete();

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
            $data = SuratKeteranganSudahSidang::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            if (empty($data->local_path) || !file_exists($data->local_path)) {
                return response()->json(['status' => false, 'message' => 'File PDF tidak ditemukan di server'], 404);
            }

            $fileName = basename($data->local_path);

            return response()->file($data->local_path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF'], 500);
        }
    }

    private function buildPdfData($data)
    {
        $stempelBase64 = '';
        $tddBase64 = '';
        $qrCodeBase64 = '';

        if (isset($data->petanda_tangan) && in_array($data->petanda_tangan, ['ya', 'stempel'])) {
            // Ambil Tanda Tangan BAAK
            $settingBaak = SettingJabatan::with('tandaTangan')->where('kunci_jabatan', 'BAAK')->first();
            $ttdRecord = $settingBaak?->tandaTangan;

            if ($ttdRecord && !empty($ttdRecord->gambar)) {
                $tddPath = base_path('../public_html/' . $ttdRecord->gambar);
                if (file_exists($tddPath)) {
                    $tddBase64 = SuratService::getBase64Image($tddPath);
                }
            }

            // Ambil Stempel
            $stempelPath = base_path('../public_html/img/stempel.png');
            if (file_exists($stempelPath)) {
                $stempelBase64 = SuratService::getBase64Image($stempelPath, 'image/png');
            }

            // Generate QR Code untuk verifikasi surat
            try {
                $qrContent = $data->nomor_surat . "\n" . $data->nama . " (" . $data->nim . ")\n" . $data->prodi . "\nStatus: LULUS SKRIPSI";
                $qrCodeBase64 = (new QRCode())->render($qrContent);
            } catch (\Throwable $e) {
                Log::warning('QR Code generation failed: ' . $e->getMessage());
            }
        }

        $kopPath = base_path('../public_html/img/kop.jpg');
        $kopBase64 = SuratService::getBase64Image($kopPath);

        $tanggalSidangIndo = !empty($data->tanggal_sidang)
            ? Carbon::parse($data->tanggal_sidang)->locale('id')->translatedFormat('d F Y')
            : '';

        $tanggalSuratIndo = !empty($data->tanggal)
            ? Carbon::parse($data->tanggal)->locale('id')->translatedFormat('d F Y')
            : Carbon::now()->locale('id')->translatedFormat('d F Y');

        return [
            'nomor_surat'             => $data->nomor_surat,
            'nama'                    => $data->nama,
            'nim'                     => $data->nim,
            'prodi'                   => $data->prodi,
            'tanggal_sidang'          => $data->tanggal_sidang,
            'tanggal_sidang_formatted'=> $tanggalSidangIndo,
            'tanggal'                 => $data->tanggal,
            'tanggal_surat_formatted' => $tanggalSuratIndo,
            'kepala'                  => $data->kepala ?? 'Kuzairi, M.Pd',
            'nidn'                    => $data->nidn ?? '80283',
            'kopBase64'               => $kopBase64,
            'stempel'                 => $stempelBase64,
            'ttd'                     => $tddBase64,
            'qrCode'                  => $qrCodeBase64,
            'petanda_tangan'          => $data->petanda_tangan ?? 'tidak',
        ];
    }
}

