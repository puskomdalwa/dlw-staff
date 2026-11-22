<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleDrive;

class UploudSuratToDrive implements ShouldQueue
{
    use Queueable;

    public $tries = 5;
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $id,
        public string $nameTable,
        public ?string $prodiName,
        public string $modelClass,
        public ?string $customFolderPath = null,
        public bool $isSignedSk6 = false
    ) {
        $this->prodiName = $prodiName ?? 'UMUM';
    }

    /**
     * Execute the job.
     */
    // public function handle(): void
    // {
    //     Log::info("Handling upload for {$this->nameTable}, ID: {$this->id}");

    //     $model = $this->modelClass;
    //     $file = $model::find($this->id);

    //     if (!$file) {
    //         Log::error("File record not found for model {$this->modelClass} with ID {$this->id}");
    //         return;
    //     }

    //     try {
    //         $filePath = $file->local_path;
    //         if (!file_exists($filePath)) {
    //             Log::error("Local file does not exist at: {$filePath}");
    //             return;
    //         }

    //         $fileName = basename($filePath);
    //         $root = config('filesystems.disks.google.folder');

    //         $prodiFolder = GoogleDrive::getOrCreateDriveFolder(
    //             "{$this->prodiName}",
    //             $root
    //         );

    //         $suratFolder = GoogleDrive::getOrCreateDriveFolder(
    //             "{$this->nameTable}",
    //             $prodiFolder
    //         );

    //         $fileId = Storage::disk('google')->put(
    //             "{$suratFolder}/{$fileName}",
    //             fopen($filePath, 'r')
    //         );

    //         Storage::disk('google')->setVisibility($fileId, 'public');

    //         $file->update([
    //             'drive_file_id' => $fileId,
    //             'drive_link' => "https://drive.google.com/file/d/{$fileId}/view",
    //             'status' => 'uploaded'
    //         ]);

    //         Log::info("Successfully uploaded {$fileName} to Drive");
    //     } catch (\Throwable $e) {
    //         $file->update(['status' => 'failed']);
    //         Log::error("Upload failed for {$this->nameTable}: " . $e->getMessage());
    //         throw $e;
    //     }
    // }

    public function handle(): void
    {
        Log::info("Handling upload for {$this->nameTable}, ID: {$this->id}");

        $model = $this->modelClass;
        $file = $model::find($this->id);

        if (!$file) {
            Log::error("File record not found for model {$this->modelClass} with ID {$this->id}");
            return;
        }

        try {
            $filePath = $this->isSignedSk6 ? $file->signed_local_path : $file->local_path;
            if (empty($filePath) || !file_exists($filePath)) {
                Log::error("Local file does not exist at: {$filePath}");
                return;
            }

            $fileName = basename($filePath);

            // 1. KHUSUS Dokumen Berkas Sidang Skripsi: {prodi}/Dokumen Sidang Skripsi/{NIM}
            if ($this->modelClass === \App\Models\DokumenSkripsi::class || $file instanceof \App\Models\DokumenSkripsi) {
                $daftarSidang = $file->daftarSidangSkripsi ?? \App\Models\DaftarSidangSkripsi::find($file->daftar_sidang_skripsi_id);
                $prodi = $daftarSidang && !empty($daftarSidang->prodi) ? trim($daftarSidang->prodi) : ($this->prodiName ?: 'UMUM');
                $cleanProdi = preg_replace('/[\\\\\/:*?"<>|]/', ' ', $prodi);
                $cleanProdi = trim(preg_replace('/\s+/', ' ', $cleanProdi)) ?: 'UMUM';

                $nim = $daftarSidang ? trim($daftarSidang->nim) : 'UNKNOWN';
                $cleanNim = preg_replace('/[^a-zA-Z0-9_\-]/', '', $nim) ?: $nim;
                $folderPath = "{$cleanProdi}/Dokumen Sidang Skripsi/{$cleanNim}";

                GoogleDrive::ensureFolder($cleanProdi);
                GoogleDrive::ensureFolder("{$cleanProdi}/Dokumen Sidang Skripsi");
                GoogleDrive::ensureFolder($folderPath);
            }
            // 2. Custom folder khusus jika secara eksplisit dipassing
            elseif (!empty($this->customFolderPath)) {
                $folderPath = trim($this->customFolderPath, '/');
                $parts = explode('/', $folderPath);
                $curr = '';
                foreach ($parts as $p) {
                    $curr = $curr ? "{$curr}/{$p}" : $p;
                    GoogleDrive::ensureFolder($curr);
                }
            }
            // 3. SEMUA SURAT DAN DOKUMEN LAINNYA: Tetap menggunakan format bawaan {ProdiName}/{NamaSurat}
            else {
                $folderPath = "{$this->prodiName}/{$this->nameTable}";

                // Pastikan folder prodi ada
                GoogleDrive::ensureFolder($this->prodiName);

                // Pastikan folder surat ada di dalam folder prodi
                GoogleDrive::ensureFolder($folderPath);
            }

            // Upload file menggunakan Gdrive
            $result = GoogleDrive::uploadFile($folderPath, $filePath, $fileName);

            if ($this->isSignedSk6) {
                $signedUpdate = [];
                if (!empty($result['file_id'])) {
                    GoogleDrive::setPublicPermission($result['file_id']);
                    $signedUpdate['signed_drive_file_id'] = $result['file_id'];
                    $signedUpdate['signed_drive_link'] = "https://drive.google.com/file/d/{$result['file_id']}/view";
                }
                if (!empty($signedUpdate)) {
                    $file->update($signedUpdate);
                }
            } else {
                $updateData = [
                    // 'drive_path' => $result['path'],
                    'status' => 'uploaded'
                ];

                // Jika file ID berhasil didapat, simpan juga drive_file_id dan drive_link
                if (!empty($result['file_id'])) {
                    // Set permission agar file bisa diakses oleh siapa saja dengan link
                    GoogleDrive::setPublicPermission($result['file_id']);

                    $updateData['drive_file_id'] = $result['file_id'];
                    $updateData['drive_link'] = "https://drive.google.com/file/d/{$result['file_id']}/view";
                }

                $file->update($updateData);

                if (
                    ($this->modelClass === \App\Models\DokumenSkripsi::class || $file instanceof \App\Models\DokumenSkripsi)
                    && !empty($updateData['drive_link'])
                ) {
                    $daftarSidang = $file->daftarSidangSkripsi ?? \App\Models\DaftarSidangSkripsi::find($file->daftar_sidang_skripsi_id);
                    if ($daftarSidang && \App\Services\GoogleSheetSidangHkiService::isHki($daftarSidang)) {
                        try {
                            SyncDaftarSidangHkiToGoogleSheet::dispatch($daftarSidang->id, 'update');
                        } catch (\Throwable $sheetEx) {
                            Log::warning('Dispatch sync Google Sheet Sidang HKI setelah upload Drive gagal: ' . $sheetEx->getMessage());
                        }
                    }
                }
            }

            Log::info("Successfully uploaded {$fileName} to Drive at path: {$result['path']}" .
                (!empty($result['file_id']) ? ", file_id: {$result['file_id']}" : ""));
        } catch (\Throwable $e) {
            $file->update(['status' => 'failed']);
            Log::error("Upload failed: " . $e->getMessage());
            throw $e;
        }
    }
}
