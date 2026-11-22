<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploudSuratToDrive;
use App\Models\DaftarSidangSkripsi;
use App\Models\DokumenSkripsi;
use App\Models\Prodi;
use App\Services\GoogleDrive;
use App\Services\GoogleSheetSidangHkiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DaftarSidangSkripsiController extends Controller
{
    /**
     * Menampilkan daftar pendaftaran sidang skripsi.
     */
    public function index(Request $request)
    {
        $query = DaftarSidangSkripsi::with(['dokumen', 'prodiRel']);

        $user = Auth::user();
        $userProdi = $user?->prodi ?: ($user?->prodi_id ? Prodi::find($user->prodi_id) : null);

        if ($userProdi) {
            // Scope Prodi jika user login terafiliasi dengan prodi tertentu (Staff)
            $query->where(function ($q) use ($userProdi) {
                $q->where('prodi_id', $userProdi->id)
                  ->orWhere('prodi', $userProdi->nama)
                  ->orWhere('prodi', $userProdi->alias)
                  ->orWhereRaw('LOWER(TRIM(prodi)) = ?', [strtolower(trim($userProdi->nama))])
                  ->orWhereRaw('LOWER(TRIM(prodi)) = ?', [strtolower(trim($userProdi->alias))]);
            });
        } elseif ($request->filled('prodi_id')) {
            // Filter Prodi dari Request (untuk Admin / BAAK yang tidak terafiliasi prodi)
            $prodiFilter = Prodi::find($request->prodi_id);
            $query->where(function ($q) use ($request, $prodiFilter) {
                $q->where('prodi_id', $request->prodi_id);
                if ($prodiFilter) {
                    $q->orWhere('prodi', $prodiFilter->nama)
                      ->orWhere('prodi', $prodiFilter->alias)
                      ->orWhereRaw('LOWER(TRIM(prodi)) = ?', [strtolower(trim($prodiFilter->nama))])
                      ->orWhereRaw('LOWER(TRIM(prodi)) = ?', [strtolower(trim($prodiFilter->alias))]);
                }
            });
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('prodi', 'like', "%{$search}%")
                  ->orWhere('judul_skripsi', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sortBy', 'id');
        $sortType = $request->input('sortType', 'desc');
        $query->orderBy($sortBy, $sortType);

        // Pagination
        $limit = (int) $request->input('limit', 10);
        $data = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Data pendaftaran sidang skripsi berhasil diambil'
        ]);
    }

    /**
     * Menyimpan data pendaftaran sidang skripsi baru beserta dokumen pendukung.
     */
    public function store(Request $request)
    {
        Log::info('DaftarSidangSkripsi store request', $request->except(['dokumen_files']));

        $validator = Validator::make($request->all(), [
            'nama'           => 'required|string|max:255',
            'nim'            => 'required|string|max:50',
            'nik'            => 'nullable|string|max:50',
            'prodi'          => 'required|string|max:255',
            'prodi_id'       => 'nullable|integer',
            'judul_skripsi'  => 'required|string',
            'periode_sidang' => 'required|date',
            'dospem1'        => 'nullable|string|max:255',
            'dospem2'        => 'nullable|string|max:255',
            'pembimbing1'    => 'nullable|string|max:255',
            'pembimbing2'    => 'nullable|string|max:255',
            'dokumen'        => 'nullable|array',
            'dokumen.*.nama_dokumen' => 'nullable|string|max:255',
            'dokumen_files.*'        => 'nullable|file|max:20480', // Maksimal 20MB per file
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $validated = $validator->validated();

            // Set user_id
            $validated['user_id'] = Auth::id();

            // Sinkronisasi prodi_id jika belum terisi atau bernilai 0
            $validated['prodi_id'] = GoogleSheetSidangHkiService::resolveProdiId(
                $validated['prodi'],
                $validated['prodi_id'] ?? null
            );

            $pembimbing1 = $validated['pembimbing1'] ?? ($validated['dospem1'] ?? null);
            $pembimbing2 = $validated['pembimbing2'] ?? ($validated['dospem2'] ?? null);

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
                'user_id'        => $validated['user_id'],
            ]);

            // Simpan dokumen jika ada yang diunggah
            $this->handleDocumentUploads($request, $daftarSidang);

            DB::commit();

            $daftarSidang->load('dokumen');

            try {
                if (GoogleSheetSidangHkiService::isHki($daftarSidang)) {
                    $synced = GoogleSheetSidangHkiService::appendRow($daftarSidang);
                    if (!$synced) {
                        Log::warning("Sync Google Sheet Sidang HKI (store) mengembalikan false untuk ID {$daftarSidang->id}, NIM {$daftarSidang->nim}");
                    }
                }
            } catch (\Throwable $sheetEx) {
                Log::warning('Sync Google Sheet Sidang HKI (store) gagal: ' . $sheetEx->getMessage());
            }

            return response()->json([
                'status' => true,
                'data' => $daftarSidang,
                'message' => 'Pendaftaran sidang skripsi berhasil disimpan'
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error storing DaftarSidangSkripsi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail pendaftaran sidang skripsi.
     */
    public function show($id)
    {
        $data = DaftarSidangSkripsi::with('dokumen')->find($id);

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

    /**
     * Mengupdate data pendaftaran sidang skripsi.
     */
    public function update(Request $request, $id)
    {
        $daftarSidang = DaftarSidangSkripsi::find($id);

        if (!$daftarSidang) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama'           => 'required|string|max:255',
            'nim'            => 'required|string|max:50',
            'nik'            => 'nullable|string|max:50',
            'prodi'          => 'required|string|max:255',
            'prodi_id'       => 'nullable|integer',
            'judul_skripsi'  => 'required|string',
            'periode_sidang' => 'required|date',
            'dospem1'        => 'nullable|string|max:255',
            'dospem2'        => 'nullable|string|max:255',
            'pembimbing1'    => 'nullable|string|max:255',
            'pembimbing2'    => 'nullable|string|max:255',
            'dokumen_files.*'=> 'nullable|file|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $validated = $validator->validated();

            $validated['prodi_id'] = GoogleSheetSidangHkiService::resolveProdiId(
                $validated['prodi'],
                $validated['prodi_id'] ?? null
            );

            $updateData = [
                'nama'           => $validated['nama'],
                'nim'            => $validated['nim'],
                'nik'            => $validated['nik'] ?? null,
                'prodi'          => $validated['prodi'],
                'prodi_id'       => $validated['prodi_id'] ?? null,
                'judul_skripsi'  => $validated['judul_skripsi'],
                'periode_sidang' => $validated['periode_sidang'],
            ];

            if (array_key_exists('pembimbing1', $validated) || array_key_exists('dospem1', $validated)) {
                $updateData['pembimbing1'] = $validated['pembimbing1'] ?? ($validated['dospem1'] ?? null);
            }
            if (array_key_exists('pembimbing2', $validated) || array_key_exists('dospem2', $validated)) {
                $updateData['pembimbing2'] = $validated['pembimbing2'] ?? ($validated['dospem2'] ?? null);
            }

            $oldNama  = $daftarSidang->nama;
            $oldNim   = $daftarSidang->nim;
            $oldProdi = $daftarSidang->prodi;

            $daftarSidang->update($updateData);

            // Jika Nama, NIM, atau Prodi berubah, sinkronkan dokumen lama ke folder & nama baru
            $hasIdentityChanged = ($oldNama !== $validated['nama']) ||
                                  ($oldNim !== $validated['nim']) ||
                                  ($oldProdi !== $validated['prodi']);

            if ($hasIdentityChanged) {
                $this->syncExistingDocumentsOnEdit($daftarSidang, $oldNama, $oldNim, $oldProdi);
            }

            // Unggah dokumen tambahan jika disertakan
            $this->handleDocumentUploads($request, $daftarSidang);

            DB::commit();

            $daftarSidang->load('dokumen');

            try {
                if (GoogleSheetSidangHkiService::isHki($daftarSidang)) {
                    $synced = GoogleSheetSidangHkiService::updateRow($daftarSidang, $oldNim);
                    if (!$synced) {
                        Log::warning("Sync Google Sheet Sidang HKI (update) mengembalikan false untuk ID {$daftarSidang->id}, NIM {$daftarSidang->nim}");
                    }
                }
            } catch (\Throwable $sheetEx) {
                Log::warning('Sync Google Sheet Sidang HKI (update) gagal: ' . $sheetEx->getMessage());
            }

            return response()->json([
                'status' => true,
                'data' => $daftarSidang,
                'message' => 'Data pendaftaran sidang skripsi berhasil diperbarui'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating DaftarSidangSkripsi: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengupdate data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus data pendaftaran sidang skripsi beserta seluruh dokumennya.
     */
    public function destroy($id)
    {
        $daftarSidang = DaftarSidangSkripsi::with('dokumen')->find($id);

        if (!$daftarSidang) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        try {
            $nim = trim($daftarSidang->nim ?? '');
            $cleanNim = preg_replace('/[^a-zA-Z0-9_\-]/', '', $nim) ?: $nim;

            // Hapus file fisik lokal dan Google Drive jika ada
            foreach ($daftarSidang->dokumen as $doc) {
                if ($doc->local_path && File::exists($doc->local_path)) {
                    @File::delete($doc->local_path);
                }
                if ($doc->drive_file_id) {
                    try {
                        \App\Services\GoogleDrive::deleteFile($doc->drive_file_id);
                    } catch (\Throwable $e) {}
                }
            }

            $prodi = trim($daftarSidang->prodi ?: 'UMUM');
            $cleanProdi = preg_replace('/[\\\\\/:*?"<>|]/', ' ', $prodi);
            $cleanProdi = trim(preg_replace('/\s+/', ' ', $cleanProdi)) ?: 'UMUM';

            // Hapus direktori spesifik mahasiswa ini di public_html/pdf/{prodi}/Dokumen Sidang Skripsi/{NIM}
            if ($cleanProdi && $cleanNim) {
                $folder = base_path("../public_html/pdf/{$cleanProdi}/Dokumen Sidang Skripsi/{$cleanNim}");
                if (File::isDirectory($folder)) {
                    @File::deleteDirectory($folder);
                }
                $folderLegacy = base_path("../public_html/{$cleanProdi}/Dokumen Sidang Skripsi/{$cleanNim}");
                if (File::isDirectory($folderLegacy)) {
                    @File::deleteDirectory($folderLegacy);
                }
            }

            $folderOldNim = storage_path('app/public/Berkas Skripsi/' . $cleanNim);
            if (File::isDirectory($folderOldNim)) {
                @File::deleteDirectory($folderOldNim);
            }

            $folderOld = storage_path('app/public/dokumen_skripsi/' . $daftarSidang->id);
            if (File::isDirectory($folderOld)) {
                @File::deleteDirectory($folderOld);
            }

            $daftarSidang->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data pendaftaran sidang skripsi berhasil dihapus'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error deleting DaftarSidangSkripsi: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus data'
            ], 500);
        }
    }

    /**
     * Endpoint untuk menambah/mengunggah dokumen baru pada pendaftaran yang sudah ada.
     */
    public function uploadDokumen(Request $request, $id)
    {
        $daftarSidang = DaftarSidangSkripsi::find($id);

        if (!$daftarSidang) {
            return response()->json([
                'status' => false,
                'message' => 'Data pendaftaran sidang tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'file'         => 'required|file|max:20480',
            'nama_dokumen' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $namaDokumen = $request->input('nama_dokumen');

            $doc = $this->saveSingleDocument($daftarSidang, $file, $namaDokumen);

            try {
                if (GoogleSheetSidangHkiService::isHki($daftarSidang)) {
                    $daftarSidang->load('dokumen');
                    $synced = GoogleSheetSidangHkiService::updateRow($daftarSidang);
                    if (!$synced) {
                        Log::warning("Sync Google Sheet Sidang HKI (uploadDokumen) mengembalikan false untuk ID {$daftarSidang->id}, NIM {$daftarSidang->nim}");
                    }
                }
            } catch (\Throwable $sheetEx) {
                Log::warning('Sync Google Sheet Sidang HKI (uploadDokumen) gagal: ' . $sheetEx->getMessage());
            }

            return response()->json([
                'status' => true,
                'data' => $doc,
                'message' => 'Dokumen berhasil diunggah'
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Error uploadDokumen: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengunggah dokumen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint untuk menghapus dokumen spesifik.
     */
    public function deleteDokumen($dokumenId)
    {
        $dokumen = DokumenSkripsi::find($dokumenId);

        if (!$dokumen) {
            return response()->json([
                'status' => false,
                'message' => 'Dokumen tidak ditemukan'
            ], 404);
        }

        try {
            if ($dokumen->local_path && File::exists($dokumen->local_path)) {
                @File::delete($dokumen->local_path);
            }

            if ($dokumen->drive_file_id) {
                try {
                    \App\Services\GoogleDrive::deleteFile($dokumen->drive_file_id);
                } catch (\Throwable $e) {}
            }

            $dokumen->delete();

            return response()->json([
                'status' => true,
                'message' => 'Dokumen berhasil dihapus'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error deleting DokumenSkripsi: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus dokumen'
            ], 500);
        }
    }

    /**
     * Helper untuk memproses file upload dokumen dari request form.
     */
    protected function handleDocumentUploads(Request $request, DaftarSidangSkripsi $daftarSidang): void
    {
        // 1. Cek jika dikirim dalam bentuk array dokumen_files[*] beserta dokumen_names[*]
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

        // 2. Cek jika dikirim sebagai dokumen[index][file] & dokumen[index][nama_dokumen]
        if ($request->has('dokumen') && is_array($request->dokumen)) {
            foreach ($request->dokumen as $index => $docData) {
                if ($request->hasFile("dokumen.{$index}.file")) {
                    $file = $request->file("dokumen.{$index}.file");
                    $docName = $docData['nama_dokumen'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $this->saveSingleDocument($daftarSidang, $file, $docName);
                }
            }
        }
    }

    /**
     * Jika Nama, NIM, atau Prodi diedit, otomatis sinkronkan seluruh dokumen lama
     * ke nama berkas baru dan folder baru di server (public_html/pdf/{prodi}/Dokumen Sidang Skripsi/{nim}) serta Google Drive.
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
                            } catch (\Throwable $e) {}
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
                } catch (\Throwable $e) {}
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

