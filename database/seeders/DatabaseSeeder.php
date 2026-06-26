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
                'name' => '1 Hour',
                'description' => 'Perfect for quick browsing and messaging',
                'price' => 1000,
                'duration_minutes' => 60,
                'upload_speed' => '2M',
                'download_speed' => '5M',
                'mikrotik_profile' => '2M_5M',
                'sort_order' => 1,
            ],
            [
                'name' => '2 Hours',
                'description' => 'Great for a short work session',
                'price' => 2000,
                'duration_minutes' => 120,
                'upload_speed' => '2M',
                'download_speed' => '5M',
                'mikrotik_profile' => '2M_5M',
                'sort_order' => 2,
            ],
            [
                'name' => 'Daily Pass',
                'description' => 'Full day of unlimited browsing',
                'price' => 5000,
                'duration_minutes' => 1440,
                'upload_speed' => '5M',
                'download_speed' => '10M',
                'mikrotik_profile' => '5M_10M',
                'sort_order' => 3,
            ],
            [
                'name' => '3 Days',
                'description' => 'Weekend special - stay connected',
                'price' => 12000,
                'duration_minutes' => 4320,
                'upload_speed' => '5M',
                'download_speed' => '10M',
                'mikrotik_profile' => '5M_10M',
                'sort_order' => 4,
            ],
            [
                'name' => 'Weekly',
                'description' => 'Best value for regular users',
                'price' => 25000,
                'duration_minutes' => 10080,
                'upload_speed' => '10M',
                'download_speed' => '20M',
                'mikrotik_profile' => '10M_20M',
                'sort_order' => 5,
            ],
            [
                'name' => 'Monthly',
                'description' => 'Ultimate package for heavy users',
                'price' => 80000,
                'duration_minutes' => 43800,
                'upload_speed' => '10M',
                'download_speed' => '20M',
                'mikrotik_profile' => '10M_20M',
                'sort_order' => 6,
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
