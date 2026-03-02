<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use App\Models\User;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Obtiene el usuario logueado
     * @param $id el id del usuario de la Request
     * @return $logged_user un array de parámetros
     */
    public function get_logged_user($user){
        $roles_logged_user = [];
        foreach($user->roles as $r){
            $roles_logged_user['id'] = $r->id;
            $roles_logged_user['name'] = $r->name;
        }
        $permissions_logged_user = [];
        $can = [];
        foreach($user->getPermissions() as $per){
            $permiso['id'] = $per->id;
            $permiso['name'] = $per->name;
            array_push($permissions_logged_user, $permiso);
            array_push($can, $per->name);
        }
        // ordenamos los permisos alfabeticamente
        sort($can);
        $logged_user = [
            'id' => $user->id,
            'id_usuario_sqlserver' => $user->id_usuario_sqlserver,
            'name' => $user->name,
            'email' => $user->email,
            'usuario' => $user->usuario,
            'connected' => $user->connected,
            'id_sesion_activa' => $user->id_sesion_activa,
            'id_prestador' => $user->id_prestador,
            'company_name' => $user->company_name,
            'tipo_doc' => $user->tipo_doc,
            'nro_doc' => $user->nro_doc,
            'perfil_completo' => $user->perfil_completo,
            'apellido' => $user->apellido,
            'nombre' => $user->nombre,
            'roles' => $roles_logged_user,
            'permisos' => $permissions_logged_user,
            'can' => $can
        ];
        return $logged_user;
    }

    /**
     * Obtiene el usuario logueado
     * @param $id el id del usuario de la Request
     * @return $logged_user un array de parámetros
     */
    public function get_external_logged_user($user){
        $roles_logged_user = [];
        foreach($user->roles as $r){
            $roles_logged_user['id'] = $r->id;
            $roles_logged_user['name'] = $r->name;
        }
        $permissions_logged_user = [];
        $can = [];
        foreach($user->getPermissions() as $per){
            $permiso['id'] = $per->id;
            $permiso['name'] = $per->name;
            array_push($permissions_logged_user, $permiso);
            array_push($can, $per->name);
        }
        // ordenamos los permisos alfabeticamente
        sort($can);
        $logged_user = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'usuario' => $user->usuario,
            'id_prestador' => $user->id_prestador,
            'company_name' => $user->company_name,
            'tipo_doc' => $user->tipo_doc,
            'nro_doc' => $user->nro_doc,
            'roles' => $roles_logged_user,
            'permisos' => $permissions_logged_user,
            'can' => $can
        ];
        return $logged_user;
    }
}
