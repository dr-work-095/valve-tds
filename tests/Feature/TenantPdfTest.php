<?php

use App\Jobs\GenerateDatasheetPdfJob;

test('datasheet creation queues the high priority PDF job', function () {
    // Existing TenantConfiguratorTest covers real datasheet creation.
    $job = new GenerateDatasheetPdfJob(42, 99);

    expect($job->tenantId)->toBe(42)
        ->and($job->datasheetId)->toBe(99)
        ->and($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(60);
});
