<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name' => 'Admin User',
            'email' => 'erp@college.com',
            'password' => Hash::make('erpadmin123'),
            'role' => 'admin',
        ]);
    }
}