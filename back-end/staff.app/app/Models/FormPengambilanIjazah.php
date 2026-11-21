<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormPengambilanIjazah extends Model
{
    use HasFactory;

    protected $table = 'form_pengambilan_ijazah';

    protected $guarded = ['id'];

    protected $fillable = [
        'nama_mhs',
        'nim',
        'prodi',
        'dosen_pembimbing',
        'judul_skripsi',
        'tanggal_sidang',
        'no_hp',
        'drive_file_id',
        'local_path',
        'drive_link',
        'signed_local_path',
        'signed_drive_file_id',
        'signed_drive_link',
        'status',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function signatures()
    {
        return $this->hasMany(PengambilanIjazahSignature::class, 'form_pengambilan_ijazah_id')
            ->orderBy('step_order');
    }
}
