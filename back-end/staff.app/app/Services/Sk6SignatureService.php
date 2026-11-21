<?php

namespace App\Services;

use App\Jobs\UploudSuratToDrive;
use App\Models\Prodi;
use App\Models\SettingJabatan;
use App\Models\Sk6Signature;
use App\Models\Sk6SignerDefault;
use App\Models\SuratKeterangan6;
use App\Models\SuratKeteranganLulusMataKuliah;
use App\Models\TandaTangan;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Sk6SignatureService
{
    public const STEP_ORDER = [
        'kaprodi' => 1,
        'baku' => 2,
        'kkn' => 3,
        'idaroh_syuun' => 4,
        'keamanan' => 5,
    ];

    public const SHORT_LABELS = [
        'kaprodi' => 'Kaprodi',
        'baku' => 'BAKU',
        'kkn' => 'KKN',
        'idaroh_syuun' => 'Warek I',
        'keamanan' => 'Keamanan',
    ];

    /**
     * Initial fallback defaults sourced from setting_jabatan / module presets.
     */
    private function buildInitialDefaults(): array
    {
        $settingBaku = SettingJabatan::with('tandaTangan')->where('kunci_jabatan', 'kepala_biro_keuangan')->first();
        $bakuNama = $settingBaku?->tandaTangan?->nama ?: 'Dr. Musleh Harry, SH, M.Hum';
        $bakuNidn = $settingBaku?->nidn;

        $settingKkn = SettingJabatan::with('tandaTangan')->where('kunci_jabatan', 'ketua_tasma')->first();
        $kknNama = $settingKkn?->tandaTangan?->nama ?: 'Achmad Djuaini, M.Pd';
        $kknNidn = $settingKkn?->nidn;

        $settingKompre = SettingJabatan::with('tandaTangan')->where('kunci_jabatan', 'ketua_komprehensif')->first();
        $warekNama = $settingKompre?->tandaTangan?->nama ?: 'Dr. Habib Zainal Abidin Bilfaqih, M.Pd.';
        $warekNidn = $settingKompre?->nidn;

        $settingQa = SettingJabatan::with('tandaTangan')->where('kunci_jabatan', 'ketua_qismul_aman')->first();
        $qaNama = $settingQa?->tandaTangan?->nama ?: 'Ust. Fathul Munif';
        $qaNidn = $settingQa?->nidn;

        return [
            [
                'step_order' => 1,
                'step_key' => 'kaprodi',
                'title' => 'Ketua Program Studi',
                'signer_name' => null,
                'signer_nidn' => null,
            ],
            [
                'step_order' => 2,
                'step_key' => 'baku',
                'title' => 'Kepala Biro Administrasi Keuangan',
                'signer_name' => $bakuNama,
                'signer_nidn' => $bakuNidn,
            ],
            [
                'step_order' => 3,
                'step_key' => 'kkn',
                'title' => 'Ketua TASMA, KKN & PPL',
                'signer_name' => $kknNama,
                'signer_nidn' => $kknNidn,
            ],
            [
                'step_order' => 4,
                'step_key' => 'idaroh_syuun',
                'title' => 'Wakil Rektor I',
                'signer_name' => $warekNama,
                'signer_nidn' => $warekNidn,
            ],
            [
                'step_order' => 5,
                'step_key' => 'keamanan',
                'title' => 'Ketua Qismul Aman',
                'signer_name' => $qaNama,
                'signer_nidn' => $qaNidn,
            ],
        ];
    }

    /**
     * Get module-level default signers for SK6 (seeding missing rows once).
     */
    public function getSignerDefaults(): array
    {
        $existing = Sk6SignerDefault::all()->keyBy('step_key');
        $fallback = $this->buildInitialDefaults();

        $results = [];
        foreach ($fallback as $def) {
            $row = $existing->get($def['step_key']);
            if (!$row) {
                $row = Sk6SignerDefault::create([
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
                'auto_per_prodi_hint' => $row->step_key === 'kaprodi',
            ];
        }

        usort($results, fn (array $a, array $b) => $a['step_order'] <=> $b['step_order']);
        return $results;
    }

    /**
     * Update module-level default signers for SK6 and optionally apply across existing SK6 records.
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
            $rawName = isset($item['signer_name']) ? trim((string) $item['signer_name']) : '';
            $signerNidn = isset($item['signer_nidn']) && trim((string) $item['signer_nidn']) !== ''
                ? trim((string) $item['signer_nidn'])
                : null;

            $row = Sk6SignerDefault::where('step_key', $stepKey)->first();
            if ($row) {
                $signerNameValue = $stepKey === 'kaprodi'
                    ? ($rawName !== '' ? $rawName : null)
                    : ($rawName !== '' ? $rawName : '-');

                $row->update([
                    'title' => $title !== '' ? $title : $row->title,
                    'signer_name' => $signerNameValue,
                    'signer_nidn' => $signerNidn,
                    'updated_by' => $userId,
                ]);
                $updatedStepKeys[$stepKey] = $row->fresh();
            }
        }

        $affectedSignatures = 0;
        if ($applyToExisting && !empty($updatedStepKeys)) {
            SuratKeterangan6::with([
                'prodi.tandaTangan',
                'skAdminKeuangan',
                'skTasmaKknPpl',
                'skUjianKomprehensifDiniyah',
                'skQismulAman',
            ])->chunkById(100, function ($sk6List) {
                foreach ($sk6List as $sk6) {
                    $this->ensureStepsForSk6($sk6);
                }
            });

            foreach ($updatedStepKeys as $stepKey => $defRow) {
                if ($stepKey === 'kaprodi') {
                    if (!empty($defRow->signer_name)) {
                        $query = Sk6Signature::where('step_key', 'kaprodi');
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
                    continue;
                }

                $query = Sk6Signature::where('step_key', $stepKey);
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
     * Resolve default metadata (order, title, signer_name, signer_nidn) for the 5 SK6 steps.
     */
    public function resolveStepDefinitions(SuratKeterangan6 $sk6): array
    {
        $sk6->loadMissing([
            'prodi.tandaTangan',
            'skAdminKeuangan',
            'skTasmaKknPpl',
            'skUjianKomprehensifDiniyah',
            'skQismulAman',
        ]);

        $defaults = collect($this->getSignerDefaults())->keyBy('step_key');

        $prodi = $sk6->prodi ?: ($sk6->prodi_id ? Prodi::with('tandaTangan')->find($sk6->prodi_id) : null);
        $prodiNama = $prodi?->nama;
        $defKaprodi = $defaults->get('kaprodi');
        $hasKaprodiOverride = !empty($defKaprodi['signer_name']);
        $kaprodiTitle = $hasKaprodiOverride
            ? ($defKaprodi['title'] ?: ('Ketua Program Studi' . ($prodiNama ? ' ' . $prodiNama : '')))
            : (($defKaprodi['title'] ?: 'Ketua Program Studi') . ($prodiNama ? ' ' . $prodiNama : ''));
        $kaprodiNama = $hasKaprodiOverride
            ? $defKaprodi['signer_name']
            : ($prodi?->tandaTangan?->nama ?: ($prodi?->nama_kepala ?: '-'));
        $kaprodiNidn = $hasKaprodiOverride
            ? ($defKaprodi['signer_nidn'] ?? null)
            : $prodi?->nidn_kepala;

        $defBaku = $defaults->get('baku');
        $bakuTitle = $defBaku['title'] ?? 'Kepala Biro Administrasi Keuangan';
        $bakuNama = $defBaku['signer_name'] ?: ($sk6->skAdminKeuangan?->kepala_biro ?: 'Dr. Musleh Harry, SH, M.Hum');
        $bakuNidn = $defBaku['signer_nidn'] ?? null;

        $defKkn = $defaults->get('kkn');
        $kknTitle = $defKkn['title'] ?? 'Ketua TASMA, KKN & PPL';
        $kknNama = $defKkn['signer_name'] ?: ($sk6->skTasmaKknPpl?->ketua ?: 'Achmad Djuaini, M.Pd');
        $kknNidn = $defKkn['signer_nidn'] ?? $sk6->skTasmaKknPpl?->nidn;

        $skukd = $sk6->skUjianKomprehensifDiniyah;
        $defWarek = $defaults->get('idaroh_syuun');
        $warekTitle = $defWarek['title'] ?? 'Wakil Rektor I';
        $warekNama = $defWarek['signer_name'] ?? null;
        if (empty($warekNama) && $skukd && $skukd->tanda_tangan_id) {
            $ttdWarek = TandaTangan::find($skukd->tanda_tangan_id);
            $warekNama = $ttdWarek?->nama;
        }
        if (empty($warekNama)) {
            $warekNama = $skukd?->koor_komprehensif ?: 'Dr. Habib Zainal Abidin Bilfaqih, M.Pd.';
        }
        $warekNidn = $defWarek['signer_nidn'] ?? $skukd?->nidn;

        $defQa = $defaults->get('keamanan');
        $qaTitle = $defQa['title'] ?? 'Ketua Qismul Aman';
        $qaNama = $defQa['signer_name'] ?: ($sk6->skQismulAman?->ketua ?: 'Ust. Fathul Munif');
        $qaNidn = $defQa['signer_nidn'] ?? $sk6->skQismulAman?->nidn;

        return [
            [
                'step_order' => 1,
                'step_key' => 'kaprodi',
                'title' => $kaprodiTitle,
                'signer_name' => $kaprodiNama,
                'signer_nidn' => $kaprodiNidn,
            ],
            [
                'step_order' => 2,
                'step_key' => 'baku',
                'title' => $bakuTitle,
                'signer_name' => $bakuNama,
                'signer_nidn' => $bakuNidn,
            ],
            [
                'step_order' => 3,
                'step_key' => 'kkn',
                'title' => $kknTitle,
                'signer_name' => $kknNama,
                'signer_nidn' => $kknNidn,
            ],
            [
                'step_order' => 4,
                'step_key' => 'idaroh_syuun',
                'title' => $warekTitle,
                'signer_name' => $warekNama,
                'signer_nidn' => $warekNidn,
            ],
            [
                'step_order' => 5,
                'step_key' => 'keamanan',
                'title' => $qaTitle,
                'signer_name' => $qaNama,
                'signer_nidn' => $qaNidn,
            ],
        ];
    }

    /**
     * Ensure all 5 timeline steps exist for a given SK6 record (supports legacy SK6 rows).
     */
    public function ensureStepsForSk6(SuratKeterangan6 $sk6): Collection
    {
        $definitions = $this->resolveStepDefinitions($sk6);
        $existing = Sk6Signature::where('surat_keterangan_6_id', $sk6->id)
            ->get()
            ->keyBy('step_key');

        foreach ($definitions as $def) {
            if (!$existing->has($def['step_key'])) {
                $created = Sk6Signature::create([
                    'surat_keterangan_6_id' => $sk6->id,
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
    public function formatTimeline(SuratKeterangan6 $sk6, bool $withQrDataUri = false): array
    {
        $steps = $this->ensureStepsForSk6($sk6);

        return $steps->map(function (Sk6Signature $step) use ($withQrDataUri) {
            $verifyUrl = url('/verify/sk6/' . $step->signature_token);
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
     * Confirm (sign via QR confirmation) a specific step and regenerate the SK6 PDF.
     */
    public function confirmStep(SuratKeterangan6 $sk6, string $stepKey, array $options = []): Sk6Signature
    {
        if (!array_key_exists($stepKey, self::STEP_ORDER)) {
            throw new \InvalidArgumentException("Step key '{$stepKey}' tidak valid.");
        }

        $steps = $this->ensureStepsForSk6($sk6);
        /** @var Sk6Signature $step */
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
        $sk6->refresh();

        if ($this->isCompleted($sk6)) {
            $this->regenerateSignedSk6Pdf($sk6);
            $this->queueCompleteDriveUpload($sk6);
        }

        return $step->fresh();
    }

    /**
     * Revoke (reset to pending) a specific step and clear signed PDF / Drive artifacts.
     */
    public function revokeStep(SuratKeterangan6 $sk6, string $stepKey): Sk6Signature
    {
        if (!array_key_exists($stepKey, self::STEP_ORDER)) {
            throw new \InvalidArgumentException("Step key '{$stepKey}' tidak valid.");
        }

        $steps = $this->ensureStepsForSk6($sk6);
        /** @var Sk6Signature $step */
        $step = $steps->firstWhere('step_key', $stepKey);

        $step->update([
            'status' => 'pending',
            'signed_at' => null,
            'signed_by_source' => null,
            'signed_by_user_id' => null,
            'notes' => null,
        ]);

        $sk6->refresh();
        $this->clearOldDriveFile($sk6);

        return $step->fresh();
    }

    /**
     * Check whether all 5 required QR signature steps are approved.
     */
    public function isCompleted(SuratKeterangan6 $sk6): bool
    {
        $steps = $this->ensureStepsForSk6($sk6);
        return $steps->count() === count(self::STEP_ORDER)
            && $steps->every(fn (Sk6Signature $s) => $s->status === 'approved');
    }

    /**
     * Dispatch Google Drive upload job for the finalized SK6 PDF (with all 5 QR codes).
     */
    public function queueCompleteDriveUpload(SuratKeterangan6 $sk6): void
    {
        $sk6->loadMissing('prodi');
        $this->clearOldDriveFile($sk6, keepLocalFile: true);

        if (!empty($sk6->signed_local_path) && file_exists($sk6->signed_local_path)) {
            $prodiName = $sk6->prodi?->nama ?? 'UMUM';
            UploudSuratToDrive::dispatch(
                $sk6->id,
                'Surat Keterangan 6',
                $prodiName,
                SuratKeterangan6::class,
                null,
                true
            );
        }
    }

    private function clearOldDriveFile(SuratKeterangan6 $sk6, bool $keepLocalFile = false): void
    {
        if (!empty($sk6->signed_drive_file_id)) {
            try {
                GoogleDrive::deleteFile($sk6->signed_drive_file_id);
            } catch (\Throwable $e) {
                Log::warning('Failed deleting old signed SK6 Drive file: ' . $e->getMessage());
            }
        }

        $updates = [];
        if (!empty($sk6->signed_drive_file_id) || !empty($sk6->signed_drive_link)) {
            $updates['signed_drive_file_id'] = null;
            $updates['signed_drive_link'] = null;
        }

        if (!$keepLocalFile && !empty($sk6->signed_local_path)) {
            if (file_exists($sk6->signed_local_path)) {
                @File::delete($sk6->signed_local_path);
            }
            $updates['signed_local_path'] = null;
        }

        if (!empty($updates)) {
            $sk6->forceFill($updates)->save();
        }
    }

    /**
     * Render QR code data URI (modern circular dot QR with UII Dalwa logo centered) pointing to verification URL.
     */
    public function generateQrDataUri(Sk6Signature $step): string
    {
        return QrCodeSignatureService::generateStyledVerifyQr(url('/verify/sk6/' . $step->signature_token));
    }

    /**
     * Regenerate combined SK6 PDF file with current QR signature states.
     */
    public function regenerateSk6Pdf(SuratKeterangan6 $sk6): ?string
    {
        return $this->regenerateSignedSk6Pdf($sk6);
    }

    /**
     * Generate & store the signed SK6 PDF (with QR codes) in signed_local_path.
     */
    public function regenerateSignedSk6Pdf(SuratKeterangan6 $sk6): ?string
    {
        $sklmk = SuratKeteranganLulusMataKuliah::leftJoin('prodi', 'prodi.id', '=', 'surat_keterangan_lulus_mata_kuliah.prodi_id')
            ->leftJoin('fakultas_prodi', 'fakultas_prodi.prodi_id', '=', 'prodi.id')
            ->leftJoin('fakultas', 'fakultas.id', '=', 'fakultas_prodi.fakultas_id')
            ->leftJoin('tanda_tangan as tt_prodi', 'tt_prodi.id', '=', 'prodi.tanda_tangan_id')
            ->select(
                'surat_keterangan_lulus_mata_kuliah.*',
                'prodi.nama as nama_prodi',
                'fakultas.nama as fakultas',
                'prodi.alias as alias_prodi',
                'prodi.nama_kepala as nama_kepala_prodi',
                'tt_prodi.nama as kaprodi_nama',
                'tt_prodi.gambar as kaprodi_gambar',
                'tt_prodi.tdd as kaprodi_tdd'
            )
            ->where('surat_keterangan_lulus_mata_kuliah.id', $sk6->surat_keterangan_lulus_mata_kuliah_id)
            ->first();

        if (!$sklmk) {
            return null;
        }

        $pdfData = $this->buildSk6PdfData($sk6, $sklmk, true);
        $pdf = Pdf::loadView('pdf.sk_6', $pdfData)->setPaper('a4', 'portrait');

        $prodiFolder = preg_replace('/[\\\\\/:*?"<>|]/', ' ', $sklmk->nama_prodi ?? 'UMUM');
        $prodiFolder = trim(preg_replace('/\s+/', ' ', $prodiFolder)) ?: 'UMUM';
        $directory = base_path('../public_html/pdf/' . $prodiFolder . '/Sk6Controller');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $fileName = 'SK6_TTD_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $sklmk->nim) . '_' . $sk6->id . '.pdf';
        $path = $directory . '/' . $fileName;

        if (!empty($sk6->signed_local_path) && $sk6->signed_local_path !== $path && File::exists($sk6->signed_local_path)) {
            @File::delete($sk6->signed_local_path);
        }

        $pdf->save($path);
        $sk6->update(['signed_local_path' => $path]);

        return $path;
    }

    /**
     * Build Blade data array for pdf.sk_6 (optionally including QR codes for approved steps).
     */
    public function buildSk6PdfData(SuratKeterangan6 $sk6, $sklmk, bool $includeQr = true): array
    {
        $sk6->loadMissing([
            'skAdminKeuangan',
            'skTasmaKknPpl',
            'skUjianKomprehensifDiniyah',
            'skQismulAman',
        ]);

        $skak = $sk6->skAdminKeuangan;
        $sktkp = $sk6->skTasmaKknPpl;
        $skukd = $sk6->skUjianKomprehensifDiniyah;
        $skqa = $sk6->skQismulAman;

        $steps = $this->ensureStepsForSk6($sk6)->keyBy('step_key');
        $qrCodes = [];
        foreach (array_keys(self::STEP_ORDER) as $key) {
            $step = $steps->get($key);
            $qrCodes[$key] = ($includeQr && $step && $step->status === 'approved')
                ? $this->generateQrDataUri($step)
                : '';
        }

        $kopPath = base_path('../public_html/img/kop.jpg');
        $kopBase64 = SuratService::getBase64Image($kopPath);

        $stempelPath = base_path('../public_html/img/stempel.png');
        $stempelBase64 = SuratService::getBase64Image($stempelPath);

        $kaprodiStep = $steps->get('kaprodi');
        $bakuStep = $steps->get('baku');
        $kknStep = $steps->get('kkn');
        $warekStep = $steps->get('idaroh_syuun');
        $qaStep = $steps->get('keamanan');

        return [
            'nomor_surat_sklmk' => $sklmk->nomor_surat,
            'nomor_surat_skak' => $skak ? $skak->nomor_surat : '-',
            'nomor_surat_sktkp' => $sktkp ? $sktkp->nomor_surat : '-',
            'nomor_surat_skukd' => $skukd ? $skukd->nomor_surat : '-',
            'nomor_surat_skqa' => $skqa ? $skqa->nomor_surat : '-',

            'nama' => $sklmk->nama_lengkap,
            'tempat_lahir' => $sklmk->tempat_lahir,
            'tanggal_lahir' => SuratService::formatTanggalIndonesian($sklmk->tanggal_lahir),
            'nim' => $sklmk->nim,
            'fakultas' => $sklmk->fakultas,
            'prodi' => $sklmk->nama_prodi,
            'alamat' => $sklmk->alamat_rumah,
            'kelas' => $sklmk->kelas_pondok,
            'alias_prodi' => $sklmk->alias_prodi,
            'nama_kepala_prodi' => $sklmk->nama_kepala_prodi,
            'tanggal_surat' => SuratService::formatTanggalIndonesian($sklmk->tanggal),
            'kopBase64' => $kopBase64,
            'tanggal_awal' => $skqa && $skqa->tanggal_berlaku_dari ? SuratService::formatTanggalIndonesian($skqa->tanggal_berlaku_dari) : '-',
            'tanggal_akhir' => $skqa && $skqa->tanggal_berlaku_sampai ? SuratService::formatTanggalIndonesian($skqa->tanggal_berlaku_sampai) : '-',

            // Signer names & titles synced with sk6_signatures
            'kaprodi_title' => $kaprodiStep?->title ?: ('Ketua Program Studi ' . ($sklmk->nama_prodi ?? '')),
            'kaprodi_nama' => $kaprodiStep?->signer_name ?: ($sklmk->kaprodi_nama ?: $sklmk->nama_kepala_prodi),
            'kaprodi_nidn' => $kaprodiStep?->signer_nidn,
            'qr_kaprodi' => $qrCodes['kaprodi'],

            'skak_title' => $bakuStep?->title ?: 'Kepala Biro Administrasi Keuangan',
            'skak_nama' => $bakuStep?->signer_name ?: 'Dr. Musleh Harry, SH, M.Hum',
            'skak_nidn' => $bakuStep?->signer_nidn,
            'qr_baku' => $qrCodes['baku'],

            'sktkp_title' => $kknStep?->title ?: 'Ketua TASMA, KKN & PPL',
            'sktkp_nama' => $kknStep?->signer_name ?: 'Achmad Djuaini, M.Pd',
            'sktkp_nidn' => $kknStep?->signer_nidn,
            'qr_kkn' => $qrCodes['kkn'],

            'skukd_title' => $warekStep?->title ?: 'Wakil Rektor I',
            'skukd_nama' => $warekStep?->signer_name ?: 'Dr. Habib Zainal Abidin Bilfaqih, M.Pd.',
            'skukd_nidn' => $warekStep?->signer_nidn,
            'qr_idaroh_syuun' => $qrCodes['idaroh_syuun'],

            'skqa_title' => $qaStep?->title ?: 'Ketua Qismul Aman',
            'skqa_nama' => $qaStep?->signer_name ?: 'Ust. Fathul Munif',
            'skqa_nidn' => $qaStep?->signer_nidn,
            'qr_keamanan' => $qrCodes['keamanan'],

            'stempel' => $stempelBase64,
            'petanda_tangan' => 'tidak',
        ];
    }

    private function generateUniqueToken(): string
    {
        return Str::random(40);
    }
}
