<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    public const PLANS = ['starter', 'growth', 'pro'];

    public const STATUSES = ['trial', 'active', 'expired', 'suspended'];

    public const PROVISIONING_STATUSES = ['pending', 'provisioning', 'ready', 'failed'];

    use HasDatabase;

    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $connection = 'central';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (! is_string($tenant->tenant_uid) || $tenant->tenant_uid === '') {
                $tenant->tenant_uid = static::generateTenantUid($tenant->company_name);
            }
        });
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'tenant_uid',
            'company_name',
            'plan',
            'status',
            'subscription_ends_at',
            'admin_notes',
            'created_at',
            'updated_at',
            'data',
        ];
    }

    protected function casts(): array
    {
        return [
            'subscription_ends_at' => 'datetime',
            'data' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id', 'tenant_uid');
    }

    public function getTenantKeyName(): string
    {
        return 'tenant_uid';
    }

    public function getIncrementing()
    {
        return true;
    }

    public function getKeyType()
    {
        return 'int';
    }

    public static function generateTenantUid(string $companyName): string
    {
        $slug = preg_replace('/[^a-z0-9]/', '', Str::lower($companyName)) ?? '';
        $base = 'tenant_'.($slug !== '' ? $slug : 'company');
        $tenantUid = $base;
        $counter = 2;

        while (static::on('central')->where('tenant_uid', $tenantUid)->exists()) {
            $tenantUid = $base.$counter;
            $counter++;
        }

        return substr($tenantUid, 0, 64);
    }

    public function provisioningStatus(): string
    {
        return (string) ($this->getInternal('provisioning_status') ?? 'pending');
    }

    public function provisioningError(): ?string
    {
        $error = $this->getInternal('provisioning_error');

        return is_string($error) ? $error : null;
    }
}
