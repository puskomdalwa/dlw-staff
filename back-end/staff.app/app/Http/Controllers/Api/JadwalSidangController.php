<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalSidang;
use App\Models\MahasiswaSidang;
use App\Models\PengajuanJudul;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class JadwalSidangController extends Controller
{
    private function applyProdiScope($query, ?Prodi $loginProdi, $prodiId = null): void
    {
        $targetProdi = $loginProdi ?: ($prodiId ? Prodi::find($prodiId) : null);
        if (!$targetProdi && empty($prodiId)) {
            return;
        }

        $query->where(function ($q) use ($targetProdi, $prodiId) {
            if ($targetProdi) {
                $q->where('pengajuan_judul.prodi_id', $targetProdi->id)
                  ->orWhere('pengajuan_judul.prodi', $targetProdi->nama)
                  ->orWhere('pengajuan_judul.prodi', $targetProdi->alias)
                  ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($targetProdi->nama))])
                  ->orWhereRaw('LOWER(TRIM(pengajuan_judul.prodi)) = ?', [strtolower(trim($targetProdi->alias))]);
            } elseif (!empty($prodiId)) {
                $q->where('pengajuan_judul.prodi_id', $prodiId);
            }
        });
    }

    private function applyGenderScope($query, $user): void
    {
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
    }

    public function getMahasiswa(Request $request)
    {
        $user = Auth::user();
        $loginProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);

        $query = PengajuanJudul::leftJoin('prodi', 'prodi.id', '=', 'pengajuan_judul.prodi_id')
            ->select(
                'pengajuan_judul.id',
                'pengajuan_judul.nim',
                'pengajuan_judul.nama_mhs',
                'pengajuan_judul.prodi_id',
                'pengajuan_judul.judul_proposal_skripsi',
                'pengajuan_judul.dosen_penasehat',
                'pengajuan_judul.jenis_kelamin',
                DB::raw('COALESCE(prodi.nama, pengajuan_judul.prodi) as nama_prodi')
            );

        $this->applyProdiScope($query, $loginProdi, $request->input('prodi_id'));
        $this->applyGenderScope($query, $user);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->orWhere('pengajuan_judul.nim', 'LIKE', "%{$search}%")
                  ->orWhere('pengajuan_judul.nama_mhs', 'LIKE', "%{$search}%")
                  ->orWhere('pengajuan_judul.judul_proposal_skripsi', 'LIKE', "%{$search}%")
                  ->orWhere('pengajuan_judul.dosen_penasehat', 'LIKE', "%{$search}%")
                  ->orWhere('pengajuan_judul.prodi', 'LIKE', "%{$search}%")
                  ->orWhere('prodi.nama', 'LIKE', "%{$search}%");
            });
        }

        $result = $query->orderBy('pengajuan_judul.id', 'desc')->get();

        return response()->json([
            'status'  => true,
            'data'    => $result,
            'message' => 'Data mahasiswa pengajuan judul berhasil diambil',
        ]);
    }

    public function index(Request $request)
    {
        Log::info('JadwalSidang index:', $request->all());

        $user = Auth::user();
        $loginProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);

        $query = JadwalSidang::query()->withCount([
            'mahasiswaSidang' => function ($q) use ($loginProdi, $user) {
                if ($loginProdi || (strtolower($user?->level?->nama ?? '') !== 'admin' && in_array($user?->jenis_kelamin, ['L', 'P'], true))) {
                    $q->whereHas('pengajuanJudul', function ($pj) use ($loginProdi, $user) {
                        $this->applyProdiScope($pj, $loginProdi);
                        $this->applyGenderScope($pj, $user);
                    });
                }
            }
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tgl_sidang', 'LIKE', "%{$search}%")
                  ->orWhereHas('mahasiswaSidang.pengajuanJudul', function ($sub) use ($search) {
                      $sub->where('nama_mhs', 'LIKE', "%{$search}%")
                          ->orWhere('nim', 'LIKE', "%{$search}%")
                          ->orWhere('judul_proposal_skripsi', 'LIKE', "%{$search}%");
                  });
            });
        }

        $allowedSort = ['id', 'tgl_sidang', 'mahasiswa_sidang_count', 'created_at'];
        $sortBy = in_array($request->input('sortBy'), $allowedSort, true)
            ? $request->input('sortBy')
            : 'tgl_sidang';
        $sortType = strtolower($request->input('sortType', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortType);

        if ($request->has('all') && filter_var($request->input('all'), FILTER_VALIDATE_BOOLEAN)) {
            $result = $query->get();
        } else {
            $result = $query->paginate($request->input('limit', 10));
        }

        return response()->json([
            'status'  => true,
            'data'    => $result,
            'message' => 'Data jadwal sidang berhasil diambil',
        ]);
    }

    public function store(Request $request)
    {
        Log::info('JadwalSidang store:', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'tgl_sidang'            => 'required|date',
                'pengajuan_judul_ids'   => 'nullable|array',
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

            $jadwal = DB::transaction(function () use ($validated) {
                $jadwal = JadwalSidang::create([
                    'tgl_sidang' => $validated['tgl_sidang'],
                ]);

                if (!empty($validated['pengajuan_judul_ids'])) {
                    $ids = array_unique($validated['pengajuan_judul_ids']);
                    foreach ($ids as $pjId) {
                        MahasiswaSidang::firstOrCreate([
                            'jadwal_sidang_id'   => $jadwal->id,
                            'pengajuan_judul_id' => $pjId,
                        ]);
                    }
                }

                return $jadwal->load('mahasiswaSidang.pengajuanJudul');
            });

            return response()->json([
                'status'  => true,
                'data'    => $jadwal,
                'message' => 'Jadwal sidang berhasil ditambahkan',
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Jadwal sidang gagal ditambahkan: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $loginProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);

        $jadwal = JadwalSidang::with([
            'mahasiswaSidang' => function ($q) use ($loginProdi, $user) {
                if ($loginProdi || (strtolower($user?->level?->nama ?? '') !== 'admin' && in_array($user?->jenis_kelamin, ['L', 'P'], true))) {
                    $q->whereHas('pengajuanJudul', function ($pj) use ($loginProdi, $user) {
                        $this->applyProdiScope($pj, $loginProdi);
                        $this->applyGenderScope($pj, $user);
                    });
                }
                $q->with('pengajuanJudul.prodiRel');
            },
        ])->find($id);

        if (!$jadwal) {
            return response()->json([
                'status'  => false,
                'message' => 'Jadwal sidang tidak ditemukan',
            ], 404);
        }

        $jadwal->setAttribute('mahasiswa_sidang_count', $jadwal->mahasiswaSidang->count());

        return response()->json([
            'status'  => true,
            'data'    => $jadwal,
            'message' => 'Detail jadwal sidang berhasil diambil',
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info('JadwalSidang update:', $request->all());

        try {
            $jadwal = JadwalSidang::find($id);
            if (!$jadwal) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Jadwal sidang tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'tgl_sidang'            => 'required|date',
                'pengajuan_judul_ids'   => 'nullable|array',
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
            $user = Auth::user();
            $loginProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);

            DB::transaction(function () use ($jadwal, $validated, $request, $loginProdi, $user) {
                $jadwal->update([
                    'tgl_sidang' => $validated['tgl_sidang'],
                ]);

                if ($request->has('pengajuan_judul_ids')) {
                    $ids = array_values(array_unique(array_map('intval', $validated['pengajuan_judul_ids'] ?? [])));

                    // Hanya sinkronkan mahasiswa dalam cakupan prodi/gender user agar tidak menimpa peserta prodi lain
                    $deleteQuery = MahasiswaSidang::where('jadwal_sidang_id', $jadwal->id);
                    if (!empty($ids)) {
                        $deleteQuery->whereNotIn('pengajuan_judul_id', $ids);
                    }
                    if ($loginProdi || (strtolower($user?->level?->nama ?? '') !== 'admin' && in_array($user?->jenis_kelamin, ['L', 'P'], true))) {
                        $deleteQuery->whereHas('pengajuanJudul', function ($pj) use ($loginProdi, $user) {
                            $this->applyProdiScope($pj, $loginProdi);
                            $this->applyGenderScope($pj, $user);
                        });
                    }
                    $deleteQuery->delete();

                    foreach ($ids as $pjId) {
                        MahasiswaSidang::firstOrCreate([
                            'jadwal_sidang_id'   => $jadwal->id,
                            'pengajuan_judul_id' => $pjId,
                        ]);
                    }
                }
            });

            return $this->show($jadwal->id);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Jadwal sidang gagal diupdate: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $jadwal = JadwalSidang::find($id);
            if (!$jadwal) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Jadwal sidang tidak ditemukan',
                ], 404);
            }

            $jadwal->mahasiswaSidang()->delete();
            $jadwal->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Jadwal sidang berhasil dihapus',
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'status'  => false,
                'message' => 'Jadwal sidang gagal dihapus',
            ], 500);
        }
    }
}
