<?php

namespace App\Jobs;

use App\Models\PengajuanJudul;
use App\Services\GoogleSheetHkiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncPengajuanJudulHkiToGoogleSheet implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $pengajuanJudulId,
        public string $action = 'store' // 'store' or 'update'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Handling Google Sheet HKI sync for PengajuanJudul ID: {$this->pengajuanJudulId}, Action: {$this->action}");

        $pj = PengajuanJudul::find($this->pengajuanJudulId);

        if (!$pj) {
            Log::warning("PengajuanJudul ID {$this->pengajuanJudulId} not found for Google Sheet HKI sync");
            return;
        }

        if (!GoogleSheetHkiService::isHki($pj)) {
            Log::info("PengajuanJudul ID {$this->pengajuanJudulId} bukan prodi HKI, lewati sync Google Sheet");
            return;
        }

        if ($this->action === 'store') {
            GoogleSheetHkiService::appendRow($pj);
        } else {
            GoogleSheetHkiService::updateRow($pj);
        }
    }
}

