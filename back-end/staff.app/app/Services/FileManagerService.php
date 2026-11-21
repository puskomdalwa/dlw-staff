<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class FileManagerService
{
    /**
     * Pemetaan nama folder / controller ke kelas Model Eloquent masing-masing.
     * Digunakan untuk mencocokkan berkas fisik ke database demi mengambil metadata (misal: link Drive).
     */
    private $modelMap = [
        'Sk6Controller' => \App\Models\SuratKeterangan6::class,
        'SuratIzinPenelitianController' => \App\Models\SuratIzinPenelitian::class,
        'SuratKeteranganAdministrasiKeuanganController' => \App\Models\SuratKeteranganAdministrasiKeuangan::class,
        'SuratKeteranganAktifMahasiswaController' => \App\Models\SuratKeteranganAktifMahasiswa::class,
        'SuratKeteranganController' => \App\Models\SuratKeterangan::class,
        'SuratKeteranganDaftarS2Controller' => \App\Models\SuratKeteranganDaftarS2::class,
        'SuratKeteranganKknController' => \App\Models\SuratKeteranganKkn::class,
        'SuratKeteranganLulusMataKuliahController' => \App\Models\SuratKeteranganLulusMataKuliah::class,
        'SuratKeteranganPplController' => \App\Models\SuratKeteranganPpl::class,
        'SuratKeteranganQismulAmanController' => \App\Models\SuratKeteranganQismulAman::class,
        'SuratKeteranganSpmController' => \App\Models\SuratKeteranganSpm::class,
        'SuratKeteranganCutiController' => \App\Models\SuratKeteranganCuti::class,
        'SuratKeteranganTasmaKknPplController' => \App\Models\SuratKeteranganTasmaKknPpl::class,
        'SuratKeteranganTransferController' => \App\Models\SuratKeteranganTransfer::class,
        'SuratKeteranganUjianKomprehensifDiniyahController' => \App\Models\SuratKeteranganUjianKomprehensifDiniyah::class,
        'SuratPernyataanVerifikasiNilaiController' => \App\Models\SuratPernyataanVerifikasiNilai::class,
        'SuratTugasController' => \App\Models\SuratTugas::class,
        'Pengumuman Akademik' => \App\Models\PengumumanAkademik::class,
        'PengumumanAkademik' => \App\Models\PengumumanAkademik::class,
        'PengumumanAkademikController' => \App\Models\PengumumanAkademik::class,
        'PengajuanJudulController' => \App\Models\PengajuanJudul::class,
        'PengajuanJudul' => \App\Models\PengajuanJudul::class,
        'Pengajuan Judul' => \App\Models\PengajuanJudul::class,
        'SkYudisiumController' => \App\Models\SkYudisium::class,
        'SkYudisium' => \App\Models\SkYudisium::class,
        'FormPengambilanIjazahController' => \App\Models\FormPengambilanIjazah::class,
        'Form Pengambilan Ijazah' => \App\Models\FormPengambilanIjazah::class,
        'DokumenSkripsi' => \App\Models\DokumenSkripsi::class,
        'Dokumen Sidang Skripsi' => \App\Models\DokumenSkripsi::class,
    ];

    /**
     * Format nama folder yang ramah dibaca untuk antarmuka pengguna (UI).
     */
    public function getFolderDisplayName(string $name): string
    {
        $displayNames = [
            'PengajuanJudulController' => 'Pengajuan Judul',
            'PengajuanJudul' => 'Pengajuan Judul',
            'Sk6Controller' => 'SK 6',
            'SkYudisiumController' => 'SK Yudisium',
            'FormPengambilanIjazahController' => 'Form Pengambilan Ijazah',
            'SuratIzinPenelitianController' => 'Surat Izin Penelitian',
            'SuratKeteranganAdministrasiKeuanganController' => 'Surat Keterangan Administrasi Keuangan',
            'SuratKeteranganAktifMahasiswaController' => 'Surat Keterangan Aktif Mahasiswa',
            'SuratKeteranganDaftarS2Controller' => 'Surat Keterangan Daftar S2',
            'SuratKeteranganKknController' => 'Surat Keterangan KKN',
            'SuratKeteranganLulusMataKuliahController' => 'Surat Keterangan Lulus Mata Kuliah',
            'SuratKeteranganPplController' => 'Surat Keterangan PPL',
            'SuratKeteranganQismulAmanController' => 'Surat Keterangan Qismul Aman',
            'SuratKeteranganSpmController' => 'Surat Keterangan SPM',
            'SuratKeteranganCutiController' => 'Surat Keterangan Cuti',
            'SuratKeteranganTasmaKknPplController' => 'Surat Keterangan TASMA KKN PPL',
            'SuratKeteranganTransferController' => 'Surat Keterangan Transfer',
            'SuratKeteranganUjianKomprehensifDiniyahController' => 'Surat Keterangan Ujian Komprehensif Diniyah',
            'SuratPernyataanVerifikasiNilaiController' => 'Surat Pernyataan Verifikasi Nilai',
            'SuratTugasController' => 'Surat Tugas',
            'PengumumanAkademikController' => 'Pengumuman Akademik',
            'Dokumen Sidang Skripsi' => 'Dokumen Sidang Skripsi',
        ];

        return $displayNames[$name] ?? $name;
    }

    /**
     * Mengambil daftar isi (file & folder) dari direktori secara aman.
     *
     * @param string $userRootPath Path absolut direktori utama yang diperbolehkan untuk user.
     * @param string $subPath Path relatif subdirektori yang diminta.
     * @return array
     */
    public function listContents(string $userRootPath, string $subPath): array
    {
        $realRoot = realpath($userRootPath);
        if (!$realRoot) {
            throw new RuntimeException("Direktori root tidak ditemukan.");
        }

        // Menggabungkan path root dengan subpath dan menyelesaikan path absolut
        $targetPath = realpath($realRoot . '/' . $subPath);

        // Validasi keamanan: Mencegah celah Directory Traversal
        if (!$targetPath || strpos($targetPath, $realRoot) !== 0) {
            throw new RuntimeException("Akses ditolak: Percobaan Directory Traversal.");
        }

        $directories = [];
        $files = [];

        // 1. Scan folder dari penyimpanan lokal
        $dirs = File::directories($targetPath);
        foreach ($dirs as $dir) {
            $name = basename($dir);
            // Dapatkan path relatif dari realRoot
            $relPath = substr($dir, strlen($realRoot) + 1);
            $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);

            $directories[] = [
                'name'         => $name,
                'display_name' => $this->getFolderDisplayName($name),
                'type'         => 'folder',
                'path'         => $relPath,
            ];
        }

        // Sinkronisasi otomatis folder dari Google Drive jika sedang membuka Dokumen Sidang Skripsi
        $normalizedSubPath = str_replace('\\', '/', $subPath);
        if (strpos($normalizedSubPath, 'Dokumen Sidang Skripsi') !== false || basename($targetPath) === 'Dokumen Sidang Skripsi') {
            try {
                $prodiName = basename($realRoot);
                $driveSearchPath = $normalizedSubPath 
                    ? (strpos($normalizedSubPath, $prodiName) !== false ? $normalizedSubPath : "{$prodiName}/{$normalizedSubPath}")
                    : "{$prodiName}/Dokumen Sidang Skripsi";

                if (\Illuminate\Support\Facades\Storage::disk('google')->directoryExists($driveSearchPath)) {
                    $driveItems = \Illuminate\Support\Facades\Storage::disk('google')->listContents($driveSearchPath, false)->toArray();
                    $existingNames = array_column($directories, 'name');

                    foreach ($driveItems as $dItem) {
                        if ($dItem->type() === 'dir') {
                            $dirName = basename($dItem->path());
                            if (!in_array($dirName, $existingNames)) {
                                $localSub = "{$targetPath}/{$dirName}";
                                if (!File::isDirectory($localSub)) {
                                    File::makeDirectory($localSub, 0755, true);
                                }
                                $relSubPath = ($normalizedSubPath ? "{$normalizedSubPath}/{$dirName}" : $dirName);
                                $directories[] = [
                                    'name'         => $dirName,
                                    'display_name' => $dirName,
                                    'type'         => 'folder',
                                    'path'         => $relSubPath,
                                ];
                                $existingNames[] = $dirName;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 2. Scan file (Mendukung PDF, Word, Excel, Gambar, ZIP/RAR dokumen skripsi)
        $parts = explode('/', str_replace('\\', '/', $subPath));
        $controllerName = end($parts);
        $isControllerFolder = isset($this->modelMap[$controllerName]);

        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp', 'zip', 'rar'];
        $allFiles = File::files($targetPath);

        foreach ($allFiles as $file) {
            $name = $file->getFilename();
            $ext = strtolower($file->getExtension());

            // Filter ekstensi berkas yang didukung
            if (!in_array($ext, $allowedExtensions)) {
                continue;
            }

            $filePath = $file->getRealPath();

            // Dapatkan informasi record dari database dan status keaktifan file
            $recordInfo = $this->getFileRecordInfo($filePath);

            // Jika berada di folder controller terdaftar (misal Sk6, SuratKeterangan), filter file fisik usang (orphaned)
            if ($isControllerFolder) {
                if (!$recordInfo || !$recordInfo['is_active']) {
                    continue;
                }
            }

            $relPath = substr($filePath, strlen($realRoot) + 1);
            $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);

            // Konstruksi URL Publik untuk file fisik lokal
            $pdfRoot = realpath(base_path('../public_html/pdf'));
            $publicRelPath = substr($filePath, strlen($pdfRoot) + 1);
            $publicRelPath = str_replace(DIRECTORY_SEPARATOR, '/', $publicRelPath);

            // Encode masing-masing bagian path agar spasi dan karakter khusus aman diakses browser
            $encodedSegments = array_map('rawurlencode', explode('/', $publicRelPath));
            $publicUrl = url('pdf/' . implode('/', $encodedSegments));

            $driveLink = $recordInfo ? $recordInfo['drive_link'] : null;

            $files[] = [
                'name'       => $name,
                'type'       => 'file',
                'extension'  => $ext,
                'path'       => $relPath,
                'size'       => $this->formatBytes($file->getSize()),
                'url'        => $publicUrl,
                'drive_link' => $driveLink ?: null,
            ];
        }

        return [
            'folders' => $directories,
            'files'   => $files,
        ];
    }

    /**
     * Mendapatkan informasi record database terkait file fisik dan mengecek keaktifannya.
     *
     * @param string $absoluteFilePath Path absolut file fisik.
     * @return array|null
     */
    private function getFileRecordInfo(string $absoluteFilePath): ?array
    {
        $filePath = str_replace('\\', '/', $absoluteFilePath);
        $parts = explode('/', $filePath);

        if (count($parts) < 3) {
            return null;
        }

        $fileName = end($parts);

        // 1. Cek jika berkas merupakan Dokumen Berkas Sidang Skripsi (DokumenSkripsi atau Berkas)
        if (strpos($filePath, 'Dokumen Sidang Skripsi') !== false) {
            try {
                $record = \App\Models\DokumenSkripsi::where('local_path', 'like', '%' . $fileName)->first()
                    ?: \App\Models\Berkas::where('local_path', 'like', '%' . $fileName)->first();
                if ($record) {
                    $activeFileName = basename(str_replace('\\', '/', $record->local_path));
                    return [
                        'drive_link' => $record->drive_link,
                        'is_active'  => ($activeFileName === $fileName),
                    ];
                }
            } catch (\Throwable $e) {}
        }

        // 2. Cek apakah folder parent cocok dengan salah satu model terdaftar
        $controllerName = $parts[count($parts) - 2];
        if (isset($this->modelMap[$controllerName])) {
            $modelClass = $this->modelMap[$controllerName];
            try {
                // Cari record berdasarkan kesamaan nama file di kolom local_path
                $record = $modelClass::where('local_path', 'like', '%' . $fileName)->first();
                if ($record) {
                    $isActive = false;
                    if ($record->local_path) {
                        $activeFileName = basename(str_replace('\\', '/', $record->local_path));
                        $isActive = ($activeFileName === $fileName);
                    }

                    return [
                        'drive_link' => $record->drive_link,
                        'is_active'  => $isActive,
                    ];
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        // 3. Fallback: Cek di PengajuanJudul atau Berkas
        try {
            $record = \App\Models\PengajuanJudul::where('local_path', 'like', '%' . $fileName)->first()
                ?: \App\Models\Berkas::where('local_path', 'like', '%' . $fileName)->first();
            if ($record) {
                $activeFileName = basename(str_replace('\\', '/', $record->local_path));
                return [
                    'drive_link' => $record->drive_link,
                    'is_active'  => ($activeFileName === $fileName),
                ];
            }
        } catch (\Throwable $e) {}

        return null;
    }

    /**
     * Memformat ukuran file bytes ke ukuran manusiawi.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
