<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\DaftarSidangSkripsi;
use App\Models\DokumenSkripsi;
use App\Models\PengajuanJudul;
use App\Models\Prodi;
use App\Models\PublicFormSetting;
use App\Services\GoogleDrive;
use App\Services\GoogleSheetSidangHkiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PublicFormDaftarSidangSkripsiController extends Controller
{
    /**
     * Helper: Mendapatkan atau membuat setting form pendaftaran sidang skripsi.
     */
    protected function getSetting($slug = null): ?PublicFormSetting
    {
        if ($slug) {
            return PublicFormSetting::where('form_code', 'daftar_sidang_skripsi')
                ->where('slug', $slug)
                ->first() ?: PublicFormSetting::where('slug', $slug)->first();
        }

        return PublicFormSetting::firstOrCreate(
            ['form_code' => 'daftar_sidang_skripsi'],
            [
                'slug'              => 'daftar-sidang-skripsi',
                'title'             => 'Formulir Pendaftaran Sidang Skripsi',
                'description'       => 'Silakan lengkapi formulir pendaftaran sidang skripsi berikut dan unggah seluruh berkas dokumen persyaratan.',
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
                'message' => 'Setting form publik pendaftaran sidang berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error getAdminSetting DaftarSidangSkripsi: ' . $th->getMessage());
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
                'message' => 'Pengaturan form publik pendaftaran sidang berhasil disimpan'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error updateAdminSetting DaftarSidangSkripsi: ' . $th->getMessage());
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
                    'message' => 'Tautan formulir pendaftaran sidang tidak ditemukan atau tidak valid.'
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
                    ? 'Formulir pendaftaran sidang siap digunakan'
                    : 'Formulir pendaftaran sidang sedang ditutup atau masa aktif formulir telah berakhir.'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error checkPublicForm DaftarSidangSkripsi: ' . $th->getMessage());
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

            // Perkaya dengan data judul dari tabel pengajuan_judul
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

            $juduls = PengajuanJudul::where(function ($q) use ($nims, $cleanNims) {
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

            // Ambil data dosen pembimbing dari surat_tugas jika tersedia
            $suratTugasList = \App\Models\SuratTugas::where(function ($q) use ($nims, $cleanNims) {
                if (!empty($nims)) {
                    $q->whereIn('nim_nik', $nims);
                }
                if (!empty($cleanNims)) {
                    $q->orWhereIn('nim_nik', $cleanNims);
                }
            })->orderBy('id', 'desc')->get();

            $stMap = [];
            foreach ($suratTugasList as $st) {
                $rawStNim = $st->nim_nik;
                $cleanStNim = preg_replace('/[^a-zA-Z0-9]/', '', $st->nim_nik);
                if (!empty($rawStNim) && !isset($stMap[$rawStNim])) $stMap[$rawStNim] = $st;
                if (!empty($cleanStNim) && !isset($stMap[$cleanStNim])) $stMap[$cleanStNim] = $st;
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

                $matchedSt = $stMap[$mNim] ?? ($stMap[$cleanMNim] ?? null);
                if ($matchedSt) {
                    $m->dospem1 = $matchedSt->pebimbing1 ?: ($matchedSt->pembimbing1 ?? null);
                    $m->dospem2 = $matchedSt->pebimbing2 ?: ($matchedSt->pembimbing2 ?? null);
                } else {
                    $m->dospem1 = null;
                    $m->dospem2 = null;
                }
            }
            unset($m);

            return response()->json([
                'status' => true,
                'data' => $listMhs,
                'message' => 'Data mahasiswa berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error searchMahasiswaPublic DaftarSidangSkripsi: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data mahasiswa'
            ], 500);
        }
    }

    /**
     * [PUBLIC] Cari data dosen untuk Dospem 1 & Dospem 2 di form publik.
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
            Log::error('Error searchDosenPublic DaftarSidangSkripsi: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data dosen'
            ], 500);
        }
    }

    /**
     * [PUBLIC] Ambil judul skripsi dan pembimbing berdasarkan NIM dari pengajuan_judul dan surat_tugas.
     */
    public function getJudulByNimPublic(Request $request, $nim)
    {
        try {
            $rawNim = trim($nim);
            $cleanNim = preg_replace('/[^a-zA-Z0-9]/', '', $rawNim);

            $data = PengajuanJudul::where('nim', $rawNim)
                ->orWhere('nim', $cleanNim)
                ->orWhere('nim', 'like', "%{$rawNim}%")
                ->orWhere('nim', 'like', "%{$cleanNim}%")
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(nim, '.', ''), '-', ''), ' ', '') = ?", [$cleanNim])
                ->orderBy('id', 'desc')
                ->first();

            // Coba ambil dospem dari surat_tugas jika ada
            $suratTugas = \App\Models\SuratTugas::where('nim_nik', $rawNim)
                ->orWhere('nim_nik', $cleanNim)
                ->orWhere('nim_nik', 'like', "%{$rawNim}%")
                ->orWhere('nim_nik', 'like', "%{$cleanNim}%")
                ->orderBy('id', 'desc')
                ->first();

            $pembimbing1 = $suratTugas ? ($suratTugas->pebimbing1 ?: ($suratTugas->pembimbing1 ?? null)) : null;
            $pembimbing2 = $suratTugas ? ($suratTugas->pebimbing2 ?: ($suratTugas->pembimbing2 ?? null)) : null;

            if (!$data && !$suratTugas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data pengajuan judul tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $resultData = $data ? $data->toArray() : [];
            $resultData['dospem1'] = $pembimbing1;
            $resultData['dospem2'] = $pembimbing2;
            $resultData['pembimbing1'] = $pembimbing1;
            $resultData['pembimbing2'] = $pembimbing2;

            return response()->json([
                'status' => true,
                'data' => $resultData,
                'message' => 'Data pengajuan judul dan pembimbing berhasil diambil'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data judul'
            ], 500);
        }
    }

    /**
     * [PUBLIC] Submit pendaftaran sidang skripsi dari form publik tanpa login.
     */
    public function submitPublic(Request $request, $slug = null)
    {
        try {
            $setting = $this->getSetting($slug);

            if (!$setting || !$setting->isAvailable()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Maaf, formulir pendaftaran sidang ini sedang tidak menerima tanggapan baru (tutup atau masa aktif berakhir).'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama'            => 'required|string|max:255',
                'nim'             => 'required|string|max:50',
                'nik'             => 'nullable|string|max:50',
                'prodi'           => 'required|string|max:255',
                'prodi_id'        => 'nullable|integer',
                'judul_skripsi'   => 'required|string',
                'periode_sidang'  => 'required|date',
                'dospem1'         => 'nullable|string|max:255',
                'dospem2'         => 'nullable|string|max:255',
                'pembimbing1'     => 'nullable|string|max:255',
                'pembimbing2'     => 'nullable|string|max:255',
                'dokumen_files.*' => 'nullable|file|max:20480',
            ], [
                'nama.required'           => 'Nama Lengkap Mahasiswa wajib diisi',
                'nim.required'            => 'NIM Mahasiswa wajib diisi',
                'prodi.required'          => 'Program Studi wajib diisi',
                'judul_skripsi.required'  => 'Judul Skripsi wajib diisi',
                'periode_sidang.required' => 'Periode/Tanggal Sidang wajib diisi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal, silakan periksa kelengkapan formulir Anda.',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $validated = $validator->validated();

            // Match prodi_id jika belum terisi atau bernilai 0
            $validated['prodi_id'] = GoogleSheetSidangHkiService::resolveProdiId(
                $validated['prodi'],
                $validated['prodi_id'] ?? null
            );

            $pembimbing1 = $validated['pembimbing1'] ?? ($validated['dospem1'] ?? null);
            $pembimbing2 = $validated['pembimbing2'] ?? ($validated['dospem2'] ?? null);

            $rawNim = trim($validated['nim']);
            $cleanNim = preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawNim);
            $alnumNim = preg_replace('/[^a-zA-Z0-9]/', '', $rawNim);

            // Cek apakah mahasiswa ini sudah pernah mendaftar sebelumnya (edit/resubmit via form publik)
            $existing = DaftarSidangSkripsi::where('nim', $rawNim)
                ->orWhere('nim', $cleanNim)
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(nim, '.', ''), '-', ''), ' ', '') = ?", [$alnumNim])
                ->orderBy('id', 'desc')
                ->first();

            $isUpdate = false;
            $oldNim = null;

            if ($existing) {
                // Update pendaftaran yang sudah ada
                $isUpdate = true;
                $oldNama = $existing->nama;
                $oldNim = $existing->nim;
                $oldProdi = $existing->prodi;

                $updateData = [
                    'nama'           => $validated['nama'],
                    'nim'            => $validated['nim'],
                    'nik'            => $validated['nik'] ?? $existing->nik,
                    'prodi'          => $validated['prodi'],
                    'prodi_id'       => $validated['prodi_id'] ?? $existing->prodi_id,
                    'judul_skripsi'  => $validated['judul_skripsi'],
                    'periode_sidang' => $validated['periode_sidang'],
                ];
                if ($pembimbing1) $updateData['pembimbing1'] = $pembimbing1;
                if ($pembimbing2) $updateData['pembimbing2'] = $pembimbing2;

                $existing->update($updateData);

                // Jika Nama, NIM, atau Prodi berubah, otomatis sinkronkan seluruh berkas lama
                $hasIdentityChanged = ($oldNama !== $validated['nama']) ||
                    ($oldNim !== $validated['nim']) ||
                    ($oldProdi !== $validated['prodi']);

                if ($hasIdentityChanged) {
                    $this->syncExistingDocumentsOnEdit($existing, $oldNama, $oldNim, $oldProdi);
                }

                // Unggah berkas baru atau pengganti jika ada yang dilampirkan
                $this->handleDocumentUploads($request, $existing);

                $setting->increment('total_submissions');
                $daftarSidang = $existing;
                $messageSuccess = 'Pendaftaran sidang skripsi berhasil diperbarui! Berkas Anda telah disinkronkan.';
            } else {
                // Pendaftaran baru
                $daftarSidang = DaftarSidangSkripsi::create([
                    'nama'           => $validated['nama'],
                    'nim'            => $validated['nim'],
                    'nik'            => $validated['nik'] ?? null,
                    'prodi'          => $validated['prodi'],
                    'prodi_id'       => $validated['prodi_id'] ?? null,
                    'judul_skripsi'  => $validated['judul_skripsi'],
                    'periode_sidang' => $validated['periode_sidang'],
                    'pembimbing1'    => $pembimbing1,
                    'pembimbing2'    => $pembimbing2,
                    'user_id'        => null, // Submit publik tanpa login
                ]);

                // Simpan berkas fisik ke: public_html/pdf/{prodi}/Dokumen Sidang Skripsi/{nim} & Google Drive
                $this->handleDocumentUploads($request, $daftarSidang);

                // Tambah counter submission di setting
                $setting->increment('total_submissions');
                $messageSuccess = 'Pendaftaran sidang skripsi berhasil dikirim! Berkas Anda sedang diproses oleh sistem.';
            }

            DB::commit();

            $daftarSidang->load('dokumen');

            try {
                if (GoogleSheetSidangHkiService::isHki($daftarSidang)) {
                    $synced = $isUpdate
                        ? GoogleSheetSidangHkiService::updateRow($daftarSidang, $oldNim)
                        : GoogleSheetSidangHkiService::appendRow($daftarSidang);

                    if (!$synced) {
                        Log::warning("Sync Google Sheet Sidang HKI (public submit) mengembalikan false untuk ID {$daftarSidang->id}, NIM {$daftarSidang->nim}");
                    }
                }
            } catch (\Throwable $sheetEx) {
                Log::warning('Sync Google Sheet Sidang HKI (public submit) gagal: ' . $sheetEx->getMessage());
            }

            return response()->json([
                'status' => true,
                'data' => $daftarSidang,
                'message' => $messageSuccess
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error submitPublic DaftarSidangSkripsi: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengirim pendaftaran: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Helper untuk memproses file upload dokumen dari request form.
     */
    protected function handleDocumentUploads(Request $request, DaftarSidangSkripsi $daftarSidang): void
    {
        if ($request->hasFile('dokumen_files')) {
            $files = $request->file('dokumen_files');
            $names = $request->input('dokumen_names', []);

            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $docName = $names[$index] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $this->saveSingleDocument($daftarSidang, $file, $docName);
                }
            }
        }
    }

    /**
     * Jika Nama, NIM, atau Prodi diedit/diperbarui pada pendaftaran yang sudah ada,
     * otomatis sinkronkan seluruh dokumen lama ke nama berkas baru dan folder baru
     * di server (public_html) serta Google Drive.
     */
    protected function syncExistingDocumentsOnEdit(DaftarSidangSkripsi $daftarSidang, string $oldNama, string $oldNim, string $oldProdi): void
    {
        $cleanNewProdi = preg_replace('/[\\\\\/:*?"<>|]/', ' ', trim($daftarSidang->prodi ?: 'UMUM'));
        $cleanNewProdi = trim(preg_replace('/\s+/', ' ', $cleanNewProdi)) ?: 'UMUM';

        $cleanNewNim = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($daftarSidang->nim ?? '')) ?: 'UNKNOWN';
        $cleanNewNama = trim(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($daftarSidang->nama ?? '')), '_') ?: 'Mahasiswa';

        $cleanOldProdi = preg_replace('/[\\\\\/:*?"<>|]/', ' ', trim($oldProdi ?: 'UMUM'));
        $cleanOldProdi = trim(preg_replace('/\s+/', ' ', $cleanOldProdi)) ?: 'UMUM';
        $cleanOldNim = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($oldNim ?? '')) ?: 'UNKNOWN';

        $newServerDir = base_path("../public_html/pdf/{$cleanNewProdi}/Dokumen Sidang Skripsi/{$cleanNewNim}");
        if (!File::isDirectory($newServerDir)) {
            File::makeDirectory($newServerDir, 0755, true);
        }

        $newDriveFolder = "{$cleanNewProdi}/Dokumen Sidang Skripsi/{$cleanNewNim}";
        $oldServerDir = base_path("../public_html/pdf/{$cleanOldProdi}/Dokumen Sidang Skripsi/{$cleanOldNim}");
        $oldLegacyServerDir = base_path("../public_html/{$cleanOldProdi}/Dokumen Sidang Skripsi/{$cleanOldNim}");

        foreach ($daftarSidang->dokumen as $doc) {
            $rawDoc = trim($doc->nama_dokumen);
            $cleanDoc = trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $rawDoc), '_') ?: 'Dokumen';

            $currentLocal = $doc->local_path;
            $extension = $currentLocal ? pathinfo($currentLocal, PATHINFO_EXTENSION) : 'pdf';
            if (!$extension) $extension = 'pdf';

            $newFileName = "{$cleanNewNama}_{$cleanNewNim}_{$cleanDoc}.{$extension}";
            $newLocalPath = "{$newServerDir}/{$newFileName}";

            // 1. Pindahkan / Rename berkas lokal di server
            if ($currentLocal && File::exists($currentLocal)) {
                if ($currentLocal !== $newLocalPath) {
                    @rename($currentLocal, $newLocalPath);
                }
            }

            // 2. Upload ke folder Google Drive yang baru
            if (File::exists($newLocalPath)) {
                try {
                    GoogleDrive::ensureFolder($cleanNewProdi);
                    GoogleDrive::ensureFolder("{$cleanNewProdi}/Dokumen Sidang Skripsi");
                    GoogleDrive::ensureFolder($newDriveFolder);

                    $result = GoogleDrive::uploadFile($newDriveFolder, $newLocalPath, $newFileName);
                    if (!empty($result['file_id'])) {
                        GoogleDrive::setPublicPermission($result['file_id']);

                        $oldDriveId = $doc->drive_file_id;
                        $doc->update([
                            'drive_file_id' => $result['file_id'],
                            'drive_link'    => "https://drive.google.com/file/d/{$result['file_id']}/view",
                            'local_path'    => $newLocalPath,
                            'status'        => 'uploaded',
                        ]);

                        // Hapus file lama di Drive jika ID berbeda
                        if ($oldDriveId && $oldDriveId !== $result['file_id']) {
                            try {
                                GoogleDrive::deleteFile($oldDriveId);
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to sync doc {$doc->id} to new Drive folder: " . $e->getMessage());
                }
            }
        }

        // Hapus direktori lama jika berbeda dan sudah kosong
        if ($oldServerDir !== $newServerDir && File::isDirectory($oldServerDir)) {
            $filesLeft = glob("{$oldServerDir}/*");
            if (empty($filesLeft)) {
                @File::deleteDirectory($oldServerDir);
            }
        }
        if (isset($oldLegacyServerDir) && File::isDirectory($oldLegacyServerDir)) {
            $legacyFilesLeft = glob("{$oldLegacyServerDir}/*");
            if (empty($legacyFilesLeft)) {
                @File::deleteDirectory($oldLegacyServerDir);
            }
        }
    }

    /**
     * Simpan file dokumen sidang skripsi ke:
     * - Server di dalam folder: public_html/pdf/{prodi}/Dokumen Sidang Skripsi/{nim mahasiswa}
     * - Google Drive di dalam folder: {prodi}/Dokumen Sidang Skripsi/{nim mahasiswa}
     */
    protected function saveSingleDocument(DaftarSidangSkripsi $daftarSidang, $uploadedFile, string $namaDokumen): DokumenSkripsi
    {
        $rawNama = trim($daftarSidang->nama ?? '');
        $cleanNama = trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $rawNama), '_') ?: 'Mahasiswa';

        $rawNim = trim($daftarSidang->nim ?? '');
        $cleanNim = preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawNim) ?: ($rawNim ?: 'UNKNOWN_' . $daftarSidang->id);

        $rawDoc = trim($namaDokumen);
        $cleanDoc = trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $rawDoc), '_') ?: 'Dokumen';

        $extension = $uploadedFile->getClientOriginalExtension() ?: 'pdf';
        $fileName = "{$cleanNama}_{$cleanNim}_{$cleanDoc}.{$extension}";

        $prodi = trim($daftarSidang->prodi ?: 'UMUM');
        $cleanProdi = preg_replace('/[\\\\\/:*?"<>|]/', ' ', $prodi);
        $cleanProdi = trim(preg_replace('/\s+/', ' ', $cleanProdi)) ?: 'UMUM';

        // 1. Simpan di server dalam folder public_html/pdf/{prodi}/Dokumen Sidang Skripsi/{nim mahasiswa}
        $serverDirectory = base_path("../public_html/pdf/{$cleanProdi}/Dokumen Sidang Skripsi/{$cleanNim}");
        if (!File::isDirectory($serverDirectory)) {
            File::makeDirectory($serverDirectory, 0755, true);
        }

        $uploadedFile->move($serverDirectory, $fileName);
        $localPath = "{$serverDirectory}/{$fileName}";

        // 2. Format path di Google Drive: {prodi}/Dokumen Sidang Skripsi/{nim mahasiswa}
        $driveFolderPath = "{$cleanProdi}/Dokumen Sidang Skripsi/{$cleanNim}";

        $driveFileId = null;
        $driveLink = null;
        $status = 'pending';

        // Langsung upload ke Google Drive
        try {
            GoogleDrive::ensureFolder($cleanProdi);
            GoogleDrive::ensureFolder("{$cleanProdi}/Dokumen Sidang Skripsi");
            GoogleDrive::ensureFolder($driveFolderPath);

            $result = GoogleDrive::uploadFile($driveFolderPath, $localPath, $fileName);

            if (!empty($result['file_id'])) {
                $driveFileId = $result['file_id'];
                $driveLink = "https://drive.google.com/file/d/{$driveFileId}/view";
                GoogleDrive::setPublicPermission($driveFileId);
                $status = 'uploaded';
            }
        } catch (\Throwable $e) {
            Log::error("Direct upload Google Drive failed for {$fileName}: " . $e->getMessage());
        }

        // Simpan data dokumen ke database (update jika sudah ada dokumen dengan nama yang sama, atau create baru)
        $existingDoc = DokumenSkripsi::where('daftar_sidang_skripsi_id', $daftarSidang->id)
            ->where('nama_dokumen', $namaDokumen)
            ->first();

        if ($existingDoc) {
            if ($existingDoc->local_path && $existingDoc->local_path !== $localPath && File::exists($existingDoc->local_path)) {
                @File::delete($existingDoc->local_path);
            }
            if ($existingDoc->drive_file_id && $driveFileId && $existingDoc->drive_file_id !== $driveFileId) {
                try {
                    GoogleDrive::deleteFile($existingDoc->drive_file_id);
                } catch (\Throwable $e) {
                }
            }

            $existingDoc->update([
                'drive_file_id' => $driveFileId ?: $existingDoc->drive_file_id,
                'drive_link'    => $driveLink ?: $existingDoc->drive_link,
                'local_path'    => $localPath,
                'status'        => $status,
            ]);
            $dokumen = $existingDoc;
        } else {
            $dokumen = DokumenSkripsi::create([
                'daftar_sidang_skripsi_id' => $daftarSidang->id,
                'nama_dokumen'             => $namaDokumen,
                'drive_file_id'            => $driveFileId,
                'drive_link'               => $driveLink,
                'local_path'               => $localPath,
                'status'                   => $status,
            ]);
        }

        // Jika upload langsung terkendala, dispatch job sebagai background retry
        if ($status !== 'uploaded' && File::exists($localPath)) {
            try {
                UploudSuratToDrive::dispatch(
                    $dokumen->id,
                    'Dokumen Sidang Skripsi',
                    $cleanProdi,
                    DokumenSkripsi::class,
                    $driveFolderPath
                );
            } catch (\Throwable $ex) {
                Log::warning('Dispatch upload to drive failed: ' . $ex->getMessage());
            }
        }

        return $dokumen;
    }
}
