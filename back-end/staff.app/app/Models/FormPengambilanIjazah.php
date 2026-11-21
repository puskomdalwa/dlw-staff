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
        'status',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
