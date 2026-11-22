<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDrive
{
    /**
     * Inisialisasi Google Drive API client menggunakan refresh token dari config.
     */
    protected static function createDriveService(): \Google_Service_Drive
    {
        $client = new \Google_Client();
        $client->setClientId(config('filesystems.disks.google.clientId'));
        $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
        $client->addScope(\Google_Service_Drive::DRIVE);
        $client->setAccessType('offline');
        $client->refreshToken(config('filesystems.disks.google.refreshToken'));

        return new \Google_Service_Drive($client);
    }

    /**
     * Deteksi MIME type asli file berdasarkan magic bytes dan ekstensi nama file.
     */
    public static function detectMimeType(string $localFilePath, string $fileName): string
    {
        if (is_file($localFilePath) && is_readable($localFilePath)) {
            $fh = @fopen($localFilePath, 'rb');
            if ($fh) {
                $header = fread($fh, 16) ?: '';
                fclose($fh);

                if (str_starts_with($header, "\xFF\xD8\xFF")) {
                    return 'image/jpeg';
                }
                if (str_starts_with($header, "\x89PNG\r\n\x1A\n")) {
                    return 'image/png';
                }
                if (str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a')) {
                    return 'image/gif';
                }
                if (strlen($header) >= 12 && substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP') {
                    return 'image/webp';
                }
                if (str_starts_with($header, '%PDF-')) {
                    return 'application/pdf';
                }
            }
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION) ?: pathinfo($localFilePath, PATHINFO_EXTENSION));
        $extMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpe'  => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        if (isset($extMap[$ext])) {
            return $extMap[$ext];
        }

        if (function_exists('mime_content_type') && is_file($localFilePath)) {
            $detected = @mime_content_type($localFilePath);
            if (!empty($detected) && $detected !== 'application/octet-stream') {
                return $detected;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Set permission file menjadi "anyone with link can view"
     * Agar file bisa diakses melalui link tanpa harus login
     */
    public static function setPublicPermission(string $fileId): bool
    {
        try {
            $service = self::createDriveService();

            $permission = new \Google_Service_Drive_Permission();
            $permission->setType('anyone');
            $permission->setRole('reader');

            $service->permissions->create($fileId, $permission);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to set public permission for file {$fileId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus file dari Google Drive berdasarkan fileId
     */
    public static function deleteFile(string $fileId): bool
    {
        try {
            $service = self::createDriveService();
            $service->files->delete($fileId);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete file {$fileId} from Drive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memastikan folder ada di Google Drive (pengecekan case-insensitive per segmen).
     * Jika folder dengan nama yang sama sudah ada, tidak membuat folder duplikat
     * dan langsung mengembalikan path folder yang sudah ada.
     */
    public static function ensureFolder(string $path): string
    {
        $cleanPath = trim($path, '/');
        if ($cleanPath === '') {
            return '/';
        }

        try {
            $disk = Storage::disk('google');
            $segments = array_values(array_filter(explode('/', $cleanPath), fn($s) => trim($s) !== ''));
            $currentPath = '';

            foreach ($segments as $segment) {
                $targetLower = mb_strtolower(trim($segment));
                $parentLocation = $currentPath === '' ? '/' : $currentPath;
                $foundPath = null;

                try {
                    $contents = $disk->listContents($parentLocation);
                    foreach ($contents as $item) {
                        if (($item['type'] ?? '') === 'dir') {
                            $itemBaseName = basename($item['path']);
                            if (mb_strtolower(trim($itemBaseName)) === $targetLower) {
                                $foundPath = $item['path'];
                                break;
                            }
                        }
                    }
                } catch (\Exception $listEx) {
                    Log::warning("List contents failed for {$parentLocation}: " . $listEx->getMessage());
                }

                if ($foundPath !== null) {
                    $currentPath = $foundPath;
                } else {
                    $newPath = $currentPath === '' ? trim($segment) : "{$currentPath}/" . trim($segment);
                    if (!$disk->exists($newPath)) {
                        $disk->makeDirectory($newPath);
                    }
                    $currentPath = $newPath;
                }
            }

            return $currentPath ?: $cleanPath;
        } catch (\Exception $e) {
            Log::error("Failed to ensure folder {$path} exists: " . $e->getMessage());
            return $cleanPath;
        }
    }

    /**
     * Upload file ke Google Drive secara binary-safe dengan MIME type eksplisit dan dapatkan file ID.
     *
     * @return array{path: string, file_id: string|null}
     */
    public static function uploadFile(string $folderPath, string $localFilePath, string $fileName): array
    {
        $resolvedFolder = trim($folderPath, '/');
        $fullPath = ($resolvedFolder !== '' ? $resolvedFolder . '/' : '') . $fileName;
        $mimeType = self::detectMimeType($localFilePath, $fileName);

        // Baca konten biner secara utuh agar stream pointer & CRLF tidak merusak header file
        $binaryData = @file_get_contents($localFilePath);

        $disk = Storage::disk('google');
        if ($binaryData !== false) {
            $disk->put($fullPath, $binaryData, ['mimetype' => $mimeType]);
        } else {
            $stream = fopen($localFilePath, 'rb');
            $disk->put($fullPath, $stream, ['mimetype' => $mimeType]);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        // Cari file yang baru diupload untuk mendapatkan ID dan verifikasi MIME type di Google Drive
        $fileId = null;
        $driveMime = null;
        try {
            $contents = $disk->listContents($resolvedFolder !== '' ? $resolvedFolder : '/');
            foreach ($contents as $item) {
                if (($item['type'] ?? '') === 'file' && ($item['path'] === $fullPath || basename($item['path']) === $fileName)) {
                    $fileId = $item['extraMetadata']['id'] ?? ($item['extra_metadata']['id'] ?? null);
                    $driveMime = $item['mime_type']
                        ?? $item['mimeType']
                        ?? ($item['extraMetadata']['mimeType'] ?? ($item['extra_metadata']['mimeType'] ?? null));
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch file ID for {$fullPath}: " . $e->getMessage());
        }

        // Jika adapter Drive menyimpan MIME type berbeda (mis. text/plain untuk gambar/PDF), perbaiki langsung via Drive API
        if ($fileId && $mimeType !== 'application/octet-stream' && $binaryData !== false) {
            try {
                $service = self::createDriveService();
                if ($driveMime === null) {
                    $driveMime = $service->files->get($fileId, ['fields' => 'mimeType'])->getMimeType();
                }
                if ($driveMime !== $mimeType) {
                    $driveFile = new \Google_Service_Drive_DriveFile();
                    $driveFile->setMimeType($mimeType);
                    $service->files->update($fileId, $driveFile, [
                        'data'       => $binaryData,
                        'mimeType'   => $mimeType,
                        'uploadType' => 'multipart',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to enforce MIME type {$mimeType} for Drive file {$fileId}: " . $e->getMessage());
            }
        }

        return [
            'path' => $fullPath,
            'file_id' => $fileId,
        ];
    }
}
