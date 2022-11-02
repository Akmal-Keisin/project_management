<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // User::factory(10)->create();
        Role::insert([
            [
                'name' => 'Project Manager'
            ],
            [
                'name' => 'Leader'
            ],
            [
                'name' => 'Member'
            ]
        ]);
    }
}
