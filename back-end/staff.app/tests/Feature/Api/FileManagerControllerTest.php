<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Level;
use App\Models\Prodi;
use App\Models\PengumumanAkademik;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class FileManagerControllerTest extends TestCase
{
    protected $baakUser;
    protected $prodiUser;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('level', function ($table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('prodi', function ($table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('level_id');
            $table->unsignedBigInteger('prodi_id')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('pengumuman_akademik', function ($table) {
            $table->id();
            $table->string('nomor_surat')->nullable();
            $table->string('tentang')->nullable();
            $table->string('kepada')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->string('local_path')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('drive_link')->nullable();
            $table->string('file_word_path')->nullable();
            $table->timestamps();
        });

        Level::create(['id' => 1, 'nama' => 'staff']);
        Level::create(['id' => 2, 'nama' => 'admin']);
        Level::create(['id' => 3, 'nama' => 'baak']);

        $prodi = Prodi::create(['id' => 1, 'nama' => 'Ekonomi Syariah']);

        $this->baakUser = User::create([
            'name' => 'BAAK User',
            'email' => 'baak@example.com',
            'password' => bcrypt('secret'),
            'level_id' => 3,
            'prodi_id' => null,
        ]);

        $this->prodiUser = User::create([
            'name' => 'Staff Prodi',
            'email' => 'prodi@example.com',
            'password' => bcrypt('secret'),
            'level_id' => 1,
            'prodi_id' => $prodi->id,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'level_id' => 2,
            'prodi_id' => null,
        ]);
    }

    public function test_baak_user_only_sees_baak_folders()
    {
        // Pastikan folder BAAK/Pengumuman Akademik ada
        $baakDir = base_path('../public_html/pdf/BAAK/Pengumuman Akademik');
        if (!File::exists($baakDir)) {
            File::makeDirectory($baakDir, 0755, true);
        }

        // Pastikan ada folder prodi lain
        $otherProdiDir = base_path('../public_html/pdf/Ekonomi Syariah');
        if (!File::exists($otherProdiDir)) {
            File::makeDirectory($otherProdiDir, 0755, true);
        }

        $response = $this->actingAs($this->baakUser, 'sanctum')
            ->getJson('/api/file-manager/list');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'user_prodi' => 'BAAK',
                ]
            ]);

        $folders = collect($response->json('data.folders'))->pluck('name')->toArray();

        // Role BAAK harus melihat folder miliknya (Pengumuman Akademik)
        $this->assertContains('Pengumuman Akademik', $folders);

        // Role BAAK TIDAK BOLEH melihat folder prodi lain seperti Ekonomi Syariah
        $this->assertNotContains('Ekonomi Syariah', $folders);
    }

    public function test_baak_user_can_view_pengumuman_akademik_files_and_drive_link()
    {
        $dir = base_path('../public_html/pdf/BAAK/Pengumuman Akademik');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $testFile = $dir . '/test_pengumuman_unit.pdf';
        File::put($testFile, '%PDF-1.4 dummy pdf content');

        PengumumanAkademik::create([
            'nomor_surat' => '999/BAAK/2026',
            'tentang' => 'Pengumuman Libur Akademik',
            'local_path' => 'BAAK/Pengumuman Akademik/test_pengumuman_unit.pdf',
            'drive_link' => 'https://drive.google.com/file/d/test_drive_link/view',
        ]);

        $response = $this->actingAs($this->baakUser, 'sanctum')
            ->getJson('/api/file-manager/list?path=Pengumuman%20Akademik');

        $response->assertStatus(200);

        $files = collect($response->json('data.files'));
        $matched = $files->firstWhere('name', 'test_pengumuman_unit.pdf');

        $this->assertNotNull($matched);
        $this->assertEquals('https://drive.google.com/file/d/test_drive_link/view', $matched['drive_link']);

        if (File::exists($testFile)) {
            File::delete($testFile);
        }
    }

    public function test_staff_user_sees_their_prodi_folder()
    {
        $response = $this->actingAs($this->prodiUser, 'sanctum')
            ->getJson('/api/file-manager/list');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('Ekonomi Syariah', $data['user_prodi']);
    }

    public function test_admin_user_sees_all_folders()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/file-manager/list');

        $response->assertStatus(200);

        $folders = collect($response->json('data.folders'))->pluck('name')->toArray();
        $this->assertContains('BAAK', $folders);
        $this->assertContains('Ekonomi Syariah', $folders);
    }
}
