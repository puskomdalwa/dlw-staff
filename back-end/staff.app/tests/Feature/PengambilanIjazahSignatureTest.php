<?php

namespace Tests\Feature;

use App\Jobs\UploudSuratToDrive;
use App\Models\FormPengambilanIjazah;
use App\Models\IntegrationApiKey;
use App\Models\Level;
use App\Models\PengambilanIjazahSignature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PengambilanIjazahSignatureTest extends TestCase
{
    use RefreshDatabase;

    private array $cleanupPaths = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupPaths as $path) {
            if (!empty($path) && File::exists($path)) {
                @File::delete($path);
            }
        }
        parent::tearDown();
    }

    private function createAdminUser(): User
    {
        $level = Level::create(['nama' => 'admin']);
        return User::create([
            'name' => 'Admin Test',
            'username' => 'admintest',
            'email' => 'admin@test.local',
            'password' => Hash::make('secret123'),
            'level_id' => $level->id,
            'jenis_kelamin' => 'L',
        ]);
    }

    public function test_four_step_signature_timeline_dual_drive_upload_and_external_api_flow(): void
    {
        Queue::fake();
        $user = $this->createAdminUser();

        // 1. Create Form Pengambilan Ijazah via authenticated store endpoint
        $storeRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/form-pengambilan-ijazah', [
                'nama_mhs' => 'Ahmad Fauzi',
                'nim' => '202285290099',
                'prodi' => 'Pendidikan Bahasa Arab',
                'dosen_pembimbing' => 'Dr. Dosen Pembimbing, M.Pd',
                'judul_skripsi' => 'Implementasi Pembelajaran Bahasa Arab Berbasis Digital',
                'tanggal_sidang' => '2026-11-21',
                'no_hp' => '081234567890',
            ]);

        $storeRes->assertStatus(201)->assertJsonPath('status', true);
        $formId = $storeRes->json('data.id');
        $form = FormPengambilanIjazah::findOrFail($formId);

        if (!empty($form->local_path)) {
            $this->cleanupPaths[] = $form->local_path;
        }

        // Unsigned PDF saved and unsigned Drive upload queued
        $this->assertNotEmpty($form->local_path);
        $this->assertFileExists($form->local_path);
        Queue::assertPushed(UploudSuratToDrive::class, function ($job) use ($form) {
            return $job->id === $form->id
                && $job->modelClass === FormPengambilanIjazah::class
                && $job->isSignedSk6 === false;
        });

        // 2. Index endpoint includes 4-step signature summary
        $indexRes = $this->actingAs($user, 'sanctum')->getJson('/api/form-pengambilan-ijazah');
        $indexRes->assertStatus(200)
            ->assertJsonPath('data.data.0.id', $form->id)
            ->assertJsonPath('data.data.0.signed_count', 0)
            ->assertJsonPath('data.data.0.total_steps', 4)
            ->assertJsonPath('data.data.0.is_completed', false);

        $indexSteps = $indexRes->json('data.data.0.signatures');
        $this->assertSame(
            ['keuangan_pondok', 'baku', 'baak', 'warek_1'],
            array_column($indexSteps, 'step_key')
        );

        // 3. Download PDF before all 4/4 signed is blocked (422)
        $blockedPdf = $this->actingAs($user, 'sanctum')
            ->getJson("/api/form-pengambilan-ijazah/download-pdf/{$form->id}");
        $blockedPdf->assertStatus(422);

        // 4. Integration API Key auto-created cleanly for pengambilan_ijazah_external
        $cfgRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/form-pengambilan-ijazah/integration-config');
        $cfgRes->assertStatus(200)->assertJsonPath('status', true);
        $apiKey = $cfgRes->json('data.api_key');
        $this->assertStringStartsWith('pij_', $apiKey);
        $this->assertSame('http://localhost/api/external/form-pengambilan-ijazah', $cfgRes->json('data.base_url'));

        // Unauthenticated external access rejected
        $this->getJson('/api/external/form-pengambilan-ijazah')->assertStatus(401);

        // 5. Sign steps 1 & 2 internally
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/form-pengambilan-ijazah/{$form->id}/signatures/keuangan_pondok/confirm", [
                'signer_name' => 'Ust. Keuangan Pondok',
                'notes' => 'Lunas syahriah & tanggungan pondok',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 1)
            ->assertJsonPath('data.is_completed', false);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/form-pengambilan-ijazah/{$form->id}/signatures/baku/confirm", [
                'signer_name' => 'Dr. Musleh Harry, SH, M.Hum',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 2)
            ->assertJsonPath('data.is_completed', false);

        // Still not 4/4: no signed Drive job yet
        Queue::assertNotPushed(UploudSuratToDrive::class, function ($job) {
            return $job->isSignedSk6 === true;
        });

        // 6. Sign steps 3 & 4 via External API
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson('/api/external/form-pengambilan-ijazah/by-nim/202285290099/sign', [
                'step_key' => 'baak',
                'signer_name' => 'Kepala BAAK Test',
                'source' => 'web-baak',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 3)
            ->assertJsonPath('data.form.is_completed', false);

        $finalSign = $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson('/api/external/form-pengambilan-ijazah/by-nim/202285290099/sign', [
                'step_key' => 'warek_1',
                'signer_name' => 'Dr. Habib Zainal Abidin Bilfaqih, M.Pd.',
                'source' => 'web-warek1',
            ]);
        $finalSign->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 4)
            ->assertJsonPath('data.form.is_completed', true);

        $form->refresh();
        if (!empty($form->signed_local_path)) {
            $this->cleanupPaths[] = $form->signed_local_path;
        }

        // Signed PDF generated and queued for signed Drive upload
        $this->assertNotEmpty($form->signed_local_path);
        $this->assertFileExists($form->signed_local_path);
        Queue::assertPushed(UploudSuratToDrive::class, function ($job) use ($form) {
            return $job->id === $form->id
                && $job->modelClass === FormPengambilanIjazah::class
                && $job->isSignedSk6 === true;
        });

        // 7. Download PDF now succeeds when 4/4 signed
        $pdfRes = $this->actingAs($user, 'sanctum')
            ->get("/api/form-pengambilan-ijazah/download-pdf/{$form->id}");
        $pdfRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $pdfRes->headers->get('content-type'));

        // 8. Styled PNG QR data URI + public verification JSON & web page
        $timelineRes = $this->actingAs($user, 'sanctum')
            ->getJson("/api/form-pengambilan-ijazah/{$form->id}/signatures");
        $timelineRes->assertStatus(200);
        $step1Qr = $timelineRes->json('data.steps.0.qr_data_uri');
        $this->assertStringStartsWith('data:image/png;base64,', $step1Qr);

        $token = PengambilanIjazahSignature::where('form_pengambilan_ijazah_id', $form->id)
            ->where('step_key', 'warek_1')
            ->value('signature_token');
        $this->getJson("/api/form-pengambilan-ijazah/verify/{$token}")
            ->assertStatus(200)
            ->assertJsonPath('verified', true)
            ->assertJsonPath('data.nim', '202285290099');
        $this->get("/verify/form-pengambilan-ijazah/{$token}")
            ->assertStatus(200)
            ->assertSee('Tanda Tangan QR Terverifikasi');

        // 9. Revoking a step deletes signed PDF and blocks download again
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$form->id}/revoke", [
                'step_key' => 'warek_1',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.signed_count', 3);

        $form->refresh();
        $this->assertNull($form->signed_local_path);
        $this->actingAs($user, 'sanctum')
            ->getJson("/api/form-pengambilan-ijazah/download-pdf/{$form->id}")
            ->assertStatus(422);

        // 10. Regenerate API key invalidates old key and enables new key
        $regenRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/form-pengambilan-ijazah/integration-config/regenerate');
        $regenRes->assertStatus(200);
        $newKey = $regenRes->json('data.api_key');
        $this->assertNotSame($apiKey, $newKey);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah')
            ->assertStatus(401);

        $this->withHeaders(['X-API-Key' => $newKey])
            ->getJson('/api/external/form-pengambilan-ijazah')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1);

        // 11. Verify index returns short_label & is_signed booleans (no raw Eloquent overwrite)
        $indexCheck = $this->actingAs($user, 'sanctum')->getJson('/api/form-pengambilan-ijazah');
        $indexCheck->assertStatus(200)
            ->assertJsonPath('data.data.0.signatures.0.short_label', 'Keu. Pondok')
            ->assertJsonPath('data.data.0.signatures.0.is_signed', true)
            ->assertJsonPath('data.data.0.signatures.3.is_signed', false);
    }

    public function test_signer_defaults_can_be_configured_and_applied_across_module_records(): void
    {
        Queue::fake();
        $user = $this->createAdminUser();

        $form1 = FormPengambilanIjazah::create([
            'nama_mhs' => 'Mahasiswa Satu',
            'nim' => '20220001',
            'prodi' => 'PBA',
            'dosen_pembimbing' => 'Dosen A',
            'judul_skripsi' => 'Judul A',
            'tanggal_sidang' => '2026-11-20',
            'status' => 'pending',
        ]);

        // Load defaults & update with apply_to_existing = true across module
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/form-pengambilan-ijazah/signer-defaults')
            ->assertStatus(200)
            ->assertJsonCount(4, 'data');

        $updateRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/form-pengambilan-ijazah/signer-defaults', [
                'apply_to_existing' => true,
                'apply_scope' => 'all',
                'steps' => [
                    [
                        'step_key' => 'keuangan_pondok',
                        'title' => 'Bendahara Keuangan Pondok',
                        'signer_name' => 'Ust. Zulfikar, S.E.',
                        'signer_nidn' => '112233',
                    ],
                    [
                        'step_key' => 'baku',
                        'title' => 'Kepala BAKU',
                        'signer_name' => 'Dr. Musleh Harry, SH, M.Hum',
                        'signer_nidn' => '445566',
                    ],
                    [
                        'step_key' => 'baak',
                        'title' => 'Kepala BAAK',
                        'signer_name' => 'Kuzairi, M.Pd',
                        'signer_nidn' => '80283',
                    ],
                    [
                        'step_key' => 'warek_1',
                        'title' => 'Wakil Rektor I',
                        'signer_name' => 'Dr. Wakil Rektor Baru, M.Pd',
                        'signer_nidn' => '998877',
                    ],
                ],
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.applied_to_existing', true)
            ->assertJsonPath('data.affected_signatures', 4);

        // Existing form picks up updated default signers
        $this->assertDatabaseHas('pengambilan_ijazah_signatures', [
            'form_pengambilan_ijazah_id' => $form1->id,
            'step_key' => 'keuangan_pondok',
            'title' => 'Bendahara Keuangan Pondok',
            'signer_name' => 'Ust. Zulfikar, S.E.',
            'signer_nidn' => '112233',
        ]);

        // Future form automatically uses the newly saved defaults
        $form2Res = $this->actingAs($user, 'sanctum')
            ->postJson('/api/form-pengambilan-ijazah', [
                'nama_mhs' => 'Mahasiswa Dua',
                'nim' => '20220002',
                'prodi' => 'PBA',
                'dosen_pembimbing' => 'Dosen B',
                'judul_skripsi' => 'Judul B',
                'tanggal_sidang' => '2026-11-21',
            ]);
        $form2Res->assertStatus(201);
        $form2 = FormPengambilanIjazah::findOrFail($form2Res->json('data.id'));
        if (!empty($form2->local_path)) {
            $this->cleanupPaths[] = $form2->local_path;
        }

        $this->assertDatabaseHas('pengambilan_ijazah_signatures', [
            'form_pengambilan_ijazah_id' => $form2->id,
            'step_key' => 'warek_1',
            'signer_name' => 'Dr. Wakil Rektor Baru, M.Pd',
            'signer_nidn' => '998877',
        ]);
    }

    public function test_external_api_sequential_filters_and_non_skipping_guard(): void
    {
        Queue::fake();
        $user = $this->createAdminUser();

        $formA = FormPengambilanIjazah::create([
            'nama_mhs' => 'Mahasiswa A',
            'nim' => '20229001',
            'prodi' => 'PBA',
            'dosen_pembimbing' => 'Dosen A',
            'judul_skripsi' => 'Judul A',
            'tanggal_sidang' => '2026-11-20',
            'status' => 'pending',
        ]);

        $formB = FormPengambilanIjazah::create([
            'nama_mhs' => 'Mahasiswa B',
            'nim' => '20229002',
            'prodi' => 'PBA',
            'dosen_pembimbing' => 'Dosen B',
            'judul_skripsi' => 'Judul B',
            'tanggal_sidang' => '2026-11-20',
            'status' => 'pending',
        ]);

        $apiKey = IntegrationApiKey::getOrCreateForService(
            'pengambilan_ijazah_external',
            'Form Pengambilan Ijazah External Integration',
            'pij_',
            $user->id
        )->api_key;

        // Invalid filter value -> 422
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=unknown')
            ->assertStatus(422);

        // Before keuangan_pondok signs: ready_for_step=baku returns 0 items
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baku')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        // Signing baku with require_previous_approved=true before keuangan_pondok is rejected (422)
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/sign", [
                'step_key' => 'baku',
                'require_previous_approved' => true,
            ])
            ->assertStatus(422);

        // Signing with required_steps=['keuangan_pondok'] is rejected (422)
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/sign", [
                'step_key' => 'baak',
                'required_steps' => ['keuangan_pondok'],
            ])
            ->assertStatus(422);

        // Step 1 (keuangan_pondok) signs formA
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/sign", [
                'step_key' => 'keuangan_pondok',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200);

        // Now ready_for_step=baku returns ONLY formA (not formB)
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baku')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $formA->id);

        // Explicit approved_steps & pending_steps combination works too
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?approved_steps=keuangan_pondok&pending_steps=baku')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $formA->id);

        // Now BAKU can sign formA with require_previous_approved=true
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/sign", [
                'step_key' => 'baku',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 2);

        // ready_for_step=baak now returns formA, ready_for_step=baku is empty
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baku')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baak')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $formA->id);

        // Internal UI revoke and external API revoke both work cleanly
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/form-pengambilan-ijazah/{$formA->id}/signatures/baku/revoke")
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 1);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/revoke", ['step_key' => 'keuangan_pondok'])
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 0);

        $this->assertNotNull($formB->id);
    }
}
