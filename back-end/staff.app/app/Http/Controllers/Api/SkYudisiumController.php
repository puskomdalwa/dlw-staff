<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\Fakultas;
use App\Models\FakultasProdi;
use App\Models\LogSurat;
use App\Models\NoSurat;
use App\Models\Prodi;
use App\Models\SkYudisium;
use App\Models\TandaTangan;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SkYudisiumController extends Controller
{
    public function index(Request $request)
    {
        $groupSql = "COALESCE(NULLIF(TRIM(sk_yudisium.nomor_surat), ''), sk_yudisium.batch_id, CONCAT('ID_', sk_yudisium.id))";

        $data = SkYudisium::leftJoin('prodi', 'prodi.id', '=', 'sk_yudisium.prodi_id')
            ->leftJoin('tanda_tangan', 'tanda_tangan.id', '=', 'sk_yudisium.tanda_tangan_id')
            ->select(
                DB::raw("{$groupSql} as group_key"),
                DB::raw('MIN(sk_yudisium.id) as id'),
                DB::raw('MAX(sk_yudisium.batch_id) as batch_id'),
                DB::raw('MAX(sk_yudisium.nomor_surat) as nomor_surat'),
                DB::raw('MAX(sk_yudisium.tanggal) as tanggal'),
                DB::raw('MAX(sk_yudisium.prodi) as prodi'),
                DB::raw('GROUP_CONCAT(DISTINCT COALESCE(prodi.nama, sk_yudisium.prodi) SEPARATOR ", ") as nama_prodi'),
                DB::raw('MAX(prodi.alias) as alias_prodi'),
                DB::raw('MAX(tanda_tangan.nama) as nama_ttd'),
                DB::raw('MAX(sk_yudisium.drive_link) as drive_link'),
                DB::raw('MAX(sk_yudisium.drive_file_id) as drive_file_id'),
                DB::raw('MAX(sk_yudisium.local_path) as local_path'),
                DB::raw('MAX(sk_yudisium.status) as status'),
                DB::raw('MAX(sk_yudisium.petanda_tangan) as petanda_tangan'),
                DB::raw('COUNT(sk_yudisium.id) as total_mahasiswa'),
                DB::raw('MAX(sk_yudisium.created_at) as created_at')
            )
            ->groupBy(DB::raw($groupSql));

        if ($request->filled('prodi_id')) {
            $prodiFilter = Prodi::find($request->prodi_id);
            $data->where(function ($q) use ($request, $prodiFilter) {
                $q->where('sk_yudisium.prodi_id', $request->prodi_id);
                if ($prodiFilter) {
                    $q->orWhere('sk_yudisium.prodi', $prodiFilter->nama)
                        ->orWhere('sk_yudisium.prodi', $prodiFilter->alias);
                }
            });
        }

        $login = Auth::user()?->prodi;
        if ($login) {
            $data->where(function ($q) use ($login) {
                $q->where('sk_yudisium.prodi_id', $login->id)
                    ->orWhere('sk_yudisium.prodi', $login->nama)
                    ->orWhere('sk_yudisium.prodi', $login->alias);
            });
        }

        $auth = Auth::user()?->jenis_kelamin;
        if ($auth == 'L') {
            $data->where('sk_yudisium.jenis_kelamin', 'L');
        } elseif ($auth == 'P') {
            $data->where('sk_yudisium.jenis_kelamin', 'P');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->orWhere('sk_yudisium.nim', 'like', "%{$search}%")
                    ->orWhere('sk_yudisium.nama_mhs', 'like', "%{$search}%")
                    ->orWhere('sk_yudisium.prodi', 'like', "%{$search}%")
                    ->orWhere('sk_yudisium.nomor_surat', 'like', "%{$search}%")
                    ->orWhere('sk_yudisium.nilai_skripsi', 'like', "%{$search}%")
                    ->orWhere('prodi.nama', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sortBy', 'id');
        $sortType = $request->input('sortType', 'desc');

        if ($sortBy === 'id') {
            $data->orderBy(DB::raw('MIN(sk_yudisium.id)'), $sortType);
        } elseif ($sortBy === 'nomor_surat') {
            $data->orderBy(DB::raw('MAX(sk_yudisium.nomor_surat)'), $sortType);
        } elseif ($sortBy === 'tanggal') {
            $data->orderBy(DB::raw('MAX(sk_yudisium.tanggal)'), $sortType);
        } elseif ($sortBy === 'total_mahasiswa') {
            $data->orderBy(DB::raw('COUNT(sk_yudisium.id)'), $sortType);
        } else {
            $data->orderBy(DB::raw('MIN(sk_yudisium.id)'), 'desc');
        }

        $data = $data->paginate($request->input('limit', 10));

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function getMahasiswa($id)
    {
        $sk = SkYudisium::find($id);
        if (!$sk) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        if (!empty($sk->nomor_surat)) {
            $query = SkYudisium::where('sk_yudisium.nomor_surat', $sk->nomor_surat);
        } elseif (!empty($sk->batch_id)) {
            $query = SkYudisium::where('sk_yudisium.batch_id', $sk->batch_id);
        } else {
            $query = SkYudisium::where('sk_yudisium.id', $sk->id);
        }

        $mahasiswa = $query
            ->leftJoin('prodi', 'prodi.id', '=', 'sk_yudisium.prodi_id')
            ->select(
                'sk_yudisium.*',
                'prodi.nama as prodi_nama'
            )
            ->orderBy('sk_yudisium.id', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'sk' => $sk,
                'mahasiswa' => $mahasiswa,
                'total' => $mahasiswa->count(),
            ],
            'message' => 'Data mahasiswa SK Yudisium berhasil diambil'
        ]);
    }

    public function store(Request $request)
    {
        Log::info('SkYudisium store request', $request->all());
        try {
            $validator = Validator::make($request->all(), [
                'nama_mhs'       => 'required|string|max:255',
                'nim'            => 'required|string|max:255',
                'prodi'          => 'required|string|max:255',
                'prodi_id'       => 'nullable',
                'no_surat'       => 'nullable|string|max:255',
                'nomor_surat'    => 'nullable|string|max:255',
                'nilai_skripsi'  => 'nullable|string|max:255',
                'tanggal'        => 'nullable|date',
                'jenis_kelamin'  => 'nullable|in:L,P',
                'petanda_tangan' => 'nullable|in:ya,tidak,stempel',
                'ketua' => 'nullable|string|max:255',
                'nidn' => 'nullable|string|max:255',
                'tanda_tangan_id'=> 'nullable',
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

            $prodiId = $prodiObj ? $prodiObj->id : ($validate['prodi_id'] ?? null);
            $tanggal = $validate['tanggal'] ?? date('Y-m-d');

            // Nomor Surat
            $rawNoSurat = $validate['no_surat'] ?? null;
            $formattedNoSurat = $validate['nomor_surat'] ?? null;

            if (!empty($rawNoSurat)) {
                $formattedNoSurat = SuratService::formatNomorSurat('SKY', $rawNoSurat, $tanggal, $prodiId);

                // Catat ke NoSurat & LogSurat jika belum tercatat
                if (!NoSurat::where('nomor', $rawNoSurat)->exists()) {
                    $nomorObj = new NoSurat();
                    $nomorObj->nomor = $rawNoSurat;
                    $nomorObj->user_id = Auth::id() ?? 1;
                    $nomorObj->save();
                }

                $log = new LogSurat();
                $log->nomor = $rawNoSurat;
                $log->nomor_surat = $formattedNoSurat;
                $log->nama_surat = 'SK Yudisium';
                $log->user_id = Auth::id() ?? 1;
                $log->save();
            }

            // Tanda Tangan Dekan Lookup
            $ttdId = $validate['tanda_tangan_id'] ?? null;
            if (!$ttdId && $prodiId) {
                $fakultas = DB::table('fakultas_prodi')
                    ->join('fakultas', 'fakultas.id', '=', 'fakultas_prodi.fakultas_id')
                    ->where('fakultas_prodi.prodi_id', $prodiId)
                    ->select('fakultas.*')
                    ->first();

                if ($fakultas) {
                    $rawTtd = $fakultas->tanda_tangan_id;
                    if (is_array($rawTtd)) {
                        $ttdId = $rawTtd[0] ?? null;
                    } elseif (is_numeric($rawTtd)) {
                        $ttdId = (int)$rawTtd;
                    } elseif (is_string($rawTtd)) {
                        $decoded = json_decode($rawTtd, true);
                        $ttdId = is_array($decoded) ? ($decoded[0] ?? null) : (is_numeric($rawTtd) ? (int)$rawTtd : null);
                    }
                }
            }

            $sk = new SkYudisium();
            $sk->batch_id       = 'SKY-' . date('YmdHis') . '-' . Str::random(6);
            $sk->nama_mhs       = $validate['nama_mhs'];
            $sk->nim            = $validate['nim'];
            $sk->prodi          = $validate['prodi'];
            $sk->prodi_id       = $prodiId;
            $sk->tanda_tangan_id= $ttdId;
            $sk->tanggal        = $tanggal;
            $sk->jenis_kelamin  = $validate['jenis_kelamin'] ?? Auth::user()?->jenis_kelamin ?? 'L';
            $sk->nomor_surat    = $formattedNoSurat;
            $sk->nilai_skripsi  = $validate['nilai_skripsi'] ?? null;
            $sk->petanda_tangan = $validate['petanda_tangan'] ?? 'tidak';
            $sk->user_id        = Auth::id();
            $sk->status         = 'pending';
            
            $signer = \App\Services\SuratService::resolveSigner('SKY', [
                'prodi_id' => $prodiId ?? null,
                'tanda_tangan_id' => $validate['tanda_tangan_id'] ?? null,
                'ketua' => $validate['ketua'] ?? null,
                'nidn' => $validate['nidn'] ?? ($validate['nidn_kepala'] ?? null),
            ]);
            $sk->ketua = $signer['ketua'];
            $sk->nidn = $signer['nidn'];
            $sk->save();

            $this->generatePdfFile($sk->id);

            $prodiName = Auth::user()?->prodi ? Auth::user()->prodi->nama : ($prodiObj?->nama ?: ($sk->prodi ?: 'UMUM'));
            $nameTable = 'SK Yudisium';
            UploudSuratToDrive::dispatch($sk->id, $nameTable, $prodiName, SkYudisium::class);

            return response()->json([
                'status' => true,
                'data' => $sk,
                'message' => 'Data SK Yudisium berhasil ditambahkan'
            ]);
        } catch (\Throwable $th) {
            Log::error('SkYudisium store error: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Data gagal ditambahkan',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $data = SkYudisium::leftJoin('prodi', 'prodi.id', '=', 'sk_yudisium.prodi_id')
            ->leftJoin('tanda_tangan', 'tanda_tangan.id', '=', 'sk_yudisium.tanda_tangan_id')
            ->select(
                'sk_yudisium.*',
                'prodi.nama as nama_prodi',
                'prodi.alias as alias_prodi',
                'tanda_tangan.nama as nama_ttd'
            )
            ->where('sk_yudisium.id', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        // Ekstrak no_surat murni (nomor urut) jika ada
        $noSuratPure = '';
        if (!empty($data->nomor_surat)) {
            $parts = explode('/', $data->nomor_surat);
            $firstPart = trim($parts[0] ?? '');
            if (str_contains($firstPart, '-')) {
                $subParts = explode('-', $firstPart);
                $noSuratPure = end($subParts);
            } else {
                $noSuratPure = $firstPart;
            }
        }
        $data->no_surat = $noSuratPure;

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info("SkYudisium update request (id: {$id})", $request->all());
        try {
            $validator = Validator::make($request->all(), [
                'nama_mhs'       => 'required|string|max:255',
                'nim'            => 'required|string|max:255',
                'prodi'          => 'required|string|max:255',
                'prodi_id'       => 'nullable',
                'no_surat'       => 'nullable|string|max:255',
                'nomor_surat'    => 'nullable|string|max:255',
                'nilai_skripsi'  => 'nullable|string|max:255',
                'tanggal'        => 'nullable|date',
                'jenis_kelamin'  => 'nullable|in:L,P',
                'petanda_tangan' => 'nullable|in:ya,tidak,stempel',
                'ketua' => 'nullable|string|max:255',
                'nidn' => 'nullable|string|max:255',
                'tanda_tangan_id'=> 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validate = $validator->validated();

            $sk = SkYudisium::find($id);
            if (!$sk) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $oldDriveFileId = $sk->drive_file_id;
            $oldLocalPath   = $sk->local_path;

            $prodiObj = Auth::user()?->prodi;
            if (!$prodiObj && !empty($validate['prodi_id'])) {
                $prodiObj = Prodi::find($validate['prodi_id']);
            }
            if (!$prodiObj && !empty($validate['prodi'])) {
                $prodiObj = Prodi::where('nama', $validate['prodi'])
                    ->orWhere('alias', $validate['prodi'])
                    ->first();
            }

            $prodiId = $prodiObj ? $prodiObj->id : ($validate['prodi_id'] ?? $sk->prodi_id);
            $tanggal = $validate['tanggal'] ?? $sk->tanggal ?? date('Y-m-d');

            // Nomor Surat
            $rawNoSurat = $validate['no_surat'] ?? null;
            $formattedNoSurat = $validate['nomor_surat'] ?? $sk->nomor_surat;

            if (!empty($rawNoSurat)) {
                $formattedNoSurat = SuratService::formatNomorSurat('SKY', $rawNoSurat, $tanggal, $prodiId);

                if (!NoSurat::where('nomor', $rawNoSurat)->exists()) {
                    $nomorObj = new NoSurat();
                    $nomorObj->nomor = $rawNoSurat;
                    $nomorObj->user_id = Auth::id() ?? 1;
                    $nomorObj->save();
                }

                $log = new LogSurat();
                $log->nomor = $rawNoSurat;
                $log->nomor_surat = $formattedNoSurat;
                $log->nama_surat = 'SK Yudisium';
                $log->user_id = Auth::id() ?? 1;
                $log->save();
            }

            // Tanda tangan
            $ttdId = $validate['tanda_tangan_id'] ?? $sk->tanda_tangan_id;
            if (!$ttdId && $prodiId) {
                $fakultas = DB::table('fakultas_prodi')
                    ->join('fakultas', 'fakultas.id', '=', 'fakultas_prodi.fakultas_id')
                    ->where('fakultas_prodi.prodi_id', $prodiId)
                    ->select('fakultas.*')
                    ->first();

                if ($fakultas) {
                    $rawTtd = $fakultas->tanda_tangan_id;
                    if (is_array($rawTtd)) {
                        $ttdId = $rawTtd[0] ?? null;
                    } elseif (is_numeric($rawTtd)) {
                        $ttdId = (int)$rawTtd;
                    } elseif (is_string($rawTtd)) {
                        $decoded = json_decode($rawTtd, true);
                        $ttdId = is_array($decoded) ? ($decoded[0] ?? null) : (is_numeric($rawTtd) ? (int)$rawTtd : null);
                    }
                }
            }

            $sk->nama_mhs       = $validate['nama_mhs'];
            $sk->nim            = $validate['nim'];
            $sk->prodi          = $validate['prodi'];
            $sk->prodi_id       = $prodiId;
            $sk->tanda_tangan_id= $ttdId;
            $sk->tanggal        = $tanggal;
            $sk->jenis_kelamin  = $validate['jenis_kelamin'] ?? $sk->jenis_kelamin ?? 'L';
            $sk->nomor_surat    = $formattedNoSurat;
            $sk->nilai_skripsi  = $validate['nilai_skripsi'] ?? $sk->nilai_skripsi;
            $sk->petanda_tangan = $validate['petanda_tangan'] ?? $sk->petanda_tangan ?? 'tidak';

            if (!empty($oldDriveFileId)) {
                \App\Services\GoogleDrive::deleteFile($oldDriveFileId);
            }
            if (!empty($oldLocalPath) && file_exists($oldLocalPath)) {
                @unlink($oldLocalPath);
            }

            $sk->drive_file_id = null;
            $sk->drive_link    = null;
            $sk->status        = 'pending';
            
            $signer = \App\Services\SuratService::resolveSigner('SKY', [
                'prodi_id' => $prodiId ?? $sk->prodi_id ?? null,
                'tanda_tangan_id' => $validate['tanda_tangan_id'] ?? $sk->tanda_tangan_id ?? null,
                'ketua' => $validate['ketua'] ?? $sk->ketua ?? null,
                'nidn' => $validate['nidn'] ?? ($validate['nidn_kepala'] ?? $sk->nidn ?? null),
            ]);
            $sk->ketua = $signer['ketua'];
            $sk->nidn = $signer['nidn'];
            $sk->save();

            $this->generatePdfFile($sk->id);

            $prodiName = Auth::user()?->prodi ? Auth::user()->prodi->nama : ($prodiObj?->nama ?: ($sk->prodi ?: 'UMUM'));
            $nameTable = 'SK Yudisium';
            UploudSuratToDrive::dispatch($sk->id, $nameTable, $prodiName, SkYudisium::class);

            return response()->json([
                'status' => true,
                'data' => $sk,
                'message' => 'Data SK Yudisium berhasil diperbarui'
            ]);
        } catch (\Throwable $th) {
            Log::error('SkYudisium update error: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Data gagal diperbarui',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $sk = SkYudisium::find($id);
            if (!$sk) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if (!empty($sk->nomor_surat)) {
                $batchItems = SkYudisium::where('nomor_surat', $sk->nomor_surat)->get();
            } elseif (!empty($sk->batch_id)) {
                $batchItems = SkYudisium::where('batch_id', $sk->batch_id)->get();
            } else {
                $batchItems = collect([$sk]);
            }

            if (!empty($sk->drive_file_id)) {
                \App\Services\GoogleDrive::deleteFile($sk->drive_file_id);
            }
            if (!empty($sk->local_path) && file_exists($sk->local_path)) {
                @unlink($sk->local_path);
            }

            SkYudisium::whereIn('id', $batchItems->pluck('id'))->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data SK Yudisium beserta seluruh mahasiswa di dalamnya berhasil dihapus'
            ]);
        } catch (\Throwable $th) {
            Log::error('SkYudisium destroy error: ' . $th->getMessage());
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
            $data = SkYudisium::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $viewPath = resource_path('views/pdf/sk_yudisium.blade.php');
            $viewMtime = file_exists($viewPath) ? filemtime($viewPath) : 0;
            $pdfMtime = (!empty($data->local_path) && file_exists($data->local_path)) ? filemtime($data->local_path) : 0;

            if (empty($data->local_path) || !file_exists($data->local_path) || $viewMtime > $pdfMtime) {
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
            Log::error('SkYudisium downloadPdf error: ' . $th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF']);
        }
    }

    public function generatePdfFile($idOrBatchId): ?string
    {
        try {
            if (is_numeric($idOrBatchId)) {
                $sk = SkYudisium::find($idOrBatchId);
            } else {
                $sk = SkYudisium::where('batch_id', $idOrBatchId)->first();
            }

            if (!$sk) {
                return null;
            }

            // Ambil semua mahasiswa dalam satu batch / SK
            if (!empty($sk->nomor_surat)) {
                $mahasiswas = SkYudisium::where('sk_yudisium.nomor_surat', $sk->nomor_surat)
                    ->leftJoin('prodi', 'prodi.id', '=', 'sk_yudisium.prodi_id')
                    ->select('sk_yudisium.*', 'prodi.nama as prodi_nama')
                    ->orderBy('sk_yudisium.id', 'asc')
                    ->get();
            } elseif (!empty($sk->batch_id)) {
                $mahasiswas = SkYudisium::where('sk_yudisium.batch_id', $sk->batch_id)
                    ->leftJoin('prodi', 'prodi.id', '=', 'sk_yudisium.prodi_id')
                    ->select('sk_yudisium.*', 'prodi.nama as prodi_nama')
                    ->orderBy('sk_yudisium.id', 'asc')
                    ->get();
            } else {
                $mahasiswas = collect([$sk]);
            }

            $prodiObj = null;
            if (!empty($sk->prodi_id)) {
                $prodiObj = Prodi::find($sk->prodi_id);
            }
            if (!$prodiObj && !empty($sk->prodi)) {
                $prodiObj = Prodi::where('nama', $sk->prodi)
                    ->orWhere('alias', $sk->prodi)
                    ->orWhere('nama', 'like', "%{$sk->prodi}%")
                    ->first();
            }

            if (!$prodiObj && isset($mahasiswas)) {
                foreach ($mahasiswas as $m) {
                    if (!empty($m->prodi_id)) {
                        $prodiObj = Prodi::find($m->prodi_id);
                        if ($prodiObj) break;
                    }
                    if (!empty($m->prodi)) {
                        $prodiObj = Prodi::where('nama', $m->prodi)
                            ->orWhere('alias', $m->prodi)
                            ->orWhere('nama', 'like', "%{$m->prodi}%")
                            ->first();
                        if ($prodiObj) break;
                    }
                }
            }

            $fakultas = null;
            if ($prodiObj) {
                $fakultas = DB::table('fakultas_prodi')
                    ->join('fakultas', 'fakultas.id', '=', 'fakultas_prodi.fakultas_id')
                    ->where('fakultas_prodi.prodi_id', $prodiObj->id)
                    ->select('fakultas.*')
                    ->first();
            }

            // Jika fakultas belum ditemukan, deteksi dari nomor surat (misal /FS/, /FT/, /FD/, /FA/)
            if (!$fakultas && !empty($sk->nomor_surat)) {
                $noUpper = strtoupper($sk->nomor_surat);
                if (str_contains($noUpper, '/FS/') || str_contains($noUpper, 'SYARIAH')) {
                    $fakultas = DB::table('fakultas')->where('nama', 'like', '%Syariah%')->first();
                } elseif (str_contains($noUpper, '/FT/') || str_contains($noUpper, 'TARBIYAH')) {
                    $fakultas = DB::table('fakultas')->where('nama', 'like', '%Tarbiyah%')->first();
                } elseif (str_contains($noUpper, '/FD/') || str_contains($noUpper, '/FDK/') || str_contains($noUpper, 'DAKWAH')) {
                    $fakultas = DB::table('fakultas')->where('nama', 'like', '%Dakwah%')->first();
                } elseif (str_contains($noUpper, '/FA/') || str_contains($noUpper, 'ADAB')) {
                    $fakultas = DB::table('fakultas')->where('nama', 'like', '%Adab%')->first();
                }
            }

            // Default fallback ke Syariah jika tetap belum ketemu
            if (!$fakultas) {
                $fakultas = DB::table('fakultas')->where('nama', 'like', '%Syariah%')->first();
            }

            // Bangun list tembusan sesuai prodi-prodi di bawah fakultas tersebut
            $tembusanList = [];
            if ($fakultas) {
                $prodisFakultas = DB::table('fakultas_prodi')
                    ->join('prodi', 'prodi.id', '=', 'fakultas_prodi.prodi_id')
                    ->where('fakultas_prodi.fakultas_id', $fakultas->id)
                    ->orderBy('prodi.id', 'asc')
                    ->select('prodi.id', 'prodi.nama', 'prodi.alias')
                    ->get();

                foreach ($prodisFakultas as $p) {
                    $alias = $p->alias ?: $p->nama;
                    if ($p->id == 6 || stripos($p->nama, 'Hukum Keluarga') !== false) {
                        $alias = 'HKI';
                    } elseif ($p->id == 3 || stripos($p->nama, 'Ekonomi Syariah') !== false) {
                        $alias = 'ESy';
                    } elseif ($p->id == 7 || stripos($p->nama, 'Sejarah') !== false) {
                        $alias = 'SPI';
                    }
                    $tembusanList[] = "Ka. Prodi " . $alias;
                }

                // Urutkan khusus Fakultas Syariah: HKI dahulu baru ESy
                if (stripos($fakultas->nama, 'Syariah') !== false) {
                    usort($tembusanList, function ($a, $b) {
                        if (str_contains($a, 'HKI')) return -1;
                        if (str_contains($b, 'HKI')) return 1;
                        return 0;
                    });
                }
            }

            $tembusanList[] = "Para Kepala Biro";

            $namaFakultas = $fakultas ? $fakultas->nama : 'SYARIAH';
            // Pastikan tidak ada duplikasi kata "FAKULTAS" jika sudah diawali kata tersebut
            if (stripos($namaFakultas, 'FAKULTAS') === 0) {
                $namaFakultas = trim(substr($namaFakultas, 8));
            }

            $namaDekan = $fakultas ? ($fakultas->dekan ?? '') : '';
            $nidnDekan = $fakultas ? ($fakultas->nidn_dekan ?? $fakultas->nidn ?? '') : '';

            $ttdId = $sk->tanda_tangan_id;
            if (!$ttdId && $fakultas) {
                $rawTtd = $fakultas->tanda_tangan_id;
                if (is_array($rawTtd)) {
                    $ttdId = $rawTtd[0] ?? null;
                } elseif (is_numeric($rawTtd)) {
                    $ttdId = (int)$rawTtd;
                } elseif (is_string($rawTtd)) {
                    $decoded = json_decode($rawTtd, true);
                    $ttdId = is_array($decoded) ? ($decoded[0] ?? null) : (is_numeric($rawTtd) ? (int)$rawTtd : null);
                }
            }

            $ttdModel = $ttdId ? TandaTangan::find($ttdId) : null;
            if (!$ttdModel && !empty($namaDekan)) {
                $ttdModel = TandaTangan::where('nama', 'like', "%{$namaDekan}%")->first();
            }

            if ($ttdModel && empty($namaDekan)) {
                $namaDekan = $ttdModel->nama;
            }

            $kopPath = base_path('../public_html/img/kop.jpg');
            $kopBase64 = SuratService::getBase64Image($kopPath);

            $tddBase64 = '';
            $stempelBase64 = '';

            if (isset($sk->petanda_tangan) && in_array($sk->petanda_tangan, ['ya', 'stempel'])) {
                $ttdRaw = $ttdModel ? ($ttdModel->tdd ?: $ttdModel->gambar) : null;
                if ($sk->petanda_tangan === 'ya' && !empty($ttdRaw)) {
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

            $tanggalParsed = !empty($sk->tanggal) ? Carbon::parse($sk->tanggal) : Carbon::now();
            $tanggalFormatted = SuratService::formatTanggalIndonesian($tanggalParsed->format('Y-m-d'));
            $tahunSurat = $tanggalParsed->format('Y');

            $prodiDisplay = $prodiObj ? $prodiObj->nama : $sk->prodi;

            $pdfData = [
                'nama_fakultas'   => $namaFakultas,
                'nomor_surat'     => $sk->nomor_surat ?: '.......................................................',
                'nama_mhs'        => $sk->nama_mhs,
                'nim'             => $sk->nim,
                'prodi'           => $prodiDisplay,
                'nilai_skripsi'   => $sk->nilai_skripsi ?: '-',
                'tanggal'         => $tanggalFormatted,
                'tahun'           => $tahunSurat,
                'ketua'           => $sk->ketua,
                'nidn'            => $sk->nidn,
                'nama_dekan'      => !empty($sk->ketua) ? $sk->ketua : ($namaDekan ?: 'Dr. Abdul Kadir, M.Ag'),
                'nidn_dekan'      => !empty($sk->nidn) ? $sk->nidn : ($nidnDekan ?: '2104087201'),
                'kopBase64'       => $kopBase64,
                'ttd'             => $tddBase64,
                'stempel'         => $stempelBase64,
                'petanda_tangan'  => $sk->petanda_tangan ?? 'tidak',
                'tembusan_list'   => $tembusanList,
                'tembusan_prodis' => $tembusanList,
                'mahasiswas'      => $mahasiswas,
            ];

            if (!empty($sk->local_path) && file_exists($sk->local_path)) {
                @unlink($sk->local_path);
            }

            $prodiFolder = Auth::user()?->prodi
                ? Auth::user()->prodi->nama
                : ($prodiObj?->nama ?: ($sk->prodi ?: 'UMUM'));

            $directory = base_path('../public_html/pdf/' . $prodiFolder . '/SkYudisiumController/');
            $pdf = Pdf::loadView('pdf.sk_yudisium', $pdfData)->setPaper('a4', 'portrait');
            $fileName = 'sk_yudisium_' . ($sk->batch_id ?: ($sk->nim ?: 'doc')) . '_' . uniqid() . '.pdf';

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $path = $directory . $fileName;
            $pdf->save($path);

            // Update local_path untuk semua baris mahasiswa dalam batch ini
            SkYudisium::whereIn('id', $mahasiswas->pluck('id'))->update(['local_path' => $path]);

            return $path;
        } catch (\Throwable $e) {
            Log::error("SkYudisium generatePdfFile error: " . $e->getMessage() . " on line " . $e->getLine());
            return null;
        }
    }

    public function import(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file'           => 'required|file|mimes:xlsx,xls,csv|max:10240',
                'prodi_id'       => 'nullable',
                'tanggal'        => 'nullable|date',
                'nomor_surat'    => 'nullable|string',
                'no_surat'       => 'nullable|string',
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

            $prodiId = $request->prodi_id;
            $tanggal = $request->tanggal ?: date('Y-m-d');
            $petandaTangan = $request->petanda_tangan ?: 'tidak';
            $nomorSurat = $request->nomor_surat ?: $request->no_surat;
            $userId = Auth::id();

            $import = new \App\Imports\SkYudisiumImport($prodiId, $tanggal, $petandaTangan, $nomorSurat, $userId);
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            return response()->json([
                'status' => true,
                'message' => "Import SK Yudisium selesai: {$import->getSuccessCount()} berhasil ditambahkan, {$import->getFailedCount()} gagal/dilewati.",
                'data' => [
                    'success' => $import->getSuccessCount(),
                    'failed'  => $import->getFailedCount(),
                    'skipped' => $import->getSkippedRows(),
                    'errors'  => $import->getErrors(),
                ]
            ]);
        } catch (\Throwable $th) {
            Log::error('Gagal melakukan import SK Yudisium: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Import gagal: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan nomor surat SK Yudisium berdasarkan NIM mahasiswa.
     *
     * @param Request $request
     * @param string|null $nim
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNomorSuratByNim(Request $request, $nim = null)
    {
        try {
            $targetNim = $nim ?: $request->input('nim');

            if (empty($targetNim)) {
                return response()->json([
                    'status' => false,
                    'message' => 'NIM wajib diisi.',
                    'nim' => null,
                    'nomor_surat' => null,
                    'data' => null,
                ], 422);
            }

            $rawNim = trim($targetNim);
            $cleanNim = preg_replace('/[^a-zA-Z0-9]/', '', $rawNim);

            $data = SkYudisium::where(function ($q) use ($rawNim, $cleanNim) {
                    $q->where('nim', $rawNim);
                    if (!empty($cleanNim)) {
                        $q->orWhere('nim', $cleanNim)
                          ->orWhere('nim', 'like', "%{$rawNim}%")
                          ->orWhere('nim', 'like', "%{$cleanNim}%")
                          ->orWhereRaw("REPLACE(REPLACE(REPLACE(nim, '.', ''), '-', ''), ' ', '') = ?", [$cleanNim]);
                    }
                })
                ->orderByRaw("CASE WHEN nomor_surat IS NOT NULL AND TRIM(nomor_surat) != '' THEN 0 ELSE 1 END")
                ->orderBy('id', 'desc')
                ->first();

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data SK Yudisium untuk NIM tersebut tidak ditemukan.',
                    'nim' => $rawNim,
                    'nomor_surat' => null,
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Nomor surat SK Yudisium berhasil ditemukan.',
                'nim' => $data->nim,
                'nomor_surat' => $data->nomor_surat,
                'data' => [
                    'nim' => $data->nim,
                    'nomor_surat' => $data->nomor_surat,
                    'nama_mhs' => $data->nama_mhs,
                    'prodi' => $data->prodi,
                    'tanggal' => $data->tanggal,
                ]
            ]);
        } catch (\Throwable $th) {
            Log::error('Error getNomorSuratByNim SkYudisium: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan sistem saat mengambil data: ' . $th->getMessage(),
                'nim' => $targetNim ?? null,
                'nomor_surat' => null,
                'data' => null,
            ], 500);
        }
    }

    /**
     * Menampilkan semua data SK Yudisium (per-mahasiswa) dengan pagination & filter.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {
        try {
            $query = SkYudisium::leftJoin('prodi', 'prodi.id', '=', 'sk_yudisium.prodi_id')
                ->leftJoin('tanda_tangan', 'tanda_tangan.id', '=', 'sk_yudisium.tanda_tangan_id')
                ->select(
                    'sk_yudisium.*',
                    'prodi.nama as nama_prodi',
                    'prodi.alias as alias_prodi',
                    'tanda_tangan.nama as nama_ttd'
                );

            // Filter prodi_id
            if ($request->filled('prodi_id')) {
                $prodiFilter = Prodi::find($request->prodi_id);
                $query->where(function ($q) use ($request, $prodiFilter) {
                    $q->where('sk_yudisium.prodi_id', $request->prodi_id);
                    if ($prodiFilter) {
                        $q->orWhere('sk_yudisium.prodi', $prodiFilter->nama)
                            ->orWhere('sk_yudisium.prodi', $prodiFilter->alias);
                    }
                });
            }

            // Scope user prodi jika login
            $login = Auth::user()?->prodi;
            if ($login) {
                $query->where(function ($q) use ($login) {
                    $q->where('sk_yudisium.prodi_id', $login->id)
                        ->orWhere('sk_yudisium.prodi', $login->nama)
                        ->orWhere('sk_yudisium.prodi', $login->alias);
                });
            }

            // Scope jenis kelamin jika login
            $auth = Auth::user()?->jenis_kelamin;
            if ($auth == 'L') {
                $query->where('sk_yudisium.jenis_kelamin', 'L');
            } elseif ($auth == 'P') {
                $query->where('sk_yudisium.jenis_kelamin', 'P');
            }

            // Filter status
            if ($request->filled('status')) {
                $query->where('sk_yudisium.status', $request->status);
            }

            // Filter nomor surat spesifik
            if ($request->filled('nomor_surat')) {
                $query->where('sk_yudisium.nomor_surat', 'like', "%{$request->nomor_surat}%");
            }

            // Filter tanggal / periode
            if ($request->filled('tanggal')) {
                $query->whereDate('sk_yudisium.tanggal', $request->tanggal);
            }

            // Filter NIM
            if ($request->filled('nim')) {
                $rawNim = trim($request->nim);
                $cleanNim = preg_replace('/[^a-zA-Z0-9]/', '', $rawNim);
                $query->where(function ($q) use ($rawNim, $cleanNim) {
                    $q->where('sk_yudisium.nim', $rawNim);
                    if (!empty($cleanNim)) {
                        $q->orWhere('sk_yudisium.nim', $cleanNim)
                            ->orWhere('sk_yudisium.nim', 'like', "%{$rawNim}%")
                            ->orWhere('sk_yudisium.nim', 'like', "%{$cleanNim}%")
                            ->orWhereRaw("REPLACE(REPLACE(REPLACE(sk_yudisium.nim, '.', ''), '-', ''), ' ', '') = ?", [$cleanNim]);
                    }
                });
            }

            // Search query umum
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->orWhere('sk_yudisium.nim', 'like', "%{$search}%")
                        ->orWhere('sk_yudisium.nama_mhs', 'like', "%{$search}%")
                        ->orWhere('sk_yudisium.prodi', 'like', "%{$search}%")
                        ->orWhere('sk_yudisium.nomor_surat', 'like', "%{$search}%")
                        ->orWhere('sk_yudisium.nilai_skripsi', 'like', "%{$search}%")
                        ->orWhere('prodi.nama', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->input('sortBy', 'id');
            $sortType = strtolower($request->input('sortType', 'desc')) === 'asc' ? 'asc' : 'desc';

            $validColumns = [
                'id'            => 'sk_yudisium.id',
                'nim'           => 'sk_yudisium.nim',
                'nama_mhs'      => 'sk_yudisium.nama_mhs',
                'nama'          => 'sk_yudisium.nama_mhs',
                'nomor_surat'   => 'sk_yudisium.nomor_surat',
                'tanggal'       => 'sk_yudisium.tanggal',
                'prodi'         => 'sk_yudisium.prodi',
                'nilai_skripsi' => 'sk_yudisium.nilai_skripsi',
                'created_at'    => 'sk_yudisium.created_at',
            ];

            $orderColumn = $validColumns[$sortBy] ?? 'sk_yudisium.id';
            $query->orderBy($orderColumn, $sortType);

            // Pagination (support parameter 'limit', 'per_page', atau 'rowsPerPage')
            $limit = $request->input('limit', $request->input('per_page', $request->input('rowsPerPage', 10)));
            $data = $query->paginate((int) $limit);

            return response()->json([
                'status' => true,
                'data' => $data,
                'message' => 'Data SK Yudisium berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error all SkYudisium: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data SK Yudisium: ' . $th->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Alias untuk method all.
     */
    public function allData(Request $request)
    {
        return $this->all($request);
    }

    /**
     * Alias untuk method all.
     */
    public function getAll(Request $request)
    {
        return $this->all($request);
    }
}
