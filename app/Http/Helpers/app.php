<?php

/**
 * Retorna una cadena sin acentos ni caracteres especiales
 * @return string
 */
function eliminar_acentos($cadena){
		
    //Reemplazamos la A y a
    $cadena = str_replace(
    array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
    array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
    $cadena
    );

    //Reemplazamos la E y e
    $cadena = str_replace(
    array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
    array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
    $cadena );

    //Reemplazamos la I y i
    $cadena = str_replace(
    array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
    array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
    $cadena );

    //Reemplazamos la O y o
    $cadena = str_replace(
    array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
    array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
    $cadena );

    //Reemplazamos la U y u
    $cadena = str_replace(
    array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
    array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
    $cadena );

    //Reemplazamos la N, n, C y c
    $cadena = str_replace(
    array('Ñ', 'ñ', 'Ç', 'ç'),
    array('N', 'n', 'C', 'c'),
    $cadena
    );
    
    return $cadena;
}

/**
 * Retorna una cadena sin acentos ni caracteres especiales
 * @return string
 */
function reemplazar_espacios($cadena, $reemplazo = '%20'){
    return str_replace(' ', $reemplazo, $cadena);
}

/**
 * Retorna una representación en letras de un número dado
 * śolo para números enteros menores a veinte
 * @return string
 */
function cantidad_a_letras($cantidad, $mostrar_parentesis = true){
    if($cantidad != null){
        if($mostrar_parentesis){
            $cant = '(';
        }else{
            $cant = '';
        }

        switch ($cantidad) {
            case 1:
                $cant = $cant.'UNO';
                break;
            case 2:
                 $cant = $cant.'DOS';
                break;
            case 3:
                $cant = $cant.'TRES';
                break;
            case 4:
                $cant = $cant.'CUATRO';
                break;
            case 5:
                $cant = $cant.'CINCO';
                break;
            case 6:
                $cant = $cant.'SEIS';
                break;
            case 7:
                $cant = $cant.'SIETE';
                break;
            case 8:
                $cant = $cant.'OCHO';
                break;
            case 9:
                $cant = $cant.'NUEVE';
                break;
            case 10:
                $cant = $cant.'DIEZ';
                break;
            case 11:
                $cant = $cant.'ONCE';
                break;
            case 12:
                $cant = $cant.'DOCE';
                break;
            case 13:
                $cant = $cant.'TRECE';
                break;
            case 14:
                $cant = $cant.'CATORCE';
                break;
            case 15:
                $cant = $cant.'QUINCE';
                break;
            case 16:
                $cant = $cant.'DIECISEIS';
                break;
            case 17:
                $cant = $cant.'DIECISIETE';
                break;
            case 18:
                $cant = $cant.'DIECIOCHO';
                break;
            case 19:
                $cant = $cant.'DIECINUEVE';
                break;
            case 20:
                $cant = $cant.'VEINTE';
                break;
            default:
                $cant = '';
                break;
        }
        if($mostrar_parentesis){
            $cant = $cant.')';
        }
        return $cant;
    }else{
        return '';
    }
}

/**
* Devuelve el tipo de usuario logueado
* @return string
*/
function get_traer_tipo_usuario($user) {
    if($user->id_prestador != null){
        return 'prestador';
    }
    // return Session::get('usuario.tipo');
}

/**
* Devuelve el tipo de usuario logueado
* @return string
*/
function get_traer_id_prestador() {
    $id_prestador = Session::get('usuario.id_prestador');
    if ( empty($id_prestador) )
        $id_prestador = '';
    return $id_prestador;
}

/**
* Devuelve si es un usuario prestador
* @param boolean $return_as_number Si debe devolver la respuesta en formato numérico (default: FALSE)
* @param boolean|int
*/
function get_es_prestador($user, $return_as_number = FALSE) {

    if ( $return_as_number )
    {
        return get_traer_tipo_usuario($user) == 'prestador' ? 1 : 0;
    }
    else
    {
        return get_traer_tipo_usuario($user) == 'prestador' ? TRUE : FALSE;
    }
}

/**
* Devuelve si es un usuario supervisor
* @param boolean $return_as_number Si debe devolver la respuesta en formato numérico (default: FALSE)
* @param boolean|int
*/
function get_es_supervisor($return_as_number = FALSE) {

    if ( $return_as_number )
    {
        return get_traer_tipo_usuario() == 'supervisor' ? 1 : 0;
    }
    else
    {
        return get_traer_tipo_usuario() == 'supervisor' ? TRUE : FALSE;
    }
}

/**
* Devuelve si es un usuario normal
* @param boolean $return_as_number Si debe devolver la respuesta en formato numérico (default: FALSE)
* @param boolean|int
*/
function get_es_usuario($return_as_number = FALSE) {

    if ( $return_as_number )
    {
        return get_traer_tipo_usuario() == 'usuario' ? 1 : 0;
    }
    else
    {
        return get_traer_tipo_usuario() == 'usuario' ? TRUE : FALSE;
    }
}

/**
* Devuelve el ID de la empresa propia del usuario
* @param int $id_usuario
* @return int $id_empresa
*/
// function get_traer_id_empresa_propia() {

//     //  crea objeto
//     $empresa_propia_obj = new App\Models\EmpresaPropia;

//     //  devuelve el id empresa, si existe
//     if ( $empresa_propia = $empresa_propia_obj->traer() )
//         return $empresa_propia[0]->id_empresa;
// }

/**
* Devuelve la empresa empresa propia
* @return mixed OBJECT|FALSE
*/
// function get_traer_empresa_propia($traer_sucursales = FALSE, $id_contrato = NULL, $connection = NULL) {

//     //  crea la variable
//     $empresa_propia = FALSE;

//     //  crea objeto
//     $empresa_propia_obj = new App\Models\EmpresaPropia($id_contrato, $connection);

//     //  devuelve el id empresa, si existe
//     if ( $empresa_propia = $empresa_propia_obj->traer() )
//     {
//         //  obtiene la empresa
//         $empresa_propia = (array)$empresa_propia[0];

//         //  si tiene logo
//         if ( !empty($empresa_propia['logo']) )
//         {
//             //  obtiene el path del logo
//             $logo_path = get_images_path($empresa_propia['logo']);

//             //  si el logo existe
//             if ( File::exists($logo_path) )
//             {
//                 //  establece la url y el path
//                 $empresa_propia['logo_url'] = get_images_url($empresa_propia['logo']);
//                 $empresa_propia['logo_path'] = $logo_path;
//             }
//         }

//         //  si tiene que devolver las sucursales, obtiene las sucursales
//         if ( $traer_sucursales )
//             $empresa_propia['sucursales'] = $empresa_propia_obj->ejecutar_sp('sp_sucursal_Select', ['p_id_empresa' => $empresa_propia['id_empresa'] ]);
//     }

//     return $empresa_propia;
// }

/**
* Devuelve la URL del logo de la empresa propia
* @return mixed string|FALSE
*/
// function get_url_imagen_empresa_propia()
// {
//     $url = FALSE;
//     $empresa_propia = get_traer_empresa_propia();
//     if ( $empresa_propia )
//         return $empresa_propia['logo_url'];
// }

/**
* Devuelve el Path del logo de la empresa propia
* @return mixed string|FALSE
*/
// function get_path_imagen_empresa_propia()
// {
//     $url = FALSE;
//     $empresa_propia = get_traer_empresa_propia();
//     if ( $empresa_propia )
//         return $empresa_propia['logo_path'];
// }

/**
 * Devuelve el nombre del contrato
 */
// function get_slug_contrato_actual() {
//     if ( $sesion_usuario = Session::get('usuario') )
//         if ( !empty($sesion_usuario['n_contrato']) )
//             return $sesion_usuario['n_contrato'];
// }

/**
* Calcula la edad de una persona
* @param mixed $params Parámetros (Obligatorios: fec_baja, id_vigencia, id_usuario | Opcionales: id_tipo_baja, n_baja)
* @return int|FALSE ID de la Baja o FALSE cuando hay error
*/
function get_edad($fecha) {

    //  datos obligatorios
    if ( $fecha == '' ){
        return;
    }

    //  si viene como fecha (no deberia)
    if ( strpos($fecha, '/') ) {
        $fecha = explode('/', $fecha);
        $fecha = $fecha[2].$fecha[1].$fecha[0];
    }

    //  la fecha en variables
    $ano = Carbon\Carbon::parse($fecha)->format('Y');
    $mes = Carbon\Carbon::parse($fecha)->format('m');
    $dia = Carbon\Carbon::parse($fecha)->format('d');

    //  si tiene los valores, calcula la edad y devuelve el resultado
    if ( is_numeric($ano) && is_numeric($mes) && is_numeric($dia) ){
        return Carbon\Carbon::createFromDate($ano, $mes, $dia)->age;
    }
}

/**
* Codifica nombre de una persona
* @param string $nombre
* @param string $fecha
* @param string $sexo
* @param string $separador (Opcional)
* @return string
*/
function get_codificar_nombre_persona($nombre, $fecha, $sexo, $separador = '.', $tipo_nombre = 'apellido-nombre') {

    //  copiado de la funcion SQL de Sergio.

    $campo_nombre = $tipo_nombre = 'apellido-nombre' ? 1 : 0;
    $campo_apellido = $tipo_nombre = 'apellido-nombre' ? 0 : 1;
    $fecha = empty($fecha) ? '19790101' : $fecha;
    $sexo = empty($sexo) ? 'M' : $sexo;

    //  descompone el nombre (???)
    $nombres = explode(' ', $nombre);
    $nombre = $nombres[$campo_nombre];
    $apellido = empty($nombres[$campo_apellido]) ? 'DESCONOCIDO' : $nombres[$campo_apellido];

    $fecha = Carbon\Carbon::parse($fecha)->format('d.m.Y');

    return strtoupper($sexo . $separador . substr($nombre, 0, 2) . $separador . substr($apellido, 0, 2) . $separador . $fecha);
}

/**
* Verifica si la persona es titular de un grupo
* @param int $id_persona
* @return boolean
*/
// function get_es_titular_de_grupo($id_persona) {

//     //  datos obligatorios
//     if ( empty($id_persona) ){
//         return FALSE;
//     }

//     //  crea el objeto
//     $persona_obj = new App\Models\Persona;

//     //  devuelve el resultado
//     return $persona_obj->es_titular($id_persona);
// }

/**
* Convierte una fecha dd/mm/YYYY a Ymd de Carbon
* @param mixed $params Parámetros (Obligatorios: fec_baja, id_vigencia, id_usuario | Opcionales: id_tipo_baja, n_baja)
* @return Carbon Date|FALSE Objeto Carbon o FALSE cuando hay error
*/
function get_carbon_ymd($fecha) {

    //  datos obligatorios
    if ( $fecha == '' ){
        return;
    }

    //  si viene como fecha (no deberia)
    if ( strpos($fecha, '/') ) {
        $fecha = explode('/', $fecha);
        $fecha = $fecha[2].$fecha[1].$fecha[0];
    }

    //  la fecha en variables
    $ano = Carbon\Carbon::parse($fecha)->format('Y');
    $mes = Carbon\Carbon::parse($fecha)->format('m');
    $dia = Carbon\Carbon::parse($fecha)->format('d');

    //  si tiene los valores, calcula la edad y devuelve el resultado
    if ( is_numeric($ano) && is_numeric($mes) && is_numeric($dia) ){
        return Carbon\Carbon::createFromDate($ano, $mes, $dia);
    }
}

/**
* Realiza la baja o eliminación suave de registros en sqlserver finalizando la vigencia
* @param mixed $params Parámetros (Obligatorios: fec_baja, id_vigencia, id_usuario | Opcionales: id_tipo_baja, n_baja)
* @return int|FALSE ID de la Baja o FALSE cuando hay error
*/
function realizar_baja($params) {

    //  datos obligatorios
    if ( !isset($params['id_vigencia']) || $params['id_vigencia'] <= 0 ){
        return FALSE;
    }
    //  crea objeto
    $baja_obj = new App\Http\Controllers\ConexionSpController();

    //  establece los valores no obligatorios si no vienen
    $params['id_tipo_baja'] = !isset($params['id_tipo_baja']) ? 1 : $params['id_tipo_baja'];
    $params['n_baja'] = !isset($params['n_baja']) ? '' : $params['n_baja'];
    $params['fecha_baja'] = ( !isset($params['fecha_baja']) || $params['fecha_baja'] == '' ) ? Carbon\Carbon::now()->subMinute()->format('Ymd H:i:s') : $params['fecha_baja'];
    $params['id_usuario'] = !isset($params['id_usuario']) ? 1 : $params['id_usuario'];

    $parametros_sp = [
        'id_tipo_baja' => $params['id_tipo_baja'],
        'id_usuario' => $params['id_usuario'],
        'fec_baja' => $params['fecha_baja'],
        'id_vigencia' => $params['id_vigencia'],
        'n_baja' => $params['n_baja'],
    ];
    //  inserta objeto
    $ret = $baja_obj->ejecutar_sp_directo('afiliacion', 'sp_baja_Insert', $parametros_sp);
    $sp = [
        'sp_baja_Insert' => $parametros_sp
    ];
    $response = [
        'sp_baja_Insert' => $ret
    ];

    if ( isset($ret[0]->id_baja) && $ret[0]->id_baja > 0 ){
        return [
            'sp' => $sp,
            'response' => $response,
            'id_baja' => $ret[0]->id_baja,
            'estado' => true
        ];
    }else{
        return [
            'sp' => $sp,
            'response' => $response,
            'id_baja' => -1,
            'estado' => false
        ];
    }
}

/**
* Finaliza una vigencia del modelo admin
* @param mixed $params Parámetros (Obligatorios: fec_baja, id_vigencia, id_usuario | Opcionales: id_tipo_baja, n_baja)
* @return int|FALSE ID de la Baja o FALSE cuando hay error
*/
// function get_finalizar_admin($params) {

//     //  datos obligatorios
//     if ( !isset($params['id_vigencia']) || $params['id_vigencia'] <= 0 )
//         return FALSE;

//     //  crea objeto
//     $baja_obj = new App\Models\Admin\Baja;

//     //  establece los valores no obligatorios si no vienen
//     $params['id_tipo_baja'] = !isset($params['id_tipo_baja']) ? 1 : $params['id_tipo_baja'];
//     $params['n_baja'] = !isset($params['n_baja']) ? '' : $params['n_baja'];
//     $params['fec_baja'] = ( !isset($params['fec_baja']) || $params['fec_baja'] == '' ) ? Carbon\Carbon::now()->subMinute()->format('Ymd') : $params['fec_baja'];
//     $params['id_usuario'] = ( !isset($params['id_usuario']) || $params['id_usuario'] == '' ) ? Auth::user()->id : $params['id_usuario'];

//     //  inserta objeto
//     $ret = $baja_obj->insertar([  // sp_baja_Insert
//         'p_id_tipo_baja' => $params['id_tipo_baja'],
//         'p_id_usuario_envia' => $params['id_usuario'],
//         'p_fec_baja' => $params['fec_baja'],
//         'p_id_vigencia' => $params['id_vigencia'],
//         'p_n_baja' => $params['n_baja'],
//     ]);

//     if ( isset($ret['id']) && $ret['id'] > 0 )
//         return $ret['id'];
//     else
//         return FALSE;
// }

/**
* Devuelve la fecha de vigencia
* @param int $id_vigencia
* @return string
*/
// function get_fecha_vigencia($id_vigencia) {

//     if ( !is_numeric($id_vigencia) )
//         return FALSE;

//     //  crea el objeto
//     $vigencia_obj = new App\Models\Vigencia;

//     //  busca la fecha
//     if ( $vigencia = $vigencia_obj->ejecutar(' SELECT convert(varchar, fec_vigencia, 121) as fec_vigencia FROM vigencia WHERE id_vigencia = ?', [$id_vigencia], TRUE, 'fec_vigencia') )
//         return $vigencia;
// }

/**
* Inserta un texto de un objeto
* @param string $objeto
* @param int $id_objeto
* @param string $texto
* @param string $etiqueta (opcional)
* @param int $id_usuario (opcional)
* @param string $fecha (opcional)
* @param boolean $devolver_ids_creados
* @return mixed ID del Objeto Texto, IDs creados o FALSE si hubo algún error
*/
// function get_insertar_objeto_texto($objeto, $id_objeto, $texto, $etiqueta = NULL, $id_usuario = NULL, $fecha = NULL, $devolver_ids_creados = FALSE) {

//     //  variables necesarias
//     $id_usuario = $id_usuario != NULL ? $id_usuario : Auth::user()->id;
//     $fecha = $fecha != NULL ? $fecha : Carbon\Carbon::now()->format('Ymd');

//     //  datos obligatorios
//     if ( empty($objeto) || empty($id_objeto) || empty($texto) || empty($id_usuario) || empty($fecha) )
//         return FALSE;

//     //  crea objeto
//     $texto_obj = new App\Models\Texto;

//     //  inserta texto
//     $res = $texto_obj->insertar([
//         'n_texto' => $texto,
//         'referencia' => '',
//         'id_usuario' => $id_usuario,
//         'fecha' => $fecha,
//     ]);

//     //  si es valido el insert
//     if ( get_validar_insert($res, 'id_texto') )
//     {
//         //  obtiene el texto
//         $id_texto = $res['id_texto'];

//         //  inserta texto
//         $res_objeto_texto = $texto_obj->ejecutar_sp('sp_objeto_texto_Insert', [
//             'p_n_objeto' => $objeto,
//             'p_id_objeto' => $id_objeto,
//             'p_id_texto' => $id_texto,
//             'p_etiqueta' => $etiqueta,
//             'p_id_usuario' => $id_usuario,
//             'p_fecha' => $fecha,
//         ]);

//         if ( get_validar_ejecucion_insert($res_objeto_texto) )
//             $id_objeto_texto = $res_objeto_texto[0]->id;
//     }

//     //  si no pudo insertar
//     if ( empty($id_texto) || empty($id_objeto_texto) )
//         return FALSE;

//     //  devuelve ids o el id creado
//     if ( $devolver_ids_creados )
//     {
//         return [
//             'id_texto' => $id_texto,
//             'id_objeto_texto' => $id_objeto_texto,
//         ];
//     }
//     else
//     {
//         return $id_objeto_texto;
//     }
// }

/**
* Obtiene un texto
* @param string $objeto
* @param int $id_objeto
* @param string $etiqueta (opcional)
* @param boolean $devolver_objeto (opcional) Si devuelve el objeto o el texto únicamente
* @return mixed Devuelve el texto o el objeto
*/
// function get_traer_objeto_texto($objeto, $id_objeto, $etiqueta = NULL, $devolver_objeto = FALSE) {

//     //  datos obligatorios
//     if ( empty($id_objeto) || empty($objeto) )
//         return FALSE;

//     //  crea objeto
//     $objeto_texto_obj = new App\Models\ObjetoTexto;

//     //  si encuentra, devuelve objeto
//     $objeto_texto = $objeto_texto_obj->traer([
//         'p_n_objeto' => $objeto,
//         'p_id_objeto' => $id_objeto,
//         'p_etiqueta' => $etiqueta
//     ]);

//     //  si no encuentra
//     if ( empty($objeto_texto) )
//         return FALSE;

//     return ( $devolver_objeto ? (array)$objeto_texto[0] : $objeto_texto[0]->n_texto );
// }

/**
* Inserta un texto
* @param string $objeto
* @param int $id_objeto
* @param string $etiqueta
* @param string $texto
* @param int $id_usuario
* @param string $fecha
* @param string $referencia
* @return int|FALSE ID del Texto o FALSE cuando hay error
*/
// function get_insertar_texto_objeto($objeto, $id_objeto, $etiqueta, $texto, $archivo = NULL, $referencia = '', $id_usuario = NULL, $fecha = NULL, $devolver_ids_creados = FALSE) {

//     //  datos obligatorios
//     if ( $objeto == '' || $etiqueta == '' || $id_objeto == '' || !is_numeric($id_objeto) || ( $id_usuario == NULL && ( !Auth::user()->id || !Auth::user()->id ) ) )
//         return FALSE;

//     //  variables necesarias
//     $id_usuario = $id_usuario != NULL ? $id_usuario : Auth::user()->id;
//     $fecha = $fecha != NULL ? $fecha : Carbon\Carbon::now()->format('Ymd');

//     //  crea objeto
//     $texto_obj = new App\Models\Texto;

//     //  verifica si existe el objeto
//     $actual = $texto_obj->ejecutar_sp('sp_get_texto_Select', [
//         'p_get_objeto' => $objeto,
//         'p_get_etiqueta' => $etiqueta,
//         'p_get_id' => $id_objeto,
//     ]);

//     //  variable de control
//     $id = FALSE;
//     $inserta = TRUE;

//     //  si existe
//     if ( isset($actual[0]->id_get_texto) && $actual[0]->id_get_texto > 0 )
//     {
//         //  si el texto es el mismo, no se debe insertar
//         if ( trim($actual[0]->n_texto) == trim($texto) )
//         {
//             $id = $actual[0]->id_get_texto;
//             $inserta = FALSE;
//         }
//         //  si el texto no es el mismo
//         else
//         {
//             //  modifica el flag
//             $inserta = FALSE;

//             //  establece parametros para la baja de la lista
//             $params = [
//                 'id_tipo_baja' => 1,
//                 'id_usuario' => Auth::user()->id,
//                 'fec_baja' => Carbon\Carbon::now()->format('Ymd'),
//                 'id_vigencia' => $actual[0]->id_vigencia,
//             ];

//             //  finaliza texto
//             if ( $id_baja = dar_baja($params) )
//                 $inserta = TRUE;
//         }
//     }

//     //  si tiene que insertar
//     if ( $inserta && $texto != '' )
//     {
//         //  ingresa texto
//         if ( !($id_texto = get_insertar_texto($texto, $archivo, $referencia, $id_usuario, $fecha, $devolver_ids_creados)) )
//             return FALSE;

//         //  inserta objeto
//         $ret = $texto_obj->ejecutar_sp('sp_get_texto_Insert', [
//             'p_get_objeto' => $objeto,
//         	'p_get_etiqueta' => $etiqueta,
//             'p_get_id' => $id_objeto,
//         	'p_id_texto' => $id_texto,
//             'p_id_usuario' => $id_usuario,
//             'p_fecha' => $fecha
//         ]);

//         if ( isset($ret[0]->id) && $ret[0]->id > 0 )
//             $id = $ret[0]->id;
//     }

//     if ( $inserta && $texto == '' )
//         $id = TRUE;

//     return $id;
// }

/**
* Obtiene un texto
* @param string $objeto
* @param int $id_objeto
* @param string $etiqueta
* @return mixed
*/
// function get_traer_texto_objeto($objeto, $id_objeto, $etiqueta) {

//     //  datos obligatorios
//     if ( !is_numeric($id_objeto) || $objeto == '' || $etiqueta == '' )
//         return FALSE;

//     //  crea objeto
//     $texto_obj = new App\Models\Texto;

//     //  si encuentra, devuelve objeto
//     if ( $ret = $texto_obj->ejecutar_sp('sp_get_texto_Select', [
//         'p_get_objeto' => $objeto,
//         'p_get_id' => $id_objeto,
//         'p_get_etiqueta' => $etiqueta,
//     ], TRUE) )
//         return (array)$ret;
// }

/**
* Inserta un texto
* @param string $texto
* @param int $id_usuario
* @param string $fecha
* @param string $referencia
* @return int|FALSE ID del Texto o FALSE cuando hay error
*/
// function get_insertar_texto($texto, $archivo = NULL, $referencia = '', $id_usuario = NULL, $fecha = NULL, $devolver_ids_creados = FALSE) {

//     //  datos obligatorios
//     if ( $texto == '' || ( $id_usuario == NULL && ( !Auth::user()->id || !Auth::user()->id ) ) ){ 
//         return FALSE;
//     }

//     //  variables necesarias
//     $id_usuario = $id_usuario != NULL ? $id_usuario : Auth::user()->id;
//     $fecha = !empty($fecha) ? $fecha : Carbon\Carbon::now()->format('Ymd');

//     //  si debe insertar adjunto
//     if ( $archivo != NULL )
//     {
//         //  inserta adjunto obteniendo los datos completos
//         if ( $adjunto_obj = get_insertar_adjunto($archivo, $texto, $id_tipo_adjunto = NULL, $id_usuario, $fecha, $referencia, $devolver_ids_creados) )
//         {
//             if ( !$devolver_ids_creados ){
//                 return $adjunto_obj['id_texto'];
//             } else {
//                 return $adjunto_obj;
//             }
//         }
//     }

//     //  crea objeto
//     $texto_obj = new App\Models\Texto;

//     //  inserta objeto
//     $ret = $texto_obj->insertar([
//         'id_usuario' => $id_usuario,
//         'fecha' => $fecha,
//         'n_texto' => $texto,
//         'referencia' => $referencia,
//     ]);

//     if ( isset($ret['id_texto']) && $ret['id_texto'] > 0 ) {
//         return $ret['id_texto'];
//     } else {
//         return FALSE;
//     }
// }

/**
* Obtiene un texto
* @param int $id_texto
* @return mixed
*/
// function get_traer_texto($id_texto) {

//     //  datos obligatorios
//     if ( !is_numeric($id_texto) || $id_texto == '' || $id_texto == NULL ) {
//         return FALSE;
//     }

//     //  crea objeto
//     $texto_obj = new App\Models\Texto;

//     //  si encuentra, devuelve objeto
//     if ( $ret = $texto_obj->traerPorId($id_texto) ){
//         return (array)$ret;
//     }
// }

/**
* Inserta un atrituto de un objeto
* @param string $objeto
* @param int $id_objeto
* @param string $key
* @param string $value (opcional)
* @param int $id_usuario (opcional)
* @param string $fecha (opcional)
* @return mixed ID del Objeto Atributo, ObjetoAtributo o FALSE si hubo algún error
*/
function get_insertar_objeto_atributo($objeto, $id_objeto, $key, $value, $id_usuario = NULL, $fecha = NULL) {

    //  variables necesarias
    $id_usuario = $id_usuario != NULL ? $id_usuario : Auth::user()->id;
    $fecha = $fecha != NULL ? $fecha : Carbon\Carbon::now()->format('Ymd');

    //  datos obligatorios
    if ( empty($objeto) || empty($id_objeto) || empty($key) )
        return FALSE;

    //  crea objeto
    $objeto_atributo_obj = new App\Models\ObjetoAtributo;

    //  inserta objeto
    $res = $objeto_atributo_obj->insertar([
        'p_n_objeto' => $objeto,
        'p_id_objeto' => $id_objeto,
        'p_key' => $key,
        'p_value' => $value,
        'p_id_usuario' => $id_usuario == NULL ? Auth::user()->id : $id_usuario,
        'p_fecha' => $fecha == NULL ? Carbon\Carbon::now()->format('Ymd H:i:s') : Carbon\Carbon::parse($fecha)->format('Ymd H:i:s')
    ]);

    //  si no es valido el insert
    if ( !get_validar_insert($res) )
        return FALSE;

    //  devuelve ids o el id creado
    return $res['id'];
}

/**
* Obtiene el atributo de un objeto
* @param string $objeto
* @param int $id_objeto
* @param string $key (opcional)
* @param boolean $devolver_objeto (opcional) Si devuelve el objeto o el texto únicamente
* @param boolean $devolver_lista (opcional) Si devuelve el objeto único o la lista
* @return mixed Devuelve el texto o el objeto
*/
function get_traer_objeto_atributo($objeto, $id_objeto, $key = NULL, $devolver_objeto = FALSE, $devolver_lista = FALSE) {

    //  datos obligatorios
    if ( empty($id_objeto) || empty($objeto) )
        return FALSE;

    //  crea objeto
    $objeto_atributo_obj = new App\Models\ObjetoAtributo;

    //  crea los parametros por defecto
    $params = [
        'p_n_objeto' => $objeto,
        'p_id_objeto' => $id_objeto
    ];

    //  si tiene key lo agrega a los parametros
    if ( !empty($key) )
        $params['p_key'] = $key;

    //  si encuentra, devuelve objeto
    $objeto_atributo = $objeto_atributo_obj->traer($params);

    //  si no encuentra
    if ( empty($objeto_atributo) )
        return FALSE;

    if ( $devolver_objeto )
    {
        return $devolver_lista ? $objeto_atributo : (array)$objeto_atributo[0];
    }
    else
    {
        return $objeto_atributo[0]->value;
    }
}

//

// function get_traer_parentesco($id_parentesco, $n_parentesco = NULL) {

//     //  datos obligatorios
//     if ( ( !is_numeric($id_parentesco) || empty($id_parentesco) ) && empty($n_parentesco) ){
//         return FALSE;
//     }

//     //  crea objeto
//     $parentesco_obj = new App\Models\Parentesco;

//     if ( !empty($id_parentesco) )
//     {
//         //  si encuentra, devuelve objeto
//         if ( $ret = $parentesco_obj->traerPorId($id_parentesco) )
//             return (array)$ret;
//     }
//     else
//     {
//         //  si encuentra, devuelve objeto
//         if ( $ret = $parentesco_obj->traer(['p_n_parentesco' => $n_parentesco]) )
//         {
//             if ( count($ret) == 1 )
//                 $ret = $ret[0];
//             return (array)$ret;
//         }
//     }

//     return FALSE;
// }

/**
* Inserta un adjunto
* @param string $archivo
* @param string $texto
* @param int $id_usuario
* @param string $fecha
* @param string $referencia
* @return int|FALSE ID del Adjunto o FALSE cuando hay error
*/
// function get_insertar_adjunto($archivo, $texto = NULL, $id_tipo_adjunto = NULL, $id_usuario = NULL, $fecha = NULL, $referencia = '', $devolver_ids_creados = FALSE, $id_texto = NULL) {
//     // dump('$archivo', $archivo, '$texto', $texto, '$id_tipo_adjunto', $id_tipo_adjunto, '$id_usuario', $id_usuario, '$fecha', $fecha, '$referencia', $referencia, '$devolver_ids_creados',$devolver_ids_creados);
//     //  si no pasa tipo de adjunto
//     if ( $id_tipo_adjunto == NULL || ( $id_usuario == NULL && ( !Auth::user()->id || !Auth::user()->id ) ) )
//     {
//         //  obtiene el primer tipo de adjunto (que encuentra)
//         $tipo_adjunto_obj = new App\Models\TipoAdjunto;
//         $tipo_adjunto_obj = $tipo_adjunto_obj->traerPrimero();

//         if ( $tipo_adjunto_obj && isset($tipo_adjunto_obj->id_tipo_adjunto) && is_numeric($tipo_adjunto_obj->id_tipo_adjunto) ){
//             $id_tipo_adjunto = $tipo_adjunto_obj->id_tipo_adjunto;
//         }
//     }
//     // dd('id_tipo_adjunto', $id_tipo_adjunto, '$tipo_adjunto_obj', $tipo_adjunto_obj);
//     //  datos obligatorios
//     if ( $archivo == '' || $id_tipo_adjunto == NULL ){
//         return FALSE;
//     }

//     //  variables necesarias
//     $id_usuario = $id_usuario != NULL ? $id_usuario : Auth::user()->id;
//     $fecha = $fecha != NULL ? $fecha : Carbon\Carbon::now()->format('Ymd');

//     //  si no existe el texto en la base de datos y tiene texto
//     if ( $id_texto == NULL && $texto != NULL )
//     {
//         //  crea objeto
//         $texto_obj = new App\Models\Texto;

//         //  inserta objeto
//         $ret = $texto_obj->insertar([
//             'id_usuario' => $id_usuario,
//             'fecha' => $fecha,
//             'n_texto' => $texto,
//             'referencia' => $referencia,
//         ]);

//         //  si lo inserta, toma el ID
//         if ( isset($ret['id_texto']) && $ret['id_texto'] > 0 ){
//             $id_texto = $ret['id_texto'];
//         }else{
//             return FALSE;
//         }
//     }

//     //  crea objeto
//     $adjunto_obj = new App\Models\Adjunto;

//     //  inserta objeto
//     $ret = $adjunto_obj->insertar([
//         'p_n_archivo' => $archivo,
//         'p_id_tipo_adjunto' => $id_tipo_adjunto,
//         'p_id_texto' => $id_texto,
//     ]);

//     //  si inserta el adjunto
//     if ( isset($ret['id']) && $ret['id'] > 0 )
//     {
//         //  si tiene que devolver los ids creados
//         if ( !$devolver_ids_creados )
//         {
//             return $ret['id'];
//         }
//         //  si tiene que devolver los ids creados
//         else
//         {
//             return [
//                 'id_adjunto' => $ret['id'],
//                 'id_texto' => $id_texto,
//             ];
//         }
//     }
//     else
//     {
//         return FALSE;
//     }
// }

/**
* Inserta un adjunto de un objeto
* @param string $objeto
* @param int $id_objeto
* @param string $archivo
* @param string $texto
* @param int $id_usuario
* @param string $fecha
* @param string $referencia
* @return int|FALSE ID del Adjunto o FALSE cuando hay error
*/
// function get_insertar_objeto_adjunto($objeto, $id_objeto, $archivo, $referencia = '', $id_tipo_adjunto = NULL, $id_usuario = NULL, $fecha = NULL, $devolver_ids_creados = FALSE) {

//     //  variables necesarias
//     $id_objeto_texto = FALSE;
//     $id_usuario = $id_usuario != NULL ? $id_usuario : Auth::user()->id;
//     $fecha = $fecha != NULL ? $fecha : Carbon\Carbon::now()->format('Ymd H:i:s');

//     //  si no pasa tipo de adjunto
//     if ( $id_tipo_adjunto == NULL )
//     {
//         //  obtiene el primer tipo de adjunto (que encuentra)
//         $tipo_adjunto_obj = new App\Models\TipoAdjunto;
//         $tipo_adjunto_obj = $tipo_adjunto_obj->traerPrimero();

//         if ( $tipo_adjunto_obj && isset($tipo_adjunto_obj->id_tipo_adjunto) && is_numeric($tipo_adjunto_obj->id_tipo_adjunto) )
//             $id_tipo_adjunto = $tipo_adjunto_obj->id_tipo_adjunto;
//     }

//     //  datos obligatorios
//     if ( empty($archivo) || empty($id_tipo_adjunto) )
//         return 'FALSE';

//     //  inserta el adjunto
//     $id_adjunto = get_insertar_adjunto($archivo);

//     //  si inserta
//     if ( empty($id_adjunto) )
//         return FALSE;

//     //  crea objeto
//     $objeto_adjunto_obj = new App\Models\ObjetoAdjunto;

//     //  inserta objeto
//     $res_objeto = $objeto_adjunto_obj->insertar([
//         'p_n_objeto' => $objeto,
//         'p_id_objeto' => $id_objeto,
//         'p_id_adjunto' => $id_adjunto,
//         'p_id_usuario' => $id_usuario,
//         'p_fecha' => $fecha
//     ]);

//     if ( !get_validar_insert($res_objeto) )
//         return FALSE;

//     //  si tiene referencia lo agrega
//     if ( !empty($referencia) )
//         $id_objeto_texto = get_insertar_objeto_texto('adjunto', $id_adjunto, $referencia);

//     //  si tiene que devolver los ids creados
//     if ( !$devolver_ids_creados )
//         return $res_objeto['id'];

//     //  si tiene que devolver los ids creados
//     return [
//         'id_adjunto' => $id_adjunto,
//         'id_objeto_adjunto' => $res_objeto['id'],
//         'id_objeto_texto' => $id_objeto_texto,
//     ];
// }

/**
* Convierte un objeto a un array multidimensional
* @param object $objetos
* @return mixed
*/
function get_objeto_a_array($objetos) {

    //  crea array vacio
    $array = array();

    //  por cada objeto, lo convierte a array y lo agrega al array de retorno
    foreach ( $objetos as $objeto )
        array_push($array, (array)$objeto);

    //  devuelve array
    return $array;
}

/**
* Obtiene la persona
* @param int $id_persona
* @return mixed
// */
// function get_traer_persona($id_persona) {

//     if ( !is_numeric($id_persona) || $id_persona == '' || $id_persona == NULL )
//         return FALSE;

//     //  crea objeto
//     $persona_obj = new App\Models\Persona;

//     //  crea variable de retorno
//     $persona = FALSE;

//     //  si encuentra, devuelve objeto
//     return $persona_obj->ejecutar(' SELECT * FROM persona WHERE id_persona = ?', [$id_persona], TRUE);
// }

/**
* Obtiene el mail de una persona
* @param int $id_persona
* @return mixed
*/
// function get_email_persona($id_persona) {

//     if ( !is_numeric($id_persona) || $id_persona == '' || $id_persona == NULL )
//         return FALSE;

//     //  crea objeto
//     $persona_obj = new App\Models\Persona;

//     //  crea variable de retorno
//     $email = FALSE;

//     //  si encuentra, devuelve objeto
//     if ( $ret = $persona_obj->ejecutar_sp('sp_persona_email_Select', ['p_id_persona' => $id_persona]) )
//         $email = $ret[0]->n_contacto;

//     if ( env('APP_DEBUG') )
//         $email = env('ADMIN_EMAIL');

//     //  devuelve el resultado
//     return $email;
// }

/**
* Obtiene el nombre de una persona
* @param int $id_persona
* @return mixed
*/
// function get_nombre_persona($id_persona) {

//     if ( !is_numeric($id_persona) || $id_persona == '' || $id_persona == NULL )
//         return FALSE;

//     //  crea objeto
//     $persona_obj = new App\Models\Persona;

//     //  crea variable de retorno
//     $email = FALSE;

//     //  si encuentra, devuelve objeto
//     if ( $ret = $persona_obj->ejecutar_sp('sp_persona_Select', ['id_persona' => $id_persona]) )
//         $email = $ret[0]->n_persona;

//     //  devuelve el resultado
//     return $email;
// }

/**
* Obtiene el id_empresa de una persona
* @param int $id_persona
* @return int Id Empresa
*/
// function get_persona_id_empresa($id_persona) {

//     if ( !is_numeric($id_persona) || $id_persona == '' || $id_persona == NULL )
//         return FALSE;

//     //  variable por defecto
//     $id_empresa = FALSE;

//     //  crea objeto
//     $empresa_obj = new App\Models\Empresa('afiliacion');

//     //	obtiene la empresa
//     if ( $empresa = $empresa_obj->traer(['p_id_persona' => $id_persona]) )
//         $id_empresa = $empresa[0]->id_empresa;

//     //  devuelve el resultado
//     return $id_empresa;
// }

/**
* Obtiene el mail de un usuario
* @param int $id_usuario (opcional)
* @return string Si tiene parametro id_usuario busca en la base de datos, sino, devuelve el logueado
*/
function get_email_usuario($id_usuario = NULL) {

    if ( !is_numeric($id_usuario) || $id_usuario == '' || $id_usuario == NULL )
    {
        // dd('si');
        return Session::get('usuario.email');
    }
    else
    {
        
        //  crea objeto
        $usuario_obj = new App\Models\Admin\Usuario;

        //  crea variable de retorno
        $email = FALSE;

        //  si encuentra, devuelve objeto
        if ( $ret = $usuario_obj->ejecutar_sp('sp_usuario_email_Select', ['p_id_usuario' => $id_usuario]) ){
            $email = $ret[0]->email;
        }
        // dd('ret', $ret);
    }

    //  devuelve el resultado
    return $email;
}

/**
* Obtiene el nombre de un usuario
* @param int $id_usuario
* @return mixed
*/
function get_nombre_usuario($id_usuario) {

    if ( !is_numeric($id_usuario) || $id_usuario == '' || $id_usuario == NULL )
        return FALSE;

    //  crea objeto
    $usuario_obj = new App\Models\Admin\Usuario;

    //  crea variable de retorno
    $email = FALSE;

    //  si encuentra, devuelve objeto
    if ( $ret = $usuario_obj->ejecutar_sp('sp_usuario_Select', ['p_id_usuario' => $id_usuario]) )
        $email = $ret[0]->apynom;

    //  devuelve el resultado
    return $email;
}

/**
* Devuelve si tiene o no periodo activo
* @return boolean
*/
function get_hay_periodo_activo()
{
    return get_periodo_activo('boolean');
}

/**
* Devuelve el id del periodo activo
* @return int
*/
function get_id_periodo_activo()
{
    return get_periodo_activo('id');
}

/**
* Devuelve los datos del periodo activo
* @param string $devolucion Que debe devolver
* @return mixed
*/
function get_periodo_activo($devolucion = 'objeto') {

    //  obtiene los datos de la caja del usuario
    $periodo_obj = new App\Models\Prepagas\Periodo;

    //  obtiene los periodos activos
    $periodo = $periodo_obj->traer(['activo' => 1]);

    //  si existe el periodo
    if ( $periodo && !empty($periodo) && !empty($periodo[0]) )
    {
        if ( !is_array($periodo[0]) ){
            $periodo = (array)$periodo[0];
        }

        //  si debe devolver si está activo o no
        if ( $devolucion == 'boolean' ){
            return ( empty($periodo['id_periodo']) ? FALSE : TRUE );
        }

        //  si debe devolver el objeto
        if ( $devolucion == 'objeto' ){
            return $periodo;
        }

        if ( $devolucion != 'boolean' && $devolucion != 'objeto' )
        {
            if ( $devolucion == 'id' ){
                $devolucion = 'id_periodo';
            }

            if ( isset($periodo[$devolucion]) ){
                return $periodo[$devolucion];
            }
        }
    }

    return FALSE;
}

/**
* Devuelve si tiene o no caja activa
* @return boolean
*/
function get_tiene_caja_activa()
{
    return get_caja_usuario('boolean');
}

/**
* Devuelve el saldo de la caja activa
* @return decimal
*/
function get_saldo_caja_activa()
{
    return get_caja_usuario('saldo');
}

/**
* Devuelve el id de la caja activa
* @return int
*/
function get_id_caja_activa()
{
    return get_caja_usuario('id');
}

/**
* Devuelve los datos de la caja del usuario
* @param string $devolucion Que debe devolver
* @return mixed
*/
function get_caja_usuario($devolucion = 'id') {

    //  obtiene los datos de la caja del usuario
    $caja = Session::get('usuario.caja');

    //  si existe la caja
    if ( $caja )
    {
        if ( !is_array($caja) )
            $caja = (array)$caja;

        //  si debe devolver si está activa o no
        if ( $devolucion == 'boolean' )
            return ( $caja['activa'] == 1 ? TRUE : FALSE );

        //  si debe devolver el objeto
        if ( $devolucion == 'objeto' )
            return $caja;

        if ( $devolucion != 'boolean' && $devolucion != 'objeto' )
        {
            if ( $devolucion == 'id' )
                $devolucion = 'id_caja';

            if ( isset($caja[$devolucion]) )
                return $caja[$devolucion];
        }
    }

    return FALSE;
}

/**
* Actualiza el saldo de la caja activa del usuario
* @param decimal $valor Valor a sumar al saldo
* @return mixed Valor o FALSE
*/
function get_actualizar_saldo_caja_activa($valor) {

    //  si los datos obligatorios no están
    if ( empty($valor) || !get_tiene_caja_activa() )
        return FALSE;

    //  obtiene la caja actual del usuario
    $caja = Session::get('usuario.caja');

    //  actualiza el saldo
    $caja['saldo'] -= $valor;

    //  establece los datos nuevos
    Session::put('usuario.caja', $caja);

    //  devuelve el saldo
    return $caja['saldo'];
}

/**
* Devuelve si tiene o no punto de venta activo
* @return boolean
*/
function get_tiene_punto_venta_activo()
{
    return get_punto_venta_usuario('boolean');
}

/**
* Devuelve el id del punto de venta activo
* @return int
*/
function get_id_punto_venta_activo()
{
    return get_punto_venta_usuario('id');
}

/**
* Devuelve los datos de la caja del usuario
* @param string $devolucion Que debe devolver
* @return mixed
*/
function get_punto_venta_usuario($devolucion = 'id') {

    
    //  obtiene los datos del punto de venta del usuario
    $punto_venta = Session::get('usuario.punto_venta');

    //  si existe el punto de venta
    if ( $punto_venta )
    {
        if ( !is_array($punto_venta) ){
            $punto_venta = (array)$punto_venta;
        }

        //  si debe devolver si tiene habilitado el punto de venta
        if ( $devolucion == 'boolean' ){
            return ( empty($punto_venta['id_pvta']) ? FALSE : TRUE );
        }

        //  si debe devolver el objeto
        if ( $devolucion == 'objeto' ){
            return $caja;
        }

        if ( $devolucion != 'boolean' && $devolucion != 'objeto' )
        {
            if ( $devolucion == 'id' ){
                $devolucion = 'id_pvta';
            }

            if ( isset($punto_venta[$devolucion]) ){
                return $punto_venta[$devolucion];
            }
        }
    }

    return FALSE;
}

/**
* Devuelve un string con los caracteres solicitados, agregando los zeros necesarios a la izquierda
* @param string $valor
* @param int Cantidad total de caracteres
* @return string
*/
function get_agregar_ceros($valor, $cantidad) {

    $ret = $valor;
    $cant = strlen($valor);

    for($i=$cant;$i<$cantidad;$i++)
        $ret = '0' . $ret;

    return $ret;
}

/**
 * Devuelve un string con el número de autoizacion formateado con 11 cifras y completado con 0
 * @param id_sucursal el id de la sucursal
 * @param numero_autorizacion el número de la autorización
 * @return string
 */
function get_numero_autorizacion_formateado($id_sucursal, $numero_autorizacion) {
    return get_agregar_ceros($id_sucursal, 3).get_agregar_ceros($numero_autorizacion, 8);
}

/**
* Devuelve un valor formateado
* @param decimal Valor a formatear
* @return string Valor formateado
*/
function get_formatear_valor($valor) {
    return '$ ' . number_format($valor, 2, ',', '.');
}

/**
* Devuelve una fecha formateado
* @param decimal Fecha a formatear
* @return string Fecha formateada
*/
function get_formatear_fecha($fecha, $formato = 'd/m/Y', $separador = '-', $origen = 'SQL') {

    //  si no hay fecha devuelve la de hoy
    if ( $fecha == "" )
        return date($formato);

    //  si tiene espacio lo separa y toma la fecha
    if ( strpos($fecha, ' ') > -1 )
    {
        $fecha = explode(' ', $fecha);
        $fecha = $fecha[0];
    }

    //  separa por el separador
    $fecha = explode($separador, $fecha);

    //  devuelve la fecha
    if ( $origen == 'SQL' ){
        $fecha = $fecha[0] . $fecha[1] . $fecha[2];
    } else {
        $fecha = $fecha[2] . $fecha[1] . $fecha[0];
    }

    return date_format(date_create($fecha), $formato);
}

/**
* Devuelve un string para ser utilizado como slug
* @param string $string
* @return string
*/
function get_slug($string) {

    // Removes html tags
    $string = strip_tags($string);

    // Replace tildes
    $string = str_replace('á', 'a', $string);
    $string = str_replace('Á', 'a', $string);
    $string = str_replace('é', 'e', $string);
    $string = str_replace('É', 'e', $string);
    $string = str_replace('í', 'i', $string);
    $string = str_replace('Í', 'i', $string);
    $string = str_replace('ó', 'o', $string);
    $string = str_replace('Ó', 'o', $string);
    $string = str_replace('ú', 'u', $string);
    $string = str_replace('Ú', 'u', $string);
    $string = str_replace('ü', 'u', $string);
    $string = str_replace('Ü', 'u', $string);
    $string = str_replace('ñ', 'n', $string);
    $string = str_replace('Ñ', 'n', $string);

    // Preserve escaped octets.
    $string = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $string);

    // Remove percent signs that are not part of an octet.
    $string = str_replace('%', '', $string);

    // Restore octets.
    $string = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $string);

    $string = strtolower($string);
    $string = preg_replace('/&.+?;/', '', $string); // kill entities
    $string = str_replace('.', '-', $string);

    $string = preg_replace('/[^%a-z0-9 _-]/', '', $string);
    $string = preg_replace('/\s+/', '-', $string);
    $string = preg_replace('|-+|', '-', $string);
    $string = trim($string, '-');

    return $string;
}

/**
* Devuelve los datos de una plantilla dados los datos
* @param array $plantilla Los campos de la plantilla
* @param array $datos Los datos ingresados
* @return array
*/
function get_procesar_datos_plantilla($plantilla_modelo, $datos) {
    $plantilla = [];
    $plantilla_temp = [];
    foreach ( $plantilla_modelo as $dato_modelo )
    {
        if ( strpos($dato_modelo->valores_posibles, ',') !== false )
            $dato_modelo->valores_posibles = explode(',', $dato_modelo->valores_posibles);
        $plantilla_temp['dato'.$dato_modelo->id_dato] = $dato_modelo;
    }
    foreach ( $datos as $dato )
    {
		if ( isset($plantilla_temp['dato'.$dato->id_dato]) )
        {
            if ( $plantilla_temp['dato'.$dato->id_dato]->tipo_dato=='radio' || $plantilla_temp['dato'.$dato->id_dato]->tipo_dato=='checkbox' )
            {
                $array = [];
                if ( strpos($dato->dato_valor, ',') !== false )
                {
                    $dato->dato_valor = str_replace(', ', ',', $dato->dato_valor);
                    $dato->dato_valor = str_replace(' ,', ',', $dato->dato_valor);
                    $array = explode(',', $dato->dato_valor);
                }
                else
                {
                    array_push($array, $dato->dato_valor);
                }
                $dato->dato_valor = $array;
            }
			$plantilla_temp['dato'.$dato->id_dato]->valor = $dato->dato_valor;
        }
    }
    foreach ( $plantilla_temp as $campo ){
        $plantilla[] = $campo;
    }
    $planilla = collect($plantilla)->sortBy('orden_expo');
    return $plantilla;
}

/**
* Devuelve los datos por plantillas
* @param mixed $plantillas Los datos devueltos por la base de datos
* @return mixed
*/
function get_procesar_plantilla($plantillas) {

    //  crea la lista vacia
    $temp = array();
    $ret = array();

    //  por cada plantilla
    foreach ( $plantillas as $dato )
    {
        //  si no existe crea el item
        if ( !isset($temp[$dato->id_plantilla]) )
        {
            //  crea el item y le agrega el nombre
            $temp[$dato->id_plantilla] = array();
            $temp[$dato->id_plantilla]['id_plantilla'] = $dato->id_plantilla;
            $temp[$dato->id_plantilla]['n_plantilla'] = $dato->n_plantilla;
            $temp[$dato->id_plantilla]['campos'] = array();
        }

        //  obtiene el valor
        $dato->valor = $dato->dato_valor;

        //  si el valor tiene comas, lo convierte en un array (es una lista)
        if ( strpos($dato->valor, ',') !== false ){
            $dato->valor = explode(',', $dato->valor);
        }

        //  crea la variable vacia
        $valores_posibles = array();

        //  crea un array de los valores posibles
        $dato->valores_posibles = explode(',', $dato->valores_posibles);

        //  si es un array
        if ( is_array($dato->valores_posibles) && count($dato->valores_posibles) > 0 )
        {
            //  por cada valor
            foreach ( $dato->valores_posibles as $valor )
            {
                //  lo agrega al item como un n_dato
                $item['n_dato'] = $valor;

                //  lo agrega a la lista de valores posibles
                array_push($valores_posibles, $item);
            }
        }

        //  establece el verdadero valor
        $dato->valores_posibles = $valores_posibles;

        //  agrega el dato en el item
        array_push($temp[$dato->id_plantilla]['campos'], $dato);
    }

    //  por cada item temporal, lo agrega a la variable de retorno (saca el index básicamente)
    //  si hay una forma mas prolija de hacerla, hacela ;)
    foreach ( $temp as $item ){
        array_push($ret, $item);
    }

    //  devuelve la lista
    return $ret;
}

/**
* Devuelve un JSON para ser utilizado en angular
* @param array $array Array a convertir
* @return json
*/
function get_array_a_angular_json($array) {
    // return htmlspecialchars(json_encode($array));
    // se quitó htmlspecialchars() en laravel 5.7 por la doble codificación para que no de error angular
    return json_encode($array);
}

/**
* Convierte un string con guiones (bajos o normales) a camel case
* @param string String a convertir
* @param string Que guion debe convertir (guion normal por defecto)
* @param boolean Si pone mayuscula el primer caracter (FALSE por defecto)
* @return string
*/
function get_convertir_a_camel_case($string, $guion = '-', $capitalizar_primer_caracter = false) {

    $str = str_replace(' ', '', ucwords(str_replace($guion, ' ', $string)));

    if ( !$capitalizar_primer_caracter ) {
        $str[0] = strtolower($str[0]);
    }

    return $str;
}


/**
* Obtiene el ID de una Acción de Internación
* @param string $accion Accion a buscar
* @return int
*/
function get_traer_id_internacion_accion($accion) {

    //  datos obligatorios
    if ( empty($accion) ){
        return FALSE;
    }

    //  reemplaza valores
    if ( $accion == 'apertura' ){
        $accion = 'ingreso';
    }
    if ( $accion == 'alta' ){
        $accion = 'egreso';
    }

    //  crea objeto
    $internacion_accion_obj = new App\Models\Validaciones\InternacionAccion;

    //  si encuentra, devuelve objeto
    $acciones = $internacion_accion_obj->traer(['n_accion' => $accion]); 
    if (!empty($acciones)){
        return $acciones[0]->id_accion;
    }
}

/**
* Obtiene el ID de una Acción de Internación
* @param string $tipo_alta_internacion Tipo de Alta a Buscar
* @return int
*/
function get_traer_id_tipo_alta_internacion($tipo_alta_internacion) {

    //  datos obligatorios
    if ( empty($tipo_alta_internacion) ){
        return FALSE;
    }

    //  crea objeto
    $internacion_obj = new App\Models\Validaciones\Internacion;

    //  si encuentra, devuelve objeto
    $tipos_alta_internacion = $internacion_obj->ejecutar_sp('AWEB_TraerTiposAltaInternacion', ['descripcion' => $tipo_alta_internacion]);
    if (!empty($tipos_alta_internacion)){
        return $tipos_alta_internacion[0]->id_tipo_alta_internacion;
    }
}

/**
* Obtiene el ID del Tipo de Documento CUIT
* @return mixed INT o FALSE
*/
function get_traer_id_cuit() {
    $id_cuit = FALSE;
    $cuit_obj = get_traer_tipo_documento('CUIT');
    if ( empty($cuit_obj) ){
        $cuit_obj = get_traer_tipo_documento('CUI');
    }
    if ( !empty($cuit_obj) ){
        $id_cuit = $cuit_obj['id_tipo_doc'];
    }
    return $id_cuit;
}

/**
* Obtiene un tipo de documento
* @param string $abrev Abreviación a buscar
* @return mixed
*/
function get_traer_tipo_documento($abrev) {

    //  datos obligatorios
    if ( empty($abrev) ){
        return FALSE;
    }

    //  crea objeto
    return get_traer_tipo_documento_por('abrev_tipo_doc', $abrev);
}

/**
* Obtiene el ID de un tipo de documento
* @param string $abrev Abreviación a buscar
* @return mixed
*/
function get_traer_id_tipo_documento($abrev) {

    //  datos obligatorios
    if ( empty($abrev) ){
        return FALSE;
    }

    //  crea objeto
    return get_traer_tipo_documento_por('abrev_tipo_doc', $abrev, TRUE);
}

/**
* Obtiene el ID de un tipo de documento
* @param string $abrev Abreviación a buscar
* @return mixed
*/
function get_traer_abrev_tipo_documento($id_tipo_documento) {

    //  datos obligatorios
    if ( empty($id_tipo_documento) ){
        return FALSE;
    }

    //  crea objeto
    $tipo_documento = get_traer_tipo_documento_por('id_tipo_doc', $id_tipo_documento);
    if (!empty($tipo_documento)){
        return $tipo_documento['abrev_tipo_doc'];
    }
}

/**
* Obtiene un tipo de documento
* @param string $abrev Abreviación a buscar
* @return mixed
*/
function get_traer_tipo_documento_por($campo, $valor, $devolver_id = FALSE) {

    //  datos obligatorios
    if ( empty($campo) || empty($valor) ){
        return FALSE;
    }

    //  crea objeto
    // $tipo_documento_obj = new App\Models\TipoDocumento('validacion');
    $tipo_documento_obj = new App\Http\Controllers\ConexionSpController();

    //  si encuentra, devuelve objeto
    if ( $tipos_documentos = $tipo_documento_obj->ejecutar_sp_directo('validacion', 'sp_tipo_doc_Select', null) )
    {
        foreach ( $tipos_documentos as $tipo_documento )
        {
            if ( trim($tipo_documento->{$campo}) == trim($valor) )
            {
                $tipo_documento = (array)$tipo_documento;
                if ( $devolver_id ){
                    return $tipo_documento['id_tipo_doc'];
                } else {
                    return $tipo_documento;
                }
            }
        }
    }
}

/**
* Obtiene un tipo de documento
* @param string $abrev Abreviación a buscar
* @return mixed
*/
function get_traer_prestacion_ajuste_factura($devolver_id = FALSE) {

    //  crea objeto
    $prestacion_obj = new App\Models\Prestacion;

    //  si encuentra, devuelve objeto
    if ( $prestacion = $prestacion_obj->ejecutar_sp('sp_ajuste_factura_Select', NULL, TRUE) ){
        return $devolver_id ? $prestacion['id_prestacion'] : $prestacion;
    }
}

/**
* Obtiene el logo que corresponde
* @param int $id_convenio ID del convenio conectado (opcional)
* @param string $origen De donde viene la solicitud
* @param int $id_empresa ID de la empresa (del afiliado para lo prestacional, del proveedor para el resto) (opcional)

*/
function get_logo($id_convenio = NULL, $origen = NULL, $id_empresa = NULL) {

    $logo = '';

    //origenes... por el momento...
    //emision-recetario
    //emision-validacion

    //  si tiene empresa
    if ( $id_empresa != NULL )
    {
        //  crea el objeto
        $empresa = new App\Models\Empresa;

        //  obtiene la empresa
        $empresa = $empresa->traerPorId($id_empresa);

        //  si existe la empresa y tiene nombre
        if ( $empresa && !empty($empresa['Razon_Social']) )
        {
            //  establece el nombre del logo
            $razon_social = strtolower($empresa['Razon_Social']);
            $razon_social = str_replace(' - ', '', $razon_social);
            $razon_social = str_replace(' / ', '', $razon_social);
            $razon_social = str_replace(', ', '', $razon_social);
            $razon_social = str_replace('. ', '', $razon_social);
            $logo_empresa = 'logo-' . str_replace(' ', '-', $razon_social) . '.png';

            //  si el logo existe, cambia la variable
            if ( File::exists(get_path_imagenes($logo_empresa)) ){
                $logo = $logo_empresa;
            }
        }
    }

    //  si no existe el logo, carga el por defecto
    if ( $logo == '' ){
        $logo = env('OWN_SERVER', false) ? 'logo-plataforma.png' : 'logo.png';
    }

    return $logo;
}

function get_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

/**
* Devuelve los datos del contrato para la app
* @param int ID del Contrato
*/

function get_contrato_app_info($id_contrato = NULL) {

    $ret = [];

    //  crea objeto
    $contrato_obj = new App\Models\Admin\Contrato;

    //  obtiene la conexion del contrato
    $contrato = $contrato_obj->ejecutar_sp('sp_contrato_conexion_select', ['p_id_contrato' => $id_contrato]);

    //  si encuentra datos
    if ( !empty($contrato) && !empty($contrato[0]) && !empty($contrato[0]->n_conexion) && !empty($contrato[0]->n_contrato) )
    {
        //  establece valores
        $ret['n_contrato'] = $contrato[0]->n_contrato;
        $ret['connection_db'] = $contrato[0]->n_conexion;
        $ret['url'] = '/';

        //  obtiene los modulos del contrato
        $contrato_modulos = $contrato_obj->ejecutar_sp('sp_contrato_modulo_select', ['p_id_contrato' => $id_contrato]);

        //  si encuentra datos, por cada modulo, verifica si hay un modulo por defecto, establece el modulo por defecto
        if ( !empty($contrato_modulos) ){
            foreach ( $contrato_modulos as $modulo ){
                if ( $modulo->defecto == 1 ){
                    $ret['url'] = '/' . $modulo->slug;
                }
            }
        }
    }
// dd('app.php->get_contrato_app_info->ret',$ret);
    return $ret;
}

/**
* Devuelve el contrato o un campo en particular
* @param $id_contrato ID del contrato
* @param $campo El campo a devolver (opcional)
* @return mixed
*/
function get_traer_contrato($id_contrato, $campo = NULL)
{
    //  crea objeto
    $contrato_obj = new App\Models\Admin\Contrato;

    //  si encuentra, devuelve objeto
    if ( $contrato = $contrato_obj->ejecutar_sp('sp_contrato_select', ['p_id_contrato' => $id_contrato]) )
    {
        if ( $campo == NULL )
            return (array)$contrato[0];
        else
            return $contrato[0]->$campo;
    }
}

/**
* Devuelve si el usuario tiene la integración activa
* @param $slug Slug de la Integración
* @return boolean
*/
function get_usuario_integracion_activa($slug)
{
    if ( Auth::check() )
    {
        //  obtiene las integraciones
        $integraciones = Session::get('usuario.integraciones');

        //  si tiene integraciones
        if ( !empty($integraciones) && count($integraciones) )
            foreach ( $integraciones as $key => $value )
                if ( strtolower(trim($slug)) == strtolower(trim($key)) )
                    return $value;
    }

    return FALSE;
}

/**
* Devuelve si el contrato indicado tiene la integración activa
* @param $id_contrato ID del contrato
* @param $slug Slug de la Integración
* @return boolean
*/
function get_contrato_integracion_activa($id_contrato, $slug)
{
    //  crea objeto
    $contrato_obj = new App\Models\Admin\Contrato;

    //  si encuentra, devuelve objeto
    if ( $integracion = $contrato_obj->ejecutar_sp('sp_contrato_integracion_select', ['p_id_contrato' => $id_contrato, 'slug' => $slug]) )
        if ( !empty($integracion) || !empty($integracion[0]->id_contrato) )
            return TRUE;

    //  fallback
    return FALSE;
}

/**
* Formatea un string como periodo
* @param string $periodo Periodo a formatear
* @param mixed $separador El caracter de separación o FALSE (FALSE por default)
* @param boolean $ano_mes Formato del periodo (AñoMes por default)
* @return string
*/
function get_formatear_periodo($periodo, $separador = FALSE, $ano_mes = TRUE)
{
    if ( empty($periodo) )
        return FALSE;

    $periodo = str_replace('-', '', $periodo);
    $periodo = str_replace(' ', '', $periodo);
    $periodo = str_replace('_', '', $periodo);

    if ( $separador )
    {
        $mes = substr($periodo, -1, 2);
        $ano = substr($periodo, 0, 4);
        if ( $ano_mes )
            $periodo = $ano . $separador . get_agregar_ceros($mes, 2);
        else
            $periodo = get_agregar_ceros($mes, 2) . $separador . $ano;
    }

    return $periodo;
}

/**
* Elimina el caracter del separador decimal
* @param string $valor Valor a formatear
* @param int $decimales Cantidad de decimales a tener en cuenta
* @return string
*/
function get_sacar_caracter_decimal($valor, $decimales = 2) {
	$valor = str_replace(',', '.', $valor);
    $valor = number_format($valor, $decimales, '', '');
	return $valor;
}

/**
* Arma una lista de datos que vienen del SQL de forma padre-hijo
* @param array $array
* @param array $options
* @return array
*/
function get_lista_jerarquica($elements, $options = ['id' => 'id', 'second_id' => NULL, 'parent_id' => 'parent_id']) {

    //  genera el arbol
    $elements = __parent_child_tree($elements, $options, 0);

    //  si hay elementos achata el arbol manteniendo el orden
    if ( !empty($elements) )
        $elements = __flatten_parent_child_tree($elements);

    //  agrega orden
    if ( !empty($elements) )
        for ( $i=0; $i<count($elements); $i++ )
            $elements[$i]['orden'] = $i;

    return $elements;
}

/**
* Hace una lista de un arbol de una estructura jerárquica (padre-hijo)
* @param array $array
* @return array
*/
function __flatten_parent_child_tree($array) {
    $result = [];
    foreach ($array as $item)
	{
        if (is_array($item))
		{
            $result[] = array_filter($item, function($array) {
                return ! is_array($array);
            });
            $result = array_merge($result, __flatten_parent_child_tree($item));
        }
    }
    return array_filter($result);
}

/**
* Devuelve una estructura jerárica (padre-hijo)
* @param array $array
* @param array $options
* @return int $parent_id
*/
function __parent_child_tree($elements, $options = ['id' => 'id', 'parent_id' => 'parent_id'], $parent_id = 0)
{
    $branch = array();
    foreach ($elements as $element)
	{
        $element = !is_array($element) ? (array)$element : $element;
        if ($element[$options['parent_id']] == $parent_id)
		{
            $children = __parent_child_tree($elements, $options, $element[$options['id']]);
            if ($children)
                $element['childs'] = $children;
            $branch[] = $element;
        }
    }
    return $branch;
}

/**
* Convierte un string en un string capitalizado
* @param string String a convertir
* @return string
*/
function capitalize($string) {

    $words = explode(' ', strtolower($string));
    $str = ' ';
    foreach($words as $word){
        $word = strtoupper(substr($word, 0, 1)).substr($word, 1);
        $str = $str.$word.' ';
    }
    $str = trim($str);

    return $str;
}

/**
 * Ordena un array asociativo de objetos por una de sus claves
 */
function object_sorter($clave, $orden=NULL, $sencible=true) {
    // Si se necesita insensible a mayúsculas y minúsculas usar strnatcasecmp
    if($sencible){
        // sensible a mayúsculas y minúsculas
        return function ($a, $b) use ($clave, $orden) {
              $result=  ($orden=="DESC") ? strnatcmp($b->$clave, $a->$clave) :  strnatcmp($a->$clave, $b->$clave);
              return $result;
        };
    }else{
        // insensible a matúsculas y minúsculas
        return function ($a, $b) use ($clave, $orden) {
            $result=  ($orden=="DESC") ? strnatcasecmp($b->$clave, $a->$clave) :  strnatcasecmp($a->$clave, $b->$clave);
            return $result;
      };
    }
}

/**
 * Ordena un array asociativo de objetos por una de sus claves
 */
function array_sorter($clave, $orden=NULL, $sencible=true) {
    // Si se necesita insensible a mayúsculas y minúsculas usar strnatcasecmp
    if($sencible){
        // sensible a mayúsculas y minúsculas
        return function ($a, $b) use ($clave, $orden) {
              $result=  ($orden=="DESC") ? strnatcmp($b[$clave], $a[$clave]) :  strnatcmp($a[$clave], $b[$clave]);
              return $result;
        };
    }else{
        // insensible a matúsculas y minúsculas
        return function ($a, $b) use ($clave, $orden) {
            $result=  ($orden=="DESC") ? strnatcasecmp($b[$clave], $a[$clave]) :  strnatcasecmp($a[$clave], $b[$clave]);
            return $result;
      };
    }
}