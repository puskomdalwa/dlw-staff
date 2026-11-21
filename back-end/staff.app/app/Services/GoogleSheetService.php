<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    // Spreadsheet ID dari link: https://docs.google.com/spreadsheets/d/1yz0SwLCZ3MCpmOxi6CLAyrh-tf-iRCvYxwnSY2pe9gI/edit?usp=sharing
    public static string $spreadsheetId = '1yz0SwLCZ3MCpmOxi6CLAyrh-tf-iRCvYxwnSY2pe9gI';
    public static int $gid = 0;

    /**
     * Inisialisasi Google Client menggunakan kredensial yang sudah ada di .env
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
     * Dapatkan nama tab/sheet berdasarkan GID (default 0)
     */
    public static function getSheetName(Sheets $service): string
    {
        try {
            $spreadsheet = $service->spreadsheets->get(self::$spreadsheetId);
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getSheetId() == self::$gid) {
                    return $sheet->getProperties()->getTitle();
                }
            }
            return $spreadsheet->getSheets()[0]->getProperties()->getTitle();
        } catch (\Throwable $e) {
            return 'Sheet1';
        }
    }

    /**
     * Dapatkan header kolom dari baris 1 spreadsheet
     */
    public static function getHeaders(Sheets $service, string $sheetName): array
    {
        try {
            $response = $service->spreadsheets_values->get(self::$spreadsheetId, "'{$sheetName}'!1:1");
            $rows = $response->getValues();
            return !empty($rows[0]) ? $rows[0] : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Konversi index kolom (1-based) menjadi huruf kolom Excel/Google Sheet (1 -> A, 6 -> F, 27 -> AA)
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
     * Susun array data baris berdasarkan header yang ada di spreadsheet
     * Mendukung header: Timestamp, NIM, NAMA, TANGGAL SIDANG, NO HP, JUDUL, PRODI, DOSEN PEMBIMBING
     */
    public static function buildRowData($form, array $headers = [], array $existingRow = []): array
    {
        $timestamp = !empty($existingRow[0]) ? $existingRow[0] : date('d/m/Y H:i:s');

        $tglSidang = '';
        if (!empty($form->tanggal_sidang)) {
            $parsedDate = date_create($form->tanggal_sidang);
            $tglSidang = $parsedDate ? $parsedDate->format('d/m/Y') : (string) $form->tanggal_sidang;
        }

        // Jika sheet memiliki header di baris 1, petakan secara dinamis berdasarkan nama kolom
        if (!empty($headers)) {
            $row = [];
            foreach ($headers as $index => $header) {
                $h = strtolower(trim((string)$header));
                if (str_contains($h, 'time') || str_contains($h, 'waktu')) {
                    $row[] = $timestamp;
                } elseif (str_contains($h, 'nim') || str_contains($h, 'nrim')) {
                    $nim = (string) ($form->nim ?? '');
                    $row[] = (!empty($nim) && str_starts_with($nim, '0')) ? "'" . $nim : $nim;
                } elseif (str_contains($h, 'nama') || str_contains($h, 'mhs')) {
                    $row[] = (string) ($form->nama_mhs ?? '');
                } elseif (str_contains($h, 'sidang') || str_contains($h, 'tanggal') || str_contains($h, 'tgl')) {
                    $row[] = $tglSidang;
                } elseif (str_contains($h, 'hp') || str_contains($h, 'telepon') || str_contains($h, 'wa') || str_contains($h, 'telp')) {
                    $hp = (string) ($form->no_hp ?? '');
                    $row[] = (!empty($hp) && str_starts_with($hp, '0')) ? "'" . $hp : $hp;
                } elseif (str_contains($h, 'judul') || str_contains($h, 'skripsi')) {
                    $row[] = (string) ($form->judul_skripsi ?? '');
                } elseif (str_contains($h, 'prodi') || str_contains($h, 'jurusan') || str_contains($h, 'program studi')) {
                    $row[] = (string) ($form->prodi ?? '');
                } elseif (str_contains($h, 'pembimbing') || str_contains($h, 'dosen')) {
                    $row[] = (string) ($form->dosen_pembimbing ?? '');
                } elseif (str_contains($h, 'bebas') || str_contains($h, 'tanggungan')) {
                    $row[] = $existingRow[$index] ?? '';
                } else {
                    $row[] = $existingRow[$index] ?? '';
                }
            }
            return $row;
        }

        // Default urutan jika header tidak terbaca: Timestamp, NIM, NAMA, TANGGAL SIDANG, NO HP, JUDUL
        $nim = (string) ($form->nim ?? '');
        $hp = (string) ($form->no_hp ?? '');
        return [
            $timestamp,
            (!empty($nim) && str_starts_with($nim, '0')) ? "'" . $nim : $nim,
            (string) ($form->nama_mhs ?? ''),
            $tglSidang,
            (!empty($hp) && str_starts_with($hp, '0')) ? "'" . $hp : $hp,
            (string) ($form->judul_skripsi ?? ''),
        ];
    }

    /**
     * Menambahkan baris baru saat store data (selalu di baris kosong berikutnya, tidak menimpa)
     */
    public static function appendRow($form): bool
    {
        try {
            $client = self::getClient();
            $service = new Sheets($client);
            $sheetName = self::getSheetName($service);
            $headers = self::getHeaders($service, $sheetName);

            $row = self::buildRowData($form, $headers);

            $body = new ValueRange([
                'values' => [$row]
            ]);

            $params = ['valueInputOption' => 'USER_ENTERED'];

            // Hitung nomor baris kosong berikutnya secara eksplisit dari kolom A
            $existingRows = $service->spreadsheets_values->get(self::$spreadsheetId, "'{$sheetName}'!A:A")->getValues() ?? [];
            $nextRowIndex = count($existingRows) + 1;

            // Jika sheet masih kosong (belum ada header), data ditaruh mulai baris 2
            if ($nextRowIndex < 2) {
                $nextRowIndex = 2;
            }

            $lastColLetter = self::columnIndexToLetter(count($row));
            $targetRange = "'{$sheetName}'!A{$nextRowIndex}:{$lastColLetter}{$nextRowIndex}";

            $service->spreadsheets_values->update(
                self::$spreadsheetId,
                $targetRange,
                $body,
                $params
            );

            Log::info("GoogleSheetService: Berhasil menambahkan baris baru ke-{$nextRowIndex} untuk NIM {$form->nim} (Nama: {$form->nama_mhs})");
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetService appendRow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengupdate baris yang sudah ada berdasarkan NIM saat update data
     */
    public static function updateRow($form): bool
    {
        try {
            $client = self::getClient();
            $service = new Sheets($client);
            $sheetName = self::getSheetName($service);
            $headers = self::getHeaders($service, $sheetName);

            // Tentukan index kolom NIM berdasarkan header (default index 1 = kolom B)
            $nimColIndex = 1;
            if (!empty($headers)) {
                foreach ($headers as $idx => $header) {
                    $h = strtolower(trim((string)$header));
                    if (str_contains($h, 'nim') || str_contains($h, 'nrim')) {
                        $nimColIndex = $idx;
                        break;
                    }
                }
            }
            // Ambil semua data baris yang ada di spreadsheet
            $lastHeaderLetter = self::columnIndexToLetter(!empty($headers) ? count($headers) : 10);
            $range = "'{$sheetName}'!A:{$lastHeaderLetter}";
            $response = $service->spreadsheets_values->get(self::$spreadsheetId, $range);
            $rows = $response->getValues();

            $targetRowIndex = null;
            $existingRow = [];
            if (!empty($rows)) {
                foreach ($rows as $index => $row) {
                    if ($index === 0) {
                        continue; // Lewati baris header
                    }
                    $rowNim = $row[$nimColIndex] ?? '';
                    if (!empty($form->nim) && trim((string)$rowNim) === trim((string)$form->nim)) {
                        $targetRowIndex = $index + 1; // 1-indexed untuk Google Sheets
                        $existingRow = $row;
                        break;
                    }
                }
            }

            // Jika belum ada di spreadsheet, tambahkan sebagai baris baru
            if (!$targetRowIndex) {
                return self::appendRow($form);
            }

            $updatedRow = self::buildRowData($form, $headers, $existingRow);

            $body = new ValueRange([
                'values' => [$updatedRow]
            ]);

            $lastColLetter = self::columnIndexToLetter(count($updatedRow));
            $updateRange = "'{$sheetName}'!A{$targetRowIndex}:{$lastColLetter}{$targetRowIndex}";
            $params = ['valueInputOption' => 'USER_ENTERED'];

            $service->spreadsheets_values->update(
                self::$spreadsheetId,
                $updateRange,
                $body,
                $params
            );
            Log::info("GoogleSheetService: Berhasil update baris {$targetRowIndex} untuk NIM {$form->nim}");
            return true;
        } catch (\Throwable $e) {
            Log::error("GoogleSheetService updateRow error: " . $e->getMessage());
            return false;
        }
    }
}
