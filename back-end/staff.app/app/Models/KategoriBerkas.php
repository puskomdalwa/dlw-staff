<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriBerkas extends Model
{
    use HasFactory;

    protected $table = 'kategori_berkas';

    protected $guarded = ['id'];

    public function berkas()
    {
        return $this->hasMany(Berkas::class, 'kategori_berkas_id');
    }
}
