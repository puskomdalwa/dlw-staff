<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalSidang extends Model
{
    use HasFactory;

    protected $table = 'jadwal_sidang';

    protected $guarded = ['id'];

    public function mahasiswaSidang()
    {
        return $this->hasMany(MahasiswaSidang::class, 'jadwal_sidang_id');
    }

    public function pengajuanJudul()
    {
        return $this->belongsToMany(
            PengajuanJudul::class,
            'mahasiswa_sidang',
            'jadwal_sidang_id',
            'pengajuan_judul_id'
        )->withTimestamps();
    }
}
