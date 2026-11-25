<?php

namespace App\Jobs;

use App\Models\GeneratedDatasheet;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Throwable;

class GenerateDatasheetPdfJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $tenantId,
        public int $datasheetId,
    ) {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        $tenant = Tenant::on('central')->findOrFail($this->tenantId);
        tenancy()->initialize($tenant);

        try {
            $datasheet = GeneratedDatasheet::findOrFail($this->datasheetId);
            if ($datasheet->status !== 'queued') {
                return;
            }

            $html = view('tenant.datasheets.pdf', [
                'datasheet' => $datasheet,
                'snapshot' => $datasheet->selected_specs,
            ])->render();

            $filename = 'datasheet-'.$datasheet->document_number.'-'.$datasheet->revision.'-'.Str::lower(Str::random(8)).'.pdf';
            $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'datasheet.pdf';
            $relativePath = $tenant->tenant_uid.'/datasheets/'.$filename;
            $absolutePath = Storage::disk('tenant_private')->path($relativePath);

            if (! is_dir(dirname($absolutePath))) {
                mkdir(dirname($absolutePath), 0755, true);
            }

            Browsershot::html($html)
                ->setChromePath(env('CHROME_PATH', 'C:\Program Files\Google\Chrome\Application\chrome.exe'))
                ->showBackground()
                ->format('A4')
                ->margins(8, 8, 8, 8)
                ->save($absolutePath);

            if (! is_file($absolutePath) || filesize($absolutePath) < 100) {
                throw new RuntimeException('The generated PDF is empty or invalid.');
            }

            $datasheet->update([
                'status' => 'generated',
                'pdf_path' => $relativePath,
            ]);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        $tenant = Tenant::on('central')->find($this->tenantId);

        if ($tenant) {
            tenancy()->initialize($tenant);
            try {
                GeneratedDatasheet::whereKey($this->datasheetId)->update(['status' => 'failed']);
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        Log::error('Datasheet PDF generation failed.', [
            'tenant_id' => $this->tenantId,
            'datasheet_id' => $this->datasheetId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
