<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@bukutu.co.tz',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        // Create demo packages
        $packages = [
            [
                'name' => '6 Hours',
                'description' => 'Browsing na WhatsApp muda mrefu — bei nafuu',
                'price' => 600,
                'duration_minutes' => 360,
                'upload_speed' => '2M',
                'download_speed' => '5M',
                'mikrotik_profile' => '2M_5M',
                'sort_order' => 1,
            ],
            [
                'name' => '12 Hours',
                'description' => 'Siku nzima ya internet — bora zaidi',
                'price' => 1000,
                'duration_minutes' => 720,
                'upload_speed' => '2M',
                'download_speed' => '5M',
                'mikrotik_profile' => '2M_5M',
                'sort_order' => 2,
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }

        // Create a test router (disabled by default, configure your own)
        Router::create([
            'name' => 'Main Office Router',
            'ip_address' => '192.168.88.1',
            'api_port' => 8728,
            'username' => 'admin',
            'password' => encrypt('change_this_password'),
            'location' => 'Main Office',
            'is_active' => false,
            'connection_status' => 'unknown',
            'notes' => 'Configure with actual router credentials before enabling',
        ]);

        echo "Database seeded successfully!\n";
        echo "Admin login: admin@bukutu.co.tz / password\n";
    }
}
