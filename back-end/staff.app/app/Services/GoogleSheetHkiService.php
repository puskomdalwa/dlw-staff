<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class GoogleSheetHkiService
{
    /**
     * Spreadsheet ID dari link:
     * https://docs.google.com/spreadsheets/d/1zGBU8gxxW1nnwVoUC9PBVFT5boDpWXUy-1fDKv0Goj0/edit?gid=0#gid=0
     */
    public static string $spreadsheetId = '1zGBU8gxxW1nnwVoUC9PBVFT5boDpWXUy-1fDKv0Goj0';
    public static int $gid = 0;
    public static string $defaultSheetTitle = 'BANK JUDUL';

    /**
     * Dapatkan Spreadsheet ID (dari .env jika ada, atau default)
     */
    public static function getSpreadsheetId(): string
    {
        return env('GOOGLE_SHEET_HKI_ID') ?: self::$spreadsheetId;
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
     * Mengecek apakah data (PengajuanJudul / SuratTugas) merupakan Prodi HKI (Hukum Keluarga Islam)
     */
    public static function isHki($obj): bool
    {
        if (!$obj) {
            return false;
        }

        // Cek ID prodi (6 = Hukum Keluarga Islam / AS-HK)
        if (!empty($obj->prodi_id) && (int)$obj->prodi_id === 6) {
            return true;
        }

        // Cek teks string prodi
        $prodi = strtolower(trim((string)($obj->prodi ?? '')));
        if ($prodi === 'hki' || $prodi === 'as-hk' || str_contains($prodi, 'hukum keluarga') || str_contains($prodi, 'ahwal')) {
            return true;
        }

        // Cek relasi jika ada
        if (method_exists($obj, 'prodi') && $obj->prodi) {
            $alias = strtolower(trim((string)($obj->prodi->alias ?? '')));
            $nama = strtolower(trim((string)($obj->prodi->nama ?? '')));
            if ($alias === 'hki' || $alias === 'as-hk' || str_contains($nama, 'hukum keluarga') || str_contains($nama, 'ahwal')) {
                return true;
            }
        }

        if (method_exists($obj, 'prodiRelation') && $obj->prodiRelation) {
            $alias = strtolower(trim((string)($obj->prodiRelation->alias ?? '')));
            $nama = strtolower(trim((string)($obj->prodiRelation->nama ?? '')));
            if ($alias === 'hki' || $alias === 'as-hk' || str_contains($nama, 'hukum keluarga') || str_contains($nama, 'ahwal')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menambahkan baris baru dari proses Pengajuan Judul untuk HKI
     */
    public static function appendRow($pj): bool
    {
        if (!self::isHki($pj)) {
            Log::info("GoogleSheetHkiService: Bukan prodi HKI (ID {$pj->id}), sync dilewati.");
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
            Log::error("GoogleSheetHkiService appendRow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengupdate baris yang sudah ada dari proses Pengajuan Judul untuk HKI
     */
    public static function updateRow($pj): bool
    {
        if (!self::isHki($pj)) {
            Log::info("GoogleSheetHkiService: Bukan prodi HKI (ID {$pj->id}), sync dilewati.");
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
            Log::error("GoogleSheetHkiService updateRow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sinkronisasi PEMBIMBING 1 dan PEMBIMBING 2 dari proses Surat Tugas untuk HKI
     */
    public static function syncPembimbingFromSuratTugas($st): bool
    {
        if (!self::isHki($st)) {
            Log::info("GoogleSheetHkiService [SuratTugas]: Bukan prodi HKI (ID {$st->id}), sync dilewati.");
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
            Log::error("GoogleSheetHkiService syncPembimbingFromSuratTugas error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tambah baris baru via Google Sheets API (native Google Spreadsheet)
     */
    protected static function appendRowSheetsApi($pj, Client $client, string $fileId): bool
    {
        $service = new Sheets($client);
        $sheetTitle = self::getTargetSheetTitle($service, $fileId);

        // Ambil data baris mulai row 3 (setelah baris 2: header)
        $range = "'{$sheetTitle}'!A3:F";
        $response = $service->spreadsheets_values->get($fileId, $range);
        $rows = $response->getValues() ?? [];

        $targetRowNumber = null;
        $maxNo = 0;
        $lastRowWithNumber = 2;

        foreach ($rows as $idx => $r) {
            $currRow = 3 + $idx;
            $valNo = isset($r[0]) ? (int)preg_replace('/[^0-9]/', '', (string)$r[0]) : 0;
            $valNama = isset($r[1]) ? trim((string)$r[1]) : '';
            $valNim = isset($r[2]) ? trim((string)$r[2]) : '';
            $valJudul = isset($r[3]) ? trim((string)$r[3]) : '';

            if ($valNo > $maxNo) {
                $maxNo = $valNo;
            }

            if (!empty($r[0])) {
                $lastRowWithNumber = $currRow;
            }

            // Baris target pertama yang kolom nama, nim, dan judulnya masih kosong
            if (empty($valNama) && empty($valNim) && empty($valJudul) && !$targetRowNumber) {
                $targetRowNumber = $currRow;
            }
        }

        if (!$targetRowNumber) {
            $targetRowNumber = max($lastRowWithNumber + 1, 3 + count($rows));
        }

        $existingNo = null;
        if (isset($rows[$targetRowNumber - 3][0]) && !empty(trim((string)$rows[$targetRowNumber - 3][0]))) {
            $existingNo = trim((string)$rows[$targetRowNumber - 3][0]);
        }
        $noUrut = $existingNo ?: ($maxNo > 0 ? (string)($maxNo + 1) : (string)($targetRowNumber - 2));

        $namaMhs = strtoupper(trim((string)($pj->nama_mhs ?? '')));
        $nim = (string)($pj->nim ?? '');
        $judul = trim((string)($pj->judul_proposal_skripsi ?? ''));

        // Di sheet HKI: A=NO, B=NAMA, C=NIM, D=JUDUL, E=PEMBIMBING 1, F=PEMBIMBING 2
        // Saat pengajuan judul, PEMBIMBING 1 & 2 dikosongkan (diisi via Surat Tugas)
        $row = [
            $noUrut,
            $namaMhs,
            $nim,
            $judul,
            '', // PEMBIMBING 1
            '', // PEMBIMBING 2
        ];

        $body = new ValueRange(['values' => [$row]]);
        $targetRange = "'{$sheetTitle}'!A{$targetRowNumber}:F{$targetRowNumber}";
        $service->spreadsheets_values->update($fileId, $targetRange, $body, ['valueInputOption' => 'USER_ENTERED']);

        Log::info("GoogleSheetHkiService [Sheets API]: Berhasil menambahkan baris ke-{$targetRowNumber} pada tab '{$sheetTitle}' untuk NIM {$nim}");
        return true;
    }

    /**
     * Update baris via Google Sheets API (native Google Spreadsheet)
     */
    protected static function updateRowSheetsApi($pj, Client $client, string $fileId): bool
    {
        $service = new Sheets($client);
        $sheetTitle = self::getTargetSheetTitle($service, $fileId);

        $range = "'{$sheetTitle}'!A3:F";
        $response = $service->spreadsheets_values->get($fileId, $range);
        $rows = $response->getValues() ?? [];

        $cleanTargetNim = preg_replace('/[^0-9]/', '', (string)$pj->nim);
        $matchedRowNumber = null;

        foreach ($rows as $idx => $r) {
            $valNim = isset($r[2]) ? (string)$r[2] : '';
            $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);

            if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                $matchedRowNumber = 3 + $idx;
                break;
            }
        }

        if (!$matchedRowNumber) {
            return self::appendRowSheetsApi($pj, $client, $fileId);
        }

        $namaMhs = strtoupper(trim((string)($pj->nama_mhs ?? '')));
        $nim = (string)($pj->nim ?? '');
        $judul = trim((string)($pj->judul_proposal_skripsi ?? ''));

        // Update B (NAMA), C (NIM), D (JUDUL) tanpa mengubah PEMBIMBING 1 & 2
        $range = "'{$sheetTitle}'!B{$matchedRowNumber}:D{$matchedRowNumber}";
        $body = new ValueRange(['values' => [[$namaMhs, $nim, $judul]]]);
        $service->spreadsheets_values->update($fileId, $range, $body, ['valueInputOption' => 'USER_ENTERED']);

        Log::info("GoogleSheetHkiService [Sheets API]: Berhasil update baris ke-{$matchedRowNumber} pada tab '{$sheetTitle}' untuk NIM {$nim}");
        return true;
    }

    /**
     * Update PEMBIMBING 1 & 2 via Google Sheets API dari Surat Tugas
     */
    protected static function processPembimbingSheetsApi($st, Client $client, string $fileId): bool
    {
        $service = new Sheets($client);
        $sheetTitle = self::getTargetSheetTitle($service, $fileId);

        $nimRaw = (string)($st->nim_nik ?? '');
        $cleanTargetNim = preg_replace('/[^0-9]/', '', $nimRaw);
        $pembimbing1 = trim((string)($st->pembimbing1 ?? ''));
        $pembimbing2 = trim((string)($st->pembimbing2 ?? ''));
        $namaMhs = strtoupper(trim((string)($st->nama_mhs ?? '')));
        $judulSkripsi = trim((string)($st->judul_skripsi ?? ''));

        $range = "'{$sheetTitle}'!A3:F";
        $response = $service->spreadsheets_values->get($fileId, $range);
        $rows = $response->getValues() ?? [];

        $matchedRowNumber = null;
        $maxNo = 0;
        $firstEmptyRowNumber = null;
        $lastRowWithNumber = 2;

        foreach ($rows as $idx => $r) {
            $currRow = 3 + $idx;
            $valNo = isset($r[0]) ? (int)preg_replace('/[^0-9]/', '', (string)$r[0]) : 0;
            $valNama = isset($r[1]) ? trim((string)$r[1]) : '';
            $valNim = isset($r[2]) ? (string)$r[2] : '';
            $valJudul = isset($r[3]) ? trim((string)$r[3]) : '';

            if ($valNo > $maxNo) {
                $maxNo = $valNo;
            }

            if (!empty($r[0])) {
                $lastRowWithNumber = $currRow;
            }

            $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);
            if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                $matchedRowNumber = $currRow;
                break;
            }

            if (empty($valNama) && empty($valNim) && empty($valJudul) && !$firstEmptyRowNumber) {
                $firstEmptyRowNumber = $currRow;
            }
        }

        if ($matchedRowNumber) {
            // Update E (PEMBIMBING 1) dan F (PEMBIMBING 2)
            $range = "'{$sheetTitle}'!E{$matchedRowNumber}:F{$matchedRowNumber}";
            $body = new ValueRange(['values' => [[$pembimbing1, $pembimbing2]]]);
            $service->spreadsheets_values->update($fileId, $range, $body, ['valueInputOption' => 'USER_ENTERED']);

            // Jika judul atau nama kosong di baris tersebut, lengkapi
            if (!empty($judulSkripsi)) {
                $service->spreadsheets_values->update($fileId, "'{$sheetTitle}'!D{$matchedRowNumber}", new ValueRange(['values' => [[$judulSkripsi]]]), ['valueInputOption' => 'USER_ENTERED']);
            }
            if (!empty($namaMhs)) {
                $service->spreadsheets_values->update($fileId, "'{$sheetTitle}'!B{$matchedRowNumber}", new ValueRange(['values' => [[$namaMhs]]]), ['valueInputOption' => 'USER_ENTERED']);
            }

            Log::info("GoogleSheetHkiService [SuratTugas Sheets API]: Berhasil update PEMBIMBING 1 & 2 pada baris {$matchedRowNumber} tab '{$sheetTitle}' untuk NIM {$nimRaw}");
            return true;
        }

        // Jika mahasiswa belum ada di sheet, buat baris baru
        $targetRowNumber = $firstEmptyRowNumber ?: max($lastRowWithNumber + 1, 3 + count($rows));
        $existingNo = null;
        if (isset($rows[$targetRowNumber - 3][0]) && !empty(trim((string)$rows[$targetRowNumber - 3][0]))) {
            $existingNo = trim((string)$rows[$targetRowNumber - 3][0]);
        }
        $noUrut = $existingNo ?: ($maxNo > 0 ? (string)($maxNo + 1) : (string)($targetRowNumber - 2));

        $row = [
            $noUrut,
            $namaMhs,
            $nimRaw,
            $judulSkripsi,
            $pembimbing1,
            $pembimbing2,
        ];

        $range = "'{$sheetTitle}'!A{$targetRowNumber}:F{$targetRowNumber}";
        $body = new ValueRange(['values' => [$row]]);
        $service->spreadsheets_values->update($fileId, $range, $body, ['valueInputOption' => 'USER_ENTERED']);

        Log::info("GoogleSheetHkiService [SuratTugas Sheets API]: Berhasil append baris baru ke-{$targetRowNumber} tab '{$sheetTitle}' dengan PB.1 & PB.2");
        return true;
    }

    /**
     * Manipulasi file Excel (.xlsx) langsung di Google Drive untuk Pengajuan Judul HKI (fallback)
     */
    protected static function processXlsxOnDrive($pj, string $action, Drive $drive, string $fileId): bool
    {
        $tempPath = storage_path('app/temp_bank_judul_hki_' . uniqid() . '.xlsx');

        try {
            $response = $drive->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();
            file_put_contents($tempPath, $content);

            $spreadsheet = IOFactory::load($tempPath);
            $sheet = self::getXlsxTargetSheet($spreadsheet);

            $cleanTargetNim = preg_replace('/[^0-9]/', '', (string)$pj->nim);
            $targetRowIndex = null;
            $firstEmptyRowIndex = null;
            $maxNo = 0;
            $lastRowWithNo = 2;

            $highestRow = $sheet->getHighestRow();
            for ($r = 3; $r <= max($highestRow, 200); $r++) {
                $valNoStr = trim((string)$sheet->getCell("A{$r}")->getFormattedValue());
                $valNama = trim((string)$sheet->getCell("B{$r}")->getFormattedValue());
                $valNim = trim((string)$sheet->getCell("C{$r}")->getFormattedValue());
                $valJudul = trim((string)$sheet->getCell("D{$r}")->getFormattedValue());

                $num = (int)preg_replace('/[^0-9]/', '', $valNoStr);
                if ($num > $maxNo) {
                    $maxNo = $num;
                }
                if (!empty($valNoStr)) {
                    $lastRowWithNo = $r;
                }

                $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);
                if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                    $targetRowIndex = $r;
                    break;
                }

                if (empty($valNama) && empty($valNim) && empty($valJudul) && !$firstEmptyRowIndex) {
                    $firstEmptyRowIndex = $r;
                }
            }

            $namaMhs = strtoupper(trim((string)($pj->nama_mhs ?? '')));
            $judul = trim((string)($pj->judul_proposal_skripsi ?? ''));
            $nim = (string)($pj->nim ?? '');

            if ($action === 'update' && $targetRowIndex) {
                $sheet->setCellValue("B{$targetRowIndex}", $namaMhs);
                $sheet->setCellValue("C{$targetRowIndex}", $nim);
                $sheet->setCellValue("D{$targetRowIndex}", $judul);
                Log::info("GoogleSheetHkiService [XLSX]: Berhasil update baris {$targetRowIndex} sheet '{$sheet->getTitle()}' untuk NIM {$nim}");
            } else {
                $targetRow = $firstEmptyRowIndex ?: ($lastRowWithNo + 1);
                $existingValNo = trim((string)$sheet->getCell("A{$targetRow}")->getFormattedValue());
                $newNo = !empty($existingValNo) ? $existingValNo : ($maxNo > 0 ? ($maxNo + 1) : ($targetRow - 2));

                $sheet->setCellValue("A{$targetRow}", $newNo);
                $sheet->setCellValue("B{$targetRow}", $namaMhs);
                $sheet->setCellValue("C{$targetRow}", $nim);
                $sheet->setCellValue("D{$targetRow}", $judul);
                $sheet->setCellValue("E{$targetRow}", "");
                $sheet->setCellValue("F{$targetRow}", "");

                Log::info("GoogleSheetHkiService [XLSX]: Berhasil menambah baris {$targetRow} sheet '{$sheet->getTitle()}' untuk NIM {$nim}");
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
            Log::error("GoogleSheetHkiService processXlsxOnDrive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update PEMBIMBING 1 & 2 pada file XLSX di Google Drive (fallback)
     */
    protected static function processPembimbingXlsxOnDrive($st, Drive $drive, string $fileId): bool
    {
        $tempPath = storage_path('app/temp_st_hki_' . uniqid() . '.xlsx');

        try {
            $response = $drive->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();
            file_put_contents($tempPath, $content);

            $spreadsheet = IOFactory::load($tempPath);
            $sheet = self::getXlsxTargetSheet($spreadsheet);

            $nimRaw = (string)($st->nim_nik ?? '');
            $cleanTargetNim = preg_replace('/[^0-9]/', '', $nimRaw);
            $pembimbing1 = trim((string)($st->pembimbing1 ?? ''));
            $pembimbing2 = trim((string)($st->pembimbing2 ?? ''));
            $namaMhs = strtoupper(trim((string)($st->nama_mhs ?? '')));
            $judulSkripsi = trim((string)($st->judul_skripsi ?? ''));

            $targetRowIndex = null;
            $firstEmptyRowIndex = null;
            $maxNo = 0;
            $lastRowWithNo = 2;

            $highestRow = $sheet->getHighestRow();
            for ($r = 3; $r <= max($highestRow, 200); $r++) {
                $valNoStr = trim((string)$sheet->getCell("A{$r}")->getFormattedValue());
                $valNama = trim((string)$sheet->getCell("B{$r}")->getFormattedValue());
                $valNim = trim((string)$sheet->getCell("C{$r}")->getFormattedValue());
                $valJudul = trim((string)$sheet->getCell("D{$r}")->getFormattedValue());

                $num = (int)preg_replace('/[^0-9]/', '', $valNoStr);
                if ($num > $maxNo) {
                    $maxNo = $num;
                }
                if (!empty($valNoStr)) {
                    $lastRowWithNo = $r;
                }

                $cleanRowNim = preg_replace('/[^0-9]/', '', $valNim);
                if (!empty($cleanTargetNim) && !empty($cleanRowNim) && $cleanRowNim === $cleanTargetNim) {
                    $targetRowIndex = $r;
                    break;
                }

                if (empty($valNama) && empty($valNim) && empty($valJudul) && !$firstEmptyRowIndex) {
                    $firstEmptyRowIndex = $r;
                }
            }

            if ($targetRowIndex) {
                $sheet->setCellValue("E{$targetRowIndex}", $pembimbing1);
                $sheet->setCellValue("F{$targetRowIndex}", $pembimbing2);
                if (!empty($judulSkripsi)) {
                    $sheet->setCellValue("D{$targetRowIndex}", $judulSkripsi);
                }
                if (!empty($namaMhs)) {
                    $sheet->setCellValue("B{$targetRowIndex}", $namaMhs);
                }
                Log::info("GoogleSheetHkiService [SuratTugas XLSX]: Berhasil update PB.1 '{$pembimbing1}' & PB.2 '{$pembimbing2}' pada baris {$targetRowIndex} sheet '{$sheet->getTitle()}' untuk NIM {$nimRaw}");
            } else {
                $targetRow = $firstEmptyRowIndex ?: ($lastRowWithNo + 1);
                $existingValNo = trim((string)$sheet->getCell("A{$targetRow}")->getFormattedValue());
                $newNo = !empty($existingValNo) ? $existingValNo : ($maxNo > 0 ? ($maxNo + 1) : ($targetRow - 2));

                $sheet->setCellValue("A{$targetRow}", $newNo);
                $sheet->setCellValue("B{$targetRow}", $namaMhs);
                $sheet->setCellValue("C{$targetRow}", $nimRaw);
                $sheet->setCellValue("D{$targetRow}", $judulSkripsi);
                $sheet->setCellValue("E{$targetRow}", $pembimbing1);
                $sheet->setCellValue("F{$targetRow}", $pembimbing2);

                Log::info("GoogleSheetHkiService [SuratTugas XLSX]: Berhasil menambah baris baru {$targetRow} dengan PB.1 & PB.2 untuk NIM {$nimRaw}");
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
            Log::error("GoogleSheetHkiService processPembimbingXlsxOnDrive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dapatkan nama tab untuk spreadsheet HKI (default: 'BANK JUDUL')
     */
    protected static function getTargetSheetTitle(Sheets $service, string $fileId): string
    {
        try {
            $spreadsheet = $service->spreadsheets->get($fileId);
            foreach ($spreadsheet->getSheets() as $s) {
                $title = $s->getProperties()->getTitle();
                if (str_contains(strtoupper($title), 'JUDUL') || str_contains(strtoupper($title), 'BANK')) {
                    return $title;
                }
            }
            return $spreadsheet->getSheets()[0]->getProperties()->getTitle();
        } catch (\Throwable $e) {
            return self::$defaultSheetTitle;
        }
    }

    /**
     * Dapatkan worksheet target untuk file XLSX
     */
    protected static function getXlsxTargetSheet($spreadsheet)
    {
        foreach ($spreadsheet->getAllSheets() as $s) {
            $title = strtoupper(trim($s->getTitle()));
            if (str_contains($title, 'JUDUL') || str_contains($title, 'BANK')) {
                return $s;
            }
        }
        return $spreadsheet->getSheet(0);
    }
}

