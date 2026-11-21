<?php

namespace App\Jobs;

use App\Models\SuratTugas;
use App\Services\GoogleSheetKpiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncSuratTugasKpiToGoogleSheet implements ShouldQueue
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
        Log::info("Handling Google Sheet KPI sync for SuratTugas ID: {$this->suratTugasId}");

        $st = SuratTugas::find($this->suratTugasId);

        if (!$st) {
            Log::warning("SuratTugas ID {$this->suratTugasId} not found for Google Sheet KPI sync");
            return;
        }

        if (!GoogleSheetKpiService::isKpi($st)) {
            Log::info("SuratTugas ID {$this->suratTugasId} bukan prodi KPI, lewati sync Google Sheet");
            return;
        }

        GoogleSheetKpiService::syncPembimbingFromSuratTugas($st);
    }
}

