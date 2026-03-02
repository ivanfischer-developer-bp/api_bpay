<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;

class add_usuario_on_users_table_basa extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::find(1);
        $user->usuario = 'ivan.fischer';
        $user->save();
    }
}
