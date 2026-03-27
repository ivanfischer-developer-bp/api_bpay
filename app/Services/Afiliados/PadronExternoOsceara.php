<?php

namespace App\Services\Afiliados;

use Illuminate\Support\Facades\Response as FacadeResponse;


class PadronExternoOsceara
{
    /**
     * Consulta el padrón externo de Osceara y retorna el afiliado encontrado
     */
    public function consultar($nro_doc)
    {
        $url = env('URL_PADRON_EXTERNO');
        $username = env('USUARIO_PADRON_EXTERNO');
        $password = env('PASSWORD_PADRON_EXTERNO');

        $nro_doc = (int) $nro_doc;
        $estado = null;
        $info = '';
        $responseLoginData = [];
        $responsePadronData = [];

        if (!is_string($url) || trim($url) === '') {
            return [
                'status' => 'error',
                'estado' => $estado,
                'ambiente' => 'osceara',
                'code' => -12,
                'message' => 'URL del padrón externo no configurada. Revisar URL_PADRON_EXTERNO en el environment',
                'responseLogin' => $responseLoginData,
                'responsePadron' => $responsePadronData,
            ];
        }

        $client = new \GuzzleHttp\Client();
        try {
            $responseLogin = $client->request('POST', $url, [
                'json' => [
                    'ws' => 'Logueo',
                    'args' => [
                        'Usuario' => $username,
                        'Password' => $password
                    ]
                ]
            ]);

            $body = (string) $responseLogin->getBody();
            $responseLoginData = json_decode($body, true);
            if (!is_array($responseLoginData)) {
                $responseLoginData = [];
            }

            if (($responseLoginData['Texto'] ?? null) === 'Login OK') {
                $token = $responseLoginData['Token'] ?? null;

                $responsePadron = $client->request('POST', $url, [
                    'json' => [
                        "ws" =>"AfiliadosObtener",
                        "token"=> $token,
                        "IdAdmSys" => 1,
                        "username"=>"BPAY",
                        "args" => [
                            "ApellidoNombre" => "",
                            "Nombre" => "",
                            "NumDoc" => $nro_doc,
                            "CUIL" => 0,
                            "AfilNumero" => 0,
                            "IdTipoDoc" => null,
                            "IdCanalAcc" => 0,
                            "StrCanalesAcceso" => null,
                            "IdModoIng" => null,
                            "IdPlan" => null,
                            "IdDelegacion" => null,
                            "Estado" => 127,
                            "Sexo" => null,
                            "IdProvincia" => null,
                            "IdZona" => null,
                            "IdPartido" => null,
                            "TipoBeneficiario" => null,
                            "IdSitRevista" => null,
                            "IdNacionalidad" => null,
                            "IdParentesco" => null,
                            "_ModoResult" => 5,
                            "_Fields" => "NumAfiliado,ApellidoNombre,CUIL,Edad,StrTipoDoc,NumDoc,StrSexo,StrEstCivil,Parentesco,FechaNacimiento,DomiCalle,DomiNumero,DomiPiso,DomiDpto,Provincia,Localidad,Telefonos,EMail,CPostal,CanalAcceso,ModoIngreso,Plan,FechaEntregaCarnet,Delegacion,CampoActivo,FechaEstado,NumHCLinica,StrTipoBeneficiario,StrSitRevista,Partido,NumAfiliadoNuevoMasViejo,MotivoAltaBaja,Nombre,RN,RT,ID,EditaTipoBenef,StrSitRevista,SinServicio",
                            "_Pag" => 1,
                            "_RegPag" => 50,
                            "FechaBase" => "2020-05-12T00:00:00",
                            "Buscar" => 0,
                            "Padron" => 1
                        ]
                    ]
                ]);
                
                $body = (string) $responsePadron->getBody();
                $responsePadronData = json_decode($body, true);
                if (!is_array($responsePadronData)) {
                    $responsePadronData = [];
                }
                if($responsePadronData['data'] != null && is_array($responsePadronData['data'])){
                    $estado = $responsePadronData['data'][0]['CampoActivo'] ?? null;
                }else{
                    $info = $responsePadronData['header']['Info'] ?? 'No se obtuvo información del padrón externo';
                }

                return [   
                    'status' => 'success', 
                    'estado' => $estado,
                    'info' => $info,
                    'ambiente' => 'osceara',
                    'code' => 10,
                    'message' => 'Token obtenido: '.$token,
                    'responseLogin' => $responseLoginData,
                    'responsePadron' => $responsePadronData,
                ];
            } else {
                return [
                    'status' => 'error', 
                    'estado' => $estado,
                    'info' => $info,
                    'ambiente' => 'osceara',
                    'code' => -11,
                    'message' => 'Token NO obtenido',
                    'responseLogin' => $responseLoginData,
                    'responsePadron' => $responsePadronData,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'estado' => $estado,
                'info' => $info,
                'ambiente' => 'osceara',
                'code' => -10,
                'message' => 'Error al conectar con el servicio del padrón externo: ' . $e->getMessage(),
                'responseLogin' => $responseLoginData,
                'responsePadron' => $responsePadronData,
            ];
        }
    }

    /**
     * Busca un afiliado en el padrón externo de Osceara y retorna el afiliado encontrado
     */
    public function buscar_afiliado($cuit, $nro_doc)
    {
        $url = env('URL_PADRON_EXTERNO');
        $username = env('USUARIO_PADRON_EXTERNO');
        $password = env('PASSWORD_PADRON_EXTERNO');

        $nro_doc = (int) $nro_doc;
        $afiliado = null;
        $info = '';
        $responseLoginData = [];
        $responsePadronData = [];

        if (!is_string($url) || trim($url) === '') {
            return [
                'status' => 'error',
                'afiliado' => $afiliado,
                'ambiente' => 'osceara',
                'code' => -12,
                'message' => 'URL del padrón externo no configurada. Revisar URL_PADRON_EXTERNO en el environment',
                'responseLogin' => $responseLoginData,
                'responsePadron' => $responsePadronData,
            ];
        }

        $client = new \GuzzleHttp\Client();
        try {
            $responseLogin = $client->request('POST', $url, [
                'json' => [
                    'ws' => 'Logueo',
                    'args' => [
                        'Usuario' => $username,
                        'Password' => $password
                    ]
                ]
            ]);

            $body = (string) $responseLogin->getBody();
            $responseLoginData = json_decode($body, true);
            if (!is_array($responseLoginData)) {
                $responseLoginData = [];
            }

            if (($responseLoginData['Texto'] ?? null) === 'Login OK') {
                $token = $responseLoginData['Token'] ?? null;

                $responsePadron = $client->request('POST', $url, [
                    'json' => [
                        "ws" =>"AfiliadosObtener",
                        "token"=> $token,
                        "IdAdmSys" => 1,
                        "username"=>"BPAY",
                        "args" => [
                            "ApellidoNombre" => "",
                            "Nombre" => "",
                            "NumDoc" => $nro_doc != null ? intVal($nro_doc) : 0,
                            "CUIL" => $cuit != null ? intVal($cuit) : 0,
                            "AfilNumero" => 0,
                            "IdTipoDoc" => null,
                            "IdCanalAcc" => 0,
                            "StrCanalesAcceso" => null,
                            "IdModoIng" => null,
                            "IdPlan" => null,
                            "IdDelegacion" => null,
                            "Estado" => 127,
                            "Sexo" => null,
                            "IdProvincia" => null,
                            "IdZona" => null,
                            "IdPartido" => null,
                            "TipoBeneficiario" => null,
                            "IdSitRevista" => null,
                            "IdNacionalidad" => null,
                            "IdParentesco" => null,
                            "_ModoResult" => 5,
                            "_Fields" => null,
                            "_Pag" => 1,
                            "_RegPag" => 50,
                            "FechaBase" => null,
                            "Buscar" => 0,
                            "Padron" => 1
                        ]
                    ]
                ]);
                
                $body = (string) $responsePadron->getBody();
                $responsePadronData = json_decode($body, true);
                if (!is_array($responsePadronData)) {
                    $responsePadronData = [];
                }
                if($responsePadronData['data'] != null && is_array($responsePadronData['data'])){
                    $afiliado = $responsePadronData['data'][0] ?? null;
                }else{
                    $info = $responsePadronData['header']['Info'] ?? 'No se obtuvo información del padrón externo';
                }

                return [   
                    'status' => 'success', 
                    'afiliado' => $afiliado,
                    'info' => $info,
                    'ambiente' => 'osceara',
                    'code' => 10,
                    'message' => 'Token obtenido: '.$token,
                    'responseLogin' => $responseLoginData,
                    'responsePadron' => $responsePadronData,
                ];
            } else {
                return [
                    'status' => 'error', 
                    'afiliado' => $afiliado,
                    'info' => $info,
                    'ambiente' => 'osceara',
                    'code' => -11,
                    'message' => 'Token NO obtenido',
                    'responseLogin' => $responseLoginData,
                    'responsePadron' => $responsePadronData,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'estado' => $estado,
                'info' => $info,
                'ambiente' => 'osceara',
                'code' => -10,
                'message' => 'Error al conectar con el servicio del padrón externo: ' . $e->getMessage(),
                'responseLogin' => $responseLoginData,
                'responsePadron' => $responsePadronData,
            ];
        }
    }
}