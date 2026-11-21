<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengajuanJudul;
use App\Models\Prodi;
use App\Models\PublicFormSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PublicFormPengajuanJudulController extends Controller
{
    /**
     * Helper: Mendapatkan atau membuat setting form pengajuan judul.
     */
    protected function getSetting($slug = null): ?PublicFormSetting
    {
        if ($slug) {
            return PublicFormSetting::where('form_code', 'pengajuan_judul')
                ->where('slug', $slug)
                ->first() ?: PublicFormSetting::where('slug', $slug)->first();
        }

        return PublicFormSetting::firstOrCreate(
            ['form_code' => 'pengajuan_judul'],
            [
                'slug'              => 'pengajuan-judul',
                'title'             => 'Formulir Pengajuan Judul Skripsi Program Strata 1',
                'description'       => 'Silakan lengkapi formulir pengajuan judul skripsi berikut dengan data yang valid.',
                'is_active'         => true,
                'expired_at'        => Carbon::now()->addDays(7),
                'duration_type'     => 'days',
                'duration_value'    => 7,
                'total_submissions' => 0,
            ]
        );
    }

    /**
     * [ADMIN] Mendapatkan setting form publik saat ini.
     */
    public function getAdminSetting()
    {
        try {
            $setting = $this->getSetting();

            return response()->json([
                'status' => true,
                'data' => [
                    'id'                => $setting->id,
                    'form_code'         => $setting->form_code,
                    'slug'              => $setting->slug,
                    'title'             => $setting->title,
                    'description'       => $setting->description,
                    'is_active'         => (bool) $setting->is_active,
                    'expired_at'        => $setting->expired_at ? $setting->expired_at->format('Y-m-d H:i:s') : null,
                    'duration_type'     => $setting->duration_type ?? 'days',
                    'duration_value'    => $setting->duration_value ?? 7,
                    'total_submissions' => $setting->total_submissions,
                    'is_available'      => $setting->isAvailable(),
                    'remaining_text'    => $setting->getRemainingTimeText(),
                ],
                'message' => 'Setting form publik pengajuan judul berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error getAdminSetting PengajuanJudul: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil setting: ' . $th->getMessage()
            ], 500);
        }
    }
    /**
     * [ADMIN] Update setting link form publik & batas aktifnya.
     */
    public function updateAdminSetting(Request $request)
    {
        try {
            $setting = $this->getSetting();

            $validator = Validator::make($request->all(), [
                'is_active'         => 'required|boolean',
                'slug'              => 'required|string|max:100|regex:/^[a-zA-Z0-9\-_]+$/|unique:public_form_settings,slug,' . $setting->id,
                'title'             => 'nullable|string|max:255',
                'description'       => 'nullable|string',
                'duration_type'     => 'required|in:minutes,hours,days,custom,indefinite',
                'duration_value'    => 'nullable|numeric|min:1',
                'custom_expired_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $durationType = $validated['duration_type'];
            $durationValue = !empty($validated['duration_value']) ? (int) $validated['duration_value'] : null;

            $now = Carbon::now();
            $expiredAt = null;

            if ($validated['is_active']) {
                switch ($durationType) {
                    case 'minutes':
                        $expiredAt = $now->copy()->addMinutes($durationValue ?: 30);
                        break;
                    case 'hours':
                        $expiredAt = $now->copy()->addHours($durationValue ?: 1);
                        break;
                    case 'days':
                        $expiredAt = $now->copy()->addDays($durationValue ?: 7);
                        break;
                    case 'custom':
                        $expiredAt = !empty($validated['custom_expired_at'])
                            ? Carbon::parse($validated['custom_expired_at'])
                            : $now->copy()->addDays(7);
                        break;
                    case 'indefinite':
                        $expiredAt = null;
                        break;
                }
            }

            $setting->update([
                'is_active'       => $validated['is_active'],
                'slug'            => Str::slug($validated['slug']),
                'title'           => $validated['title'] ?? $setting->title,
                'description'     => $validated['description'] ?? $setting->description,
                'duration_type'   => $durationType,
                'duration_value'  => $durationValue,
                'expired_at'      => $expiredAt,
                'created_by'      => \Illuminate\Support\Facades\Auth::id() ?? $setting->created_by,
            ]);

            return response()->json([
                'status' => true,
                'data' => [
                    'id'                => $setting->id,
                    'form_code'         => $setting->form_code,
                    'slug'              => $setting->slug,
                    'title'             => $setting->title,
                    'description'       => $setting->description,
                    'is_active'         => (bool) $setting->is_active,
                    'expired_at'        => $setting->expired_at ? $setting->expired_at->format('Y-m-d H:i:s') : null,
                    'duration_type'     => $setting->duration_type,
                    'duration_value'    => $setting->duration_value,
                    'total_submissions' => $setting->total_submissions,
                    'is_available'      => $setting->isAvailable(),
                    'remaining_text'    => $setting->getRemainingTimeText(),
                ],
                'message' => 'Pengaturan form publik berhasil disimpan'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error updateAdminSetting PengajuanJudul: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan pengaturan: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * [PUBLIC] Cek status & ketersediaan form publik berdasarkan slug.
     */
    public function checkPublicForm(Request $request, $slug = null)
    {
        try {
            $setting = $this->getSetting($slug);

            if (!$setting) {
                return response()->json([
                    'status' => false,
                    'is_available' => false,
                    'message' => 'Tautan formulir tidak ditemukan atau tidak valid.'
                ], 404);
            }

            $isAvailable = $setting->isAvailable();

            return response()->json([
                'status' => true,
                'is_available' => $isAvailable,
                'data' => [
                    'slug'           => $setting->slug,
                    'title'          => $setting->title,
                    'description'    => $setting->description,
                    'is_active'      => (bool) $setting->is_active,
                    'expired_at'     => $setting->expired_at ? $setting->expired_at->format('d/m/Y H:i') : null,
                    'remaining_text' => $setting->getRemainingTimeText(),
                ],
                'message' => $isAvailable
                    ? 'Formulir siap digunakan'
                    : 'Formulir sedang ditutup atau masa aktif formulir telah berakhir.'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error checkPublicForm PengajuanJudul: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'is_available' => false,
                'message' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }

    /**
     * [PUBLIC] Autocomplete pencarian mahasiswa publik.
     */
    public function searchMahasiswaPublic(Request $request)
    {
        try {
            $slug = $request->query('slug');
            $setting = $this->getSetting($slug);

            if (!$setting || !$setting->isAvailable()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Formulir tidak aktif atau telah kedaluwarsa'
                ], 403);
            }

            $search = $request->query('search', '');
            $data = \App\Services\Mahasiswa::all(
                null,
                20,
                $search,
                null,
                null,
                null,
                null
            );

            return response()->json([
                'status' => true,
                'data' => $data,
                'message' => 'Data mahasiswa berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error searchMahasiswaPublic PengajuanJudul: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data mahasiswa'
            ], 500);
        }
    }

    /**
     * [PUBLIC] Autocomplete pencarian dosen publik.
     */
    public function searchDosenPublic(Request $request)
    {
        try {
            $slug = $request->query('slug');
            $setting = $this->getSetting($slug);

            if (!$setting || !$setting->isAvailable()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Formulir tidak aktif atau telah kedaluwarsa'
                ], 403);
            }

            $search = $request->query('search', '');
            $dosen = \App\Services\DosenService::searchDosen($search);

            return response()->json([
                'status' => true,
                'data' => $dosen,
                'message' => 'Data dosen berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error searchDosenPublic PengajuanJudul: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data dosen'
            ], 500);
        }
    }

    /**
     * [PUBLIC] Pengecekan kemiripan judul proposal skripsi untuk form publik.
     */
    public function checkSimilarityPublic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|min:3',
            'prodi_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = \App\Services\JudulSimilarityService::checkSimilarity(
            $request->input('judul'),
            $request->filled('prodi_id') ? (int)$request->input('prodi_id') : null,
            null,
            30.0
        );

        return response()->json([
            'status' => true,
            'data' => $result,
            'message' => 'Pengecekan kemiripan judul berhasil dilakukan'
        ]);
    }

    /**
     * [PUBLIC] Submit data pengajuan judul dari mahasiswa tanpa login.
     */
    public function submitPublic(Request $request, $slug = null)
    {
        try {
            $setting = $this->getSetting($slug);

            if (!$setting || !$setting->isAvailable()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Maaf, formulir ini sedang tidak menerima tanggapan baru (tutup atau masa aktif berakhir).'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama_mhs'               => 'required|string|max:255',
                'nim'                    => 'required|string|max:50',
                'prodi'                  => 'required|string|max:255',
                'dosen_penasehat'        => 'required|string|max:255',
                'judul_proposal_skripsi' => 'required|string',
                'rumusan_masalah'        => 'required|string',
                'catatan_kprodi'         => 'nullable|string',
                'jenis_kelamin'          => 'nullable|in:L,P',
            ], [
                'nama_mhs.required'               => 'Nama Mahasiswa wajib diisi',
                'nim.required'                    => 'NIM wajib diisi',
                'prodi.required'                  => 'Program Studi wajib diisi',
                'dosen_penasehat.required'        => 'Dosen Penasehat Akademik wajib diisi',
                'judul_proposal_skripsi.required' => 'Judul Proposal Skripsi wajib diisi',
                'rumusan_masalah.required'        => 'Rumusan Masalah / Fokus Penelitian wajib diisi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mohon lengkapi semua data formulir dengan benar.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Match Prodi for prodi_id & tanda_tangan_id
            $prodiObj = Prodi::where('nama', $validated['prodi'])
                ->orWhere('alias', $validated['prodi'])
                ->first();

            $pj = new PengajuanJudul();
            $pj->nim = $validated['nim'];
            $pj->nama_mhs = $validated['nama_mhs'];
            $pj->prodi = $validated['prodi'];
            $pj->prodi_id = $prodiObj ? $prodiObj->id : null;
            $pj->judul_proposal_skripsi = $validated['judul_proposal_skripsi'];
            $pj->rumusan_masalah = $validated['rumusan_masalah'];
            $pj->dosen_penasehat = $validated['dosen_penasehat'];
            $pj->catatan_kprodi = $validated['catatan_kprodi'] ?? null;
            $pj->tanda_tangan_id = $prodiObj ? $prodiObj->tanda_tangan_id : null;
            $pj->jenis_kelamin = $validated['jenis_kelamin'] ?? null;
            $pj->tanggal = date('Y-m-d');
            $pj->petanda_tangan = 'tidak';
            $pj->user_id = null; // Submit publik tanpa user login
            $pj->status = 'pending';
            $pj->save();

            // Generate file PDF
            $controller = new PengajuanJudulController();
            $controller->generatePdfFile($pj->id, true);
            $pj->refresh();

            // Sync ke Google Sheet khusus prodi jika berlaku
            \App\Jobs\SyncPengajuanJudulEsyToGoogleSheet::dispatch($pj->id, 'store');
            \App\Jobs\SyncPengajuanJudulHkiToGoogleSheet::dispatch($pj->id, 'store');
            \App\Jobs\SyncPengajuanJudulKpiToGoogleSheet::dispatch($pj->id, 'store');

            // Increment counter submissions
            $setting->increment('total_submissions');

            return response()->json([
                'status' => true,
                'message' => 'Formulir Pengajuan Judul Skripsi Anda berhasil disimpan!',
                'data' => [
                    'id'                     => $pj->id,
                    'nim'                    => $pj->nim,
                    'nama_mhs'               => $pj->nama_mhs,
                    'prodi'                  => $pj->prodi,
                    'dosen_penasehat'        => $pj->dosen_penasehat,
                    'judul_proposal_skripsi' => $pj->judul_proposal_skripsi,
                    'rumusan_masalah'        => $pj->rumusan_masalah,
                    'tanggal'                => $pj->tanggal,
                    'download_url'           => url('/api/public/pengajuan-judul/download/' . $pj->id),
                ]
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Error submitPublic PengajuanJudul: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menyimpan formulir: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * [PUBLIC] Download PDF formulir publik setelah submit tanpa login.
     */
    public function downloadPdfPublic($id)
    {
        try {
            $data = PengajuanJudul::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $controller = new PengajuanJudulController();

            $viewPath = resource_path('views/pdf/pengajuan_judul.blade.php');
            $viewMtime = file_exists($viewPath) ? filemtime($viewPath) : 0;
            $pdfMtime = (!empty($data->local_path) && file_exists($data->local_path)) ? filemtime($data->local_path) : 0;

            if (empty($data->local_path) || !file_exists($data->local_path) || $viewMtime > $pdfMtime) {
                $controller->generatePdfFile($data->id, false);
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
            Log::error('Public downloadPdf error PengajuanJudul: ' . $th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF: ' . $th->getMessage()], 500);
        }
    }
}
