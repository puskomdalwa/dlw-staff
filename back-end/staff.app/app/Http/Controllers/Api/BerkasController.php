<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Berkas;
use App\Models\KategoriBerkas;
use App\Models\Prodi;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BerkasController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Berkas index request: ', $request->all());

        $data = Berkas::with(['kategoriBerkas', 'prodi']);

        $user = Auth::user();
        if ($user && $user->prodi_id) {
            $data->where('prodi_id', $user->prodi_id);
        } elseif ($request->filled('prodi_id')) {
            $data->where('prodi_id', $request->prodi_id);
        }

        // Filter tipe berkas (non_skripsi atau skripsi)
        if ($request->filled('tipe_berkas')) {
            $data->where('tipe_berkas', $request->tipe_berkas);
        }

        // Filter kategori berkas
        if ($request->filled('kategori_berkas_id')) {
            $data->where('kategori_berkas_id', $request->kategori_berkas_id);
        }

        // Filter search (nama, nim, nama_dokumen, atau nama kategori)
        if ($request->filled('search')) {
            $search = $request->search;
            $data->where(function ($q) use ($search) {
                $q->where('nama_mhs', 'LIKE', "%{$search}%")
                  ->orWhere('nim', 'LIKE', "%{$search}%")
                  ->orWhere('nama_dokumen', 'LIKE', "%{$search}%")
                  ->orWhereHas('kategoriBerkas', function ($sub) use ($search) {
                      $sub->where('nama_kategori', 'LIKE', "%{$search}%");
                  });
            });
        }

        $data->orderBy(
            $request->input('sortBy', 'id'),
            $request->input('sortType', 'desc')
        );

        $limit = (int) $request->input('limit', 10);
        $result = $data->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $result,
            'message' => 'Data berkas berhasil diambil'
        ]);
    }

    /**
     * Resolve Prodi berdasarkan input form atau user yang login.
     */
    protected function resolveProdi(?int $requestProdiId): array
    {
        $user = Auth::user();
        $targetProdiId = $requestProdiId ?: $user?->prodi_id;
        $prodi = $targetProdiId ? Prodi::find($targetProdiId) : null;
        $rawProdi = $prodi ? $prodi->nama : 'UMUM';
        $cleanProdi = trim(preg_replace('/[\\\\\/:*?"<>|]/', ' ', $rawProdi)) ?: 'UMUM';

        return [$prodi?->id, $cleanProdi];
    }

    /**
     * Validasi & resolve direktori lokal (case-insensitive per segmen).
     * Jika folder dengan nama yang sama sudah ada di dalam folder prodi, langsung gunakan folder tersebut.
     * Jika belum ada, buatkan folder baru.
     */
    protected function ensureLocalDirectory(string $subPath): string
    {
        $basePath = base_path('../public_html/pdf');
        if (!File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        $segments = array_values(array_filter(explode('/', trim($subPath, '/')), fn($s) => trim($s) !== ''));
        $currentDir = rtrim($basePath, '/\\');

        foreach ($segments as $segment) {
            $targetLower = mb_strtolower(trim($segment));
            $matchedDir = null;

            foreach (File::directories($currentDir) as $dir) {
                if (mb_strtolower(trim(basename($dir))) === $targetLower) {
                    $matchedDir = $dir;
                    break;
                }
            }

            if ($matchedDir !== null) {
                $currentDir = $matchedDir;
            } else {
                $currentDir = $currentDir . DIRECTORY_SEPARATOR . trim($segment);
                if (!File::isDirectory($currentDir)) {
                    File::makeDirectory($currentDir, 0755, true);
                }
            }
        }

        return str_replace('\\', '/', $currentDir);
    }

    /**
     * Simpan satu file (UploadedFile atau base64) ke lokal dan upload ke Google Drive.
     *
     * Format penyimpanan:
     * - Non Skripsi:
     *     Folder: {prodi}/{nama_kategori}
     *     File  : {slug_kategori}_{nim}_{uniqid}.{ext}
     *     Contoh: Ekonomi Syariah/Surat Keterangan Aktif Mahasiswa/surat_keterangan_aktif_mahasiswa_201785290300_6aa1342b5edc9.jpg
     *
     * - Berkas Skripsi:
     *     Folder: {prodi}/Dokumen Sidang Skripsi/{nim}
     *     File  : {NAMA_MAHASISWA}_{NIM}_{Nama_Dokumen}.{ext}
     *     Contoh: Ekonomi Syariah/Dokumen Sidang Skripsi/202185290026/MOH_SHOLIHIN_YASIN_202185290026_Lembar_Konsultasi_Dospem_1.jpg
     */
    protected function saveAndUploadSingleFile(
        string $tipeBerkas,
        string $cleanProdi,
        string $folderCategory,
        string $nim,
        string $namaMhs,
        ?string $namaDokumen,
        $uploadedFile = null,
        ?string $base64String = null
    ): array {
        $cleanNim = preg_replace('/[^a-zA-Z0-9_\-]/', '', $nim) ?: 'UNKNOWN';

        if ($tipeBerkas === 'skripsi') {
            $subPath = "{$cleanProdi}/Dokumen Sidang Skripsi/{$cleanNim}";
            $cleanNamaUpper = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $namaMhs), '_')) ?: 'MAHASISWA';
            $cleanDoc = trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $namaDokumen ?: 'Dokumen'), '_') ?: 'Dokumen';
            $baseFileName = "{$cleanNamaUpper}_{$cleanNim}_{$cleanDoc}";
        } else {
            $subPath = "{$cleanProdi}/{$folderCategory}";
            $slugKategori = Str::slug($folderCategory, '_') ?: 'berkas';
            $baseFileName = "{$slugKategori}_{$cleanNim}_" . uniqid();
        }

        // Validasi & pastikan direktori lokal ada (gunakan folder existing jika sudah ada)
        $serverDirectory = $this->ensureLocalDirectory($subPath);

        $fileName = '';
        $localPath = '';

        if ($uploadedFile) {
            $ext = strtolower($uploadedFile->getClientOriginalExtension() ?: 'jpg');
            $fileName = "{$baseFileName}.{$ext}";
            $uploadedFile->move($serverDirectory, $fileName);
            $localPath = "{$serverDirectory}/{$fileName}";
        } elseif (!empty($base64String)) {
            $ext = 'jpg';
            $rawBase64 = $base64String;
            if (preg_match('/^data:image\/(\w+);base64,/', $rawBase64, $matches)) {
                $ext = strtolower($matches[1]) === 'png' ? 'png' : 'jpg';
                $rawBase64 = substr($rawBase64, strpos($rawBase64, ',') + 1);
            }
            $fileData = base64_decode($rawBase64);
            $fileName = "{$baseFileName}.{$ext}";
            $localPath = "{$serverDirectory}/{$fileName}";
            File::put($localPath, $fileData);
        }

        // Validasi folder di Google Drive & upload (jika nama folder sama sudah ada di prodi, langsung masukkan ke folder tersebut)
        $driveFileId = null;
        $driveLink = null;
        $status = 'pending';

        try {
            $resolvedDriveFolder = GoogleDrive::ensureFolder($subPath);
            $uploadResult = GoogleDrive::uploadFile($resolvedDriveFolder, $localPath, $fileName);

            if (!empty($uploadResult['file_id'])) {
                GoogleDrive::setPublicPermission($uploadResult['file_id']);
                $driveFileId = $uploadResult['file_id'];
                $driveLink = "https://drive.google.com/file/d/{$driveFileId}/view";
                $status = 'uploaded';
            }
        } catch (\Throwable $e) {
            Log::error("Gagal mengupload berkas {$fileName} ke Google Drive: " . $e->getMessage());
            $status = 'failed';
        }

        return [
            'local_path'    => $localPath,
            'drive_file_id' => $driveFileId,
            'drive_link'    => $driveLink,
            'status'        => $status,
        ];
    }

    public function store(Request $request)
    {
        Log::info('Berkas store request: ', $request->except(['file_base64', 'file', 'dokumen_files', 'dokumen_base64']));

        try {
            $tipeBerkas = $request->input('tipe_berkas', 'non_skripsi');

            $validator = Validator::make($request->all(), [
                'nama_mhs'           => 'required|string|max:255',
                'nim'                => 'required|string|max:50',
                'kategori_berkas_id' => $tipeBerkas === 'skripsi'
                    ? 'nullable|exists:kategori_berkas,id'
                    : 'required|exists:kategori_berkas,id',
                'nama_dokumen'       => 'nullable|string|max:255',
                'prodi_id'           => 'nullable|exists:prodi,id',
                'jenis_kelamin'      => 'nullable|in:L,P',
                'tipe_berkas'        => 'nullable|in:non_skripsi,skripsi',
                'file'               => 'nullable|file|max:25600',
                'file_base64'        => 'nullable|string',
                'dokumen'            => 'nullable|array',
                'dokumen.*.nama_dokumen' => 'nullable|string|max:255',
                'dokumen_files.*'        => 'nullable|file|max:25600',
                'dokumen_base64.*'       => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            [$prodiId, $cleanProdi] = $this->resolveProdi(
                isset($validated['prodi_id']) ? (int) $validated['prodi_id'] : null
            );

            if (!$prodiId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Program studi wajib dipilih atau akun harus terhubung ke prodi.',
                    'errors' => ['prodi_id' => ['Program studi tidak ditemukan']]
                ], 422);
            }

            // Cek apakah mode multi-dokumen (Tab 2 Berkas Skripsi seperti Form Pendaftaran Sidang Skripsi)
            $dokumenMeta = $request->input('dokumen', []);
            $dokumenFiles = $request->file('dokumen_files', []);
            $dokumenBase64 = $request->input('dokumen_base64', []);
            $hasMultiDocs = is_array($dokumenMeta) && count($dokumenMeta) > 0 && (
                !empty($dokumenFiles) || !empty(array_filter((array) $dokumenBase64))
            );

            if ($hasMultiDocs) {
                $kategori = !empty($validated['kategori_berkas_id'])
                    ? KategoriBerkas::find($validated['kategori_berkas_id'])
                    : KategoriBerkas::whereRaw('LOWER(nama_kategori) = ?', ['dokumen sidang skripsi'])->first();

                $savedItems = [];
                foreach ($dokumenMeta as $idx => $meta) {
                    $uploadedFile = $dokumenFiles[$idx] ?? null;
                    $base64Str = $dokumenBase64[$idx] ?? null;

                    if (!$uploadedFile && empty($base64Str)) {
                        continue;
                    }

                    $docName = trim($meta['nama_dokumen'] ?? '');
                    if (!$docName) {
                        $docName = $uploadedFile
                            ? pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME)
                            : 'Dokumen Skripsi #' . ($idx + 1);
                    }

                    $uploadInfo = $this->saveAndUploadSingleFile(
                        'skripsi',
                        $cleanProdi,
                        'Dokumen Sidang Skripsi',
                        $validated['nim'],
                        $validated['nama_mhs'],
                        $docName,
                        $uploadedFile,
                        $base64Str
                    );

                    // Update jika sudah ada dokumen skripsi dengan nim & nama_dokumen yang sama, atau create baru
                    $existingDoc = Berkas::where('nim', $validated['nim'])
                        ->where('tipe_berkas', 'skripsi')
                        ->where('nama_dokumen', $docName)
                        ->first();

                    if ($existingDoc) {
                        if (!empty($existingDoc->drive_file_id) && $existingDoc->drive_file_id !== $uploadInfo['drive_file_id']) {
                            try {
                                GoogleDrive::deleteFile($existingDoc->drive_file_id);
                            } catch (\Throwable $e) {}
                        }
                        if (!empty($existingDoc->local_path) && $existingDoc->local_path !== $uploadInfo['local_path'] && File::exists($existingDoc->local_path)) {
                            @File::delete($existingDoc->local_path);
                        }
                        $existingDoc->update([
                            'nama_mhs'           => $validated['nama_mhs'],
                            'kategori_berkas_id' => $kategori?->id,
                            'prodi_id'           => $prodiId,
                            'jenis_kelamin'      => $validated['jenis_kelamin'] ?? $existingDoc->jenis_kelamin,
                            'drive_file_id'      => $uploadInfo['drive_file_id'],
                            'local_path'         => $uploadInfo['local_path'],
                            'drive_link'         => $uploadInfo['drive_link'],
                            'status'             => $uploadInfo['status'],
                        ]);
                        $berkas = $existingDoc;
                    } else {
                        $berkas = Berkas::create([
                            'nama_mhs'           => $validated['nama_mhs'],
                            'nim'                => $validated['nim'],
                            'kategori_berkas_id' => $kategori?->id,
                            'nama_dokumen'       => $docName,
                            'prodi_id'           => $prodiId,
                            'tipe_berkas'        => 'skripsi',
                            'jenis_kelamin'      => $validated['jenis_kelamin'] ?? null,
                            'drive_file_id'      => $uploadInfo['drive_file_id'],
                            'local_path'         => $uploadInfo['local_path'],
                            'drive_link'         => $uploadInfo['drive_link'],
                            'status'             => $uploadInfo['status'],
                        ]);
                    }

                    $berkas->load(['kategoriBerkas', 'prodi']);
                    $savedItems[] = $berkas;
                }

                if (count($savedItems) === 0) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Minimal satu file dokumen berkas skripsi wajib dilampirkan atau discan.',
                        'errors' => ['dokumen' => ['Belum ada file atau hasil scan yang dilampirkan']]
                    ], 422);
                }

                $allUploaded = collect($savedItems)->every(fn($b) => $b->status === 'uploaded');

                return response()->json([
                    'status'  => true,
                    'data'    => $savedItems,
                    'message' => $allUploaded
                        ? count($savedItems) . ' berkas skripsi berhasil disimpan dan terunggah ke Google Drive'
                        : count($savedItems) . ' berkas skripsi berhasil disimpan'
                ], 201);
            }

            // Mode Single Dokumen (Tab 1 Non Skripsi atau single upload)
            if (!$request->hasFile('file') && !$request->filled('file_base64')) {
                return response()->json([
                    'status' => false,
                    'message' => 'File berkas atau hasil scan kamera wajib dilampirkan',
                    'errors' => ['file' => ['File berkas atau hasil scan kamera wajib dilampirkan']]
                ], 422);
            }

            $kategori = !empty($validated['kategori_berkas_id'])
                ? KategoriBerkas::find($validated['kategori_berkas_id'])
                : null;
            $rawKategori = $kategori ? $kategori->nama_kategori : ($tipeBerkas === 'skripsi' ? 'Dokumen Sidang Skripsi' : 'Lain-Lain');
            $cleanKategori = trim(preg_replace('/[\\\\\/:*?"<>|]/', ' ', $rawKategori)) ?: 'Lain-Lain';

            $docName = ($tipeBerkas === 'non_skripsi' && $kategori)
                ? $kategori->nama_kategori
                : ($validated['nama_dokumen'] ?? $cleanKategori);

            $uploadInfo = $this->saveAndUploadSingleFile(
                $tipeBerkas,
                $cleanProdi,
                $tipeBerkas === 'skripsi' ? 'Dokumen Sidang Skripsi' : $cleanKategori,
                $validated['nim'],
                $validated['nama_mhs'],
                $docName,
                $request->file('file'),
                $request->input('file_base64')
            );

            $berkas = Berkas::create([
                'nama_mhs'           => $validated['nama_mhs'],
                'nim'                => $validated['nim'],
                'kategori_berkas_id' => $kategori?->id,
                'nama_dokumen'       => $docName,
                'prodi_id'           => $prodiId,
                'tipe_berkas'        => $tipeBerkas,
                'jenis_kelamin'      => $validated['jenis_kelamin'] ?? null,
                'drive_file_id'      => $uploadInfo['drive_file_id'],
                'local_path'         => $uploadInfo['local_path'],
                'drive_link'         => $uploadInfo['drive_link'],
                'status'             => $uploadInfo['status'],
            ]);

            $berkas->load(['kategoriBerkas', 'prodi']);

            return response()->json([
                'status'  => true,
                'data'    => $berkas,
                'message' => $uploadInfo['status'] === 'uploaded'
                    ? 'Berkas berhasil disimpan dan terunggah ke Google Drive'
                    : 'Berkas berhasil disimpan di server lokal (upload ke Google Drive gagal)'
            ], 201);

        } catch (\Throwable $th) {
            Log::error("Berkas store error: " . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $berkas = Berkas::with(['kategoriBerkas', 'prodi'])->find($id);

        if (!$berkas) {
            return response()->json([
                'status' => false,
                'message' => 'Data berkas tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $berkas,
            'message' => 'Data berkas berhasil diambil'
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info("Berkas update request [{$id}]: ", $request->except(['file_base64', 'file']));

        try {
            $berkas = Berkas::find($id);

            if (!$berkas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data berkas tidak ditemukan'
                ], 404);
            }

            $tipeBerkas = $request->input('tipe_berkas', $berkas->tipe_berkas ?? 'non_skripsi');

            $validator = Validator::make($request->all(), [
                'nama_mhs'           => 'required|string|max:255',
                'nim'                => 'required|string|max:50',
                'kategori_berkas_id' => $tipeBerkas === 'skripsi'
                    ? 'nullable|exists:kategori_berkas,id'
                    : 'required|exists:kategori_berkas,id',
                'nama_dokumen'       => 'nullable|string|max:255',
                'prodi_id'           => 'nullable|exists:prodi,id',
                'jenis_kelamin'      => 'nullable|in:L,P',
                'tipe_berkas'        => 'nullable|in:non_skripsi,skripsi',
                'file'               => 'nullable|file|max:25600',
                'file_base64'        => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            [$prodiId, $cleanProdi] = $this->resolveProdi(
                isset($validated['prodi_id']) ? (int) $validated['prodi_id'] : $berkas->prodi_id
            );

            $kategoriId = $validated['kategori_berkas_id'] ?? $berkas->kategori_berkas_id;
            $kategori = $kategoriId ? KategoriBerkas::find($kategoriId) : null;
            $rawKategori = $kategori ? $kategori->nama_kategori : ($tipeBerkas === 'skripsi' ? 'Dokumen Sidang Skripsi' : 'Lain-Lain');
            $cleanKategori = trim(preg_replace('/[\\\\\/:*?"<>|]/', ' ', $rawKategori)) ?: 'Lain-Lain';
            $docName = ($tipeBerkas === 'non_skripsi' && $kategori)
                ? $kategori->nama_kategori
                : ($validated['nama_dokumen'] ?? $berkas->nama_dokumen ?? $cleanKategori);

            // Cek apakah ada file pengganti baru
            if ($request->hasFile('file') || $request->filled('file_base64')) {
                // Hapus file lama di Google Drive jika ada
                if (!empty($berkas->drive_file_id)) {
                    try {
                        GoogleDrive::deleteFile($berkas->drive_file_id);
                    } catch (\Throwable $e) {
                        Log::warning("Gagal menghapus file lama di Drive: " . $e->getMessage());
                    }
                }

                // Hapus file lama di server lokal jika ada
                if (!empty($berkas->local_path) && File::exists($berkas->local_path)) {
                    @File::delete($berkas->local_path);
                }

                $uploadInfo = $this->saveAndUploadSingleFile(
                    $tipeBerkas,
                    $cleanProdi,
                    $tipeBerkas === 'skripsi' ? 'Dokumen Sidang Skripsi' : $cleanKategori,
                    $validated['nim'],
                    $validated['nama_mhs'],
                    $docName,
                    $request->file('file'),
                    $request->input('file_base64')
                );

                $berkas->local_path    = $uploadInfo['local_path'];
                $berkas->drive_file_id = $uploadInfo['drive_file_id'];
                $berkas->drive_link    = $uploadInfo['drive_link'];
                $berkas->status        = $uploadInfo['status'];
            }

            // Update metadata
            $berkas->nama_mhs           = $validated['nama_mhs'];
            $berkas->nim                = $validated['nim'];
            $berkas->kategori_berkas_id = $kategori?->id;
            $berkas->nama_dokumen       = $docName;
            $berkas->prodi_id           = $prodiId;
            $berkas->tipe_berkas        = $tipeBerkas;
            if (isset($validated['jenis_kelamin'])) {
                $berkas->jenis_kelamin = $validated['jenis_kelamin'];
            }
            $berkas->save();

            $berkas->load(['kategoriBerkas', 'prodi']);

            return response()->json([
                'status'  => true,
                'data'    => $berkas,
                'message' => 'Data berkas berhasil diupdate'
            ]);

        } catch (\Throwable $th) {
            Log::error("Berkas update error: " . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $berkas = Berkas::find($id);

            if (!$berkas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data berkas tidak ditemukan'
                ], 404);
            }

            // Hapus file dari Google Drive
            if (!empty($berkas->drive_file_id)) {
                try {
                    GoogleDrive::deleteFile($berkas->drive_file_id);
                } catch (\Throwable $e) {
                    Log::warning("Gagal menghapus file di Drive: " . $e->getMessage());
                }
            }

            // Hapus file lokal
            if (!empty($berkas->local_path) && File::exists($berkas->local_path)) {
                @File::delete($berkas->local_path);
            }

            $berkas->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data berkas berhasil dihapus'
            ]);

        } catch (\Throwable $th) {
            Log::error("Berkas destroy error: " . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus berkas: ' . $th->getMessage()
            ], 500);
        }
    }
}
