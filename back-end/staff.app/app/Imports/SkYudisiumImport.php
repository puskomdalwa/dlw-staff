<?php

namespace App\Imports;

use App\Models\Fakultas;
use App\Models\LogSurat;
use App\Models\NoSurat;
use App\Models\Prodi;
use App\Models\SkYudisium;
use App\Models\TandaTangan;
use App\Services\SuratService;
use App\Http\Controllers\Api\SkYudisiumController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class SkYudisiumImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private $prodiId;
    private $tanggal;
    private $petandaTangan;
    private $nomorSurat;
    private $userId;
    private $batchId;

    private $successCount = 0;
    private $failedCount = 0;
    private $skippedRows = [];
    private $errors = [];

    public function __construct(
        $prodiId = null,
        $tanggal = null,
        $petandaTangan = 'tidak',
        $nomorSurat = null,
        $userId = null
    ) {
        $this->prodiId = $prodiId ? (int)$prodiId : null;
        $this->tanggal = $tanggal ?: date('Y-m-d');
        $this->petandaTangan = $petandaTangan ?: 'tidak';
        $this->nomorSurat = $nomorSurat ? trim($nomorSurat) : null;
        $this->userId = $userId;
        $this->batchId = 'SKY-' . date('YmdHis') . '-' . Str::random(6);
    }

    public function collection(Collection $rows)
    {
        $controller = new SkYudisiumController();

        // Ambil default prodi model jika ada
        $defaultProdi = $this->prodiId ? Prodi::find($this->prodiId) : null;

        // Siapkan nomor surat seragam untuk seluruh batch yang diimport
        $batchNomorSurat = null;
        if (!empty($this->nomorSurat)) {
            $rawNo = trim($this->nomorSurat);

            // Jika pengguna memasukkan nomor saja (tanpa format slash)
            if (!str_contains($rawNo, '/')) {
                $batchNomorSurat = SuratService::formatNomorSurat('SKY', $rawNo, $this->tanggal, $this->prodiId);

                if (!NoSurat::where('nomor', $rawNo)->exists()) {
                    $nomorObj = new NoSurat();
                    $nomorObj->nomor = $rawNo;
                    $nomorObj->user_id = $this->userId ?? 1;
                    $nomorObj->save();
                }

                $log = new LogSurat();
                $log->nomor = $rawNo;
                $log->nomor_surat = $batchNomorSurat;
                $log->nama_surat = 'SK Yudisium';
                $log->user_id = $this->userId ?? 1;
                $log->save();
            } else {
                // Jika sudah menyertakan format lengkap
                $batchNomorSurat = $rawNo;
            }
        }

        $firstSavedSk = null;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                // Kolom NIM
                $nim = trim((string)($row['nim'] ?? $row['nomor_induk_mahasiswa'] ?? ''));

                // Kolom Nama
                $nama = trim((string)($row['nama'] ?? $row['nama_mhs'] ?? $row['nama_mahasiswa'] ?? ''));

                // Kolom Prodi
                $prodiName = trim((string)($row['prodi'] ?? $row['program_studi'] ?? ''));

                // Kolom Nilai Skripsi
                $nilaiSkripsi = trim((string)($row['nilai_skripsi'] ?? $row['nilai'] ?? $row['skripsi'] ?? ''));

                // Kolom Jenis Kelamin
                $jkRaw = strtoupper(trim((string)($row['jenis_kelamin'] ?? $row['jk'] ?? '')));
                $jenisKelamin = ($jkRaw === 'P' || str_starts_with($jkRaw, 'PEREMPUAN')) ? 'P' : 'L';

                // Skip baris kosong
                if (empty($nim) && empty($nama)) {
                    continue;
                }

                if (empty($nim) || empty($nama)) {
                    $this->failedCount++;
                    $this->skippedRows[] = "Baris {$rowNumber}: NIM atau Nama Mahasiswa kosong";
                    continue;
                }

                // Resolve Prodi
                $targetProdiId = $this->prodiId;
                $targetProdiName = $defaultProdi?->nama ?: $prodiName;

                if (!empty($prodiName)) {
                    $matchedProdi = Prodi::where('nama', 'like', "%{$prodiName}%")
                        ->orWhere('alias', 'like', "%{$prodiName}%")
                        ->first();
                    if ($matchedProdi) {
                        $targetProdiId = $matchedProdi->id;
                        $targetProdiName = $matchedProdi->nama;
                    }
                }

                // Resolve Dekan TTD
                $ttdId = null;
                if ($targetProdiId) {
                    $fakultas = DB::table('fakultas_prodi')
                        ->join('fakultas', 'fakultas.id', '=', 'fakultas_prodi.fakultas_id')
                        ->where('fakultas_prodi.prodi_id', $targetProdiId)
                        ->select('fakultas.*')
                        ->first();

                    if ($fakultas) {
                        $rawTtd = $fakultas->tanda_tangan_id;
                        if (is_array($rawTtd)) {
                            $ttdId = $rawTtd[0] ?? null;
                        } elseif (is_numeric($rawTtd)) {
                            $ttdId = (int)$rawTtd;
                        } elseif (is_string($rawTtd)) {
                            $decoded = json_decode($rawTtd, true);
                            $ttdId = is_array($decoded) ? ($decoded[0] ?? null) : (is_numeric($rawTtd) ? (int)$rawTtd : null);
                        }
                    }
                }

                // Nomor Surat (gunakan nomor surat per baris jika ada, atau nomor surat batch)
                $rowNomorSurat = trim((string)($row['nomor_surat'] ?? $row['no_surat'] ?? ''));
                $finalNomorSurat = !empty($rowNomorSurat) ? $rowNomorSurat : $batchNomorSurat;

                // Buat data SK Yudisium
                $sk = new SkYudisium();
                $sk->batch_id        = $this->batchId;
                $sk->nama_mhs        = $nama;
                $sk->nim             = $nim;
                $sk->prodi           = $targetProdiName ?: ($defaultProdi?->nama ?: 'UMUM');
                $sk->prodi_id        = $targetProdiId;
                $sk->tanda_tangan_id = $ttdId;
                $sk->tanggal         = $this->tanggal;
                $sk->jenis_kelamin   = $jenisKelamin;
                $sk->nomor_surat     = $finalNomorSurat;
                $sk->nilai_skripsi   = $nilaiSkripsi ?: null;
                $sk->petanda_tangan  = $this->petandaTangan;
                $sk->user_id         = $this->userId;
                $sk->status          = 'pending';
                $sk->save();

                if (!$firstSavedSk) {
                    $firstSavedSk = $sk;
                }

                $this->successCount++;
            } catch (\Throwable $e) {
                $this->failedCount++;
                $this->errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                Log::error("SkYudisiumImport row {$rowNumber} error: " . $e->getMessage());
            }
        }

        // Setelah seluruh baris tersimpan, generate 1 PDF utuh untuk seluruh mahasiswa di batch ini
        if ($firstSavedSk && $this->successCount > 0) {
            $controller->generatePdfFile($firstSavedSk->id);

            $prodiName = $defaultProdi?->nama ?: ($firstSavedSk->prodi ?: 'UMUM');
            $nameTable = 'SK Yudisium';
            \App\Jobs\UploudSuratToDrive::dispatch($firstSavedSk->id, $nameTable, $prodiName, SkYudisium::class);
        }
    }

    public function getBatchId(): string
    {
        return $this->batchId;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
