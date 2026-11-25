<?php

use App\Models\Admin;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\Rule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create', function () {
    $name = trim((string) $this->ask('Super Admin name'));
    $email = strtolower(trim((string) $this->ask('Super Admin email')));
    $password = (string) $this->secret('Password (minimum 12 characters)');
    $confirmation = (string) $this->secret('Confirm password');

    $validator = validator(
        compact('name', 'email', 'password', 'confirmation'),
        [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('central.admins')],
            'password' => ['required', 'string', 'min:12', 'same:confirmation'],
        ],
        ['password.same' => 'The password confirmation does not match.'],
    );

    if ($validator->fails()) {
        $this->error($validator->errors()->first());

        return self::FAILURE;
    }

    Admin::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'is_active' => true,
    ]);

    $this->info('Super Admin account created.');

    return self::SUCCESS;
})->purpose('Create a Super Admin account using interactive prompts');

Schedule::command('app:expire-overdue-tenants')->dailyAt('01:00')->onOneServer();
