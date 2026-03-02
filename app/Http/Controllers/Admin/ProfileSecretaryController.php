<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\ProfileDoctor;
use App\Models\ProfileSecretary;

use Carbon\Carbon;
use DB;

use App\Http\Controllers\ConexionSpController;

class ProfileSecretaryController extends ConexionSpController
{
     /**
     * Obtiene los datos del perfil Secretaria
     */
    public function search(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/secretary/search',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => []
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
            $id_usuario = request('id_usuario');
            $this->params = [
                'id_usuario' => $id_usuario
            ];
            
            if($user_logueado->hasRole('secretaria medico') || $user_logueado->hasRole('super administrador')){
                $id_usuario = $user_logueado->hasRole('secretaria medico') ? $user_logueado->id : request('id_usuario');
                if($id_usuario != null){
                    $profile_secretary = ProfileSecretary::with('doctors')->where('user_id', $id_usuario)->first();
                    array_push($extras['responses'], ['profile_secretary' => $profile_secretary]);
                    if(!empty($profile_secretary) && is_array($profile_secretary)){
                        $profile_secretary = $profile_secretary[0];   
                    }
                    if(isset($profile_secretary->user_id) && $profile_secretary->user_id != null){
                        $status = 'ok';
                        $count = 1;
                        $message = 'Perfil encontrado';
                        $data = $profile_secretary;
                    }else{
                        $status = 'fail';
                        $count = 0;
                        $message = 'Perfil NO encontrado';
                        $data = null;
                    }
                }else{
                    $data = ProfileSecretary::with('doctors')->get();
                    array_push($extras['responses'], $data);
                    if($data != null){
                        $status = 'ok';
                        $count = 1;
                        $message = 'Perfil encontrado';
                        $data = $profile_secretary;
                    }else{
                        $status = 'fail';
                        $count = 0;
                        $message = 'Perfil NO encontrado';
                        $data = null;
                    }
                }
            }else{
                array_push($errors, 'El usuario no tiene permisos');
                $message = 'No se puede acceder al perfil secretary';
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
     * Completa el perfil de usuario con rol secretaria
     */
    public function complete(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/secretary/complete',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => []
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
            
            if($user_logueado->hasRole('secretaria medico') || $user_logueado->hasRole('super administrador')){
                $id_usuario = request('id_usuario');
                if($user_logueado->hasRole('secretaria medico') && $user_logueado->id != $id_usuario){
                    array_push($errors, 'El usuario con rol SECRETARIA MEDICO no puede completar el perfil de otro usuario.');
                    $message = 'No se pudo completar el perfil porque el usuario no tiene los permisos adecuados';
                }else{
                    $secretary = request('secretary');
                    $this->params = [
                        'id_usuario' => $id_usuario,
                        'secretary' => $secretary
                    ];
                    $profile_secretary = new ProfileSecretary();
                    $profile_secretary->user_id = $id_usuario;
                    $profile_secretary->apellido = $secretary['apellido'];
                    $profile_secretary->nombre = $secretary['nombre'];
                    $profile_secretary->tipoDoc = $secretary['tipoDoc'];
                    $profile_secretary->nroDoc = $secretary['nroDoc'];
                    $profile_secretary->sexo = $secretary['sexo'];
                    $profile_secretary->email = $secretary['email'];
                    // return response()->json([
                    //     'params' => $this->params,
                    //     'profile_secretary' => $profile_secretary
                    // ]);
                    $response_profile_secretary = ProfileSecretary::updateOrCreate(['user_id' => $id_usuario], [
                        'user_id' => $id_usuario,
                        'apellido' => $secretary['apellido'],
                        'nombre' => $secretary['nombre'],
                        'tipoDoc' => $secretary['tipoDoc'],
                        'nroDoc' => $secretary['nroDoc'],
                        'sexo' => $secretary['sexo'],
                        'email' => $secretary['email'],
                    ]);
                    array_push($extras['responses'], ['profile_secretary' => $response_profile_secretary]);
                    if($response_profile_secretary){
                        $user = User::find($id_usuario);
                        $user->perfil_completo = true;
                        $response_user = $user->update();
                        array_push($extras['responses'], ['user' => $response_user]);
                        $status = 'ok';
                        $count = 1;
                        $message = 'Perfil de usuario completado';
                        $data = [
                            'id_usuario' => $id_usuario,
                            'usuario' => $user,
                            'perfil_secretary' => $profile_secretary
                        ];
                    }else{
                        $staus = 'fail';
                        $count = 1;
                        $message = 'Perfil no guardado';
                        array_push($errors, 'error guardando perfil de secretaria');
                    }
                }
            }else{
                array_push($errors, 'El usuario no tiene rol secretaria medico ni super administrador');
                $message = 'No se puede completar el perfil del usuario';
            }

            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => null,
                'data' => $data,
                'params' => $this->params,
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
                'params' => $this->params,
                'logged_user' => null,
                'extras' => $extras
            ]);
        } 
    }

    /**
     * Crea o actualiza una relación con médicos
     */
    public function relate(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/secretary/relate',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => []
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
            $id_usuario_secretaria = request('id_usuario_secretaria');
            $ids_medicos = request('ids_medicos');
            $this->params = [
                'id_usuario_secretaria' => $id_usuario_secretaria,
                'ids_medicos' => $ids_medicos
            ];
            
            if($user_logueado->hasRole('super administrador')){
                // $user->roles()->attach($roleId); // Asociar
                // $user->roles()->detach($roleId); // Desasociar
                // $user->roles()->sync([1, 2, 3]); // Sincronizar
                // sync() acepta un array de IDs para reemplazar todas las relaciones existentes.
                $profile_secretary = ProfileSecretary::with('doctors')->where('user_id', '=', $id_usuario_secretaria)->first();
                array_push($extras['responses'], ['profile_secretary' => $profile_secretary]);
                if(!empty($profile_secretary)){
                    $data = $profile_secretary->doctors()->sync($ids_medicos);
                    array_push($extras['responses'], ['sync doctors' => $data]);
                    if(!empty($data)){
                        $message = 'Registros sincronizados';
                        $status = 'ok';
                        $count = 1;
                    }else{
                        array_push($errors, 'Usuario Medico no encontrado');
                        $message = 'Fallo al sincronizar';
                    }
                }else{
                    array_push($errors, 'El usuario con id '.$id_usuario_secretaria.' no tiene rol SECCRETARIA MEDICO');
                    $message = 'Usuario no encontrado';
                }
            }else{
                array_push($errors, 'El usuario no tiene permisos');
                $message = 'El usuario con rol '.$logged_user->roles->name.' no puede relacionar medicos con secretarias.';
            }

            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => null,
                'data' => $data,
                'params' => $this->params,
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
     * Obtiene los datos del perfil Secretaria
     */
    public function list(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/secretary/search',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => []
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
            $this->params = [];

            $data = ProfileSecretary::with('doctors')->get();
            array_push($extras['responses'], $data);
            if($data != null){
                $status = 'ok';
                $count = 1;
                $message = 'Perfil encontrado';
            }else{
                $status = 'fail';
                $count = 0;
                $message = 'Perfil NO encontrado';
                $data = null;
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

}