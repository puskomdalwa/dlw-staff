<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class pddiktiService
{
    private string $baseUrl = "https://pddikti.uiidalwa.com/ws/live2.php";
    private ?string $token = null;

    public function __construct()
    {

        // Ambil dari cache jika token valid sudah ada
        $cachedToken = Cache::get('pddikti_token');
        if ($cachedToken) {
            $this->token = $cachedToken;
            return;
        }

        try {
            $response = Http::withoutVerifying()->timeout(10)->post(
                $this->baseUrl,
                [
                    "act" => "GetToken",
                    "username" => "dedenr219@gmail.com",
                    "password" => "pdD!kt!u!!dalw43330"
                ]
            );



            if ($response->successful()) {
                $token = $response->json()['data']['token'] ?? null;
                if ($token) {
                    Cache::put('pddikti_token', $token, 3600);
                    $this->token = $token;
                    return;
                }
            }

            Log::error('Gagal mengambil token PDDIKTI: HTTP ' . $response->status() . ' - ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Exception saat mengambil token PDDIKTI: ' . $e->getMessage());
        }
    }

    /**
     * Fetch riwayat nilai mahasiswa by NIM from PDDIKTI API
     */
    public function getNilaiByNimPDIKTI(string $nim): array
    {
        if (!$this->token) {
            throw new \Exception("Server PDDIKTI tidak dapat dihubungi (Server Offline / Cloudflare Error 1033) atau token autentikasi gagal diambil.");
        }

        $fetchNilai = function (string $filterNim) {
            $response = Http::withoutVerifying()->timeout(15)->post(
                $this->baseUrl,
                [
                    'act' => 'GetRiwayatNilaiMahasiswa',
                    'token' => $this->token,
                    'filter' => "nim='$filterNim'",
                    'order' => 'id_periode asc',
                    'limit' => '100',
                    'offset' => '0'
                ]
            );

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            Log::warning("PDDIKTI API gagal untuk NIM: {$filterNim}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        };

        $data = $fetchNilai($nim);

        // Fallback: jika kosong dan NIM mengandung titik, coba NIM tanpa titik (misal 2021.01.001 -> 202101001)
        if (empty($data) && str_contains($nim, '.')) {
            $cleanNim = str_replace('.', '', $nim);
            Log::info("PDDIKTI fallback NIM tanpa titik: {$cleanNim}");
            $data = $fetchNilai($cleanNim);
        }

        return $data;
    }

    public function getIpkMahasiswa($nim)
    {
        if (!$this->token) {
            throw new \Exception("Server PDDIKTI tidak dapat dihubungi (Server Offline / Cloudflare Error 1033) atau token autentikasi gagal diambil.");
        }

        $fetchIpk = function (string $filterNim) {
            $response = Http::withoutVerifying()->timeout(15)->post(
                $this->baseUrl,
                [
                    'act' => 'GetListPerkuliahanMahasiswa',
                    'token' => $this->token,
                    'filter' => "nim='$filterNim'",
                    'order' => 'id_semester desc',
                    'limit' => '1',
                    'offset' => '0'
                ]
            );

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            Log::warning("PDDIKTI API gagal untuk NIM: {$filterNim}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        };

        $data = $fetchIpk($nim);

        // Fallback: jika kosong dan NIM mengandung titik, coba NIM tanpa titik (misal 2021.01.001 -> 202101001)
        if (empty($data) && str_contains($nim, '.')) {
            $cleanNim = str_replace('.', '', $nim);
            Log::info("PDDIKTI fallback NIM tanpa titik: {$cleanNim}");
            $data = $fetchIpk($cleanNim);
        }

        return $data;
    }
    public function getJudulSkripsi($nim)
    {
        if (!$this->token) {
            throw new \Exception("Server PDDIKTI tidak dapat dihubungi (Server Offline / Cloudflare Error 1033) atau token autentikasi gagal diambil.");
        }

        $fetchSkripsi = function (string $filterNim) {
            // Cek GetDetailMahasiswaLulusDO karena memiliki kolom judul_skripsi resmi PDDIKTI
            $responseLulus = Http::withoutVerifying()->timeout(15)->post(
                $this->baseUrl,
                [
                    'act' => 'GetDetailMahasiswaLulusDO',
                    'token' => $this->token,
                    'filter' => "nim='$filterNim'",
                    'limit' => '1',
                    'offset' => '0'
                ]
            );

            if ($responseLulus->successful() && !empty($responseLulus->json()['data'])) {
                $item = $responseLulus->json()['data'][0] ?? [];
                if (!empty($item['judul_skripsi'])) {
                    return $responseLulus->json()['data'];
                }
            }

            // Fallback ke GetListMahasiswa
            $response = Http::withoutVerifying()->timeout(15)->post(
                $this->baseUrl,
                [
                    'act' => 'GetListMahasiswa',
                    'token' => $this->token,
                    'filter' => "nim='$filterNim'",
                    'limit' => '1',
                    'offset' => '0'
                ]
            );

            if ($response->successful() && !empty($response->json()['data'])) {
                return $response->json()['data'];
            }

            // Jika ada data dari GetDetailMahasiswaLulusDO meskipun judul_skripsi kosong
            if (!empty($responseLulus->json()['data'])) {
                return $responseLulus->json()['data'];
            }

            Log::warning("PDDIKTI API gagal untuk NIM: {$filterNim}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        };

        $data = $fetchSkripsi($nim);

        // Fallback: jika kosong dan NIM mengandung titik, coba NIM tanpa titik (misal 2021.01.001 -> 202101001)
        if (empty($data) && str_contains($nim, '.')) {
            $cleanNim = str_replace('.', '', $nim);
            Log::info("PDDIKTI fallback NIM tanpa titik: {$cleanNim}");
            $data = $fetchSkripsi($cleanNim);
        }

        return $data;
    }
}
