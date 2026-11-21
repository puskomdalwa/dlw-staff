<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokumenSkripsi extends Model
{
    use HasFactory;

    protected $table = 'dokumen_skripsi';

    protected $guarded = ['id'];

    public function daftarSidangSkripsi()
    {
        return $this->belongsTo(DaftarSidangSkripsi::class, 'daftar_sidang_skripsi_id');
    }
}

