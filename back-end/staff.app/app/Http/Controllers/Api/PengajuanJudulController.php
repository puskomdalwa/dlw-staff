<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\PengajuanJudul;
use App\Models\Prodi;
use App\Models\TandaTangan;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PengajuanJudulController extends Controller
{
    public function index(Request $request)
    {
        $data = PengajuanJudul::leftJoin('prodi', 'prodi.id', '=', 'pengajuan_judul.prodi_id')
            ->leftJoin('tanda_tangan', 'tanda_tangan.id', '=', 'pengajuan_judul.tanda_tangan_id')
            ->select(
                'pengajuan_judul.*',
                DB::raw('COALESCE(prodi.nama, pengajuan_judul.prodi) as nama_prodi'),
                'prodi.alias as alias_prodi',
                'prodi.nama_kepala as nama_kepala_prodi',
                'tanda_tangan.nama as nama_ttd_kaprodi'
            );

        $user = Auth::user();
        $loginProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);

        if ($loginProdi) {
            // Scope Prodi jika user login terafiliasi dengan prodi tertentu (Staff)
            $data->where(function ($q) use ($loginProdi) {
                $q->where('pengajuan_judul.prodi_id', $loginProdi->id)
                    ->orWhere('pengajuan_judul.prodi', $loginProdi->nama)
                    ->orWhere('pengajuan_judul.prodi', $loginProdi->alias)
                    ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($loginProdi->nama))])
                    ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($loginProdi->alias))]);
            });
        } elseif ($request->filled('prodi_id')) {
            // Filter Prodi dari Request (untuk Admin / BAAK yang tidak terafiliasi prodi)
            $prodiFilter = Prodi::find($request->prodi_id);
            $data->where(function ($q) use ($request, $prodiFilter) {
                $q->where('pengajuan_judul.prodi_id', $request->prodi_id);
                if ($prodiFilter) {
                    $q->orWhere('pengajuan_judul.prodi', $prodiFilter->nama)
                        ->orWhere('pengajuan_judul.prodi', $prodiFilter->alias)
                        ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($prodiFilter->nama))])
                        ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($prodiFilter->alias))]);
                }
            });
        }

        $auth = $user?->jenis_kelamin;
        if ($auth == 'L') {
            $data->where(function ($q) {
                $q->where('pengajuan_judul.jenis_kelamin', 'L')
                    ->orWhereNull('pengajuan_judul.jenis_kelamin');
            });
        } elseif ($auth == 'P') {
            $data->where(function ($q) {
                $q->where('pengajuan_judul.jenis_kelamin', 'P')
                    ->orWhereNull('pengajuan_judul.jenis_kelamin');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->orWhere('pengajuan_judul.nim', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.nama_mhs', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.prodi', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.judul_proposal_skripsi', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.rumusan_masalah', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.dosen_penasehat', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.catatan_kprodi', 'like', "%{$search}%")
                    ->orWhere('prodi.nama', 'like', "%{$search}%");
            });
        }

        $data->orderBy(
            $request->input('sortBy', 'id'),
            $request->input('sortType', 'desc')
        );

        $data = $data->paginate($request->input('limit', 10));

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function store(Request $request)
    {
        Log::info('PengajuanJudul store request', $request->all());
        try {
            $validator = Validator::make($request->all(), [
                'prodi_id'               => 'nullable',
                'nim'                    => 'required|string|max:255',
                'nama_mhs'               => 'required|string|max:255',
                'prodi'                  => 'required|string|max:255',
                'judul_proposal_skripsi' => 'required|string',
                'rumusan_masalah'        => 'required|string',
                'dosen_penasehat'        => 'required|string|max:255',
                'catatan_kprodi'         => 'nullable|string',
                'tanda_tangan_id'        => 'nullable',
                'jenis_kelamin'          => 'nullable|in:L,P',
                'tanggal'                => 'nullable|date',
                'petanda_tangan'         => 'nullable|in:ya,tidak,stempel',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $prodiObj = Auth::user()?->prodi;
            if (!$prodiObj && !empty($validate['prodi_id'])) {
                $prodiObj = Prodi::find($validate['prodi_id']);
            }
            if (!$prodiObj && !empty($validate['prodi'])) {
                $prodiObj = Prodi::where('nama', $validate['prodi'])
                    ->orWhere('alias', $validate['prodi'])
                    ->first();
            }

            $pj = new PengajuanJudul();
            $pj->nim = $validate['nim'];
            $pj->nama_mhs = $validate['nama_mhs'];
            $pj->prodi = $validate['prodi'];
            $pj->prodi_id = $prodiObj ? $prodiObj->id : ($validate['prodi_id'] ?? null);
            $pj->judul_proposal_skripsi = $validate['judul_proposal_skripsi'];
            $pj->rumusan_masalah = $validate['rumusan_masalah'];
            $pj->dosen_penasehat = $validate['dosen_penasehat'];
            $pj->catatan_kprodi = $validate['catatan_kprodi'] ?? null;
            $pj->tanda_tangan_id = $prodiObj ? $prodiObj->tanda_tangan_id : null;
            $pj->jenis_kelamin = $validate['jenis_kelamin'] ?? Auth::user()->jenis_kelamin;
            $pj->tanggal = $validate['tanggal'] ?? date('Y-m-d');
            $pj->petanda_tangan = $validate['petanda_tangan'] ?? 'tidak';
            $pj->user_id = Auth::id();
            $pj->status = 'pending';
            $pj->save();

            $this->generatePdfFile($pj->id);

            // Sync ke Google Sheet khusus prodi jika berlaku
            \App\Jobs\SyncPengajuanJudulEsyToGoogleSheet::dispatch($pj->id, 'store');
            \App\Jobs\SyncPengajuanJudulHkiToGoogleSheet::dispatch($pj->id, 'store');
            \App\Jobs\SyncPengajuanJudulKpiToGoogleSheet::dispatch($pj->id, 'store');

            return response()->json([
                'status' => true,
                'data' => $pj,
                'message' => 'Data berhasil ditambahkan'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal ditambahkan',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function checkSimilarity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|min:3',
            'prodi_id' => 'nullable',
            'exclude_id' => 'nullable|integer',
            'threshold' => 'nullable|numeric|min:10|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $judul = $request->input('judul');
        $prodiId = $request->filled('prodi_id') ? (int)$request->input('prodi_id') : null;
        $excludeId = $request->filled('exclude_id') ? (int)$request->input('exclude_id') : null;
        $threshold = $request->filled('threshold') ? (float)$request->input('threshold') : 30.0;

        $result = \App\Services\JudulSimilarityService::checkSimilarity(
            $judul,
            $prodiId,
            $excludeId,
            $threshold
        );

        return response()->json([
            'status' => true,
            'data' => $result,
            'message' => 'Pengecekan kemiripan judul berhasil dilakukan'
        ]);
    }

    public function getByNim($nim)
    {
        $rawNim = trim($nim);
        $cleanNim = preg_replace('/[^a-zA-Z0-9]/', '', $rawNim);

        $data = PengajuanJudul::where('nim', $rawNim)
            ->orWhere('nim', $cleanNim)
            ->orWhere('nim', 'like', "%{$rawNim}%")
            ->orWhere('nim', 'like', "%{$cleanNim}%")
            ->orWhereRaw("REPLACE(REPLACE(REPLACE(nim, '.', ''), '-', ''), ' ', '') = ?", [$cleanNim])
            ->orderBy('id', 'desc')
            ->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data pengajuan judul tidak ditemukan',
                'data' => null
            ], 404);
        }

        $suratTugas = \App\Models\SuratTugas::where('nim_nik', $rawNim)
            ->orWhere('nim_nik', $cleanNim)
            ->orWhereRaw("REPLACE(REPLACE(REPLACE(nim_nik, '.', ''), '-', ''), ' ', '') = ?", [$cleanNim])
            ->orderBy('id', 'desc')
            ->first();

        $dospem1 = $suratTugas?->pembimbing1 ?: ($suratTugas?->dospem1 ?: ($data->dospem1 ?? null));
        $dospem2 = $suratTugas?->pembimbing2 ?: ($suratTugas?->dospem2 ?: ($data->dospem2 ?? null));

        $resData = $data->toArray();
        $resData['dospem1'] = $dospem1;
        $resData['dospem2'] = $dospem2;
        $resData['pembimbing1'] = $dospem1;
        $resData['pembimbing2'] = $dospem2;

        return response()->json([
            'status' => true,
            'data' => $resData,
            'message' => 'Data pengajuan judul berhasil diambil'
        ]);
    }

    public function show($id)
    {
        $data = PengajuanJudul::leftJoin('prodi', 'prodi.id', '=', 'pengajuan_judul.prodi_id')
            ->leftJoin('tanda_tangan', 'tanda_tangan.id', '=', 'pengajuan_judul.tanda_tangan_id')
            ->select(
                'pengajuan_judul.*',
                'prodi.nama as nama_prodi',
                'prodi.alias as alias_prodi',
                'tanda_tangan.nama as nama_ttd_kaprodi'
            )
            ->where('pengajuan_judul.id', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info("PengajuanJudul update request (id: {$id})", $request->all());
        try {
            $validator = Validator::make($request->all(), [
                'prodi_id'               => 'nullable',
                'nim'                    => 'required|string|max:255',
                'nama_mhs'               => 'required|string|max:255',
                'prodi'                  => 'required|string|max:255',
                'judul_proposal_skripsi' => 'required|string',
                'rumusan_masalah'        => 'required|string',
                'dosen_penasehat'        => 'required|string|max:255',
                'catatan_kprodi'         => 'nullable|string',
                'tanda_tangan_id'        => 'nullable',
                'jenis_kelamin'          => 'nullable|in:L,P',
                'tanggal'                => 'nullable|date',
                'petanda_tangan'         => 'nullable|in:ya,tidak,stempel',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $pj = PengajuanJudul::find($id);
            if (!$pj) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $oldDriveFileId = $pj->drive_file_id;
            $oldLocalPath = $pj->local_path;

            $prodiObj = Auth::user()?->prodi;
            if (!$prodiObj && !empty($validate['prodi_id'])) {
                $prodiObj = Prodi::find($validate['prodi_id']);
            }
            if (!$prodiObj && !empty($validate['prodi'])) {
                $prodiObj = Prodi::where('nama', $validate['prodi'])
                    ->orWhere('alias', $validate['prodi'])
                    ->first();
            }

            $pj->nim = $validate['nim'];
            $pj->nama_mhs = $validate['nama_mhs'];
            $pj->prodi = $validate['prodi'];
            $pj->prodi_id = $prodiObj ? $prodiObj->id : ($validate['prodi_id'] ?? $pj->prodi_id);
            $pj->judul_proposal_skripsi = $validate['judul_proposal_skripsi'];
            $pj->rumusan_masalah = $validate['rumusan_masalah'];
            $pj->dosen_penasehat = $validate['dosen_penasehat'];
            $pj->catatan_kprodi = $validate['catatan_kprodi'] ?? null;
            $pj->tanda_tangan_id = $prodiObj ? $prodiObj->tanda_tangan_id : $pj->tanda_tangan_id;
            $pj->jenis_kelamin = $validate['jenis_kelamin'] ?? Auth::user()->jenis_kelamin ?? $pj->jenis_kelamin;
            $pj->tanggal = $validate['tanggal'] ?? $pj->tanggal ?? date('Y-m-d');
            $pj->petanda_tangan = $validate['petanda_tangan'] ?? 'tidak';

            if (!empty($oldDriveFileId)) {
                \App\Services\GoogleDrive::deleteFile($oldDriveFileId);
            }
            if (!empty($oldLocalPath) && file_exists($oldLocalPath)) {
                @unlink($oldLocalPath);
            }

            $pj->drive_file_id = null;
            $pj->drive_link = null;
            $pj->status = 'pending';
            $pj->save();

            $this->generatePdfFile($pj->id);

            // Sync ke Google Sheet khusus prodi jika berlaku
            \App\Jobs\SyncPengajuanJudulEsyToGoogleSheet::dispatch($pj->id, 'update');
            \App\Jobs\SyncPengajuanJudulHkiToGoogleSheet::dispatch($pj->id, 'update');
            \App\Jobs\SyncPengajuanJudulKpiToGoogleSheet::dispatch($pj->id, 'update');

            return response()->json([
                'status' => true,
                'data' => $pj,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal diupdate',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pj = PengajuanJudul::find($id);
            if (!$pj) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if (!empty($pj->drive_file_id)) {
                \App\Services\GoogleDrive::deleteFile($pj->drive_file_id);
            }
            if (!empty($pj->local_path) && file_exists($pj->local_path)) {
                @unlink($pj->local_path);
            }

            $pj->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal dihapus',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function downloadPdf($id)
    {
        try {
            $data = PengajuanJudul::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $viewPath = resource_path('views/pdf/pengajuan_judul.blade.php');
            $viewMtime = file_exists($viewPath) ? filemtime($viewPath) : 0;
            $hasLocalFile = !empty($data->local_path) && file_exists($data->local_path) && is_readable($data->local_path);
            $pdfMtime = $hasLocalFile ? filemtime($data->local_path) : 0;

            if (!$hasLocalFile || $viewMtime > $pdfMtime) {
                $this->generatePdfFile($data->id, false);
                $data->refresh();
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
            Log::error("PengajuanJudul downloadPdf error for ID {$id}: " . $th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Gagal download PDF',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    private function formatArabicIfPresent(?string $rawText, int $lineWidth = 60): string
    {
        $rawText = trim($rawText ?? '');
        if ($rawText === '') {
            return '';
        }

        $cleanText = trim($rawText, '"\' ');
        $hasArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $cleanText);
        if ($hasArabic && class_exists('\ArPHP\I18N\Arabic')) {
            $arabic = new \ArPHP\I18N\Arabic();
            $cleanText = preg_replace('/\s+/u', ' ', $cleanText);
            $glyphs = $arabic->utf8Glyphs($cleanText, $lineWidth);
            return ($cleanText !== $rawText) ? '"' . $glyphs . '"' : $glyphs;
        }

        return $rawText;
    }

    public function generatePdfFile($id, bool $dispatchDrive = true): ?string
    {
        try {
            $pj = PengajuanJudul::find($id);
            if (!$pj) {
                return null;
            }

            $prodiObj = null;
            if (!empty($pj->prodi_id)) {
                $prodiObj = Prodi::find($pj->prodi_id);
            }
            if (!$prodiObj && !empty($pj->prodi)) {
                $prodiObj = Prodi::where('nama', $pj->prodi)
                    ->orWhere('alias', $pj->prodi)
                    ->first();
            }

            $ttdId = $pj->tanda_tangan_id ?: ($prodiObj ? $prodiObj->tanda_tangan_id : null);
            $ttdModel = $ttdId ? TandaTangan::find($ttdId) : null;

            $kopPath = base_path('../public_html/img/kop.jpg');
            $kopBase64 = SuratService::getBase64Image($kopPath);

            $tddBase64 = '';
            $stempelBase64 = '';

            if (isset($pj->petanda_tangan) && in_array($pj->petanda_tangan, ['ya', 'stempel'])) {
                $ttdRaw = $ttdModel ? ($ttdModel->tdd ?: $ttdModel->gambar) : null;
                if ($pj->petanda_tangan === 'ya' && !empty($ttdRaw)) {
                    if (str_starts_with($ttdRaw, 'data:image')) {
                        $tddBase64 = $ttdRaw;
                    } else {
                        $tddPath = base_path('../public_html/' . $ttdRaw);
                        if (file_exists($tddPath)) {
                            $tddBase64 = SuratService::getBase64Image($tddPath);
                        }
                    }
                }

                $stempelPath = base_path('../public_html/img/stempel.png');
                if (file_exists($stempelPath)) {
                    $stempelBase64 = SuratService::getBase64Image($stempelPath, 'image/png');
                }
            }

            $namaKaprodi = $prodiObj?->nama_kepala ?: ($ttdModel?->nama ?: '');
            $nidnKaprodi = $prodiObj?->nidn_kepala ?: '';
            $prodiDisplay = $prodiObj?->nama ?: $pj->prodi;

            $tanggalFormatted = !empty($pj->tanggal)
                ? SuratService::formatTanggalIndonesian($pj->tanggal)
                : SuratService::formatTanggalIndonesian(date('Y-m-d'));

            $pdfData = [
                'nim'                    => $pj->nim ?? '',
                'nama_mhs'               => $pj->nama_mhs ?? '',
                'prodi'                  => $prodiDisplay ?? '',
                'judul_proposal_skripsi' => $this->formatArabicIfPresent($pj->judul_proposal_skripsi, 60),
                'rumusan_masalah'        => $this->formatArabicIfPresent($pj->rumusan_masalah, 60),
                'dosen_penasehat'        => $pj->dosen_penasehat ?? '',
                'catatan_kprodi'         => $this->formatArabicIfPresent($pj->catatan_kprodi, 60),
                'tanggal'                => $tanggalFormatted,
                'nama_kaprodi'           => $namaKaprodi,
                'nidn_kaprodi'           => $nidnKaprodi,
                'kopBase64'              => $kopBase64,
                'ttd'                    => $tddBase64,
                'stempel'                => $stempelBase64,
                'petanda_tangan'         => $pj->petanda_tangan ?? 'tidak',
            ];

            if (!empty($pj->local_path) && file_exists($pj->local_path)) {
                @unlink($pj->local_path);
            }

            $prodiFolder = Auth::user()?->prodi
                ? Auth::user()->prodi->nama
                : ($prodiObj?->nama ?: ($pj->prodi ?: 'UMUM'));

            $directory = SuratService::resolvePdfDirectory($prodiFolder, 'PengajuanJudulController');
            $pdf = Pdf::loadView('pdf.pengajuan_judul', $pdfData)->setPaper('a4', 'portrait');
            $safeNim = SuratService::sanitizeFileSegment($pj->nim ?? null, (string) $pj->id);
            $fileName = 'pengajuan_judul_' . $safeNim . '_' . uniqid() . '.pdf';

            $path = $directory . $fileName;
            $pdf->save($path);

            PengajuanJudul::where('id', $pj->id)->update(['local_path' => $path]);

            if ($dispatchDrive) {
                $nameTable = 'Pengajuan Judul';
                UploudSuratToDrive::dispatch($pj->id, $nameTable, $prodiFolder, PengajuanJudul::class);
            }

            return $path;
        } catch (\Throwable $e) {
            Log::error("PengajuanJudul generatePdfFile error: " . $e->getMessage() . " on line " . $e->getLine());
            return null;
        }
    }

    public function getSudahSidang(Request $request)
    {
        $query = PengajuanJudul::leftJoin('prodi', 'prodi.id', '=', 'pengajuan_judul.prodi_id')
            ->select(
                'pengajuan_judul.id',
                'pengajuan_judul.nim',
                'pengajuan_judul.nama_mhs',
                'pengajuan_judul.prodi_id',
                'pengajuan_judul.judul_proposal_skripsi',
                'pengajuan_judul.dosen_penasehat',
                'pengajuan_judul.jenis_kelamin',
                'pengajuan_judul.status_sidang',
                DB::raw('COALESCE(prodi.nama, pengajuan_judul.prodi) as nama_prodi')
            );

        $user = Auth::user();
        $loginProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);

        if ($loginProdi) {
            $query->where(function ($q) use ($loginProdi) {
                $q->where('pengajuan_judul.prodi_id', $loginProdi->id)
                    ->orWhere('pengajuan_judul.prodi', $loginProdi->nama)
                    ->orWhere('pengajuan_judul.prodi', $loginProdi->alias)
                    ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($loginProdi->nama))])
                    ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($loginProdi->alias))]);
            });
        } elseif ($request->filled('prodi_id')) {
            $prodiFilter = Prodi::find($request->prodi_id);
            $query->where(function ($q) use ($request, $prodiFilter) {
                $q->where('pengajuan_judul.prodi_id', $request->prodi_id);
                if ($prodiFilter) {
                    $q->orWhere('pengajuan_judul.prodi', $prodiFilter->nama)
                        ->orWhere('pengajuan_judul.prodi', $prodiFilter->alias)
                        ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($prodiFilter->nama))])
                        ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($prodiFilter->alias))]);
                }
            });
        }

        $isAdmin = strtolower($user?->level?->nama ?? '') === 'admin';
        if ($user && !$isAdmin) {
            $authGender = $user->jenis_kelamin;
            if ($authGender === 'L' || $authGender === 'P') {
                $query->where(function ($q) use ($authGender) {
                    $q->where('pengajuan_judul.jenis_kelamin', $authGender)
                        ->orWhereNull('pengajuan_judul.jenis_kelamin');
                });
            }
        }

        if ($request->filled('status_sidang') && in_array($request->status_sidang, ['sudah', 'belum'], true)) {
            $query->where('pengajuan_judul.status_sidang', $request->status_sidang);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->orWhere('pengajuan_judul.nim', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.nama_mhs', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.prodi', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.judul_proposal_skripsi', 'like', "%{$search}%")
                    ->orWhere('pengajuan_judul.dosen_penasehat', 'like', "%{$search}%")
                    ->orWhere('prodi.nama', 'like', "%{$search}%");
            });
        }

        $data = $query->orderBy('pengajuan_judul.id', 'desc')->get();

        return response()->json([
            'status'  => true,
            'data'    => $data,
            'message' => 'Data mahasiswa berhasil diambil',
        ]);
    }

    public function updateStatusSidang(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'checked_ids'     => 'nullable|array',
                'checked_ids.*'   => 'integer|exists:pengajuan_judul,id',
                'displayed_ids'   => 'nullable|array',
                'displayed_ids.*' => 'integer|exists:pengajuan_judul,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            $checkedIds = array_values(array_unique(array_map('intval', $validated['checked_ids'] ?? [])));
            $displayedIds = array_values(array_unique(array_map('intval', $validated['displayed_ids'] ?? [])));
            $uncheckedIds = array_values(array_diff($displayedIds, $checkedIds));

            $user = Auth::user();
            $loginProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);
            $isAdmin = strtolower($user?->level?->nama ?? '') === 'admin';

            $applyScope = function ($query) use ($loginProdi, $user, $isAdmin) {
                if ($loginProdi) {
                    $query->where(function ($q) use ($loginProdi) {
                        $q->where('prodi_id', $loginProdi->id)
                            ->orWhere('prodi', $loginProdi->nama)
                            ->orWhere('prodi', $loginProdi->alias)
                            ->orWhereRaw('LOWER(TRIM(prodi)) = ?', [strtolower(trim($loginProdi->nama))])
                            ->orWhereRaw('LOWER(TRIM(prodi)) = ?', [strtolower(trim($loginProdi->alias))]);
                    });
                }
                if ($user && !$isAdmin && in_array($user->jenis_kelamin, ['L', 'P'], true)) {
                    $gender = $user->jenis_kelamin;
                    $query->where(function ($q) use ($gender) {
                        $q->where('jenis_kelamin', $gender)
                            ->orWhereNull('jenis_kelamin');
                    });
                }
            };

            DB::transaction(function () use ($checkedIds, $uncheckedIds, $applyScope) {
                if (!empty($checkedIds)) {
                    $qSudah = PengajuanJudul::whereIn('id', $checkedIds);
                    $applyScope($qSudah);
                    $qSudah->update(['status_sidang' => 'sudah']);
                }

                if (!empty($uncheckedIds)) {
                    $qBelum = PengajuanJudul::whereIn('id', $uncheckedIds);
                    $applyScope($qBelum);
                    $qBelum->update(['status_sidang' => 'belum']);
                }
            });

            return response()->json([
                'status'  => true,
                'message' => 'Status sidang mahasiswa berhasil disimpan',
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menyimpan status sidang: ' . $th->getMessage(),
            ], 500);
        }
    }
}

