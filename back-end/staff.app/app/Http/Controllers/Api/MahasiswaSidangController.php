<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MahasiswaSidang;
use App\Models\PengajuanJudul;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MahasiswaSidangController extends Controller
{
    public function index(Request $request)
    {
        Log::info('MahasiswaSidang index:', $request->all());

        $query = MahasiswaSidang::query()
            ->leftJoin('jadwal_sidang', 'jadwal_sidang.id', '=', 'mahasiswa_sidang.jadwal_sidang_id')
            ->leftJoin('pengajuan_judul', 'pengajuan_judul.id', '=', 'mahasiswa_sidang.pengajuan_judul_id')
            ->leftJoin('prodi', 'prodi.id', '=', 'pengajuan_judul.prodi_id')
            ->select(
                'mahasiswa_sidang.*',
                'jadwal_sidang.tgl_sidang',
                'pengajuan_judul.nim',
                'pengajuan_judul.nama_mhs',
                'pengajuan_judul.judul_proposal_skripsi',
                'pengajuan_judul.dosen_penasehat',
                'pengajuan_judul.jenis_kelamin',
                DB::raw('COALESCE(prodi.nama, pengajuan_judul.prodi) as nama_prodi')
            );

        if ($request->filled('jadwal_sidang_id')) {
            $query->where('mahasiswa_sidang.jadwal_sidang_id', $request->jadwal_sidang_id);
        }

        $user = Auth::user();
        $loginProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);

        if ($loginProdi) {
            $query->where(function ($q) use ($loginProdi) {
                $q->where('pengajuan_judul.prodi_id', $loginProdi->id)
                  ->orWhere('pengajuan_judul.prodi', $loginProdi->nama)
                  ->orWhere('pengajuan_judul.prodi', $loginProdi->alias);
            });
        } elseif ($request->filled('prodi_id')) {
            $prodiFilter = Prodi::find($request->prodi_id);
            $query->where(function ($q) use ($request, $prodiFilter) {
                $q->where('pengajuan_judul.prodi_id', $request->prodi_id);
                if ($prodiFilter) {
                    $q->orWhere('pengajuan_judul.prodi', $prodiFilter->nama)
                      ->orWhere('pengajuan_judul.prodi', $prodiFilter->alias);
                }
            });
        }

        $isAdmin = strtolower($user?->level?->nama ?? '') === 'admin';
        if ($user && !$isAdmin) {
            $authGender = $user->jenis_kelamin;
            if ($authGender === 'L') {
                $query->where(function ($q) {
                    $q->where('pengajuan_judul.jenis_kelamin', 'L')
                      ->orWhereNull('pengajuan_judul.jenis_kelamin');
                });
            } elseif ($authGender === 'P') {
                $query->where(function ($q) {
                    $q->where('pengajuan_judul.jenis_kelamin', 'P')
                      ->orWhereNull('pengajuan_judul.jenis_kelamin');
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->orWhere('pengajuan_judul.nim', 'LIKE', "%{$search}%")
                  ->orWhere('pengajuan_judul.nama_mhs', 'LIKE', "%{$search}%")
                  ->orWhere('pengajuan_judul.judul_proposal_skripsi', 'LIKE', "%{$search}%")
                  ->orWhere('pengajuan_judul.prodi', 'LIKE', "%{$search}%")
                  ->orWhere('prodi.nama', 'LIKE', "%{$search}%")
                  ->orWhere('jadwal_sidang.tgl_sidang', 'LIKE', "%{$search}%");
            });
        }

        $sortMap = [
            'id'                     => 'mahasiswa_sidang.id',
            'tgl_sidang'             => 'jadwal_sidang.tgl_sidang',
            'nim'                    => 'pengajuan_judul.nim',
            'nama_mhs'               => 'pengajuan_judul.nama_mhs',
            'judul_proposal_skripsi' => 'pengajuan_judul.judul_proposal_skripsi',
        ];
        $sortBy = $sortMap[$request->input('sortBy')] ?? 'mahasiswa_sidang.id';
        $sortType = strtolower($request->input('sortType', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortType);

        $data = $query->paginate($request->input('limit', 10));

        return response()->json([
            'status'  => true,
            'data'    => $data,
            'message' => 'Data mahasiswa sidang berhasil diambil',
        ]);
    }

    public function getPengajuanJudul(Request $request)
    {
        $query = PengajuanJudul::leftJoin('prodi', 'prodi.id', '=', 'pengajuan_judul.prodi_id')
            ->select(
                'pengajuan_judul.id',
                'pengajuan_judul.nim',
                'pengajuan_judul.nama_mhs',
                'pengajuan_judul.judul_proposal_skripsi',
                'pengajuan_judul.dosen_penasehat',
                'pengajuan_judul.jenis_kelamin',
                DB::raw('COALESCE(prodi.nama, pengajuan_judul.prodi) as nama_prodi')
            );

        $user = Auth::user();
        $loginProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);
        if ($loginProdi) {
            $query->where(function ($q) use ($loginProdi) {
                $q->where('pengajuan_judul.prodi_id', $loginProdi->id)
                  ->orWhere('pengajuan_judul.prodi', $loginProdi->nama)
                  ->orWhere('pengajuan_judul.prodi', $loginProdi->alias);
            });
        }

        $isAdmin = strtolower($user?->level?->nama ?? '') === 'admin';
        if ($user && !$isAdmin) {
            $authGender = $user->jenis_kelamin;
            if ($authGender === 'L') {
                $query->where(function ($q) {
                    $q->where('pengajuan_judul.jenis_kelamin', 'L')
                      ->orWhereNull('pengajuan_judul.jenis_kelamin');
                });
            } elseif ($authGender === 'P') {
                $query->where(function ($q) {
                    $q->where('pengajuan_judul.jenis_kelamin', 'P')
                      ->orWhereNull('pengajuan_judul.jenis_kelamin');
                });
            }
        }

        if ($request->filled('exclude_jadwal_id')) {
            $existingIds = MahasiswaSidang::where('jadwal_sidang_id', $request->exclude_jadwal_id)
                ->pluck('pengajuan_judul_id');
            if ($existingIds->isNotEmpty()) {
                $query->whereNotIn('pengajuan_judul.id', $existingIds);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->orWhere('pengajuan_judul.nim', 'LIKE', "%{$search}%")
                  ->orWhere('pengajuan_judul.nama_mhs', 'LIKE', "%{$search}%")
                  ->orWhere('pengajuan_judul.judul_proposal_skripsi', 'LIKE', "%{$search}%");
            });
        }

        $result = $query->orderBy('pengajuan_judul.id', 'desc')->limit(30)->get();

        return response()->json([
            'status'  => true,
            'data'    => $result,
            'message' => 'Data pengajuan judul berhasil diambil',
        ]);
    }

    public function store(Request $request)
    {
        Log::info('MahasiswaSidang store:', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'jadwal_sidang_id'      => 'required|exists:jadwal_sidang,id',
                'pengajuan_judul_id'    => 'required_without:pengajuan_judul_ids|nullable|exists:pengajuan_judul,id',
                'pengajuan_judul_ids'   => 'nullable|array|min:1',
                'pengajuan_judul_ids.*' => 'exists:pengajuan_judul,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            $jadwalId = $validated['jadwal_sidang_id'];

            if (!empty($validated['pengajuan_judul_ids'])) {
                $created = [];
                foreach (array_unique($validated['pengajuan_judul_ids']) as $pjId) {
                    $created[] = MahasiswaSidang::firstOrCreate([
                        'jadwal_sidang_id'   => $jadwalId,
                        'pengajuan_judul_id' => $pjId,
                    ]);
                }

                return response()->json([
                    'status'  => true,
                    'data'    => $created,
                    'message' => 'Mahasiswa sidang berhasil ditambahkan',
                ], 201);
            }

            $pjId = $validated['pengajuan_judul_id'];
            $exists = MahasiswaSidang::where('jadwal_sidang_id', $jadwalId)
                ->where('pengajuan_judul_id', $pjId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Mahasiswa tersebut sudah terdaftar pada jadwal sidang ini',
                    'errors'  => [
                        'pengajuan_judul_id' => ['Mahasiswa sudah terdaftar pada jadwal sidang ini'],
                    ],
                ], 422);
            }

            $item = MahasiswaSidang::create([
                'jadwal_sidang_id'   => $jadwalId,
                'pengajuan_judul_id' => $pjId,
            ]);

            return response()->json([
                'status'  => true,
                'data'    => $item,
                'message' => 'Mahasiswa sidang berhasil ditambahkan',
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Mahasiswa sidang gagal ditambahkan: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $item = MahasiswaSidang::leftJoin('jadwal_sidang', 'jadwal_sidang.id', '=', 'mahasiswa_sidang.jadwal_sidang_id')
            ->leftJoin('pengajuan_judul', 'pengajuan_judul.id', '=', 'mahasiswa_sidang.pengajuan_judul_id')
            ->leftJoin('prodi', 'prodi.id', '=', 'pengajuan_judul.prodi_id')
            ->select(
                'mahasiswa_sidang.*',
                'jadwal_sidang.tgl_sidang',
                'pengajuan_judul.nim',
                'pengajuan_judul.nama_mhs',
                'pengajuan_judul.judul_proposal_skripsi',
                'pengajuan_judul.dosen_penasehat',
                DB::raw('COALESCE(prodi.nama, pengajuan_judul.prodi) as nama_prodi')
            )
            ->where('mahasiswa_sidang.id', $id)
            ->first();

        if (!$item) {
            return response()->json([
                'status'  => false,
                'message' => 'Data mahasiswa sidang tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'data'    => $item,
            'message' => 'Data mahasiswa sidang berhasil diambil',
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info('MahasiswaSidang update:', $request->all());

        try {
            $item = MahasiswaSidang::find($id);
            if (!$item) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data mahasiswa sidang tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'jadwal_sidang_id'   => 'required|exists:jadwal_sidang,id',
                'pengajuan_judul_id' => 'required|exists:pengajuan_judul,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            $duplicate = MahasiswaSidang::where('jadwal_sidang_id', $validated['jadwal_sidang_id'])
                ->where('pengajuan_judul_id', $validated['pengajuan_judul_id'])
                ->where('id', '!=', $id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Mahasiswa tersebut sudah terdaftar pada jadwal sidang ini',
                    'errors'  => [
                        'pengajuan_judul_id' => ['Mahasiswa sudah terdaftar pada jadwal sidang ini'],
                    ],
                ], 422);
            }

            $item->update([
                'jadwal_sidang_id'   => $validated['jadwal_sidang_id'],
                'pengajuan_judul_id' => $validated['pengajuan_judul_id'],
            ]);

            return response()->json([
                'status'  => true,
                'data'    => $item,
                'message' => 'Mahasiswa sidang berhasil diupdate',
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Mahasiswa sidang gagal diupdate: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $item = MahasiswaSidang::find($id);
            if (!$item) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data mahasiswa sidang tidak ditemukan',
                ], 404);
            }

            $item->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Mahasiswa sidang berhasil dihapus',
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Mahasiswa sidang gagal dihapus',
            ], 500);
        }
    }
}
