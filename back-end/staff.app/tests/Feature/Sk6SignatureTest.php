<?php

namespace Tests\Feature;

use App\Models\IntegrationApiKey;
use App\Models\Level;
use App\Models\Prodi;
use App\Models\SuratKeterangan6;
use App\Models\SuratKeteranganAdministrasiKeuangan;
use App\Models\SuratKeteranganLulusMataKuliah;
use App\Models\SuratKeteranganQismulAman;
use App\Models\SuratKeteranganTasmaKknPpl;
use App\Models\SuratKeteranganUjianKomprehensifDiniyah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class Sk6SignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    private function seedSk6Scenario(): array
    {
        $level = Level::create(['nama' => 'admin']);
        $user = User::create([
            'name' => 'Admin Test',
            'username' => 'admintest',
            'email' => 'admin@test.local',
            'password' => Hash::make('secret123'),
            'level_id' => $level->id,
            'jenis_kelamin' => 'L',
        ]);

        $prodi = Prodi::create([
            'kode' => '88204',
            'alias' => 'PBA',
            'nama' => 'Pendidikan Bahasa Arab',
            'aktif' => 'Y',
            'nama_kepala' => 'Dr. Mustahar Ali Wardana, M.Pd.',
            'nidn_kepala' => '2126056801',
        ]);

        $sklmk = (new SuratKeteranganLulusMataKuliah())->forceFill([
            'nomor_surat' => 'SKLM-001/TEST/2026',
            'prodi_id' => $prodi->id,
            'nama_lengkap' => 'Ahmad Fauzi',
            'tempat_lahir' => 'Pasuruan',
            'tanggal_lahir' => '2002-01-01',
            'nim' => '202285290001',
            'prodi_mahasiswa' => $prodi->nama,
            'alamat_rumah' => 'Bangil',
            'kelas_pondok' => 'VI A',
            'tanggal' => '2026-11-21',
            'user_id' => $user->id,
            'jenis_kelamin' => 'L',
            'status' => 'pending',
        ]);
        $sklmk->save();

        $skak = SuratKeteranganAdministrasiKeuangan::create([
            'nomor_surat' => 'SKAK-001/TEST/2026',
            'prodi_id' => $prodi->id,
            'nama_lengkap' => 'Ahmad Fauzi',
            'tempat_lahir' => 'Pasuruan',
            'tanggal_lahir' => '2002-01-01',
            'nim' => '202285290001',
            'prodi_mhs' => $prodi->nama,
            'user_id' => $user->id,
            'alamat_rumah' => 'Bangil',
            'kelas_pondok' => 'VI A',
            'tanggal' => '2026-11-21',
            'jenis_kelamin' => 'L',
            'status' => 'pending',
        ]);

        $sktkp = SuratKeteranganTasmaKknPpl::create([
            'nomor_surat' => 'STTKP-001/TEST/2026',
            'prodi_id' => $prodi->id,
            'nama_lengkap' => 'Ahmad Fauzi',
            'tempat_lahir' => 'Pasuruan',
            'tanggal_lahir' => '2002-01-01',
            'nim' => '202285290001',
            'prodi_mhs' => $prodi->nama,
            'user_id' => $user->id,
            'alamat_rumah' => 'Bangil',
            'kelas_pondok' => 'VI A',
            'tanggal' => '2026-11-21',
            'jenis_kelamin' => 'L',
            'status' => 'pending',
        ]);

        $skukd = SuratKeteranganUjianKomprehensifDiniyah::create([
            'nomor_surat' => 'SKUKD-001/TEST/2026',
            'prodi_id' => $prodi->id,
            'nama_lengkap' => 'Ahmad Fauzi',
            'tempat_lahir' => 'Pasuruan',
            'tanggal_lahir' => '2002-01-01',
            'nim' => '202285290001',
            'prodi_mhs' => $prodi->nama,
            'user_id' => $user->id,
            'alamat_rumah' => 'Bangil',
            'kelas_pondok' => 'VI A',
            'tanggal' => '2026-11-21',
            'jenis_kelamin' => 'L',
            'status' => 'pending',
        ]);

        $skqa = SuratKeteranganQismulAman::create([
            'nomor_surat' => 'SKQA-001/TEST/2026',
            'prodi_id' => $prodi->id,
            'nama_lengkap' => 'Ahmad Fauzi',
            'tempat_lahir' => 'Pasuruan',
            'tanggal_lahir' => '2002-01-01',
            'nim' => '202285290001',
            'prodi_mhs' => $prodi->nama,
            'user_id' => $user->id,
            'alamat_rumah' => 'Bangil',
            'kelas_pondok' => 'VI A',
            'tanggal_berlaku_dari' => '2026-11-01',
            'tanggal_berlaku_sampai' => '2026-12-01',
            'tanggal' => '2026-11-21',
            'jenis_kelamin' => 'L',
            'status' => 'pending',
        ]);

        $sk6 = SuratKeterangan6::create([
            'nama_mhs' => 'Ahmad Fauzi',
            'nim' => '202285290001',
            'tanggal' => '2026-11-21',
            'prodi_id' => $prodi->id,
            'surat_keterangan_lulus_mata_kuliah_id' => $sklmk->id,
            'surat_keterangan_administrasi_keuangan_id' => $skak->id,
            'surat_keterangan_tasma_kkn_ppl_id' => $sktkp->id,
            'surat_keterangan_ujian_komprehensif_diniyah_id' => $skukd->id,
            'surat_keterangan_qismul_aman_id' => $skqa->id,
        ]);

        return [$user, $prodi, $sk6];
    }

    public function test_sk6_timeline_initialization_and_internal_confirm_revoke(): void
    {
        [$user, $prodi, $sk6] = $this->seedSk6Scenario();

        $res = $this->actingAs($user, 'sanctum')->getJson("/api/sk6/{$sk6->id}/signatures");
        $res->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.sk6.total_steps', 6)
            ->assertJsonPath('data.sk6.signed_count', 0);

        $steps = $res->json('data.steps');
        $this->assertSame(['kaprodi', 'kkn', 'keamanan', 'baku_kabag', 'baku_aupk', 'idaroh_syuun'], array_column($steps, 'step_key'));
        $this->assertSame('Ketua Program Studi Pendidikan Bahasa Arab', $steps[0]['title']);
        $this->assertSame('Ketua TASMA, KKN & PPL', $steps[1]['title']);
        $this->assertSame('Ketua Qismul Aman', $steps[2]['title']);
        $this->assertSame('Kabag BAKU', $steps[3]['title']);
        $this->assertSame('BAKU Kabag', $steps[3]['short_label']);
        $this->assertSame('Kepala Biro AUPK', $steps[4]['title']);
        $this->assertSame('BAKU AUPK', $steps[4]['short_label']);
        $this->assertSame('Idaroh Syuun', $steps[5]['title']);
        $this->assertSame('Idaroh Syuun', $steps[5]['short_label']);
        $this->assertSame([false, false, true, false, false, true], array_column($steps, 'can_sign_in_staff'));

        // External-only steps cannot be signed directly from web staff (403)
        foreach (['kaprodi', 'kkn', 'baku_kabag', 'baku_aupk'] as $externalStep) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/sk6/{$sk6->id}/signatures/{$externalStep}/confirm")
                ->assertStatus(403);
        }

        // Keamanan & Idaroh Syuun CAN be signed and revoked directly from web staff
        $confirm = $this->actingAs($user, 'sanctum')
            ->postJson("/api/sk6/{$sk6->id}/signatures/keamanan/confirm", [
                'notes' => 'Disetujui langsung di web staff',
            ]);
        $confirm->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.signed_count', 1);

        $token = $confirm->json('data.steps.2.signature_token');
        $this->assertNotEmpty($token);
        $this->assertStringStartsWith('data:image/png;base64,', $confirm->json('data.steps.2.qr_data_uri'));

        // Public QR verification endpoints
        $this->getJson("/api/sk6/verify/{$token}")
            ->assertStatus(200)
            ->assertJsonPath('verified', true)
            ->assertJsonPath('data.step_key', 'keamanan');

        $this->get("/verify/sk6/{$token}")
            ->assertStatus(200)
            ->assertSee('Tanda Tangan QR Terverifikasi');

        // Revoke back to pending
        $revoke = $this->actingAs($user, 'sanctum')
            ->postJson("/api/sk6/{$sk6->id}/signatures/keamanan/revoke");
        $revoke->assertStatus(200)
            ->assertJsonPath('data.signed_count', 0);

        // Idaroh Syuun also signable & revocable in web staff
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/sk6/{$sk6->id}/signatures/idaroh_syuun/confirm")
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 1);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/sk6/{$sk6->id}/signatures/idaroh_syuun/revoke")
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 0);

        $sk6->refresh();
        if (!empty($sk6->local_path) && file_exists($sk6->local_path)) {
            $this->assertGreaterThan(1000, filesize($sk6->local_path));
            @unlink($sk6->local_path);
        }
    }

    public function test_external_api_key_authentication_regeneration_and_signing(): void
    {
        [$user, $prodi, $sk6] = $this->seedSk6Scenario();

        // Missing API key -> 401
        $this->getJson('/api/external/sk6')->assertStatus(401);

        // Fetch config as authenticated user
        $cfg = $this->actingAs($user, 'sanctum')->getJson('/api/sk6/integration-config');
        $cfg->assertStatus(200);
        $apiKey = $cfg->json('data.api_key');
        $this->assertStringStartsWith('sk6_', $apiKey);

        // Sign BAKU Kabag step via external API by NIM
        $sign = $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/by-nim/{$sk6->nim}/sign", [
                'step_key' => 'baku_kabag',
                'signer_name' => 'Kabag BAKU Test',
                'source' => 'web-keuangan',
                'notes' => 'Verifikasi Kabag BAKU via web keuangan',
            ]);

        $sign->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.signed_step.step_key', 'baku_kabag')
            ->assertJsonPath('data.signed_step.signed_by_source', 'web-keuangan')
            ->assertJsonPath('data.sk6.signed_count', 1);

        // Regenerate API key
        $regen = $this->actingAs($user, 'sanctum')->postJson('/api/sk6/integration-config/regenerate');
        $regen->assertStatus(200);
        $newKey = $regen->json('data.api_key');
        $this->assertNotSame($apiKey, $newKey);

        // Old key must fail, new key must succeed
        $this->withHeader('X-API-Key', $apiKey)->getJson('/api/external/sk6')->assertStatus(401);
        $this->withHeader('X-API-Key', $newKey)->getJson('/api/external/sk6/' . $sk6->id)
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 1);

        // Download PDF must be blocked (422) until all 6 steps are signed
        $this->actingAs($user, 'sanctum')
            ->getJson("/api/sk6/download-pdf/{$sk6->id}")
            ->assertStatus(422);

        // Complete remaining 5 steps and verify queue dispatch + PDF download allowed
        Queue::fake();
        foreach (['kaprodi', 'kkn', 'keamanan', 'baku_aupk', 'idaroh_syuun'] as $stepKey) {
            $this->withHeader('X-API-Key', $newKey)
                ->postJson("/api/external/sk6/{$sk6->id}/sign", ['step_key' => $stepKey])
                ->assertStatus(200);
        }

        Queue::assertPushed(\App\Jobs\UploudSuratToDrive::class, function ($job) use ($sk6) {
            return $job->id === $sk6->id
                && $job->modelClass === SuratKeterangan6::class
                && $job->isSignedSk6 === true;
        });

        $this->actingAs($user, 'sanctum')
            ->get("/api/sk6/download-pdf/{$sk6->id}")
            ->assertStatus(200);

        $sk6->refresh();
        $this->assertNotEmpty($sk6->signed_local_path);
        $this->assertFileExists($sk6->signed_local_path);
        $pdfBinary = file_get_contents($sk6->signed_local_path);
        $this->assertIsString($pdfBinary);
        $this->assertStringContainsString('/Subtype /Image', $pdfBinary);

        $sklmk = \App\Models\SuratKeteranganLulusMataKuliah::find($sk6->surat_keterangan_lulus_mata_kuliah_id);
        $sk6Html = view('pdf.sk_6', app(\App\Services\Sk6SignatureService::class)->buildSk6PdfData($sk6, $sklmk, true))->render();
        $this->assertStringContainsString('QR BAKU AUPK', $sk6Html);
        $this->assertStringNotContainsString('QR BAKU Kabag', $sk6Html);
        @unlink($sk6->signed_local_path);
    }

    public function test_sk6_signer_defaults_can_be_configured_and_applied_across_module(): void
    {
        [$user, $prodi, $sk6] = $this->seedSk6Scenario();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/sk6/signer-defaults')
            ->assertStatus(200)
            ->assertJsonCount(6, 'data');

        $updateRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/sk6/signer-defaults', [
                'apply_to_existing' => true,
                'apply_scope' => 'all',
                'steps' => [
                    [
                        'step_key' => 'kaprodi',
                        'title' => 'Ketua Program Studi',
                        'signer_name' => '',
                        'signer_nidn' => '',
                    ],
                    [
                        'step_key' => 'kkn',
                        'title' => 'Ketua TASMA, KKN & PPL',
                        'signer_name' => 'Ketua KKN Baru, M.Pd',
                        'signer_nidn' => '556677',
                    ],
                    [
                        'step_key' => 'keamanan',
                        'title' => 'Ketua Qismul Aman',
                        'signer_name' => 'Ketua Qismul Aman Baru',
                        'signer_nidn' => '110022',
                    ],
                    [
                        'step_key' => 'baku_kabag',
                        'title' => 'Kabag BAKU',
                        'signer_name' => 'Pejabat BAKU Kabag Baru, S.E.',
                        'signer_nidn' => '223341',
                    ],
                    [
                        'step_key' => 'baku_aupk',
                        'title' => 'Kepala Biro AUPK',
                        'signer_name' => 'Dr. Pejabat BAKU AUPK Baru, M.H.',
                        'signer_nidn' => '223344',
                    ],
                    [
                        'step_key' => 'idaroh_syuun',
                        'title' => 'Idaroh Syuun',
                        'signer_name' => 'Pejabat Idaroh Syuun Baru, Ph.D.',
                        'signer_nidn' => '889900',
                    ],
                ],
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.applied_to_existing', true)
            ->assertJsonPath('data.affected_signatures', 5);

        // Kaprodi kept per-prodi auto signer when default left blank
        $this->assertDatabaseHas('sk6_signatures', [
            'surat_keterangan_6_id' => $sk6->id,
            'step_key' => 'kaprodi',
            'signer_name' => 'Dr. Mustahar Ali Wardana, M.Pd.',
        ]);

        // Other 4 steps updated across existing SK6 record
        $this->assertDatabaseHas('sk6_signatures', [
            'surat_keterangan_6_id' => $sk6->id,
            'step_key' => 'idaroh_syuun',
            'title' => 'Idaroh Syuun',
            'signer_name' => 'Pejabat Idaroh Syuun Baru, Ph.D.',
            'signer_nidn' => '889900',
        ]);
    }

    public function test_external_api_sequential_filters_and_non_skipping_guard(): void
    {
        Queue::fake();
        [$user, $prodi, $sk6A] = $this->seedSk6Scenario();

        // Second SK6 record (legacy/pending, no signatures created yet)
        $sk6B = SuratKeterangan6::create([
            'nama_mhs' => 'Budi Santoso',
            'nim' => '202285290002',
            'tanggal' => '2026-11-21',
            'prodi_id' => $prodi->id,
            'surat_keterangan_lulus_mata_kuliah_id' => $sk6A->surat_keterangan_lulus_mata_kuliah_id,
            'surat_keterangan_administrasi_keuangan_id' => $sk6A->surat_keterangan_administrasi_keuangan_id,
            'surat_keterangan_tasma_kkn_ppl_id' => $sk6A->surat_keterangan_tasma_kkn_ppl_id,
            'surat_keterangan_ujian_komprehensif_diniyah_id' => $sk6A->surat_keterangan_ujian_komprehensif_diniyah_id,
            'surat_keterangan_qismul_aman_id' => $sk6A->surat_keterangan_qismul_aman_id,
        ]);

        $apiKey = IntegrationApiKey::getOrCreateDefault($user->id)->api_key;

        // Invalid step filter returns 422
        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=invalid_step')
            ->assertStatus(422);

        // Before kaprodi signs: ready_for_step=kkn returns 0 items
        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=kkn')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        // With require_previous_approved=true, signing kkn before kaprodi is rejected (422)
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'kkn',
                'require_previous_approved' => true,
            ])
            ->assertStatus(422);

        // Signing with required_steps=['kaprodi'] is also rejected (422)
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'keamanan',
                'required_steps' => ['kaprodi'],
            ])
            ->assertStatus(422);

        // Kaprodi signs sk6A (step 1) with require_previous_approved=true -> succeeds (no earlier steps)
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'kaprodi',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200);

        // Now ready_for_step=kkn returns ONLY sk6A (not sk6B), while ready_for_step=baku_kabag still returns 0
        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=kkn')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=baku_kabag')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        // Equivalent explicit query filters also return ONLY sk6A
        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?step_key=kkn&step_status=pending&require_previous_approved=1')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?approved_steps=kaprodi&pending_steps=kkn')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        // BAKU Kabag cannot sign yet with require_previous_approved=true because KKN & Keamanan are still pending
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'baku_kabag',
                'require_previous_approved' => true,
            ])
            ->assertStatus(422);

        // Now KKN can sign sk6A with require_previous_approved=true
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'kkn',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.sk6.signed_count', 2);

        // After KKN signs, ready_for_step=kkn is empty and ready_for_step=keamanan has sk6A
        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=kkn')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=keamanan')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        // Keamanan signs -> ready_for_step=baku_kabag has sk6A, ready_for_step=baku_aupk still 0
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'keamanan',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200);

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=baku_kabag')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        // BAKU AUPK cannot sign before BAKU Kabag
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'baku_aupk',
                'require_previous_approved' => true,
            ])
            ->assertStatus(422);

        // BAKU Kabag signs -> ready_for_step=baku_aupk now has sk6A, then BAKU AUPK signs
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'baku_kabag',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200);

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=baku_aupk')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'baku_aupk',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.sk6.signed_count', 5);

        // Internal UI revoke is blocked for external-only steps (403) and allowed for staff steps; external API revoke works
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/sk6/{$sk6A->id}/signatures/baku_aupk/revoke")
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/sk6/{$sk6A->id}/signatures/keamanan/revoke")
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 4);

        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/revoke", ['step_key' => 'baku_aupk'])
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 3);

        // Clean up unused variable warning
        $this->assertNotNull($sk6B->id);
    }

    public function test_external_api_gender_putra_putri_filter(): void
    {
        [$user, $prodi, $sk6Putra] = $this->seedSk6Scenario();

        $sklmkPutri = (new SuratKeteranganLulusMataKuliah())->forceFill([
            'nomor_surat' => 'SKLM-002/TEST/2026',
            'prodi_id' => $prodi->id,
            'nama_lengkap' => 'Siti Aminah',
            'tempat_lahir' => 'Pasuruan',
            'tanggal_lahir' => '2002-05-05',
            'nim' => '202285290099',
            'prodi_mahasiswa' => $prodi->nama,
            'alamat_rumah' => 'Bangil',
            'kelas_pondok' => 'VI B',
            'tanggal' => '2026-11-21',
            'user_id' => $user->id,
            'jenis_kelamin' => 'P',
            'status' => 'pending',
        ]);
        $sklmkPutri->save();

        $sk6Putri = SuratKeterangan6::create([
            'nama_mhs' => 'Siti Aminah',
            'nim' => '202285290099',
            'tanggal' => '2026-11-21',
            'prodi_id' => $prodi->id,
            'surat_keterangan_lulus_mata_kuliah_id' => $sklmkPutri->id,
            'surat_keterangan_administrasi_keuangan_id' => $sk6Putra->surat_keterangan_administrasi_keuangan_id,
            'surat_keterangan_tasma_kkn_ppl_id' => $sk6Putra->surat_keterangan_tasma_kkn_ppl_id,
            'surat_keterangan_ujian_komprehensif_diniyah_id' => $sk6Putra->surat_keterangan_ujian_komprehensif_diniyah_id,
            'surat_keterangan_qismul_aman_id' => $sk6Putra->surat_keterangan_qismul_aman_id,
        ]);

        $apiKey = IntegrationApiKey::getOrCreateDefault($user->id)->api_key;

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?jenis_kelamin=L')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6Putra->id)
            ->assertJsonPath('data.items.0.jenis_kelamin', 'L');

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?jenis_kelamin=putri')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6Putri->id)
            ->assertJsonPath('data.items.0.jenis_kelamin', 'P');

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?jenis_kelamin=invalid')
            ->assertStatus(422);
    }

    public function test_existing_legacy_order_and_warek_title_are_synced_to_revised_timeline(): void
    {
        [$user, $prodi, $sk6] = $this->seedSk6Scenario();

        \Illuminate\Support\Facades\DB::table('sk6_signer_defaults')->insert([
            ['step_order' => 1, 'step_key' => 'kaprodi', 'title' => 'Ketua Program Studi', 'signer_name' => null, 'signer_nidn' => null],
            ['step_order' => 2, 'step_key' => 'baku', 'title' => 'Kepala Biro Administrasi Keuangan', 'signer_name' => 'BAKU', 'signer_nidn' => null],
            ['step_order' => 3, 'step_key' => 'kkn', 'title' => 'Ketua TASMA, KKN & PPL', 'signer_name' => 'KKN', 'signer_nidn' => null],
            ['step_order' => 4, 'step_key' => 'idaroh_syuun', 'title' => 'Wakil Rektor I', 'signer_name' => 'Idaroh', 'signer_nidn' => null],
            ['step_order' => 5, 'step_key' => 'keamanan', 'title' => 'Ketua Qismul Aman', 'signer_name' => 'QA', 'signer_nidn' => null],
        ]);

        \Illuminate\Support\Facades\DB::table('sk6_signatures')->insert([
            ['surat_keterangan_6_id' => $sk6->id, 'step_order' => 1, 'step_key' => 'kaprodi', 'title' => 'Ketua Program Studi', 'status' => 'pending', 'signature_token' => 'tok_1'],
            ['surat_keterangan_6_id' => $sk6->id, 'step_order' => 2, 'step_key' => 'baku', 'title' => 'Kepala Biro Administrasi Keuangan', 'status' => 'pending', 'signature_token' => 'tok_2'],
            ['surat_keterangan_6_id' => $sk6->id, 'step_order' => 3, 'step_key' => 'kkn', 'title' => 'Ketua TASMA, KKN & PPL', 'status' => 'pending', 'signature_token' => 'tok_3'],
            ['surat_keterangan_6_id' => $sk6->id, 'step_order' => 4, 'step_key' => 'idaroh_syuun', 'title' => 'Wakil Rektor I', 'status' => 'pending', 'signature_token' => 'tok_4'],
            ['surat_keterangan_6_id' => $sk6->id, 'step_order' => 5, 'step_key' => 'keamanan', 'title' => 'Ketua Qismul Aman', 'status' => 'pending', 'signature_token' => 'tok_5'],
        ]);

        $defaultsRes = $this->actingAs($user, 'sanctum')->getJson('/api/sk6/signer-defaults');
        $defaultsRes->assertStatus(200);
        $defaults = $defaultsRes->json('data');
        $this->assertSame(['kaprodi', 'kkn', 'keamanan', 'baku_kabag', 'baku_aupk', 'idaroh_syuun'], array_column($defaults, 'step_key'));
        $this->assertSame('Kepala Biro AUPK', $defaults[4]['title']);
        $this->assertSame('Idaroh Syuun', $defaults[5]['title']);
        $this->assertSame('Idaroh Syuun', $defaults[5]['short_label']);

        $timelineRes = $this->actingAs($user, 'sanctum')->getJson("/api/sk6/{$sk6->id}/signatures");
        $timelineRes->assertStatus(200);
        $steps = $timelineRes->json('data.steps');
        $this->assertSame(['kaprodi', 'kkn', 'keamanan', 'baku_kabag', 'baku_aupk', 'idaroh_syuun'], array_column($steps, 'step_key'));
        $this->assertSame([1, 2, 3, 4, 5, 6], array_column($steps, 'step_order'));
        $this->assertSame('Kepala Biro AUPK', $steps[4]['title']);
        $this->assertSame('Idaroh Syuun', $steps[5]['title']);
        $this->assertSame('Idaroh Syuun', $steps[5]['short_label']);

        $cfgRes = $this->actingAs($user, 'sanctum')->getJson('/api/sk6/integration-config');
        $cfgRes->assertStatus(200);
        $cfgSteps = $cfgRes->json('data.step_keys');
        $this->assertSame(['kaprodi', 'kkn', 'keamanan', 'baku_kabag', 'baku_aupk', 'idaroh_syuun'], array_column($cfgSteps, 'step_key'));
        $this->assertSame('Idaroh Syuun', $cfgSteps[5]['title']);
    }
}
