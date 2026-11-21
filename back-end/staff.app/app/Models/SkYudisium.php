<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkYudisium extends Model
{
    use HasFactory;

    protected $table = 'sk_yudisium';

    protected $guarded = [];

    protected static function booted()
    {
        static::saved(function ($model) {
            if ($model->isDirty(['drive_file_id', 'drive_link', 'status', 'local_path']) && !empty($model->batch_id)) {
                static::withoutEvents(function () use ($model) {
                    static::where('batch_id', $model->batch_id)
                        ->where('id', '!=', $model->id)
                        ->update([
                            'drive_file_id' => $model->drive_file_id,
                            'drive_link'    => $model->drive_link,
                            'status'        => $model->status,
                            'local_path'    => $model->local_path,
                        ]);
                });
            }
        });
    }

    public function prodi()
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
