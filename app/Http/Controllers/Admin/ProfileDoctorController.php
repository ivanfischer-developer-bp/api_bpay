<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\ProfileDoctor;
use App\Mail\NotificacionEmailRegistroUsuarioDoctor;

use Carbon\Carbon;
use DB;
use Mail;

use App\Http\Controllers\ConexionSpController;

class ProfileDoctorController extends ConexionSpController
{
    /**
     * Obtiene los datos del perfil Medico
     */
    public function buscar_perfil_medico(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/doctor/buscar-perfil-medico',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        try{
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $user_logueado = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user_logueado);
            // variables de respuesta
            $status = 'fail';
            $message = '';
            $count = 0;
            $data = [];
            $code = 0;
            $errors = [];
            $id_usuario = request('id_usuario');
            $params = [
                'id_usuario' => $id_usuario
            ];

            if($user_logueado->hasRole(['medico', 'medico supervisor', 'medico administrador', 'medico auditor', 'medico auditor externo', 'super administrador'])) {
                $id_usuario = $user_logueado->hasRole(['medico', 'medico supervisor', 'medico administrador', 'medico auditor', 'medico auditor externo']) ? $user_logueado->id : request('id_usuario');
                $profile_doctor = ProfileDoctor::where('user_id', '=', $id_usuario)->get();
                $extras['responses'] = ['profile_doctor' => $profile_doctor];
                $status = 'ok';
                $count = 1;
                $message = 'Perfil encontrado';
                $data = $profile_doctor[0];

                if(!empty($profile_doctor) && isset($profile_doctor[0]->user_id) && $profile_doctor[0]->user_id != null){
                    // if($profile_doctor[0]->cuit != null){
                    //     array_push($extras['sps'], ['sp_traerConvenioPrescripcion' => ['cuit' => $profile_doctor[0]->cuit]]);
                    //     array_push($extras['queries'], $this->get_query('validacion', 'sp_traerConvenioPrescripcion', ['cuit' => $profile_doctor[0]->cuit]));
                    //     $resp = $this->ejecutar_sp_directo('validacion', 'sp_traerConvenioPrescripcion', ['cuit' => $profile_doctor[0]->cuit]);
                    //     array_push($extras['responses'], ['sp_traerConvenioPrescripcion' => $resp]);

                    //     if(!empty($resp) && isset($resp[0]->id_convenio) && $resp[0]->id_convenio != null){
                    //         $profile_doctor[0]->id_convenio = $resp[0]->id_convenio;
                    //         $status = 'ok';
                    //         $count = 1;
                    //         $code = 3;
                    //         $message = 'Perfil encontrado';
                    //         $data = $profile_doctor[0];
                    //     }else{
                    //         $profile_doctor[0]->id_convenio = null;
                    //         $status = 'ok';
                    //         $count = 1;
                    //         $code = 2;
                    //         $message = 'Perfil encontrado';
                    //         $data = $profile_doctor[0];
                    //     }
                    // }
                    if($profile_doctor[0]->idRefeps == null) {
                        $url = env('SISA_URL_PROFESIONALES').'?usuario='.env('SISA_USER').'&clave='.env('SISA_PASSWORD').'&nrodoc='.$profile_doctor[0]->nroDoc;
                        $extras['url'] = $url;
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Laravel/8.0)');
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,*/*;q=0.8',
                            'Accept-Language: es-ES,es;q=0.8',
                            'Cache-Control: no-cache'
                        ));
                        
                        $resp = curl_exec($ch);
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $curl_error = curl_error($ch);
                        $curl_errno = curl_errno($ch);
                        $extras['curl_sisa_debug'] = [
                            'http_code' => $http_code,
                            'curl_error' => $curl_error,
                            'curl_errno' => $curl_errno,
                            'response_length' => strlen($resp),
                            'response_type' => gettype($resp)
                        ];
                        curl_close($ch);
                        
                        // Verificar errores de cURL
                        if ($curl_errno !== 0) {
                            array_push($errors, "Error cURL #{$curl_errno}: {$curl_error}");
                            array_push($extras['responses'], ['consulta SISA' => "Error cURL #{$curl_errno}: {$curl_error}"]);
                            array_push($extras['responses'], ['perfil modificado' => false]);
                            $status = 'ok';
                            $count = 1;
                            $code = 6;
                            $message = 'Perfil encontrado';
                            $data = $profile_doctor[0];
                        } else if ($http_code !== 200) {
                            array_push($errors, "HTTP Error: {$http_code}");
                            array_push($extras['responses'], ['consulta SISA' => "HTTP Error: {$http_code}"]);
                            array_push($extras['responses'], ['perfil modificado' => false]);
                            $status = 'ok';
                            $count = 1;
                            $code = 5;
                            $message = 'Perfil encontrado';
                            $data = $profile_doctor[0];
                        } else if($resp != null && $resp !== false){
                            $xml = simplexml_load_string($resp);
                            $json = json_encode($xml);
                            $response = json_decode($json ,TRUE);
                            array_push($extras['responses'], ['consulta SISA' => $response]);
                            $profile_doctor[0]->idRefeps = $response['profesionales']['profesional']['codigo'];
                            $prof_doctor = $profile_doctor[0];
                            $prof_doctor->save();
                            array_push($extras['responses'], ['perfil modificado' => $response]);
                            $status = 'ok';
                            $code = 1;
                            $count = 1;
                            $message = 'Perfil encontrado';
                            $data = $profile_doctor[0];
                        }else{
                            array_push($errors, 'cURL retornó NULL o FALSE');
                            array_push($extras['responses'], ['consulta SISA' => 'cURL retornó NULL o FALSE']);
                            array_push($extras['responses'], ['perfil modificado' => false]);
                            $status = 'ok';
                            $count = 1;
                            $code = 4;
                            $message = 'Perfil encontrado';
                            $data = $profile_doctor[0];
                        }
                    }
                }else{
                    $status = 'empty';
                    $count = 0;
                    $message = 'Perfil NO encontrado';
                    $data = null;
                }
            }else{
                array_push($errors, 'El usuario no tiene permisos');
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user_logueado->roles[0]->name).' no tiene permiso. Solo usuarios con Rol MEDICO o SUPER ADMINISTRADOR puden acceder';
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
     * Completa el perfil de usuario con rol medico
     * los datos requeridos para las recetas
     */
    public function completar_perfil_medico(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/doctor/completar-perfil-medico',
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
            $res = ['message' => ''];
            $error = null;
            $code = null;
            $count = 0;
            $data = [];
            $errors = [];
            $params = [];

            if($user_logueado->hasRole(['medico', 'medico supervisor', 'medico administrador', 'medico auditor', 'medico auditor externo', 'super administrador'])
            ){
                $id_usuario = request('id_usuario');
                if($user_logueado->hasRole('medico') && $user_logueado->id != $id_usuario){
                    array_push($errors, 'El usuario con rol MEDICO no puede completar el perfil de otro usuario.');
                    $message = 'No se pudo completar el perfil porque el usuario no tiene los permisos adecuados';
                }else{
                    $medico = request('medico');
                    $this->params = [
                        'id_usuario' => $id_usuario,
                        'medico' => $medico
                    ];
                    
                    if($medico['sexo'] = 'M' 
                        || $medico['sexo'] == 'm' 
                        || $medico['sexo'] == 'Masculino' 
                        || $medico['sexo'] == 'masculino'
                        ){
                        $sexo = 'Masculino';
                    }else if($medico['sexo'] = 'F' 
                        || $medico['sexo'] == 'f' 
                        || $medico['sexo'] == 'Femenino' 
                        || $medico['sexo'] == 'femenino'
                        ){
                        $sexo = 'Femenino';
                    }else{
                        $sexo = 'No Binario';
                    }

                    $ambiente_recipe = $medico['ambiente_recipe'] != null 
                        && $medico['ambiente_recipe'] !=  'receta' 
                        && $medico['ambiente_recipe'] !=  'Receta' 
                        ? $medico['ambiente_recipe'] 
                        : env('AMBIENTE_RECIPE', 'local');

                    $profile_doctor = new ProfileDoctor();
                    $profile_doctor->user_id = $id_usuario;
                    $profile_doctor->apellido = $medico['apellido'];
                    $profile_doctor->nombre = $medico['nombre'];
                    $profile_doctor->nombre = $medico['tratamiento'];
                    $profile_doctor->tipoDoc = $medico['tipoDoc'] == 'DU' ? 'DNI' : $medico['tipoDoc'];
                    $profile_doctor->nroDoc = $medico['nroDoc'];
                    $profile_doctor->especialidad = $medico['especialidad'];
                    $profile_doctor->sexo = $sexo;
                    $profile_doctor->fechaNacimiento = $medico['fechaNacimiento'];
                    $profile_doctor->email = strtolower($medico['email']);
                    $profile_doctor->telefono = $medico['telefono'];
                    $profile_doctor->pais = $medico['pais'];
                    $profile_doctor->firmalink = $medico['firmalink'];
                    $profile_doctor->matricula_tipo = $medico['matricula_tipo'] != null ? $medico['matricula_tipo'] : $medico['matricula']['tipo'];
                    $profile_doctor->matricula_numero = $medico['matricula_numero'] != null ? $medico['matricula_numero'] : $medico['matricula']['numero'];
                    $profile_doctor->matricula_provincia = $medico['matricula_provincia'] != null ? $medico['matricula_provincia'] : $medico['matricula']['provincia'];     
                    $profile_doctor->cuit = $medico['cuit'];
                    $profile_doctor->horario = $medico['horario'];
                    $profile_doctor->diasAtencion = $medico['diasAtencion'];
                    $profile_doctor->datosContacto = $medico['datosContacto'];
                    $profile_doctor->nombreConsultorio = $medico['nombreConsultorio'];
                    $profile_doctor->direccionConsultorio = $medico['direccionConsultorio'];
                    $profile_doctor->informacionAdicional = $medico['informacionAdicional'];
                    $profile_doctor->idTributario = $medico['cuit'];
                    $profile_doctor->ambiente_recipe = $ambiente_recipe;
                    $profile_doctor->firma_registrada = $medico['firma_registrada'] != null ? $medico['firma_registrada'] : false;
                    $profile_doctor->idRefeps = $medico['idRefeps'];
                    // return response()->json([
                    //     'params' => $this->params,
                    //     'profile_doctor' => $profile_doctor
                    // ]);
                    $response_profile_doctor = ProfileDoctor::updateOrCreate(['user_id' => $id_usuario], [
                        'user_id' => $id_usuario,
                        'apellido' => $medico['apellido'],
                        'nombre' => $medico['nombre'],
                        'tratamiento' => $medico['tratamiento'],
                        'tipoDoc' => $medico['tipoDoc'] == 'DU' ? 'DNI' : $medico['tipoDoc'],
                        'nroDoc' => $medico['nroDoc'],
                        'especialidad' => $medico['especialidad'],
                        'sexo' => $sexo,
                        'fechaNacimiento' => $medico['fechaNacimiento'],
                        'email' => strtolower($medico['email']),
                        'telefono' => $medico['telefono'] != null && $medico['telefono'] != '' ? $medico['telefono'] : '0',
                        'pais' => $medico['pais'],
                        'firmalink' => $medico['firmalink'],
                        'matricula_tipo' => $medico['matricula_tipo'] != null ? $medico['matricula_tipo'] : $medico['matricula']['tipo'],
                        'matricula_numero' => $medico['matricula_numero'] != null ? $medico['matricula_numero'] : $medico['matricula']['numero'],
                        'matricula_provincia' => $medico['matricula_provincia'] != null ? $medico['matricula_provincia'] : $medico['matricula']['provincia'], 
                        'cuit' => $medico['cuit'],
                        'idTributario' => $medico['cuit'],
                        'horario' => $medico['horario'],
                        'diasAtencion' => $medico['diasAtencion'],
                        'datosContacto' => $medico['datosContacto'],
                        'nombreConsultorio' => $medico['nombreConsultorio'],
                        'direccionConsultorio' => $medico['direccionConsultorio'],
                        'informacionAdicional' => $medico['informacionAdicional'],
                        'ambiente_recipe' => $ambiente_recipe,
                        'firma_registrada' => $medico['firma_registrada'] != null ? $medico['firma_registrada'] : false,
                        'idRefeps' => $medico['idRefeps'],
                        'id_convenio' => null,
                    ]);
                    array_push($extras['responses'], ['profile_doctor' => $response_profile_doctor]);
                    if($response_profile_doctor){
                        $datos_notificacion = [
                            'apellido' => $medico['apellido'],
                            'nombre' => $medico['nombre'],
                            'matricula_tipo' => $medico['matricula_tipo'],
                            'matricula_numero' => $medico['matricula_numero']
                        ];
                        if(env('AMBIENTE') != 'produccion'){
                            $asunto = 'Verificación de email (staging)';
                        }else{
                            $asunto = 'Verificación de email';
                        }

                        // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = new NotificacionEmailRegistroUsuarioDoctor($asunto, $datos_notificacion);
                            // Envía automáticamente con fallback
                            $resultado = $this->sendEmail($trimemails, $mailable);
                            array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                            if ($resultado) {
                                $message = 'Email enviado con Microsoft Graph. ';
                                $error = null;
                                $status = 'ok';
                                $code = 1;
                            }else{
                                $message = 'Error al enviar email con Microsoft Graph';
                                $error = $resultado;
                                $status = 'fail';
                                $code = -3;
                            }
                        }else{
                            Mail::to($user_logueado->email)->send(new NotificacionEmailRegistroUsuarioDoctor($asunto, $datos_notificacion));
                            if(Mail::failures()){
                                array_push($extras['responses'], ['smtp_result' => false]);
                                Log::channel('email')->error('Email fallido por SMTP', [
                                    'email' => $user_logueado->email,
                                    'asunto' => $asunto,
                                    'datos_notificacion' => $datos_notificacion
                                ]);
                                $message = 'Error al enviar email por SMTP. ';
                                $error = Mail::failures();
                                $status = 'fail';
                                $code = -4;
                            }else{
                                array_push($extras['responses'], ['smtp_result' => true]);
                                Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                                    'email' => $user_logueado->email,
                                    'asunto' => $asunto,
                                    'datos_notificacion' => $datos_notificacion
                                ]);
                                $message = 'Email enviado por SMTP. ';
                                $error = null;
                                $status = 'ok';
                                $code = 2;
                            }
                            Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                        }

                        // Mail::to($user_logueado->email)->send(new NotificacionEmailRegistroUsuarioDoctor($asunto, $datos_notificacion));
                        $user = User::find($id_usuario);
                        $user->perfil_completo = true;
                        $response_user = $user->update();
                        array_push($extras['responses'], ['user' => $response_user]);
                        $status = 'ok';
                        $count = 1;
                        $message = 'Perfil de usuario completado. '.$message;
                        $data = [
                            'id_usuario' => $id_usuario,
                            'usuario' => $user,
                            'perfil_medico' => $profile_doctor
                        ];
                    }else{
                        $staus = 'fail';
                        $count = 1;
                        $message = 'Perfil no guardado. '.$message;
                        array_push($errors, 'error guardando perfil de medico');
                    }
                }
            }else{
                array_push($errors, 'El usuario con rol '.strtoupper($user_logueado->roles[0]->name).' no tiene permiso para realizar esta acción.');
                $message = 'No se puede completar el perfil del usuario';
            }

            return response()->json([
                'status' => $status,
                'errors' => $errors,
                'message' => $message,
                'code' => $code,
                'count' => $count,
                'line' => null,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'errors' => ['Backend failed'],
                'message' => $th->getMessage(),
                'code' => -1,
                'count' => 0,
                'line' => $th->getLine(),
                'data' => null,
                'params' => $params,
                'logged_user' => null,
                'extras' => $extras
            ]);
        } 
    }

    /**
     * Obtiene un listado de perfil Medico
     */
    public function listar_medicos(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/doctor/listar-medicos',
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
            $this->params = [];
             
            $data = ProfileDoctor::with('users')->get();
            array_push($extras['responses'], ['profile_doctor' => $data]);
            if(!empty($data)){
                $count = sizeof($data);
                $status = 'ok';
                $message = 'Médicos encontrados';
            }else{
                $status = 'empty';
                $count = 0;
                $message = 'No se encontraron médicos';
                $data = null;
            }

            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'data' => $data,
                'params' => $params,
                'line' => null,
                'code' => null,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            $errors = ['Line: '.$th->getLine().' Error: '.$th->getMessage()];
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => ['Backend failed'],
                'message' => $th->getMessage(),
                'code' => -1,
                'line' => $th->getLine(),
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null
            ]);
        }
    }

    /**
     * Actualiza los datos de perfil de un medico
     */
    public function actualizar_perfil_medico(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/doctor/actualizar-perfil-pedico',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => []
        ];
        try{
            $user_logueado = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user_logueado);
            // variables de respuesta
            $status = '';
            $message = '';
            $count = 0;
            $data = [];
            $errors = [];
            $medico = request('medico');
            $params = [
                'medico' => $medico
            ];
            
            if($user_logueado->hasRole('super administrador')){
                $profile = ProfileDoctor::find($medico['id']);
                
                if(!$profile) {
                    array_push($errors, 'Médico no encontrado con ID: ' . $medico['id']);
                    $status = 'fail';
                    $message = 'Médico no encontrado';
                    $count = 0;
                    $data = null;
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'message' => $message,
                        'data' => $data,
                        'params' => $params,
                        'extras' => $extras,
                        'errors' => $errors,
                        'line' => null,
                        'code' => -3,
                        'logged_user' => $logged_user
                    ]);
                }
                $ambiente_recipe = $medico['ambiente_recipe'] != null 
                    && $medico['ambiente_recipe'] !=  'receta' 
                    && $medico['ambiente_recipe'] !=  'Receta' 
                    ? $medico['ambiente_recipe'] 
                    : env('AMBIENTE_RECIPE', 'local');
                $profile->apellido = $medico['apellido'];
                $profile->nombre = $medico['nombre'];
                $profile->tratamiento = $medico['tratamiento'];
                $profile->tipoDoc = $medico['tipoDoc'];
                $profile->nroDoc = $medico['nroDoc'];
                $profile->especialidad = $medico['especialidad'];
                $profile->sexo = $medico['sexo'];
                $profile->fechaNacimiento = $medico['fechaNacimiento'];
                $profile->email = $medico['email'];
                $profile->telefono = $medico['telefono'];
                $profile->pais = $medico['pais'];
                $profile->firmalink = $medico['firmalink'];
                $profile->matricula_tipo = $medico['matricula_tipo'];
                $profile->matricula_numero = $medico['matricula_numero'];
                $profile->matricula_provincia = $medico['matricula_provincia'];
                $profile->cuit = $medico['cuit'];
                $profile->idTributario = $medico['idTributario'];
                $profile->horario = $medico['horario'];
                $profile->diasAtencion = $medico['diasAtencion'];
                $profile->datosContacto = $medico['datosContacto'];
                $profile->nombreConsultorio = $medico['nombreConsultorio'];
                $profile->direccionConsultorio = $medico['direccionConsultorio'];
                $profile->informacionAdicional = $medico['informacionAdicional'];
                $profile->ambiente_recipe = $ambiente_recipe;
                $profile->firma_registrada = $medico['firma_registrada'] != null ? $medico['firma_registrada'] : false;
                $profile->idRefeps = $medico['idRefeps'];
                $profile->id_convenio = $medico['id_convenio'];
                
                $p = $profile->save();
                if($p){
                    $count = 1;
                    $status = 'ok';
                    $message = 'Perfil médico actualizado con éxito';
                    $code = 1;
                    $data = $p;
                    $errors = [' '];
                }else{
                    $count = 0;
                    $status = 'fail';
                    $message = 'No se pudo actualizar el perfil medico';
                    $code = 1;
                    $data = null;
                    $errors = ['Fallo al actualizar perfil médico'];
                }
            }else{
                array_push($errors, 'El usuario no tiene permisos');
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Solo usuarios con Rol SUPER ADMINISTRADOR puden acceder';
            }

            return response()->json([
                'status' => $status,
                'count' => $count,
                'message' => $message,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'errors' => $errors,
                'line' => null,
                'code' => null,
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            $errors = ['Line: '.$th->getLine().' Error: '.$th->getMessage()];
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null
            ]);
        }
    }

    /**
     * Actualizar el estado de la firma registrada de un médico
     */
    public function actualizar_firma_registrada(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/doctor/actualizar-firma-registrada',
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
            $code = 0;
            $data = null;
            $errors = [];
            $id_medico = request('id_medico');
            $firma_registrada = request()->boolean('firma_registrada');
            $params = [
                'id_medico' => $id_medico,
                'firma_registrada' => $firma_registrada
            ];

            if($user_logueado->hasRole(['medico', 'medico supervisor', 'medico administrador', 'medico auditor', 'medico auditor externo', 'super administrador'])){
                $profile = ProfileDoctor::find($id_medico);
                
                if($profile) {
                    $profile->firma_registrada = $firma_registrada ? 1 : 0;
                    $p = $profile->save();
                    $profile->refresh(); // Verificar que realmente se guardó
                    if(!$p) {
                        // Si save() retorna false, hay un problema de validación
                        $errors = $profile->getErrors(); // Si tienes validaciones en el modelo
                        array_push($errors, 'Error al guardar: posible problema de validación');
                    }
                } else {
                    $p = false;
                    array_push($errors, 'Perfil de médico no encontrado');
                }
                $extras['responses'] = ['profile_doctor' => $p];
                if($p){
                    $count = 1;
                    $status = 'ok';
                    $message = 'Firma registrada actualizada con éxito';
                    $code = 1;
                    $data = $profile;
                }else{
                    $count = 0;
                    $status = 'fail';
                    $message = 'No se pudo actualizar la firma registrada';
                    $code = -2;
                    $data = null;
                    $errors = ['Fallo al actualizar firma registrada'];
                }
            }else{
                array_push($errors, 'El usuario no tiene permisos');
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso para realizar esta acción';
            }

            return response()->json([
                'status' => $status,
                'count' => $count,
                'message' => $message,
                'data' => $data,
                'params' => $params,  
                'extras' => $extras,
                'errors' => $errors,
                'line' => null,
                'code' => $code,
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            $errors = ['Line: '.$th->getLine().' Error: '.$th->getMessage()];
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null
            ]);
        }
    }

    /**
     * Asigna una matricula, provincia y especialidad a un perfil médico actualizando el registro
     * */  
    public function asignar_matricula_medico(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/profile/doctor/asignar-matricula-medico',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => []
        ];
        try{
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            // variables de respuesta
            $status = '';
            $message = '';
            $count = 0;
            $data = [];
            $errors = [];

            $user_id = request('user_id');
            $medico_id = request('medico_id');
            $matricula_numero = request('matricula_numero');
            $matricula_provincia = request('matricula_provincia');
            $especialidad = request('especialidad');
            $idRefeps = request('idRefeps') != null ? request('idRefeps') : null;

            $params = [
                'user_id' => $user_id,
                'medico_id' => $medico_id,
                'matricula_numero' => $matricula_numero,
                'matricula_provincia' => $matricula_provincia,
                'especialidad' => $especialidad,
                'idRefeps' => $idRefeps
            ];
            
            if($user->hasRole('super administrador')
                || $user->hasRole('medico administrador')
                || $user->hasRole('medico supervisor')
                || ($user->hasRole('medico') && $user->id == $user_id)
                || ($user->hasRole('medico auditor') && $user->id == $user_id)
                || ($user->hasRole('medico auditor externo') && $user->id == $user_id)
            ){
                $profile = ProfileDoctor::find($medico_id);
                
                if(!$profile) {
                    array_push($errors, 'Médico no encontrado con ID: ' . $medico_id);
                    $status = 'fail';
                    $message = 'Médico no encontrado';
                    $count = 0;
                    $data = null;
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'message' => $message,
                        'data' => $data,
                        'params' => $params,
                        'extras' => $extras,
                        'errors' => $errors,
                        'line' => null,
                        'code' => -3,
                        'logged_user' => $logged_user
                    ]);
                }
                
                if($matricula_numero != null){
                    $profile->matricula_numero = $matricula_numero;
                }
                if($matricula_provincia != null){
                    $profile->matricula_provincia = $matricula_provincia;
                }
                if($especialidad != null){
                    $profile->especialidad = $especialidad;
                }
                if($idRefeps != null){
                    $profile->idRefeps = $idRefeps;
                }

                $p = $profile->save();
                
                if($p){
                    $count = 1;
                    $status = 'ok';
                    $message = 'Matrícula asignada con éxito';
                    $code = 1;
                    $data = $p;
                    $errors = [' '];
                }else{
                    $count = 0;
                    $status = 'fail';
                    $message = 'No se pudo asignar la matrícula';
                    $code = 1;
                    $data = null;
                    $errors = ['Fallo al asignar la matrícula'];
                }
            }else{
                array_push($errors, 'El usuario no tiene un Rol permitido para esta acción');
                $status = 'unauthorized';
                $message = 'El usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso para realizar esta acción.';
            }

            return response()->json([
                'status' => $status,
                'count' => $count,
                'message' => $message,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'errors' => $errors,
                'line' => null,
                'code' => null,
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            $errors = ['Line: '.$th->getLine().' Error: '.$th->getMessage()];
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null
            ]);
        }
    }
}