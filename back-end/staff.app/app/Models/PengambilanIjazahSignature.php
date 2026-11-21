<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengambilanIjazahSignature extends Model
{
    protected $table = 'pengambilan_ijazah_signatures';

    protected $guarded = ['id'];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function formPengambilanIjazah()
    {
        return $this->belongsTo(FormPengambilanIjazah::class, 'form_pengambilan_ijazah_id');
    }
}
