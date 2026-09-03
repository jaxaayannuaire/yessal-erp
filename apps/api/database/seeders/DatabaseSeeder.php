<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            EntitlementSeeder::class,
            RbacSeeder::class,
            CaisseDemoSeeder::class,
        ]);
    }
}
