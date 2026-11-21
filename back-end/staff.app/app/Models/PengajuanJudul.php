<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanJudul extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_judul';

    protected $guarded = ['id'];

    public function prodiRel()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function tandaTangan()
    {
        return $this->belongsTo(TandaTangan::class, 'tanda_tangan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

