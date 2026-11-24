<?php

namespace App\Support;

use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\UniqueIdentifierGenerator;

class TenantIdGenerator implements UniqueIdentifierGenerator
{
    public static function generate($resource): string
    {
        $companyName = is_object($resource) ? (string) ($resource->company_name ?? '') : '';
        $slug = preg_replace('/[^a-z0-9]/', '', Str::lower($companyName)) ?? '';

        return 'tenant_'.($slug !== '' ? $slug : 'company');
    }
}
