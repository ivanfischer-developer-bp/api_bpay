<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;

class add_usuario_on_users_table extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::get();
        $ambiente = config('site.ambiente');
        foreach($users as $user){
            switch ($ambiente){
                case 'casa asistencial':
                    $user->usuario = $user->email;
                    $user->save();
                    break;
                case 'local':
                    $user->usuario = $user->email;
                    $user->save();
                    break;
                case 'staging':
                    $user->usuario = $user->email;
                    $user->save();
                    break;
            }
        }
    }
}
