<?php

namespace App\Jobs;

use App\Models\SuratTugas;
use App\Services\GoogleSheetEsyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncSuratTugasEsyToGoogleSheet implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $suratTugasId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Handling Google Sheet ESY sync for SuratTugas ID: {$this->suratTugasId}");

        $st = SuratTugas::find($this->suratTugasId);

        if (!$st) {
            Log::warning("SuratTugas ID {$this->suratTugasId} not found for Google Sheet sync");
            return;
        }

        if (!GoogleSheetEsyService::isEkonomiSyariah($st)) {
            Log::info("SuratTugas ID {$this->suratTugasId} bukan prodi Ekonomi Syariah, lewati sync Google Sheet");
            return;
        }

        GoogleSheetEsyService::syncPembimbingFromSuratTugas($st);
    }
}

