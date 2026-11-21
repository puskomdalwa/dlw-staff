<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class GoogleDrive
{
    /**
     * Set permission file menjadi "anyone with link can view"
     * Agar file bisa diakses melalui link tanpa harus login
     */
    public static function setPublicPermission(string $fileId): bool
    {
        try {
            $client = new \Google_Client();
            $client->setClientId(config('filesystems.disks.google.clientId'));
            $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
            $client->addScope(\Google_Service_Drive::DRIVE);

            // Set credentials menggunakan refresh token
            $client->setAccessType('offline');
            $client->refreshToken(config('filesystems.disks.google.refreshToken'));

            $service = new \Google_Service_Drive($client);

            // Buat permission "anyone with link can view"
            $permission = new \Google_Service_Drive_Permission();
            $permission->setType('anyone');
            $permission->setRole('reader');

            $service->permissions->create($fileId, $permission);

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to set public permission for file {$fileId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus file dari Google Drive berdasarkan fileId
     */
    public static function deleteFile(string $fileId): bool
    {
        try {
            $client = new \Google_Client();
            $client->setClientId(config('filesystems.disks.google.clientId'));
            $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
            $client->addScope(\Google_Service_Drive::DRIVE);

            $client->setAccessType('offline');
            $client->refreshToken(config('filesystems.disks.google.refreshToken'));

            $service = new \Google_Service_Drive($client);
            $service->files->delete($fileId);

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to delete file {$fileId} from Drive: " . $e->getMessage());
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
                    \Illuminate\Support\Facades\Log::warning("List contents failed for {$parentLocation}: " . $listEx->getMessage());
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
            \Illuminate\Support\Facades\Log::error("Failed to ensure folder {$path} exists: " . $e->getMessage());
            return $cleanPath;
        }
    }

    /**
     * Upload file ke Google Drive dan dapatkan file ID
     * 
     * @return array{path: string, file_id: string|null}
     */
    public static function uploadFile(string $folderPath, string $localFilePath, string $fileName): array
    {
        $resolvedFolder = trim($folderPath, '/');
        $fullPath = ($resolvedFolder !== '' ? $resolvedFolder . '/' : '') . $fileName;

        // Upload file menggunakan Laravel Storage disk 'google' (Flysystem)
        $disk = Storage::disk('google');
        $stream = fopen($localFilePath, 'r');
        $disk->put($fullPath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        // Cari file yang baru diupload untuk mendapatkan ID
        $fileId = null;
        try {
            $contents = $disk->listContents($resolvedFolder !== '' ? $resolvedFolder : '/');
            foreach ($contents as $item) {
                if (($item['type'] ?? '') === 'file' && ($item['path'] === $fullPath || basename($item['path']) === $fileName)) {
                    $fileId = $item['extraMetadata']['id'] ?? null;
                    break;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to fetch file ID for {$fullPath}: " . $e->getMessage());
        }

        return [
            'path' => $fullPath,
            'file_id' => $fileId
        ];
    }
}
