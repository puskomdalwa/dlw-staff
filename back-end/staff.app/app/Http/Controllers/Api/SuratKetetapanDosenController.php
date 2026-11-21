<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\Institusi;
use App\Models\NoSurat;
use App\Models\SettingJabatan;
use App\Models\SuratKetetapanDosen;
use App\Models\TandaTangan;
use App\Services\GoogleDrive;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SuratKetetapanDosenController extends Controller
{
    public function index(Request $request)
    {
        Log::info('SuratKetetapanDosen index:', $request->all());

        $data = SuratKetetapanDosen::query()
            ->leftJoin('institusi', 'institusi.id', '=', 'surat_ketetapan_dosen.institusi_id')
            ->select(
                'surat_ketetapan_dosen.*',
                'institusi.nama_institusi',
                'institusi.rektor as nama_rektor'
            );

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->orWhere('surat_ketetapan_dosen.nama_dosen', 'LIKE', "%{$search}%")
                  ->orWhere('surat_ketetapan_dosen.nidn', 'LIKE', "%{$search}%")
                  ->orWhere('surat_ketetapan_dosen.jabatan', 'LIKE', "%{$search}%")
                  ->orWhere('surat_ketetapan_dosen.instansi', 'LIKE', "%{$search}%")
                  ->orWhere('surat_ketetapan_dosen.nomor', 'LIKE', "%{$search}%")
                  ->orWhere('institusi.nama_institusi', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('institusi_id')) {
            $data->where('surat_ketetapan_dosen.institusi_id', $request->institusi_id);
        }

        $user = Auth::user();
        $isAdmin = strtolower($user?->level?->nama ?? '') === 'admin';
        if ($user && !$isAdmin) {
            $authGender = $user->jenis_kelamin;
            if ($authGender === 'L') {
                $data->where('surat_ketetapan_dosen.jenis_kelamin', 'L');
            } elseif ($authGender === 'P') {
                $data->where('surat_ketetapan_dosen.jenis_kelamin', 'P');
            }
        }

        $data->orderBy(
            'surat_ketetapan_dosen.' . $request->input('sortBy', 'id'),
            $request->input('sortType', 'desc')
        );

        $data = $data->paginate($request->input('limit', 10));

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data berhasil diambil'
        ]);
    }

    private function resolveJenisSuratAlias(?Institusi $institusi): string
    {
        $nama = strtolower(trim($institusi?->nama_institusi ?? ''));
        if (str_contains($nama, 'universitas islam internasional') || str_contains($nama, 'uii')) {
            return 'SKD-UII';
        }
        if (str_contains($nama, 'institut agama islam') || str_contains($nama, 'iai')) {
            return 'SKD-IAI';
        }
        return 'SKD-UII';
    }

    private function extractRawNoSurat(?string $nomorStr): string
    {
        if (empty($nomorStr)) {
            return '';
        }
        $parts = explode('/', $nomorStr);
        $firstPart = $parts[0];
        if (strpos($firstPart, '-') !== false) {
            $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
        }
        return trim($firstPart);
    }

    public function store(Request $request)
    {
        Log::info('SuratKetetapanDosen store:', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'institusi_id'         => 'required|exists:institusi,id',
                'no_surat'             => 'required|regex:/^[0-9]+$/|max:50|unique:nomor,nomor',
                'nama_dosen'           => 'required|string|max:255',
                'jabatan'              => 'required|string|max:255',
                'nidn'                 => 'nullable|string|max:255',
                'instansi'             => 'required|string|max:255',
                'jenis_kelamin'        => 'nullable|in:L,P',
                'alamat_penandatangan' => 'nullable|string|max:255',
                'tempat_ditetapkan'    => 'nullable|string|max:100',
                'tanggal'              => 'nullable|date',
                'petanda_tangan'       => 'nullable|in:ya,tidak,stempel',
            ], [
                'no_surat.required' => 'Nomor surat wajib diisi',
                'no_surat.regex'    => 'Nomor surat hanya boleh berupa angka',
                'no_surat.unique'   => 'Nomor surat sudah terpakai',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $institusi = Institusi::find($validate['institusi_id']);
            $aliasJenisSurat = $this->resolveJenisSuratAlias($institusi);
            $rawNoSurat = trim((string) $validate['no_surat']);
            $tanggal = $validate['tanggal'] ?? date('Y-m-d');
            $formattedNomor = SuratService::formatNomorSurat($aliasJenisSurat, $rawNoSurat, $tanggal, null);

            $skd = new SuratKetetapanDosen();
            $skd->institusi_id         = $validate['institusi_id'];
            $skd->nomor                = $formattedNomor;
            $skd->nama_dosen           = $validate['nama_dosen'];
            $skd->jabatan              = $validate['jabatan'];
            $skd->nidn                 = $validate['nidn'] ?? null;
            $skd->instansi             = $validate['instansi'];
            $skd->jenis_kelamin        = $validate['jenis_kelamin'] ?? (Auth::user()?->jenis_kelamin ?: 'L');
            $skd->alamat_penandatangan = !empty($validate['alamat_penandatangan'])
                ? $validate['alamat_penandatangan']
                : 'Jl. Raya Raci No. 51 Bangil-Pasuruan';
            $skd->tempat_ditetapkan    = !empty($validate['tempat_ditetapkan'])
                ? $validate['tempat_ditetapkan']
                : 'Bangil';
            $skd->tanggal              = $tanggal;
            $skd->petanda_tangan       = $validate['petanda_tangan'] ?? 'ya';
            $skd->user_id              = Auth::user()?->id;
            $skd->status               = 'pending';
            $skd->save();

            $nomor = new NoSurat();
            $nomor->nomor = $rawNoSurat;
            $nomor->user_id = Auth::user()?->id;
            $nomor->save();

            $data = $this->fetchJoinedData($skd->id);

            if ($data) {
                $path = $this->generateAndSavePdf($data);
                $skd->update(['local_path' => $path]);

                $folderName = Auth::user()?->prodi?->nama ?: ($data->nama_institusi ?: 'UMUM');
                UploudSuratToDrive::dispatch($skd->id, 'Surat Ketetapan Dosen', $folderName, SuratKetetapanDosen::class);
            }

            return response()->json([
                'status'  => true,
                'data'    => $skd,
                'message' => 'Data berhasil ditambahkan'
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Data gagal ditambahkan: ' . $th->getMessage(),
                'error'   => $th->getMessage() . ' di baris ' . $th->getLine()
            ], 500);
        }
    }

    public function show($id)
    {
        $skd = SuratKetetapanDosen::leftJoin('institusi', 'institusi.id', '=', 'surat_ketetapan_dosen.institusi_id')
            ->select(
                'surat_ketetapan_dosen.*',
                'institusi.nama_institusi',
                'institusi.kop_surat',
                'institusi.rektor as nama_rektor'
            )
            ->where('surat_ketetapan_dosen.id', $id)
            ->first();

        if (!$skd) {
            return response()->json([
                'status'  => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $skd->no_surat = $this->extractRawNoSurat($skd->nomor);

        return response()->json([
            'status'  => true,
            'data'    => $skd,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info('SuratKetetapanDosen update:', $request->all());

        try {
            $skd = SuratKetetapanDosen::find($id);

            if (!$skd) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $origNoSurat = $this->extractRawNoSurat($skd->nomor);

            $validator = Validator::make($request->all(), [
                'institusi_id'         => 'required|exists:institusi,id',
                'no_surat'             => [
                    'required',
                    'regex:/^[0-9]+$/',
                    'max:50',
                    function ($attribute, $value, $fail) use ($origNoSurat) {
                        $val = trim((string) $value);
                        if ($val !== $origNoSurat && NoSurat::where('nomor', $val)->exists()) {
                            $fail('Nomor surat sudah terpakai');
                        }
                    },
                ],
                'nama_dosen'           => 'required|string|max:255',
                'jabatan'              => 'required|string|max:255',
                'nidn'                 => 'nullable|string|max:255',
                'instansi'             => 'required|string|max:255',
                'jenis_kelamin'        => 'nullable|in:L,P',
                'alamat_penandatangan' => 'nullable|string|max:255',
                'tempat_ditetapkan'    => 'nullable|string|max:100',
                'tanggal'              => 'nullable|date',
                'petanda_tangan'       => 'nullable|in:ya,tidak,stempel',
            ], [
                'no_surat.required' => 'Nomor surat wajib diisi',
                'no_surat.regex'    => 'Nomor surat hanya boleh berupa angka',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $oldDriveFileId = $skd->drive_file_id;
            $oldLocalPath   = $skd->local_path;

            $rawNoSurat = trim((string) $validate['no_surat']);
            if ($rawNoSurat !== $origNoSurat) {
                if (!empty($origNoSurat)) {
                    NoSurat::where('nomor', $origNoSurat)->delete();
                }
                NoSurat::updateOrCreate(
                    ['nomor' => $rawNoSurat],
                    ['user_id' => Auth::user()?->id]
                );
            }

            $institusi = Institusi::find($validate['institusi_id']);
            $aliasJenisSurat = $this->resolveJenisSuratAlias($institusi);
            $tanggal = !empty($validate['tanggal']) ? $validate['tanggal'] : ($skd->tanggal ?: date('Y-m-d'));
            $formattedNomor = SuratService::formatNomorSurat($aliasJenisSurat, $rawNoSurat, $tanggal, null);

            $skd->institusi_id = $validate['institusi_id'];
            $skd->nomor        = $formattedNomor;
            $skd->nama_dosen   = $validate['nama_dosen'];
            $skd->jabatan      = $validate['jabatan'];
            $skd->nidn         = $validate['nidn'] ?? null;
            $skd->instansi     = $validate['instansi'];
            if (isset($validate['jenis_kelamin'])) {
                $skd->jenis_kelamin = $validate['jenis_kelamin'];
            }
            if (array_key_exists('alamat_penandatangan', $validate)) {
                $skd->alamat_penandatangan = $validate['alamat_penandatangan'] ?: 'Jl. Raya Raci No. 51 Bangil-Pasuruan';
            }
            if (array_key_exists('tempat_ditetapkan', $validate)) {
                $skd->tempat_ditetapkan = $validate['tempat_ditetapkan'] ?: 'Bangil';
            }
            if (!empty($validate['tanggal'])) {
                $skd->tanggal = $validate['tanggal'];
            }
            $skd->petanda_tangan = $validate['petanda_tangan'] ?? ($skd->petanda_tangan ?? 'ya');

            $skd->drive_file_id = null;
            $skd->drive_link    = null;
            $skd->status        = 'pending';
            $skd->save();

            if ($oldDriveFileId) {
                GoogleDrive::deleteFile($oldDriveFileId);
            }
            if ($oldLocalPath && File::exists($oldLocalPath)) {
                @File::delete($oldLocalPath);
            }

            $data = $this->fetchJoinedData($skd->id);
            if ($data) {
                $path = $this->generateAndSavePdf($data);
                $skd->local_path = $path;
                $skd->save();

                $folderName = Auth::user()?->prodi?->nama ?: ($data->nama_institusi ?: 'UMUM');
                UploudSuratToDrive::dispatch($skd->id, 'Surat Ketetapan Dosen', $folderName, SuratKetetapanDosen::class);
            }

            return response()->json([
                'status'  => true,
                'data'    => $skd,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Data gagal diupdate: ' . $th->getMessage() . ' di baris ' . $th->getLine()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $skd = SuratKetetapanDosen::find($id);

            if (!$skd) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $origNoSurat = $this->extractRawNoSurat($skd->nomor);
            if (!empty($origNoSurat)) {
                NoSurat::where('nomor', $origNoSurat)->delete();
            }

            if ($skd->drive_file_id) {
                GoogleDrive::deleteFile($skd->drive_file_id);
            }
            if ($skd->local_path && File::exists($skd->local_path)) {
                @File::delete($skd->local_path);
            }

            $skd->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Data gagal dihapus'
            ], 500);
        }
    }

    public function downloadPdf($id)
    {
        try {
            $data = SuratKetetapanDosen::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $joined = $this->fetchJoinedData($data->id);
            if ($joined) {
                $oldLocalPath = $data->local_path;
                $path = $this->generateAndSavePdf($joined);
                $data->update(['local_path' => $path]);
                if (!empty($oldLocalPath) && $oldLocalPath !== $path && File::exists($oldLocalPath)) {
                    @File::delete($oldLocalPath);
                }
            }

            if (empty($data->local_path) || !file_exists($data->local_path)) {
                return response()->json(['status' => false, 'message' => 'File PDF tidak ditemukan di server'], 404);
            }

            $fileName = basename($data->local_path);

            return response()->file($data->local_path, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF'], 500);
        }
    }

    private function fetchJoinedData($id)
    {
        return SuratKetetapanDosen::leftJoin('institusi', 'institusi.id', '=', 'surat_ketetapan_dosen.institusi_id')
            ->select(
                'surat_ketetapan_dosen.*',
                'institusi.nama_institusi',
                'institusi.kop_surat',
                'institusi.rektor as nama_rektor'
            )
            ->where('surat_ketetapan_dosen.id', $id)
            ->first();
    }

    private function generateAndSavePdf($data): string
    {
        $pdfData = $this->buildPdfData($data);
        $rawFolder = Auth::user()?->prodi?->nama ?: ($data->nama_institusi ?: 'UMUM');
        $cleanFolder = preg_replace('/[\\\\\/:*?"<>|]/', ' ', $rawFolder);
        $cleanFolder = trim(preg_replace('/\s+/', ' ', $cleanFolder)) ?: 'UMUM';

        $directory = base_path('../public_html/pdf/' . $cleanFolder . '/SuratKetetapanDosenController');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $safeSlug = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $data->nidn ?: $data->nama_dosen);
        $fileName = 'surat_ketetapan_dosen_' . trim($safeSlug, '_') . '_' . uniqid() . '.pdf';
        $path = $directory . '/' . $fileName;

        Pdf::loadView('pdf.surat_ketetapan_dosen', $pdfData)
            ->setPaper('a4', 'portrait')
            ->save($path);

        return $path;
    }

    private function buildPdfData($data): array
    {
        $stempelBase64 = '';
        $tddBase64 = '';

        $sjRektor = SettingJabatan::with('tandaTangan')->where('kunci_jabatan', 'rektor')->first();
        $rektorNama = !empty($data->nama_rektor)
            ? $data->nama_rektor
            : ($sjRektor?->tandaTangan?->nama ?? '');

        if (isset($data->petanda_tangan) && in_array($data->petanda_tangan, ['ya', 'stempel'])) {
            if ($data->petanda_tangan === 'ya') {
                $ttdSource = null;

                if ($sjRektor && $sjRektor->tandaTangan) {
                    $ttdSource = $sjRektor->tandaTangan->tdd ?: $sjRektor->tandaTangan->gambar;
                }
                if (empty($ttdSource) && !empty($rektorNama)) {
                    $ttdMatch = TandaTangan::where('nama', $rektorNama)->first();
                    if ($ttdMatch) {
                        $ttdSource = $ttdMatch->tdd ?: $ttdMatch->gambar;
                    }
                }

                if (!empty($ttdSource)) {
                    if (str_starts_with($ttdSource, 'data:image')) {
                        $tddBase64 = $ttdSource;
                    } else {
                        $relTtd = ltrim($ttdSource, '/');
                        $ttdPath = public_path($relTtd);
                        if (!file_exists($ttdPath)) {
                            $ttdPath = base_path('../public_html/' . $relTtd);
                        }
                        if (file_exists($ttdPath)) {
                            $tddBase64 = SuratService::getBase64Image($ttdPath);
                        }
                    }
                }
            }

            $stempelPath = public_path('img/stempel.png');
            if (!file_exists($stempelPath)) {
                $stempelPath = base_path('../public_html/img/stempel.png');
            }
            if (file_exists($stempelPath)) {
                $stempelBase64 = SuratService::getBase64Image($stempelPath, 'image/png');
            }
        }

        $kopBase64 = '';
        if (!empty($data->kop_surat)) {
            $relKop = ltrim($data->kop_surat, '/');
            $institusiKopPath = public_path($relKop);
            if (!file_exists($institusiKopPath)) {
                $institusiKopPath = base_path('../public_html/' . $relKop);
            }
            if (file_exists($institusiKopPath)) {
                $kopBase64 = SuratService::getBase64Image($institusiKopPath);
            }
        }
        if (empty($kopBase64)) {
            $defaultKopPath = public_path('img/kop.jpg');
            if (!file_exists($defaultKopPath)) {
                $defaultKopPath = base_path('../public_html/img/kop.jpg');
            }
            if (file_exists($defaultKopPath)) {
                $kopBase64 = SuratService::getBase64Image($defaultKopPath);
            }
        }

        return [
            'institusi_id'         => $data->institusi_id,
            'nomor'                => $data->nomor,
            'nama_institusi'       => $data->nama_institusi ?: $data->instansi,
            'rektor'               => $rektorNama ?: '-',
            'alamat_penandatangan' => $data->alamat_penandatangan ?: 'Jl. Raya Raci No. 51 Bangil-Pasuruan',
            'tempat_ditetapkan'    => $data->tempat_ditetapkan ?: 'Bangil',
            'nama_dosen'           => $data->nama_dosen,
            'jabatan'              => $data->jabatan,
            'nidn'                 => $data->nidn,
            'instansi'             => $data->instansi,
            'jenis_kelamin'        => $data->jenis_kelamin,
            'tanggal_surat'        => SuratService::formatTanggalIndonesian($data->tanggal ?: date('Y-m-d')),
            'kopBase64'            => $kopBase64,
            'stempel'              => $stempelBase64,
            'ttd'                  => $tddBase64,
            'petanda_tangan'       => $data->petanda_tangan ?? 'ya',
        ];
    }
}
