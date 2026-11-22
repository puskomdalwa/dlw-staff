<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormPengambilanIjazah;
use App\Models\IntegrationApiKey;
use App\Models\PengambilanIjazahSignature;
use App\Services\PengambilanIjazahSignatureService;
use App\Services\SignatureStepFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PengambilanIjazahSignatureController extends Controller
{
    private const SERVICE_KEY = 'pengambilan_ijazah_external';
    private const SERVICE_NAME = 'Form Pengambilan Ijazah External Integration';
    private const KEY_PREFIX = 'pij_';
    private const STEP_RULE = 'required|in:keuangan_pondok,baku,baak,warek_1';

    public function __construct(private PengambilanIjazahSignatureService $signatureService)
    {
    }

    /**
     * Internal UI: Get 4-step signature timeline for a FormPengambilanIjazah record.
     */
    public function timeline($id)
    {
        $form = FormPengambilanIjazah::find($id);
        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Data Form Pengambilan Ijazah tidak ditemukan',
            ], 404);
        }

        $steps = $this->signatureService->formatTimeline($form, true);
        $signedCount = collect($steps)->where('status', 'approved')->count();

        return response()->json([
            'status' => true,
            'data' => [
                'form' => [
                    'id' => $form->id,
                    'nama_mhs' => $form->nama_mhs,
                    'nim' => $form->nim,
                    'prodi' => $form->prodi,
                    'jenis_kelamin' => $form->jenis_kelamin,
                    'tanggal_sidang' => $form->tanggal_sidang,
                    'signed_count' => $signedCount,
                    'total_steps' => count($steps),
                    'is_completed' => $signedCount === count($steps),
                    'drive_link' => $form->drive_link,
                    'signed_drive_link' => $form->signed_drive_link,
                ],
                'steps' => $steps,
            ],
            'message' => 'Timeline TTD Form Pengambilan Ijazah berhasil diambil',
        ]);
    }

    /**
     * Internal UI: Confirm/sign a step directly from staff web app.
     */
    public function confirm(Request $request, $id, string $stepKey)
    {
        $form = FormPengambilanIjazah::find($id);
        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Data Form Pengambilan Ijazah tidak ditemukan',
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
            $this->signatureService->confirmStep($form, $stepKey, [
                'signer_name' => $request->input('signer_name'),
                'signer_nidn' => $request->input('signer_nidn'),
                'notes' => $request->input('notes'),
                'source' => 'web_staff',
                'user_id' => Auth::id(),
            ]);

            $fresh = $form->fresh();
            $steps = $this->signatureService->formatTimeline($fresh, true);
            $signedCount = collect($steps)->where('status', 'approved')->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'steps' => $steps,
                    'signed_count' => $signedCount,
                    'total_steps' => count($steps),
                    'is_completed' => $signedCount === count($steps),
                    'drive_link' => $fresh->drive_link,
                    'signed_drive_link' => $fresh->signed_drive_link,
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
        $form = FormPengambilanIjazah::find($id);
        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Data Form Pengambilan Ijazah tidak ditemukan',
            ], 404);
        }

        try {
            $this->signatureService->revokeStep($form, $stepKey);
            $fresh = $form->fresh();
            $steps = $this->signatureService->formatTimeline($fresh, true);
            $signedCount = collect($steps)->where('status', 'approved')->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'steps' => $steps,
                    'signed_count' => $signedCount,
                    'total_steps' => count($steps),
                    'is_completed' => $signedCount === count($steps),
                    'drive_link' => $fresh->drive_link,
                    'signed_drive_link' => $fresh->signed_drive_link,
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
    public function integrationConfig()
    {
        $record = IntegrationApiKey::getOrCreateForService(
            self::SERVICE_KEY,
            self::SERVICE_NAME,
            self::KEY_PREFIX,
            Auth::id()
        );

        return response()->json([
            'status' => true,
            'data' => [
                'base_url' => url('/api/external/form-pengambilan-ijazah'),
                'verify_base_url' => url('/verify/form-pengambilan-ijazah'),
                'api_key' => $record->api_key,
                'is_active' => $record->is_active,
                'last_used_at' => $record->last_used_at?->toIso8601String(),
                'updated_at' => $record->updated_at?->toIso8601String(),
                'step_keys' => [
                    ['step_order' => 1, 'step_key' => 'keuangan_pondok', 'title' => 'Keuangan Pondok'],
                    ['step_order' => 2, 'step_key' => 'baku', 'title' => 'Kepala Biro Administrasi Keuangan (Keuangan Jamiah)'],
                    ['step_order' => 3, 'step_key' => 'baak', 'title' => 'Kepala BAAK (Akademik Jamiah)'],
                    ['step_order' => 4, 'step_key' => 'warek_1', 'title' => 'Wakil Rektor I'],
                ],
            ],
            'message' => 'Konfigurasi integrasi API Form Pengambilan Ijazah berhasil diambil',
        ]);
    }

    /**
     * Internal UI: Regenerate API Key for external integration.
     */
    public function regenerateApiKey()
    {
        $record = IntegrationApiKey::getOrCreateForService(
            self::SERVICE_KEY,
            self::SERVICE_NAME,
            self::KEY_PREFIX,
            Auth::id()
        );
        $record->update([
            'api_key' => IntegrationApiKey::generateKey(self::KEY_PREFIX),
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'base_url' => url('/api/external/form-pengambilan-ijazah'),
                'verify_base_url' => url('/verify/form-pengambilan-ijazah'),
                'api_key' => $record->api_key,
                'is_active' => $record->is_active,
                'updated_at' => $record->updated_at?->toIso8601String(),
            ],
            'message' => 'API Key berhasil digenerate ulang',
        ]);
    }

    /**
     * Internal UI: Get module-level default signers for all 4 steps.
     */
    public function getSignerDefaults()
    {
        return response()->json([
            'status' => true,
            'data' => $this->signatureService->getSignerDefaults(),
            'message' => 'Default penandatangan Form Pengambilan Ijazah berhasil diambil',
        ]);
    }

    /**
     * Internal UI: Update module-level default signers and optionally apply across existing rows.
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
                ? "Default penandatangan disimpan dan diterapkan ke {$result['affected_signatures']} tahap tanda tangan."
                : 'Default penandatangan berhasil disimpan untuk data baru berikutnya.',
        ]);
    }

    /**
     * External API: List Form Pengambilan Ijazah records with signature progress.
     */
    public function externalIndex(Request $request)
    {
        $query = FormPengambilanIjazah::orderByDesc('id');

        if ($request->filled('nim')) {
            $query->where('nim', $request->input('nim'));
        }
        if ($request->filled('prodi')) {
            $query->where('prodi', 'like', '%' . $request->input('prodi') . '%');
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                    ->orWhere('nama_mhs', 'like', "%{$search}%")
                    ->orWhere('prodi', 'like', "%{$search}%");
            });
        }

        try {
            $gender = SignatureStepFilter::resolveGenderFilter($request);
            if ($gender !== null) {
                $query->where('jenis_kelamin', $gender);
            }

            SignatureStepFilter::applyExternalListFilters($query, $request, PengambilanIjazahSignatureService::STEP_ORDER);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $limit = min(max((int) $request->input('limit', 15), 1), 100);
        $paginated = $query->paginate($limit);

        $rows = $paginated->getCollection()->map(function (FormPengambilanIjazah $form) {
            return $this->formatExternalItem($form);
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
            'message' => 'Daftar Form Pengambilan Ijazah berhasil diambil',
        ]);
    }

    /**
     * External API: Show single record by ID with full 4-step timeline.
     */
    public function externalShow($id)
    {
        $form = FormPengambilanIjazah::find($id);
        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Data Form Pengambilan Ijazah tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatExternalItem($form, true),
            'message' => 'Detail Form Pengambilan Ijazah berhasil diambil',
        ]);
    }

    /**
     * External API: Show latest record by student NIM.
     */
    public function externalShowByNim(string $nim)
    {
        $form = FormPengambilanIjazah::where('nim', $nim)
            ->orderByDesc('id')
            ->first();

        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => "Data Form Pengambilan Ijazah untuk NIM '{$nim}' tidak ditemukan",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatExternalItem($form, true),
            'message' => 'Detail Form Pengambilan Ijazah berhasil diambil',
        ]);
    }

    /**
     * External API: Confirm/sign a step by record ID.
     */
    public function externalSign(Request $request, $id)
    {
        $form = FormPengambilanIjazah::find($id);
        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Data Form Pengambilan Ijazah tidak ditemukan',
            ], 404);
        }

        return $this->handleExternalSign($request, $form);
    }

    /**
     * External API: Confirm/sign a step by student NIM (uses latest record for NIM).
     */
    public function externalSignByNim(Request $request, string $nim)
    {
        $form = FormPengambilanIjazah::where('nim', $nim)->orderByDesc('id')->first();
        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => "Data Form Pengambilan Ijazah untuk NIM '{$nim}' tidak ditemukan",
            ], 404);
        }

        return $this->handleExternalSign($request, $form);
    }

    /**
     * External API: Revoke a step confirmation by record ID.
     */
    public function externalRevoke(Request $request, $id)
    {
        $form = FormPengambilanIjazah::find($id);
        if (!$form) {
            return response()->json([
                'status' => false,
                'message' => 'Data Form Pengambilan Ijazah tidak ditemukan',
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

        $this->signatureService->revokeStep($form, $request->input('step_key'));

        return response()->json([
            'status' => true,
            'data' => $this->formatExternalItem($form->fresh(), true),
            'message' => 'Konfirmasi TTD berhasil dibatalkan',
        ]);
    }

    /**
     * Public API: Verify QR token as JSON.
     */
    public function verifyJson(string $token)
    {
        $sig = PengambilanIjazahSignature::with('formPengambilanIjazah')
            ->where('signature_token', $token)
            ->first();

        if (!$sig || $sig->status !== 'approved') {
            return response()->json([
                'status' => false,
                'verified' => false,
                'message' => 'Tanda tangan QR tidak ditemukan atau belum terkonfirmasi.',
            ], 404);
        }

        $form = $sig->formPengambilanIjazah;
        return response()->json([
            'status' => true,
            'verified' => true,
            'data' => [
                'nama_mhs' => $form?->nama_mhs,
                'nim' => $form?->nim,
                'prodi' => $form?->prodi,
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
        $sig = PengambilanIjazahSignature::with('formPengambilanIjazah')
            ->where('signature_token', $token)
            ->first();

        $timeline = $sig && $sig->formPengambilanIjazah
            ? $this->signatureService->formatTimeline($sig->formPengambilanIjazah, false)
            : [];

        return response()->view('verify.pengambilan_ijazah', [
            'signature' => $sig,
            'form' => $sig?->formPengambilanIjazah,
            'timeline' => $timeline,
        ]);
    }

    private function handleExternalSign(Request $request, FormPengambilanIjazah $form)
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
            'required_steps.*' => 'string|in:keuangan_pondok,baku,baak,warek_1',
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
            $step = $this->signatureService->confirmStep($form, $request->input('step_key'), [
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
                    'verify_url' => url('/verify/form-pengambilan-ijazah/' . $step->signature_token),
                ],
                'form' => $this->formatExternalItem($form->fresh(), true),
            ],
            'message' => "TTD QR untuk step '{$step->step_key}' berhasil dikonfirmasi",
        ]);
    }

    private function formatExternalItem(FormPengambilanIjazah $form, bool $withQr = false): array
    {
        $steps = $this->signatureService->formatTimeline($form, $withQr);
        $signedCount = collect($steps)->where('status', 'approved')->count();

        return [
            'id' => $form->id,
            'nama_mhs' => $form->nama_mhs,
            'nim' => $form->nim,
            'prodi' => $form->prodi,
            'jenis_kelamin' => $form->jenis_kelamin,
            'dosen_pembimbing' => $form->dosen_pembimbing,
            'tanggal_sidang' => $form->tanggal_sidang,
            'drive_link' => $form->drive_link,
            'signed_drive_link' => $form->signed_drive_link,
            'signed_count' => $signedCount,
            'total_steps' => count($steps),
            'is_completed' => $signedCount === count($steps),
            'steps' => $steps,
        ];
    }
}
