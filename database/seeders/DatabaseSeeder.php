<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin@videyview.com',
        ], [
            'name' => 'Admin Bot',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        \App\Models\Video::create([
            'title' => 'Sample Free Video',
            'slug' => 'sample-free-video',
            'url' => 'https://cdn.videy.co/wwuXRjr61.mp4',
            'is_premium' => false,
            'is_free_to_all' => true,
        ]);

        \App\Models\Video::create([
            'title' => 'Sample Premium Video',
            'slug' => 'sample-premium-video',
            'url' => 'https://cdn.videy.co/wwuXRjr61.mp4',
            'is_premium' => true,
        ]);
    }
}
