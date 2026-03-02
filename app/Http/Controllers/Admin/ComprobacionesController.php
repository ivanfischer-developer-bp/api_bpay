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

class ComprobacionesController extends ConexionSpController
{
/**
     * Obtiene un listado de todos los emails registrados en el sistema
     * @return emails: string[]
     */
    public function email_existente(Request $request){
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/comprobar/email-existente',
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
            
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];

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
                if(strcmp($email, $e->email) == 0){
                    $data = true;
                }
            }

            if($data){
                $status = 'ok';
                $message = 'email existente';
                $code = 1;
            }else{
                $status = 'ok';
                $message = 'email inexistente';
                $code = 2;
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
                'logged_user' => null,
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
     * y verifica si el nombre de usuario existe y está en uso
     * @return data: boolean
     */
    public function usuario_existente(Request $request){
        // return 'llega';
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'comprobar/usuario-existente',
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

            
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];

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
                if(strcmp($usuario, $u->usuario) == 0){
                    $data = true;
                }
            }

            if($data){
                $status = 'ok';
                $message = 'usuario existente';
                $code = 1;
            }else{
                $status = 'ok';
                $message = 'usuario inexistente';
                $code = 2;
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
                'logged_user' => null,
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
     * y verifica si el nombre de usuario existe y está en uso
     * @return data: boolean
     */
    public function nroDoc_existente(Request $request){
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'comprobar/nroDoc-existente',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $nroDoc = request('nroDoc');
            $todos = request('todos');
            $id_usuario = request('id_usuario');
           
            $params = [
                'nroDoc' => $nroDoc,
                'id_usuario' => $id_usuario,
                'todos' => $todos
            ];

            
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];

            if(isset($todos) && $todos){
                $numeros = DB::table('profile_doctors')->select('nroDoc', 'user_id', 'deleted_at')->get()->toArray();
                $extras['response'] = $numeros;
            }else{
                $numeros = DB::table('profile_doctors')->select('nroDoc', 'user_id', 'deleted_at')->where('deleted_at', null)->get()->toArray();
                $extras['response'] = $numeros;
            }
            $count = sizeof($numeros);
            $data = false;
            
            foreach($numeros as $n){
                if(strcmp($nroDoc, $n->nroDoc) == 0){  // si el nroDoc es igual al nroDoc del perfil
                    if(!isset($id_usuario) || $id_usuario == null || $id_usuario == 0){
                        // si no se envía el id del usuario, se considera que el nroDoc no es único
                        $data = true;
                    }elseif(isset($id_usuario) && $id_usuario != null && $id_usuario != 0){
                        // si se envía el id del usuario, se verifica que el nroDoc
                        // no pertenezca al mismo usuario
                        if($n->user_id != $id_usuario){
                            // si el no es el usuario, se considera que no es único
                            $data = true;
                        }else{
                            // si el usuario, se considera que es único
                            $data = false;
                        }
                    }
                    // $data = true;
                }
            }

            if($data){
                $status = 'ok';
                $message = 'nroDoc existente';
                $code = 1;
            }else{
                $status = 'ok';
                $message = 'nroDoc inexistente';
                $code = 2;
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
                'logged_user' => null,
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
}