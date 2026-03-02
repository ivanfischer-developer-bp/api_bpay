<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Models\User;

use App\Http\Controllers\Controller;

class RoleController extends Controller
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
            'url' => '/admin/role/search',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $user_id = request('user_id');
            $params = [
                'user_id' => $user_id
            ];
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            if($user->es_super_admin()){
                if($user_id != null){
                    $u = User::with('roles')->find($user_id);
                    $data = $u->roles->pluck('name', 'id');
                    $count = sizeof($data);
                    if($count > 0){
                        $message = 'Estos son los roles del usuario: ';
                        $status = 'ok';
                    }else{
                        $message = 'El usuario no tiene roles asignados';
                        $status = 'empty';
                    }
                }else{
                    $data = Role::with('permissions')->orderBy('name', 'asc')->get();
                    $count = sizeof($data);
                    if($count > 0){
                        $message = 'Estos son los roles disponibles en el sistema ';
                        $status = 'ok';
                    }else{
                        $message = 'No se encontraron roles.';
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
            'url' => '/admin/role/store',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $role_name = request('role_name');
            $params = [
                'role_name' => $role_name
            ];

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden crear roles
            if($user->es_super_admin()){
                $roles = Role::get()->pluck('name')->toArray();
                // return $roles; // in_array($role_name, $roles);
                if(in_array($role_name, $roles)){
                    $message = 'No se pudo crear el rol ' . $role_name . '. El rol ya existe.';
                    $status = 'failed';
                    $data = [];
                    $count = 0;
                    array_push($errors, 'El rol ya existe.');
                }else{
                    $data = Role::create(['guard_name' => 'api', 'name' => $role_name]);
                    
                    if(!empty($data) && !empty($data->id) && $data->id > 1){
                        $count = 1;
                    }
                    if($count > 0){
                        $message = 'El rol ' . strtoupper($role_name) . ' ha sido creado con éxito.';
                        $status = 'ok';
                        // asignar permiso para cambiar password al rol
                        $role = Role::where('name', 'like', $role_name)->first();
                        $permission = Permission::where('name', 'like', 'cambiar password')->get();
                        $response = $role->givePermissionTo($permission);
                        if(!empty($response) && sizeof($response->permissions) > 0){
                            $count = sizeof($response->permissions);
                            $message = $message . ' Al rol ' . strtoupper($role_name) . ' se le ha asignado el permiso CAMBIAR PASSWORD';
                            $status = 'ok';
                            $data = $permission;
                        }
                    }else{
                        $message = 'El rol ' . strtoupper($role_name) . ' no ha podido ser creado';
                        array_push($errors, 'El rol no pudo ser creado. Error no especificado');
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
            'url' => '/admin/role/update',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $role_name = request('role_name');
            $role_id = request('role_id');
            $params = [
                'role_name' => $role_name,
                "role_id" => $role_id
            ];

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden actualizar roles
            if($user->es_super_admin()){
                $role = Role::find($role_id);
                if(!empty($role)){
                    $role_old_name = $role->name;
                    $response = $role->update(['name' => $role_name]);
                    if($response > 0){
                        $count = 1;
                        $message = 'El rol ' . $role_old_name . ' ha sido actualizado a ' . $role_name .'.';
                        $status = 'ok';
                        $data = Role::find($role_id);
                    }else{
                        $message = 'El rol ' . $role->name . 'no ha podido ser actualizado';
                        array_push($errors, 'Error al actualizar el rol. Error no especificado.');
                        $status = 'fail';
                        $data = null;
                        $count = 0;
                    }
                }else{
                    $count = 0;
                    $data = null;
                    $message = 'No se pudo actualizar el rol con id ' . $role_id . '. Rol no encontrado';
                    array_push($errors, 'El rol no existe en la base de datos.');
                    $status = 'fail';
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
                'code' => -1,
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
            'url' => '/admin/role/destroy',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden eliminar roles
            if($user->es_super_admin()){
                $role_id = request('role_id');
                $params = [
                    'role_id' => $role_id
                ];
                if($role_id == null){
                    $count = 0;
                    $message = 'El parámetro role_id es obligatorio.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos.');
                    $status = 'fail';
                }else{
                    $role = Role::find($role_id);
                    if(!empty($role)){
                        $role_old_name = $role->name;
                        $data = $role->delete();
                        if($data > 0){
                            $count = 1;
                            $message = 'El rol ' . $role_old_name . ' ha sido eliminado de la base de datos.';
                            $status = 'ok';
                        }else{
                            $message = 'El rol ' . $role->name . 'no ha podido ser eliminado';
                            array_push($errors, 'Error al eliminar el rol. Error no especificado.');
                            $status = 'fail';
                        }
                    }else{
                        $count = 0;
                        $message = 'No se pudo eliminar el rol con id ' . $role_id . '. Rol no encontrado';
                        array_push($errors, 'El rol no existe en la base de datos.');
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
     * Assigns the permissions to the specified role
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function asignar_permiso(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/role/permission/asign',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $permission_name = request('permission_name');
            $permission_id = request('permission_id');
            $role_id = request('role_id');
            $params = [
                'permission_name' => $permission_name,
                'permission_id' => $permission_id,
                'role_id' => $role_id
            ];

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden crear roles
            if($user->es_super_admin()){
                if($role_id == null){
                    $count = 0;
                    $message = 'El parámetro role_id es obligatorio.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos.');
                    $status = 'fail';
                }else{
                    $role = Role::find($role_id);
                    if(!empty($role)){
                        if($permission_name != null){
                            $permission = Permission::where('name', 'like', $permission_name)->get();
                        }
                        if($permission_id != null){
                            $permission = Permission::where('id', '=', $permission_id)->get();
                        }
                        if($permission_name == null && $permission_id == null){
                            array_push($errors, 'No hay parámetros especificados. Debe definir permission_name o permission_id.');
                            return response()->json([
                                'status' => 'fail',
                                'count' => 0,
                                'errors' => $errors,
                                'message' => 'No se asignaron permisos',
                                'line' => null,
                                'code' => null,
                                'data' => null,
                                'params' => $params,
                                'logged_useruser' => $logged_user,
                                'extras' => $extras
                            ]);
                        }
                        // return $permission;
                        if(!empty($permission) && !empty($permission[0])){
                            $permission = $permission[0];
                            if($role->hasPermissionTo($permission)){
                                $count = 0;
                                $message = 'El rol ' . $role->name . ' ya tiene asignado el permiso: ' . $permission->name;
                                array_push($errors, 'No se asignó el permiso al rol.');
                                $status = 'fail';
                                $data = null;
                            }else{
                                $response = $role->givePermissionTo($permission);
                                // return $response;
                                if(!empty($response) && sizeof($response->permissions) > 0){
                                    $count = sizeof($response->permissions);
                                    $message = 'Al rol ' . $role->name . ' se le han asignado los permisos solicitados';
                                    $status = 'ok';
                                    $data = $permission;
                                }else{
                                    $message = 'No se han podido asignar los permisos solicitados al rol ' . $role->name . '.';
                                    array_push($errors, 'Error al otorgar permisos. Error no especificado.');
                                    $status = 'fail';
                                }
                            }
                        }else{
                            $count = 0;
                            $message = 'Debe crear los permisos primero para luego asignarlos.';
                            $data = null;
                            array_push($errors, 'No existen permisos que coincidan con el parámetro dado.');
                            $status = 'fail';
                        }
                    }else{
                        $count = 0;
                        $message = 'No se asignaron permisos al rol con id ' . $role_id . '. Rol no encontrado';
                        array_push($errors, 'El rol no existe en la base de datos.');
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
     * Assigns the permissions to the specified role
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function quitar_permiso(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/role/permission/revoke',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $permission_name = request('permission_name');
            $permission_id = request('permission_id');
            $role_id = request('role_id');
            $params = [
                'permission_name' => $permission_name,
                'permission_id' => $permission_id,
                'role_id' => $role_id
            ];

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden quitar permisos
            if($user->es_super_admin()){
                if($role_id == null){
                    $count = 0;
                    $message = 'El parámetro role_id es obligatorio.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos.');
                    $status = 'fail';
                }else{
                    $role = Role::find($role_id);
                    if(!empty($role)){
                        if($permission_name != null){
                            $permission = Permission::where('name', 'like', $permission_name)->get();
                        }
                        if($permission_id != null){
                            $permission = Permission::where('id', '=', $permission_id)->get();
                        }
                        if($permission_name == null && $permission_id == null){
                            array_push($errors, 'No hay parámetros especificados.');
                            return response()->json([
                                'status' => 'fail',
                                'count' => 0,
                                'errors' => $errors,
                                'message' => 'No se quitaron permisos. Debe definir permission_name o permission_id.',
                                'line' => null,
                                'code' => null,
                                'data' => null,
                                'params' => $params,
                                'logged_user' => $logged_user,
                                'extras' => $extras
                            ]);
                        }
                        // return $permission;
                        if(!empty($permission) && !empty($permission[0])){
                            $permission = $permission[0];
                            if($role->hasPermissionTo($permission)){
                                $response = $role->revokePermissionTo($permission);
                                if(!empty($response)){
                                    $count = 1;
                                    $message = 'Al rol ' . $role->name . ' se le han quitado los permisos solicitados';
                                    $status = 'ok';
                                    $data = $permission;
                                }else{
                                    $message = 'No se han podido quitar los permisos solicitados al rol ' . $role->name . '.';
                                    array_push($errors, 'Error al quitar permisos. Error no especificado.');
                                    $status = 'fail';
                                }
                            }else{
                                $count = 0;
                                $message = 'Al rol ' . $role->name . ' no tiene asignado el permiso: ' . $permission->name;
                                array_push($errors, 'El rol no tiene asignado el permiso');
                                $status = 'fail';
                                $data = null;
                            }
                        }else{
                            $count = 0;
                            $message = 'Los permisos deben existir y haber sido asignados al rol primeramente para poder quitarse.';
                            $data = null;
                            array_push($errors, 'No existen permisos que coincidan con el parámetro dado.');
                            $status = 'fail';
                        }
                    }else{
                        $count = 0;
                        $message = 'No se quitaron permisos al rol con id ' . $role_id . '. Rol no encontrado';
                        array_push($errors, 'El rol no existe en la base de datos.');
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
                'message' => $message,
                'line' => null,
                'code' => null,
                'errors' => $errors,
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
                'code' => $th->getCode(),
                'line' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Cambia los permisos directos de un rol
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sincronizar_permisos(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/role/permission/synchronize',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $permissions = request('permissions');
            // $revoque_permissions = request('quitar_permisos');
            // $assign_permissions = request('asignar_permisos');
            $role_id = request('role_id');
            $params = [
                // 'revoque_permissions' => $revoque_permissions,
                // 'assign_permissions' => $assign_permissions,
                'permissions' => $permissions,
                'role_id' => $role_id
            ];

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden crear roles
            if($user->es_super_admin()){
                if($role_id == null){
                    $count = 0;
                    $message = 'El parámetro role_id es requerido.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos.');
                    $status = 'fail';
                }else{
                    $r = Role::with('permissions')->find($role_id);
                    if(!empty($r)){
                        $r->syncPermissions($permissions);
                        $count = sizeof($r->permissions);
                        $message = 'Al rol ' . $r->name . ' se le han asignado los permisos solicitados';
                        $status = 'ok';
                        $data = $user;
                    }else{
                        $count = 0;
                        $message = 'El rol no existe en la base de datos.';
                        array_push($errors, 'No se asignaron permisos al rol con id ' . $role_id . '. Rol no encontrado');
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
                'code' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }
    
    /**
     * Consulta que usuarios tienen un rol específico
    * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function consultar_asignacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/role/consultar-asignacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $role_id = request('role_id');
            $role_name = request('role_name');
            $params = [
                'role_id' => $role_id,
                'role_name' => $role_name
            ];

            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            
            // solo usuarios super administradores pueden eliminar permisos
            if($user->es_super_admin()){
                // comprobar que el parámetro role_id o role_name se haya pasado
                if(empty($role_name) && empty($role_id)){
                    $count = 0;
                    $message = 'Debe proporcionarse role_name o role_id.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos');
                    $status = 'fail';
                }else{
                    if(empty($role_id)){
                        $rol = Role::where('name', '=', $role_name)->first();
                        // $roles = Role::permission($rol->name)->get();
                        $users = User::role($rol->name)->get();
                        $data = [
                            'usuarios' => $users
                        ];
                        $status = 'ok';
                        $message = 'Usuarios obtenidos';
                        $count  = sizeof($users);
                    }else{
                        $rol = Role::find($role_id);
                        // $roles = $rol->getRoleNames();
                        // $roles = Role::permission($rol->name)->get();
                        $users = User::role($rol->name)->get();
                        $data = [
                            'usuarios' => $users
                        ];
                        $status = 'ok';
                        $message = 'Usuarios obtenidos';
                        $count  = sizeof($users);
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
