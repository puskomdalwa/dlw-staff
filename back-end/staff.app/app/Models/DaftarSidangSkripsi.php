<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaftarSidangSkripsi extends Model
{
    use HasFactory;

    protected $table = 'daftar_sidang_skripsi';

    protected $guarded = ['id'];

    protected $casts = [
        'periode_sidang' => 'date:Y-m-d',
    ];

    public function dokumen()
    {
        return $this->hasMany(DokumenSkripsi::class, 'daftar_sidang_skripsi_id');
    }

    public function prodiRel()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

