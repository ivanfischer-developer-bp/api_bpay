<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

use App\Models\User;

use Carbon\Carbon;


use App\Http\Controllers\ConexionSpController;

class TachController extends ConexionSpController
{

    /**
     * Obtiene un qr de tach
     */
    public function obtenerQrTach($codigo_interno, $company_id, $description, $quantity, $coseguro, $expiration_datetime, $token_tach){

        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];

        try {
            
            // Realizar llamada POST con cURL
            $ch = curl_init();
            $company_id = env('TACH_COMPANY_ID_' . env('TACH_AMBIENTE'));
            // Preparar datos para la API de TACH
            $body = [
                "company_id" => $company_id,
                "products" => [
                    [
                        "description" => $description,
                        "quantity" => $quantity,
                        "amount" => $coseguro
                    ]
                ],
                "expiration_datetime" => $expiration_datetime
            ];
            // return $body;
            // Configurar headers con el token de autorización
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token_tach,
                'Accept: application/json'
            ];
            // return $this->params['token_tach'];
            
            $url_base = 'TACH_URL_BASE_' . env('TACH_AMBIENTE');
            // return $url_base;
            $full_url = env($url_base) . env('TACH_URL_QR');
            // return $full_url;
            // Log para debug
            Log::channel('tach')->info('Preparando llamada API TACH QR', [
                'url' => $full_url,
                'body' => $body,
                'codigo_interno' => $codigo_interno
            ]);
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $full_url, // https://api.tachwallet.dev/b2b/third-qr/api/v1/qr
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);

            Log::channel('tach')->info('Response API TACH QR', [
                'http_code' => $http_code,
                'response' => $response,
                'curl_error' => $curl_error
            ]);

            if ($response && $http_code == 200) {
                return json_decode($response, true);
            }else{
                return $response;
            }
            
        } catch (\Exception $e) {
            Log::channel('tach')->error('Excepción en llamada TACH QR API: ' . $e->getMessage());
            return null;
            
        }finally{
            curl_close($ch);
        }

    }
}