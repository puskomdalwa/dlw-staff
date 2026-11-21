<?php

namespace App\Services;

use App\Models\DaftarSidangSkripsi;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

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
        $client = new Client();
        $client->setClientId(config('filesystems.disks.google.clientId'));
        $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
        $client->refreshToken(config('filesystems.disks.google.refreshToken'));
        $client->addScope(Sheets::SPREADSHEETS);
        $client->addScope('https://www.googleapis.com/auth/drive');
        return $client;
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

        $prodiStr = strtolower(trim((string) ($daftarSidang->prodi ?? '')));
        if (
            $prodiStr === 'hki' ||
            $prodiStr === 'as-hk' ||
            str_contains($prodiStr, 'hukum keluarga') ||
            str_contains($prodiStr, 'ahwal')
        ) {
            return true;
        }

        if ($daftarSidang instanceof DaftarSidangSkripsi) {
            $daftarSidang->loadMissing('prodiRel');
            if ($daftarSidang->prodiRel) {
                $alias = strtolower(trim((string) ($daftarSidang->prodiRel->alias ?? '')));
                $nama = strtolower(trim((string) ($daftarSidang->prodiRel->nama ?? '')));
                if (
                    $alias === 'hki' ||
                    $alias === 'as-hk' ||
                    str_contains($nama, 'hukum keluarga') ||
                    str_contains($nama, 'ahwal')
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function getSheetName(Sheets $service, string $fileId): string
    {
        try {
            $spreadsheet = $service->spreadsheets->get($fileId);
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ((int) $sheet->getProperties()->getSheetId() === self::$gid) {
                    return $sheet->getProperties()->getTitle();
                }
            }
            $sheets = $spreadsheet->getSheets();
            if (!empty($sheets)) {
                return $sheets[0]->getProperties()->getTitle();
            }
        } catch (\Throwable $e) {
            Log::warning('GoogleSheetSidangHkiService getSheetName fallback: ' . $e->getMessage());
        }

        return self::$defaultSheetTitle;
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
                } elseif ($h === 'nama' || str_contains($h, 'nama mahasiswa')) {
                    $row[] = (string) ($daftarSidang->nama ?? '');
                } elseif (str_contains($h, 'tempat') || str_contains($h, 'lahir') || $h === 'ttl') {
                    $row[] = $ttl !== '' ? $ttl : $existingVal;
                } elseif ($h === 'nim' || str_contains($h, 'nrim')) {
                    $row[] = self::formatNumericText((string) ($daftarSidang->nim ?? ''));
                } elseif (str_contains($h, 'nik') || str_contains($h, 'ktp')) {
                    $nik = (string) ($daftarSidang->nik ?? '');
                    $row[] = $nik !== '' ? self::formatNumericText($nik) : $existingVal;
                } elseif (str_contains($h, 'judul')) {
                    $row[] = (string) ($daftarSidang->judul_skripsi ?? '');
                } elseif (str_contains($h, 'periode')) {
                    $row[] = $periodeBulan;
                } elseif (str_contains($h, 'skripsi') && (str_contains($h, 'file') || str_contains($h, 'word'))) {
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

    public static function appendRow(DaftarSidangSkripsi $daftarSidang): bool
    {
        if (!self::isHki($daftarSidang)) {
            Log::info("GoogleSheetSidangHkiService: Bukan prodi HKI (ID {$daftarSidang->id}), sync dilewati.");
            return false;
        }

        try {
            $fileId = self::getSpreadsheetId();
            $client = self::getClient();
            $service = new Sheets($client);
            $sheetName = self::getSheetName($service, $fileId);
            $headers = self::getHeaders($service, $fileId, $sheetName);

            $row = self::buildRowData($daftarSidang, $headers);
            $body = new ValueRange(['values' => [$row]]);

            $colCount = max(count($headers), count($row));
            $lastColLetter = self::columnIndexToLetter($colCount);
            $allRows = $service->spreadsheets_values->get($fileId, "'{$sheetName}'!A:{$lastColLetter}")->getValues() ?? [];
            $nextRowIndex = self::findNextEmptyRowIndex($allRows);

            $targetColLetter = self::columnIndexToLetter(count($row));
            $targetRange = "'{$sheetName}'!A{$nextRowIndex}:{$targetColLetter}{$nextRowIndex}";

            $service->spreadsheets_values->update(
                $fileId,
                $targetRange,
                $body,
                ['valueInputOption' => 'USER_ENTERED']
            );

            Log::info("GoogleSheetSidangHkiService: Berhasil menambahkan baris ke-{$nextRowIndex} pada '{$sheetName}' untuk NIM {$daftarSidang->nim}");
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetSidangHkiService appendRow error: " . $e->getMessage());
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
            $service = new Sheets($client);
            $sheetName = self::getSheetName($service, $fileId);
            $headers = self::getHeaders($service, $fileId, $sheetName);

            // Cari index kolom NIM berdasarkan header (default index 3 = kolom D)
            $nimColIndex = 3;
            if (!empty($headers)) {
                foreach ($headers as $idx => $header) {
                    $h = strtolower(trim((string) $header));
                    if ($h === 'nim' || str_contains($h, 'nrim')) {
                        $nimColIndex = $idx;
                        break;
                    }
                }
            }

            $colCount = !empty($headers) ? count($headers) : 17;
            $lastHeaderLetter = self::columnIndexToLetter($colCount);
            $rows = $service->spreadsheets_values->get($fileId, "'{$sheetName}'!A:{$lastHeaderLetter}")->getValues() ?? [];

            $cleanNewNim = preg_replace('/[^0-9]/', '', (string) ($daftarSidang->nim ?? ''));
            $cleanOldNim = preg_replace('/[^0-9]/', '', (string) ($oldNim ?? ''));

            $targetRowIndex = null;
            $existingRow = [];

            foreach ($rows as $idx => $row) {
                if ($idx === 0) {
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
                    $targetRowIndex = $idx + 1; // 1-indexed
                    $existingRow = $row;
                    break;
                }
            }

            if (!$targetRowIndex) {
                return self::appendRow($daftarSidang);
            }

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
            Log::error("GoogleSheetSidangHkiService updateRow error: " . $e->getMessage());
            return false;
        }
    }
}
