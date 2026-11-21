<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class GoogleSheetKpiService
{
    /**
     * Spreadsheet ID dari link:
     * https://docs.google.com/spreadsheets/d/1decPEwvgSKICesKpfyTfgu8kIRHuZRBy/edit?gid=1809981776#gid=1809981776
     */
    public static string $spreadsheetId = '1decPEwvgSKICesKpfyTfgu8kIRHuZRBy';
    public static int $gid = 1809981776; // Sheet 'NIM 2023'
    public static string $defaultSheetTitle = 'Sheet1';

    /**
     * Dapatkan Spreadsheet ID (dari .env jika ada, atau default)
     */
    public static function getSpreadsheetId(): string
    {
        return env('GOOGLE_SHEET_KPI_ID') ?: self::$spreadsheetId;
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
     * Mengecek apakah data (PengajuanJudul / SuratTugas) merupakan Prodi KPI (Komunikasi dan Penyiaran Islam)
     */
    public static function isKpi($obj): bool
    {
        if (!$obj) {
            return false;
        }

        // Cek ID prodi (5 = Komunikasi dan Penyiaran Islam / KPI)
        if (!empty($obj->prodi_id) && (int)$obj->prodi_id === 5) {
            return true;
        }

        // Cek teks string prodi
        $prodi = strtolower(trim((string)($obj->prodi ?? '')));
        if ($prodi === 'kpi' || str_contains($prodi, 'komunikasi') || str_contains($prodi, 'penyiaran islam')) {
            return true;
        }

        // Cek relasi jika ada
        if (method_exists($obj, 'prodi') && $obj->prodi) {
            $alias = strtolower(trim((string)($obj->prodi->alias ?? '')));
            $nama = strtolower(trim((string)($obj->prodi->nama ?? '')));
            if ($alias === 'kpi' || str_contains($nama, 'komunikasi') || str_contains($nama, 'penyiaran islam')) {
                return true;
            }
        }

        if (method_exists($obj, 'prodiRelation') && $obj->prodiRelation) {
            $alias = strtolower(trim((string)($obj->prodiRelation->alias ?? '')));
            $nama = strtolower(trim((string)($obj->prodiRelation->nama ?? '')));
            if ($alias === 'kpi' || str_contains($nama, 'komunikasi') || str_contains($nama, 'penyiaran islam')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menambahkan baris baru dari proses Pengajuan Judul untuk KPI
     */
    public static function appendRow($pj): bool
    {
        if (!self::isKpi($pj)) {
            Log::info("GoogleSheetKpiService: Bukan prodi KPI (ID {$pj->id}), sync dilewati.");
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
            Log::error("GoogleSheetKpiService appendRow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengupdate baris yang sudah ada dari proses Pengajuan Judul untuk KPI
     */
    public static function updateRow($pj): bool
    {
        if (!self::isKpi($pj)) {
            Log::info("GoogleSheetKpiService: Bukan prodi KPI (ID {$pj->id}), sync dilewati.");
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
            Log::error("GoogleSheetKpiService updateRow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sinkronisasi PEMBIMBING 1 dan PEMBIMBING 2 dari proses Surat Tugas untuk KPI
     */
    public static function syncPembimbingFromSuratTugas($st): bool
    {
        if (!self::isKpi($st)) {
            Log::info("GoogleSheetKpiService [SuratTugas]: Bukan prodi KPI (ID {$st->id}), sync dilewati.");
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
            Log::error("GoogleSheetKpiService syncPembimbingFromSuratTugas error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dapatkan sheet target berdasarkan angkatan NIM mahasiswa.
     * Bila sheet angkatan (contoh: NIM 2024, NIM 2025) belum ada, otomatis buat sheet baru
     * dengan nama "NIM {angkatan}" menggunakan format/template dari angkatan sebelumnya.
     */
    public static function getOrCreateTargetSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $nim): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $cleanNim = preg_replace('/[^0-9]/', '', $nim);
        $angkatan = strlen($cleanNim) >= 4 ? substr($cleanNim, 0, 4) : date('Y');
        $expectedSheetTitle = "NIM {$angkatan}";

        // 1. Cek apakah sheet dengan nama 'NIM {angkatan}' sudah ada
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (strcasecmp(trim($name), $expectedSheetTitle) === 0) {
                return $spreadsheet->getSheetByName($name);
            }
        }

        // 2. Bila belum ada, cari template sheet terbaik untuk dikloning (NIM 2023, NIM 2022, dsb)
        $templateSheet = $spreadsheet->getSheetByName('NIM 2023')
            ?? $spreadsheet->getSheetByName('NIM 2022')
            ?? $spreadsheet->getSheetByName('NIM 2021')
            ?? $spreadsheet->getSheet(1)
            ?? $spreadsheet->getSheet(0);

        $newSheet = clone $templateSheet;
        $newSheet->setTitle($expectedSheetTitle);

        // Bersihkan data baris mahasiswa dari template (mulai baris 3 ke bawah), mempertahankan baris header (baris 1 & 2)
        $highestRow = $newSheet->getHighestRow();
        $highestCol = $newSheet->getHighestColumn();
        for ($r = 3; $r <= max($highestRow, 3); $r++) {
            for ($col = 'A'; $col <= $highestCol; $col++) {
                $newSheet->setCellValue("{$col}{$r}", null);
            }
        }

        $spreadsheet->addSheet($newSheet);
        Log::info("GoogleSheetKpiService: Berhasil membuat sheet baru '{$expectedSheetTitle}' karena angkatan {$angkatan} belum ada.");

        return $newSheet;
    }

    /**
     * Helper untuk Sheets API (jika file native Google Spreadsheet):
     * Dapatkan atau otomatis buat sheet baru 'NIM {angkatan}' jika belum ada.
     */
    protected static function getOrCreateSheetInSheetsApi(Sheets $service, string $fileId, string $nim): string
    {
        $cleanNim = preg_replace('/[^0-9]/', '', $nim);
        $angkatan = strlen($cleanNim) >= 4 ? substr($cleanNim, 0, 4) : date('Y');
        $expectedSheetTitle = "NIM {$angkatan}";

        $spreadsheet = $service->spreadsheets->get($fileId);
        $templateSheetId = null;

        foreach ($spreadsheet->getSheets() as $sh) {
            $title = $sh->getProperties()->getTitle();
            if (strcasecmp(trim($title), $expectedSheetTitle) === 0) {
                return $title;
            }
            if ($title === 'NIM 2023' || str_contains($title, 'NIM')) {
                $templateSheetId = $sh->getProperties()->getSheetId();
            }
        }

        // Buat sheet baru jika belum ada
        try {
            if ($templateSheetId !== null) {
                $duplicateRequest = new \Google\Service\Sheets\DuplicateSheetRequest([
                    'sourceSheetId' => $templateSheetId,
                    'newSheetName'  => $expectedSheetTitle,
                ]);
                $body = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                    'requests' => [
                        new \Google\Service\Sheets\Request(['duplicateSheet' => $duplicateRequest])
                    ]
                ]);
                $service->spreadsheets->batchUpdate($fileId, $body);

                // Kosongkan isi baris data dari baris 3 ke bawah
                $clearRange = "'{$expectedSheetTitle}'!A3:Z1000";
                $service->spreadsheets_values->clear($fileId, $clearRange, new \Google\Service\Sheets\ClearValuesRequest());
            } else {
                $addSheetRequest = new \Google\Service\Sheets\AddSheetRequest([
                    'properties' => ['title' => $expectedSheetTitle]
                ]);
                $body = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                    'requests' => [
                        new \Google\Service\Sheets\Request(['addSheet' => $addSheetRequest])
                    ]
                ]);
                $service->spreadsheets->batchUpdate($fileId, $body);

                $headerRange = "'{$expectedSheetTitle}'!A1:F1";
                $headerBody = new ValueRange([
                    'values' => [['No.', 'Mahasiswa', 'NIM', 'JUDUL', 'Dosen Pembimbing I', 'Dosen Pembimbing II']]
                ]);
                $service->spreadsheets_values->update($fileId, $headerRange, $headerBody, ['valueInputOption' => 'USER_ENTERED']);
            }

            Log::info("GoogleSheetKpiService [Sheets API]: Berhasil membuat sheet baru '{$expectedSheetTitle}'.");
            return $expectedSheetTitle;
        } catch (\Throwable $e) {
            Log::warning("GoogleSheetKpiService: Gagal membuat sheet baru '{$expectedSheetTitle}': " . $e->getMessage());
            return $spreadsheet->getSheets()[0]->getProperties()->getTitle();
        }
    }

    /**
     * Proses file XLSX di Google Drive untuk Pengajuan Judul (append / update)
     */
    protected static function processXlsxOnDrive($pj, string $action, Drive $drive, string $fileId): bool
    {
        $tempPath = storage_path('app/temp_kpi_' . uniqid() . '.xlsx');

        try {
            $response = $drive->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();
            file_put_contents($tempPath, $content);

            $spreadsheet = IOFactory::load($tempPath);

            $cleanTargetNim = preg_replace('/[^0-9]/', '', (string)$pj->nim);
            $matchedSheet = null;
            $matchedRowIndex = null;

            // Cari apakah NIM sudah ada di seluruh sheet
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                for ($r = 1; $r <= $sheet->getHighestRow(); $r++) {
                    $valNim = trim((string)$sheet->getCell("C{$r}")->getFormattedValue());
                    $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);

                    if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                        $matchedSheet = $sheet;
                        $matchedRowIndex = $r;
                        break 2;
                    }
                }
            }

            if ($matchedSheet && $matchedRowIndex) {
                // Update baris yang sudah ada
                $matchedSheet->setCellValue("B{$matchedRowIndex}", $pj->nama_mhs);
                $matchedSheet->setCellValue("C{$matchedRowIndex}", $pj->nim);
                $matchedSheet->setCellValue("D{$matchedRowIndex}", $pj->judul_proposal_skripsi);

                Log::info("GoogleSheetKpiService [XLSX]: Mengupdate baris {$matchedRowIndex} di sheet '{$matchedSheet->getTitle()}' untuk NIM {$pj->nim}");
            } else {
                // Dapatkan sheet angkatan mahasiswa (bila belum ada, otomatis buat sheet baru 'NIM + angkatan')
                $targetSheet = self::getOrCreateTargetSheet($spreadsheet, (string)$pj->nim);

                // Cari baris header (biasanya baris 1 atau 2)
                $headerRowNumber = 1;
                for ($r = 1; $r <= 3; $r++) {
                    $colA = strtolower(trim((string)$targetSheet->getCell("A{$r}")->getFormattedValue()));
                    $colC = strtolower(trim((string)$targetSheet->getCell("C{$r}")->getFormattedValue()));
                    if (str_contains($colA, 'no') || str_contains($colC, 'nim')) {
                        $headerRowNumber = $r;
                        break;
                    }
                }

                // Cari baris terakhir yang terisi
                $lastFilledRow = $headerRowNumber;
                $maxNo = 0;
                $highestRow = $targetSheet->getHighestRow();

                for ($r = $headerRowNumber + 1; $r <= max($highestRow, 1000); $r++) {
                    $valNo = trim((string)$targetSheet->getCell("A{$r}")->getFormattedValue());
                    $valNama = trim((string)$targetSheet->getCell("B{$r}")->getFormattedValue());
                    $valNim = trim((string)$targetSheet->getCell("C{$r}")->getFormattedValue());
                    $valJudul = trim((string)$targetSheet->getCell("D{$r}")->getFormattedValue());

                    if (!empty($valNama) || !empty($valNim) || !empty($valJudul) || !empty($valNo)) {
                        $lastFilledRow = $r;
                        $num = (int)preg_replace('/[^0-9]/', '', $valNo);
                        if ($num > $maxNo) {
                            $maxNo = $num;
                        }
                    }
                }

                $newRowIndex = $lastFilledRow + 1;
                $newNo = $maxNo > 0 ? ($maxNo + 1) : ($newRowIndex - $headerRowNumber);

                // Format kolom KPI:
                // A: No.
                // B: Mahasiswa (Nama)
                // C: NIM
                // D: JUDUL
                $targetSheet->setCellValue("A{$newRowIndex}", $newNo);
                $targetSheet->setCellValue("B{$newRowIndex}", $pj->nama_mhs);
                $targetSheet->setCellValue("C{$newRowIndex}", $pj->nim);
                $targetSheet->setCellValue("D{$newRowIndex}", $pj->judul_proposal_skripsi);

                Log::info("GoogleSheetKpiService [XLSX]: Menambahkan baris baru {$newRowIndex} di sheet '{$targetSheet->getTitle()}' untuk NIM {$pj->nim}");
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
            Log::error("GoogleSheetKpiService processXlsxOnDrive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Proses pengisian Dosen Pembimbing I dan II pada file XLSX KPI dari Surat Tugas
     */
    protected static function processPembimbingXlsxOnDrive($st, Drive $drive, string $fileId): bool
    {
        $tempPath = storage_path('app/temp_st_kpi_' . uniqid() . '.xlsx');

        try {
            $response = $drive->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();
            file_put_contents($tempPath, $content);

            $spreadsheet = IOFactory::load($tempPath);

            $nimRaw = (string)($st->nim_nik ?? $st->nim ?? '');
            $cleanTargetNim = preg_replace('/[^0-9]/', '', $nimRaw);
            $pembimbing1 = trim((string)($st->pembimbing1 ?? ''));
            $pembimbing2 = trim((string)($st->pembimbing2 ?? ''));
            $namaMhs = trim((string)($st->nama_mhs ?? ''));
            $judulSkripsi = trim((string)($st->judul_skripsi ?? ''));

            $matchedSheet = null;
            $matchedRowIndex = null;

            // Cari mahasiswa di seluruh sheet
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                for ($r = 1; $r <= $sheet->getHighestRow(); $r++) {
                    $valNim = trim((string)$sheet->getCell("C{$r}")->getFormattedValue());
                    $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);

                    if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                        $matchedSheet = $sheet;
                        $matchedRowIndex = $r;
                        break 2;
                    }
                }
            }

            if ($matchedSheet && $matchedRowIndex) {
                // Kolom E = Dosen Pembimbing I, Kolom F = Dosen Pembimbing II
                $matchedSheet->setCellValue("E{$matchedRowIndex}", $pembimbing1);
                $matchedSheet->setCellValue("F{$matchedRowIndex}", $pembimbing2);

                if (!empty($judulSkripsi)) {
                    $matchedSheet->setCellValue("D{$matchedRowIndex}", $judulSkripsi);
                }
                if (!empty($namaMhs)) {
                    $matchedSheet->setCellValue("B{$matchedRowIndex}", $namaMhs);
                }

                Log::info("GoogleSheetKpiService [SuratTugas XLSX]: Berhasil mengisi Pembimbing I '{$pembimbing1}' dan Pembimbing II '{$pembimbing2}' pada baris {$matchedRowIndex} sheet '{$matchedSheet->getTitle()}' untuk NIM {$nimRaw}");
            } else {
                // Jika belum ada, buat baris baru di sheet angkatan (bila belum ada, otomatis buat sheet baru 'NIM + angkatan')
                $targetSheet = self::getOrCreateTargetSheet($spreadsheet, $nimRaw);

                $headerRowNumber = 1;
                for ($r = 1; $r <= 3; $r++) {
                    $colA = strtolower(trim((string)$targetSheet->getCell("A{$r}")->getFormattedValue()));
                    $colC = strtolower(trim((string)$targetSheet->getCell("C{$r}")->getFormattedValue()));
                    if (str_contains($colA, 'no') || str_contains($colC, 'nim')) {
                        $headerRowNumber = $r;
                        break;
                    }
                }

                $lastFilledRow = $headerRowNumber;
                $maxNo = 0;
                $highestRow = $targetSheet->getHighestRow();

                for ($r = $headerRowNumber + 1; $r <= max($highestRow, 1000); $r++) {
                    $valNo = trim((string)$targetSheet->getCell("A{$r}")->getFormattedValue());
                    $valNama = trim((string)$targetSheet->getCell("B{$r}")->getFormattedValue());
                    $valNim = trim((string)$targetSheet->getCell("C{$r}")->getFormattedValue());
                    $valJudul = trim((string)$targetSheet->getCell("D{$r}")->getFormattedValue());

                    if (!empty($valNama) || !empty($valNim) || !empty($valJudul) || !empty($valNo)) {
                        $lastFilledRow = $r;
                        $num = (int)preg_replace('/[^0-9]/', '', $valNo);
                        if ($num > $maxNo) {
                            $maxNo = $num;
                        }
                    }
                }

                $newRowIndex = $lastFilledRow + 1;
                $newNo = $maxNo > 0 ? ($maxNo + 1) : ($newRowIndex - $headerRowNumber);

                $targetSheet->setCellValue("A{$newRowIndex}", $newNo);
                $targetSheet->setCellValue("B{$newRowIndex}", $namaMhs);
                $targetSheet->setCellValue("C{$newRowIndex}", $nimRaw);
                $targetSheet->setCellValue("D{$newRowIndex}", $judulSkripsi);
                $targetSheet->setCellValue("E{$newRowIndex}", $pembimbing1);
                $targetSheet->setCellValue("F{$newRowIndex}", $pembimbing2);

                Log::info("GoogleSheetKpiService [SuratTugas XLSX]: Baris baru {$newRowIndex} ditambahkan di sheet '{$targetSheet->getTitle()}' dengan Pembimbing I '{$pembimbing1}' & Pembimbing II '{$pembimbing2}' untuk NIM {$nimRaw}");
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
            Log::error("GoogleSheetKpiService processPembimbingXlsxOnDrive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tambah baris baru via Google Sheets API (jika native Google Spreadsheet)
     */
    protected static function appendRowSheetsApi($pj, Client $client, string $fileId): bool
    {
        $service = new Sheets($client);
        $targetSheetTitle = self::getOrCreateSheetInSheetsApi($service, $fileId, (string)$pj->nim);

        $res = $service->spreadsheets_values->get($fileId, "'{$targetSheetTitle}'!A1:F1000");
        $rows = $res->getValues() ?? [];

        $cleanTargetNim = preg_replace('/[^0-9]/', '', (string)$pj->nim);
        $maxNo = 0;
        $lastFilledRow = 1;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 1;
            $valNo = trim((string)($row[0] ?? ''));
            $valNama = trim((string)($row[1] ?? ''));
            $valNim = trim((string)($row[2] ?? ''));
            $valJudul = trim((string)($row[3] ?? ''));

            $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);
            if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                // Update baris yang sudah ada
                $updateRange = "'{$targetSheetTitle}'!B{$rowNum}:D{$rowNum}";
                $updateBody = new ValueRange(['values' => [[$pj->nama_mhs, $pj->nim, $pj->judul_proposal_skripsi]]]);
                $service->spreadsheets_values->update($fileId, $updateRange, $updateBody, ['valueInputOption' => 'USER_ENTERED']);
                return true;
            }

            if (!empty($valNama) || !empty($valNim) || !empty($valJudul) || !empty($valNo)) {
                $lastFilledRow = $rowNum;
                $num = (int)preg_replace('/[^0-9]/', '', $valNo);
                if ($num > $maxNo) {
                    $maxNo = $num;
                }
            }
        }

        $newRowIndex = $lastFilledRow + 1;
        $newNo = $maxNo > 0 ? ($maxNo + 1) : $newRowIndex;

        $newRow = [
            $newNo,
            $pj->nama_mhs,
            $pj->nim,
            $pj->judul_proposal_skripsi,
            $pj->dosen_penasehat ?? '',
            ''
        ];

        $range = "'{$targetSheetTitle}'!A{$newRowIndex}:F{$newRowIndex}";
        $body = new ValueRange(['values' => [$newRow]]);
        $service->spreadsheets_values->update($fileId, $range, $body, ['valueInputOption' => 'USER_ENTERED']);

        return true;
    }

    /**
     * Update baris via Google Sheets API (jika native Google Spreadsheet)
     */
    protected static function updateRowSheetsApi($pj, Client $client, string $fileId): bool
    {
        return self::appendRowSheetsApi($pj, $client, $fileId);
    }

    /**
     * Update pembimbing via Google Sheets API (jika native Google Spreadsheet)
     */
    protected static function processPembimbingSheetsApi($st, Client $client, string $fileId): bool
    {
        $service = new Sheets($client);
        $spreadsheet = $service->spreadsheets->get($fileId);
        $nimRaw = (string)($st->nim_nik ?? $st->nim ?? '');
        $cleanTargetNim = preg_replace('/[^0-9]/', '', $nimRaw);
        $pembimbing1 = trim((string)($st->pembimbing1 ?? ''));
        $pembimbing2 = trim((string)($st->pembimbing2 ?? ''));
        $namaMhs = trim((string)($st->nama_mhs ?? ''));
        $judulSkripsi = trim((string)($st->judul_skripsi ?? ''));

        foreach ($spreadsheet->getSheets() as $sheet) {
            $sheetTitle = $sheet->getProperties()->getTitle();
            $res = $service->spreadsheets_values->get($fileId, "'{$sheetTitle}'!A1:F1000");
            $rows = $res->getValues() ?? [];

            foreach ($rows as $index => $row) {
                $rowNum = $index + 1;
                $cleanRowNim = preg_replace('/[^0-9]/', '', (string)($row[2] ?? ''));

                if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                    $updateRange = "'{$sheetTitle}'!E{$rowNum}:F{$rowNum}";
                    $body = new ValueRange(['values' => [[$pembimbing1, $pembimbing2]]]);
                    $service->spreadsheets_values->update($fileId, $updateRange, $body, ['valueInputOption' => 'USER_ENTERED']);

                    if (!empty($judulSkripsi)) {
                        $jRange = "'{$sheetTitle}'!D{$rowNum}";
                        $jBody = new ValueRange(['values' => [[$judulSkripsi]]]);
                        $service->spreadsheets_values->update($fileId, $jRange, $jBody, ['valueInputOption' => 'USER_ENTERED']);
                    }

                    return true;
                }
            }
        }

        // Jika belum ada di sheet manapun, buat baris baru di sheet angkatan
        try {
            $targetSheetTitle = self::getOrCreateSheetInSheetsApi($service, $fileId, $nimRaw);
            $res = $service->spreadsheets_values->get($fileId, "'{$targetSheetTitle}'!A1:F1000");
            $rows = $res->getValues() ?? [];
            $lastRow = count($rows) + 1;
            $maxNo = 0;
            foreach ($rows as $r) {
                $n = (int)preg_replace('/[^0-9]/', '', (string)($r[0] ?? ''));
                if ($n > $maxNo) $maxNo = $n;
            }
            $newNo = $maxNo > 0 ? ($maxNo + 1) : $lastRow;

            $newRow = [
                $newNo,
                $namaMhs,
                $nimRaw,
                $judulSkripsi,
                $pembimbing1,
                $pembimbing2
            ];
            $appendBody = new ValueRange(['values' => [$newRow]]);
            $service->spreadsheets_values->update($fileId, "'{$targetSheetTitle}'!A{$lastRow}:F{$lastRow}", $appendBody, ['valueInputOption' => 'USER_ENTERED']);
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetKpiService [Sheets API] append new pembimbing error: " . $e->getMessage());
            return false;
        }
    }
}

