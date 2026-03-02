<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Models\User;

use App\Http\Controllers\Controller;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/permission/search',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $user_id = request('user_id');
            $role_id = request('role_id');
            $params = [
                'user_id' => $user_id,
                'role_id' => $role_id
            ];
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            if($user->es_super_admin()){
                if($user_id != null){
                    $u = User::with('permissions', 'roles')->find($user_id);
                    $permissions_direct = $u->getDirectPermissions();
                    $permissions_via_roles = $u->getPermissionsViaRoles();
                    $all_permissions = $u->getAllPermissions();
                    $system_permissions = Permission::get();
                    $data = [
                        'permisos_directos' => $permissions_direct,
                        'permisos_del_rol' => $permissions_via_roles,
                        'todos_los_permisos' => $all_permissions,
                        'permisos_del_sistema' => $system_permissions,
                        'permisos_disponibles' => $system_permissions
                    ];
                    $count = sizeof($data);
                    if($count > 0){
                        $message = 'El usuario tiene rol '.$u->getRoleNames().'. Estos son todos los permisos del usuario: ';
                        array_push($errors, 'No se produjeron errores');
                        $status = 'ok';
                    }else{
                        $message = 'El usuario no tiene permisos especiales asignados';
                        array_push($errors, 'No se produjeron errores');
                        $status = 'empty';
                    }
                }else if($role_id != null){
                    $r = Role::with('permissions')->find($role_id);
                    if(!empty($r)){
                        // $p = Permission::select('name', 'id')
                        //     ->from('permissions')
                        //     ->join('role_has_permissions', 'role_has_permissions.permission_id', 'permissions.id')
                        //     ->where('role_has_permissions.role_id', '=', $r->id)
                        //     ->get();
                        // $data = $p;
                        $system_permissions = Permission::get();
                        $role_permissions = $r->permissions;
                        $data = [
                            'permisos_del_rol' => $role_permissions,
                            'permisos_del_sistema' => $system_permissions,
                            'permisos_disponibles' => $system_permissions
                        ];
                        $count = sizeof($data);
                        if($count > 0){
                            $message = 'Estos son los permisos del rol: '.$r->name;
                            array_push($errors, 'No se produjeron errores');
                            $status = 'ok';
                        }else{
                            $message = 'El rol no tiene permisos asignados';
                            array_push($errors, 'No se produjeron errores');
                            $status = 'empty';
                        }
                    }else{
                        $count = 0;
                        $message = 'El rol no existe en la base de datos';
                        array_push($errors, 'Rol no encontrado');
                        $status = 'fail';
                    }
                }else{
                    $data = Permission::get();
                    $count = sizeof($data);
                    if($count > 0){
                        $message = 'Estos son los permisos disponibles en el sistema ';
                        array_push($errors, 'No se produjeron errores');
                        $status = 'ok';
                    }else{
                        $message = 'No se encontraron permisos.';
                        array_push($errors, 'No se produjeron errores');
                        $status = 'empty';
                    }
                }
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => null,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/permission/store',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $permission_name = request('permission_name');
            $params = [
                'permission_name' => $permission_name
            ];

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden crear permisos
            if($user->es_super_admin()){
                $permissions = Permission::get()->pluck('name')->toArray();
                // return $permissions; // in_array($permission_name, $permissions);
                if(in_array($permission_name, $permissions)){
                    $message = 'No se pudo crear el permiso ' . $permission_name . '. El permiso ya existe.';
                    $status = 'failed';
                    $data = [];
                    $count = 0;
                    array_push($errors, 'El permiso ya existe.');
                }else{
                    $data = Permission::create(['guard_name' => 'api', 'name' => $permission_name]);
                    // asignamos el permiso recién creado al rol super administrador
                    $role = Role::where('name', 'super administrador')->first();
                    $role->givePermissionTo($data);
                    if(!empty($data) && !empty($data->id) && $data->id > 1){
                        $count = 1;
                    }
                    if($count > 0){
                        $message = 'El permiso ' . $data->name . ' ha sido creado con éxito';
                        array_push($errors, 'No se produjeron errores');
                        $status = 'ok';
                    }else{
                        $message = 'El permiso ' . $permission_name . ' no ha podido ser creado';
                        array_push($errors, 'El permiso no pudo crearse. Error no especificado.');
                        $status = 'fail';
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($user->roles[0]->name);
                $count  = 0;
                $data = null;
                array_push($errors, 'El usuario no está autorizado para acceder a esta ruta');
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => null,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Error de backend');
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'data' => null,
                'code' => -1,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/permission/update',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $permission_name = request('permission_name');
            $permission_id = request('permission_id');
            $params = [
                'permission_id' => $permission_id,
                'permission_name' => $permission_name
            ];

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden actualizar permisos
            if($user->es_super_admin()){
                if(empty($permission_id)){
                    $count = 0;
                    $message = 'El parámetro permission_id es requerido.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos');
                    $status = 'fail';
                }else if(empty($permission_name)){
                    $count = 0;
                    $message = 'El parámetro permission_name es requerido.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos');
                    $status = 'fail';
                }else{
                    $permission = Permission::find($permission_id);
                    if(!empty($permission)){
                        $permission_old_name = $permission->name;
                        $data = $permission->update(['name' => $permission_name]);
                        if($data > 0){
                            $count = 1;
                            $message = 'El permiso ' . $permission_old_name . ' ha sido actualizado a ' . $permission_name .'.';
                            $errors = [];
                            $status = 'ok';
                        }else{
                            $message = 'El permiso ' . $permission->name . 'no ha podido ser actualizado';
                            array_push($errors, 'Error al actualizar el permiso. Error no especificado.');
                            $status = 'fail';
                        }
                    }else{
                        $count = 0;
                        $message = 'El permiso no existe en la base de datos.';
                        array_push($errors, 'No se pudo actualizar el permiso con id ' . $permission_id . '. Permiso no encontrado');
                        $status = 'fail';
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($user->roles[0]->name);
                $count  = 0;
                $data = null;
                array_push($errors, 'El usuario no está autorizado para acceder a esta ruta');
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => null,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Error de backend');
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/permission/destroy',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $permission_id = request('permission_id');
            $permission_name = request('permission_name');
            $params = [
                'permission_id' => $permission_id,
                'permission_name' => $permission_name
            ];

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden eliminar permisos
            if($user->es_super_admin()){
                // comprobar que el parámetro permission_id o permission_name se haya pasado
                if(empty($permission_name) && empty($permission_id)){
                    $count = 0;
                    $message = 'Debe proporcionarse permission_name o permission_id.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos');
                    $status = 'fail';
                }else{
                    if(empty($permission_id)){
                        $permission = Permission::where('name', '=', $permission_name)->first();
                    }else{
                        $permission = Permission::find($permission_id);
                    }
                    if(!empty($permission)){
                        $permission_old_name = $permission->name;
                        $data = $permission->delete();
                        if($data > 0){
                            $count = 1;
                            $message = 'El permiso ' . $permission_old_name . ' ha sido eliminado de la base de datos.';
                            $status = 'ok';
                        }else{
                            $message = 'El permiso ' . $permission->name . 'no ha podido ser eliminado';
                            array_push($errors, 'Error al eliminar el permiso. Error no especificado.');
                            $status = 'fail';
                        }
                    }else{
                        $count = 0;
                        $message = 'No se pudo eliminar el permiso. Permiso no encontrado';
                        array_push($errors, 'El permiso no existe en la base de datos.');
                        $status = 'fail';
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($user->roles[0]->name);
                $count  = 0;
                $data = null;
                array_push($errors, 'El usuario no está autorizado para acceder a esta ruta');
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => null,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Error de backend');
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' =>  $extras
            ]);
        }
    }

    /**
     * consulta a que roles y/o usuarios a sido asignado el permiso y devuelve un array por cada uno
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function consultar_asignacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/permission/consultar-asignacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $permission_id = request('permission_id');
            $permission_name = request('permission_name');
            $params = [
                'permission_id' => $permission_id,
                'permission_name' => $permission_name
            ];

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden eliminar permisos
            if($user->es_super_admin()){
                // comprobar que el parámetro permission_id o permission_name se haya pasado
                if(empty($permission_name) && empty($permission_id)){
                    $count = 0;
                    $message = 'Debe proporcionarse permission_name o permission_id.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos');
                    $status = 'fail';
                }else{
                    if(empty($permission_id)){
                        $permiso = Permission::where('name', '=', $permission_name)->first();
                        $roles = $permiso->getRoleNames();
                        // $roles = Role::permission($permiso->name)->get();
                        $users = User::permission($permiso->name)->get();
                        $data = [
                            'roles' => $roles,
                            'usuarios' => $users
                        ];
                        $status = 'ok';
                        $message = 'Roles y Usuarios obtenidos';
                        $count  = 0;
                    }else{
                        $permiso = Permission::find($permission_id);
                        $roles = $permiso->getRoleNames();
                        // $roles = Role::permission($permiso->name)->get();
                        $users = User::permission($permiso->name)->get();
                        $data = [
                            'roles' => $roles,
                            'usuarios' => $users
                        ];
                        $status = 'ok';
                        $message = 'Roles y Usuarios obtenidos';
                        $count  = 0;
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($user->roles[0]->name);
                $count  = 0;
                $data = null;
                array_push($errors, 'El usuario no está autorizado para acceder a esta ruta');
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => null,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]); 
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' =>  $extras
            ]);
        }
    }
}
