<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');
        if (!$password && app()->environment(['local', 'testing'])) {
            $password = Str::random(32);
            $this->command?->warn('Generated a local admin password: ' . $password);
        }

        if (!$password) {
            throw new \RuntimeException('ADMIN_PASSWORD must be set before running AdminUserSeeder outside local/testing.');
        }

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Affiliates Admin'),
                'password' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );
    }
}
