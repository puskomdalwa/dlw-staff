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
            ->assertJsonPath('data.sk6.total_steps', 5)
            ->assertJsonPath('data.sk6.signed_count', 0);

        $steps = $res->json('data.steps');
        $this->assertSame(['kaprodi', 'baku', 'kkn', 'idaroh_syuun', 'keamanan'], array_column($steps, 'step_key'));
        $this->assertSame('Ketua Program Studi Pendidikan Bahasa Arab', $steps[0]['title']);
        $this->assertSame('Kepala Biro Administrasi Keuangan', $steps[1]['title']);
        $this->assertSame('Ketua TASMA, KKN & PPL', $steps[2]['title']);
        $this->assertSame('Wakil Rektor I', $steps[3]['title']);
        $this->assertSame('Ketua Qismul Aman', $steps[4]['title']);

        $confirm = $this->actingAs($user, 'sanctum')
            ->postJson("/api/sk6/{$sk6->id}/signatures/kaprodi/confirm", [
                'notes' => 'Disetujui langsung di web staff',
            ]);
        $confirm->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.signed_count', 1);

        $token = $confirm->json('data.steps.0.signature_token');
        $this->assertNotEmpty($token);
        $this->assertStringStartsWith('data:image/png;base64,', $confirm->json('data.steps.0.qr_data_uri'));

        // Public QR verification endpoints
        $this->getJson("/api/sk6/verify/{$token}")
            ->assertStatus(200)
            ->assertJsonPath('verified', true)
            ->assertJsonPath('data.step_key', 'kaprodi');

        $this->get("/verify/sk6/{$token}")
            ->assertStatus(200)
            ->assertSee('Tanda Tangan QR Terverifikasi');

        // Revoke back to pending
        $revoke = $this->actingAs($user, 'sanctum')
            ->postJson("/api/sk6/{$sk6->id}/signatures/kaprodi/revoke");
        $revoke->assertStatus(200)
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

        // Sign BAKU step via external API by NIM
        $sign = $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/by-nim/{$sk6->nim}/sign", [
                'step_key' => 'baku',
                'signer_name' => 'Dr. Musleh Harry, SH, M.Hum',
                'source' => 'web-keuangan',
                'notes' => 'Lunas via web keuangan',
            ]);

        $sign->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.signed_step.step_key', 'baku')
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

        // Download PDF must be blocked (422) until all 5 steps are signed
        $this->actingAs($user, 'sanctum')
            ->getJson("/api/sk6/download-pdf/{$sk6->id}")
            ->assertStatus(422);

        // Complete remaining 4 steps and verify queue dispatch + PDF download allowed
        Queue::fake();
        foreach (['kaprodi', 'kkn', 'idaroh_syuun', 'keamanan'] as $stepKey) {
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
        @unlink($sk6->signed_local_path);
    }

    public function test_sk6_signer_defaults_can_be_configured_and_applied_across_module(): void
    {
        [$user, $prodi, $sk6] = $this->seedSk6Scenario();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/sk6/signer-defaults')
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');

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
                        'step_key' => 'baku',
                        'title' => 'Kepala Biro Administrasi Keuangan',
                        'signer_name' => 'Dr. Pejabat BAKU Baru, M.H.',
                        'signer_nidn' => '223344',
                    ],
                    [
                        'step_key' => 'kkn',
                        'title' => 'Ketua TASMA, KKN & PPL',
                        'signer_name' => 'Ketua KKN Baru, M.Pd',
                        'signer_nidn' => '556677',
                    ],
                    [
                        'step_key' => 'idaroh_syuun',
                        'title' => 'Wakil Rektor I',
                        'signer_name' => 'Warek I Baru, Ph.D.',
                        'signer_nidn' => '889900',
                    ],
                    [
                        'step_key' => 'keamanan',
                        'title' => 'Ketua Qismul Aman',
                        'signer_name' => 'Ketua Qismul Aman Baru',
                        'signer_nidn' => '110022',
                    ],
                ],
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.applied_to_existing', true)
            ->assertJsonPath('data.affected_signatures', 4);

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
            'signer_name' => 'Warek I Baru, Ph.D.',
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

        // Before kaprodi signs: ready_for_step=baku returns 0 items
        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=baku')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        // With require_previous_approved=true, signing baku before kaprodi is rejected (422)
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'baku',
                'require_previous_approved' => true,
            ])
            ->assertStatus(422);

        // Signing with required_steps=['kaprodi'] is also rejected (422)
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'kkn',
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

        // Now ready_for_step=baku returns ONLY sk6A (not sk6B)
        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=baku')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        // Equivalent explicit query filters also return ONLY sk6A
        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?step_key=baku&step_status=pending&require_previous_approved=1')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?approved_steps=kaprodi&pending_steps=baku')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        // Now BAKU can sign sk6A with require_previous_approved=true
        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/sign", [
                'step_key' => 'baku',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.sk6.signed_count', 2);

        // After BAKU signs, ready_for_step=baku is empty and ready_for_step=kkn has sk6A
        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=baku')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        $this->withHeader('X-API-Key', $apiKey)
            ->getJson('/api/external/sk6?ready_for_step=kkn')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $sk6A->id);

        // Internal UI revoke and external API revoke both work cleanly
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/sk6/{$sk6A->id}/signatures/baku/revoke")
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 1);

        $this->withHeader('X-API-Key', $apiKey)
            ->postJson("/api/external/sk6/{$sk6A->id}/revoke", ['step_key' => 'kaprodi'])
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 0);

        // Clean up unused variable warning
        $this->assertNotNull($sk6B->id);
    }
}
