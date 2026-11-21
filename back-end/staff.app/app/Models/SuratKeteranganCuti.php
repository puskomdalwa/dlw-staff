<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKeteranganCuti extends Model
{
    use HasFactory;

    protected $table = 'surat_keterangan_cuti';

    protected $guarded = [];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
