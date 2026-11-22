<?php

namespace App\Services;

use App\Models\FormPengambilanIjazah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PengambilanIjazahGenderFixService
{
    /**
     * Local tables that store student NIM + jenis_kelamin ('L' / 'P'), checked in priority order.
     */
    private const LOCAL_GENDER_TABLES = [
        'sk_yudisium',
        'surat_keterangan_lulus_mata_kuliah',
        'surat_keterangan_administrasi_keuangan',
        'surat_keterangan_tasma_kkn_ppl',
        'surat_keterangan_ujian_komprehensif_diniyah',
        'surat_keterangan_qismul_aman',
        'pengajuan_judul',
    ];

    /**
     * Process a small cursor-paginated batch of FormPengambilanIjazah records missing jenis_kelamin.
     */
    public function processBatch(
        int $afterId = 0,
        int $batchSize = 5,
        bool $includeSimkeu = true,
        bool $overwrite = false
    ): array {
        $batchSize = min(max($batchSize, 1), 25);

        $baseQuery = FormPengambilanIjazah::query()->with('user:id,jenis_kelamin');
        if (!$overwrite) {
            $baseQuery->where(function ($q) {
                $q->whereNull('jenis_kelamin')
                    ->orWhere('jenis_kelamin', '');
            });
        }

        $totalTarget = (clone $baseQuery)->count();

        $rows = (clone $baseQuery)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        $processedCount = $rows->count();
        $lastProcessedId = $rows->isNotEmpty() ? (int) $rows->last()->id : $afterId;
        $hasMore = $rows->isNotEmpty() && (clone $baseQuery)->where('id', '>', $lastProcessedId)->exists();

        $localMap = $this->buildLocalGenderMap($rows);
        $simkeuCache = [];

        $updatedCount = 0;
        $skippedNotFound = 0;
        $unresolvedSamples = [];

        foreach ($rows as $form) {
            $resolved = $this->resolveGenderForForm($form, $localMap, $simkeuCache, $includeSimkeu);

            if ($resolved !== null) {
                $form->update(['jenis_kelamin' => $resolved]);
                $updatedCount++;
            } else {
                $skippedNotFound++;
                $unresolvedSamples[] = [
                    'id' => $form->id,
                    'nim' => $form->nim,
                    'nama_mhs' => $form->nama_mhs,
                ];
            }
        }

        return [
            'total_target' => $totalTarget,
            'processed_in_batch' => $processedCount,
            'updated_count' => $updatedCount,
            'skipped_not_found' => $skippedNotFound,
            'last_processed_id' => $lastProcessedId,
            'next_after_id' => $lastProcessedId,
            'has_more' => $hasMore,
            'unresolved_samples' => $unresolvedSamples,
        ];
    }

    /**
     * Build a lookup map [clean_nim => 'L'|'P'] from local tables for all NIMs in the current batch.
     */
    private function buildLocalGenderMap($rows): array
    {
        $rawNims = [];
        $cleanNims = [];
        foreach ($rows as $form) {
            $raw = trim((string) ($form->nim ?? ''));
            if ($raw === '') {
                continue;
            }
            $rawNims[] = $raw;
            $clean = self::cleanNim($raw);
            if ($clean !== '') {
                $cleanNims[] = $clean;
            }
        }

        $rawNims = array_values(array_unique($rawNims));
        $cleanNims = array_values(array_unique($cleanNims));

        if (empty($rawNims) && empty($cleanNims)) {
            return [];
        }

        $map = [];
        foreach (self::LOCAL_GENDER_TABLES as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'nim') || !Schema::hasColumn($table, 'jenis_kelamin')) {
                continue;
            }

            $records = DB::table($table)
                ->select(['nim', 'jenis_kelamin'])
                ->whereNotNull('jenis_kelamin')
                ->where('jenis_kelamin', '!=', '')
                ->where(function ($q) use ($rawNims, $cleanNims) {
                    if (!empty($rawNims)) {
                        $q->whereIn('nim', $rawNims);
                    }
                    if (!empty($cleanNims)) {
                        $q->orWhereIn(
                            DB::raw("REPLACE(REPLACE(REPLACE(nim, '.', ''), '-', ''), ' ', '')"),
                            $cleanNims
                        );
                    }
                })
                ->orderByDesc('id')
                ->get();

            foreach ($records as $rec) {
                $gender = $this->tryNormalize($rec->jenis_kelamin);
                if ($gender === null) {
                    continue;
                }
                $clean = self::cleanNim($rec->nim);
                if ($clean !== '' && !isset($map[$clean])) {
                    $map[$clean] = $gender;
                }
            }
        }

        return $map;
    }

    private function resolveGenderForForm(
        FormPengambilanIjazah $form,
        array $localMap,
        array &$simkeuCache,
        bool $includeSimkeu
    ): ?string {
        $cleanNim = self::cleanNim($form->nim);

        if ($cleanNim !== '' && isset($localMap[$cleanNim])) {
            return $localMap[$cleanNim];
        }

        if ($includeSimkeu) {
            $simkeuGender = $this->lookupFromSimkeu($form, $cleanNim, $simkeuCache);
            if ($simkeuGender !== null) {
                return $simkeuGender;
            }
        }

        if ($form->user && !empty($form->user->jenis_kelamin)) {
            return $this->tryNormalize($form->user->jenis_kelamin);
        }

        return null;
    }

    private function lookupFromSimkeu(FormPengambilanIjazah $form, string $cleanNim, array &$simkeuCache): ?string
    {
        $cacheKey = $cleanNim !== '' ? ('nim:' . $cleanNim) : ('name:' . strtolower(trim((string) $form->nama_mhs)));
        if (array_key_exists($cacheKey, $simkeuCache)) {
            return $simkeuCache[$cacheKey];
        }

        $rawNim = trim((string) ($form->nim ?? ''));
        $searchCandidates = array_values(array_unique(array_filter([
            $rawNim,
            $cleanNim !== $rawNim ? $cleanNim : null,
        ])));

        foreach ($searchCandidates as $keyword) {
            $gender = $this->searchSimkeuAndMatch($keyword, $cleanNim, $form->nama_mhs);
            if ($gender !== null) {
                return $simkeuCache[$cacheKey] = $gender;
            }
        }

        $rawName = trim((string) ($form->nama_mhs ?? ''));
        if ($rawName !== '') {
            $gender = $this->searchSimkeuAndMatch($rawName, $cleanNim, $rawName);
            if ($gender !== null) {
                return $simkeuCache[$cacheKey] = $gender;
            }
        }

        return $simkeuCache[$cacheKey] = null;
    }

    private function searchSimkeuAndMatch(string $keyword, string $targetCleanNim, ?string $targetName): ?string
    {
        try {
            $results = Mahasiswa::all(null, 5, $keyword, null, null, null, null, false);
            if (!is_array($results) || empty($results)) {
                return null;
            }

            $normalizedTargetName = self::cleanName($targetName);
            foreach ($results as $item) {
                $itemNim = self::cleanNim($item->nim ?? '');
                $itemName = self::cleanName($item->nama ?? $item->nama_mhs ?? '');

                $nimMatched = $targetCleanNim !== '' && $itemNim === $targetCleanNim;
                $nameMatched = $normalizedTargetName !== '' && $itemName === $normalizedTargetName;

                if ($nimMatched || $nameMatched || count($results) === 1) {
                    $gender = $this->tryNormalize(
                        $item->jenis_kelamin ?? $item->jk ?? $item->jenis_kelamin_nama ?? null
                    );
                    if ($gender !== null) {
                        return $gender;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Simkeu gender lookup failed for '{$keyword}': " . $e->getMessage());
        }

        return null;
    }

    private function tryNormalize(mixed $value): ?string
    {
        try {
            return SignatureStepFilter::normalizeGender($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private static function cleanNim(?string $nim): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', (string) $nim));
    }

    private static function cleanName(?string $name): string
    {
        return strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $name)));
    }
}
