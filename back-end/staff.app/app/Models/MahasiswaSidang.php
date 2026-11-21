<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MahasiswaSidang extends Model
{
    use HasFactory;

    protected $table = 'mahasiswa_sidang';

    protected $guarded = ['id'];

    public function jadwalSidang()
    {
        return $this->belongsTo(JadwalSidang::class, 'jadwal_sidang_id');
    }

    public function pengajuanJudul()
    {
        return $this->belongsTo(PengajuanJudul::class, 'pengajuan_judul_id');
    }
}
