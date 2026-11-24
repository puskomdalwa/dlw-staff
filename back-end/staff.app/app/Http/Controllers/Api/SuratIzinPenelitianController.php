<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\FakultasProdi;
use App\Models\LogSurat;
use App\Models\NoSurat;
use App\Models\Prodi;
use App\Models\SuratIzinPenelitian;
use App\Models\TandaTangan;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SuratIzinPenelitianController extends Controller
{
    public function index(Request $request)
    {
        $data = SuratIzinPenelitian::join('prodi', 'prodi.id', '=', 'surat_izin_penelitian.prodi_id');

        $data->select(
            'surat_izin_penelitian.*',
            'prodi.nama as nama_prodi',
            'prodi.alias as alias_prodi'
        );

        if ($request->filled("prodi_id")) {
            $data->where('surat_izin_penelitian.prodi_id', $request->prodi_id);
        }

        $login = Auth::user()->prodi;
        if ($login) {
            $data->where('surat_izin_penelitian.prodi_id', $login->id);
        }

        $auth = Auth::user()->jenis_kelamin;
        if ($auth == 'L') {
            $data->where('surat_izin_penelitian.jenis_kelamin', 'L');
        } else {
            $data->where('surat_izin_penelitian.jenis_kelamin', 'P');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->orWhere('surat_izin_penelitian.nim', 'like', "%{$search}%")
                    ->orWhere('surat_izin_penelitian.nama', 'like', "%{$search}%")
                    ->orWhere('surat_izin_penelitian.judul', 'like', "%{$search}%")
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
        try {
            if (empty($request->judul) && !empty($request->nim)) {
                $rawNim = trim($request->nim);
                $cleanNim = preg_replace('/[^a-zA-Z0-9]/', '', $rawNim);
                $pj = \App\Models\PengajuanJudul::where('nim', $rawNim)
                    ->orWhere('nim', $cleanNim)
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(nim, '.', ''), '-', ''), ' ', '') = ?", [$cleanNim])
                    ->orderBy('id', 'desc')
                    ->first();
                if ($pj) {
                    $request->merge(['judul' => $pj->judul_proposal_skripsi]);
                }
            }

            $validator = Validator::make($request->all(), [
                'prodi_id' => 'required',
                'no_surat' => 'required|string|max:255|unique:nomor,nomor',
                'nama' => 'required|string|max:255',
                'nim' => 'required|string|max:255',
                'prodi_mhs' => 'nullable|string|max:255',
                'kepada' => 'nullable|string|max:255',
                'judul' => 'nullable|string',
                'semester' => 'required|string|max:255',
                'dari_tanggal' => 'required|date',
                'tanggal' => 'required|date',
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

            $formattedNoSurat = SuratService::formatNomorSurat('SIP', $no_surat, $validate['tanggal'], $validate['prodi_id']);
            $sip = new SuratIzinPenelitian();
            $sip->nomor = $formattedNoSurat;
            $sip->nama = $validate['nama'];
            $sip->nim = $validate['nim'];
            $sip->prodi_mhs = $validate['prodi_mhs'] ?? null;
            $sip->kepada = $validate['kepada'] ?? null;
            $judul = $validate['judul'] ?? null;
            if (empty($judul) && !empty($validate['nim'])) {
                $pj = \App\Models\PengajuanJudul::where('nim', $validate['nim'])->orderBy('id', 'desc')->first();
                if ($pj) {
                    $judul = $pj->judul_proposal_skripsi;
                }
            }
            $sip->judul = $judul;
            $sip->prodi_id = $validate['prodi_id'];
            $sip->petanda_tangan = $validate['petanda_tangan'] ?? 'tidak';
            $sip->semester = $validate['semester'];
            $sip->dari_tanggal = $validate['dari_tanggal'];
            $sip->tanggal = $validate['tanggal'];
            $sip->user_id = Auth::user()->id;
            $sip->jenis_kelamin = Auth::user()->jenis_kelamin;
            $sip->status = 'pending';

            $signer = \App\Services\SuratService::resolveSigner('SIP', [
                'prodi_id' => $validate['prodi_id'] ?? null,
                'tanda_tangan_id' => $validate['tanda_tangan_id'] ?? null,
                'ketua' => $validate['ketua'] ?? null,
                'nidn' => $validate['nidn'] ?? ($validate['nidn_kepala'] ?? null),
            ]);
            if (\Illuminate\Support\Facades\Schema::hasColumn('surat_izin_penelitian', 'ketua')) {
                $sip->ketua = $signer['ketua'];
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('surat_izin_penelitian', 'nidn')) {
                $sip->nidn = $signer['nidn'];
            }
            $sip->save();

            $Nomor = new NoSurat();
            $Nomor->nomor = $no_surat;
            $Nomor->user_id = Auth::user()->id;
            $Nomor->save();

            $log = new LogSurat();
            $log->nomor = $no_surat;
            $log->nomor_surat = $formattedNoSurat;
            $log->nama_surat = 'Surat Izin Penelitian';
            $log->user_id = Auth::user()->id;
            $log->save();

            $this->generateAndSavePdf($sip->id);

            $prodiName = Auth::user()?->prodi
                ? Auth::user()->prodi->nama
                : (Prodi::find($sip->prodi_id)?->nama ?? 'UMUM');
            $nameTable = 'Surat Izin Penelitian';
            UploudSuratToDrive::dispatch($sip->id, $nameTable, $prodiName, SuratIzinPenelitian::class);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil ditambahkan'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal ditambahkan',
                'error' => $th->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $data = SuratIzinPenelitian::join('prodi', 'prodi.id', '=', 'surat_izin_penelitian.prodi_id')
            ->select('surat_izin_penelitian.*', 'prodi.nama as nama_prodi')
            ->where('surat_izin_penelitian.id', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }


        $nomorStr = $data->nomor_surat ?? $data->nomor ?? null;
        if ($nomorStr) {
            $parts = explode('/', $nomorStr);
            $firstPart = $parts[0];
            if (strpos($firstPart, '-') !== false) {
                $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
            }
            $data->no_surat = trim($firstPart);
        }

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            if (empty($request->judul) && !empty($request->nim)) {
                $rawNim = trim($request->nim);
                $cleanNim = preg_replace('/[^a-zA-Z0-9]/', '', $rawNim);
                $pj = \App\Models\PengajuanJudul::where('nim', $rawNim)
                    ->orWhere('nim', $cleanNim)
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(nim, '.', ''), '-', ''), ' ', '') = ?", [$cleanNim])
                    ->orderBy('id', 'desc')
                    ->first();
                if ($pj) {
                    $request->merge(['judul' => $pj->judul_proposal_skripsi]);
                }
            }

            $validator = Validator::make($request->all(), [
                'no_surat' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($id) {
                        $sip = \App\Models\SuratIzinPenelitian::find($id);
                        if ($sip) {
                            $originalNoSurat = '';
                            $nomorStr = $sip->nomor_surat ?? $sip->nomor ?? null;
                            if ($nomorStr) {
                                $parts = explode('/', $nomorStr);
                                $firstPart = $parts[0];
                                if (strpos($firstPart, '-') !== false) {
                                    $firstPart = substr($firstPart, strpos($firstPart, '-') + 1);
                                }
                                $originalNoSurat = trim($firstPart);
                            }
                            if ($value !== $originalNoSurat) {
                                if (\App\Models\NoSurat::where('nomor', $value)->exists()) {
                                    $fail('Nomor surat sudah terpakai.');
                                }
                            }
                        }
                    }
                ],
                'prodi_id' => 'required',
                'tanda_tangan_id' => 'nullable|exists:tanda_tangan,id',
                'nama' => 'required|string|max:255',
                'nim' => 'required|string|max:255',
                'prodi_mhs' => 'nullable|string|max:255',
                'kepada' => 'nullable|string|max:255',
                'judul' => 'nullable|string',
                'semester' => 'required|string|max:255',
                'dari_tanggal' => 'required|date',
                'tanggal' => 'required|date',
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

            $sip = SuratIzinPenelitian::find($id);
            if (!$sip) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $oldDriveFileId = $sip->drive_file_id;
            $oldLocalPath = $sip->local_path;

            $sip->nama = $validate['nama'];
            $sip->nim = $validate['nim'];
            $sip->prodi_mhs = $validate['prodi_mhs'] ?? $sip->prodi_mhs;
            $sip->kepada = $validate['kepada'] ?? $sip->kepada;
            $judul = $validate['judul'] ?? null;
            if (empty($judul) && !empty($validate['nim'])) {
                $pj = \App\Models\PengajuanJudul::where('nim', $validate['nim'])->orderBy('id', 'desc')->first();
                if ($pj) {
                    $judul = $pj->judul_proposal_skripsi;
                }
            }
            $sip->judul = $judul;
            $sip->semester = $validate['semester'];
            $sip->dari_tanggal = $validate['dari_tanggal'];
            $sip->tanggal = $validate['tanggal'];
            $sip->prodi_id = $validate['prodi_id'];
            if (array_key_exists('tanda_tangan_id', $validate)) {
                $sip->tanda_tangan_id = $validate['tanda_tangan_id'];
            }

            $formattedNoSurat = \App\Services\SuratService::formatNomorSurat('SIP', $validate['no_surat'], $validate['tanggal'], $validate['prodi_id'] ?? null);
            $sip->nomor = $formattedNoSurat;


            $sip->jenis_kelamin = Auth::user()->jenis_kelamin;
            $sip->petanda_tangan = $validate['petanda_tangan'] ?? 'tidak';

            // Delete old file from Google Drive if exists
            if (!empty($oldDriveFileId)) {
                \App\Services\GoogleDrive::deleteFile($oldDriveFileId);
            }
            if (!empty($oldLocalPath) && file_exists($oldLocalPath)) {
                @unlink($oldLocalPath);
            }

            $sip->drive_file_id = null;
            $sip->drive_link = null;
            $sip->status = 'pending';

            $signer = \App\Services\SuratService::resolveSigner('SIP', [
                'prodi_id' => $validate['prodi_id'] ?? $sip->prodi_id ?? null,
                'tanda_tangan_id' => $validate['tanda_tangan_id'] ?? $sip->tanda_tangan_id ?? null,
                'ketua' => $validate['ketua'] ?? $sip->ketua ?? null,
                'nidn' => $validate['nidn'] ?? ($validate['nidn_kepala'] ?? $sip->nidn ?? null),
            ]);
            if (\Illuminate\Support\Facades\Schema::hasColumn('surat_izin_penelitian', 'ketua')) {
                $sip->ketua = $signer['ketua'];
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('surat_izin_penelitian', 'nidn')) {
                $sip->nidn = $signer['nidn'];
            }
            $sip->save();

            $this->generateAndSavePdf($sip->id);

            $prodiName = Auth::user()?->prodi
                ? Auth::user()->prodi->nama
                : (Prodi::find($sip->prodi_id)?->nama ?? 'UMUM');
            $nameTable = 'Surat Izin Penelitian';
            UploudSuratToDrive::dispatch($sip->id, $nameTable, $prodiName, SuratIzinPenelitian::class);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal diupdate',
                'error' => $th->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $sip = SuratIzinPenelitian::find($id);
            if (!$sip) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }
            $sip->delete();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status' => false,
                'message' => 'Data gagal dihapus'
            ]);
        }
    }

    public function downloadPdf($id)
    {
        try {
            $data = SuratIzinPenelitian::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $viewPath = resource_path('views/pdf/surat_izin_penelitian.blade.php');
            $viewMtime = file_exists($viewPath) ? filemtime($viewPath) : 0;
            $hasLocalFile = !empty($data->local_path) && file_exists($data->local_path) && is_readable($data->local_path);
            $pdfMtime = $hasLocalFile ? filemtime($data->local_path) : 0;

            if (!$hasLocalFile || $viewMtime > $pdfMtime) {
                $this->generateAndSavePdf($data->id);
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
            Log::error("SuratIzinPenelitian downloadPdf error for ID {$id}: " . $th->getMessage(), [
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

    private function generateAndSavePdf($id)
    {
        $data = SuratIzinPenelitian::leftJoin('prodi', 'prodi.id', '=', 'surat_izin_penelitian.prodi_id')
            ->leftJoin('fakultas_prodi', 'fakultas_prodi.prodi_id', '=', 'prodi.id')
            ->leftJoin('fakultas', 'fakultas.id', '=', 'fakultas_prodi.fakultas_id')
            ->leftJoin('tanda_tangan', 'tanda_tangan.id', '=', \DB::raw('COALESCE(surat_izin_penelitian.tanda_tangan_id, fakultas.tanda_tangan_id)'))
            ->select(
                'surat_izin_penelitian.*',
                'prodi.nama as prodi_name',
                'prodi.nama_kepala',
                'prodi.nidn_kepala',
                'fakultas.nama as fakultas_name',
                'fakultas.dekan as nama_dekan',
                'fakultas.nidn_dekan',
                'tanda_tangan.nama as nama_ttd',
                \DB::raw('COALESCE(tanda_tangan.tdd, tanda_tangan.gambar) as ttd'),
            )
            ->where('surat_izin_penelitian.id', $id)
            ->first();

        if (!$data) {
            return null;
        }

        $kopPath = base_path('../public_html/img/kop.jpg');
        $kopBase64 = SuratService::getBase64Image($kopPath);

        $tddBase64 = '';
        $stempelBase64 = '';

        if (in_array($data->petanda_tangan, ['ya', 'stempel'])) {
            $stempelPath = base_path('../public_html/img/stempel.png');
            if (file_exists($stempelPath)) {
                $stempelBase64 = SuratService::getBase64Image($stempelPath, 'image/png');
            }
        }

        if (in_array($data->petanda_tangan, ['ya'])) {
            if (!empty($data->ttd)) {
                if (str_starts_with($data->ttd, 'data:image')) {
                    $tddBase64 = $data->ttd;
                } else {
                    $tddPath = base_path('../public_html/' . $data->ttd);
                    if (file_exists($tddPath)) {
                        $tddBase64 = SuratService::getBase64Image($tddPath);
                    }
                }
            }
        }

        $rawJudul = trim($data->judul ?? '');
        $cleanJudul = trim($rawJudul, '"\' ');
        $hasArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $cleanJudul);
        if ($hasArabic && class_exists('\ArPHP\I18N\Arabic')) {
            $arabic = new \ArPHP\I18N\Arabic();
            $cleanJudul = preg_replace('/\s+/u', ' ', $cleanJudul);
            $glyphs = $arabic->utf8Glyphs($cleanJudul, 60);
            $formattedJudul = ($cleanJudul !== $rawJudul) ? '"' . $glyphs . '"' : $glyphs;
        } else {
            $formattedJudul = $rawJudul;
        }

        $pdfData = [
            'prodi_id' => $data->prodi_id,
            'nomor' => $data->nomor,
            'nama' => $data->nama,
            'nim' => $data->nim,
            'kepada' => $data->kepada,
            'judul' => $formattedJudul,
            'semester' => $data->semester,
            'dari_tanggal' => \App\Services\SuratService::formatTanggalIndonesian($data->dari_tanggal),
            'tanggal' => \App\Services\SuratService::formatTanggalIndonesian($data->tanggal),
            'ketua' => $data->ketua ?? null,
            'nidn' => $data->nidn ?? null,
            'nama_dekan' => !empty($data->ketua) ? $data->ketua : ($data->nama_dekan ?? $data->nama_ttd),
            'nidn_dekan' => !empty($data->nidn) ? $data->nidn : $data->nidn_dekan,
            'prodi_name' => $data->prodi_name,
            'fakultas_name' => $data->fakultas_name,
            'kopBase64' => $kopBase64,
            'ttd' => $tddBase64,
            'stempel' => $stempelBase64,
            'petanda_tangan' => $data->petanda_tangan ?? 'tidak'
        ];

        $rawProdiName = Auth::user()?->prodi ? Auth::user()->prodi->nama : ($data->prodi_name ?? 'UMUM');
        $safeNim = SuratService::sanitizeFileSegment($data->nim, (string) $data->id);
        $directory = SuratService::resolvePdfDirectory($rawProdiName, 'SuratIzinPenelitianController');

        $pdf = Pdf::loadView('pdf.surat_izin_penelitian', $pdfData)->setPaper('a4', 'portrait');
        $fileName = 'surat_izin_penelitian_' . $safeNim . '_' . uniqid() . '.pdf';

        $path = $directory . $fileName;
        $pdf->save($path);

        SuratIzinPenelitian::where('id', $data->id)->update(['local_path' => $path]);

        return $path;
    }

    public function getProdi()
    {
        try {
            $login = Auth::user()->prodi;
            if ($login) {
                $prodi = Prodi::where('id', $login->id)->get();
            } else {
                $prodi = Prodi::all();
            }
            return response()->json(['status' => true, 'data' => $prodi]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => 'Gagal ambil data prodi']);
        }
    }
}
