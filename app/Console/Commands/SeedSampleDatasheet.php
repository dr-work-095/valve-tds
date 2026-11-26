<?php

namespace App\Console\Commands;

use Database\Seeders\TenantSampleDatasheetSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-sample-datasheet')]
#[Description('Seed illustrative datasheet data into the first tenant')]
class SeedSampleDatasheet extends Command
{
    public function handle(TenantSampleDatasheetSeeder $seeder): int
    {
        $seeder->run();

        $this->info('Sample datasheet data seeded for the first tenant.');

        return self::SUCCESS;
    }
}
