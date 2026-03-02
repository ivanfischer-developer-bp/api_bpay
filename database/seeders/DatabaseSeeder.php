<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PermissionSeeder::class); // siembra roles, permisos y usuarios con roles asignados
        $this->call(add_usuario_on_users_table::class);
    }
}
