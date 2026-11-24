<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = tenancy()->tenant;

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Tenant database seeding requires an initialized tenant.');
        }

        $timestamp = now();

        DB::connection('tenant')->table('company_settings')->updateOrInsert(
            ['id' => 1],
            [
                'company_name' => $tenant->company_name,
                'updated_at' => $timestamp,
            ],
        );

        DB::connection('tenant')->table('materials')->insertOrIgnore([
            ['code' => 'ASTM A216 WCB', 'name' => 'Carbon Steel Cast', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A216 WCC', 'name' => 'Carbon Steel Cast', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A352 LCB', 'name' => 'Low Temperature Carbon Steel Cast', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A352 LCC', 'name' => 'Low Temperature Carbon Steel Cast', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A351 CF8', 'name' => 'Stainless Steel Cast 304', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A351 CF8M', 'name' => 'Stainless Steel Cast 316', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A351 CF3M', 'name' => 'Low Carbon Stainless Steel Cast 316L', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A217 WC6', 'name' => 'Chrome-Moly Alloy Steel Cast', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A217 WC9', 'name' => 'Chrome-Moly Alloy Steel Cast', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A995 4A', 'name' => 'Duplex Stainless Steel Cast', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A995 5A', 'name' => 'Super Duplex Stainless Steel Cast', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A995 6A', 'name' => 'Super Duplex Stainless Steel Cast', 'category' => 'Body/Bonnet', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A182 F6a', 'name' => '13% Chromium Stainless Steel', 'category' => 'Trim/Stem', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A182 F304', 'name' => 'Stainless Steel Forging 304', 'category' => 'Trim/Stem', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A182 F316', 'name' => 'Stainless Steel Forging 316', 'category' => 'Trim/Stem', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A182 F316L', 'name' => 'Low Carbon Stainless Steel Forging 316L', 'category' => 'Trim/Stem', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A182 F51', 'name' => 'Duplex Stainless Steel Forging', 'category' => 'Trim/Stem', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A276 420', 'name' => 'Martensitic Stainless Steel Bar', 'category' => 'Trim/Stem', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'PTFE', 'name' => 'Polytetrafluoroethylene', 'category' => 'Seat/Seal', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'RPTFE', 'name' => 'Reinforced Polytetrafluoroethylene', 'category' => 'Seat/Seal', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'PEEK', 'name' => 'Polyether Ether Ketone', 'category' => 'Seat/Seal', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'Flexible Graphite', 'name' => 'Flexible Graphite', 'category' => 'Seat/Seal', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'Buna-N', 'name' => 'Nitrile Rubber', 'category' => 'Seat/Seal', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'EPDM', 'name' => 'Ethylene Propylene Diene Monomer', 'category' => 'Seat/Seal', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'FKM', 'name' => 'Fluoroelastomer', 'category' => 'Seat/Seal', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'Monel 400', 'name' => 'Nickel-Copper Alloy', 'category' => 'Trim/Stem', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A193 B7', 'name' => 'Alloy Steel Bolting', 'category' => 'Fasteners', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A193 B8M', 'name' => 'Stainless Steel Bolting 316', 'category' => 'Fasteners', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A194 2H', 'name' => 'Carbon Steel Nuts', 'category' => 'Fasteners', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'ASTM A194 8M', 'name' => 'Stainless Steel Nuts 316', 'category' => 'Fasteners', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }
}
