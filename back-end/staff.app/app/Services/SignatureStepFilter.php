<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SignatureStepFilter
{
    /**
     * Normalize gender / putra-putri input into 'L' or 'P'.
     */
    public static function normalizeGender(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return null;
        }

        return match ($raw) {
            'l', 'putra', 'laki', 'laki-laki', 'lk', 'male', '1' => 'L',
            'p', 'putri', 'perempuan', 'pr', 'wanita', 'female', '2' => 'P',
            default => throw new \InvalidArgumentException(
                "Parameter 'jenis_kelamin' ('{$value}') tidak valid. Gunakan 'L'/'putra' atau 'P'/'putri'."
            ),
        };
    }

    /**
     * Resolve optional gender filter from request query params ('jenis_kelamin', 'putra_putri', or 'gender').
     */
    public static function resolveGenderFilter(Request $request): ?string
    {
        foreach (['jenis_kelamin', 'putra_putri', 'gender'] as $param) {
            if ($request->filled($param)) {
                return self::normalizeGender($request->input($param));
            }
        }

        return null;
    }

    /**
     * Parse comma-separated or array step keys and validate them against $stepOrder.
     *
     * @return string[]
     */
    public static function parseStepList(mixed $value, array $stepOrder, string $paramName): array
    {
        $items = is_array($value)
            ? $value
            : explode(',', (string) $value);

        $keys = [];
        foreach ($items as $item) {
            $key = trim((string) $item);
            if ($key === '') {
                continue;
            }
            if (!array_key_exists($key, $stepOrder)) {
                throw new \InvalidArgumentException("Parameter '{$paramName}' mengandung step_key '{$key}' yang tidak valid.");
            }
            if (!in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Apply step status, sequential readiness, and prerequisite step filters to an external list query.
     */
    public static function applyExternalListFilters(Builder $query, Request $request, array $stepOrder): void
    {
        if ($request->filled('step_key')) {
            $stepKey = trim((string) $request->input('step_key'));
            if (!array_key_exists($stepKey, $stepOrder)) {
                throw new \InvalidArgumentException("Parameter 'step_key' ('{$stepKey}') tidak valid.");
            }

            $stepStatus = $request->filled('step_status')
                ? trim((string) $request->input('step_status'))
                : null;

            if ($stepStatus !== null && !in_array($stepStatus, ['pending', 'approved'], true)) {
                throw new \InvalidArgumentException("Parameter 'step_status' harus bernilai 'pending' atau 'approved'.");
            }

            if ($stepStatus === 'approved') {
                self::whereStepApproved($query, $stepKey);
            } elseif ($stepStatus === 'pending') {
                self::whereStepPending($query, $stepKey);
            }

            if ($request->boolean('require_previous_approved', false)) {
                self::wherePreviousStepsApproved($query, $stepKey, $stepOrder);
            }
        } elseif ($request->boolean('require_previous_approved', false)) {
            throw new \InvalidArgumentException("Parameter 'require_previous_approved' memerlukan parameter 'step_key'.");
        }

        if ($request->filled('ready_for_step')) {
            $readyStep = trim((string) $request->input('ready_for_step'));
            if (!array_key_exists($readyStep, $stepOrder)) {
                throw new \InvalidArgumentException("Parameter 'ready_for_step' ('{$readyStep}') tidak valid.");
            }

            self::wherePreviousStepsApproved($query, $readyStep, $stepOrder);
            self::whereStepPending($query, $readyStep);
        }

        if ($request->filled('approved_steps')) {
            $approvedSteps = self::parseStepList($request->input('approved_steps'), $stepOrder, 'approved_steps');
            foreach ($approvedSteps as $key) {
                self::whereStepApproved($query, $key);
            }
        }

        if ($request->filled('pending_steps')) {
            $pendingSteps = self::parseStepList($request->input('pending_steps'), $stepOrder, 'pending_steps');
            foreach ($pendingSteps as $key) {
                self::whereStepPending($query, $key);
            }
        }
    }

    /**
     * Validate sequential/prerequisite rules before confirming a step.
     */
    public static function assertPrerequisitesSatisfied(
        Collection $steps,
        string $targetStepKey,
        array $stepOrder,
        array $shortLabels,
        array $options = []
    ): void {
        $targetOrder = $stepOrder[$targetStepKey] ?? null;
        if ($targetOrder === null) {
            throw new \InvalidArgumentException("Step key '{$targetStepKey}' tidak valid.");
        }

        $targetLabel = $shortLabels[$targetStepKey] ?? $targetStepKey;
        $byKey = $steps->keyBy('step_key');

        if (!empty($options['require_previous_approved'])) {
            $missingPrevious = [];
            foreach ($stepOrder as $key => $order) {
                if ($order < $targetOrder) {
                    $sig = $byKey->get($key);
                    if (!$sig || $sig->status !== 'approved') {
                        $missingPrevious[] = $shortLabels[$key] ?? $key;
                    }
                }
            }

            if (!empty($missingPrevious)) {
                $joined = implode(', ', $missingPrevious);
                throw new \InvalidArgumentException(
                    "Step sebelumnya ({$joined}) harus disetujui terlebih dahulu sebelum '{$targetLabel}'."
                );
            }
        }

        if (!empty($options['required_steps']) && is_array($options['required_steps'])) {
            $requiredKeys = self::parseStepList($options['required_steps'], $stepOrder, 'required_steps');
            $missingRequired = [];
            foreach ($requiredKeys as $key) {
                if ($key === $targetStepKey) {
                    continue;
                }
                $sig = $byKey->get($key);
                if (!$sig || $sig->status !== 'approved') {
                    $missingRequired[] = $shortLabels[$key] ?? $key;
                }
            }

            if (!empty($missingRequired)) {
                $joined = implode(', ', $missingRequired);
                throw new \InvalidArgumentException(
                    "Syarat step ({$joined}) belum disetujui sebelum '{$targetLabel}'."
                );
            }
        }
    }

    private static function wherePreviousStepsApproved(Builder $query, string $stepKey, array $stepOrder): void
    {
        $targetOrder = $stepOrder[$stepKey];
        foreach ($stepOrder as $prevKey => $prevOrder) {
            if ($prevOrder < $targetOrder) {
                self::whereStepApproved($query, $prevKey);
            }
        }
    }

    private static function whereStepApproved(Builder $query, string $stepKey): void
    {
        $query->whereHas('signatures', function (Builder $q) use ($stepKey) {
            $q->where('step_key', $stepKey)->where('status', 'approved');
        });
    }

    private static function whereStepPending(Builder $query, string $stepKey): void
    {
        $query->whereDoesntHave('signatures', function (Builder $q) use ($stepKey) {
            $q->where('step_key', $stepKey)->where('status', 'approved');
        });
    }
}
