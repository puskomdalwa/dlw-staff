<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MahasiswaImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MahasiswaController extends Controller
{
    public function index(Request $request)
    {
        $data = Mahasiswa::with('prodi');

        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%");
                $q->orWhere('nim', 'LIKE', "%{$search}%");
                $q->orWhere('nik', 'LIKE', "%{$search}%");
            });
        }

        $data->orderBy(
            $request->input('sortBy', 'id'),
            $request->input('sortType', 'asc')
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
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'nim' => 'required|string|max:255',
                'nik' => 'required|string|max:255',
                'tgl_lahir' => 'required|string|max:255',
                'nilai_akreditasi' => 'required|string|max:255',
                'nomor_sk_ban_pt' => 'required|string|max:255',
                'nomor_ijazah_nasional' => 'required|string|max:255',
                'tanggal_sk_yudisium' => 'required|string|max:255',
                'tanggal_penerbitan' => 'required|string|max:255',
                'prodi_id' => 'required|exists:prodi,id',
                'status' => 'required|in:belum,sudah',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            Mahasiswa::create($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil ditambahkan'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Data gagal ditambahkan: ' . $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $mahasiswa = Mahasiswa::find($id);

        if (!$mahasiswa) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json(['status' => true, 'data' => $mahasiswa, 'message' => 'Data berhasil diambil']);
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'nim' => 'required|string|max:255',
                'nik' => 'required|string|max:255',
                'tgl_lahir' => 'required|string|max:255',
                'nilai_akreditasi' => 'required|string|max:255',
                'nomor_sk_ban_pt' => 'required|string|max:255',
                'nomor_ijazah_nasional' => 'required|string|max:255',
                'tanggal_sk_yudisium' => 'required|string|max:255',
                'tanggal_penerbitan' => 'required|string|max:255',
                'prodi_id' => 'required|exists:prodi,id',
                'status' => 'required|in:belum,sudah',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
            }

            $mahasiswa = Mahasiswa::find($id);
            if (!$mahasiswa) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $mahasiswa->update($validator->validated());

            return response()->json(['status' => true, 'message' => 'Data berhasil diupdate']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => 'Data gagal diupdate'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $mahasiswa = Mahasiswa::find($id);
            if (!$mahasiswa) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }
            $mahasiswa->delete();
            return response()->json(['status' => true, 'message' => 'Data berhasil dihapus']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => 'Data gagal dihapus'], 500);
        }
    }

    public function import(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
            }

            $import = new MahasiswaImport();
            Excel::import($import, $request->file('file'));

            if ($import->getFailedCount() > 0) {
                Log::warning('Beberapa baris gagal diimpor pada Mahasiswa Import', [
                    'failed_count' => $import->getFailedCount(),
                    'errors' => $import->getErrors()
                ]);
            }

            $skippedSheets = $import->getSkippedSheets();
            $skippedMsg = count($skippedSheets) > 0 ? ", " . count($skippedSheets) . " sheet dilewati (" . implode(', ', $skippedSheets) . ")" : "";

            $skippedNames = $import->getSkippedNames();
            $skippedNamesMsg = count($skippedNames) > 0 ? ". Terdapat " . count($skippedNames) . " data yang dilewati karena ada kolom yang kosong: " . implode(' | ', $skippedNames) : "";

            return response()->json([
                'status' => true,
                'message' => "Import selesai. {$import->getSuccessCount()} berhasil, {$import->getFailedCount()} gagal{$skippedMsg}{$skippedNamesMsg}",
                'data' => [
                    'success' => $import->getSuccessCount(),
                    'failed' => $import->getFailedCount(),
                    'skipped' => count($skippedNames),
                    'skipped_sheets' => $skippedSheets,
                    'skipped_names' => $skippedNames,
                    'errors' => $import->getErrors(),
                ],
            ]);
        } catch (\Throwable $th) {
            Log::error('Gagal melakukan import Mahasiswa', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Import gagal: ' . $th->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $search = $request->query('search', '');

            // $user = $request->user();
            // $where = null;

            // if ($user && $user->prodi_id) {
            //     $where = ['prodi_id' => $user->prodi_id];
            // }

            // $data = \App\Services\Mahasiswa::all(
            //     null,   // offset
            //     20,     // limit
            //     $search,
            //     null,   // order
            //     null,   // dir
            //     $where, // where
            //     null    // pluck
            // );
            $user = $request->user();
            $where = null;
            $prodi = null;

            if ($user && $user->prodi_id) {
                $prodi = $user->prodi->alias;
                // $prodi2 = strval($prodi);
                if ($prodi == 'HKI') {
                    $prodi = 'AS-HK';
                }

                $where = [
                    ['mst_prodi.alias', '=', $prodi]
                ];
            }

            $data = \App\Services\Mahasiswa::all(
                null,   // offset
                20,     // limit
                $search,
                null,   // order
                null,   // dir
                $where, // where
                null    // pluck
            );

            // Perkaya data mahasiswa dengan data judul dari tabel pengajuan_judul
            $listMhs = is_array($data) ? $data : (is_iterable($data) ? iterator_to_array($data) : []);
            $nims = [];
            $cleanNims = [];
            foreach ($listMhs as $m) {
                if (!empty($m->nim)) {
                    $nims[] = $m->nim;
                    $cleanNims[] = preg_replace('/[^a-zA-Z0-9]/', '', $m->nim);
                }
            }

            $cleanNims = array_unique(array_filter($cleanNims));

            $juduls = \App\Models\PengajuanJudul::where(function ($q) use ($nims, $cleanNims) {
                if (!empty($nims)) {
                    $q->whereIn('nim', $nims);
                }
                if (!empty($cleanNims)) {
                    $q->orWhereIn('nim', $cleanNims)
                      ->orWhereIn(DB::raw("REPLACE(REPLACE(REPLACE(nim, '.', ''), '-', ''), ' ', '')"), $cleanNims);
                }
            })
            ->orderBy('id', 'desc')
            ->get();

            $judulsMap = [];
            foreach ($juduls as $pj) {
                $rawPjNim = $pj->nim;
                $cleanPjNim = preg_replace('/[^a-zA-Z0-9]/', '', $pj->nim);
                $lowerName = strtolower(trim($pj->nama_mhs ?? ''));

                if (!empty($rawPjNim) && !isset($judulsMap[$rawPjNim])) $judulsMap[$rawPjNim] = $pj;
                if (!empty($cleanPjNim) && !isset($judulsMap[$cleanPjNim])) $judulsMap[$cleanPjNim] = $pj;
                if (!empty($lowerName) && !isset($judulsMap['name_' . $lowerName])) $judulsMap['name_' . $lowerName] = $pj;
            }

            foreach ($listMhs as &$m) {
                $mNim = $m->nim ?? '';
                $cleanMNim = preg_replace('/[^a-zA-Z0-9]/', '', $mNim);
                $mName = strtolower(trim($m->nama ?? $m->nama_mhs ?? ''));

                $matchedPj = $judulsMap[$mNim] 
                    ?? $judulsMap[$cleanMNim] 
                    ?? ($mName ? ($judulsMap['name_' . $mName] ?? null) : null)
                    ?? null;

                if ($matchedPj) {
                    $m->judul = $matchedPj->judul_proposal_skripsi;
                    $m->judul_skripsi = $matchedPj->judul_proposal_skripsi;
                    $m->judul_proposal_skripsi = $matchedPj->judul_proposal_skripsi;
                } else {
                    $m->judul = null;
                    $m->judul_skripsi = null;
                    $m->judul_proposal_skripsi = null;
                }
            }
            unset($m);

            // Jika user mencari berdasarkan judul atau nama/NIM di pengajuan_judul
            if (!empty($search)) {
                $cleanSearch = preg_replace('/[^a-zA-Z0-9]/', '', $search);
                $pjMatches = \App\Models\PengajuanJudul::where('judul_proposal_skripsi', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('nama_mhs', 'like', "%{$search}%")
                    ->when(!empty($cleanSearch), function ($q) use ($cleanSearch) {
                        $q->orWhere('nim', 'like', "%{$cleanSearch}%")
                          ->orWhereRaw("REPLACE(REPLACE(REPLACE(nim, '.', ''), '-', ''), ' ', '') like ?", ["%{$cleanSearch}%"]);
                    })
                    ->limit(10)
                    ->get();

                $existingNims = array_filter(array_column($listMhs, 'nim'));
                $existingCleanNims = array_map(function ($n) {
                    return preg_replace('/[^a-zA-Z0-9]/', '', $n);
                }, $existingNims);

                foreach ($pjMatches as $pj) {
                    $pjClean = preg_replace('/[^a-zA-Z0-9]/', '', $pj->nim);
                    if (!in_array($pj->nim, $existingNims) && !in_array($pjClean, $existingCleanNims)) {
                        $listMhs[] = (object)[
                            'id' => $pj->nim,
                            'nim' => $pj->nim,
                            'nama' => $pj->nama_mhs,
                            'nama_mhs' => $pj->nama_mhs,
                            'prodi_mhs' => $pj->prodi,
                            'alias_prodi' => $pj->prodi,
                            'jenis_kelamin' => $pj->jenis_kelamin,
                            'judul' => $pj->judul_proposal_skripsi,
                            'judul_skripsi' => $pj->judul_proposal_skripsi,
                            'judul_proposal_skripsi' => $pj->judul_proposal_skripsi,
                        ];
                        $existingNims[] = $pj->nim;
                        $existingCleanNims[] = $pjClean;
                    }
                }
            }

            return response()->json([
                'status' => true,
                'data' => $listMhs,
                'message' => 'Data berhasil diambil',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data mahasiswa: ' . $th->getMessage()
            ], 500);
        }
    }
}
