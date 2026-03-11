<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

use App\Traits\SendsEmailsTrait;

use App\Models\User;
use App\Models\Conexion;

class ConexionSpController extends Controller
{
    use SendsEmailsTrait;
    
    public $status = null;
    public $message = null;
    public $count = 0;
    public $errors = [];
    public $error = null;
    public $data = null;
    public $resultado = null;
    public $error_message_ok = null;
    public $message_ok = null;
    public $message_fail = null;
    public $message_empty = null;

    /**
     * The database name
     * @var db String la base de datos a la cual se hará conexion: 'afiliacion', 'validacion', 'admin', 'alfabeta
     */
    public $db = null;
    public $sp = null;
    public $params = [];
    public $options = [];
    public $response = [];

    public $line = null;
    public $code = null;
    public $sps = [];
    public $logged_user = null;
    public $extras = [];

    public $controlador = '';
    public $funcion = '';
    public $url = '';
    public $permiso_requerido = '';
    public $user_id = 1;
    public $params_sp = [];
    public $param_id_usuario = '';
    public $tipo_id_usuario = 'id';
    public $param_usuario = '';
    public $verificado = [];
    public $metodo_http = 'get';

    
    /**
     * Constructor
     * @var db Stringbase de datos: 'afiliacion', 'validacion', 'admin'
     * @var sp String nombre del procesimiento almacenado 
     * @var params Array de parámetros para el sp
     * @var options Array de opciones
     */
    public function __construct($db = null, $sp = null, $params = null, $options = []){
        $this->db = $db;
        $this->sp = $sp;
        $this->params = $params;
        $this->options = $options;
        $this->errors = array_key_exists('$errors', $options) ? $options['$errors'] : [];
        $this->error = array_key_exists('$error', $options) ? $options['$error'] : NULL;
        $this->error_message_ok = array_key_exists('$error_message_ok', $options) ? $options['$error_message_ok'] : NULL;
        $this->message_ok = array_key_exists('$message_ok', $options) ? $options['$message_ok'] : NULL;
        $this->message_fail = array_key_exists('$message_fail', $options) ? $options['$message_fail'] : NULL;
        $this->message_empty = array_key_exists('$message_empty', $options) ? $options['$message_empty'] : NULL;
    }

    /**
     * Ejecuta el sp con las propiedades definidas en la clase
     * Método protegido
     */
    public function ejecutar_sp_simple()
    {
        $this->extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => $this->url,
            'controller' => $this->controlador,
            'function' => $this->funcion,
            'queries' => [],
            'sps' => [],
            'responses' => [],
            'verificado' => $this->verificado
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($this->user_id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            if($this->permiso_requerido == '' || $user->hasPermissionTo($this->permiso_requerido)){
                if($this->param_id_usuario != ''){
                    if($this->tipo_id_usuario == 'id'){
                        $this->params_sp[$this->param_id_usuario] = $id_usuario;
                    }
                    if($this->tipo_id_usuario == 'usuario'){
                        $this->params_sp[$this->param_id_usuario] = $logged_user['usuario'];
                        // $this->params_sp[$this->param_id_usuario] = 'mrocco@cajaabogados.org.ar';
                    }
                    if($this->tipo_id_usuario == 'email'){
                        $this->params_sp[$this->param_id_usuario] = $logged_user['email'];
                    }
                    if($this->tipo_id_usuario == 'param'){
                        $this->params_sp[$this->param_id_usuario] = $this->param_usuario;
                    }
                }
                // array_push($this->extras['sps'], [$this->sp => $this->params_sp]);
                $this->extras['sps'][$this->sp] = $this->params_sp;
                // return $extras['sps'];
                array_push($this->extras['queries'], $this->get_query($this->db, $this->sp, $this->params_sp));
                $response = $this->ejecutar_sp_directo($this->db, $this->sp, $this->params_sp);
                // array_push($this->extras['responses'], [$this->sp => $response]);
                $this->extras['responses'][$this->sp] = $response;
                // return $response;
                if(is_array($response) && array_key_exists('error', $response)){
                    array_push($errors, $response['error']);
                    $status = 'fail';
                    $message = 'Se produjo un error al realizar la petición';
                    $count = 0;
                    $data = null;
                    $code = -3;
                }else if(empty($response)){
                    $status = 'empty';
                    $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                    $count = 0;
                    $data = $response;
                    $code = -4;
                }else{
                    $status = 'ok';
                    $message = 'Transacción realizada con éxito.';
                    $count = sizeof($response);
                    $data = $response;
                    $code = 1;
                }
                $this->response = [
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => $code,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $this->extras,
                    'logged_user' => $logged_user,
                ];
                return response()->json($this->response); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($this->permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                $this->response = [
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -2,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $this->extras,
                    'logged_user' => $logged_user,
                ];
                return response()->json($this->response); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' de ConexionSpController.'.' Error: '.$th->getMessage());
            $this->response = [
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $this->params,
                'extras' => $this->extras,
                'logged_user' => $logged_user,
            ];
            return response()->json($this->response);
        }
    }

    protected function ejecutar_sp()
    {
        try {
            $conexion = new Conexion($this->db);
            $response_sp = $conexion->ejecutar_sp($this->sp, $this->params_sp);
            
            $this->extras = [
                'api_software_version' => config('site.software_version'),
                'ambiente' => config('site.ambiente'),
                'url' => $this->url,
                'controller' => $this->controlador,
                'function' => $this->funcion,
                'sps' => [$this->sp => $this->params_sp],
                'responses' => [$this->sp => $response_sp],
                'verificado' => $this->verificado,
                'queries' => $this->get_query($this->db, $this->sp, $this->params_sp)
            ];
            $user = User::with('roles', 'permissions')->find($this->user_id);
            $this->logged_user = $this->get_logged_user($user);
            return $response_sp;
        } catch (\Throwable $th) {
            array_push($this->errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
        }
    }

    /**
     * Ejecuta el sp con los parámetros dados
     * Método público
     * Este método está pensado para trabajar todos los datos en la clase hija,
     * el resultado es crudo, como viene del sp, no tiene ningún valor agregado,
     * toda la respuesta debe trabajarse en la clase hija.
     * 
     * @var db String base de datos a consultar 'afiliacion', 'validacion', 'admin', si es null se usa la propiedad de la clase
     * @var sp String nombre del procedimiento almacenado a ejecutar, si es null se usa la propiedad de la clase
     * @var params Array de parámetros para el sp, si es null se usa la propiedad de la clase
     * 
     * @return data datos devueltos por el sp tal cual vienen si la conexión tiene éxito
     * @return errors Array de errores producidos si la conexión falla
     */
    public function ejecutar_sp_directo($db, $sp, $params)
    {
        $db = $db == null ? $this->db : $db;
        $sp = $sp == null ? $this->sp : $sp;
        $params = $params == null ? $this->params : $params;
        $this->resultado = null;
        $this->error = null;
        try {
            $conexion = new Conexion($db);
            $this->resultado = $conexion->ejecutar_sp($sp, $params);
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            $this->errors[] = $this->error;
        }
        // si resultado no está null devuelve el resultado
        if($this->resultado != null){
            return $this->resultado;
        }else{
            // si no hay errores registrados devuelve el resultado esté como esté
            if($this->error == null){
                return $this->resultado;
            }else{
                // si capturó un error con catch devuelve el error
                return ['error' => $this->error];
            }
        }
    }

     /**
     * Devuelve un json de respuesta con los resultados de la consulta
     * con un formato específico prestablecido. 
     * Método protegido
     * @return \Illuminate\Http\Response
     */
    
     protected function get_response(){
        $user = User::with('roles', 'permissions')->find($this->user_id);
        $this->logged_user = $this->get_logged_user($user);
        return response()->json([
            'status' => $this->status,
            'count' => $this->count,
            'errors' => $this->errors,
            'message' => $this->message,
            'line' => $this->line,
            'code' => $this->code,
            'data' => $this->data,
            'params' => $this->params,
            'extras' => [
                'api_software_version' => config('site.software_version'),
                'ambiente' => config('site.ambiente'),
                'url' => $this->url,
                'controller' => $this->controlador,
                'function' => $this->funcion,
                'queries' => [],
                'sps' => [],
                'responses' => [],
                'verificado' => $this->verificado
            ],
            'logged_user' => $this->logged_user
        ]);
    }

    /**
     * Devuelve un string con la consulta hecha al sp con todos los parámetros y sus valores
     */
    public function get_query($db, $sp, $params){
        try {
            $query = '';
            if(env('AMBIENTE') == 'local'){
                $query = 'SET NOCOUNT ON; EXEC ';
            }else{
                $query = 'EXEC ';
            }
            switch ($db){
                case 'afiliacion':
                    $query = $query.env('AFILIACION_DB_DATABASE').'..';
                    break;
                case 'validacion':
                    $query = $query.env('VALIDACION_DB_DATABASE').'..';
                    break;
                case 'admin': 
                    $query = $query.env('ADMIN_DB_DATABASE').'..'; //'oh_admin_prd..';
                    break;
                case 'alfabeta':
                    $query = $query.'Alfabeta..';
                    break;
                default:
                    $query = $query.'..';
                    break;
            }
            $query = $query.$sp;
            $sp_params = ' ';
            $parametros = $this->params;
            if($params != null){
                $parametros = $params;
            }
            if($parametros != null && sizeof($parametros) > 0){
                foreach ($parametros as $key => $value) {
                    $val = $value.', ';
                    if(is_numeric($value) && substr($value, 0, 2) == 'id'){
                        $val = $value.', ';
                    }else if(is_string($value)){
                        $val = "'".$value."', ";
                    } 
                    if(is_null($value) || (is_string($value) && $value == 'null')){
                        $val = ", ";
                    }
                    $sp_params .= '@' . $key . ' = '.$val;
                }
                $query = $query.$sp_params;
                $query = substr($query, 0, -2);
            }
            return $query;
        } catch (\Throwable $th) {
            return 'Error escribiendo query, línea: '.$th->getLine().' Codigo: '.$th->getCode().' Mensaje: '.$th->getMessage();
        }
    }

    public function endpoint_no_implementado()
    {
        $this->status = 'fail';
        $this->message = 'Endpoint no implementado';
        $this->count = 0;
        $this->errors = ['Endpoint no implementado'];
        $this->code = -1;
        $this->data = null;
        return $this->get_response();
    }
}