<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sk6Signature extends Model
{
    protected $table = 'sk6_signatures';

    protected $guarded = ['id'];

    protected $casts = [
        'signed_at' => 'datetime',
        'step_order' => 'integer',
    ];

    public function suratKeterangan6()
    {
        return $this->belongsTo(SuratKeterangan6::class, 'surat_keterangan_6_id');
    }
}
