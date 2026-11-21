<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengumumanAkademik extends Model
{
    use HasFactory;

    protected $table = 'pengumuman_akademik';

    protected $guarded = ['id'];

    public function tandaTangan()
    {
        return $this->belongsTo(TandaTangan::class, 'tanda_tangan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
