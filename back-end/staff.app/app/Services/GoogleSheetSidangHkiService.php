<?php

namespace App\Services;

use App\Models\DaftarSidangSkripsi;
use App\Models\Prodi;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class GoogleSheetSidangHkiService
{
    /**
     * Spreadsheet ID dari link:
     * https://docs.google.com/spreadsheets/d/1KksM5PRhREoLlC-TCrm0o4TljgLkwZTNPL-6etmHfuQ/edit?usp=sharing
     */
    public static string $spreadsheetId = '1KksM5PRhREoLlC-TCrm0o4TljgLkwZTNPL-6etmHfuQ';
    public static int $gid = 1743810960;
    public static string $defaultSheetTitle = 'Form Responses 1';

    public static function getSpreadsheetId(): string
    {
        return env('GOOGLE_SHEET_SIDANG_HKI_ID') ?: self::$spreadsheetId;
    }

    protected static function getClient(): Client
    {
        $clientId = config('filesystems.disks.google.clientId');
        $clientSecret = config('filesystems.disks.google.clientSecret');
        $refreshToken = config('filesystems.disks.google.refreshToken');

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            throw new \RuntimeException('Konfigurasi Google Drive/Sheets (clientId/clientSecret/refreshToken) belum lengkap.');
        }

        $client = new Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->addScope(Sheets::SPREADSHEETS);
        $client->addScope('https://www.googleapis.com/auth/drive');
        $client->refreshToken($refreshToken);
        return $client;
    }

    /**
     * Mengecek apakah string teks merepresentasikan Prodi HKI.
     */
    public static function isHkiText(?string $text): bool
    {
        $lower = strtolower(trim((string) ($text ?? '')));
        if ($lower === '') {
            return false;
        }

        if (
            $lower === 'hki' ||
            $lower === 'as-hk' ||
            $lower === 'as-hki' ||
            $lower === 'as' ||
            str_contains($lower, 'hukum keluarga') ||
            str_contains($lower, 'ahwal') ||
            preg_match('/\bhki\b/i', $lower) === 1
        ) {
            return true;
        }

        return false;
    }

    /**
     * Menentukan prodi_id dari nama/alias prodi bila belum terisi atau bernilai 0.
     */
    public static function resolveProdiId(?string $prodiName, $prodiId = null): ?int
    {
        if (!empty($prodiId) && (int) $prodiId > 0) {
            return (int) $prodiId;
        }

        $cleanName = trim((string) ($prodiName ?? ''));
        if ($cleanName !== '') {
            $lower = strtolower($cleanName);
            $prodiModel = Prodi::whereRaw('LOWER(TRIM(nama)) = ?', [$lower])
                ->orWhereRaw('LOWER(TRIM(alias)) = ?', [$lower])
                ->first();

            if ($prodiModel) {
                return (int) $prodiModel->id;
            }

            if (self::isHkiText($cleanName)) {
                $hkiProdi = Prodi::find(6) ?: Prodi::whereRaw('LOWER(TRIM(alias)) IN (?, ?)', ['as-hk', 'hki'])->first();
                return $hkiProdi ? (int) $hkiProdi->id : 6;
            }
        }

        return null;
    }

    /**
     * Mengecek apakah data pendaftaran sidang skripsi merupakan Prodi HKI.
     */
    public static function isHki($daftarSidang): bool
    {
        if (!$daftarSidang) {
            return false;
        }

        if (!empty($daftarSidang->prodi_id) && (int) $daftarSidang->prodi_id === 6) {
            return true;
        }

        if (self::isHkiText($daftarSidang->prodi ?? null)) {
            return true;
        }

        if ($daftarSidang instanceof DaftarSidangSkripsi) {
            $daftarSidang->loadMissing('prodiRel');
            if ($daftarSidang->prodiRel) {
                if (
                    self::isHkiText($daftarSidang->prodiRel->alias ?? null) ||
                    self::isHkiText($daftarSidang->prodiRel->nama ?? null)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function getTargetSheetInfo(Sheets $service, string $fileId): array
    {
        try {
            $spreadsheet = $service->spreadsheets->get($fileId);
            foreach ($spreadsheet->getSheets() as $sheet) {
                $props = $sheet->getProperties();
                if ((int) $props->getSheetId() === self::$gid) {
                    return [
                        'title' => $props->getTitle(),
                        'rowCount' => (int) ($props->getGridProperties()?->getRowCount() ?? 1000),
                    ];
                }
            }
            $sheets = $spreadsheet->getSheets();
            if (!empty($sheets)) {
                $props = $sheets[0]->getProperties();
                return [
                    'title' => $props->getTitle(),
                    'rowCount' => (int) ($props->getGridProperties()?->getRowCount() ?? 1000),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('GoogleSheetSidangHkiService getTargetSheetInfo fallback: ' . $e->getMessage());
        }

        return [
            'title' => self::$defaultSheetTitle,
            'rowCount' => 1000,
        ];
    }

    protected static function getHeaders(Sheets $service, string $fileId, string $sheetName): array
    {
        try {
            $response = $service->spreadsheets_values->get($fileId, "'{$sheetName}'!1:1");
            $rows = $response->getValues();
            return !empty($rows[0]) ? $rows[0] : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected static function isNimHeader(string $normalizedHeader): bool
    {
        return $normalizedHeader === 'nim'
            || str_contains($normalizedHeader, 'nrim')
            || preg_match('/\bnim\b/', $normalizedHeader) === 1;
    }

    protected static function columnIndexToLetter(int $colNumber): string
    {
        $letter = '';
        while ($colNumber > 0) {
            $colNumber--;
            $letter = chr(65 + ($colNumber % 26)) . $letter;
            $colNumber = intdiv($colNumber, 26);
        }
        return $letter ?: 'A';
    }

    protected static function formatNumericText(?string $value): string
    {
        $val = trim((string) ($value ?? ''));
        if ($val === '') {
            return '';
        }
        // Cegah leading zero hilang atau angka 15+ digit (seperti NIK 16 digit) menjadi notasi ilmiah
        if (ctype_digit($val) && (str_starts_with($val, '0') || strlen($val) >= 15)) {
            return "'" . $val;
        }
        return $val;
    }

    protected static function formatPeriodeBulan($periodeSidang): string
    {
        if (empty($periodeSidang)) {
            return '';
        }

        try {
            $formatted = Carbon::parse($periodeSidang)->locale('id')->translatedFormat('F');
            $upper = strtoupper(trim($formatted));
            $map = [
                'JANUARY'   => 'JANUARI',
                'FEBRUARY'  => 'FEBRUARI',
                'MARCH'     => 'MARET',
                'MAY'       => 'MEI',
                'JUNE'      => 'JUNI',
                'JULY'      => 'JULI',
                'AUGUST'    => 'AGUSTUS',
                'OCTOBER'   => 'OKTOBER',
                'DECEMBER'  => 'DESEMBER',
                'MEY'       => 'MEI',
            ];
            return $map[$upper] ?? $upper;
        } catch (\Throwable $e) {
            return strtoupper((string) $periodeSidang);
        }
    }

    protected static function extractDocLinks(DaftarSidangSkripsi $daftarSidang): array
    {
        $daftarSidang->loadMissing('dokumen');

        $links = [
            'skripsi' => '',
            'foto'    => '',
            'dospem1' => '',
            'dospem2' => '',
            'nota'    => '',
            'ijazah'  => '',
        ];

        foreach ($daftarSidang->dokumen as $doc) {
            $link = trim((string) ($doc->drive_link ?? ''));
            if ($link === '') {
                continue;
            }
            $name = strtolower(trim((string) ($doc->nama_dokumen ?? '')));

            if ((str_contains($name, 'konsultasi') || str_contains($name, 'dospem')) && str_contains($name, '1')) {
                $links['dospem1'] = $link;
            } elseif ((str_contains($name, 'konsultasi') || str_contains($name, 'dospem')) && str_contains($name, '2')) {
                $links['dospem2'] = $link;
            } elseif (str_contains($name, 'foto') || str_contains($name, '3x4') || str_contains($name, 'pasfoto')) {
                $links['foto'] = $link;
            } elseif (str_contains($name, 'nota') || str_contains($name, 'acc')) {
                $links['nota'] = $link;
            } elseif (str_contains($name, 'ijazah')) {
                $links['ijazah'] = $link;
            } elseif (str_contains($name, 'skripsi') || str_contains($name, 'naskah')) {
                $links['skripsi'] = $link;
            }
        }

        return $links;
    }

    protected static function fetchMahasiswaExtra(string $nim): array
    {
        $extra = ['ttl' => '', 'no_hp' => ''];
        if (trim($nim) === '') {
            return $extra;
        }

        try {
            $mhs = Mahasiswa::nim($nim);
            if (is_array($mhs)) {
                $mhs = $mhs[0] ?? null;
            }
            if (is_object($mhs)) {
                $tempat = trim((string) ($mhs->tempat_lahir ?? ''));
                $tglRaw = trim((string) ($mhs->tanggal_lahir ?? ''));
                $tglFormatted = $tglRaw !== '' ? SuratService::formatTanggalIndonesian($tglRaw) : '';
                if ($tempat !== '' && $tglFormatted !== '') {
                    $extra['ttl'] = "{$tempat}, {$tglFormatted}";
                } elseif ($tempat !== '') {
                    $extra['ttl'] = $tempat;
                } elseif ($tglFormatted !== '') {
                    $extra['ttl'] = $tglFormatted;
                }

                $extra['no_hp'] = trim((string) ($mhs->hp ?? $mhs->no_hp ?? $mhs->telepon ?? $mhs->no_telp ?? ''));
            }
        } catch (\Throwable $e) {
            Log::warning("GoogleSheetSidangHkiService fetchMahasiswaExtra failed for NIM {$nim}: " . $e->getMessage());
        }

        return $extra;
    }

    public static function buildRowData(DaftarSidangSkripsi $daftarSidang, array $headers = [], array $existingRow = []): array
    {
        $timestamp = !empty($existingRow[0]) ? $existingRow[0] : date('n/j/Y H:i:s');
        $periodeBulan = self::formatPeriodeBulan($daftarSidang->periode_sidang);
        $docLinks = self::extractDocLinks($daftarSidang);

        $existingTtl = trim((string) ($existingRow[2] ?? ''));
        $existingHp = trim((string) ($existingRow[15] ?? ''));
        $extra = ($existingTtl === '' || $existingHp === '')
            ? self::fetchMahasiswaExtra((string) $daftarSidang->nim)
            : ['ttl' => $existingTtl, 'no_hp' => $existingHp];

        $ttl = $extra['ttl'] !== '' ? $extra['ttl'] : $existingTtl;
        $noHp = $extra['no_hp'] !== '' ? $extra['no_hp'] : $existingHp;

        if (!empty($headers)) {
            $row = [];
            foreach ($headers as $index => $header) {
                $h = strtolower(trim((string) $header));
                $existingVal = $existingRow[$index] ?? '';

                if (str_contains($h, 'timestamp') || str_contains($h, 'waktu')) {
                    $row[] = $timestamp;
                } elseif ($h === 'nama' || str_contains($h, 'nama mahasiswa') || str_contains($h, 'nama lengkap')) {
                    $row[] = (string) ($daftarSidang->nama ?? '');
                } elseif (str_contains($h, 'tempat') || str_contains($h, 'lahir') || $h === 'ttl') {
                    $row[] = $ttl !== '' ? $ttl : $existingVal;
                } elseif (self::isNimHeader($h)) {
                    $row[] = self::formatNumericText((string) ($daftarSidang->nim ?? ''));
                } elseif (str_contains($h, 'nik') || str_contains($h, 'ktp')) {
                    $nik = (string) ($daftarSidang->nik ?? '');
                    $row[] = $nik !== '' ? self::formatNumericText($nik) : $existingVal;
                } elseif (str_contains($h, 'judul')) {
                    $row[] = (string) ($daftarSidang->judul_skripsi ?? '');
                } elseif (str_contains($h, 'periode')) {
                    $row[] = $periodeBulan;
                } elseif (str_contains($h, 'skripsi')) {
                    $row[] = $docLinks['skripsi'] !== '' ? $docLinks['skripsi'] : $existingVal;
                } elseif (str_contains($h, 'foto') || str_contains($h, '3x4')) {
                    $row[] = $docLinks['foto'] !== '' ? $docLinks['foto'] : $existingVal;
                } elseif (str_contains($h, 'dospem 1') || (str_contains($h, 'konsultasi') && str_contains($h, '1'))) {
                    $row[] = $docLinks['dospem1'] !== '' ? $docLinks['dospem1'] : $existingVal;
                } elseif (str_contains($h, 'dospem 2') || (str_contains($h, 'konsultasi') && str_contains($h, '2'))) {
                    $row[] = $docLinks['dospem2'] !== '' ? $docLinks['dospem2'] : $existingVal;
                } elseif (str_contains($h, 'nota') || str_contains($h, 'acc')) {
                    $row[] = $docLinks['nota'] !== '' ? $docLinks['nota'] : $existingVal;
                } elseif (str_contains($h, 'pembimbing 1')) {
                    $p1 = trim((string) ($daftarSidang->pembimbing1 ?? ''));
                    $row[] = $p1 !== '' ? $p1 : $existingVal;
                } elseif (str_contains($h, 'pembimbing 2')) {
                    $p2 = trim((string) ($daftarSidang->pembimbing2 ?? ''));
                    $row[] = $p2 !== '' ? $p2 : $existingVal;
                } elseif (str_contains($h, 'hp') || str_contains($h, 'telepon') || str_contains($h, 'telp')) {
                    $row[] = $noHp !== '' ? self::formatNumericText($noHp) : $existingVal;
                } elseif (str_contains($h, 'ijazah')) {
                    $row[] = $docLinks['ijazah'] !== '' ? $docLinks['ijazah'] : $existingVal;
                } else {
                    $row[] = $existingVal;
                }
            }
            return $row;
        }

        // Fallback urutan kolom A-Q sesuai struktur Form Responses 1
        return [
            $timestamp,
            (string) ($daftarSidang->nama ?? ''),
            $ttl,
            self::formatNumericText((string) ($daftarSidang->nim ?? '')),
            self::formatNumericText((string) ($daftarSidang->nik ?? '')),
            (string) ($daftarSidang->judul_skripsi ?? ''),
            $periodeBulan,
            $docLinks['skripsi'],
            $docLinks['foto'],
            $docLinks['dospem1'],
            '',
            $docLinks['dospem2'],
            $docLinks['nota'],
            (string) ($daftarSidang->pembimbing1 ?? ''),
            (string) ($daftarSidang->pembimbing2 ?? ''),
            self::formatNumericText($noHp),
            $docLinks['ijazah'],
        ];
    }

    /**
     * Cari nomor baris kosong berikutnya (1-indexed, minimal baris 2)
     * dengan memeriksa seluruh kolom pada setiap baris agar tidak pernah menimpa baris berisi data.
     */
    public static function findNextEmptyRowIndex(array $rows): int
    {
        $lastNonEmptyRow = 1; // Baris 1 adalah header
        foreach ($rows as $idx => $r) {
            $rowNumber = $idx + 1;
            if (!is_array($r)) {
                continue;
            }
            foreach ($r as $cell) {
                if (trim((string) $cell) !== '') {
                    $lastNonEmptyRow = $rowNumber;
                    break;
                }
            }
        }

        return max($lastNonEmptyRow + 1, 2);
    }

    protected static function isXlsxFile(Client $client, string $fileId): bool
    {
        try {
            $drive = new Drive($client);
            $file = $drive->files->get($fileId, ['fields' => 'id, name, mimeType']);
            $mimeType = (string) $file->getMimeType();
            $name = strtolower((string) $file->getName());
            return str_contains($mimeType, 'spreadsheetml') || str_ends_with($name, '.xlsx');
        } catch (\Throwable $e) {
            Log::warning("GoogleSheetSidangHkiService check mimeType failed: " . $e->getMessage());
            return false;
        }
    }

    public static function appendRow(DaftarSidangSkripsi $daftarSidang): bool
    {
        if (!self::isHki($daftarSidang)) {
            Log::info("GoogleSheetSidangHkiService: Bukan prodi HKI (ID {$daftarSidang->id}), sync dilewati.");
            return false;
        }

        try {
            $fileId = self::getSpreadsheetId();
            $client = self::getClient();

            if (self::isXlsxFile($client, $fileId)) {
                return self::processXlsxOnDrive($daftarSidang, 'append', new Drive($client), $fileId);
            }

            $service = new Sheets($client);
            $sheetInfo = self::getTargetSheetInfo($service, $fileId);
            $sheetName = $sheetInfo['title'];
            $headers = self::getHeaders($service, $fileId, $sheetName);

            $row = self::buildRowData($daftarSidang, $headers);
            $body = new ValueRange(['values' => [$row]]);

            $colCount = max(count($headers), count($row));
            $lastColLetter = self::columnIndexToLetter($colCount);
            $allRows = $service->spreadsheets_values->get($fileId, "'{$sheetName}'!A:{$lastColLetter}")->getValues() ?? [];
            $nextRowIndex = self::findNextEmptyRowIndex($allRows);

            if ($nextRowIndex <= $sheetInfo['rowCount']) {
                $targetColLetter = self::columnIndexToLetter(count($row));
                $targetRange = "'{$sheetName}'!A{$nextRowIndex}:{$targetColLetter}{$nextRowIndex}";

                $service->spreadsheets_values->update(
                    $fileId,
                    $targetRange,
                    $body,
                    ['valueInputOption' => 'USER_ENTERED']
                );
            } else {
                $service->spreadsheets_values->append(
                    $fileId,
                    "'{$sheetName}'!A1",
                    $body,
                    ['valueInputOption' => 'USER_ENTERED', 'insertDataOption' => 'INSERT_ROWS']
                );
            }

            Log::info("GoogleSheetSidangHkiService: Berhasil menambahkan baris ke-{$nextRowIndex} pada '{$sheetName}' untuk NIM {$daftarSidang->nim}");
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetSidangHkiService appendRow error: " . $e->getMessage(), [
                'file_id' => self::getSpreadsheetId(),
                'daftar_sidang_id' => $daftarSidang->id ?? null,
                'nim' => $daftarSidang->nim ?? null,
                'prodi' => $daftarSidang->prodi ?? null,
                'prodi_id' => $daftarSidang->prodi_id ?? null,
            ]);
            return false;
        }
    }

    public static function updateRow(DaftarSidangSkripsi $daftarSidang, ?string $oldNim = null): bool
    {
        if (!self::isHki($daftarSidang)) {
            Log::info("GoogleSheetSidangHkiService: Bukan prodi HKI (ID {$daftarSidang->id}), sync update dilewati.");
            return false;
        }

        try {
            $fileId = self::getSpreadsheetId();
            $client = self::getClient();

            if (self::isXlsxFile($client, $fileId)) {
                return self::processXlsxOnDrive($daftarSidang, 'update', new Drive($client), $fileId, $oldNim);
            }

            $service = new Sheets($client);
            $sheetInfo = self::getTargetSheetInfo($service, $fileId);
            $sheetName = $sheetInfo['title'];
            $headers = self::getHeaders($service, $fileId, $sheetName);

            $nimColIndex = self::findNimColumnIndex($headers);
            $colCount = !empty($headers) ? count($headers) : 17;
            $lastHeaderLetter = self::columnIndexToLetter($colCount);
            $rows = $service->spreadsheets_values->get($fileId, "'{$sheetName}'!A:{$lastHeaderLetter}")->getValues() ?? [];

            $matched = self::findRowByNim($rows, $nimColIndex, $daftarSidang->nim, $oldNim);
            if (!$matched) {
                return self::appendRow($daftarSidang);
            }

            $targetRowIndex = $matched['rowIndex'];
            $existingRow = $matched['row'];

            $updatedRow = self::buildRowData($daftarSidang, $headers, $existingRow);
            $body = new ValueRange(['values' => [$updatedRow]]);

            $lastColLetter = self::columnIndexToLetter(count($updatedRow));
            $updateRange = "'{$sheetName}'!A{$targetRowIndex}:{$lastColLetter}{$targetRowIndex}";

            $service->spreadsheets_values->update(
                $fileId,
                $updateRange,
                $body,
                ['valueInputOption' => 'USER_ENTERED']
            );

            Log::info("GoogleSheetSidangHkiService: Berhasil update baris ke-{$targetRowIndex} pada '{$sheetName}' untuk NIM {$daftarSidang->nim}");
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetSidangHkiService updateRow error: " . $e->getMessage(), [
                'file_id' => self::getSpreadsheetId(),
                'daftar_sidang_id' => $daftarSidang->id ?? null,
                'nim' => $daftarSidang->nim ?? null,
            ]);
            return false;
        }
    }

    protected static function findNimColumnIndex(array $headers): int
    {
        foreach ($headers as $idx => $header) {
            if (self::isNimHeader(strtolower(trim((string) $header)))) {
                return $idx;
            }
        }
        return 3; // Default kolom D
    }

    protected static function findRowByNim(array $rows, int $nimColIndex, ?string $newNim, ?string $oldNim = null): ?array
    {
        $cleanNewNim = preg_replace('/[^0-9]/', '', (string) ($newNim ?? ''));
        $cleanOldNim = preg_replace('/[^0-9]/', '', (string) ($oldNim ?? ''));

        foreach ($rows as $idx => $row) {
            if ($idx === 0 || !is_array($row)) {
                continue; // Baris header
            }
            $rowNimClean = preg_replace('/[^0-9]/', '', (string) ($row[$nimColIndex] ?? ''));
            if ($rowNimClean === '') {
                continue;
            }
            if (
                ($cleanNewNim !== '' && $rowNimClean === $cleanNewNim) ||
                ($cleanOldNim !== '' && $rowNimClean === $cleanOldNim)
            ) {
                return [
                    'rowIndex' => $idx + 1, // 1-indexed
                    'row' => $row,
                ];
            }
        }

        return null;
    }

    /**
     * Fallback jika file spreadsheet di Google Drive masih berformat .xlsx.
     */
    protected static function processXlsxOnDrive(
        DaftarSidangSkripsi $daftarSidang,
        string $action,
        Drive $drive,
        string $fileId,
        ?string $oldNim = null
    ): bool {
        $tempPath = storage_path('app/temp_sidang_hki_' . uniqid() . '.xlsx');

        try {
            $response = $drive->files->get($fileId, ['alt' => 'media']);
            file_put_contents($tempPath, $response->getBody()->getContents());

            $spreadsheet = IOFactory::load($tempPath);
            $sheet = $spreadsheet->getSheetByName(self::$defaultSheetTitle) ?: $spreadsheet->getSheet(0);
            $rows = $sheet->toArray(null, true, false, false);
            $headers = !empty($rows[0]) ? array_map(fn ($v) => (string) ($v ?? ''), $rows[0]) : [];

            $targetRowIndex = null;
            $existingRow = [];

            if ($action === 'update') {
                $nimColIndex = self::findNimColumnIndex($headers);
                $matched = self::findRowByNim($rows, $nimColIndex, $daftarSidang->nim, $oldNim);
                if ($matched) {
                    $targetRowIndex = $matched['rowIndex'];
                    $existingRow = $matched['row'];
                }
            }

            if (!$targetRowIndex) {
                $targetRowIndex = self::findNextEmptyRowIndex($rows);
            }

            $rowValues = self::buildRowData($daftarSidang, $headers, $existingRow);
            foreach ($rowValues as $colIdx => $val) {
                $colLetter = self::columnIndexToLetter($colIdx + 1);
                $cellVal = is_string($val) && str_starts_with($val, "'") ? substr($val, 1) : $val;
                $sheet->setCellValueExplicit(
                    "{$colLetter}{$targetRowIndex}",
                    (string) $cellVal,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }

            $writer = new XlsxWriter($spreadsheet);
            $writer->save($tempPath);

            $drive->files->update($fileId, new \Google\Service\Drive\DriveFile(), [
                'data' => file_get_contents($tempPath),
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploadType' => 'multipart',
            ]);

            @unlink($tempPath);
            Log::info("GoogleSheetSidangHkiService [XLSX]: Berhasil {$action} baris ke-{$targetRowIndex} untuk NIM {$daftarSidang->nim}");
            return true;
        } catch (\Throwable $e) {
            @unlink($tempPath);
            Log::error("GoogleSheetSidangHkiService processXlsxOnDrive error: " . $e->getMessage());
            return false;
        }
    }
}
