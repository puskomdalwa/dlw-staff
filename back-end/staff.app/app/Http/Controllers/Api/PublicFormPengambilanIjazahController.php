<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncFormPengambilanIjazahToGoogleSheet;
use App\Jobs\UploudSuratToDrive;
use App\Models\FormPengambilanIjazah;
use App\Models\PublicFormSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PublicFormPengambilanIjazahController extends Controller
{
    /**
     * Helper: Mendapatkan atau membuat setting form pengambilan ijazah.
     */
    protected function getSetting($slug = null): ?PublicFormSetting
    {
        if ($slug) {
            return PublicFormSetting::where('slug', $slug)->first();
        }

        return PublicFormSetting::firstOrCreate(
            ['form_code' => 'pengambilan_ijazah'],
            [
                'slug' => 'pengambilan-ijazah',
                'title' => 'Formulir Pengambilan Ijazah (Bebas Tanggungan)',
                'description' => 'Silakan isi formulir pengambilan ijazah dan bebas tanggungan dengan data yang valid.',
                'is_active' => true,
                'expired_at' => Carbon::now()->addDays(7),
                'duration_type' => 'days',
                'duration_value' => 7,
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
                'message' => 'Setting form publik berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error getAdminSetting: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil setting: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * [ADMIN] Update setting link form publik & masa aktifnya.
     */
    public function updateAdminSetting(Request $request)
    {
        try {
            $setting = $this->getSetting();

            $validator = Validator::make($request->all(), [
                'is_active'          => 'required|boolean',
                'slug'               => 'required|string|max:100|regex:/^[a-zA-Z0-9\-_]+$/|unique:public_form_settings,slug,' . $setting->id,
                'title'              => 'nullable|string|max:255',
                'description'        => 'nullable|string',
                'duration_type'      => 'required|in:minutes,hours,days,custom,indefinite',
                'duration_value'     => 'nullable|numeric|min:1',
                'custom_expired_at'  => 'nullable|date',
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
            Log::error('Error updateAdminSetting: ' . $th->getMessage());
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
            Log::error('Error checkPublicForm: ' . $th->getMessage());
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
            Log::error('Error searchMahasiswaPublic: ' . $th->getMessage());
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
            Log::error('Error searchDosenPublic: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data dosen'
            ], 500);
        }
    }

    /**
     * [PUBLIC] Submit data dari user luar tanpa login.
     */
    public function submitPublic(Request $request, $slug = null)
    {
        try {
            $setting = $this->getSetting($slug);

            if (!$setting || !$setting->isAvailable()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Maaf, formulir ini sedang tidak menerima tanggapan baru (tutup atau kedaluwarsa).'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama_mhs'          => 'required|string|max:255',
                'nim'               => 'required|string|max:50',
                'prodi'             => 'required|string|max:255',
                'dosen_pembimbing'  => 'required|string|max:255',
                'judul_skripsi'     => 'required|string',
                'tanggal_sidang'    => 'required|date',
                'no_hp'             => 'required|string|max:255',
            ], [
                'nama_mhs.required'         => 'Nama Mahasiswa wajib diisi',
                'nim.required'              => 'NIM wajib diisi',
                'prodi.required'            => 'Program Studi wajib diisi',
                'dosen_pembimbing.required' => 'Dosen Pembimbing wajib diisi',
                'judul_skripsi.required'    => 'Judul Skripsi wajib diisi',
                'tanggal_sidang.required'   => 'Tanggal Sidang wajib diisi',
                'no_hp.required'            => 'Nomor HP/WhatsApp wajib diisi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mohon lengkapi semua data formulir dengan benar.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $validated['user_id'] = null; // Submit publik tanpa user ID
            $validated['status']  = 'pending';

            // Simpan data formulir
            $form = FormPengambilanIjazah::create($validated);

            // Generate file PDF lokal ke public_html/pdf/BAAK/Form Pengambilan Ijazah/
            FormPengambilanIjazahController::generateAndSavePdf($form->id);
            $form->refresh();

            // Dispatch job upload ke Google Drive
            $nameTable = 'Form Pengambilan Ijazah';
            $prodiName = 'BAAK';
            UploudSuratToDrive::dispatch($form->id, $nameTable, $prodiName, FormPengambilanIjazah::class);

            // Dispatch job sync ke Google Spreadsheet secara langsung agar data terjamin masuk tanpa harus menunggu queue worker
            try {
                SyncFormPengambilanIjazahToGoogleSheet::dispatchSync($form->id, 'store');
            } catch (\Throwable $sheetEx) {
                Log::error("Google Sheet sync error on submitPublic: " . $sheetEx->getMessage());
            }

            // Tambahkan counter total submissions
            $setting->increment('total_submissions');

            return response()->json([
                'status' => true,
                'message' => 'Formulir Pengambilan Ijazah Anda berhasil disimpan!',
                'data' => [
                    'id'               => $form->id,
                    'nim'              => $form->nim,
                    'nama_mhs'         => $form->nama_mhs,
                    'prodi'            => $form->prodi,
                    'dosen_pembimbing' => $form->dosen_pembimbing,
                    'judul_skripsi'    => $form->judul_skripsi,
                    'tanggal_sidang'   => $form->tanggal_sidang,
                    'no_hp'            => $form->no_hp,
                    'download_url'     => url('/api/public/form-pengambilan-ijazah/download/' . $form->id),
                ]
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Error submitPublic FormPengambilanIjazah: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan server saat menyimpan formulir: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * [PUBLIC] Download PDF formulir publik setelah pengisian berhasil.
     */
    public function downloadPdfPublic($id)
    {
        try {
            $data = FormPengambilanIjazah::find($id);

            if (!$data) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $viewPath = resource_path('views/pdf/form_pengambilan_ijazah.blade.php');
            $viewMtime = file_exists($viewPath) ? filemtime($viewPath) : 0;
            $pdfMtime = (!empty($data->local_path) && file_exists($data->local_path)) ? filemtime($data->local_path) : 0;

            if (empty($data->local_path) || !file_exists($data->local_path) || $viewMtime > $pdfMtime) {
                FormPengambilanIjazahController::generateAndSavePdf($data->id);
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
            Log::error('downloadPdfPublic error: ' . $th->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal download PDF: ' . $th->getMessage()], 500);
        }
    }
}
