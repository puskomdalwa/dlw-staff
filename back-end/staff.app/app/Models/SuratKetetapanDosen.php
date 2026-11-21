<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKetetapanDosen extends Model
{
    use HasFactory;

    protected $table = 'surat_ketetapan_dosen';

    protected $guarded = [];

    public function institusi()
    {
        return $this->belongsTo(Institusi::class, 'institusi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
