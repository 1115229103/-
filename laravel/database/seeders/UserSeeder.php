<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Membership;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@aistory.dev'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin123456'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Assign free plan to admin if not already a member
        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan && !Membership::where('user_id', $admin->id)->exists()) {
            Membership::create([
                'user_id' => $admin->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'starts_at' => now(),
            ]);
        }

        // Demo user
        $demo = User::firstOrCreate(
            ['email' => 'demo@aistory.dev'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('Demo123456'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );

        if ($freePlan && !Membership::where('user_id', $demo->id)->exists()) {
            Membership::create([
                'user_id' => $demo->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'starts_at' => now(),
            ]);
        }
    }
}
