<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class GoogleSheetEsyService
{
    /**
     * Spreadsheet ID dari link:
     * https://docs.google.com/spreadsheets/d/1vuguHKsYJkAExb9NtaHnySHnpZ-A1CO5/edit?gid=2130003647#gid=2130003647
     */
    public static string $spreadsheetId = '1vuguHKsYJkAExb9NtaHnySHnpZ-A1CO5';
    public static int $gid = 2130003647;

    /**
     * Dapatkan Spreadsheet ID (dari .env jika ada, atau default)
     */
    public static function getSpreadsheetId(): string
    {
        return env('GOOGLE_SHEET_ESY_ID') ?: self::$spreadsheetId;
    }

    /**
     * Inisialisasi Google Client menggunakan kredensial yang ada di .env
     */
    protected static function getClient(): Client
    {
        $client = new Client();
        $client->setClientId(config('filesystems.disks.google.clientId'));
        $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
        $client->refreshToken(config('filesystems.disks.google.refreshToken'));
        $client->addScope(Sheets::SPREADSHEETS);
        $client->addScope('https://www.googleapis.com/auth/drive');
        return $client;
    }

    /**
     * Mengecek apakah data (PengajuanJudul / SuratTugas) merupakan Prodi Ekonomi Syariah
     */
    public static function isEkonomiSyariah($obj): bool
    {
        if (!$obj) {
            return false;
        }

        // Cek ID prodi (3 = Ekonomi Syariah)
        if (!empty($obj->prodi_id) && (int)$obj->prodi_id === 3) {
            return true;
        }

        // Cek teks string prodi
        $prodi = strtolower(trim((string)($obj->prodi ?? '')));
        if ($prodi === 'esy' || str_contains($prodi, 'ekonomi syariah')) {
            return true;
        }

        // Cek relasi jika ada
        if (method_exists($obj, 'prodi') && $obj->prodi) {
            $alias = strtolower(trim((string)($obj->prodi->alias ?? '')));
            $nama = strtolower(trim((string)($obj->prodi->nama ?? '')));
            if ($alias === 'esy' || str_contains($nama, 'ekonomi syariah')) {
                return true;
            }
        }

        if (method_exists($obj, 'prodiRelation') && $obj->prodiRelation) {
            $alias = strtolower(trim((string)($obj->prodiRelation->alias ?? '')));
            $nama = strtolower(trim((string)($obj->prodiRelation->nama ?? '')));
            if ($alias === 'esy' || str_contains($nama, 'ekonomi syariah')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menambahkan baris baru dari proses Pengajuan Judul
     */
    public static function appendRow($pj): bool
    {
        if (!self::isEkonomiSyariah($pj)) {
            Log::info("GoogleSheetEsyService: Bukan prodi Ekonomi Syariah (ID {$pj->id}), sync dilewati.");
            return false;
        }

        try {
            $client = self::getClient();
            $drive = new Drive($client);
            $fileId = self::getSpreadsheetId();

            $file = $drive->files->get($fileId, ['fields' => 'id, name, mimeType, capabilities']);
            $mimeType = $file->getMimeType();

            if (str_contains($mimeType, 'spreadsheetml') || str_ends_with(strtolower($file->getName()), '.xlsx')) {
                return self::processXlsxOnDrive($pj, 'append', $drive, $fileId);
            }

            return self::appendRowSheetsApi($pj, $client, $fileId);
        } catch (\Throwable $e) {
            Log::error("GoogleSheetEsyService appendRow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengupdate baris yang sudah ada dari proses Pengajuan Judul
     */
    public static function updateRow($pj): bool
    {
        if (!self::isEkonomiSyariah($pj)) {
            Log::info("GoogleSheetEsyService: Bukan prodi Ekonomi Syariah (ID {$pj->id}), sync dilewati.");
            return false;
        }

        try {
            $client = self::getClient();
            $drive = new Drive($client);
            $fileId = self::getSpreadsheetId();

            $file = $drive->files->get($fileId, ['fields' => 'id, name, mimeType, capabilities']);
            $mimeType = $file->getMimeType();

            if (str_contains($mimeType, 'spreadsheetml') || str_ends_with(strtolower($file->getName()), '.xlsx')) {
                return self::processXlsxOnDrive($pj, 'update', $drive, $fileId);
            }

            return self::updateRowSheetsApi($pj, $client, $fileId);
        } catch (\Throwable $e) {
            Log::error("GoogleSheetEsyService updateRow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sinkronisasi PB.1 dan PB.2 saat store atau update Surat Tugas
     */
    public static function syncPembimbingFromSuratTugas($st): bool
    {
        if (!self::isEkonomiSyariah($st)) {
            Log::info("GoogleSheetEsyService [SuratTugas]: Bukan prodi Ekonomi Syariah (ID {$st->id}), sync dilewati.");
            return false;
        }

        try {
            $client = self::getClient();
            $drive = new Drive($client);
            $fileId = self::getSpreadsheetId();

            $file = $drive->files->get($fileId, ['fields' => 'id, name, mimeType, capabilities']);
            $mimeType = $file->getMimeType();

            if (str_contains($mimeType, 'spreadsheetml') || str_ends_with(strtolower($file->getName()), '.xlsx')) {
                return self::processPembimbingXlsxOnDrive($st, $drive, $fileId);
            }

            return self::processPembimbingSheetsApi($st, $client, $fileId);
        } catch (\Throwable $e) {
            Log::error("GoogleSheetEsyService syncPembimbingFromSuratTugas error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Proses pengisian PB.1 dan PB.2 pada file XLSX di Google Drive dari Surat Tugas
     */
    protected static function processPembimbingXlsxOnDrive($st, Drive $drive, string $fileId): bool
    {
        $tempPath = storage_path('app/temp_st_esy_' . uniqid() . '.xlsx');

        try {
            $response = $drive->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();
            file_put_contents($tempPath, $content);

            $spreadsheet = IOFactory::load($tempPath);

            $nimRaw = (string)($st->nim_nik ?? '');
            $cleanTargetNim = preg_replace('/[^0-9]/', '', $nimRaw);
            $pembimbing1 = trim((string)($st->pembimbing1 ?? ''));
            $pembimbing2 = trim((string)($st->pembimbing2 ?? ''));
            $namaMhs = strtoupper(trim((string)($st->nama_mhs ?? '')));
            $judulSkripsi = trim((string)($st->judul_skripsi ?? ''));
            $angkatan = strlen($cleanTargetNim) >= 4 ? substr($cleanTargetNim, 0, 4) : date('Y');

            $matchedSheet = null;
            $matchedRowIndex = null;

            // Cari mahasiswa di semua sheet (Mahasiswa dan Mahasiswi)
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $headerRowNumber = 3;
                for ($r = 1; $r <= 6; $r++) {
                    $colA = strtolower(trim((string)$sheet->getCell("A{$r}")->getFormattedValue()));
                    $colB = strtolower(trim((string)$sheet->getCell("B{$r}")->getFormattedValue()));
                    if ($colA === 'no' || str_contains($colB, 'judul')) {
                        $headerRowNumber = $r;
                        break;
                    }
                }

                $highestRow = $sheet->getHighestRow();
                for ($r = $headerRowNumber + 1; $r <= max($highestRow, 1000); $r++) {
                    $valNim = trim((string)$sheet->getCell("D{$r}")->getFormattedValue());
                    $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);

                    if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                        $matchedSheet = $sheet;
                        $matchedRowIndex = $r;
                        break 2;
                    }
                }
            }

            if ($matchedSheet && $matchedRowIndex) {
                // Update PB.1 dan PB.2 pada baris yang sudah ada
                $matchedSheet->setCellValue("F{$matchedRowIndex}", $pembimbing1);
                $matchedSheet->setCellValue("G{$matchedRowIndex}", $pembimbing2);
                if (!empty($judulSkripsi)) {
                    $matchedSheet->setCellValue("B{$matchedRowIndex}", $judulSkripsi);
                }
                if (!empty($namaMhs)) {
                    $matchedSheet->setCellValue("C{$matchedRowIndex}", $namaMhs);
                }

                Log::info("GoogleSheetEsyService [SuratTugas XLSX]: Berhasil mengisi PB.1 '{$pembimbing1}' dan PB.2 '{$pembimbing2}' pada baris {$matchedRowIndex} sheet '{$matchedSheet->getTitle()}' untuk NIM {$nimRaw}");
            } else {
                // Jika mahasiswa belum ada di spreadsheet, buat baris baru di sheet yang sesuai
                $isFemale = false;
                $pj = \App\Models\PengajuanJudul::where('nim', 'like', "%{$cleanTargetNim}%")->first();
                if ($pj && !empty($pj->jenis_kelamin)) {
                    $jk = strtoupper(trim((string)$pj->jenis_kelamin));
                    $isFemale = ($jk === 'P' || str_contains($jk, 'PEREMPUAN') || str_contains($jk, 'BANAT'));
                } else {
                    $mhs = \App\Models\Mahasiswa::where('nim', 'like', "%{$cleanTargetNim}%")->first();
                    if ($mhs && !empty($mhs->jenis_kelamin)) {
                        $jk = strtoupper(trim((string)$mhs->jenis_kelamin));
                        $isFemale = ($jk === 'P' || str_contains($jk, 'PEREMPUAN') || str_contains($jk, 'BANAT'));
                    }
                }

                $targetSheet = null;
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $cleanTitle = strtoupper(trim($sheet->getTitle()));
                    if ($isFemale) {
                        if (str_contains($cleanTitle, 'MAHASISWI') || str_contains($cleanTitle, 'BANAT') || str_contains($cleanTitle, 'PEREMPUAN')) {
                            $targetSheet = $sheet;
                            break;
                        }
                    } else {
                        if ((str_contains($cleanTitle, 'MAHASISWA') && !str_contains($cleanTitle, 'MAHASISWI')) || str_contains($cleanTitle, 'BANIN') || str_contains($cleanTitle, 'LAKI')) {
                            $targetSheet = $sheet;
                            break;
                        }
                    }
                }

                if (!$targetSheet) {
                    $targetSheet = $isFemale ? ($spreadsheet->getSheet(1) ?? $spreadsheet->getSheet(0)) : $spreadsheet->getSheet(0);
                }

                $headerRowNumber = 3;
                for ($r = 1; $r <= 6; $r++) {
                    $colA = strtolower(trim((string)$targetSheet->getCell("A{$r}")->getFormattedValue()));
                    $colB = strtolower(trim((string)$targetSheet->getCell("B{$r}")->getFormattedValue()));
                    if ($colA === 'no' || str_contains($colB, 'judul')) {
                        $headerRowNumber = $r;
                        break;
                    }
                }

                $lastFilledRow = $headerRowNumber;
                $maxNo = 0;
                $highestRow = $targetSheet->getHighestRow();

                for ($r = $headerRowNumber + 1; $r <= max($highestRow, 1000); $r++) {
                    $valNoStr = trim((string)$targetSheet->getCell("A{$r}")->getFormattedValue());
                    $valJudul = trim((string)$targetSheet->getCell("B{$r}")->getFormattedValue());
                    $valNama = trim((string)$targetSheet->getCell("C{$r}")->getFormattedValue());
                    $valNim = trim((string)$targetSheet->getCell("D{$r}")->getFormattedValue());

                    if (!empty($valJudul) || !empty($valNama) || !empty($valNim) || !empty($valNoStr)) {
                        $lastFilledRow = $r;
                        $num = (int)preg_replace('/[^0-9]/', '', $valNoStr);
                        if ($num > $maxNo) {
                            $maxNo = $num;
                        }
                    }
                }

                $newRowIndex = $lastFilledRow + 1;
                $newNo = $maxNo > 0 ? ($maxNo + 1) : ($newRowIndex - $headerRowNumber);

                $targetSheet->setCellValue("A{$newRowIndex}", $newNo);
                $targetSheet->setCellValue("B{$newRowIndex}", $judulSkripsi);
                $targetSheet->setCellValue("C{$newRowIndex}", $namaMhs);
                $targetSheet->setCellValue("D{$newRowIndex}", $nimRaw);
                $targetSheet->setCellValue("E{$newRowIndex}", $angkatan);
                $targetSheet->setCellValue("F{$newRowIndex}", $pembimbing1);
                $targetSheet->setCellValue("G{$newRowIndex}", $pembimbing2);

                Log::info("GoogleSheetEsyService [SuratTugas XLSX]: Baris baru {$newRowIndex} ditambahkan di sheet '{$targetSheet->getTitle()}' dengan PB.1 '{$pembimbing1}' & PB.2 '{$pembimbing2}' untuk NIM {$nimRaw}");
            }

            $writer = new XlsxWriter($spreadsheet);
            $writer->save($tempPath);

            $updatedContent = file_get_contents($tempPath);
            $drive->files->update($fileId, new \Google\Service\Drive\DriveFile(), [
                'data' => $updatedContent,
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploadType' => 'multipart'
            ]);

            @unlink($tempPath);
            return true;
        } catch (\Throwable $e) {
            @unlink($tempPath);
            Log::error("GoogleSheetEsyService processPembimbingXlsxOnDrive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Proses pengisian PB.1 dan PB.2 pada Google Sheets API dari Surat Tugas
     */
    protected static function processPembimbingSheetsApi($st, Client $client, string $fileId): bool
    {
        $service = new Sheets($client);
        $spreadsheet = $service->spreadsheets->get($fileId);
        $sheets = $spreadsheet->getSheets();

        $nimRaw = (string)($st->nim_nik ?? '');
        $cleanTargetNim = preg_replace('/[^0-9]/', '', $nimRaw);
        $pembimbing1 = trim((string)($st->pembimbing1 ?? ''));
        $pembimbing2 = trim((string)($st->pembimbing2 ?? ''));
        $namaMhs = strtoupper(trim((string)($st->nama_mhs ?? '')));
        $judulSkripsi = trim((string)($st->judul_skripsi ?? ''));
        $angkatan = strlen($cleanTargetNim) >= 4 ? substr($cleanTargetNim, 0, 4) : date('Y');

        $matchedSheetTitle = null;
        $matchedRowNumber = null;

        foreach ($sheets as $sheet) {
            $title = $sheet->getProperties()->getTitle();
            $headerInfo = self::getHeaderInfo($service, $title);
            $headerRowNumber = $headerInfo['rowNumber'];

            $range = "'{$title}'!D" . ($headerRowNumber + 1) . ":D";
            $response = $service->spreadsheets_values->get($fileId, $range);
            $rows = $response->getValues() ?? [];

            foreach ($rows as $idx => $r) {
                $valNim = isset($r[0]) ? (string)$r[0] : '';
                $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);
                if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                    $matchedSheetTitle = $title;
                    $matchedRowNumber = $headerRowNumber + $idx + 1;
                    break 2;
                }
            }
        }

        if ($matchedSheetTitle && $matchedRowNumber) {
            $range = "'{$matchedSheetTitle}'!F{$matchedRowNumber}:G{$matchedRowNumber}";
            $body = new ValueRange(['values' => [[$pembimbing1, $pembimbing2]]]);
            $service->spreadsheets_values->update($fileId, $range, $body, ['valueInputOption' => 'USER_ENTERED']);

            Log::info("GoogleSheetEsyService [SuratTugas Sheets API]: Berhasil update PB.1 '{$pembimbing1}' & PB.2 '{$pembimbing2}' di baris {$matchedRowNumber} tab '{$matchedSheetTitle}'");
            return true;
        }

        // Jika belum ada, cari sheet tujuan dan append
        $isFemale = false;
        $pj = \App\Models\PengajuanJudul::where('nim', 'like', "%{$cleanTargetNim}%")->first();
        if ($pj && !empty($pj->jenis_kelamin)) {
            $jk = strtoupper(trim((string)$pj->jenis_kelamin));
            $isFemale = ($jk === 'P' || str_contains($jk, 'PEREMPUAN') || str_contains($jk, 'BANAT'));
        }

        $dummyPj = (object)[
            'jenis_kelamin' => $isFemale ? 'P' : 'L',
            'nim' => $nimRaw,
            'nama_mhs' => $namaMhs,
            'judul_proposal_skripsi' => $judulSkripsi,
            'dosen_penasehat' => $pembimbing1,
        ];

        $targetSheetTitle = self::getTargetSheetName($service, $dummyPj);
        $headerInfo = self::getHeaderInfo($service, $targetSheetTitle);
        $headerRowNumber = $headerInfo['rowNumber'];
        $headers = $headerInfo['headers'];

        $range = "'{$targetSheetTitle}'!A" . ($headerRowNumber + 1) . ":E";
        $response = $service->spreadsheets_values->get($fileId, $range);
        $existingRows = $response->getValues() ?? [];

        $dataCount = 0;
        $maxNo = 0;
        $lastFilledIndex = 0;

        foreach ($existingRows as $idx => $r) {
            $valNo = isset($r[0]) ? (int)preg_replace('/[^0-9]/', '', (string)$r[0]) : 0;
            $valJudul = isset($r[1]) ? trim((string)$r[1]) : '';
            $valNama = isset($r[2]) ? trim((string)$r[2]) : '';
            $valNim = isset($r[3]) ? trim((string)$r[3]) : '';

            if (!empty($valJudul) || !empty($valNama) || !empty($valNim)) {
                $dataCount++;
                $lastFilledIndex = $idx + 1;
                if ($valNo > $maxNo) {
                    $maxNo = $valNo;
                }
            }
        }

        $nextRowIndex = $headerRowNumber + $lastFilledIndex + 1;
        $nextNo = $maxNo > 0 ? ($maxNo + 1) : ($dataCount + 1);

        $row = [
            (string)$nextNo,
            $judulSkripsi,
            $namaMhs,
            $nimRaw,
            $angkatan,
            $pembimbing1,
            $pembimbing2,
            '',
            '',
        ];

        $body = new ValueRange(['values' => [$row]]);
        $lastColLetter = self::columnIndexToLetter(count($row));
        $targetRange = "'{$targetSheetTitle}'!A{$nextRowIndex}:{$lastColLetter}{$nextRowIndex}";

        $service->spreadsheets_values->update($fileId, $targetRange, $body, ['valueInputOption' => 'USER_ENTERED']);
        Log::info("GoogleSheetEsyService [SuratTugas Sheets API]: Berhasil append baris baru ke-{$nextRowIndex} (Tab: {$targetSheetTitle}) dengan PB.1 & PB.2");
        return true;
    }

    /**
     * Manipulasi file Excel (.xlsx) langsung di Google Drive untuk Pengajuan Judul
     */
    protected static function processXlsxOnDrive($pj, string $action, Drive $drive, string $fileId): bool
    {
        $tempPath = storage_path('app/temp_bank_judul_' . uniqid() . '.xlsx');

        try {
            $response = $drive->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();
            file_put_contents($tempPath, $content);

            $spreadsheet = IOFactory::load($tempPath);

            $jk = strtoupper(trim((string)($pj->jenis_kelamin ?? '')));
            $isFemale = ($jk === 'P' || str_contains($jk, 'PEREMPUAN') || str_contains($jk, 'BANAT'));

            $targetSheet = null;
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $cleanTitle = strtoupper(trim($sheet->getTitle()));
                if ($isFemale) {
                    if (str_contains($cleanTitle, 'MAHASISWI') || str_contains($cleanTitle, 'BANAT') || str_contains($cleanTitle, 'PEREMPUAN')) {
                        $targetSheet = $sheet;
                        break;
                    }
                } else {
                    if ((str_contains($cleanTitle, 'MAHASISWA') && !str_contains($cleanTitle, 'MAHASISWI')) || str_contains($cleanTitle, 'BANIN') || str_contains($cleanTitle, 'LAKI')) {
                        $targetSheet = $sheet;
                        break;
                    }
                }
            }

            if (!$targetSheet) {
                $targetSheet = $isFemale ? ($spreadsheet->getSheet(1) ?? $spreadsheet->getSheet(0)) : $spreadsheet->getSheet(0);
            }

            $headerRowNumber = 3;
            for ($r = 1; $r <= 6; $r++) {
                $colA = strtolower(trim((string)$targetSheet->getCell("A{$r}")->getFormattedValue()));
                $colB = strtolower(trim((string)$targetSheet->getCell("B{$r}")->getFormattedValue()));
                if ($colA === 'no' || str_contains($colB, 'judul')) {
                    $headerRowNumber = $r;
                    break;
                }
            }

            $cleanTargetNim = preg_replace('/[^0-9]/', '', (string)$pj->nim);
            $targetRowIndex = null;
            $lastFilledRow = $headerRowNumber;
            $maxNo = 0;

            $highestRow = $targetSheet->getHighestRow();
            for ($r = $headerRowNumber + 1; $r <= max($highestRow, 1000); $r++) {
                $valNoStr = trim((string)$targetSheet->getCell("A{$r}")->getFormattedValue());
                $valJudul = trim((string)$targetSheet->getCell("B{$r}")->getFormattedValue());
                $valNama = trim((string)$targetSheet->getCell("C{$r}")->getFormattedValue());
                $valNim = trim((string)$targetSheet->getCell("D{$r}")->getFormattedValue());

                if (!empty($valJudul) || !empty($valNama) || !empty($valNim) || !empty($valNoStr)) {
                    $lastFilledRow = $r;
                    $num = (int)preg_replace('/[^0-9]/', '', $valNoStr);
                    if ($num > $maxNo) {
                        $maxNo = $num;
                    }

                    $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);
                    if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                        $targetRowIndex = $r;
                    }
                }
            }

            $namaMhs = strtoupper(trim((string)($pj->nama_mhs ?? '')));
            $judul = trim((string)($pj->judul_proposal_skripsi ?? ''));
            $nim = (string)($pj->nim ?? '');
            $angkatan = strlen($cleanTargetNim) >= 4 ? substr($cleanTargetNim, 0, 4) : date('Y');

            if ($action === 'update' && $targetRowIndex) {
                // Update baris yang sudah ada, pertahankan PB.1 dan PB.2 yang sudah terisi
                $targetSheet->setCellValue("B{$targetRowIndex}", $judul);
                $targetSheet->setCellValue("C{$targetRowIndex}", $namaMhs);
                $targetSheet->setCellValue("D{$targetRowIndex}", $nim);
                $targetSheet->setCellValue("E{$targetRowIndex}", $angkatan);

                Log::info("GoogleSheetEsyService [XLSX]: Berhasil update baris {$targetRowIndex} pada sheet '{$targetSheet->getTitle()}' untuk NIM {$nim}");
            } else {
                // Tambah baris baru: PB.1 dan PB.2 dikosongkan (diisi nanti lewat Surat Tugas)
                $newRowIndex = $lastFilledRow + 1;
                $newNo = $maxNo > 0 ? ($maxNo + 1) : ($newRowIndex - $headerRowNumber);

                $targetSheet->setCellValue("A{$newRowIndex}", $newNo);
                $targetSheet->setCellValue("B{$newRowIndex}", $judul);
                $targetSheet->setCellValue("C{$newRowIndex}", $namaMhs);
                $targetSheet->setCellValue("D{$newRowIndex}", $nim);
                $targetSheet->setCellValue("E{$newRowIndex}", $angkatan);
                $targetSheet->setCellValue("F{$newRowIndex}", ""); // PB.1 kosong
                $targetSheet->setCellValue("G{$newRowIndex}", ""); // PB.2 kosong

                Log::info("GoogleSheetEsyService [XLSX]: Berhasil menambah baris baru {$newRowIndex} pada sheet '{$targetSheet->getTitle()}' untuk NIM {$nim}");
            }

            $writer = new XlsxWriter($spreadsheet);
            $writer->save($tempPath);

            $updatedContent = file_get_contents($tempPath);
            $drive->files->update($fileId, new \Google\Service\Drive\DriveFile(), [
                'data' => $updatedContent,
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploadType' => 'multipart'
            ]);

            @unlink($tempPath);
            return true;
        } catch (\Throwable $e) {
            @unlink($tempPath);
            Log::error("GoogleSheetEsyService processXlsxOnDrive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tambah baris baru jika target adalah Google Spreadsheet asli (Google Sheets API)
     */
    protected static function appendRowSheetsApi($pj, Client $client, string $fileId): bool
    {
        $service = new Sheets($client);
        $sheetName = self::getTargetSheetName($service, $pj);
        $headerInfo = self::getHeaderInfo($service, $sheetName);
        $headerRowNumber = $headerInfo['rowNumber'];
        $headers = $headerInfo['headers'];

        $range = "'{$sheetName}'!A" . ($headerRowNumber + 1) . ":E";
        $response = $service->spreadsheets_values->get($fileId, $range);
        $existingRows = $response->getValues() ?? [];

        $dataCount = 0;
        $maxNo = 0;
        $lastFilledIndex = 0;

        foreach ($existingRows as $idx => $r) {
            $valNo = isset($r[0]) ? (int)preg_replace('/[^0-9]/', '', (string)$r[0]) : 0;
            $valJudul = isset($r[1]) ? trim((string)$r[1]) : '';
            $valNama = isset($r[2]) ? trim((string)$r[2]) : '';
            $valNim = isset($r[3]) ? trim((string)$r[3]) : '';

            if (!empty($valJudul) || !empty($valNama) || !empty($valNim)) {
                $dataCount++;
                $lastFilledIndex = $idx + 1;
                if ($valNo > $maxNo) {
                    $maxNo = $valNo;
                }
            }
        }

        $nextRowIndex = $headerRowNumber + $lastFilledIndex + 1;
        $nextNo = $maxNo > 0 ? ($maxNo + 1) : ($dataCount + 1);

        $row = self::buildRowData($pj, $headers, $nextNo);

        $body = new ValueRange(['values' => [$row]]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        $lastColLetter = self::columnIndexToLetter(count($row));
        $targetRange = "'{$sheetName}'!A{$nextRowIndex}:{$lastColLetter}{$nextRowIndex}";

        $service->spreadsheets_values->update($fileId, $targetRange, $body, $params);
        Log::info("GoogleSheetEsyService [Sheets API]: Berhasil menambahkan baris baru ke-{$nextRowIndex} (Tab: {$sheetName}) untuk NIM {$pj->nim}");
        return true;
    }

    /**
     * Update baris jika target adalah Google Spreadsheet asli (Google Sheets API)
     */
    protected static function updateRowSheetsApi($pj, Client $client, string $fileId): bool
    {
        $service = new Sheets($client);
        $sheetName = self::getTargetSheetName($service, $pj);
        $headerInfo = self::getHeaderInfo($service, $sheetName);
        $headerRowNumber = $headerInfo['rowNumber'];
        $headers = $headerInfo['headers'];

        $nimColIndex = 3;
        if (!empty($headers)) {
            foreach ($headers as $idx => $header) {
                $h = strtolower(trim((string)$header));
                if (str_contains($h, 'nim') || str_contains($h, 'nimko') || str_contains($h, 'nrim')) {
                    $nimColIndex = $idx;
                    break;
                }
            }
        }

        $lastHeaderLetter = self::columnIndexToLetter(!empty($headers) ? count($headers) : 10);
        $range = "'{$sheetName}'!A" . ($headerRowNumber + 1) . ":{$lastHeaderLetter}";
        $response = $service->spreadsheets_values->get($fileId, $range);
        $rows = $response->getValues() ?? [];

        $cleanTargetNim = preg_replace('/[^a-zA-Z0-9]/', '', (string)$pj->nim);
        $targetRowIndex = null;
        $existingRow = [];

        if (!empty($rows)) {
            foreach ($rows as $index => $row) {
                $rowNim = $row[$nimColIndex] ?? '';
                $cleanRowNim = preg_replace('/[^a-zA-Z0-9]/', '', (string)$rowNim);

                if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                    $targetRowIndex = $headerRowNumber + $index + 1;
                    $existingRow = $row;
                    break;
                }
            }
        }

        if (!$targetRowIndex) {
            return self::appendRowSheetsApi($pj, $client, $fileId);
        }

        $updatedRow = self::buildRowData($pj, $headers, 1, $existingRow);
        $body = new ValueRange(['values' => [$updatedRow]]);
        $lastColLetter = self::columnIndexToLetter(count($updatedRow));
        $updateRange = "'{$sheetName}'!A{$targetRowIndex}:{$lastColLetter}{$targetRowIndex}";
        $params = ['valueInputOption' => 'USER_ENTERED'];

        $service->spreadsheets_values->update($fileId, $updateRange, $body, $params);
        Log::info("GoogleSheetEsyService [Sheets API]: Berhasil update baris {$targetRowIndex} (Tab: {$sheetName}) untuk NIM {$pj->nim}");
        return true;
    }

    /**
     * Dapatkan nama tab/sheet berdasarkan jenis kelamin mahasiswa
     */
    public static function getTargetSheetName(Sheets $service, $pj): string
    {
        $jk = strtoupper(trim((string)($pj->jenis_kelamin ?? '')));
        $isFemale = ($jk === 'P' || str_contains($jk, 'PEREMPUAN') || str_contains($jk, 'BANAT'));

        try {
            $spreadsheet = $service->spreadsheets->get(self::getSpreadsheetId());
            $sheets = $spreadsheet->getSheets();

            foreach ($sheets as $sheet) {
                $title = $sheet->getProperties()->getTitle();
                $cleanTitle = strtoupper(trim($title));

                if ($isFemale) {
                    if (str_contains($cleanTitle, 'MAHASISWI') || str_contains($cleanTitle, 'BANAT') || str_contains($cleanTitle, 'PEREMPUAN')) {
                        return $title;
                    }
                } else {
                    if ((str_contains($cleanTitle, 'MAHASISWA') && !str_contains($cleanTitle, 'MAHASISWI')) || str_contains($cleanTitle, 'BANIN') || str_contains($cleanTitle, 'LAKI')) {
                        return $title;
                    }
                }
            }

            if ($isFemale && isset($sheets[1])) {
                return $sheets[1]->getProperties()->getTitle();
            }
            if (isset($sheets[0])) {
                return $sheets[0]->getProperties()->getTitle();
            }
        } catch (\Throwable $e) {
            Log::warning("GoogleSheetEsyService getTargetSheetName warning: " . $e->getMessage());
        }

        return $isFemale ? 'BANK JUDUL MAHASISWI ' : 'BANK JUDUL MAHASISWA ';
    }

    /**
     * Mendeteksi baris header pada Google Sheets API
     */
    public static function getHeaderInfo(Sheets $service, string $sheetName): array
    {
        try {
            $response = $service->spreadsheets_values->get(self::getSpreadsheetId(), "'{$sheetName}'!A1:Z6");
            $rows = $response->getValues() ?? [];

            foreach ($rows as $rowIndex => $cols) {
                $rowNumber = $rowIndex + 1;
                $rowText = strtolower(implode(' ', $cols));
                if (str_contains($rowText, 'no') && (str_contains($rowText, 'judul') || str_contains($rowText, 'nama') || str_contains($rowText, 'nim'))) {
                    return [
                        'rowNumber' => $rowNumber,
                        'headers'   => $cols,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("GoogleSheetEsyService getHeaderInfo warning: " . $e->getMessage());
        }

        return [
            'rowNumber' => 3,
            'headers'   => ['NO', 'JUDUL SKRIPSI MAHASISWA', 'NAMA MAHASISWA', 'NIM /NIMKO', 'ANGKATAN', 'PB.1', 'PB.2', 'SEMPRO', 'SKRIPSI'],
        ];
    }

    /**
     * Konversi index kolom (1-based) menjadi huruf kolom Google Sheet
     */
    public static function columnIndexToLetter(int $colNumber): string
    {
        $letter = '';
        while ($colNumber > 0) {
            $colNumber--;
            $letter = chr(65 + ($colNumber % 26)) . $letter;
            $colNumber = intval($colNumber / 26);
        }
        return $letter ?: 'A';
    }

    /**
     * Susun array data baris berdasarkan kolom-kolom spreadsheet
     */
    public static function buildRowData($pj, array $headers = [], int $no = 1, array $existingRow = []): array
    {
        $nim = (string)($pj->nim ?? '');
        $namaMhs = strtoupper(trim((string)($pj->nama_mhs ?? '')));
        $judul = trim((string)($pj->judul_proposal_skripsi ?? ''));

        $cleanDigits = preg_replace('/[^0-9]/', '', $nim);
        $angkatan = strlen($cleanDigits) >= 4 ? substr($cleanDigits, 0, 4) : date('Y');

        $nomorUrut = !empty($existingRow[0]) ? $existingRow[0] : (string)$no;

        if (!empty($headers)) {
            $row = [];
            foreach ($headers as $index => $header) {
                $h = strtolower(trim((string)$header));
                if ($h === 'no' || $h === 'nomor') {
                    $row[] = $nomorUrut;
                } elseif (str_contains($h, 'judul')) {
                    $row[] = $judul;
                } elseif (str_contains($h, 'nama') || str_contains($h, 'mhs')) {
                    $row[] = $namaMhs;
                } elseif (str_contains($h, 'nim') || str_contains($h, 'nimko') || str_contains($h, 'nrim')) {
                    $row[] = (!empty($nim) && str_starts_with($nim, '0')) ? "'" . $nim : $nim;
                } elseif (str_contains($h, 'angkatan') || str_contains($h, 'th_akademik') || str_contains($h, 'tahun')) {
                    $row[] = $angkatan;
                } elseif (str_contains($h, 'pb.1') || str_contains($h, 'pb 1') || str_contains($h, 'pembimbing 1')) {
                    // PB.1 diisi saat proses Surat Tugas (pertahankan nilai existing jika sudah ada)
                    $row[] = $existingRow[$index] ?? '';
                } elseif (str_contains($h, 'pb.2') || str_contains($h, 'pb 2') || str_contains($h, 'pembimbing 2')) {
                    // PB.2 diisi saat proses Surat Tugas (pertahankan nilai existing jika sudah ada)
                    $row[] = $existingRow[$index] ?? '';
                } elseif (str_contains($h, 'sempro') || str_contains($h, 'skripsi')) {
                    $row[] = $existingRow[$index] ?? '';
                } else {
                    $row[] = $existingRow[$index] ?? '';
                }
            }
            return $row;
        }

        return [
            $nomorUrut,
            $judul,
            $namaMhs,
            $nim,
            $angkatan,
            $existingRow[5] ?? '', // PB.1
            $existingRow[6] ?? '', // PB.2
            $existingRow[7] ?? '', // SEMPRO
            $existingRow[8] ?? '', // SKRIPSI
        ];
    }
}
