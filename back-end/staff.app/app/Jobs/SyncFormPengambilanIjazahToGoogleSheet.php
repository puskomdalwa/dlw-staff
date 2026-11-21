<?php

namespace App\Jobs;

use App\Models\FormPengambilanIjazah;
use App\Services\GoogleSheetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncFormPengambilanIjazahToGoogleSheet implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $formId,
        public string $action = 'store' // 'store' or 'update'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Handling Google Sheet sync for FormPengambilanIjazah ID: {$this->formId}, Action: {$this->action}");

        $form = FormPengambilanIjazah::find($this->formId);

        if (!$form) {
            Log::warning("FormPengambilanIjazah ID {$this->formId} not found for Google Sheet sync");
            return;
        }

        if ($this->action === 'store') {
            GoogleSheetService::appendRow($form);
        } else {
            GoogleSheetService::updateRow($form);
        }
    }
}
