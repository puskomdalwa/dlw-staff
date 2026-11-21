<?php

namespace App\Services;

use App\Models\PengajuanJudul;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JudulSimilarityService
{
    /**
     * Stopwords umum bahasa Indonesia dan istilah generik penulisan skripsi
     */
    protected static array $stopwords = [
        'dan', 'di', 'ke', 'dari', 'yang', 'untuk', 'pada', 'dengan', 'adalah', 'sebagai',
        'dalam', 'atas', 'oleh', 'tentang', 'terhadap', 'studi', 'kasus', 'pengaruh',
        'analisis', 'penerapan', 'upaya', 'efektivitas', 'implementasi', 'peran', 'faktor',
        'hubungan', 'tinjauan', 'hukum', 'perspektif', 'tahun', 'menurut', 'antara', 'desa',
        'kelurahan', 'kecamatan', 'kabupaten', 'kota', 'provinsi', 'studi', 'komparatif',
        'strategi', 'evaluasi', 'peningkatan', 'pengembangan', 'bimbingan', 'pelaksanaan',
        'suatu', 'para', 'sebuah', 'ia', 'ini', 'itu', 'atau', 'serta', 'juga'
    ];

    /**
     * Bersihkan teks dari tanda baca, angka, dan spasi ganda
     */
    public static function cleanText(string $text): string
    {
        $text = strtolower($text);
        // Hapus karakter non alfanumerik (kecuali spasi)
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        // Rapikan spasi ganda
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Ekstrak kata-kata kunci (menghilangkan stopwords dan kata terlalu pendek)
     */
    public static function extractKeywords(string $text): array
    {
        $clean = self::cleanText($text);
        $words = explode(' ', $clean);

        $keywords = [];
        foreach ($words as $w) {
            $w = trim($w);
            if (strlen($w) >= 3 && !in_array($w, self::$stopwords)) {
                $keywords[] = $w;
            }
        }

        return array_values(array_unique($keywords));
    }

    /**
     * Hitung character bigrams untuk Sørensen-Dice coefficient
     */
    protected static function getBigrams(string $text): array
    {
        $clean = preg_replace('/[^a-z0-9]/', '', strtolower($text));
        $bigrams = [];
        $len = strlen($clean);
        for ($i = 0; $i < $len - 1; $i++) {
            $bigrams[] = substr($clean, $i, 2);
        }
        return array_count_values($bigrams);
    }

    /**
     * Koefisien Sørensen-Dice (mencakup kesamaan bentuk kata dan typo)
     */
    public static function diceCoefficient(string $str1, string $str2): float
    {
        $bg1 = self::getBigrams($str1);
        $bg2 = self::getBigrams($str2);

        $intersection = 0;
        foreach ($bg1 as $bg => $count) {
            if (isset($bg2[$bg])) {
                $intersection += min($count, $bg2[$bg]);
            }
        }

        $total = array_sum($bg1) + array_sum($bg2);
        return $total > 0 ? (2.0 * $intersection) / $total : 0.0;
    }

    /**
     * Jaccard Similarity antar himpunan kata kunci
     */
    public static function jaccardSimilarity(array $tokens1, array $tokens2): float
    {
        if (empty($tokens1) && empty($tokens2)) {
            return 1.0;
        }
        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }

        $intersect = count(array_intersect($tokens1, $tokens2));
        $union = count(array_unique(array_merge($tokens1, $tokens2)));

        return $union > 0 ? $intersect / $union : 0.0;
    }

    /**
     * Hitung skor kemiripan komposit antara 2 judul (0 - 100)
     */
    public static function calculateSimilarity(string $inputJudul, string $dbJudul): array
    {
        $inputClean = self::cleanText($inputJudul);
        $dbClean = self::cleanText($dbJudul);

        if ($inputClean === $dbClean) {
            return [
                'score' => 100.0,
                'matched_keywords' => self::extractKeywords($inputJudul),
                'jaccard' => 1.0,
                'dice' => 1.0,
                'exact' => true,
            ];
        }

        $inputKeywords = self::extractKeywords($inputJudul);
        $dbKeywords = self::extractKeywords($dbJudul);

        $matchedKeywords = array_values(array_intersect($inputKeywords, $dbKeywords));

        // Metrik 1: Jaccard kata kunci utama (bobot 50%)
        $jaccard = self::jaccardSimilarity($inputKeywords, $dbKeywords);

        // Metrik 2: Sørensen-Dice bigram (bobot 30%)
        $dice = self::diceCoefficient($inputJudul, $dbJudul);

        // Metrik 3: similar_text PHP bawaan (bobot 20%)
        similar_text($inputClean, $dbClean, $simPercent);
        $simRatio = $simPercent / 100.0;

        // Skor komposit berbobot
        $score = ($jaccard * 0.50 + $dice * 0.30 + $simRatio * 0.20) * 100;

        // Bonus jika ada kecocokan frase berturut-turut (substring match)
        if (strlen($inputClean) > 15 && strlen($dbClean) > 15) {
            if (str_contains($dbClean, $inputClean) || str_contains($inputClean, $dbClean)) {
                $score = max($score, 85.0);
            }
        }

        $finalScore = min(100.0, round($score, 2));

        return [
            'score' => $finalScore,
            'matched_keywords' => $matchedKeywords,
            'jaccard' => round($jaccard * 100, 2),
            'dice' => round($dice * 100, 2),
            'exact' => false,
        ];
    }

    /**
     * Periksa kemiripan judul terhadap seluruh database pengajuan_judul dengan kecepatan tinggi
     *
     * @param string $judul Judul yang ingin dicek
     * @param int|null $prodiId Filter prodi spesifik (opsional)
     * @param int|null $excludeId ID record yang sedang diedit (agar tidak membandingkan dengan diri sendiri)
     * @param float $threshold Batas minimum kemiripan yang ditampilkan (default: 30%)
     * @param int $limit Jumlah rekomendasi judul termirip maksimal (default: 10)
     */
    public static function checkSimilarity(
        string $judul,
        ?int $prodiId = null,
        ?int $excludeId = null,
        float $threshold = 30.0,
        int $limit = 10
    ): array {
        $startTime = microtime(true);
        $trimmedJudul = trim($judul);

        if (empty($trimmedJudul) || strlen($trimmedJudul) < 5) {
            return [
                'status' => true,
                'judul' => $trimmedJudul,
                'max_score' => 0.0,
                'matches' => [],
                'total_checked' => 0,
                'execution_time_ms' => 0.0,
                'recommendation' => 'Judul terlalu pendek untuk diperiksa.',
                'level' => 'aman',
            ];
        }

        // Ambil data dari tabel pengajuan_judul hanya kolom yang relevan (meminimalkan memory & I/O)
        $query = PengajuanJudul::query()
            ->select('id', 'nim', 'nama_mhs', 'prodi', 'prodi_id', 'judul_proposal_skripsi', 'tanggal', 'status')
            ->whereNotNull('judul_proposal_skripsi')
            ->where('judul_proposal_skripsi', '!=', '');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($prodiId) {
            $query->where(function ($q) use ($prodiId) {
                $q->where('prodi_id', $prodiId)
                  ->orWhereNull('prodi_id');
            });
        }

        $records = $query->get();
        $totalChecked = count($records);

        $matches = [];
        $maxScore = 0.0;

        foreach ($records as $item) {
            $dbJudul = (string) $item->judul_proposal_skripsi;
            if (empty(trim($dbJudul))) {
                continue;
            }

            $calc = self::calculateSimilarity($trimmedJudul, $dbJudul);
            $score = $calc['score'];

            if ($score > $maxScore) {
                $maxScore = $score;
            }

            if ($score >= $threshold) {
                $level = 'rendah';
                $levelLabel = 'Kemiripan Rendah';
                $levelColor = 'info';

                if ($score >= 75.0) {
                    $level = 'tinggi';
                    $levelLabel = 'Kemiripan Sangat Tinggi (Potensi Duplikat)';
                    $levelColor = 'danger';
                } elseif ($score >= 50.0) {
                    $level = 'sedang';
                    $levelLabel = 'Kemiripan Cukup Tinggi';
                    $levelColor = 'warning';
                }

                $matches[] = [
                    'id' => $item->id,
                    'nim' => $item->nim,
                    'nama_mhs' => $item->nama_mhs,
                    'prodi' => $item->prodi,
                    'prodi_id' => $item->prodi_id,
                    'judul' => $item->judul_proposal_skripsi,
                    'tanggal' => $item->tanggal,
                    'status' => $item->status,
                    'score' => $score,
                    'level' => $level,
                    'level_label' => $levelLabel,
                    'level_color' => $levelColor,
                    'matched_keywords' => $calc['matched_keywords'],
                    'details' => [
                        'jaccard' => $calc['jaccard'],
                        'dice' => $calc['dice'],
                        'exact' => $calc['exact'],
                    ]
                ];
            }
        }

        // Urutkan dari kemiripan tertinggi ke terendah
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

        if (count($matches) > $limit) {
            $matches = array_slice($matches, 0, $limit);
        }

        $endTime = microtime(true);
        $executionTimeMs = round(($endTime - $startTime) * 1000, 2);

        // Rekomendasi sistem berdasarkan skor tertinggi
        $overallLevel = 'aman';
        $recommendation = 'Judul orisinil dan aman untuk diajukan.';

        if ($maxScore >= 75.0) {
            $overallLevel = 'tinggi';
            $recommendation = 'Peringatan: Ditemukan judul dengan kemiripan sangat tinggi. Disarankan merevisi fokus, objek, atau variabel penelitian.';
        } elseif ($maxScore >= 50.0) {
            $overallLevel = 'sedang';
            $recommendation = 'Perhatian: Ada beberapa kesamaan konsep dengan judul terdahulu. Pastikan kebaruan (*novelty*) atau lokasi studi berbeda.';
        } elseif ($maxScore >= $threshold) {
            $overallLevel = 'rendah';
            $recommendation = 'Kemiripan rendah. Judul memenuhi kriteria kebaruan.';
        }

        return [
            'status' => true,
            'judul' => $trimmedJudul,
            'max_score' => $maxScore,
            'level' => $overallLevel,
            'recommendation' => $recommendation,
            'matches' => $matches,
            'total_matches' => count($matches),
            'total_checked' => $totalChecked,
            'execution_time_ms' => $executionTimeMs,
        ];
    }
}

