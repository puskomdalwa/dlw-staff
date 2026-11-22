<?php

namespace App\Services;

use App\Jobs\UploudSuratToDrive;
use App\Models\FormPengambilanIjazah;
use App\Models\PengambilanIjazahSignature;
use App\Models\PengambilanIjazahSignerDefault;
use App\Models\SettingJabatan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PengambilanIjazahSignatureService
{
    public const STEP_ORDER = [
        'keuangan_pondok' => 1,
        'baku' => 2,
        'baak' => 3,
        'warek_1' => 4,
    ];

    public const SHORT_LABELS = [
        'keuangan_pondok' => 'Keu. Pondok',
        'baku' => 'BAKU',
        'baak' => 'BAAK',
        'warek_1' => 'Warek I',
    ];

    /**
     * Fallback defaults sourced from setting_jabatan / module presets.
     */
    private function buildInitialDefaults(): array
    {
        $settingPondok = SettingJabatan::with('tandaTangan')
            ->whereIn('kunci_jabatan', ['keuangan_pondok', 'bendahara_pondok'])
            ->first();
        $pondokNama = $settingPondok?->tandaTangan?->nama ?: 'Keuangan Pondok';
        $pondokNidn = $settingPondok?->nidn;

        $settingBaku = SettingJabatan::with('tandaTangan')
            ->where('kunci_jabatan', 'kepala_biro_keuangan')
            ->first();
        $bakuNama = $settingBaku?->tandaTangan?->nama ?: 'Dr. Musleh Harry, SH, M.Hum';
        $bakuNidn = $settingBaku?->nidn;

        $settingBaak = SettingJabatan::with('tandaTangan')
            ->whereIn('kunci_jabatan', ['BAAK', 'ka_baak', 'kepala_baak'])
            ->first();
        $baakNama = $settingBaak?->tandaTangan?->nama ?: 'Kepala BAAK';
        $baakNidn = $settingBaak?->nidn;

        $settingWarek = SettingJabatan::with('tandaTangan')
            ->whereIn('kunci_jabatan', ['warek_1', 'wakil_rektor_1', 'ketua_komprehensif'])
            ->first();
        $warekNama = $settingWarek?->tandaTangan?->nama ?: 'Dr. Habib Zainal Abidin Bilfaqih, M.Pd.';
        $warekNidn = $settingWarek?->nidn;

        return [
            [
                'step_order' => 1,
                'step_key' => 'keuangan_pondok',
                'title' => 'Keuangan Pondok',
                'signer_name' => $pondokNama,
                'signer_nidn' => $pondokNidn,
            ],
            [
                'step_order' => 2,
                'step_key' => 'baku',
                'title' => 'Kepala Biro Administrasi Keuangan (Keuangan Jamiah)',
                'signer_name' => $bakuNama,
                'signer_nidn' => $bakuNidn,
            ],
            [
                'step_order' => 3,
                'step_key' => 'baak',
                'title' => 'Kepala BAAK (Akademik Jamiah)',
                'signer_name' => $baakNama,
                'signer_nidn' => $baakNidn,
            ],
            [
                'step_order' => 4,
                'step_key' => 'warek_1',
                'title' => 'Wakil Rektor I',
                'signer_name' => $warekNama,
                'signer_nidn' => $warekNidn,
            ],
        ];
    }

    /**
     * Get module-level default signers (seeding missing rows once from initial fallback).
     */
    public function getSignerDefaults(): array
    {
        $existing = PengambilanIjazahSignerDefault::all()->keyBy('step_key');
        $fallback = $this->buildInitialDefaults();

        $results = [];
        foreach ($fallback as $def) {
            $row = $existing->get($def['step_key']);
            if (!$row) {
                $row = PengambilanIjazahSignerDefault::create([
                    'step_order' => $def['step_order'],
                    'step_key' => $def['step_key'],
                    'title' => $def['title'],
                    'signer_name' => $def['signer_name'],
                    'signer_nidn' => $def['signer_nidn'],
                ]);
            }

            $results[] = [
                'step_order' => $row->step_order,
                'step_key' => $row->step_key,
                'short_label' => self::SHORT_LABELS[$row->step_key] ?? $row->step_key,
                'title' => $row->title,
                'signer_name' => $row->signer_name,
                'signer_nidn' => $row->signer_nidn,
            ];
        }

        usort($results, fn (array $a, array $b) => $a['step_order'] <=> $b['step_order']);
        return $results;
    }

    /**
     * Update module-level default signers and optionally apply changes across existing records.
     */
    public function updateSignerDefaults(
        array $stepsInput,
        bool $applyToExisting = true,
        string $applyScope = 'all',
        ?int $userId = null
    ): array {
        $this->getSignerDefaults();

        $updatedStepKeys = [];
        foreach ($stepsInput as $item) {
            $stepKey = $item['step_key'] ?? '';
            if (!array_key_exists($stepKey, self::STEP_ORDER)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $signerName = isset($item['signer_name']) ? trim((string) $item['signer_name']) : null;
            $signerNidn = isset($item['signer_nidn']) && trim((string) $item['signer_nidn']) !== ''
                ? trim((string) $item['signer_nidn'])
                : null;

            $row = PengambilanIjazahSignerDefault::where('step_key', $stepKey)->first();
            if ($row) {
                $row->update([
                    'title' => $title !== '' ? $title : $row->title,
                    'signer_name' => $signerName !== '' ? $signerName : '-',
                    'signer_nidn' => $signerNidn,
                    'updated_by' => $userId,
                ]);
                $updatedStepKeys[$stepKey] = $row->fresh();
            }
        }

        $affectedSignatures = 0;
        if ($applyToExisting && !empty($updatedStepKeys)) {
            FormPengambilanIjazah::select('id')->chunkById(100, function ($forms) {
                foreach ($forms as $form) {
                    $this->ensureStepsForForm($form);
                }
            });

            foreach ($updatedStepKeys as $stepKey => $defRow) {
                $query = PengambilanIjazahSignature::where('step_key', $stepKey);
                if ($applyScope === 'pending') {
                    $query->where('status', 'pending');
                }
                $affectedSignatures += $query->update([
                    'title' => $defRow->title,
                    'signer_name' => $defRow->signer_name,
                    'signer_nidn' => $defRow->signer_nidn,
                    'updated_at' => now(),
                ]);
            }
        }

        return [
            'defaults' => $this->getSignerDefaults(),
            'applied_to_existing' => $applyToExisting,
            'apply_scope' => $applyScope,
            'affected_signatures' => $affectedSignatures,
        ];
    }

    /**
     * Resolve default metadata (order, title, signer_name, signer_nidn) for the 4 Form Pengambilan Ijazah steps.
     */
    public function resolveStepDefinitions(FormPengambilanIjazah $form): array
    {
        return $this->getSignerDefaults();
    }

    /**
     * Ensure all 4 timeline steps exist for a given FormPengambilanIjazah record.
     */
    public function ensureStepsForForm(FormPengambilanIjazah $form): Collection
    {
        if ($form->relationLoaded('signatures')) {
            $preloaded = $form->getRelation('signatures');
            if ($preloaded instanceof Collection && $preloaded->count() === count(self::STEP_ORDER)) {
                return $preloaded->sortBy('step_order')->values();
            }
        }

        $definitions = $this->resolveStepDefinitions($form);
        $existing = PengambilanIjazahSignature::where('form_pengambilan_ijazah_id', $form->id)
            ->get()
            ->keyBy('step_key');

        foreach ($definitions as $def) {
            if (!$existing->has($def['step_key'])) {
                $created = PengambilanIjazahSignature::create([
                    'form_pengambilan_ijazah_id' => $form->id,
                    'step_order' => $def['step_order'],
                    'step_key' => $def['step_key'],
                    'title' => $def['title'],
                    'signer_name' => $def['signer_name'],
                    'signer_nidn' => $def['signer_nidn'],
                    'status' => 'pending',
                    'signature_token' => $this->generateUniqueToken(),
                ]);
                $existing->put($def['step_key'], $created);
            }
        }

        return $existing->sortBy('step_order')->values();
    }

    /**
     * Format timeline steps for API / UI responses.
     */
    public function formatTimeline(FormPengambilanIjazah $form, bool $withQrDataUri = false): array
    {
        $steps = $this->ensureStepsForForm($form);

        return $steps->map(function (PengambilanIjazahSignature $step) use ($withQrDataUri) {
            $verifyUrl = url('/verify/form-pengambilan-ijazah/' . $step->signature_token);
            $item = [
                'id' => $step->id,
                'step_order' => $step->step_order,
                'step_key' => $step->step_key,
                'short_label' => self::SHORT_LABELS[$step->step_key] ?? $step->step_key,
                'title' => $step->title,
                'signer_name' => $step->signer_name,
                'signer_nidn' => $step->signer_nidn,
                'status' => $step->status,
                'is_signed' => $step->status === 'approved',
                'signed_at' => $step->signed_at?->toIso8601String(),
                'signed_at_formatted' => $step->signed_at
                    ? $step->signed_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y H:i') . ' WIB'
                    : null,
                'signed_by_source' => $step->signed_by_source,
                'notes' => $step->notes,
                'signature_token' => $step->signature_token,
                'verify_url' => $verifyUrl,
            ];

            if ($withQrDataUri && $step->status === 'approved') {
                $item['qr_data_uri'] = $this->generateQrDataUri($step);
            }

            return $item;
        })->values()->all();
    }

    /**
     * Confirm (sign via QR confirmation) a specific step and generate signed PDF + queue Drive upload when 4/4 complete.
     */
    public function confirmStep(FormPengambilanIjazah $form, string $stepKey, array $options = []): PengambilanIjazahSignature
    {
        if (!array_key_exists($stepKey, self::STEP_ORDER)) {
            throw new \InvalidArgumentException("Step key '{$stepKey}' tidak valid.");
        }

        $steps = $this->ensureStepsForForm($form);
        SignatureStepFilter::assertPrerequisitesSatisfied(
            $steps,
            $stepKey,
            self::STEP_ORDER,
            self::SHORT_LABELS,
            $options
        );

        /** @var PengambilanIjazahSignature $step */
        $step = $steps->firstWhere('step_key', $stepKey);

        $update = [
            'status' => 'approved',
            'signed_at' => now(),
            'signed_by_source' => $options['source'] ?? 'web_staff',
            'signed_by_user_id' => $options['user_id'] ?? null,
        ];

        if (!empty($options['signer_name'])) {
            $update['signer_name'] = trim((string) $options['signer_name']);
        }
        if (array_key_exists('signer_nidn', $options) && $options['signer_nidn'] !== null && $options['signer_nidn'] !== '') {
            $update['signer_nidn'] = trim((string) $options['signer_nidn']);
        }
        if (!empty($options['title'])) {
            $update['title'] = trim((string) $options['title']);
        }
        if (array_key_exists('notes', $options)) {
            $update['notes'] = $options['notes'];
        }
        if (empty($step->signature_token)) {
            $update['signature_token'] = $this->generateUniqueToken();
        }

        $step->update($update);
        $form->refresh();

        if ($this->isCompleted($form)) {
            $this->regenerateSignedPdf($form);
            $this->queueCompleteDriveUpload($form);
        }

        return $step->fresh();
    }

    /**
     * Revoke (reset to pending) a specific step and clear signed PDF / Drive artifacts.
     */
    public function revokeStep(FormPengambilanIjazah $form, string $stepKey): PengambilanIjazahSignature
    {
        if (!array_key_exists($stepKey, self::STEP_ORDER)) {
            throw new \InvalidArgumentException("Step key '{$stepKey}' tidak valid.");
        }

        $steps = $this->ensureStepsForForm($form);
        /** @var PengambilanIjazahSignature $step */
        $step = $steps->firstWhere('step_key', $stepKey);

        $step->update([
            'status' => 'pending',
            'signed_at' => null,
            'signed_by_source' => null,
            'signed_by_user_id' => null,
            'notes' => null,
        ]);

        $form->refresh();
        $this->clearOldSignedDriveFile($form);

        return $step->fresh();
    }

    /**
     * Check whether all 4 required QR signature steps are approved.
     */
    public function isCompleted(FormPengambilanIjazah $form): bool
    {
        $steps = $this->ensureStepsForForm($form);
        return $steps->count() === count(self::STEP_ORDER)
            && $steps->every(fn (PengambilanIjazahSignature $s) => $s->status === 'approved');
    }

    /**
     * Dispatch Google Drive upload job for the finalized signed Form Pengambilan Ijazah PDF (with all 4 QR codes).
     */
    public function queueCompleteDriveUpload(FormPengambilanIjazah $form): void
    {
        $this->clearOldSignedDriveFile($form, keepLocalFile: true);

        if (!empty($form->signed_local_path) && file_exists($form->signed_local_path)) {
            UploudSuratToDrive::dispatch(
                $form->id,
                'Form Pengambilan Ijazah',
                'BAAK',
                FormPengambilanIjazah::class,
                null,
                true
            );
        }
    }

    public function clearOldSignedDriveFile(FormPengambilanIjazah $form, bool $keepLocalFile = false): void
    {
        if (!empty($form->signed_drive_file_id)) {
            try {
                GoogleDrive::deleteFile($form->signed_drive_file_id);
            } catch (\Throwable $e) {
                Log::warning('Failed deleting old signed FormPengambilanIjazah Drive file: ' . $e->getMessage());
            }
        }

        $updates = [];
        if (!empty($form->signed_drive_file_id) || !empty($form->signed_drive_link)) {
            $updates['signed_drive_file_id'] = null;
            $updates['signed_drive_link'] = null;
        }

        if (!$keepLocalFile && !empty($form->signed_local_path)) {
            if (file_exists($form->signed_local_path)) {
                @File::delete($form->signed_local_path);
            }
            $updates['signed_local_path'] = null;
        }

        if (!empty($updates)) {
            $form->forceFill($updates)->save();
        }
    }

    /**
     * Render styled QR code data URI pointing to the verification URL.
     */
    public function generateQrDataUri(PengambilanIjazahSignature $step): string
    {
        return QrCodeSignatureService::generateStyledVerifyQr(
            url('/verify/form-pengambilan-ijazah/' . $step->signature_token)
        );
    }

    /**
     * Generate & store the signed PDF (with all QR codes) in signed_local_path.
     */
    public function regenerateSignedPdf(FormPengambilanIjazah $form): ?string
    {
        $pdfData = $this->buildPdfData($form, true);
        $pdf = Pdf::loadView('pdf.form_pengambilan_ijazah', $pdfData)->setPaper('a4', 'portrait');

        $directory = base_path('../public_html/pdf/BAAK/Form Pengambilan Ijazah/');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $nimSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) ($form->nim ?: $form->id));
        $fileName = 'form_bebas_tanggungan_ttd_' . $nimSafe . '_' . $form->id . '.pdf';
        $path = $directory . $fileName;

        if (!empty($form->signed_local_path) && $form->signed_local_path !== $path && File::exists($form->signed_local_path)) {
            @File::delete($form->signed_local_path);
        }

        $pdf->save($path);
        $form->update(['signed_local_path' => $path]);

        return $path;
    }

    /**
     * Build Blade data array for pdf.form_pengambilan_ijazah (with optional QR codes for approved steps).
     */
    public function buildPdfData(FormPengambilanIjazah $form, bool $includeQr = false): array
    {
        $kopPath = base_path('../public_html/img/kop.jpg');
        $kopBase64 = SuratService::getBase64Image($kopPath);

        $tanggalSidangFormatted = !empty($form->tanggal_sidang)
            ? SuratService::formatTanggalIndonesian($form->tanggal_sidang)
            : '';

        $rawJudul = trim($form->judul_skripsi ?? '');
        $cleanJudul = trim($rawJudul, '"\' ');
        $hasArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $cleanJudul);
        if ($hasArabic && class_exists('\ArPHP\I18N\Arabic')) {
            $arabic = new \ArPHP\I18N\Arabic();
            $cleanJudul = preg_replace('/\s+/u', ' ', $cleanJudul);
            $glyphs = $arabic->utf8Glyphs($cleanJudul, 65);
            $formattedJudul = ($cleanJudul !== $rawJudul) ? '"' . $glyphs . '"' : $glyphs;
        } else {
            $formattedJudul = $rawJudul;
        }

        $steps = $this->ensureStepsForForm($form)->keyBy('step_key');
        $qrSteps = [];
        foreach (self::STEP_ORDER as $key => $order) {
            /** @var PengambilanIjazahSignature|null $step */
            $step = $steps->get($key);
            $isApproved = $includeQr && $step && $step->status === 'approved';
            $qrSteps[$key] = [
                'title' => $step?->title ?: '',
                'signer_name' => $step?->signer_name ?: '',
                'signer_nidn' => $step?->signer_nidn ?: '',
                'is_signed' => $isApproved,
                'signed_at_date' => $isApproved && $step?->signed_at
                    ? $step->signed_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y')
                    : '',
                'qr_data_uri' => $isApproved ? $this->generateQrDataUri($step) : '',
            ];
        }

        return [
            'nim' => $form->nim,
            'nama_mhs' => $form->nama_mhs,
            'prodi' => $form->prodi,
            'dosen_pembimbing' => $form->dosen_pembimbing,
            'judul_skripsi' => $formattedJudul,
            'tanggal_sidang' => $tanggalSidangFormatted,
            'no_hp' => $form->no_hp,
            'kopBase64' => $kopBase64,
            'include_qr' => $includeQr,
            'qr_steps' => $qrSteps,
        ];
    }

    private function generateUniqueToken(): string
    {
        return Str::random(40);
    }
}
