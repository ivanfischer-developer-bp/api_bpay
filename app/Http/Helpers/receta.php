<?php

/**
 * Emite un mensaje fcm a un dispositivo específico.
 */
function emitir_mensaje_fcm($fcm_token, $titulo, $cuerpo, $data = []){
    $url = env('FIREBASE_URL');

    $fields = [
        'to' => $fcm_token,
        'notification' => [
            'title' => $titulo,
            'body' => $cuerpo,
        ],
        'data' => $data,
    ];

    $headers = [
        'Authorization: key=' . env('FIREBASE_SERVER_KEY'),
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    $result = curl_exec($ch);
    if ($result === FALSE) {
        throw new Exception('Curl failed: ' . curl_error($ch));
    }

    curl_close($ch);
    return $result;
}

/**
 * Consulta los medicamentos en innovamed
 */
function get_medicamentos($params, $numero_pagina){
    // $url = 'https://apirecipe.hml.qbitos.com/apirecipe/GetMedicamento/'.$param.'?clienteAppId='.env('QBI2RECIPE_IDFINANCIADOR').'&numeroPagina='.$numero_pagina;
    $url = env('QBI2RECIPE_URLBASE').'/GetMedicamento/'.$params.'?clienteAppId='.env('QBI2RECIPE_CLIENTEAPPID').'&numeroPagina='.$numero_pagina;
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer '.env('QBI2RECIPE_TOKEN')
        ) );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    } finally {
        if(is_resource($ch)){
            curl_close( $ch );
        }
    }
}

/**
 * Obtiene un listado de diagnósticos de https://apirecipe.hml.qbitos.com/apirecipe/GetDiagnostico
 */
function get_diagnosticos($texto){
    try {
        $ch = curl_init();
        $texto = curl_escape($ch, $texto);
        // $url = "https://apirecipe.hml.qbitos.com/apirecipe/GetDiagnostico?text=".$texto;
        $url = env('QBI2RECIPE_URLBASE')."/GetDiagnostico?text=".$texto;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer '.env('QBI2RECIPE_TOKEN')
        ) );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    } catch (\Exception $e) {
        return [
            'data' => null,
            'error' => $e->getCode(),
            'line' => $e->getLine(),
            'message' => $e->getMessage()
        ];
    } finally {
        if(is_resource($ch)){
            curl_close( $ch );
        }
    }
}

/**
 * Obtiene un listado de los financiadores de innovamed
 */
function get_financiadores(){
    try {
        // $url = 'https://apirecipe.hml.qbitos.com/apirecipe/GetFinanciadores?id=317';
        $url = env('QBI2RECIPE_URLBASE').'/GetFinanciadores?id='.env('QBI2RECIPE_CLIENTEAPPID');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer '.env('QBI2RECIPE_TOKEN')
        ) );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    } catch (\Exception $e) {
        return [
            'data' => null,
            'error' => $e->getCode(),
            'line' => $e->getLine(),
            'message' => $e->getMessage()
        ];
    } finally {
        if(is_resource($ch)){
            curl_close( $ch );
        }
    }
}

/**
 * obtiene una receta de api recipe por su numero de receta y numero documento o numero afiliado
 */
function buscar_receta($nro_receta, $dni = null, $nro_afiliado = null, $ambiente){
    try {
        $req = [];
        $req['nroReceta'] = $nro_receta;
        if($dni != null){
            $req['dni'] = $dni;
        }
        if($nro_afiliado != null){
            $req['nroAfiliado'] = $nro_afiliado;
        }
        $ambiente_recipe = $ambiente !== null ? $ambiente : env('AMBIENTE_RECIPE');
        if($ambiente_recipe == 'staging' || $ambiente_recipe == 'local'){
            $req['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID_TESTING');
            $url = env('QBI2RECIPE_URLRECETA_TESTING').'/Get';
            $token = env('QBI2RECIPE_TOKEN_TESTING');
        }else{
            $req['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID');
            $url = env('QBI2RECIPE_URLRECETA').'/Get';
            $token = env('QBI2RECIPE_TOKEN');
        }

        $ch = curl_init();
        if(!$ch){
            return response()->json([
                'error' => 'No se pudo inicializar cURL',
                'url' => $url ?? 'No definida'
            ], 500);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
        ));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        if($curl_error){
            return response()->json([
                'error' => 'CURL Error',
                'message' => $curl_error,
                'http_code' => $http_code
            ], 500);
        }
        
        return json_decode($response);
    } catch (\Exception $e) {
        if(isset($ch) && is_resource($ch)){
            curl_close($ch);
        }
        return response()->json([
            'data' => null,
            'error' => $e->getCode(),
            'line' => $e->getLine(),
            'message' => $e->getMessage()
        ]);
    }
}  

/**
 * anula una receta emitida en api recipe
 */
function anular_receta($id_receta, $ambiente_recipe){
    try {
        $errors = [];

        $ch = curl_init();
        if($ambiente_recipe == 'staging' || $ambiente_recipe == 'local'){
            curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLRECETA_TESTING').'/'.$id_receta);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN_TESTING'),
            ]);
            $clienteAppId = ["clienteAppId" => env('QBI2RECIPE_CLIENTEAPPID_TESTING')];
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($clienteAppId));
        }else{
            curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLRECETA').'/'.$id_receta);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN'),
            ]);
            $clienteAppId = ["clienteAppId" => env('QBI2RECIPE_CLIENTEAPPID')];
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($clienteAppId));
        }

        // curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLRECETA').'/'.$id_receta);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'Content-Type: application/json',
        //     'Authorization: Bearer '.env('QBI2RECIPE_TOKEN'),
        // ]);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["clienteAppId" => env('QBI2RECIPE_CLIENTEAPPID')]));
        $response = curl_exec($ch);
        
        

        $info_curl = [
            'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            'campos' => json_decode(json_encode($clienteAppId)),
            'respuesta' => json_decode($response),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'errno' => curl_errno($ch),
            'error' => curl_error($ch)
        ];
        if (curl_errno($ch)) { 
            array_push($errors, curl_error($ch));
        }
        
        curl_close($ch);

        return [
            'info_curl' => $info_curl,
            'response' => $response,
            'errors' => $errors
        ];
    } catch (\Throwable $th) {
        array_push($errors, 'helper receta Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
        if(isset($ch) && is_resource($ch)){
            curl_close($ch);
        }
        return [
            'info_curl' => null,
            'response' => null,
            'errors' => $errors
        ];
    }
}

/**  
 * Genera una receta con la api de innovamed
 */
function generar_receta_medicamentos($mireceta, $ambiente_recipe)
{
    try{
        $errors = [];
        $ch = curl_init();

        if(file_exists(storage_path('app/public/uploads/firma_medicos').'/'.$mireceta['medico']['nroDoc'].'.png')){
            $mireceta['medico']['firmabase64'] = base64_encode(file_get_contents(storage_path('app/public/uploads/firma_medicos').'/'.$mireceta['medico']['nroDoc'].'.png'));
        }

        // curl_setopt($ch, CURLOPT_URL, 'https://apirecipe.hml.qbitos.com/apirecipe/Receta');
        if($ambiente_recipe == 'staging' || $ambiente_recipe == 'local'){
            $mireceta['subemisor']['nombre'] = env('SUBEMISOR_NOMBRE_TESTING');
            $mireceta['subemisor']['cuit'] = env('SUBEMISOR_CUIT_TESTING');
            $mireceta['subemisor']['direccion'] = env('SUBEMISOR_DIRECCION_TESTING');
            $mireceta['subemisor']['logoBase64'] = base64_encode(file_get_contents(env('SUBEMISOR_LOGO_TESTING')));
            $mireceta['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID_TESTING');
            curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLRECETA_TESTING'));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN_TESTING'),
            ]);
        }else{
            $mireceta['subemisor']['nombre'] = env('SUBEMISOR_NOMBRE');
            $mireceta['subemisor']['cuit'] = env('SUBEMISOR_CUIT');
            $mireceta['subemisor']['direccion'] = env('SUBEMISOR_DIRECCION');
            $mireceta['subemisor']['logoBase64'] = base64_encode(file_get_contents(env('SUBEMISOR_LOGO')));
            $mireceta['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID');
            curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLRECETA'));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN'),
            ]);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mireceta));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);

        $info_curl = [
            'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            'campos' => json_decode(json_encode($mireceta)),
            'respuesta' => json_decode($response)
        ];

        curl_close($ch);

        return [
            'info_curl' => $info_curl,
            'response' => $response,
            'errors' => $errors
        ];

    } catch (\Throwable $th) {
        array_push($errors, 'helper receta Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
        if(isset($ch) && is_resource($ch)){
            curl_close($ch);
        }
        return [
            'info_curl' => null,
            'response' => null,
            'errors' => $errors
        ];
    }

}

/**
 * Genera un certificado médico a través de la API de Innovamed
 */
function generar_receta_certificado($miprescripcion, $ambiente_recipe)
{
    try {
        $errors = [];
        $ch = curl_init();
        if(file_exists(storage_path('app/public/uploads/firma_medicos').'/'.$miprescripcion['medico']['nroDoc'].'.png')){
            $miprescripcion['medico']['firmabase64'] = base64_encode(file_get_contents(storage_path('app/public/uploads/firma_medicos').'/'.$miprescripcion['medico']['nroDoc'].'.png'));
        }
        // curl_setopt($ch, CURLOPT_URL, 'https://apirecipe.hml.qbitos.com/apirecipe/otrasPrescripciones');
        if($ambiente_recipe == 'staging' || $ambiente_recipe == 'local'){
            $miprescripcion['subemisor']['nombre'] = env('SUBEMISOR_NOMBRE_TESTING');
            $miprescripcion['subemisor']['cuit'] = env('SUBEMISOR_CUIT_TESTING');
            $miprescripcion['subemisor']['direccion'] = env('SUBEMISOR_DIRECCION_TESTING');
            $miprescripcion['subemisor']['logoBase64'] = base64_encode(file_get_contents(env('SUBEMISOR_LOGO_TESTING')));
            $miprescripcion['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID_TESTING');
            curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLCERTIFICADOS_TESTING'));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN_TESTING'),
            ]);
        }else{
            $miprescripcion['subemisor']['nombre'] = env('SUBEMISOR_NOMBRE');
            $miprescripcion['subemisor']['cuit'] = env('SUBEMISOR_CUIT');
            $miprescripcion['subemisor']['direccion'] = env('SUBEMISOR_DIRECCION');
            $miprescripcion['subemisor']['logoBase64'] = base64_encode(file_get_contents(env('SUBEMISOR_LOGO')));
            $miprescripcion['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID');
            curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLCERTIFICADOS'));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN'),
            ]);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($miprescripcion));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        
        $info_curl = [
            'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            'campos' => json_decode(json_encode($miprescripcion)),
            'respuesta' => json_decode($response)
        ];

        curl_close($ch);
        return [
            'info_curl' => $info_curl,
            'response' => $response,
            'errors' => $errors
        ];

    } catch (\Throwable $th) {
        array_push($errors, 'helper receta Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
        if(isset($ch) && is_resource($ch)){
            curl_close($ch);
        }
        return [
            'info_curl' => null,
            'response' => null,
            'errors' => $errors
        ];
    }
}

/**
 * genera una receta de prescrición con la api de innovamed
 */
function generar_receta_prescripcion($miprescripcion, $ambiente_recipe)
{
    try {
        $errors = [];

        $ch = curl_init();

        if(file_exists(storage_path('app/public/uploads/firma_medicos').'/'.$miprescripcion['medico']['nroDoc'].'.png')){
            $miprescripcion['medico']['firmabase64'] = base64_encode(file_get_contents(storage_path('app/public/uploads/firma_medicos').'/'.$miprescripcion['medico']['nroDoc'].'.png'));
        }

        if($ambiente_recipe == 'staging' || $ambiente_recipe == 'local'){
            $miprescripcion['subemisor']['nombre'] = env('SUBEMISOR_NOMBRE_TESTING');
            $miprescripcion['subemisor']['cuit'] = env('SUBEMISOR_CUIT_TESTING');
            $miprescripcion['subemisor']['direccion'] = env('SUBEMISOR_DIRECCION_TESTING');
            $miprescripcion['subemisor']['logoBase64'] = base64_encode(file_get_contents(env('SUBEMISOR_LOGO_TESTING')));
            $miprescripcion['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID_TESTING');
            curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLPRACTICAS_TESTING'));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN_TESTING'),
            ]);
        }else{
            $miprescripcion['subemisor']['nombre'] = env('SUBEMISOR_NOMBRE');
            $miprescripcion['subemisor']['cuit'] = env('SUBEMISOR_CUIT');
            $miprescripcion['subemisor']['direccion'] = env('SUBEMISOR_DIRECCION');
            $miprescripcion['subemisor']['logoBase64'] = base64_encode(file_get_contents(env('SUBEMISOR_LOGO')));
            $miprescripcion['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID');
            curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLPRACTICAS')); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN'),
            ]);
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($miprescripcion));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $info_curl = [
            'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            'campos' => json_decode(json_encode($miprescripcion)),
            'respuesta' => json_decode($response)
        ];

        curl_close($ch);
        return [
            'info_curl' => $info_curl,
            'response' => $response,
            'errors' => $errors
        ];

    } catch (\Throwable $th) {
        array_push($errors, 'helper receta Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
        if(isset($ch) && is_resource($ch)){
            curl_close($ch);
        }
        return [
            'info_curl' => null,
            'response' => null,
            'errors' => $errors
        ];
    }
}