<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicFormSetting extends Model
{
    use HasFactory;

    protected $table = 'public_form_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active'   => 'boolean',
        'expired_at'  => 'datetime',
        'total_submissions' => 'integer',
    ];

    /**
     * Cek apakah formulir publik saat ini aktif dan belum kedaluwarsa.
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expired_at === null) {
            return true;
        }

        return Carbon::now()->lessThanOrEqualTo($this->expired_at);
    }

    /**
     * Dapatkan teks sisa waktu yang ramah pengguna dalam Bahasa Indonesia.
     */
    public function getRemainingTimeText(): string
    {
        if (!$this->is_active) {
            return 'Formulir Sedang Dinonaktifkan';
        }

        if ($this->expired_at === null) {
            return 'Aktif (Tanpa Batas Waktu)';
        }

        $now = Carbon::now();
        if ($now->greaterThan($this->expired_at)) {
            return 'Kedaluwarsa pada ' . $this->expired_at->format('d/m/Y H:i');
        }

        $diffMinutes = (int) $now->diffInMinutes($this->expired_at);
        $diffHours = (int) $now->diffInHours($this->expired_at);
        $diffDays = (int) $now->diffInDays($this->expired_at);

        if ($diffDays > 0) {
            $remainingHours = $diffHours % 24;
            return "Sisa {$diffDays} hari" . ($remainingHours > 0 ? " {$remainingHours} jam" : "");
        }

        if ($diffHours > 0) {
            $remainingMinutes = $diffMinutes % 60;
            return "Sisa {$diffHours} jam" . ($remainingMinutes > 0 ? " {$remainingMinutes} menit" : "");
        }

        return "Sisa {$diffMinutes} menit";
    }
}
