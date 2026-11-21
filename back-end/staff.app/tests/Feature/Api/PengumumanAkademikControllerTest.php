<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\JenisSurat;
use App\Models\PengumumanAkademik;
use App\Models\TandaTangan;
use App\Models\NoSurat;
use App\Models\LogSurat;
use App\Models\SettingJabatan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class PengumumanAkademikControllerTest extends TestCase
{
    protected $user;
    protected $tandaTangan;

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

        Schema::create('tanda_tangan', function ($table) {
            $table->id();
            $table->string('nama');
            $table->text('tdd')->nullable();
            $table->string('gambar')->nullable();
            $table->string('nidn')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('nomor', function ($table) {
            $table->id();
            $table->string('nomor');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('log_surat', function ($table) {
            $table->id();
            $table->string('nomor');
            $table->string('nomor_surat');
            $table->string('nama_surat');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('jenis_surat', function ($table) {
            $table->id();
            $table->string('alias')->unique();
            $table->string('nama')->nullable();
            $table->string('format_surat');
            $table->timestamps();
        });

        Schema::create('setting_jabatan', function ($table) {
            $table->id();
            $table->string('kunci_jabatan')->unique();
            $table->string('nama_jabatan');
            $table->string('nidn')->nullable();
            $table->unsignedBigInteger('tanda_tangan_id')->nullable();
            $table->timestamps();
        });

        Schema::create('pengumuman_akademik', function ($table) {
            $table->id();
            $table->string('nomor_surat');
            $table->string('judul')->nullable();
            $table->string('kepada')->nullable();
            $table->text('isi');
            $table->string('tempat')->default('Pasuruan');
            $table->date('tanggal');
            $table->unsignedBigInteger('tanda_tangan_id')->nullable();
            $table->enum('petanda_tangan', ['ya', 'tidak', 'stempel'])->default('tidak');
            $table->string('drive_file_id')->nullable();
            $table->string('local_path')->nullable();
            $table->string('drive_link')->nullable();
            $table->integer('user_id');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->enum('status', ['pending', 'uploaded', 'failed'])->default('pending');
            $table->timestamps();
        });

        DB::table('level')->insert(['id' => 1, 'nama' => 'baak']);

        $this->user = User::create([
            'name' => 'Staff BAAK',
            'email' => 'baak@example.com',
            'password' => bcrypt('password'),
            'level_id' => 1,
            'jenis_kelamin' => 'L',
        ]);

        $this->tandaTangan = TandaTangan::create([
            'nama' => 'Kepala BAAK M.Pd',
            'tdd' => null,
            'gambar' => null,
            'nidn' => '123456789',
        ]);

        JenisSurat::create([
            'alias' => 'PA',
            'nama' => 'PENGUMUMAN AKADEMIK',
            'format_surat' => 'SU-{NO}/UII.085/BAAK/PP.00/{BULAN}/{TAHUN}',
        ]);

        SettingJabatan::create([
            'kunci_jabatan' => 'BAAK',
            'nama_jabatan' => 'Kepala BAAK',
            'nidn' => '80283',
            'tanda_tangan_id' => $this->tandaTangan->id,
        ]);
    }

    public function test_index_returns_paginated_pengumuman()
    {
        Queue::fake();

        PengumumanAkademik::create([
            'nomor_surat' => 'SU-001/UII.085/BAAK/PP.00/09/2026',
            'judul' => 'Pengumuman KRS',
            'kepada' => 'Seluruh Mahasiswa',
            'isi' => 'Isi pengumuman krs',
            'tempat' => 'Pasuruan',
            'tanggal' => '2026-09-21',
            'user_id' => $this->user->id,
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/pengumuman-akademik');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Data berhasil diambil'
            ]);
    }

    public function test_store_creates_pengumuman_akademik()
    {
        Queue::fake();

        $payload = [
            'no_surat' => '101',
            'judul' => 'Pengumuman Libur Akademik',
            'kepada' => 'Seluruh Mahasiswa dan Dosen',
            'isi' => 'Diberitahukan bahwa libur semester dimulai tanggal 1 Oktober.',
            'tempat' => 'Pasuruan',
            'tanggal' => '2026-09-21',
            'tanda_tangan_id' => $this->tandaTangan->id,
            'petanda_tangan' => 'tidak',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/pengumuman-akademik', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('pengumuman_akademik', [
            'judul' => 'Pengumuman Libur Akademik',
            'isi' => 'Diberitahukan bahwa libur semester dimulai tanggal 1 Oktober.',
        ]);

        $this->assertDatabaseHas('nomor', [
            'nomor' => '101',
        ]);
    }

    public function test_show_returns_detail_pengumuman()
    {
        $item = PengumumanAkademik::create([
            'nomor_surat' => 'SU-002/UII.085/BAAK/PP.00/09/2026',
            'judul' => 'Pengumuman Ujian',
            'kepada' => 'Mahasiswa',
            'isi' => 'Isi ujian',
            'tempat' => 'Pasuruan',
            'tanggal' => '2026-09-21',
            'user_id' => $this->user->id,
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/pengumuman-akademik/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $item->id,
                    'judul' => 'Pengumuman Ujian',
                ]
            ]);
    }

    public function test_update_modifies_pengumuman()
    {
        Queue::fake();

        $item = PengumumanAkademik::create([
            'nomor_surat' => 'SU-003/UII.085/BAAK/PP.00/09/2026',
            'judul' => 'Judul Lama',
            'kepada' => 'Mahasiswa',
            'isi' => 'Isi lama',
            'tempat' => 'Pasuruan',
            'tanggal' => '2026-09-21',
            'user_id' => $this->user->id,
            'jenis_kelamin' => 'L',
        ]);

        $payload = [
            'no_surat' => '003',
            'judul' => 'Judul Baru',
            'kepada' => 'Semua',
            'isi' => 'Isi baru',
            'tempat' => 'Pasuruan',
            'tanggal' => '2026-09-21',
            'petanda_tangan' => 'tidak',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/pengumuman-akademik/{$item->id}", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('pengumuman_akademik', [
            'id' => $item->id,
            'judul' => 'Judul Baru',
            'isi' => 'Isi baru',
        ]);
    }

    public function test_destroy_deletes_pengumuman()
    {
        $item = PengumumanAkademik::create([
            'nomor_surat' => 'SU-004/UII.085/BAAK/PP.00/09/2026',
            'judul' => 'Mau Dihapus',
            'isi' => 'Isi',
            'tanggal' => '2026-09-21',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/pengumuman-akademik/{$item->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('pengumuman_akademik', [
            'id' => $item->id,
        ]);
    }
}
