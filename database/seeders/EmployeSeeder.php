<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Employé Test',
            'email' => 'employe@example.com',
            'password' => bcrypt('password'),
            'role' => 'employe',
        ]);
    }
}
