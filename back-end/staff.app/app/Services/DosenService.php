<?php

namespace App\Services;

class DosenService
{
    /**
     * Format nama dosen dengan gelar depan dan gelar belakang
     * Nama dosen dibuat Capitalize (Title Case: JAUDI -> Jaudi), sedangkan gelar tetap normal.
     * Contoh: Dr. Jaudi, M.Pd.I atau Faishol Ahmad, M.Pd.
     *
     * @param string|null $nama
     * @param string|null $gelarDepan
     * @param string|null $gelarBelakang
     * @return string
     */
    public static function formatNamaGelar($nama, $gelarDepan = '', $gelarBelakang = '')
    {
        $nama = mb_convert_case(mb_strtolower(trim($nama ?? ''), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $gelarDepan = trim($gelarDepan ?? '');
        $gelarBelakang = trim($gelarBelakang ?? '');

        $result = $nama;

        if (!empty($gelarDepan)) {
            $depanCheck = rtrim($gelarDepan, '.');
            if (!preg_match('/^' . preg_quote($depanCheck, '/') . '(\.|\s)/i', $result)) {
                $result = $gelarDepan . ' ' . $result;
            }
        }

        if (!empty($gelarBelakang)) {
            $cleanGelarBelakang = ltrim($gelarBelakang, ", \t\n\r\0\x0B");
            $belakangNormalized = str_replace('.', '', strtolower($cleanGelarBelakang));
            $resultNormalized = str_replace('.', '', strtolower($result));
            if (!str_contains($resultNormalized, $belakangNormalized)) {
                $result = rtrim($result, ", \t\n\r\0\x0B") . ', ' . $cleanGelarBelakang;
            }
        }

        return trim(rtrim($result, ", \t\n\r\0\x0B"));
    }

    /**
     * Memastikan string nama dosen seragam: nama berhuruf Capitalize (Title Case, misal: JAUDI -> Jaudi), tapi gelar tidak.
     * Contoh input:
     * - "Dr. JAUDI, M.Pd.I," -> "Dr. Jaudi, M.Pd.I"
     * - "Faishol Ahmad, M.Pd." -> "Faishol Ahmad, M.Pd."
     * - "Reiza Praselanova S.Kom M.I.Kom" -> "Reiza Praselanova, S.Kom M.I.Kom"
     */
    public static function formatNamaCapitalWithGelar($name)
    {
        $name = trim($name ?? '', " \t\n\r\0\x0B,");
        if (empty($name)) {
            return '';
        }

        // 1. Ekstrak Gelar Depan (Prof., Dr., Drs., Dra., Ir., H., Hj., K.H., Ust., Gus)
        $gelarDepan = '';
        $namaTanpaGelarDepan = $name;

        $prefixPattern = '/^((?:(?:Prof|Drs|Dra|Dr|Ir|KH|K\.H|Ust|Ustadz|Gus)\.?\s*|(?:H|Hj)\.\s*)+)(.*)$/iu';
        if (preg_match($prefixPattern, $name, $matches)) {
            $rawDepan = trim($matches[1]);
            $sisa = trim($matches[2]);
            if (!empty($sisa)) {
                $gelarDepan = $rawDepan;
                $namaTanpaGelarDepan = $sisa;
            }
        }

        // 2. Pisahkan Nama Utama dengan Gelar Belakang
        $namaUtama = '';
        $gelarBelakang = '';

        if (str_contains($namaTanpaGelarDepan, ',')) {
            $parts = explode(',', $namaTanpaGelarDepan, 2);
            $namaUtama = trim($parts[0]);
            $gelarBelakang = trim($parts[1], " \t\n\r\0\x0B,");
        } else {
            $degreePattern = '/\b((?:S\.[A-Za-z\.]+|M\.[A-Za-z\.]+|Ph\.D\.?|M\.A\.?|B\.A\.?|M\.M\.?|M\.I\.Kom|Lc\.?)(?:[\s,]+(?:S\.[A-Za-z\.]+|M\.[A-Za-z\.]+|Ph\.D\.?|M\.A\.?|B\.A\.?|M\.M\.?|M\.I\.Kom|Lc\.?))*)$/iu';
            if (preg_match($degreePattern, $namaTanpaGelarDepan, $dMatches, PREG_OFFSET_CAPTURE)) {
                $offset = $dMatches[0][1];
                $namaUtama = trim(substr($namaTanpaGelarDepan, 0, $offset));
                $gelarBelakang = trim(substr($namaTanpaGelarDepan, $offset), " \t\n\r\0\x0B,");
            } else {
                $namaUtama = $namaTanpaGelarDepan;
                $gelarBelakang = '';
            }
        }

        // 3. Ubah nama utama menjadi CAPITALIZE / Title Case (JAUDI -> Jaudi)
        $namaUtamaCapitalize = mb_convert_case(mb_strtolower($namaUtama, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $result = $namaUtamaCapitalize;

        if (!empty($gelarDepan)) {
            $cleanGelarDepan = trim(preg_replace('/\s+/', ' ', $gelarDepan));
            $cleanGelarDepan = preg_replace_callback('/\b(prof|dr|drs|dra|ir|kh|ust|gus)\b/i', function ($m) {
                $val = strtolower($m[1]);
                return match ($val) {
                    'prof' => 'Prof',
                    'dr' => 'Dr',
                    'drs' => 'Drs',
                    'dra' => 'Dra',
                    'ir' => 'Ir',
                    'kh' => 'K.H',
                    'ust' => 'Ust',
                    'gus' => 'Gus',
                    default => ucfirst($val),
                };
            }, $cleanGelarDepan);

            $result = $cleanGelarDepan . ' ' . $result;
        }

        if (!empty($gelarBelakang)) {
            $cleanGelarBelakang = trim($gelarBelakang, " \t\n\r\0\x0B,");
            $result = rtrim($result, ", ") . ', ' . $cleanGelarBelakang;
        }

        return trim(rtrim($result, ", \t\n\r\0\x0B"));
    }

    /**
     * Search dosen by name, nidn, or kode from SIAKAD API
     * @param string $search
     * @return array
     */
    public static function searchDosen($search = '')
    {
        $apiKey = config('simkeu.simkeu_api_key');
        $url = config('simkeu.simkeu_url') . "dosen-staff";
        
        if (!empty($search)) {
            $url .= "?search=" . urlencode($search);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "apikey: $apiKey",
            "Accept: application/json"
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        \Illuminate\Support\Facades\Log::info("DosenService URL: $url");
        \Illuminate\Support\Facades\Log::info("DosenService HTTP Code: $httpcode");
        \Illuminate\Support\Facades\Log::info("DosenService Error: $err");
        \Illuminate\Support\Facades\Log::info("DosenService Response: $response");

        $decoded = json_decode($response);
        if (is_object($decoded) && isset($decoded->data)) {
            $data = $decoded->data;
            if (is_array($data)) {
                foreach ($data as &$dosen) {
                    if (is_object($dosen)) {
                        $rawNama = $dosen->nama ?? '';
                        $gelarDepan = $dosen->gelar_depan ?? '';
                        $gelarBelakang = $dosen->gelar_belakang ?? '';
                        $namaGelar = self::formatNamaGelar($rawNama, $gelarDepan, $gelarBelakang);
                        $dosen->nama_asli = $rawNama;
                        $dosen->nama_gelar = $namaGelar;
                        $dosen->nama = $namaGelar;
                    } elseif (is_array($dosen)) {
                        $rawNama = $dosen['nama'] ?? '';
                        $gelarDepan = $dosen['gelar_depan'] ?? '';
                        $gelarBelakang = $dosen['gelar_belakang'] ?? '';
                        $namaGelar = self::formatNamaGelar($rawNama, $gelarDepan, $gelarBelakang);
                        $dosen['nama_asli'] = $rawNama;
                        $dosen['nama_gelar'] = $namaGelar;
                        $dosen['nama'] = $namaGelar;
                    }
                }
                unset($dosen);
            }
            return $data;
        }
        return [];
    }
}
