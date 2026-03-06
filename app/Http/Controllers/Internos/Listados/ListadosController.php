<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;


class ListadosController extends ConexionSpController
{
    /**
     * Obtiene un listado de cada tipo de listado que hay en el sistema consultando 
     * múltiples sps y devolviendo en un array asociativo cada listado
     */
    public function get_all(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/listados/get-all',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        $status = 'fail';
        $message = '';
        $count = 0;
        $data = [];
        $errors = [];
        $this->params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            if($user->hasPermissionTo('consultar listados')){
                // actividades
                array_push($extras['sps'], ['sp_actividad_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_actividad_Select', null));
                $actividades = $this->ejecutar_sp_directo('afiliacion', 'sp_actividad_Select', null);
                array_push($extras['responses'], ['sp_actividad_Select' => $actividades]);
                if(is_array($actividades) && array_key_exists('error', $actividades)){
                    $data_actividades = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $actividades,
                        'errors' => $actividades['error']
                    ];
                }else if(sizeof($actividades) == 0){
                    $data_actividades = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $actividades,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_actividades = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($actividades),
                        'data' => $actividades,
                        'errors' => []
                    ];
                }
                // conceptos
                array_push($extras['sps'], ['sp_concepto_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_concepto_Select', null));
                $conceptos = $this->ejecutar_sp_directo('afiliacion', 'sp_concepto_Select', null);
                array_push($extras['responses'], ['sp_concepto_Select' => $conceptos]);
                if(is_array($conceptos) && array_key_exists('error', $conceptos)){
                    $data_conceptos = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $conceptos,
                        'errors' => $conceptos['error']
                    ];
                }else if(sizeof($conceptos) == 0){
                    $data_conceptos = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $conceptos,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_conceptos = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($conceptos),
                        'data' => $conceptos,
                        'errors' => []
                    ];
                }
                // convenios
                array_push($extras['sps'], ['sp_convenio_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_convenio_Select', null));
                $convenios = $this->ejecutar_sp_directo('afiliacion', 'sp_convenio_Select', null);
                array_push($extras['responses'], ['sp_convenio_Select' => $convenios]);
                if(is_array($convenios) && array_key_exists('error', $convenios)){
                    $data_convenios = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $convenios,
                        'errors' => $convenios['error']
                    ];
                }else if(sizeof($convenios) == 0){
                    $data_convenios = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $convenios,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_convenios = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($convenios),
                        'data' => $convenios,
                        'errors' => []
                    ];
                }
                // criterios agrupacion
                array_push($extras['sps'], ['sp_criterio_agrupacion_select' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'sp_criterio_agrupacion_select', null));
                $criterios_agrupacion = $this->ejecutar_sp_directo('validacion', 'sp_criterio_agrupacion_select', null);
                array_push($extras['responses'], ['sp_criterio_agrupacion_select' => $criterios_agrupacion]);
                if(is_array($criterios_agrupacion) && array_key_exists('error', $criterios_agrupacion)){
                    $data_criterios_agrupacion = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $criterios_agrupacion,
                        'errors' => $criterios_agrupacion['error']
                    ];
                }else if(sizeof($criterios_agrupacion) == 0){
                    $data_criterios_agrupacion = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $criterios_agrupacion,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_criterios_agrupacion = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($criterios_agrupacion),
                        'data' => $criterios_agrupacion,
                        'errors' => []
                    ];
                }
                // // diagnosticos patologias
                // array_push($extras['sps'], ['sp_patologia_cie10_select' => null]);
                // array_push($extras['queries'], $this->get_query('afiliacion', 'sp_patologia_cie10_select', null));
                // $diagnosticos_patologias = $this->ejecutar_sp_directo('afiliacion', 'sp_patologia_cie10_select', null);
                // array_push($extras['responses'], ['sp_patologia_cie10_select' => $diagnosticos_patologias]);
                // if(is_array($diagnosticos_patologias) && array_key_exists('error', $diagnosticos_patologias)){
                //     $data_diagnosticos_patologias = [
                //         'status' => 'fail',
                //         'message' => 'Se produjo un error al realizar la petición',
                //         'count' => -1,
                //         'data' => $diagnosticos_patologias,
                //         'errors' => $diagnosticos_patologias['error']
                //     ];
                // }else if(sizeof($diagnosticos_patologias) == 0){
                //     $data_diagnosticos_patologias = [
                //         'status' => 'empty',
                //         'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                //         'count' => 0,
                //         'data' => $diagnosticos_patologias,
                //         'errors' => []
                //     ];
                // }else{
                //     $count++;
                //     $data_diagnosticos_patologias = [
                //         'status' => 'ok',
                //         'message' => 'Transacción realizada con éxito.',
                //         'count' => sizeof($diagnosticos_patologias),
                //         'data' => $diagnosticos_patologias,
                //         'errors' => []
                //     ];
                // }
                // documentacion afiliado
                array_push($extras['sps'], ['sp_documentacion_select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_documentacion_select', null));
                $documentacion_afiliados = $this->ejecutar_sp_directo('afiliacion', 'sp_documentacion_select', null);
                array_push($extras['responses'], ['sp_documentacion_select' => $documentacion_afiliados]);
                if(is_array($documentacion_afiliados) && array_key_exists('error', $documentacion_afiliados)){
                    $data_documentacion_afiliados = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $documentacion_afiliados,
                        'errors' => $documentacion_afiliados['error']
                    ];
                }else if(sizeof($documentacion_afiliados) == 0){
                    $data_documentacion_afiliados = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $documentacion_afiliados,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_documentacion_afiliados = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($documentacion_afiliados),
                        'data' => $documentacion_afiliados,
                        'errors' => []
                    ];
                }
                // documentacion patologia
                array_push($extras['sps'], ['sp_patologia_documentacion_select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_patologia_documentacion_select', null));
                $documentacion_patologias = $this->ejecutar_sp_directo('afiliacion', 'sp_patologia_documentacion_select', null);
                array_push($extras['responses'], ['sp_patologia_documentacion_select' => $documentacion_patologias]);
                if(is_array($documentacion_patologias) && array_key_exists('error', $documentacion_patologias)){
                    $data_documentacion_patologias = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $documentacion_patologias,
                        'errors' => $documentacion_patologias['error']
                    ];
                }else if(sizeof($documentacion_patologias) == 0){
                    $data_documentacion_patologias = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $documentacion_patologias,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_documentacion_patologias = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($documentacion_patologias),
                        'data' => $documentacion_patologias,
                        'errors' => []
                    ];
                }
                // estados de afiliados ===  estados de grupos
                array_push($extras['sps'], ['sp_estado_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_estado_Select', null));
                $estados_afiliados = $this->ejecutar_sp_directo('afiliacion', 'sp_estado_Select', null);
                array_push($extras['responses'], ['sp_estado_Select' => $estados_afiliados]);
                if(is_array($estados_afiliados) && array_key_exists('error', $estados_afiliados)){
                    $data_estados_afiliados = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $estados_afiliados,
                        'errors' => $estados_afiliados['error']
                    ];
                }else if(sizeof($estados_afiliados) == 0){
                    $data_estados_afiliados = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $estados_afiliados,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_estados_afiliados = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($estados_afiliados),
                        'data' => $estados_afiliados,
                        'errors' => []
                    ];
                }
                // estados civiles
                $sp = 'sp_estado_civil_Select';
                $db = 'validacion';
                array_push($extras['sps'], [$sp => null]);
                array_push($extras['queries'], $this->get_query($db, $sp, null));
                $estados_civiles = $this->ejecutar_sp_directo($db, $sp, null);
                array_push($extras['responses'], [$sp => $estados_civiles]);
                if(is_array($estados_civiles) && array_key_exists('error', $estados_civiles)){
                    $data_estados_civiles = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $estados_civiles,
                        'errors' => $estados_civiles['error']
                    ];
                }else if(sizeof($estados_civiles) == 0){
                    $data_estados_civiles = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $estados_civiles,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_estados_civiles = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($estados_civiles),
                        'data' => $estados_civiles,
                        'errors' => []
                    ];
                }
                // estados preautorizaciones
                $sp = 'sp_estado_preautorizacion_select';
                $db = 'validacion';
                array_push($extras['sps'], [$sp => null]);
                array_push($extras['queries'], $this->get_query($db, $sp, null));
                $estados_preautorizaciones = $this->ejecutar_sp_directo($db, $sp, null);
                array_push($extras['responses'], [$sp => $estados_preautorizaciones]);
                if(is_array($estados_preautorizaciones) && array_key_exists('error', $estados_preautorizaciones)){
                    $data_estados_preautorizaciones = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $estados_preautorizaciones,
                        'errors' => $estados_preautorizaciones['error']
                    ];
                }else if(sizeof($estados_preautorizaciones) == 0){
                    $data_estados_preautorizaciones = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $estados_preautorizaciones,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_estados_preautorizaciones = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($estados_preautorizaciones),
                        'data' => $estados_preautorizaciones,
                        'errors' => []
                    ];
                }
                // estados validaciones
                array_push($extras['sps'], ['AWEB_TraerEstadosValidaciones' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerEstadosValidaciones', null));
                $estados_validaciones = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerEstadosValidaciones', null);
                array_push($extras['responses'], ['AWEB_TraerEstadosValidaciones' => $estados_validaciones]);
                if(is_array($estados_validaciones) && array_key_exists('error', $estados_validaciones)){
                    $data_estados_validaciones = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $estados_validaciones,
                        'errors' => $estados_validaciones['error']
                    ];
                }else if(sizeof($estados_validaciones) == 0){
                    $data_estados_validaciones = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $estados_validaciones,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_estados_validaciones = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($estados_validaciones),
                        'data' => $estados_validaciones,
                        'errors' => []
                    ];
                }
                // frecuencias prestaciones
                array_push($extras['sps'], ['sp_frecuencia_select' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'sp_frecuencia_select', null));
                $frecuencias_prestaciones = $this->ejecutar_sp_directo('validacion', 'sp_frecuencia_select', null);
                array_push($extras['responses'], ['sp_frecuencia_select' => $frecuencias_prestaciones]);
                if(is_array($frecuencias_prestaciones) && array_key_exists('error', $frecuencias_prestaciones)){
                    $data_frecuencias_prestaciones = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $frecuencias_prestaciones,
                        'errors' => $frecuencias_prestaciones['error']
                    ];
                }else if(sizeof($frecuencias_prestaciones) == 0){
                    $data_frecuencias_prestaciones = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $frecuencias_prestaciones,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_frecuencias_prestaciones = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($frecuencias_prestaciones),
                        'data' => $frecuencias_prestaciones,
                        'errors' => []
                    ];
                }
                // gravamenes tipos conceptos
                array_push($extras['sps'], ['sp_gravamen_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_gravamen_Select', null));
                $gravamenes = $this->ejecutar_sp_directo('afiliacion', 'sp_gravamen_Select', null);
                array_push($extras['responses'], ['sp_gravamen_Select' => $gravamenes]);
                if(is_array($gravamenes) && array_key_exists('error', $gravamenes)){
                    $data_gravamenes = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $gravamenes,
                        'errors' => $gravamenes['error']
                    ];
                }else if(sizeof($gravamenes) == 0){
                    $data_gravamenes = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $gravamenes,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_gravamenes = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($gravamenes),
                        'data' => $gravamenes,
                        'errors' => []
                    ];
                }
                // motivos movimiento caja
                array_push($extras['sps'], ['AWEB_TraerMotivosMovimientoCaja' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerMotivosMovimientoCaja', null));
                $motivos_movimiento = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerMotivosMovimientoCaja', null);
                array_push($extras['responses'], ['AWEB_TraerMotivosMovimientoCaja' => $motivos_movimiento]);
                if(is_array($motivos_movimiento) && array_key_exists('error', $motivos_movimiento)){
                    $data_motivos_movimientos_caja = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $motivos_movimiento,
                        'errors' => $motivos_movimiento['error']
                    ];
                }else if(sizeof($motivos_movimiento) == 0){
                    $data_motivos_movimientos_caja = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $motivos_movimiento,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_motivos_movimientos_caja = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($motivos_movimiento),
                        'data' => $motivos_movimiento,
                        'errors' => []
                    ];
                }
                // nacionalidades
                array_push($extras['sps'], ['sp_nacionalidad_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_nacionalidad_Select', null));
                $nacionalidades = $this->ejecutar_sp_directo('afiliacion', 'sp_nacionalidad_Select', null);
                array_push($extras['responses'], ['sp_nacionalidad_Select' => $nacionalidades]);
                if(is_array($nacionalidades) && array_key_exists('error', $nacionalidades)){
                    $data_nacionalidades = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $nacionalidades,
                        'errors' => $nacionalidades['error']
                    ];
                }else if(sizeof($nacionalidades) == 0){
                    $data_nacionalidades = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $nacionalidades,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_nacionalidades = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($nacionalidades),
                        'data' => $nacionalidades,
                        'errors' => []
                    ];
                }
                // obras sociales
                array_push($extras['sps'], ['sp_osocial_select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_osocial_select', null));
                $obras_sociales = $this->ejecutar_sp_directo('afiliacion', 'sp_osocial_select', null);
                array_push($extras['responses'], ['sp_osocial_select' => $obras_sociales]);
                if(is_array($obras_sociales) && array_key_exists('error', $obras_sociales)){
                    $data_obras_sociales = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $obras_sociales,
                        'errors' => $obras_sociales['error']
                    ];
                }else if(sizeof($obras_sociales) == 0){
                    $data_obras_sociales = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $obras_sociales,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_obras_sociales = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($obras_sociales),
                        'data' => $obras_sociales,
                        'errors' => []
                    ];
                }
                // origenes
                array_push($extras['sps'], ['sp_origen_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_origen_Select', null));
                $origenes = $this->ejecutar_sp_directo('afiliacion', 'sp_origen_Select', null);
                array_push($extras['responses'], ['sp_origen_Select' => $origenes]);
                if(is_array($origenes) && array_key_exists('error', $origenes)){
                    $data_origenes = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $origenes,
                        'errors' => $origenes['error']
                    ];
                }else if(sizeof($origenes) == 0){
                    $data_origenes = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $origenes,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_origenes = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($origenes),
                        'data' => $origenes,
                        'errors' => []
                    ];
                }
                // origenes matriculas
                array_push($extras['sps'], ['sp_origen_matricula_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_origen_matricula_Select', null));
                $origenes_matriculas = $this->ejecutar_sp_directo('afiliacion', 'sp_origen_matricula_Select', null);
                array_push($extras['responses'], ['sp_origen_matricula_Select' => $origenes_matriculas]);
                if(is_array($origenes_matriculas) && array_key_exists('error', $origenes_matriculas)){
                    $data_origenes_matriculas = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $origenes_matriculas,
                        'errors' => $origenes_matriculas['error']
                    ];
                }else if(sizeof($origenes_matriculas) == 0){
                    $data_origenes_matriculas = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $origenes_matriculas,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_origenes_matriculas = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($origenes_matriculas),
                        'data' => $origenes_matriculas,
                        'errors' => []
                    ];
                }
                // parentescos
                array_push($extras['sps'], ['sp_parentesco_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_parentesco_Select', null));
                $parentescos = $this->ejecutar_sp_directo('afiliacion', 'sp_parentesco_Select', null);
                array_push($extras['responses'], ['sp_parentesco_Select' => $parentescos]);
                if(is_array($parentescos) && array_key_exists('error', $parentescos)){
                    $data_parentescos = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $parentescos,
                        'errors' => $parentescos['error']
                    ];
                }else if(sizeof($parentescos) == 0){
                    $data_parentescos = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $parentescos,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_parentescos = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($parentescos),
                        'data' => $parentescos,
                        'errors' => []
                    ];
                }
                // patologias
                array_push($extras['sps'], ['sp_patologia_select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_patologia_select', null));
                $patologias = $this->ejecutar_sp_directo('afiliacion', 'sp_patologia_select', null);
                array_push($extras['responses'], ['sp_patologia_select' => $patologias]);
                if(is_array($patologias) && array_key_exists('error', $patologias)){
                    $data_patologias = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $patologias,
                        'errors' => $patologias['error']
                    ];
                }else if(sizeof($patologias) == 0){
                    $data_patologias = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $patologias,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_patologias = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($patologias),
                        'data' => $patologias,
                        'errors' => []
                    ];
                }
                // planes
                array_push($extras['sps'], ['sp_plan_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_plan_Select', null));
                $planes = $this->ejecutar_sp_directo('afiliacion', 'sp_plan_Select', null);
                array_push($extras['responses'], ['sp_plan_Select' => $planes]);
                if(is_array($planes) && array_key_exists('error', $planes)){
                    $data_planes = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $planes,
                        'errors' => $planes['error']
                    ];
                }else if(sizeof($planes) == 0){
                    $data_planes = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $planes,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_planes = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($planes),
                        'data' => $planes,
                        'errors' => []
                    ];
                }
                // promotores
                array_push($extras['sps'], ['sp_promotor_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_promotor_Select', null));
                $promotores = $this->ejecutar_sp_directo('afiliacion', 'sp_promotor_Select', null);
                array_push($extras['responses'], ['sp_promotor_Select' => $promotores]);
                if(is_array($promotores) && array_key_exists('error', $promotores)){
                    $data_promotores = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $promotores,
                        'errors' => $promotores['error']
                    ];
                }else if(sizeof($promotores) == 0){
                    $data_promotores = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $promotores,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_promotores = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($promotores),
                        'data' => $promotores,
                        'errors' => []
                    ];
                }
                // // provincias
                // array_push($extras['sps'], ['sp_provincia_Select' => null]);
                // array_push($extras['queries'], $this->get_query('afiliacion', 'sp_provincia_Select', null));
                // $provincias = $this->ejecutar_sp_directo('afiliacion', 'sp_provincia_Select', null);
                // array_push($extras['responses'], ['sp_provincia_Select' => $provincias]);
                // if(is_array($provincias) && array_key_exists('error', $provincias)){
                //     $data_provincias = [
                //         'status' => 'fail',
                //         'message' => 'Se produjo un error al realizar la petición',
                //         'count' => -1,
                //         'data' => $provincias,
                //         'errors' => $provincias['error']
                //     ];
                // }else if(sizeof($provincias) == 0){
                //     $data_provincias = [
                //         'status' => 'empty',
                //         'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                //         'count' => 0,
                //         'data' => $provincias,
                //         'errors' => []
                //     ];
                // }else{
                //     $count++;
                //     $data_provincias = [
                //         'status' => 'ok',
                //         'message' => 'Transacción realizada con éxito.',
                //         'count' => sizeof($provincias),
                //         'data' => $provincias,
                //         'errors' => []
                //     ];
                // }
                // rango_edades
                // array_push($extras['sps'], ['sp_informe_rango_importe' => null]);
                // array_push($extras['queries'], $this->get_query('afiliacion', 'sp_informe_rango_importe', null));
                // $rango_edades = $this->ejecutar_sp_directo('afiliacion', 'sp_informe_rango_importe', null);
                // array_push($extras['responses'], ['sp_informe_rango_importe' => $rango_edades]);
                // if(is_array($rango_edades) && array_key_exists('error', $rango_edades)){
                //     $data_rango_edades = [
                //         'status' => 'fail',
                //         'message' => 'Se produjo un error al realizar la petición',
                //         'count' => -1,
                //         'data' => $rango_edades,
                //         'errors' => $rango_edades['error']
                //     ];
                // }else if(sizeof($rango_edades) == 0){
                //     $data_rango_edades = [
                //         'status' => 'empty',
                //         'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                //         'count' => 0,
                //         'data' => $rango_edades,
                //         'errors' => []
                //     ];
                // }else{
                //     $count++;
                //     $data_rango_edades = [
                //         'status' => 'ok',
                //         'message' => 'Transacción realizada con éxito.',
                //         'count' => sizeof($rango_edades),
                //         'data' => $rango_edades,
                //         'errors' => []
                //     ];
                // }
                // sucursales
                array_push($extras['sps'], ['AWEB_TraerSucursales' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerSucursales', null));
                $sucursales = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerSucursales', null);
                array_push($extras['responses'], ['AWEB_TraerSucursales' => $sucursales]);
                if(is_array($sucursales) && array_key_exists('error', $sucursales)){
                    $data_sucursales = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $sucursales,
                        'errors' => $sucursales['error']
                    ];
                }else if(sizeof($sucursales) == 0){
                    $data_sucursales = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $sucursales,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_sucursales = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($sucursales),
                        'data' => $sucursales,
                        'errors' => []
                    ];
                }
                // tipos de afip
                array_push($extras['sps'], ['sp_afip_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afip_Select', null));
                $tipos_afip = $this->ejecutar_sp_directo('afiliacion', 'sp_afip_Select', null);
                array_push($extras['responses'], ['sp_afip_Select' => $tipos_afip]);
                if(is_array($tipos_afip) && array_key_exists('error', $tipos_afip)){
                    $data_tipos_afip = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipos_afip,
                        'errors' => $tipos_afip['error']
                    ];
                }else if(sizeof($tipos_afip) == 0){
                    $data_tipos_afip = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipos_afip,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_afip = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipos_afip),
                        'data' => $tipos_afip,
                        'errors' => []
                    ];
                }
                // tipos de bajas
                array_push($extras['sps'], ['sp_tipo_baja_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_baja_Select', null));
                $tipos_baja = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_baja_Select', null);
                array_push($extras['responses'], ['sp_tipo_baja_Select' => $tipos_baja]);
                if(is_array($tipos_baja) && array_key_exists('error', $tipos_baja)){
                    $data_tipos_baja = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipos_baja,
                        'errors' => $tipos_baja['error']
                    ];
                }else if(sizeof($tipos_baja) == 0){
                    $data_tipos_baja = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipos_baja,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_baja = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipos_baja),
                        'data' => $tipos_baja,
                        'errors' => []
                    ];
                }
                // tipos de conceptos
                array_push($extras['sps'], ['sp_tipo_concepto_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_concepto_Select', null));
                $tipos_conceptos = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_concepto_Select', null);
                array_push($extras['responses'], ['sp_tipo_concepto_Select' => $tipos_conceptos]);
                if(is_array($tipos_conceptos) && array_key_exists('error', $tipos_conceptos)){
                    $data_tipos_conceptos = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipos_conceptos,
                        'errors' => $tipos_conceptos['error']
                    ];
                }else if(sizeof($tipos_conceptos) == 0){
                    $data_tipos_conceptos = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipos_conceptos,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_conceptos = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipos_conceptos),
                        'data' => $tipos_conceptos,
                        'errors' => []
                    ];
                }
                // tipos de contactos
                array_push($extras['sps'], ['sp_tipo_contacto_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_contacto_Select', null));
                $tipos_contactos = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_contacto_Select', null);
                array_push($extras['responses'], ['sp_tipo_contacto_Select' => $tipos_contactos]);
                if(is_array($tipos_contactos) && array_key_exists('error', $tipos_contactos)){
                    $data_tipos_contactos = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipos_contactos,
                        'errors' => $tipos_contactos['error']
                    ];
                }else if(sizeof($tipos_contactos) == 0){
                    $data_tipos_contactos = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipos_contactos,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_contactos = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipos_contactos),
                        'data' => $tipos_contactos,
                        'errors' => []
                    ];
                }
                // tipos de documentos
                array_push($extras['sps'], ['sp_tipo_doc_Select' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'sp_tipo_doc_Select', null));
                $tipo_doc = $this->ejecutar_sp_directo('validacion', 'sp_tipo_doc_Select', null);
                array_push($extras['responses'], ['sp_tipo_doc_Select' => $tipo_doc]);
                if(is_array($tipo_doc) && array_key_exists('error', $tipo_doc)){
                    $data_tipos_doc = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipo_doc,
                        'errors' => $tipo_doc['error']
                    ];
                }else if(sizeof($tipo_doc) == 0){
                    $data_tipos_doc = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipo_doc,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_doc = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipo_doc),
                        'data' => $tipo_doc,
                        'errors' => []
                    ];
                }
                // tipos de domicilios
                array_push($extras['sps'], ['sp_tipo_domicilio_Select' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'sp_tipo_domicilio_Select', null));
                $tipo_domicilio = $this->ejecutar_sp_directo('validacion', 'sp_tipo_domicilio_Select', null);
                array_push($extras['responses'], ['sp_tipo_domicilio_Select' => $tipo_domicilio]);
                if(is_array($tipo_domicilio) && array_key_exists('error', $tipo_domicilio)){
                    $data_tipos_domicilio = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipo_domicilio,
                        'errors' => $tipo_domicilio['error']
                    ];
                }else if(sizeof($tipo_domicilio) == 0){
                    $data_tipos_domicilio = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipo_domicilio,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_domicilio = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipo_domicilio),
                        'data' => $tipo_domicilio,
                        'errors' => []
                    ];
                }
                // tipos de factura
                array_push($extras['sps'], ['sp_tipo_factura_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_factura_Select', null));
                $tipos_factura = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_factura_Select', null);
                array_push($extras['responses'], ['sp_tipo_factura_Select' => $tipos_factura]);
                if(is_array($tipos_factura) && array_key_exists('error', $tipos_factura)){
                    $data_tipos_factura = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipos_factura,
                        'errors' => $tipos_factura['error']
                    ];
                }else if(sizeof($tipos_factura) == 0){
                    $data_tipos_factura = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipos_factura,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_factura = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipos_factura),
                        'data' => $tipos_factura,
                        'errors' => []
                    ];
                }
                // tipos de facturacion
                array_push($extras['sps'], ['sp_tipo_facturacion_select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_facturacion_select', null));
                $tipos_facturacion = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_facturacion_select', null);
                array_push($extras['responses'], ['sp_tipo_facturacion_select' => $tipos_facturacion]);
                if(is_array($tipos_facturacion) && array_key_exists('error', $tipos_facturacion)){
                    $data_tipos_facturacion = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipos_facturacion,
                        'errors' => $tipos_facturacion['error']
                    ];
                }else if(sizeof($tipos_facturacion) == 0){
                    $data_tipos_facturacion = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipos_facturacion,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_facturacion = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipos_facturacion),
                        'data' => $tipos_facturacion,
                        'errors' => []
                    ];
                }
                // tipos de internaciones
                array_push($extras['sps'], ['AWEB_TraerTiposInternacion' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerTiposInternacion', null));
                $tipos_internaciones = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerTiposInternacion', null);
                array_push($extras['responses'], ['AWEB_TraerTiposInternacion' => $tipos_internaciones]);
                if(is_array($tipos_internaciones) && array_key_exists('error', $tipos_internaciones)){
                    $data_tipos_internaciones = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipos_internaciones,
                        'errors' => $tipos_internaciones['error']
                    ];
                }else if(sizeof($tipos_internaciones) == 0){
                    $data_tipos_internaciones = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipos_internaciones,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_internaciones = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipos_internaciones),
                        'data' => $tipos_internaciones,
                        'errors' => []
                    ];
                }
                // tipos de prestacion
                array_push($extras['sps'], ['sp_tipo_prestacion_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_prestacion_Select', null));
                $tipos_prestacion = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_prestacion_Select', null);
                array_push($extras['responses'], ['sp_tipo_prestacion_Select' => $tipos_prestacion]);
                if(is_array($tipos_prestacion) && array_key_exists('error', $tipos_prestacion)){
                    $data_tipos_prestacion = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $tipos_prestacion,
                        'errors' => $tipos_prestacion['error']
                    ];
                }else if(sizeof($tipos_prestacion) == 0){
                    $data_tipos_prestacion = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $tipos_prestacion,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_tipos_prestacion = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($tipos_prestacion),
                        'data' => $tipos_prestacion,
                        'errors' => []
                    ];
                }
                // usuarios app sqlserver
                array_push($extras['sps'], ['AWEB_TraerUsuarios' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerUsuarios', null));
                $usuarios_sqlserver = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerUsuarios', null);
                array_push($extras['responses'], ['AWEB_TraerUsuarios' => $usuarios_sqlserver]);
                if(is_array($usuarios_sqlserver) && array_key_exists('error', $usuarios_sqlserver)){
                    $data_usuarios_sqlserver = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $usuarios_sqlserver,
                        'errors' => $usuarios_sqlserver['error']
                    ];
                }else if(sizeof($usuarios_sqlserver) == 0){
                    $data_usuarios_sqlserver = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $usuarios_sqlserver,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_usuarios_sqlserver = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($usuarios_sqlserver),
                        'data' => $usuarios_sqlserver,
                        'errors' => []
                    ];
                }
                // zonas_domicilios
                array_push($extras['sps'], ['sp_zona_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_zona_Select', null));
                $zonas_domicilios = $this->ejecutar_sp_directo('afiliacion', 'sp_zona_Select', null);
                array_push($extras['responses'], ['sp_zona_Select' => $zonas_domicilios]);
                if(is_array($zonas_domicilios) && array_key_exists('error', $zonas_domicilios)){
                    $data_zonas_domicilios = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $zonas_domicilios,
                        'errors' => $zonas_domicilios['error']
                    ];
                }else if(sizeof($zonas_domicilios) == 0){
                    $data_zonas_domicilios = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $zonas_domicilios,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_zonas_domicilios = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($zonas_domicilios),
                        'data' => $zonas_domicilios,
                        'errors' => []
                    ];
                }
                // zonas_prestacionales
                array_push($extras['sps'], ['AWEB_TraerZonasPrestacionales' => null]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerZonasPrestacionales', null));
                $zonas_prestacionales = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerZonasPrestacionales', null);
                array_push($extras['responses'], ['AWEB_TraerZonasPrestacionales' => $zonas_prestacionales]);
                if(is_array($zonas_prestacionales) && array_key_exists('error', $zonas_prestacionales)){
                    $data_zonas_prestacionales = [
                        'status' => 'fail',
                        'message' => 'Se produjo un error al realizar la petición',
                        'count' => -1,
                        'data' => $zonas_prestacionales,
                        'errors' => $zonas_prestacionales['error']
                    ];
                }else if(sizeof($zonas_prestacionales) == 0){
                    $data_zonas_prestacionales = [
                        'status' => 'empty',
                        'message' => 'No se encontraron registros que coincidan con los parámetros de búsqueda',
                        'count' => 0,
                        'data' => $zonas_prestacionales,
                        'errors' => []
                    ];
                }else{
                    $count++;
                    $data_zonas_prestacionales = [
                        'status' => 'ok',
                        'message' => 'Transacción realizada con éxito.',
                        'count' => sizeof($zonas_prestacionales),
                        'data' => $zonas_prestacionales,
                        'errors' => []
                    ];
                }

                if($count == 0){
                    $status = 'empty';
                    $message = 'No se encontraron registros';
                }
                if(sizeof($errors) > 0){
                    $status = 'fail';
                    $message = 'Se detectaron errores';
                }else{
                    $status = 'ok';
                    $message = 'Registros encontrados';
                }
                
                $data = [
                    'actividades' => $data_actividades,
                    'conceptos' => $data_conceptos,
                    'convenios' => $data_convenios,
                    'criterios_agrupacion' => $data_criterios_agrupacion,
                    // 'diagnosticos_patologias' => $data_diagnosticos_patologias,
                    'documentacion_afiliados' => $data_documentacion_afiliados,
                    'documentacion_patologias' => $data_documentacion_patologias,
                    'estados_afiliados' => $data_estados_afiliados,
                    'estados_civiles' => $data_estados_civiles,
                    'estados_preautorizaciones' => $data_estados_preautorizaciones,
                    'estados_validaciones' => $data_estados_validaciones,
                    'frecuencias_prestaciones' => $data_frecuencias_prestaciones,
                    'gravamenes' => $data_gravamenes,
                    'motivos_movimientos_caja' => $data_motivos_movimientos_caja,
                    'nacionalidades' => $data_nacionalidades,
                    'obras_sociales' => $data_obras_sociales,
                    'origenes' => $data_origenes,
                    'origenes_matriculas' => $data_origenes_matriculas,
                    'parentescos' => $data_parentescos,
                    'patologias' => $data_patologias,
                    'planes' => $data_planes,
                    'promotores' => $data_promotores,
                    // 'provincias' => $data_provincias,
                    // 'rango_edades' => [], // $data_rango_edades,
                    'sucursales' => $data_sucursales,
                    'tipos_afip' => $data_tipos_afip,
                    'tipos_baja' => $data_tipos_baja,
                    'tipos_conceptos' => $data_tipos_conceptos,
                    'tipos_contactos' => $data_tipos_contactos,
                    'tipos_documentos' => $data_tipos_doc,
                    'tipos_domicilios' => $data_tipos_domicilio,
                    'tipos_facturacion' => $data_tipos_facturacion,
                    'tipos_factura' => $data_tipos_factura,
                    'tipos_internaciones' => $data_tipos_internaciones,
                    'tipos_prestacion' => $data_tipos_prestacion,
                    'usuarios_sqlserver' => $data_usuarios_sqlserver,
                    'zonas_domicilios' => $data_zonas_domicilios,
                    'zonas_prestacionales' => $data_zonas_prestacionales
                ];

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR LISTADOS';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user != null ? $logged_user : null,
            ]);
        }
    }

    /**
     * Lista actividades 
     * Tiene su propio controlador ABMActividadController
     * Se deja por motivos de retrocompatibilidad
     */
    public function listar_actividades(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/actividades';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_actividad_Select';
        return $this->ejecutar_sp_simple();
    }

    // convenios se llevó a su propio controllador ABMConvniosController

    /**
     * Listar conceptos
     */
    public function listar_conceptos(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/conceptos';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_concepto_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Listar criterios_agrupación
     */
    public function listar_criterios_agrupacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/criterios-agrupacion';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'sp_criterio_agrupacion_select';
        return $this->ejecutar_sp_simple();
    }

    // documentacion de afiliados se llevó a su propio controlador ABMDocumentacionAfiliadoController

    // documentacion patologia se llevó al controlador ABMPatologiaController

    /**
     * Retorna un listado de estados de los afiliados.
     */
    public function listar_estados_afiliados(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/estados-afiliados';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_estado_Select';
        return $this->ejecutar_sp_simple();
    } 

    /**
     * Retorna un listado de estados civiles.
     * Tiene su propio controlador ABMEstadoCivilController
     * Se deja por motivos de retrocompatibilidad
     */
    public function listar_estados_civiles(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/estados-civiles';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_estado_civil_Select';
        return $this->ejecutar_sp_simple();
    } 

    /**
     * Retorna un listado de estados de validacion.
     */
    public function listar_estados_preautorizaciones(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/estados-preautorizaciones';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'sp_estado_preautorizacion_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Retorna un listado de estados de validacion.
     */
    public function listar_estados_validaciones(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/estados-validaciones';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'AWEB_TraerEstadosValidaciones';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Retorna un listado de estados de validacion.
     */
    public function listar_frecuencias_prestaciones(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/frecuencias-prestaciones';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'sp_frecuencia_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Lista motivos de movimientos de caja
     */
    public function listar_motivos_movimientos_caja(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/motivos-movimientos-caja';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'AWEB_TraerMotivosMovimientoCaja';
        if(request('codigo') != null){
            $this->params['codigo'] = request('codigo');
            $this->params_sp['id_movimiento_caja_motivo'] = request('codigo');
        }
        if(request('descripcion') != null){
            $this->params['descripcion'] = request('descripcion');
            $this->params_sp['descripcion'] = request('descripcion');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Lista nacionalidades
     */
    public function listar_nacionalidades(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/nacionalidades';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_nacionalidad_Select';
        return $this->ejecutar_sp_simple();
    }

    // obras sociales se llevó a su propio controlador ABMObraSocialController

    // origenes afiliado se llevó a su propio controlador ABMOrigenAfiliadoController

    // origenes matricula se llevó a su propio controlador ABMOrigenMatriculaController

    /**
     * Lista parentescos
     */
    public function listar_parentescos(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/parentescos';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_parentesco_Select';
        return $this->ejecutar_sp_simple();
    }

    // planes se llevó a su propio controlador ABMPlanController

    // promotores se llevó a su propio controlador ABMPromotorController

    /**
     * Lista provincias
     */
    public function listar_provincias(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/provincias';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_provincia_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Lista rango de edades
     */
    public function listar_rango_edades(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/rango-edades';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_informe_rango_importe';
        if(request('id_plan') != null){
            $this->params['id_plan'] = request('id_plan');
            $this->params_sp['id_plan'] = request('id_plan');
        }
        if(request('id_parentesco') != null){
            $this->params['id_parentesco'] = request('id_parentesco');
            $this->params_sp['id_parentesco'] = request('id_parentesco');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Retorna un listado de sucursales
     */
    public function listar_sucursales(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/sucursales';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'AWEB_TraerSucursales';
        if(request('id_empresa') != null){
            $this->params['id_empresa'] = request('id_empresa');
            $this->params_sp['id_empresa'] = request('id_empresa');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Listar tipos de documentos (facturas) de afip
     */
    public function listar_tipos_afip(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipos-afip';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_afip_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Retorna un listado de los tipos de baja
     */
    public function listar_tipos_baja(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipos-baja';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_baja_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Retorna un listado de tipos de contactos.
     * Tiene su propio controlador ABMTipoContactoController
     * Se deja por motivos de retrocompatibilidad
     */
    public function listar_tipos_contactos(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipos-contactos';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_contacto_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Retorna un listado de tipos de documentos.
     */
    public function listar_tipos_documentos(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipos-documentos';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'sp_tipo_doc_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Lista tipos de domicilios
     * Tiene su propio controlador ABMTipoDomicilioController
     * Se deja por motivos de retrocompatibilidad
     */
    public function listar_tipos_domicilios(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  
        $this->url = '/int/listados/tipos-domicilios';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_domicilio_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Listar tipos de facturacion
     */
    public function listar_tipos_facturacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipos-facturacion';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_facturacion_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Listar tipos de internaciones
     */
    public function listar_tipos_internaciones(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipos-internaciones';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'AWEB_TraerTiposInternacion';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Listar tipos de prestacion
     */
    public function listar_tipos_prestacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipos-prestacion';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_prestacion_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Retorna un listado de usuarios
     */
    public function listar_usuarios_sqlserver(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  
        $this->url = '/int/listados/usuarios-sqlserver';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'AWEB_TraerUsuarios';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Lista zonas de domicilios
     */
    public function listar_zonas_domicilios(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/zonas-domicilios';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_zona_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Lista zonas prestacionales
     */
    public function listar_zonas_prestacionales(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/zonas-prestacionales';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'validacion'; 
        $this->sp = 'AWEB_TraerZonasPrestacionales';
        if(request('id_empresa') != null){
            $this->params['id_empresa'] = request('id_empresa');
            $this->params_sp['id_empresa'] = request('id_empresa');
        }
        if(request('id_sucursal') != null){
            $this->params['id_sucursal'] = request('id_sucursal');
            $this->params_sp['id_sucursal'] = request('id_sucursal');
        }
        return $this->ejecutar_sp_simple();
    }

    

    
    public function modelo_simple(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = '';
        if(request('') != null){
            $this->params[''] = request('');
            $this->params_sp[''] = request('');
        }
        return $this->ejecutar_sp_simple();

    }

    public function modelo(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/listados/',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $data = null;
        $errors = [];
        $this->params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {

            if($user->hasPermissionTo('consultar listados')){

                array_push($extras['sps'], ['sp_' => $this->params]);
                array_push($extras['queries'], $this->get_query('', 'sp_', null));
                $resp = $this->ejecutar_sp_directo('', 'sp_', $this->params);
                array_push($extras['responses'], ['sp_' => $resp]);
                $data = $resp;
                $status = 'ok';
                $count = sizeof($resp);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 

            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR LISTADOS';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user != null ? $logged_user : null,
            ]);
        }
    }
}
