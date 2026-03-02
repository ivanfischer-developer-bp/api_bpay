<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Models\User;
use App\Models\ProfileDoctor;
use App\Models\ProfileSecretary;

use Carbon\Carbon;
use DB;

use App\Http\Controllers\ConexionSpController;

class UserController extends ConexionSpController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/list',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try{
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            // variables de respuesta
            $status = 'empty';
            $message = 'No se encontraron registros.';
            $count = 0;
            $data = [];
            $errors = [];
            $line = null;
            $code = null;
            $params = [
                'condicion' => request('condicion') != null ? request('condicion') : 'solo activos',
                'criterio' => request('criterio') != null ? request('criterio') : null,
                'valor' => request('valor') != null ? request('valor') : null
            ];
            
            // return $params;

            if($user->hasPermissionTo('listar usuarios')){   
                try {
                    // Query builder dinámico para búsqueda de usuarios
                    $query = User::with('roles', 'permissions');
                    
                    // Aplicar filtro de soft deletes según condición
                    if($params['condicion'] == 'todos'){
                        $query->withTrashed();
                    }else if($params['condicion'] == 'borrados'){
                        $query->onlyTrashed();
                    }
                    // Si condicion es 'solo activos', no se aplica nada (comportamiento por defecto)
                    
                    // Filtros dinámicos por columnas de la tabla users
                    if(request('id') != null){
                        $query->where('id', '=', request('id'));
                    }
                    if(request('name') != null){
                        $query->where('name', 'like', '%'.request('name').'%');
                    }
                    if(request('email') != null){
                        $query->where('email', 'like', '%'.request('email').'%');
                    }
                    if(request('usuario') != null){
                        $query->where('usuario', 'like', '%'.request('usuario').'%');
                    }
                    if(request('company_name') != null){
                        $query->where('company_name', 'like', '%'.request('company_name').'%');
                    }
                    if(request('id_prestador') != null){
                        $query->where('id_prestador', '=', request('id_prestador'));
                    }
                    if(request('tipo_doc') != null){
                        $query->where('tipo_doc', '=', request('tipo_doc'));
                    }
                    if(request('nro_doc') != null){
                        $query->where('nro_doc', '=', request('nro_doc'));
                    }
                    if(request('apellido') != null){
                        $query->where('apellido', 'like', '%'.request('apellido').'%');
                    }
                    if(request('nombre') != null){
                        $query->where('nombre', 'like', '%'.request('nombre').'%');
                    }
                    if(request('perfil_completo') != null){
                        $query->where('perfil_completo', '=', request('perfil_completo'));
                    }
                    if(request('connected') != null){
                        $query->where('connected', '=', request('connected'));
                    }
                    if(request('id_usuario_sqlserver') != null){
                        $query->where('id_usuario_sqlserver', '=', request('id_usuario_sqlserver'));
                    }
                    if(request('id_sesion_activa') != null){
                        $query->where('id_sesion_activa', '=', request('id_sesion_activa'));
                    }
                    
                    // Filtro por rol
                    if(request('rol') != null){
                        $query->role(request('rol'));
                    }
                    
                    // Ejecutar query
                    $response = $query->get();
                    
                    // Procesar resultados
                    if(!empty($response) && $response->count() > 0){
                        foreach($response as $resp){
                            $roles = [];
                            foreach($resp->roles as $rol){
                                $roles['id'] = $rol->id;
                                $roles['name'] = $rol->name;
                            }
                            $permisos = [];
                            foreach($resp->getPermissions() as $per){
                                $permiso['id'] = $per->id;
                                $permiso['name'] = $per->name;
                                array_push($permisos, $permiso);
                            }
                            $d = [
                                'id' => $resp->id,
                                'id_usuario_sqlserver' => $resp->id_usuario_sqlserver,
                                'name' => $resp->name,
                                'email' => $resp->email,
                                'usuario' => $resp->usuario,
                                'connected' => $resp->connected,
                                'company_name' => $resp->company_name,
                                'id_prestador' => $resp->id_prestador,
                                'tipo_doc' => $resp->tipo_doc,
                                'nro_doc' => $resp->nro_doc,
                                'apellido' => $resp->apellido,
                                'nombre' => $resp->nombre,
                                'perfil_completo' => $resp->perfil_completo,
                                'id_sesion_activa' => $resp->id_sesion_activa,
                                'deleted_at' => $resp->deleted_at,
                                'created_at' => $resp->created_at,
                                'updated_at' => $resp->updated_at,
                                'roles' => $roles,
                                'permissions' => $permisos
                            ];
                            array_push($data, $d);
                        }
                        $status = 'ok';
                        $message = 'Usuarios encontrados.';
                        $count = sizeof($data);
                    }else{
                        $data = null;
                        $status = 'empty';
                        $message = 'No se encontraron registros';
                        array_push($errors, 'Sin datos');
                        $count = 0;
                    }
                } catch (\Throwable $th) {
                    array_push($errors, 'error en consulta');
                    $message = $th->getMessage();
                    $line = $th->getLine();
                    $code = $th->getCode();
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($user->roles[0]->name).'. Se requiere permiso para LISTAR USUARIOS';
                $params = [];
                $count  = 0;
                $data = null;
                array_push($errors, 'El usuario no tiene Autorización');
            }
            // return $error;
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $line,
                'code' => $code,
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
     * Retorna un listado de usuarios paginado que coincide con los parámetros dados
     * 
     * @return \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_usuarios(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/buscar-usuarios',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try{
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            // variables de respuesta
            $status = 'empty';
            $message = 'No se encontraron registros.';
            $count = 0;
            $data = [
                'paginacion' => [
                    'pagina_actual' => 0,
                    'por_pagina' => 0,
                    'total_coincidencias' => 0,
                    'total_paginas' => 0,
                    'total_usuarios' => 0
                ],
                'registros' => []
            ];
            $errors = [];
            $line = null;
            $code = null;
            $params = [];
            
            // return $params;

            if($user->hasPermissionTo('listar usuarios')){ 
                try {
                    // Configuración de paginación (siempre activa)
                    $porPagina = request('por_pagina') ?? 20; // Registros por página (default 20)
                    $paginaActual = request('pagina_actual') ?? 1; // Página actual (default 1)
                    $params = [
                        'por_pagina' => $porPagina,
                        'pagina_actual' => $paginaActual,
                        'condicion' => request('condicion') != null ? request('condicion') : '',
                    ];
                    
                    // Query builder dinámico para búsqueda de usuarios
                    $query = User::with('roles', 'permissions');
                    
                    // Aplicar filtro de soft deletes según condición
                    if(request('condicion') == 'todos'){
                        $query->withTrashed();
                    }else if(request('condicion') == 'borrados'){
                        $query->onlyTrashed();
                    }
                    // Si condicion es 'solo activos', no se aplica nada (comportamiento por defecto)
                    
                    // Contar total de usuarios según condición (antes de filtros)
                    $queryTotalUsuarios = User::query();
                    if(request('condicion') == 'todos'){
                        $params['condicion'] = 'todos';
                        $queryTotalUsuarios->withTrashed();
                    }else if(request('condicion') == 'borrados'){
                        $params['condicion'] = 'borrados';
                        $queryTotalUsuarios->onlyTrashed();
                    }
                    $totalUsuarios = $queryTotalUsuarios->count();
                    
                    // Filtros dinámicos por columnas de la tabla users
                    if(request('id') != null){
                        $params['id'] = request('id');
                        $query->where('id', '=', request('id'));
                    }
                    if(request('name') != null){
                        $params['name'] = request('name');
                        $query->where('name', 'like', '%'.request('name').'%');
                    }
                    if(request('email') != null){
                        $params['email'] = request('email');
                        $query->where('email', 'like', '%'.request('email').'%');
                    }
                    if(request('usuario') != null){
                        $params['usuario'] = request('usuario');
                        $query->where('usuario', 'like', '%'.request('usuario').'%');
                    }
                    if(request('company_name') != null){
                        $params['company_name'] = request('company_name');
                        $query->where('company_name', 'like', '%'.request('company_name').'%');
                    }
                    if(request('id_prestador') != null){
                        $params['id_prestador'] = request('id_prestador');
                        $query->where('id_prestador', '=', request('id_prestador'));
                    }
                    if(request('tipo_doc') != null){
                        $params['tipo_doc'] = request('tipo_doc');
                        $query->where('tipo_doc', '=', request('tipo_doc'));
                    }
                    if(request('nro_doc') != null){
                        $params['nro_doc'] = request('nro_doc');
                        $query->where('nro_doc', '=', request('nro_doc'));
                    }
                    if(request('apellido') != null){
                        $params['apellido'] = request('apellido');
                        $query->where('apellido', 'like', '%'.request('apellido').'%');
                    }
                    if(request('nombre') != null){
                        $params['nombre'] = request('nombre');
                        $query->where('nombre', 'like', '%'.request('nombre').'%');
                    }
                    if(request('perfil_completo') != null){
                        $params['perfil_completo'] = request('perfil_completo');
                        $query->where('perfil_completo', '=', request('perfil_completo'));
                    }
                    if(request('connected') != null){
                        $params['connected'] = request('connected');
                        $query->where('connected', '=', request('connected'));
                    }
                    if(request('id_usuario_sqlserver') != null){
                        $params['id_usuario_sqlserver'] = request('id_usuario_sqlserver');
                        $query->where('id_usuario_sqlserver', '=', request('id_usuario_sqlserver'));
                    }
                    if(request('id_sesion_activa') != null){
                        $params['id_sesion_activa'] = request('id_sesion_activa');
                        $query->where('id_sesion_activa', '=', request('id_sesion_activa'));
                    }
                    
                    // Filtro por rol único
                    if(request('rol') != null){
                        $params['rol'] = request('rol');
                        $query->role(request('rol'));
                    }

                    // Filtro por múltiples roles (retorna usuarios que tengan AL MENOS uno de los roles)
                    
                    if(request('roles') != null){
                        $params['roles'] = request('roles');
                        $rolesParam = request('roles');
                        // Si viene como array JSON string, decodificarlo
                        if(is_string($rolesParam) && (strpos($rolesParam, '[') === 0 || strpos($rolesParam, '{') === 0)){
                            $rolesParam = json_decode($rolesParam, true);
                        }
                        // Si viene como string separado por comas
                        if(is_string($rolesParam)){
                            $roles = array_map('trim', explode(',', $rolesParam));
                        } else {
                            $roles = $rolesParam;
                        }
                        // si vienen roles, aplicar filtro
                        if(!empty($roles)){
                            $query->whereHas('roles', function($q) use ($roles) {
                                $q->whereIn('name', $roles);
                            });
                        }
                    }else{
                        // Filtrar usuarios sin roles asignados
                        $query->whereDoesntHave('roles');
                    }
                    
                    // Ejecutar query con paginación: limitar resultados directamente en la consulta
                    $response = $query->skip(($paginaActual - 1) * $porPagina)
                                      ->take($porPagina)
                                      ->get();
                    
                    // Hacer consultas separadas para contar totales (sin traer datos)
                    $queryCount = User::query();
                    
                    // Aplicar mismos filtros de soft deletes
                    if(request('condicion') == 'todos'){
                        $queryCount->withTrashed();
                    }else if(request('condicion') == 'borrados'){
                        $queryCount->onlyTrashed();
                    }
                    
                    // Aplicar mismos filtros de búsqueda
                    if(request('id') != null){
                        $queryCount->where('id', '=', request('id'));
                    }
                    if(request('name') != null){
                        $queryCount->where('name', 'like', '%'.request('name').'%');
                    }
                    if(request('email') != null){
                        $queryCount->where('email', 'like', '%'.request('email').'%');
                    }
                    if(request('usuario') != null){
                        $queryCount->where('usuario', 'like', '%'.request('usuario').'%');
                    }
                    if(request('company_name') != null){
                        $queryCount->where('company_name', 'like', '%'.request('company_name').'%');
                    }
                    if(request('id_prestador') != null){
                        $queryCount->where('id_prestador', '=', request('id_prestador'));
                    }
                    if(request('tipo_doc') != null){
                        $queryCount->where('tipo_doc', '=', request('tipo_doc'));
                    }
                    if(request('nro_doc') != null){
                        $queryCount->where('nro_doc', '=', request('nro_doc'));
                    }
                    if(request('apellido') != null){
                        $queryCount->where('apellido', 'like', '%'.request('apellido').'%');
                    }
                    if(request('nombre') != null){
                        $queryCount->where('nombre', 'like', '%'.request('nombre').'%');
                    }
                    if(request('perfil_completo') != null){
                        $queryCount->where('perfil_completo', '=', request('perfil_completo'));
                    }
                    if(request('connected') != null){
                        $queryCount->where('connected', '=', request('connected'));
                    }
                    if(request('id_usuario_sqlserver') != null){
                        $queryCount->where('id_usuario_sqlserver', '=', request('id_usuario_sqlserver'));
                    }
                    if(request('id_sesion_activa') != null){
                        $queryCount->where('id_sesion_activa', '=', request('id_sesion_activa'));
                    }
                    if(request('rol') != null){
                        $queryCount->role(request('rol'));
                    }
                    if(request('roles') != null){
                        $rolesParam = request('roles');
                        if(is_string($rolesParam) && (strpos($rolesParam, '[') === 0 || strpos($rolesParam, '{') === 0)){
                            $rolesParam = json_decode($rolesParam, true);
                        }
                        if(is_string($rolesParam)){
                            $rolesForCount = array_map('trim', explode(',', $rolesParam));
                        } else {
                            $rolesForCount = $rolesParam;
                        }
                        if(!empty($rolesForCount)){
                            $queryCount->whereHas('roles', function($q) use ($rolesForCount) {
                                $q->whereIn('name', $rolesForCount);
                            });
                        }
                    }
                    
                    // Contar total de coincidencias con los filtros aplicados
                    $totalCoincidencias = $queryCount->count();
                    $totalPaginas = ceil($totalCoincidencias / $porPagina);
                    
                    // Procesar resultados
                    if(!empty($response) && $response->count() > 0){
                        // foreach($response as $resp){
                        //     $roles = [];
                        //     foreach($resp->roles as $rol){
                        //         $roles['id'] = $rol->id;
                        //         $roles['name'] = $rol->name;
                        //     }
                        //     $permisos = [];
                        //     foreach($resp->getPermissions() as $per){
                        //         $permiso['id'] = $per->id;
                        //         $permiso['name'] = $per->name;
                        //         array_push($permisos, $permiso);
                        //     }
                        //     $d = [
                        //         'id' => $resp->id,
                        //         'id_usuario_sqlserver' => $resp->id_usuario_sqlserver,
                        //         'name' => $resp->name,
                        //         'email' => $resp->email,
                        //         'usuario' => $resp->usuario,
                        //         'connected' => $resp->connected,
                        //         'company_name' => $resp->company_name,
                        //         'id_prestador' => $resp->id_prestador,
                        //         'tipo_doc' => $resp->tipo_doc,
                        //         'nro_doc' => $resp->nro_doc,
                        //         'apellido' => $resp->apellido,
                        //         'nombre' => $resp->nombre,
                        //         'perfil_completo' => $resp->perfil_completo,
                        //         'id_sesion_activa' => $resp->id_sesion_activa,
                        //         'deleted_at' => $resp->deleted_at,
                        //         'created_at' => $resp->created_at,
                        //         'updated_at' => $resp->updated_at,
                        //         'roles' => $roles,
                        //         'permissions' => $permisos
                        //     ];
                        //     array_push($data['registros'], $d);
                        // }
                        $data['registros'] = $response;
                        $status = 'ok';
                        $message = 'Usuarios encontrados.';
                        $count = sizeof($data['registros']);
                    }else{
                        $data['registros'] = [];
                        $status = 'empty';
                        $message = 'No se encontraron registros';
                        array_push($errors, 'Sin datos');
                        $count = 0;
                    }
                    
                    // Agregar información de paginación a data
                    $data['paginacion'] = [
                        'pagina_actual' => (int)$paginaActual,
                        'por_pagina' => (int)$porPagina,
                        'total_coincidencias' => $totalCoincidencias,
                        'total_paginas' => $totalPaginas,
                        'total_usuarios' => $totalUsuarios
                    ];
                    
                } catch (\Throwable $th) {
                    array_push($errors, 'error en consulta');
                    $message = $th->getMessage();
                    $line = $th->getLine();
                    $code = $th->getCode();
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($user->roles[0]->name).'. Se requiere permiso para LISTAR USUARIOS';
                $params = [];
                $count  = 0;
                $data = null;
                array_push($errors, 'El usuario no tiene Autorización');
            }
            // return $error;
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $line,
                'code' => $code,
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
            'url' => '/admin/user/register',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            // variables de respuesta
            $status = 'fail';
            $message = 'Error';
            $count = 0;
            $data = null;
            $errors = [];
            $line = null;
            $code = null;
            $params = [
                'apellido' => $request->apellido,
                'company_name' => $request->company_name,
                'email' => $request->email,
                'id_prestador' => $request->id_prestador,
                'id_usuario_sqlserver' => $request->id_usuario_sqlserver,
                'name' => $request->name,
                'nombre' => $request->nombre,
                'nro_doc' => $request->nro_doc,
                'password' => $request->password,
                'perfil_completo' => $request->perfil_completo != null ? $request->perfil_completo : false,
                'rol' => $request->rol,
                'tipo_doc' => $request->tipo_doc,
                'usuario' => $request->usuario,
            ]; 
            
            $usuario = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($usuario);

            try {
                if($usuario->hasPermissionTo('registrar usuarios')){ 
                    // $request->validate([
                    //     'email' => 'required|string|email|unique:users',
                    //     'usuario' => 'required|string|unique:users',
                    //     'name' => 'required|string',
                    //     'password' => 'required|string'
                    // ]);
                    
                    $rol = $params['rol'] !== null ? $params['rol'] : null;
                    // $u = new User();
                    // $u->email = $params['email'];
                    // $u->name = $params['name'];
                    // $u->usuario = $params['usuario'];
                    // $u->password = Hash::make($params['password']);
                    $role = $role = Role::find($params['rol'][0]['id']) ?? Role::where('name', 'like', 'basico')->first();
                    $u = [
                        'email' => strtolower($params['email']),
                        'usuario' => strtolower($params['usuario']),
                        'name' => $params['name'],
                        'password' => Hash::make($params['password']),
                    ];
                    if($params['id_prestador'] != null){
                        $u['id_prestador'] = $params['id_prestador'];
                    }
                    if($params['company_name'] != null){
                        $u['company_name'] = $params['company_name'];
                    }
                    if($params['tipo_doc'] != null){
                        if($params['tipo_doc'] == 'DU'){
                            $params['tipo_doc'] = 'DNI';
                        }
                        $u['tipo_doc'] = $params['tipo_doc'];
                    }
                    if($params['nro_doc'] != null){
                        $u['nro_doc'] = $params['nro_doc'];
                    }
                    if($params['apellido'] != null && $params['apellido'] != ''){
                        $u['apellido'] = $params['apellido'];
                    }
                    if($params['nombre'] != null && $params['nombre'] != ''){
                        $u['nombre'] = $params['nombre'];
                    }
                    if($params['id_usuario_sqlserver'] != null && $params['id_usuario_sqlserver'] != ''){
                        $u['id_usuario_sqlserver'] = $params['id_usuario_sqlserver'];
                    }
                    if($params['perfil_completo'] != null){
                        $u['perfil_completo'] = $params['perfil_completo'];
                    }
                    // return ['params' => $params];
                    $user = User::create($u);
                    // return['user'=>$user];
                    if(!empty($user)){
                        $status = 'ok';
                        $message = 'Usuario creado con éxito.';
                        $count  = 1;
                        if($role != null){
                            $new_user = User::find($user->id);
                            // return $u;
                            $new_user->assignRole($role);
                            $message = $message . ' Se asignó el rol ' . $role['name'] . ' al usuario ' . $u['name'];
                            $error = null;
                        }else{
                            $role = Role::where('name', 'like', 'basico')->first();;
                            $new_user = User::find($user->id);
                            $new_user->assignRole($role);
                            $message = $message . ' No se indicó un rol válido. Se asignó el rol BÁSICO al usuario ' . $u['name'];
                            $error = "Rol no encontrado, se asignó rol BÁSICO por omisión.";
                        }
                        $data = [$new_user];
                    }
                }else{
                    $status = 'unauthorized';
                    $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($usuario->roles[0]->name).'. Se requiere permiso para REGISTRAR USUARIOS';
                    $params = [];
                    $count  = 0;
                    $data = null;
                    $error = null;
                }
            } catch (\Throwable $th) {
                $status = 'fail';
                array_push($errors, 'Fallo el proceso de registro');
                $count = $count;
                $data = $data;
                $message = $th->getMessage();
                $line = $th->getLine();
                $code = $th->getCode();
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $line,
                'code' => $code,
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
            'url' => '/admin/user/update',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => []
        ];
        try{
            $user_logueado = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user_logueado);
            // variables de respuesta
            $status = 'fail';
            $message = 'Error. No se encontraron registros.';
            $count = 0;
            $data = [];
            $code = 0;
            $errors = [];
            $params = [];
            $seguir = true;
            $nro = 0;

            $user_id = request('user_id');
            $id_prestador = request('id_prestador');
            $name = request('name');
            $email = request('email');
            $usuario = request('usuario');
            $id_usuario_sqlserver = request('id_usuario_sqlserver');
            $company_name = request('company_name');
            $role_name = request('role_name');
            $tipo_doc = request('tipo_doc');
            $nro_doc = request('nro_doc');
            $apellido = request('apellido');
            $nombre = request('nombre');
            $perfil_completo = request('perfil_completo');

            $params = [
                'user_id' => $user_id,
                'id_prestador' => $id_prestador,
                'id_usuario_sqlserver' => $id_usuario_sqlserver,
                'name' => $name,
                'email' => $email,
                'usuario' => $usuario,
                'company_name' => $company_name,
                'role_name' => $role_name,
                'tipo_doc' => $tipo_doc,
                'nro_doc' => $nro_doc,
                'apellido' => $apellido,
                'nombre' => $nombre,
                'perfil_coompleto' => $perfil_completo != null ? $perfil_completo : false
            ];

            if($user_logueado->hasPermissionTo('actualizar usuarios')){  
                try {
                    if($user_id == null){
                        $message = 'El parámetro user_id es requerido.';
                        array_push($errors, 'Parámetros insuficientes o incorrectos.');
                        $status = 'fail';
                    }else{
                        $user = User::with('roles')->find($user_id);
                        // si viene name se lo asignamos al usuario
                        if($name !== null){
                            $user->name = $name;
                        }
                        // si viene email se lo asignamos al usuario
                        if($email !== null){
                            $user->email = strtolower($email);
                        }
                        // si viene usuario se lo asignamos al usuario
                        if($usuario !== null){
                            $user->usuario = strtolower($usuario);
                        }
                        // si viene apellido se lo asignamos al usuario
                        if($apellido !== null){
                            $user->apellido = $apellido;
                        }
                        // si viene nombre se lo asignamos al usuario
                        if($nombre !== null){
                            $user->nombre = $nombre;
                        }
                        // si el usuario es prestador nos fijamos si viene id_prestador y se lo asignamos al usuario
                        if($user->hasRole('prestador')){
                            if($id_prestador !== null){
                                $user->id_prestador = $id_prestador;
                            }
                            // si tiene company_name se lo asignamos
                            if($company_name != null){
                                $user->company_name = $company_name;
                            }
                        }
                        // si el usuario es afiliado nos fijamos si viene tipo_doc y nro_doc y se lo asignamos al usuario
                        if($user->hasRole('afiliado')){
                            if($tipo_doc !== null){
                                if($tipo_doc == 'DU'){
                                    $tipo_doc = 'DNI';
                                }
                                $user->tipo_doc = $tipo_doc;
                            }
                            // si tiene nro_doc se lo asignamos
                            if($nro_doc != null){
                                $user->nro_doc = $nro_doc;
                            }
                        }
                        // si el nuevo rol es prestador validamos el id_prestador
                        if(request('role_name') == 'prestador'){
                            if($id_prestador !== null){
                                $user->id_prestador = $id_prestador;
                            }else{
                                $seguir = false;
                                $nro = 1;
                                $code = -8;
                                $message = 'Cuando el rol es prestador el parámetro id_prestador no puede estar vacío.';
                            }
                            // si tiene company_name se lo asignamos
                            if($company_name != null){
                                $user->company_name = $company_name;
                            }
                        }
                        $user->id_prestador = $id_prestador;
                        $user->id_usuario_sqlserver = $id_usuario_sqlserver;
                        $user->company_name = $company_name;
                        // si el nuevo rol es afiliado validamos el tipo_doc y el nro_doc
                        if(request('role_name') == 'afiliado'){
                            if($tipo_doc !== null){
                                $user->tipo_doc = $tipo_doc;
                            }else{
                                $seguir = false;
                                $nro = 2;
                                $code = -7;
                                $message = 'Cuando el rol es afiliado el parámetro tipo_doc no puede estar vacío.';
                            }
                            if($nro_doc !== null){
                                $user->nro_doc = $nro_doc;
                            }else{
                                $seguir = false;
                                $nro = 3;
                                $code = -6;
                                $message = 'Cuando el rol es afiliado el parámetro nro_doc no puede estar vacío.';
                            }
                        }
                        if($tipo_doc == 'DU'){
                            $tipo_doc = 'DNI';
                        }
                        $user->tipo_doc = $tipo_doc;
                        $user->nro_doc = $nro_doc;
                        if($seguir){
                            $u = $user->save();
                            $u_old_role = User::with('roles', 'permissions')->find(request('user_id'));
                            $old_role_name = isset($u_old_role->roles) && sizeof($u_old_role->roles) > 0 ? $u_old_role->roles[0]->name : null;
                            if($old_role_name != null){
                                $resp_u = $u_old_role->removeRole($old_role_name);
                            }
                            if($u_old_role->id == 1 || $u_old_role->apellido == 'Fischer'){
                                // evitar que se le quite el rol de admin al usuario con id 1
                                $role_name = 'super administrador';
                            }
                            $user_updated_rol = $u_old_role->assignRole($role_name);
                            if($u && $user_updated_rol){
                                $status = 'ok';
                                $message = 'Usuario actualizado.';
                                $data = [$user_updated_rol];
                                $count = sizeof($data);
                            }else{
                                $status = 'fail';
                                $message = 'Usuario NO actualizado.';
                                $data = [
                                    'usuario' => $u,
                                    'rol' => $user_updated_rol
                                ];
                                $code = -5;
                                $count = 0;
                                array_push($errors, 'Error en la actualización del usuario');
                            }
                        }else{
                            $status = 'fail';
                            $code = -4;
                            $data = null;
                            $count = 0;
                            array_push($errors, 'Error de parámetros '.$nro);
                        }
                    }
                } catch (\Throwable $th) {
                    $status = 'fail';
                    $message = 'No se pudo realizar la petición.';
                    $count = 0;
                    $code = -3;
                    $data = null;
                    array_push($errors, 'Line: '.$th->getLine().' - '.$th->getMessage());
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($usuario->roles[0]->name).'. Se requiere permiso para ACTUALIZAR USUARIOS';
                $params = [];
                $count  = 0;
                $code = -2;
                $data = null;
                array_push($errors, 'El usuario no tiene permiso');
            }
            // return $error;
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
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
                'code' => -1,
                'line' => $th->getLine(),
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
    public function delete(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/delete',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try{
            $usuario = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($usuario);

            // variables de respuesta
            $status = 'fail';
            $message = 'Error. No se encontraron registros.';
            $count = 0;
            $data = null;
            $params = [];
            $errors = [];
            $seguir = true;
            if($usuario->hasPermissionTo('eliminar usuarios')){   
                try {
                    $user_id = request('user_id');
                    $params = [
                        'user_id' => $user_id
                    ];
                    if($user_id == null){
                        $message = 'El parámetro user_id es requerido.';
                        array_push($errors, 'Parámetros insuficientes o incorrectos.');
                        $status = 'fail';
                    }else{
                        $user = User::find($user_id);
                        if(empty($user)){
                            $message = 'El usuario con id '. $user_id .' no existe en la base de datos';
                            array_push($errors, 'Usuario no encontrado.');
                            $status = 'fail';
                        }else{
                            if($user->id == 1 || $user->apellido == 'Fischer'){
                                // evitar que se elimine el usuario con id 1
                                $status = 'fail';
                                $message = 'No se puede eliminar este usuario. Operación no permitida.';
                                array_push($errors, 'Operación no permitida.');
                                $seguir = false;
                            }else{
                                $user->delete();
                                // return $user;
                                if($user){
                                    $status = 'ok';
                                    $message = 'Usuario eliminado';
                                    $count = 1;
                                    $data = $user;
                                    $error = "No se produjeron errores.";
                                }
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    $status = 'fail';
                    $message = 'Error. Operación no realizada.';
                    $count = 0;
                    $data = null;
                    $error = $th->getMessage();
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($usuario->roles[0]->name).' Se requiere permiso para ELIMINAR USUARIOS';
                array_push($errors, 'Los usuarios con rol '.$usuario->roles[0]->name.' no pueden eliminar a otros usuarios.');
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => null,
                'params' => $params,
                'data' => $data,
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
     * Vuelve a poner activos a los usuarios colocando en null el valor deleted_at
     */
    public function restore(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/restore',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try{
            $usuario = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($usuario);

            // variables de respuesta
            $status = 'fail';
            $message = 'Error. No se encontraron registros.';
            $count = 0;
            $data = null;
            $params = [];
            $errors = [];
            $line = null;
            $code = null;
            $seguir = true;
            if($usuario->hasPermissionTo('restaurar usuarios')){   
                try {
                    $user_id = request('user_id');
                    $params = [
                        'user_id' => $user_id
                    ];
                    if($user_id == null){
                        $message = 'El parámetro user_id es requerido.';
                        array_push($errors, 'Parámetros insuficientes o incorrectos.');
                        $status = 'fail';
                    }else{
                        $user = User::onlyTrashed()->where('id', $user_id)->get();
                        if(empty($user)){
                            $message = 'El usuario con id '. $user_id .' no existe en la base de datos';
                            array_push($errors, 'Usuario no encontrado.');
                            $status = 'fail';
                        }else{
                            $user = $user[0];
                            $user->restore();
                            // return $user;
                            if($user){
                                $status = 'ok';
                                $message = 'Usuario restaurado';
                                $count = 1;
                                $data = $user;
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    $status = 'fail';
                    array_push($errors, 'Error. Operación no realizada.');
                    $count = 0;
                    $data = null;
                    $message = $th->getMessage();
                    $line = $th->getLine();
                    $code = $th->getCode();
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($usuario->roles[0]->name).' Se requiere permiso para RESTAURAR USUARIOS';
                array_push($errors, 'Los usuarios con rol '.$usuario->roles[0]->name.' no pueden restaurar a otros usuarios.');
            }
            // return $error;
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $line,
                'code' => $code,
                'params' => $params,
                'data' => $data,
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
     * Elimina permanentemente un usuario de la base de datos
     */
    public function destroy(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/destroy',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try{
            $usuario = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($usuario);

            // variables de respuesta
            $status = 'fail';
            $message = 'Error. No se encontraron registros.';
            $count = 0;
            $data = null;
            $params = [];
            $errors = [];
            $line = null;
            $code = null;
            $seguir = true;
            if($usuario->hasPermissionTo('eliminar usuarios permanentemente')){   
                try {
                    $user_id = request('user_id');
                    $params = [
                        'user_id' => $user_id
                    ];
                    if($user_id == null){
                        $message = 'El parámetro user_id es requerido.';
                        array_push($errors, 'Parámetros insuficientes o incorrectos.');
                        $status = 'fail';
                    }else{
                        $secretary = ProfileSecretary::where('user_id', $user_id)->withTrashed()->first();
                        $doctor = ProfileDoctor::where('user_id', $user_id)->withTrashed()->first();
                        $data = [
                            'doctor' => $doctor,
                            'secretary' => $secretary
                        ];
                        if(!empty($doctor)){
                            if($doctor->trashed()){
                                $affected = $doctor->forceDelete();
                                $data['doctor_deleted'] = $affected;
                            }else{
                                $affected = $doctor->delete();
                                $data['doctor_erased'] = $affected;
                            }
                            if($affected == 1){
                                $user = User::where('id', $user_id)->withTrashed()->first();
                                $data['user'] = $user;
                                if(empty($user)){
                                    $message = 'El usuario con id '. $user_id .' no existe en la base de datos';
                                    array_push($errors, 'Usuario no encontrado.');
                                    $status = 'fail';
                                    $code = -8;
                                }else{
                                    if($user->trashed()){
                                        $affected = $user->forceDelete();
                                        $data['user_deleted'] = $affected;
                                    }else{
                                        $affected = $user->delete();
                                        $data['user_erased'] = $affected;
                                    }
                                    if($affected == 1){
                                        $status = 'ok';
                                        $message = 'Usuario eliminado permanentemente';
                                        $count = 1;
                                        $code = 4;
                                    }else{
                                        $status = 'fail';
                                        $message = 'El usuario no pudo ser elinminado';
                                        $count = 0;
                                        $code = -4;
                                        array_push($errors, 'El usuario no pudo ser elinminado');
                                    }
                                }
                            }
                        }
                        if(!empty($secretary)){
                            if($secretary->trashed()){
                                $affected = $secretary->forceDelete();
                                $data['secretary_deleted'] = $affected;
                            }else{
                                $affected = $secretary->delete();
                                $data['secretary_erased'] = $affected;
                            }
                            if($affected == 1){
                                $user = User::where('id', $user_id)->withTrashed()->first();
                                $data['user'] = $user;
                                if(empty($user)){
                                    $message = 'El usuario con id '. $user_id .' no existe en la base de datos';
                                    array_push($errors, 'Usuario no encontrado.');
                                    $status = 'fail';
                                    $code = -8;
                                }else{
                                    if($user->trashed()){
                                        $affected = $user->forceDelete();
                                        $data['user_deleted'] = $affected;
                                    }else{
                                        $affected = $user->delete();
                                        $data['user_erased'] = $affected;
                                    }
                                    if($affected == 1){
                                        $status = 'ok';
                                        $message = 'Usuario eliminado permanentemente';
                                        $count = 1;
                                        $code = 3;
                                    }else{
                                        $status = 'fail';
                                        $message = 'El usuario no pudo ser elinminado';
                                        $count = 0;
                                        $code = -6;
                                        array_push($errors, 'El usuario no pudo ser elinminado');
                                    }
                                }
                            }else{
                                $status = 'fail';
                                $message = 'El perfil secretaria medico no pudo ser elinminado';
                                $count = 0;
                                $code = -7;
                                array_push($errors, 'El perfil secretaria medico no pudo ser elinminado');
                            }
                        }
                        if(empty($doctor) && empty($secretary)){
                            $user = User::where('id', $user_id)->withTrashed()->first();
                            $data['user'] = $user;
                            if(empty($user)){
                                $message = 'El usuario con id '. $user_id .' no existe en la base de datos';
                                array_push($errors, 'Usuario no encontrado.');
                                $status = 'fail';
                                $code = -8;
                            }else{
                                if($user->trashed()){
                                    $affected = $user->forceDelete();
                                    $data['user_deleted'] = $affected;
                                }else{
                                    $affected = $user->delete();
                                    $data['user_erased'] = $affected;
                                }
                                if($affected == 1){
                                    $status = 'ok';
                                    $message = 'Usuario eliminado permanentemente';
                                    $count = 1;
                                    $code = 2;
                                }else{
                                    $status = 'fail';
                                    $message = 'El usuario no pudo ser elinminado';
                                    $count = 0;
                                    $code = -2;
                                    array_push($errors, 'El usuario no pudo ser elinminado');
                                }
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    $status = 'fail';
                    array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
                    $count = 0;
                    $data = null;
                    $message = $th->getMessage();
                    $line = $th->getLine();
                    $code = -7;
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($usuario->roles[0]->name).' Se requiere permiso para ELIMINAR USUARIOS PERMANENTEMENTE';
                array_push($errors, 'Los usuarios con rol '.$usuario->roles[0]->name.' no pueden eliminar a otros usuarios.');
            }
            // return $error;
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $line,
                'code' => $code,
                'params' => $params,
                'data' => $data,
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
                'code' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        } 
    }

    /**
     * Bleach the key of the specific user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
    public function password_reset(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/password-reset',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        try{
            $usuario = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($usuario);

            $affected = false;
            $actualizar = true;
            $sqlserver_actualizado = false;
            // variables de respuesta
            $status = 'fail';
            $message = 'Error. No se encontraron registros.';
            $count = 0;
            $code = null;
            $data = [
                'usuario' => null,
                'mysql_actualizado' => $affected,
                'sqlserver_actualizado' => $sqlserver_actualizado
            ];
            $errors = [];
            

            $params = request('params');
            // return response()->json(['params' => $params]);
            $password = $params['password'] !== null ? $params['password'] : 'abcd1234';
            $id = $params['id'];
            $id_usuario_sqlserver = $params['id_usuario_sqlserver'];
            $sincronizar = isset($params['sincronizar']) && $params['sincronizar'] != null ? $params['sincronizar'] : true;

            $params = [
                'id' => $id,
                'id_usuario_sqlserver' => $id_usuario_sqlserver,
                'password' => $password,
                'sincronizar' => $sincronizar
            ];

            if($usuario->hasPermissionTo('resetear password')){ 
                if(!isset($id)){
                    $status = 'fail';
                    $message = 'Error. El parámetro id es obligatorio.';
                    $code = -5;
                    $errors =  ['Parámetros insuficientes o incorrectos'];
                }else if(!isset($password)){
                    $status = 'fail';
                    $message = 'Error. El campo password no puede estar vacío.';
                    $code = -6;
                    $errors =  ['Parámetros insuficientes o incorrectos'];
                }else{
                    try {
                        $user = User::find($id);
                        if($sincronizar && $id_usuario_sqlserver != null){
                            //  define los parametros a actualizar en sqlserver
                            $parametros = [
                                'p_id_usuario' => $id_usuario_sqlserver,
                                'p_apynom' => $user->name,
                                'p_n_usuario' => $user->usuario,  
                                'p_email' => $user->email,
                                'p_activo' => 1,
                                'p_id_area' => NULL,
                                'p_supervisor' => NULL,
                                'p_desactivar' => NULL,
                                'p_activar' => NULL,
                                'p_bloqueado' => 0,
                                'p_finaliza_clave' => NULL,
                                'p_clave_nueva' => $params['password'],
                                'p_id_usuario_envia' => $logged_user['id'],
                                'p_atributos' => NULL,
                            ];
                            //  actualiza los datos del usuario
                            array_push($extras['sps'], ['sp_usuario_Update' => $parametros]);
                            array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Update', $parametros));
                            $ret = $this->ejecutar_sp_directo('admin', 'sp_usuario_Update', $parametros);
                            array_push($extras['responses'], ['sp_usuario_Update' => $ret]);
                            // $ret = $this->ejecutar_sp_directo('admin', 'sp_usuario_Update', $parametros);
                            if ( !empty($ret) && !empty($ret[0]) && $ret[0]->filas > 0 ){
                                // si se actualizó correctamente cambia la bandera
                                $sqlserver_actualizado = true;
                            }else{ 
                                // sino modifica la bandera para que no se realice el cambio en la base de datos
                                $actualizar = false;
                            }
                            // si se puede actualizar, ya sea porque se actualizó sqlserver o porque el usuario no está en sqlserver
                            if($actualizar){
                                $user->password = Hash::make($params['password']);
                                $affected = $user->save();
                                array_push($extras['responses'], ['mysql' => $affected]);
                            }
                        }else{
                            $user->password = Hash::make($password);
                            $affected = $user->save();
                            array_push($extras['responses'], ['mysql' => $affected]);
                        }
                        if($affected){
                            $status = 'ok';
                            $message = 'Contraseña reseteada';
                            $count = 1;
                            $code = 1;
                            $data = [
                                'usuario' => $user,
                                'mysql_actualizado' => $affected,
                                'sqlserver_actualizado' => $sqlserver_actualizado
                            ];
                        }else{
                            $status = 'fail';
                            $message = 'No se pudo resetear la contraseña.';
                            $count = 0;
                            $code = -4;
                            $data = [
                                'usuario' => null,
                                'mysql_actualizado' => $affected,
                                'sqlserver_actualizado' => $sqlserver_actualizado
                            ];
                        }
                        
                    } catch (\Throwable $th) {
                        $status = 'fail';
                        $message = 'No se pudo resetear la contraseña';
                        array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
                        $code = -3;
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($usuario->roles[0]->name).' no tiene permiso. Se requiere permiso para RESETEAR PASSWORD';
                $code = -2;
                array_push($errors, 'Intento de acceso no autorizado');
            }
            // return $error;
            $extras['mysql_actualizado'] = $affected;
            $extras['sqlserver_actualizado'] = $sqlserver_actualizado;
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'params' => $params,
                'data' => $data,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'params' => $params,
                'data' => $data,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]);
        } 
    }

    /**
     * Assign a role to a user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function asignar_rol(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/role/assign',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        try{
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $status = null;
            $count = 0;
            $data = null;
            $errors = [];
            $message = null;
            $params = [];

            // solo usuarios super administradores pueden asignar nuevos roles a otros usuarios
            if($user->es_super_admin()){   
                // variables de respuesta
                $role_id = request('role_id');
                $user_id = request('user_id');
                $params = [
                    'role_id' => $role_id,
                    'user_id' => $user_id
                ];
                if($user_id == null){
                    $message = 'El parámetro user_id es requerido.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos.');
                    $status = 'fail';
                }else{
                    $usuario = User::find($user_id);
                    if($role_id != null){
                        $role = Role::find($role_id);
                        if($role != null){
                            $role_name = $role->name;
                            if(!empty($usuario)){
                                if($usuario->hasRole($role_name)){
                                    $status = 'fail';
                                    $message = 'El usuario ' . $usuario->name . ' ya tiene asignado el rol ' . $role_name;
                                    array_push($errors, 'El usuario ya tiene el rol asignado.');
                                }else{
                                    $old_role_name = null;
                                    if(isset($usuario->roles) && sizeof($usuario->roles) > 0 && $usuario->roles[0]->name != null){
                                        $old_role_name = $usuario->roles[0]->name;
                                    }
                                    if($old_role_name != null){
                                        $usuario = $usuario->removeRole($old_role_name);
                                    }
                                    $data = $usuario->assignRole($role_name);
                                    $status = 'ok';
                                    $message = 'Se ha asignado el rol '. $role_name. ' al usuario '. $usuario->name;
                                    $count = $data->length;
                                    $errors = [];
                                }
                            }else{
                                $status = 'fail';
                                $message = 'Usuario no encontrado';
                                array_push($errors, 'El usuario con id ' . $user_id . ' no existe en la base de datos.');
                            }
                        }else{
                            $message = 'No existe un rol con el id o el nombre proporcionado';
                            array_push($errors, 'Parámetros insuficientes o incorrectos.');
                            $status = 'fail';
                        }
                    } 
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' . strtoupper($user->roles[0]->name);
                $count  = 0;
                $data = null;
                array_push($errors, 'El usuario no está autorizado para acceder a esta ruta');
            }
            // return $error;
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
     * Remove a role from a user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function quitar_rol(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/role/remove',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try{
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // solo usuarios super administradores pueden quitar roles a otros usuarios
            if($user->es_super_admin()){   
                // variables de respuesta
                $role_name = request('role_name');
                $role_id = request('role_id');
                $user_id = request('user_id');
                $params = [
                    'role_name' => $role_name,
                    'role_id' => $role_id,
                    'user_id' => $user_id
                ];
                $count = 0;
                $data = null;
                if($user_id == null){
                    $message = 'El parámetro user_id es requerido.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos.');
                    $status = 'fail';
                }else{
                    $usuario = User::find($user_id);
                    if($role_id != null && $role_name == null){
                        $role = Role::find($role_id);
                        if(!empty($role)){
                            $role_name = $role->name;
                        }else{
                            $role_name = null;
                            $status = 'fail';
                            $message = 'El rol con id ' . $role_id . ' no tiene existe en el sistema.';
                            array_push($errors, 'El rol no existe en el sistema');
                        }
                    }
                    if(!empty($usuario)){
                        if($role_name != null){
                            if($usuario->hasRole($role_name)){
                                $data = $usuario->removeRole($role_name);
                                $status = 'ok';
                                $message = 'Se ha quitado el rol '. $role_name. ' al usuario '. $usuario->name;
                                $count = $data->length;
                                $errors = [];
                            }else{
                                $status = 'fail';
                                $message = 'El usuario ' . $usuario->name . ' no tiene asignado el rol ' . $role_name;
                                array_push($errors, 'El usuario no tiene el rol asignado.');
                            }
                        }
                    }else{
                        $status = 'fail';
                        $message = 'Usuario no encontrado';
                        array_push($errors, 'El usuario con id ' . $user_id . ' no existe en la base de datos.');
                    } 
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario tiene rol ' .strtoupper($user->roles[0]->name);
                $count  = 0;
                $data = null;
                array_push($errors, 'El usuario no está autorizado para acceder a esta ruta');
            }
            // return $error;
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'line' => null,
                'code' => null,
                'message' => $message,
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
                'code' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }
    
    /**
     * Asigna un permiso a un usuario
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function asignar_permiso(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/permission/assign',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $permission_name = request('permission_name');
            $permission_id = request('permission_id');
            $user_id = request('user_id');
            $params = [
                'permission_name' => $permission_name,
                'permission_id' => $permission_id,
                'user_id' => $user_id
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
                if($user_id == null){
                    $count = 0;
                    $message = 'El parámetro user_id es requerido.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos.');
                    $status = 'fail';
                }else{
                    $u = User::with('permissions', 'roles')->find($user_id);
                    if(!empty($u)){
                        $name = $u->name != null ? $u->name : $u->email;
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
                                'logged_user' => $logged_user,
                                'extras' => $extras
                            ]);
                        }
                        // return $permission;
                        if(!empty($permission) && !empty($permission[0])){
                            $permission = $permission[0];
                            if($u->hasPermissionTo($permission)){
                                $count = 0;
                                $message = 'El usuario ' . $name . ' ya tiene asignado el permiso: ' . $permission->name;
                                array_push($errors, 'No se asignó el permiso al usuario.');
                                $status = 'fail';
                                $data = null;
                            }else{
                                $response = $u->givePermissionTo($permission);
                                $us = User::with('permissions')->find($user_id);
                                $per = $us->permissions;
                                $permissions_user = [];
                                foreach($per as $p){
                                    array_push($permissions_user, ['id' => $p->id, 'name' => $p->name]);
                                }
                                $response_user = [
                                    'id' => $us->id,
                                    'name' => $us->name,
                                    'email' => $us->email,
                                    'company_name' => $us->company_name,
                                    'id_prestador' => $us->id_prestador,
                                    'permissions' => $permissions_user
                                ];
                                // return $response;
                                if(!empty($response) && sizeof($response->permissions) > 0){
                                    $count = sizeof($response->permissions);
                                    $message = 'Al usuario ' . $name . ' se le han asignado los permisos solicitados';
                                    $errors = [];
                                    $status = 'ok';
                                    $data = $response_user;
                                }else{
                                    $message = 'No se han podido asignar los permisos solicitados al usuario ' . $name . '.';
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
                        $message = 'El usuario no existe en la base de datos.';
                        array_push($errors, 'No se asignaron permisos al usuario con id ' . $user_id . '. Usuario no encontrado');
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
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
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
     * Quita un permiso de un usuario
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function quitar_permiso(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/permission/revoke',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $permission_name = request('permission_name');
            $permission_id = request('permission_id');
            $user_id = request('user_id');
            $params = [
                'permission_name' => $permission_name,
                'permission_id' => $permission_id,
                'user_id' => $user_id
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
                if($user_id == null){
                    $count = 0;
                    $message = 'El parámetro user_id es requerido.';
                    array_push($errors,'Parámetros insuficientes o incorrectos.' );
                    $status = 'fail';
                }else{
                    $u = User::find($user_id);
                    if(!empty($u)){
                        $name = $u->name != null ? $u->name : $u->email;
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
                            if($u->hasPermissionTo($permission)){
                                $response = $u->revokePermissionTo($permission);
                                if(!empty($response)){
                                    $count = 1;
                                    $message = 'Al usuario ' . $name . ' se le quitaron los permisos solicitados';
                                    $errors = [];
                                    $status = 'ok';
                                    $data = $permission;
                                }else{
                                    $message = 'No se han podido quitar los permisos solicitados al usuario ' . $name . '.';
                                    array_push($errors, 'Error al quitar permisos. Error no especificado.');
                                    $status = 'fail';
                                }
                            }else{
                                $count = 0;
                                $message = 'No se quitó el permiso porque el usuario ' . $name . ' no tiene asignado el permiso: ' . $permission->name;
                                array_push($errors, 'El usuario no tiene el permiso asignado.');
                                $status = 'fail';
                                $data = null;
                            }
                        }else{
                            $count = 0;
                            $message = 'Los permisos deben existir y haber sido asignados al usuario primeramente para poder quitarse.';
                            $data = null;
                            array_push($errors, 'No existen permisos que coincidan con el parámetro dado.');
                            $status = 'fail';
                        }
                    }else{
                        $count = 0;
                        $message = 'El usuario no existe en la base de datos.';
                        array_push($errors, 'No se quitaron permisos al usuario con id ' . $user_id . '. Usuario no encontrado');
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
                '¿line' => null,
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
                'code' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Cambia los permisos directos de un usuario
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sincronizar_permisos(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/permission/synchronize',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $permissions = request('permissions');
            // $revoque_permissions = request('quitar_permisos');
            // $assign_permissions = request('asignar_permisos');
            $user_id = request('user_id');
            $params = [
                // 'revoque_permissions' => $revoque_permissions,
                // 'assign_permissions' => $assign_permissions,
                'permissions' => $permissions,
                'user_id' => $user_id
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
                if($user_id == null){
                    $count = 0;
                    $message = 'El parámetro user_id es requerido.';
                    array_push($errors, 'Parámetros insuficientes o incorrectos.');
                    $status = 'fail';
                }else{
                    $u = User::with('permissions', 'roles')->find($user_id);
                    if(!empty($u)){
                        $u->syncPermissions($permissions);
                        $name = $u->name != null ? $u->name : $u->email;
                        $count = sizeof($u->permissions);
                        $message = 'Al usuario ' . $name . ' se le han asignado los permisos solicitados';
                        $errors = [];
                        $status = 'ok';
                        $data = $user;
                    }else{
                        $count = 0;
                        $message = 'El usuario no existe en la base de datos.';
                        array_push($errors, 'No se asignaron permisos al usuario con id ' . $user_id . '. Usuario no encontrado');
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
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
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
     * Obtiene un listado de todos los emails registrados en el sistema
     * @return emails: string[]
     */
    public function email_exist(Request $request){
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/email-exist',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $email = request('email');
            $todos = request('todos');
           
            $params = [
                'email' => $email,
                'todos' => $todos
            ];

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            if($user->es_super_admin()){
                if(isset($todos) && $todos){
                    $emails = DB::table('users')->select('email', 'deleted_at')->get()->toArray();
                    $extras['response'] = $emails;
                }else{
                    $emails = DB::table('users')->select('email', 'deleted_at')->where('deleted_at', null)->get()->toArray();
                    $extras['response'] = $emails;
                }
                $count = sizeof($emails);
                $data = false;
                // return [ $email, $emails ];
                foreach($emails as $e){
                    if(strcmp(strtolower($email), strtolower($e->email)) == 0){
                        $data = true;
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
            $extras['response'] = null;
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
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
     * Obtiene un listado de todos los usuarios registrados en el sistema
     * @return data: boolean
     */
    public function usuario_exist(Request $request){
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/usuario-exist',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $usuario = request('usuario');
            $todos = request('todos');
           
            $params = [
                'usuario' => $usuario,
                'todos' => $todos
            ];

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            if($user->es_super_admin()){
                if(isset($todos) && $todos){
                    $usuarios = DB::table('users')->select('usuario', 'deleted_at')->get()->toArray();
                    $extras['response'] = $usuarios;
                }else{
                    $usuarios = DB::table('users')->select('usuario', 'deleted_at')->where('deleted_at', null)->get()->toArray();
                    $extras['response'] = $usuarios;
                }
                $count = sizeof($usuarios);
                $data = false;
                // return [ $email, $emails ];
                foreach($usuarios as $u){
                    if(strcmp(strtolower($usuario), strtolower($u->usuario)) == 0){
                        $data = true;
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
            $extras['response'] = null;
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
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
     * Cambia el estado de un perfil
     */
    public function cambiar_estado_perfil(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/cambiar-estado-perfil',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try{
            $user_logueado = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user_logueado);
            // variables de respuesta
            $status = 'fail';
            $message = '';
            $count = 0;
            $data = [];
            $errors = [];
            $params = [];
            $seguir = true;
            $nro = 0;
            
            if($user_logueado->hasRole('super administrador')){
                $id_usuario = request('id_usuario');
                $estado_perfil = request('estado_perfil');
                $user = User::find($id_usuario);
                $user->perfil_completo = $estado_perfil;
                $user->save();
                $status = 'ok';
                $message = 'Estado de perfil cambiado';
                $data = $user;
            }else{
                array_push($errors, 'El usuario no tiene rol super administrador');
                $message = 'No se puede cambiar el estado del perfil';
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
                'errors' => ['Backend failed'],
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'line' => $th->getLine(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Consultar datos del usuarios en oh_admin
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function consultar_usuario_sqlserver(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/user/consultar-usuario-sqlserver',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = 0;
        $code = 0;
        $data = null;
        $errors = [];
        $params = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = '';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'id_usuario_sqlserver' => request('id_usuario_sqlserver'),
                ];
                
                $sp = 'sp_usuario_select';
                $db = 'admin';
                $params_sp = [
                    'p_id_usuario' =>$params['id_usuario_sqlserver']
                ];
                array_push($extras['verificado'], [$sp => ['id_usuario_sqlserver' => request('id_usuario_sqlserver')]]);
                if ( empty(request('id_usuario_sqlserver')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    array_push($extras['sps'], [$sp => $params_sp]);
                    array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                    $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                    array_push($extras['responses'], [$sp => $response]);
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
                        $code = 1;
                    }
                }
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => $code,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }
    
}

