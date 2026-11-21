<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileManagerService;
use App\Actions\DownloadProdiZipAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FileManagerController extends Controller
{
    protected $fileManagerService;
    protected $downloadProdiZipAction;

    /**
     * Menggunakan Dependency Injection untuk memasukkan Service dan Action Class.
     * Menerapkan prinsip SOLID secara penuh.
     */
    public function __construct(
        FileManagerService $fileManagerService,
        DownloadProdiZipAction $downloadProdiZipAction
    ) {
        $this->fileManagerService = $fileManagerService;
        $this->downloadProdiZipAction = $downloadProdiZipAction;
    }

    /**
     * Endpoint API untuk melihat isi folder secara rekursif dan dinamis.
     * Terintegrasi dengan otorisasi Program Studi user yang sedang login.
     *
     * GET /api/file-manager/list
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $basePath = base_path('../public_html/pdf');

            // Pastikan folder default untuk tiap prodi (Dokumen Sidang Skripsi & PengajuanJudulController) selalu siap
            $this->ensureDefaultFolders($basePath);

            // Cek Otorisasi Role / Prodi:
            // Jika user BAAK: hanya melihat folder BAAK
            // Jika user Prodi/Staff: hanya melihat folder Prodi miliknya
            // Jika user Admin: dapat melihat seluruh direktori
            $userRootPath = $this->getUserRootPath($user, $basePath);

            // Pastikan direktori root user tersedia di penyimpanan lokal
            if (!File::exists($userRootPath)) {
                File::makeDirectory($userRootPath, 0755, true);
            }

            $subPath = $request->query('path', '');
            
            // Panggil logika service untuk mendapatkan file dan folder
            $contents = $this->fileManagerService->listContents($userRootPath, $subPath);

            return response()->json([
                'status' => true,
                'data' => [
                    'current_path' => str_replace('\\', '/', $subPath),
                    'folders' => $contents['folders'],
                    'files' => $contents['files'],
                    'user_prodi' => $user->prodi ? $user->prodi->nama : ($user->level ? strtoupper($user->level->nama) : ($user->level_id == 3 ? 'BAAK' : null))
                ],
                'message' => 'Daftar berkas berhasil diambil.'
            ]);
        } catch (\Throwable $th) {
            Log::error('FileManager Error: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 400);
        }
    }

    /**
     * Endpoint API untuk mengunduh seluruh isi folder tertentu dalam bentuk berkas ZIP.
     *
     * GET /api/file-manager/download-zip
     */
    public function downloadZip(Request $request)
    {
        try {
            $user = Auth::user();
            $basePath = base_path('../public_html/pdf');

            // Terapkan batas otorisasi path yang sama dengan fungsi index
            $userRootPath = $this->getUserRootPath($user, $basePath);

            $subPath = $request->query('path', '');

            // Validasi Keamanan: realpath mencegah Directory Traversal
            $realRoot = realpath($userRootPath);
            if (!$realRoot) {
                return response()->json([
                    'status' => false,
                    'message' => 'Direktori utama tidak ditemukan.'
                ], 404);
            }

            $targetPath = realpath($realRoot . '/' . $subPath);

            // Pastikan targetPath valid, berada di dalam realRoot, dan merupakan direktori
            if (!$targetPath || strpos($targetPath, $realRoot) !== 0 || !is_dir($targetPath)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak atau direktori tidak ditemukan.'
                ], 403);
            }

            // Panggil Action Class untuk mengompresi folder
            $zipPath = $this->downloadProdiZipAction->execute($targetPath);

            // Mengambil nama folder untuk dijadikan nama file ZIP
            $folderName = basename($targetPath);
            $downloadName = $folderName . '.zip';

            // Kirim respon download dan hapus file temporer otomatis setelah dikirim
            return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);

        } catch (\Throwable $th) {
            Log::error('FileManager ZIP Error: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengunduh ZIP: ' . $th->getMessage()
            ], 400);
        }
    }

    /**
     * Menentukan direktori utama yang diizinkan untuk diakses user berdasarkan role / prodi.
     * - Role BAAK: Hanya folder BAAK (public_html/pdf/BAAK)
     * - Role Staff (memiliki prodi_id): Hanya folder prodi miliknya (public_html/pdf/{prodi})
     * - Role Admin: Seluruh folder (public_html/pdf)
     */
    protected function getUserRootPath($user, string $basePath): string
    {
        $levelNama = '';
        if ($user->relationLoaded('level') && $user->level) {
            $levelNama = strtolower($user->level->nama);
        } elseif ($user->level) {
            $levelNama = strtolower($user->level->nama);
        } elseif ($user->level_id == 3) {
            $levelNama = 'baak';
        }

        if ($levelNama === 'baak') {
            return $basePath . '/BAAK';
        }

        if ($user->prodi_id && $user->prodi) {
            return $basePath . '/' . $user->prodi->nama;
        }

        return $basePath;
    }

    /**
     * Memastikan folder direktori untuk setiap prodi dan subfolder utamanya
     * (Dokumen Sidang Skripsi & PengajuanJudulController) selalu tersedia di direktori fisik server.
     */
    protected function ensureDefaultFolders(string $basePath): void
    {
        if (!File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        try {
            $prodis = \App\Models\Prodi::where('aktif', 'Y')->get();

            foreach ($prodis as $prodi) {
                $rawProdi = trim($prodi->nama);
                $cleanProdi = preg_replace('/[\\\\\/:*?"<>|]/', ' ', $rawProdi);
                $cleanProdi = trim(preg_replace('/\s+/', ' ', $cleanProdi));
                if (!$cleanProdi) continue;

                $prodiPath = "{$basePath}/{$cleanProdi}";
                if (!File::isDirectory($prodiPath)) {
                    File::makeDirectory($prodiPath, 0755, true);
                }

                // Folder Dokumen Sidang Skripsi
                $sidangPath = "{$prodiPath}/Dokumen Sidang Skripsi";
                if (!File::isDirectory($sidangPath)) {
                    File::makeDirectory($sidangPath, 0755, true);
                }

                // Folder Pengajuan Judul (PengajuanJudulController)
                $judulPath = "{$prodiPath}/PengajuanJudulController";
                if (!File::isDirectory($judulPath)) {
                    File::makeDirectory($judulPath, 0755, true);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Error ensureDefaultFolders: ' . $e->getMessage());
        }
    }
}
