<?php

namespace App\Http\Controllers\Internos\General;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;

// use UltraMsg\WhatsAppApi;
use Twilio\Rest\Client as TwilioClient;

class PhoneMessagesController extends ConexionSpController
{
    /**
     * Envía una receta por wahtsapp
     */
    public function enviar_receta_whatsapp(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/phone-messages/enviar-receta-whatsapp',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'enviar whatsapp';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                // Your Account SID and Auth Token from console.twilio.com
                $sid = env('TWILIO_ACCOUNT_SID');
                $token = env('TWILIO_AUTH_TOKEN');
                $twilio = new TwilioClient($sid, $token);

                $notificacion = $request->input('notificacion');
                $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
                $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
                $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
                $observaciones_notificacion = $notificacion['observaciones'] == null ? '' : $notificacion['observaciones'];
                $links_recetas = [];
                $telefono = $notificacion['telefono'];
                $mensaje = $notificacion['mensaje'] != null && $notificacion['mensaje'] != '' ? $notificacion['mensaje'] : 'Esta es su receta emitida';
                
                if(is_array($notificacion['array']) && sizeof($notificacion['array']) > 0){
                    foreach ($notificacion['array'] as $value) {
                        if(filter_var($value['link_pdf'], FILTER_VALIDATE_URL)){
                            array_push($links_recetas, $value['link_pdf']);
                        }
                    }
                }
                // return $links_recetas;
                $this->params = [
                    'notificacion' => $notificacion,
                    'nro_afiliado' => $nro_afiliado,
                    'n_persona' => $n_persona,
                    'nro_doc' => $nro_doc,
                    'observaciones_notificacion' => $observaciones_notificacion,
                    'array' => $links_recetas,
                    'telefono' => $telefono,
                    'mensaje' => $mensaje
                ];

                if(sizeof($links_recetas) === 0 || $telefono == null){
                    $errors = ['array de pdfs o teléfono nulos'];
                    $code = -3;
                    $data = null;
                    $status = 'fail';
                    $message = 'Verifique los parámetros, no pueden ser nulos el array de pdfs ni el teléfono';
                    $count = 0;
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'message' => $message,
                        'line' => null,
                        'code' => $code,
                        'data' => $data,
                        'params' => $this->params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]);
                }
                
                // Use the Client to make requests to the Twilio REST API
                $message = $twilio->messages->create(
                    // The number you'd like to send the message to
                    'whatsapp:'.$telefono,
                    array(
                        // A Twilio phone number you purchased at https://console.twilio.com
                        'from' => 'whatsapp:'.env('TWILIO_WHATSAPP_NUMBER'),
                        'body' => $mensaje,
                        'mediaUrl' => $links_recetas
                    )
                );
                $response_twilio = method_exists($message, 'toArray') ? $message->toArray() : (string)$message;
                
                $data = [
                    'response_twilio' =>  $response_twilio,
                ];
                
                if($response_twilio['errorMessage'] != null){
                    array_push($errors, 'Error Twilio: '.$response_twilio['errorMessage']);
                    $status = 'fail';
                    $message = 'Mensaje no enviado';
                    $code = -2;
                    $count = 0;
                }else{
                    $status = 'ok';
                    $message = 'Mensaje enviado correctamente';
                    $code = 1;
                    $count = 1;
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => $code,
                    'data' => $data,
                    'params' => $this->params,
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
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
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

    /**
     * Envía una receta por wahtsapp
     */
    public function enviar_validacion_whatsapp(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/phone-messages/enviar-validacion-whatsapp',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'enviar whatsapp';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                // Your Account SID and Auth Token from console.twilio.com
                $sid = env('TWILIO_ACCOUNT_SID');
                $token = env('TWILIO_AUTH_TOKEN');
                $twilio = new TwilioClient($sid, $token);

                $notificacion = $request->input('notificacion');
                $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
                $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
                $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
                $observaciones_notificacion = $notificacion['observaciones'] == null ? '' : $notificacion['observaciones'];
                $links_validaciones = [];
                $telefono = $notificacion['telefono'];
                $mensaje = $notificacion['mensaje'] != null && $notificacion['mensaje'] != '' ? $notificacion['mensaje'] : 'Esta es su validación emitida';

                $ngrokUrl = env('NGROK_URL');
                $relativePath = '/storage/reportes/validaciones/';
                $urlPublica = $ngrokUrl . $relativePath;
                
                if(is_array($notificacion['array']) && sizeof($notificacion['array']) > 0){
                    foreach ($notificacion['array'] as $value) {
                        array_push($links_validaciones, $urlPublica . $value);
                    }
                }
   
                $this->params = [
                    'notificacion' => $notificacion,
                    'nro_afiliado' => $nro_afiliado,
                    'n_persona' => $n_persona,
                    'nro_doc' => $nro_doc,
                    'observaciones_notificacion' => $observaciones_notificacion,
                    'array' => $links_validaciones,
                    'telefono' => $telefono,
                    'mensaje' => $mensaje
                ];

                if(sizeof($links_validaciones) === 0 || $telefono == null){
                    $errors = ['array de pdfs o teléfono nulos'];
                    $code = -3;
                    $data = null;
                    $status = 'fail';
                    $message = 'Verifique los parámetros, no pueden ser nulos el array de pdfs ni el teléfono';
                    $count = 0;
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'message' => $message,
                        'line' => null,
                        'code' => $code,
                        'data' => $data,
                        'params' => $this->params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]);
                }
                
                // Use the Client to make requests to the Twilio REST API
                try {
                    $message = $twilio->messages->create(
                        // The number you'd like to send the message to
                        'whatsapp:'.$telefono,
                        array(
                            // A Twilio phone number you purchased at https://console.twilio.com
                            'from' => 'whatsapp:'.env('TWILIO_WHATSAPP_NUMBER'),
                            'body' => $mensaje,
                            'mediaUrl' => $links_validaciones
                        )
                    );
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
                
                // return $message;
                $response_twilio = method_exists($message, 'toArray') ? $message->toArray() : (string)$message;
                // return $response_twilio;
                $data = [
                    'response_twilio' =>  $response_twilio,
                ];
                
                if($response_twilio['errorMessage'] != null){
                    array_push($errors, 'Error Twilio: '.$response_twilio['errorMessage']);
                    $status = 'fail';
                    $message = 'Mensaje no enviado';
                    $code = -2;
                    $count = 0;
                }else{
                    $status = 'ok';
                    $message = 'Mensaje enviado correctamente';
                    $code = 1;
                    $count = 1;
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => $code,
                    'data' => $data,
                    'params' => $this->params,
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
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
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




    /**
     * Envía un mensaje de whatsapp para prueba
     */
    public function enviar_mensaje_whatsapp(Request $request)
    {
        // telefono: +5493517624246 pablo, +5491151106807 sergio
        try {
            // Your Account SID and Auth Token from console.twilio.com
            $sid = env('TWILIO_ACCOUNT_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $twilio = new TwilioClient($sid, $token);

            $telefono = request('telefono');
            $enlace = request('enlace');
            $mensaje = request('mensaje') != null ? request('mensaje').' '.$enlace : 'Puede ver su receta en el siguiente enlace: '.$enlace;

            // SMS
            // Use the Client to make requests to the Twilio REST API
            $message = $twilio->messages->create(
                // The number you'd like to send the message to
                'whatsapp:'.$telefono,
                array(
                    // A Twilio phone number you purchased at https://console.twilio.com
                    'from' => 'whatsapp:'.env('TWILIO_WHATSAPP_NUMBER'),
                    // 'contentSid' => env('TWILIO_CONTENT_SID'), // elegir una plantilla de ahí
                    // 'contentVariables' => '{"1":"12/1","2":"3pm"}',
                    // The body of the text message you'd like to send
                    'body' => $mensaje
                )
            );
            $response_twilio = method_exists($message, 'toArray') ? $message->toArray() : (string)$message;
            return response()->json([
                'response_twilio' =>  $response_twilio,
                'telefono' => $telefono,
                'mensaje' => $mensaje,
                'enlace' => $enlace
            ]);
            // esto devuelve cuando funciona
            // {
            //     "response_twilio": {
            //         "body": "Puede ver su receta en el siguiente enlace: https://prescriptions.hml.qbitos.com/26142_289799ML7DSPVS0SDLRU4PWMQ3TD8MCTYJ6X.pdf",
            //         "numSegments": "1",
            //         "direction": "outbound-api",
            //         "from": "whatsapp:+14155238886",
            //         "to": "whatsapp:+5493517624246",
            //         "dateUpdated": {
            //             "date": "2025-09-11 20:26:46.000000",
            //             "timezone_type": 1,
            //             "timezone": "+00:00"
            //         },
            //         "price": null,
            //         "errorMessage": null,
            //         "uri": "/2010-04-01/Accounts/AC_REDACTED/Messages/SM_REDACTED.json",
            //         "accountSid": "AC_REDACTED",
            //         "numMedia": "0",
            //         "status": "queued",
            //         "messagingServiceSid": null,
            //         "sid": "SM2b98b6bcf8ed744513165573bcd3a831",
            //         "dateSent": null,
            //         "dateCreated": {
            //             "date": "2025-09-11 20:26:46.000000",
            //             "timezone_type": 1,
            //             "timezone": "+00:00"
            //         },
            //         "errorCode": null,
            //         "priceUnit": null,
            //         "apiVersion": "2010-04-01",
            //         "subresourceUris": {
            //             "media": "/2010-04-01/Accounts/AC_REDACTED/Messages/SM_REDACTED/Media.json"
            //         }
            //     },
            //     "telefono": "+5493517624246",
            //     "mensaje": "Puede ver su receta en el siguiente enlace: https://prescriptions.hml.qbitos.com/26142_289799ML7DSPVS0SDLRU4PWMQ3TD8MCTYJ6X.pdf",
            //     "enlace": "https://prescriptions.hml.qbitos.com/26142_289799ML7DSPVS0SDLRU4PWMQ3TD8MCTYJ6X.pdf"
            // }

            
        } catch (\Throwable $th) {
            return 'Line: '.$th->getLine().' - '.$th->getMessage();
        }
        // con ultramsg
        // try {
        //     $telefono = request('telefono');
        //     $mensaje = request('mensaje');
        //     $enlace = request('enlace');
        //     $token= env('ULTRAMSG_TOKEN');
        //     $instance_id= env('ULTRAMSG_INSTANCE');
        //     $client = new WhatsAppApi($token,$instance_id);

        //     $to=$telefono; // pablo: +5493517624246, sergio: +5491151106807
        //     $body='Puede ver su receta en el siguiente enlace: '.$enlace; 
        //     $priority=10; // 0: para alta prioridad como mensajes OTP. 5: se utiliza con mensajes generales. 10: Ofertas promocionales no urgentes y notificaciones a tus clientes.
        //     $referenceId="SDK";
        //     $api=$client->sendChatMessage($to,$body,$priority,$referenceId);
        //     return $api;
        // //     https://api.ultramsg.com/instance100689/messages/chat?token=azgbswde53uju73k&to=+543571554212&body=La+API+de+WhatsApp+en+UltraMsg.com+funciona+bien&priority=10
        // //     {
        // //          "sent": "true",
        // //          "message": "ok",
        // //          "id": 2
        // //     }
        // } catch (\Throwable $th) {
        //     return 'Line: '.$th->getLine().' - '.$th->getMessage();
        // }


        
    }
    
    /**
     * Envía un mensaje via SMS para prueba
     */
    public function enviar_mensaje_sms(Request $request)
    {
        // con twilio
        try {
            // Your Account SID and Auth Token from console.twilio.com
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $client = new TwilioClient($sid, $token);

            $telefono = request('telefono');
            $enlace = request('enlace');
            $mensaje = request('mensaje') != null ? request('mensaje').$enlace : 'Puede ver su receta en el siguiente enlace: '.$enlace;

            // SMS
            // Use the Client to make requests to the Twilio REST API
            $resp = $client->messages->create(
                // The number you'd like to send the message to
                $telefono,
                [
                    // A Twilio phone number you purchased at https://console.twilio.com
                    'from' => env('TWILIO_PHONE_NUMBER'),
                    // The body of the text message you'd like to send
                    'body' => $mensaje
                ]
            );
            return $resp;
        } catch (\Throwable $th) {
            return 'Line: '.$th->getLine().' - '.$th->getMessage();
        }
    }
}














