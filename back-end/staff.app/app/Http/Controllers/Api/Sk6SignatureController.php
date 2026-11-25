<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationApiKey;
use App\Models\Sk6Signature;
use App\Models\SuratKeterangan6;
use App\Services\SignatureStepFilter;
use App\Services\Sk6SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Sk6SignatureController extends Controller
{
    private const STEP_RULE = 'required|in:kaprodi,kkn,keamanan,baku_kabag,baku_aupk,idaroh_syuun,warek_1';
    private const STEP_ITEM_RULE = 'string|in:kaprodi,kkn,keamanan,baku_kabag,baku_aupk,idaroh_syuun,warek_1';

    public function __construct(private Sk6SignatureService $signatureService)
    {
    }

    /**
     * Internal UI: Get 6-step signature timeline for an SK6 record.
     */
    public function timeline($id)
    {
        $sk6 = SuratKeterangan6::with(['prodi', 'skLulusMk', 'skAdminKeuangan', 'skTasmaKknPpl', 'skUjianKomprehensifDiniyah', 'skQismulAman'])->find($id);
        if (!$sk6) {
            return response()->json([
                'status' => false,
                'message' => 'Data SK6 tidak ditemukan',
            ], 404);
        }

        $steps = $this->signatureService->formatTimeline($sk6, true, false);
        $signedCount = collect($steps)->where('status', 'approved')->count();

        return response()->json([
            'status' => true,
            'data' => [
                'sk6' => [
                    'id' => $sk6->id,
                    'nama_mhs' => $sk6->nama_mhs,
                    'nim' => $sk6->nim,
                    'prodi_id' => $sk6->prodi_id,
                    'prodi_nama' => $sk6->prodi?->nama,
                    'tanggal' => $sk6->tanggal,
                    'signed_count' => $signedCount,
                    'total_steps' => count($steps),
                    'is_completed' => $this->signatureService->isCompleted($sk6),
                ],
                'steps' => $steps,
            ],
            'message' => 'Timeline TTD SK6 berhasil diambil',
        ]);
    }

    /**
     * Internal UI: Confirm/sign a step directly from staff web app.
     */
    public function confirm(Request $request, $id, string $stepKey)
    {
        if (!$this->signatureService->isStaffSignableStep($stepKey)) {
            return response()->json([
                'status' => false,
                'message' => 'Tahap ini hanya dapat ditandatangani melalui website pihak terkait (eksternal).',
            ], 403);
        }

        $sk6 = SuratKeterangan6::find($id);
        if (!$sk6) {
            return response()->json([
                'status' => false,
                'message' => 'Data SK6 tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'signer_name' => 'nullable|string|max:255',
            'signer_nidn' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->signatureService->confirmStep($sk6, $stepKey, [
                'signer_name' => $request->input('signer_name'),
                'signer_nidn' => $request->input('signer_nidn'),
                'notes' => $request->input('notes'),
                'source' => 'web_staff',
                'user_id' => Auth::id(),
            ]);

            $freshSk6 = $sk6->fresh();
            $steps = $this->signatureService->formatTimeline($freshSk6, true, false);
            $signedCount = collect($steps)->where('status', 'approved')->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'steps' => $steps,
                    'signed_count' => $signedCount,
                    'total_steps' => count($steps),
                    'is_completed' => $this->signatureService->isCompleted($freshSk6),
                ],
                'message' => 'Konfirmasi TTD QR berhasil disimpan',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Internal UI: Revoke a step confirmation back to pending.
     */
    public function revoke($id, string $stepKey)
    {
        if (!$this->signatureService->isStaffSignableStep($stepKey)) {
            return response()->json([
                'status' => false,
                'message' => 'Tahap ini hanya dapat dibatalkan melalui website pihak terkait (eksternal).',
            ], 403);
        }

        $sk6 = SuratKeterangan6::find($id);
        if (!$sk6) {
            return response()->json([
                'status' => false,
                'message' => 'Data SK6 tidak ditemukan',
            ], 404);
        }

        try {
            $this->signatureService->revokeStep($sk6, $stepKey);
            $freshSk6 = $sk6->fresh();
            $steps = $this->signatureService->formatTimeline($freshSk6, true, false);
            $signedCount = collect($steps)->where('status', 'approved')->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'steps' => $steps,
                    'signed_count' => $signedCount,
                    'total_steps' => count($steps),
                    'is_completed' => $this->signatureService->isCompleted($freshSk6),
                ],
                'message' => 'Konfirmasi TTD berhasil dibatalkan',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Internal UI: Get Base URL & API Key configuration for external integration.
     */
    public function integrationConfig(Request $request)
    {
        $record = IntegrationApiKey::getOrCreateDefault(Auth::id());

        return response()->json([
            'status' => true,
            'data' => [
                'base_url' => url('/api/external/sk6'),
                'verify_base_url' => url('/verify/sk6'),
                'api_key' => $record->api_key,
                'is_active' => $record->is_active,
                'last_used_at' => $record->last_used_at?->toIso8601String(),
                'updated_at' => $record->updated_at?->toIso8601String(),
                'step_keys' => [
                    ['step_order' => 1, 'step_key' => 'kaprodi', 'title' => 'Ketua Program Studi ({Prodi})'],
                    ['step_order' => 2, 'step_key' => 'kkn', 'title' => 'Ketua TASMA, KKN & PPL'],
                    ['step_order' => 3, 'step_key' => 'keamanan', 'title' => 'Ketua Qismul Aman'],
                    ['step_order' => 4, 'step_key' => 'baku_kabag', 'title' => 'Kabag BAKU'],
                    ['step_order' => 5, 'step_key' => 'baku_aupk', 'title' => 'Kepala Biro AUPK'],
                    ['step_order' => 6, 'step_key' => 'idaroh_syuun', 'title' => 'Idaroh Syuun (Tanpa Surat)'],
                    ['step_order' => 7, 'step_key' => 'warek_1', 'title' => 'Wakil Rektor 1'],
                ],
            ],
            'message' => 'Konfigurasi integrasi API SK6 berhasil diambil',
        ]);
    }

    /**
     * Internal UI: Regenerate API Key for external integration.
     */
    public function regenerateApiKey()
    {
        $record = IntegrationApiKey::getOrCreateDefault(Auth::id());
        $record->update([
            'api_key' => IntegrationApiKey::generateKey(),
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'base_url' => url('/api/external/sk6'),
                'verify_base_url' => url('/verify/sk6'),
                'api_key' => $record->api_key,
                'is_active' => $record->is_active,
                'updated_at' => $record->updated_at?->toIso8601String(),
            ],
            'message' => 'API Key berhasil digenerate ulang',
        ]);
    }

    /**
     * Internal UI: Get module-level default signers for all 5 SK6 steps.
     */
    public function getSignerDefaults()
    {
        return response()->json([
            'status' => true,
            'data' => $this->signatureService->getSignerDefaults(),
            'message' => 'Default penandatangan SK6 berhasil diambil',
        ]);
    }

    /**
     * Internal UI: Update module-level default signers for SK6 and optionally apply to existing records.
     */
    public function updateSignerDefaults(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'steps' => 'required|array|min:1',
            'steps.*.step_key' => self::STEP_RULE,
            'steps.*.title' => 'required|string|max:255',
            'steps.*.signer_name' => 'nullable|string|max:255',
            'steps.*.signer_nidn' => 'nullable|string|max:100',
            'apply_to_existing' => 'sometimes|boolean',
            'apply_scope' => 'sometimes|in:all,pending',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->signatureService->updateSignerDefaults(
            $request->input('steps'),
            $request->boolean('apply_to_existing', true),
            $request->input('apply_scope', 'all'),
            Auth::id()
        );

        return response()->json([
            'status' => true,
            'data' => $result,
            'message' => $result['applied_to_existing']
                ? "Default penandatangan SK6 disimpan dan diterapkan ke {$result['affected_signatures']} tahap tanda tangan."
                : 'Default penandatangan SK6 berhasil disimpan untuk data baru berikutnya.',
        ]);
    }

    /**
     * External API: List SK6 records with signature progress.
     */
    public function externalIndex(Request $request)
    {
        $query = SuratKeterangan6::with(['prodi', 'skLulusMk', 'skAdminKeuangan', 'skTasmaKknPpl', 'skUjianKomprehensifDiniyah', 'skQismulAman'])
            ->orderByDesc('id');

        if ($request->filled('nim')) {
            $query->where('nim', $request->input('nim'));
        }
        if ($request->filled('prodi_id')) {
            $query->where('prodi_id', $request->input('prodi_id'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                    ->orWhere('nama_mhs', 'like', "%{$search}%");
            });
        }

        try {
            $gender = SignatureStepFilter::resolveGenderFilter($request);
            if ($gender !== null) {
                $query->whereHas('skLulusMk', function ($q) use ($gender) {
                    $q->where('jenis_kelamin', $gender);
                });
            }

            SignatureStepFilter::applyExternalListFilters($query, $request, Sk6SignatureService::STEP_ORDER);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $limit = min(max((int) $request->input('limit', 15), 1), 100);
        $paginated = $query->paginate($limit);

        $rows = $paginated->getCollection()->map(function (SuratKeterangan6 $sk6) {
            return $this->formatExternalItem($sk6);
        });

        return response()->json([
            'status' => true,
            'data' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'items' => $rows,
            ],
            'message' => 'Daftar SK6 berhasil diambil',
        ]);
    }

    /**
     * External API: Show single SK6 by ID with full 5-step timeline.
     */
    public function externalShow($id)
    {
        $sk6 = SuratKeterangan6::with(['prodi', 'skLulusMk', 'skAdminKeuangan', 'skTasmaKknPpl', 'skUjianKomprehensifDiniyah', 'skQismulAman'])->find($id);
        if (!$sk6) {
            return response()->json([
                'status' => false,
                'message' => 'Data SK6 tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatExternalItem($sk6, true),
            'message' => 'Detail SK6 berhasil diambil',
        ]);
    }

    /**
     * External API: Show latest SK6 by student NIM.
     */
    public function externalShowByNim(string $nim)
    {
        $sk6 = SuratKeterangan6::with(['prodi', 'skLulusMk', 'skAdminKeuangan', 'skTasmaKknPpl', 'skUjianKomprehensifDiniyah', 'skQismulAman'])
            ->where('nim', $nim)
            ->orderByDesc('id')
            ->first();

        if (!$sk6) {
            return response()->json([
                'status' => false,
                'message' => "Data SK6 untuk NIM '{$nim}' tidak ditemukan",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatExternalItem($sk6, true),
            'message' => 'Detail SK6 berhasil diambil',
        ]);
    }

    /**
     * External API: Confirm/sign a step by SK6 ID.
     */
    public function externalSign(Request $request, $id)
    {
        $sk6 = SuratKeterangan6::find($id);
        if (!$sk6) {
            return response()->json([
                'status' => false,
                'message' => 'Data SK6 tidak ditemukan',
            ], 404);
        }

        return $this->handleExternalSign($request, $sk6);
    }

    /**
     * External API: Confirm/sign a step by student NIM (uses latest SK6 for NIM).
     */
    public function externalSignByNim(Request $request, string $nim)
    {
        $sk6 = SuratKeterangan6::where('nim', $nim)->orderByDesc('id')->first();
        if (!$sk6) {
            return response()->json([
                'status' => false,
                'message' => "Data SK6 untuk NIM '{$nim}' tidak ditemukan",
            ], 404);
        }

        return $this->handleExternalSign($request, $sk6);
    }

    /**
     * External API: Revoke a step confirmation by SK6 ID.
     */
    public function externalRevoke(Request $request, $id)
    {
        $sk6 = SuratKeterangan6::find($id);
        if (!$sk6) {
            return response()->json([
                'status' => false,
                'message' => 'Data SK6 tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'step_key' => self::STEP_RULE,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->signatureService->revokeStep($sk6, $request->input('step_key'));

        return response()->json([
            'status' => true,
            'data' => $this->formatExternalItem($sk6->fresh(), true),
            'message' => 'Konfirmasi TTD berhasil dibatalkan',
        ]);
    }

    /**
     * Public API: Verify QR token as JSON.
     */
    public function verifyJson(string $token)
    {
        $sig = Sk6Signature::with(['suratKeterangan6.prodi', 'suratKeterangan6.skLulusMk'])
            ->where('signature_token', $token)
            ->first();

        if (!$sig || $sig->status !== 'approved') {
            return response()->json([
                'status' => false,
                'verified' => false,
                'message' => 'Tanda tangan QR tidak ditemukan atau belum terkonfirmasi.',
            ], 404);
        }

        $sk6 = $sig->suratKeterangan6;
        return response()->json([
            'status' => true,
            'verified' => true,
            'data' => [
                'nama_mhs' => $sk6?->nama_mhs,
                'nim' => $sk6?->nim,
                'prodi' => $sk6?->prodi?->nama,
                'step_key' => $sig->step_key,
                'title' => $sig->title,
                'signer_name' => $sig->signer_name,
                'signer_nidn' => $sig->signer_nidn,
                'signed_at' => $sig->signed_at?->toIso8601String(),
                'signed_by_source' => $sig->signed_by_source,
            ],
            'message' => 'Tanda tangan QR sah dan terverifikasi.',
        ]);
    }

    /**
     * Public Web Page: Verify QR token when scanned via phone camera.
     */
    public function verifyPage(string $token)
    {
        $sig = Sk6Signature::with([
            'suratKeterangan6.prodi',
            'suratKeterangan6.skLulusMk',
            'suratKeterangan6.skAdminKeuangan',
            'suratKeterangan6.skTasmaKknPpl',
            'suratKeterangan6.skUjianKomprehensifDiniyah',
            'suratKeterangan6.skQismulAman',
        ])->where('signature_token', $token)->first();

        $timeline = $sig && $sig->suratKeterangan6
            ? $this->signatureService->formatTimeline($sig->suratKeterangan6, false, false)
            : [];

        return response()->view('verify.sk6', [
            'signature' => $sig,
            'sk6' => $sig?->suratKeterangan6,
            'timeline' => $timeline,
        ]);
    }

    private function handleExternalSign(Request $request, SuratKeterangan6 $sk6)
    {
        $validator = Validator::make($request->all(), [
            'step_key' => self::STEP_RULE,
            'signer_name' => 'nullable|string|max:255',
            'signer_nidn' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'require_previous_approved' => 'sometimes|boolean',
            'required_steps' => 'sometimes|array',
            'required_steps.*' => self::STEP_ITEM_RULE,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $source = $request->input('source') ?: ('api:' . $request->input('step_key'));
        try {
            $step = $this->signatureService->confirmStep($sk6, $request->input('step_key'), [
                'signer_name' => $request->input('signer_name'),
                'signer_nidn' => $request->input('signer_nidn'),
                'title' => $request->input('title'),
                'notes' => $request->input('notes'),
                'source' => $source,
                'require_previous_approved' => $request->boolean('require_previous_approved', false),
                'required_steps' => $request->input('required_steps', []),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'signed_step' => [
                    'step_order' => $step->step_order,
                    'step_key' => $step->step_key,
                    'title' => $step->title,
                    'signer_name' => $step->signer_name,
                    'signer_nidn' => $step->signer_nidn,
                    'status' => $step->status,
                    'signed_at' => $step->signed_at?->toIso8601String(),
                    'signed_by_source' => $step->signed_by_source,
                    'verify_url' => url('/verify/sk6/' . $step->signature_token),
                ],
                'sk6' => $this->formatExternalItem($sk6->fresh(), true),
            ],
            'message' => "TTD QR untuk step '{$step->step_key}' berhasil dikonfirmasi",
        ]);
    }

    private function formatExternalItem(SuratKeterangan6 $sk6, bool $withQr = false): array
    {
        $steps = $this->signatureService->formatTimeline($sk6, $withQr);
        $signedCount = collect($steps)->where('status', 'approved')->count();

        return [
            'id' => $sk6->id,
            'nama_mhs' => $sk6->nama_mhs,
            'nim' => $sk6->nim,
            'prodi_id' => $sk6->prodi_id,
            'prodi_nama' => $sk6->prodi?->nama,
            'jenis_kelamin' => $sk6->skLulusMk?->jenis_kelamin,
            'tanggal' => $sk6->tanggal,
            'nomor_surat' => [
                'sklmk' => $sk6->skLulusMk?->nomor_surat,
                'skak' => $sk6->skAdminKeuangan?->nomor_surat,
                'sktkp' => $sk6->skTasmaKknPpl?->nomor_surat,
                'skukd' => $sk6->skUjianKomprehensifDiniyah?->nomor_surat,
                'skqa' => $sk6->skQismulAman?->nomor_surat,
            ],
            'signed_count' => $signedCount,
            'total_steps' => count($steps),
            'is_completed' => $signedCount === count($steps),
            'steps' => $steps,
        ];
    }
}
