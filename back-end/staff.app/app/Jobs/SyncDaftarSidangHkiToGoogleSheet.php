<?php

namespace App\Jobs;

use App\Models\DaftarSidangSkripsi;
use App\Services\GoogleSheetSidangHkiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncDaftarSidangHkiToGoogleSheet implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $daftarSidangId,
        public string $action = 'store', // 'store' or 'update'
        public ?string $oldNim = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Handling Google Sheet Sidang HKI sync for DaftarSidangSkripsi ID: {$this->daftarSidangId}, Action: {$this->action}");

        $daftarSidang = DaftarSidangSkripsi::with(['dokumen', 'prodiRel'])->find($this->daftarSidangId);

        if (!$daftarSidang) {
            Log::warning("DaftarSidangSkripsi ID {$this->daftarSidangId} not found for Google Sheet Sidang HKI sync");
            return;
        }

        if (!GoogleSheetSidangHkiService::isHki($daftarSidang)) {
            Log::info("DaftarSidangSkripsi ID {$this->daftarSidangId} bukan prodi HKI, lewati sync Google Sheet");
            return;
        }

        $synced = $this->action === 'store'
            ? GoogleSheetSidangHkiService::appendRow($daftarSidang)
            : GoogleSheetSidangHkiService::updateRow($daftarSidang, $this->oldNim);

        if (!$synced) {
            throw new \RuntimeException("Sync Google Sheet Sidang HKI gagal untuk DaftarSidangSkripsi ID {$this->daftarSidangId} (action: {$this->action}).");
        }
    }
}
