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
        config([
            'app.url' => 'http://localhost',
            'simkeu.simkeu_url' => null,
        ]);
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

        // 2. Index endpoint includes 4 visible signature steps (baku_kabag hidden from UI timeline, baku_aupk labeled BAKU)
        $indexRes = $this->actingAs($user, 'sanctum')->getJson('/api/form-pengambilan-ijazah');
        $indexRes->assertStatus(200)
            ->assertJsonPath('data.data.0.id', $form->id)
            ->assertJsonPath('data.data.0.signed_count', 0)
            ->assertJsonPath('data.data.0.total_steps', 4)
            ->assertJsonPath('data.data.0.is_completed', false);

        $indexSteps = $indexRes->json('data.data.0.signatures');
        $this->assertSame(
            ['keuangan_pondok', 'baku_aupk', 'baak', 'warek_1'],
            array_column($indexSteps, 'step_key')
        );
        $this->assertSame([1, 2, 3, 4], array_column($indexSteps, 'step_order'));
        $this->assertSame('BAKU', $indexSteps[1]['short_label']);
        $this->assertSame(
            [false, false, false, false],
            array_column($indexSteps, 'can_sign_in_staff')
        );

        // 3. Download PDF before all 5/5 signed is blocked (422)
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

        // 5. All steps on Form Pengambilan Ijazah are external-only and blocked (403) from web staff
        foreach (['keuangan_pondok', 'baku_kabag', 'baku_aupk', 'baak', 'warek_1'] as $externalStep) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/form-pengambilan-ijazah/{$form->id}/signatures/{$externalStep}/confirm")
                ->assertStatus(403);
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/form-pengambilan-ijazah/{$form->id}/signatures/{$externalStep}/revoke")
                ->assertStatus(403);
        }

        // Still not 5/5: no signed Drive job yet
        Queue::assertNotPushed(UploudSuratToDrive::class, function ($job) {
            return $job->isSignedSk6 === true;
        });

        // 6. Sign steps 1, 2, 3, 4 & 5 via External API
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson('/api/external/form-pengambilan-ijazah/by-nim/202285290099/sign', [
                'step_key' => 'keuangan_pondok',
                'signer_name' => 'Ust. Keuangan Pondok',
                'notes' => 'Lunas syahriah & tanggungan pondok',
                'source' => 'web-pondok',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 1)
            ->assertJsonPath('data.form.is_completed', false);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson('/api/external/form-pengambilan-ijazah/by-nim/202285290099/sign', [
                'step_key' => 'baku_kabag',
                'signer_name' => 'Kabag BAKU Test',
                'source' => 'web-keuangan',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 2)
            ->assertJsonPath('data.form.is_completed', false);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson('/api/external/form-pengambilan-ijazah/by-nim/202285290099/sign', [
                'step_key' => 'baku_aupk',
                'signer_name' => 'Dr. Musleh Harry, SH, M.Hum',
                'source' => 'web-keuangan',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 3)
            ->assertJsonPath('data.form.is_completed', false);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson('/api/external/form-pengambilan-ijazah/by-nim/202285290099/sign', [
                'step_key' => 'baak',
                'signer_name' => 'Kepala BAAK Test',
                'source' => 'web-baak',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 4)
            ->assertJsonPath('data.form.is_completed', false);

        $finalSign = $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson('/api/external/form-pengambilan-ijazah/by-nim/202285290099/sign', [
                'step_key' => 'warek_1',
                'signer_name' => 'Dr. Habib Zainal Abidin Bilfaqih, M.Pd.',
                'source' => 'web-warek1',
            ]);
        $finalSign->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 5)
            ->assertJsonPath('data.form.is_completed', true);

        $form->refresh();
        if (!empty($form->signed_local_path)) {
            $this->cleanupPaths[] = $form->signed_local_path;
        }

        // Signed PDF generated and queued for signed Drive upload (with embedded QR PNG images, excluding baku_kabag)
        $this->assertNotEmpty($form->signed_local_path);
        $this->assertFileExists($form->signed_local_path);
        $pdfBinary = file_get_contents($form->signed_local_path);
        $this->assertIsString($pdfBinary);
        $this->assertStringContainsString('/Subtype /Image', $pdfBinary);

        $renderedHtml = view('pdf.form_pengambilan_ijazah', app(\App\Services\PengambilanIjazahSignatureService::class)->buildPdfData($form, true))->render();
        $this->assertStringContainsString('QR BAKU AUPK', $renderedHtml);
        $this->assertStringNotContainsString('QR BAKU Kabag', $renderedHtml);
        $this->assertStringContainsString('data:image/png;base64,', $renderedHtml);
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
            ->assertJsonPath('data.signed_count', 4);

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

        // 11. Verify index returns short_label ('BAKU') & is_signed booleans (no raw Eloquent overwrite)
        $indexCheck = $this->actingAs($user, 'sanctum')->getJson('/api/form-pengambilan-ijazah');
        $indexCheck->assertStatus(200)
            ->assertJsonPath('data.data.0.signatures.0.short_label', 'Keu. Pondok')
            ->assertJsonPath('data.data.0.signatures.1.short_label', 'BAKU')
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
            ->assertJsonCount(5, 'data');

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
                        'step_key' => 'baku_kabag',
                        'title' => 'Kabag BAKU (Keuangan Jamiah)',
                        'signer_name' => 'Kabag BAKU Baru, S.E.',
                        'signer_nidn' => '445511',
                    ],
                    [
                        'step_key' => 'baku_aupk',
                        'title' => 'Kepala Biro AUPK (Keuangan Jamiah)',
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
            ->assertJsonPath('data.affected_signatures', 5);

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

        // Before keuangan_pondok signs: ready_for_step=baku_kabag returns 0 items
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baku_kabag')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        // Signing baku_kabag with require_previous_approved=true before keuangan_pondok is rejected (422)
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/sign", [
                'step_key' => 'baku_kabag',
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

        // Now ready_for_step=baku_kabag returns ONLY formA (not formB), while baku_aupk is still 0
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baku_kabag')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $formA->id);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baku_aupk')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        // Explicit approved_steps & pending_steps combination works too
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?approved_steps=keuangan_pondok&pending_steps=baku_kabag')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $formA->id);

        // BAKU AUPK cannot sign yet with require_previous_approved=true before BAKU Kabag
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/sign", [
                'step_key' => 'baku_aupk',
                'require_previous_approved' => true,
            ])
            ->assertStatus(422);

        // Now BAKU Kabag signs formA with require_previous_approved=true
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/sign", [
                'step_key' => 'baku_kabag',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 2);

        // Now ready_for_step=baku_aupk returns formA, ready_for_step=baku_kabag is empty
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baku_kabag')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baku_aupk')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $formA->id);

        // Now BAKU AUPK signs formA
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/sign", [
                'step_key' => 'baku_aupk',
                'require_previous_approved' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.form.signed_count', 3);

        // ready_for_step=baak now returns formA, ready_for_step=baku_aupk is empty
        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baku_aupk')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?ready_for_step=baak')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $formA->id);

        // Internal UI revoke is blocked for all steps (403); external API revoke works
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/form-pengambilan-ijazah/{$formA->id}/signatures/keuangan_pondok/revoke")
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/form-pengambilan-ijazah/{$formA->id}/signatures/baku_aupk/revoke")
            ->assertStatus(403);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/revoke", ['step_key' => 'baku_aupk'])
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 2);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/revoke", ['step_key' => 'baku_kabag'])
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 1);

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->postJson("/api/external/form-pengambilan-ijazah/{$formA->id}/revoke", ['step_key' => 'keuangan_pondok'])
            ->assertStatus(200)
            ->assertJsonPath('data.signed_count', 0);

        $this->assertNotNull($formB->id);
    }

    public function test_jenis_kelamin_storage_and_putra_putri_filters(): void
    {
        Queue::fake();
        $user = $this->createAdminUser();

        // Store Putra (L) via admin endpoint
        $resPutra = $this->actingAs($user, 'sanctum')
            ->postJson('/api/form-pengambilan-ijazah', [
                'nama_mhs' => 'Ahmad Putra',
                'nim' => '20229101',
                'prodi' => 'PBA',
                'jenis_kelamin' => 'L',
                'dosen_pembimbing' => 'Dosen A',
                'judul_skripsi' => 'Judul Putra',
                'tanggal_sidang' => '2026-11-20',
            ]);
        $resPutra->assertStatus(201)->assertJsonPath('data.jenis_kelamin', 'L');
        $formPutra = FormPengambilanIjazah::findOrFail($resPutra->json('data.id'));
        if (!empty($formPutra->local_path)) {
            $this->cleanupPaths[] = $formPutra->local_path;
        }

        // Store Putri (P) via admin endpoint
        $resPutri = $this->actingAs($user, 'sanctum')
            ->postJson('/api/form-pengambilan-ijazah', [
                'nama_mhs' => 'Fatimah Putri',
                'nim' => '20229102',
                'prodi' => 'PBA',
                'jenis_kelamin' => 'P',
                'dosen_pembimbing' => 'Dosen B',
                'judul_skripsi' => 'Judul Putri',
                'tanggal_sidang' => '2026-11-20',
            ]);
        $resPutri->assertStatus(201)->assertJsonPath('data.jenis_kelamin', 'P');
        $formPutri = FormPengambilanIjazah::findOrFail($resPutri->json('data.id'));
        if (!empty($formPutri->local_path)) {
            $this->cleanupPaths[] = $formPutri->local_path;
        }

        // Internal index filters by explicit ?jenis_kelamin=P
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/form-pengambilan-ijazah?jenis_kelamin=P')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $formPutri->id);

        // External API filters by ?jenis_kelamin=L / ?jenis_kelamin=putri and rejects invalid value
        $apiKey = IntegrationApiKey::getOrCreateForService(
            'pengambilan_ijazah_external',
            'Form Pengambilan Ijazah External Integration',
            'pij_',
            $user->id
        )->api_key;

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?jenis_kelamin=putra')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $formPutra->id)
            ->assertJsonPath('data.items.0.jenis_kelamin', 'L');

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?jenis_kelamin=P')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $formPutri->id)
            ->assertJsonPath('data.items.0.jenis_kelamin', 'P');

        $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson('/api/external/form-pengambilan-ijazah?jenis_kelamin=salah')
            ->assertStatus(422);
    }

    public function test_fix_gender_batch_cursor_resolves_local_records_without_timeout(): void
    {
        $userPutra = $this->createAdminUser();
        $prodi = \App\Models\Prodi::create([
            'kode' => '88204',
            'alias' => 'PBA',
            'nama' => 'Pendidikan Bahasa Arab',
            'aktif' => 'Y',
        ]);

        // Seed local SKLMK record with formatted NIM (dots) and gender 'P'
        (new \App\Models\SuratKeteranganLulusMataKuliah())->forceFill([
            'nomor_surat' => 'SKLM-FIX-01',
            'prodi_id' => $prodi->id,
            'nama_lengkap' => 'Siti Fatimah',
            'tempat_lahir' => 'Bangil',
            'tanggal_lahir' => '2002-01-01',
            'nim' => '2017.85.01.1723',
            'prodi_mahasiswa' => 'PBA',
            'alamat_rumah' => 'Bangil',
            'kelas_pondok' => 'VI A',
            'tanggal' => '2026-11-21',
            'user_id' => $userPutra->id,
            'jenis_kelamin' => 'P',
            'status' => 'pending',
        ])->save();

        // Row 1: matches SKLMK even without dots in NIM
        $f1 = FormPengambilanIjazah::create([
            'nama_mhs' => 'Siti Fatimah',
            'nim' => '201785011723',
            'prodi' => 'PBA',
            'dosen_pembimbing' => 'Dosen A',
            'judul_skripsi' => 'Judul 1',
            'tanggal_sidang' => '2026-11-20',
            'status' => 'pending',
        ]);

        // Row 2: already has jenis_kelamin -> should be skipped by batch query
        $f2 = FormPengambilanIjazah::create([
            'nama_mhs' => 'Sudah Ada',
            'nim' => '20229999',
            'prodi' => 'PBA',
            'jenis_kelamin' => 'L',
            'dosen_pembimbing' => 'Dosen A',
            'judul_skripsi' => 'Judul 2',
            'tanggal_sidang' => '2026-11-20',
            'status' => 'pending',
        ]);

        // Row 3: fallback to user_id gender ('L')
        $f3 = FormPengambilanIjazah::create([
            'nama_mhs' => 'Dari User',
            'nim' => '99990001',
            'prodi' => 'PBA',
            'dosen_pembimbing' => 'Dosen A',
            'judul_skripsi' => 'Judul 3',
            'tanggal_sidang' => '2026-11-20',
            'user_id' => $userPutra->id,
            'status' => 'pending',
        ]);

        // Row 4: completely unknown NIM & no user_id -> unresolved
        $f4 = FormPengambilanIjazah::create([
            'nama_mhs' => 'Tidak Diketahui',
            'nim' => '00000000',
            'prodi' => 'PBA',
            'dosen_pembimbing' => 'Dosen A',
            'judul_skripsi' => 'Judul 4',
            'tanggal_sidang' => '2026-11-20',
            'status' => 'pending',
        ]);

        // Batch 1 (batch_size=2, include_simkeu=false for deterministic offline test)
        $batch1 = $this->actingAs($userPutra, 'sanctum')
            ->postJson('/api/form-pengambilan-ijazah/fix-gender-batch', [
                'after_id' => 0,
                'batch_size' => 2,
                'include_simkeu' => false,
            ]);

        $batch1->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.total_target', 3)
            ->assertJsonPath('data.processed_in_batch', 2)
            ->assertJsonPath('data.updated_count', 2)
            ->assertJsonPath('data.skipped_not_found', 0)
            ->assertJsonPath('data.has_more', true);

        $nextAfterId = $batch1->json('data.next_after_id');
        $this->assertSame($f3->id, $nextAfterId);

        // Batch 2 (remaining 1 row: $f4)
        $batch2 = $this->actingAs($userPutra, 'sanctum')
            ->postJson('/api/form-pengambilan-ijazah/fix-gender-batch', [
                'after_id' => $nextAfterId,
                'batch_size' => 2,
                'include_simkeu' => false,
            ]);

        $batch2->assertStatus(200)
            ->assertJsonPath('data.processed_in_batch', 1)
            ->assertJsonPath('data.updated_count', 0)
            ->assertJsonPath('data.skipped_not_found', 1)
            ->assertJsonPath('data.has_more', false)
            ->assertJsonPath('data.unresolved_samples.0.id', $f4->id);

        $this->assertSame('P', $f1->fresh()->jenis_kelamin);
        $this->assertSame('L', $f2->fresh()->jenis_kelamin);
        $this->assertSame('L', $f3->fresh()->jenis_kelamin);
        $this->assertNull($f4->fresh()->jenis_kelamin);
    }

    public function test_legacy_baku_rows_are_migrated_to_kabag_and_aupk_order(): void
    {
        $user = $this->createAdminUser();
        $form = FormPengambilanIjazah::create([
            'nama_mhs' => 'Legacy Mahasiswa',
            'nim' => '20229988',
            'prodi' => 'PBA',
            'dosen_pembimbing' => 'Dosen Legacy',
            'judul_skripsi' => 'Judul Legacy',
            'tanggal_sidang' => '2026-11-20',
            'status' => 'pending',
        ]);

        \Illuminate\Support\Facades\DB::table('pengambilan_ijazah_signer_defaults')->insert([
            ['step_order' => 1, 'step_key' => 'keuangan_pondok', 'title' => 'Keuangan Pondok', 'signer_name' => 'Pondok', 'signer_nidn' => null],
            ['step_order' => 2, 'step_key' => 'baku', 'title' => 'Kepala Biro Administrasi Keuangan (Keuangan Jamiah)', 'signer_name' => 'Dr. Musleh Harry, SH, M.Hum', 'signer_nidn' => null],
            ['step_order' => 3, 'step_key' => 'baak', 'title' => 'Kepala BAAK (Akademik Jamiah)', 'signer_name' => 'BAAK', 'signer_nidn' => null],
            ['step_order' => 4, 'step_key' => 'warek_1', 'title' => 'Wakil Rektor I', 'signer_name' => 'Warek', 'signer_nidn' => null],
        ]);

        \Illuminate\Support\Facades\DB::table('pengambilan_ijazah_signatures')->insert([
            ['form_pengambilan_ijazah_id' => $form->id, 'step_order' => 1, 'step_key' => 'keuangan_pondok', 'title' => 'Keuangan Pondok', 'status' => 'pending', 'signature_token' => 'pij_tok_1'],
            ['form_pengambilan_ijazah_id' => $form->id, 'step_order' => 2, 'step_key' => 'baku', 'title' => 'Kepala Biro Administrasi Keuangan (Keuangan Jamiah)', 'status' => 'approved', 'signature_token' => 'pij_tok_2'],
            ['form_pengambilan_ijazah_id' => $form->id, 'step_order' => 3, 'step_key' => 'baak', 'title' => 'Kepala BAAK (Akademik Jamiah)', 'status' => 'pending', 'signature_token' => 'pij_tok_3'],
            ['form_pengambilan_ijazah_id' => $form->id, 'step_order' => 4, 'step_key' => 'warek_1', 'title' => 'Wakil Rektor I', 'status' => 'pending', 'signature_token' => 'pij_tok_4'],
        ]);

        $defaultsRes = $this->actingAs($user, 'sanctum')->getJson('/api/form-pengambilan-ijazah/signer-defaults');
        $defaultsRes->assertStatus(200);
        $defaults = $defaultsRes->json('data');
        $this->assertSame(['keuangan_pondok', 'baku_kabag', 'baku_aupk', 'baak', 'warek_1'], array_column($defaults, 'step_key'));
        $this->assertSame([1, 2, 3, 4, 5], array_column($defaults, 'step_order'));

        // Internal timeline hides baku_kabag (4 visible steps, baku_aupk labeled BAKU)
        $timelineRes = $this->actingAs($user, 'sanctum')->getJson("/api/form-pengambilan-ijazah/{$form->id}/signatures");
        $timelineRes->assertStatus(200);
        $steps = $timelineRes->json('data.steps');
        $this->assertSame(['keuangan_pondok', 'baku_aupk', 'baak', 'warek_1'], array_column($steps, 'step_key'));
        $this->assertSame([1, 2, 3, 4], array_column($steps, 'step_order'));
        $this->assertSame('approved', $steps[1]['status']);
        $this->assertSame('BAKU', $steps[1]['short_label']);
        $this->assertSame('pij_tok_2', $steps[1]['signature_token']);

        // External API still exposes all 5 steps including baku_kabag
        $apiKey = IntegrationApiKey::getOrCreateForService(
            'pengambilan_ijazah_external',
            'Form Pengambilan Ijazah External Integration',
            'pij_',
            $user->id
        )->api_key;
        $extRes = $this->withHeaders(['X-API-Key' => $apiKey])
            ->getJson("/api/external/form-pengambilan-ijazah/{$form->id}");
        $extRes->assertStatus(200);
        $extSteps = $extRes->json('data.steps');
        $this->assertSame(['keuangan_pondok', 'baku_kabag', 'baku_aupk', 'baak', 'warek_1'], array_column($extSteps, 'step_key'));
        $this->assertSame([1, 2, 3, 4, 5], array_column($extSteps, 'step_order'));
        $this->assertSame('pending', $extSteps[1]['status']);
        $this->assertSame('approved', $extSteps[2]['status']);
    }
}
