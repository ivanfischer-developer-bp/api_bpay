<?php

/**
* Devuelve la URL del módulo principal de un usuario
* @return string
*/
function get_url_modulo_principal_usuario()
{
	//	obtiene los modulos habilitados
	$modulos = Session::get('usuario.modulos_habilitados');

	//	si tiene modulos habilitados, devuelve la url del principal, sino, devuelve la url del logoff
	if ( isset($modulos) || is_array($modulos) )
		return env('APP_URL') . '/' . Session::get('usuario.modulos_habilitados')[0]['slug'];
	else
		return env('APP_URL') . '/salir';
}

/**
* Wrapper para armar URLs con los módulos
* @param string $modulo Módulo báse
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_url($modulo, $segmento = '')
{
	if ( function_exists('get_' . $modulo . '_url') )
		return call_user_func('get_' . $modulo . '_url', $segmento);
	else
		return env('APP_URL') . '/' . $modulo;
}

/**
* Devuelve el path de archivos
* @param string $filename
* @return string
*/
function get_file_url()
{
	return env('APP_URL') . '/archivo' ;
}

/**
* Devuelve el segmento como una URL del módulo Admin
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_assets_path($archivo = '')
{
	return public_path() . '/assets/' . $archivo;
}

/**
* Devuelve el segmento como una URL del módulo Admin
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_image_assets_path($archivo = '')
{
	return public_path() . '/assets/images/' . $archivo;
}

/**
* Devuelve el segmento como una URL del módulo Admin
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_images_path($archivo = '')
{
	return public_path() . '/statics/images/' . $archivo;
}

/**
* Devuelve el segmento como una URL del módulo Admin
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_images_url($archivo = '')
{
	return env('APP_URL') . '/statics/images/' . $archivo;
}

/**
* Devuelve la URL de los templates estaticos
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_templates_url($archivo = '')
{
	return env('APP_URL') . '/statics/scripts/templates/' . $archivo;
}

/**
* Devuelve el segmento como una URL del módulo Admin
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_admin_url($segmento = '')
{
	return env('APP_URL') . '/' . env('ADMIN_PATH') . '/' . $segmento;
}

/**
* Devuelve el segmento como una URL del módulo Expedientes
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_exp_url($segmento = '')
{
	return env('APP_URL') . '/' . env('EXP_PATH') . '/' . $segmento;
}

/**
* Devuelve el segmento como una URL del módulo Validaciones
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_val_url($segmento = '')
{
	return env('APP_URL') . '/' . env('VAL_PATH') . '/' . $segmento;
}

/**
* Devuelve el segmento como una URL del módulo Prepagas
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_pre_url($segmento = '')
{
	return env('APP_URL') . '/' . env('PRE_PATH') . '/' . $segmento;
}

/**
* Devuelve el segmento como una URL del módulo Planes Especiales
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_pes_url($segmento = '')
{
	return env('APP_URL') . '/' . env('PES_PATH') . '/' . $segmento;
}

/**
* Devuelve el segmento como una URL del módulo Seguimiento de Documentos
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_doc_url($segmento = '')
{
	return env('APP_URL') . '/' . env('DOC_PATH') . '/' . $segmento;
}

/**
* Devuelve el segmento como una URL del módulo Legajos
* @param string $segmento Segmento a adicionar a la URL
* @return string
*/
function get_leg_url($segmento = '')
{
	return env('APP_URL') . '/' . env('LEG_PATH') . '/' . $segmento;
}

/**
* Devuelve una cadena de texto reemplazando las barras con el separador de accion
* @param string $url URL a convertir
* @param string $separator Separador de modulo-accion.
* @return string
*
*/
function get_url_a_accion($url, $separator = ' > ', $replace_dashes = FALSE, $ucwords = TRUE) {
	// a partir de laravel 5.2 la función url() 
	// retorna una instancia Illuminate\Routing\UrlGenerator 
	// cuando no se proporciona una ruta.
	$url = str_replace(url()->full(), '', $url);
	$url = rtrim($url, '/');
	$accion = str_replace('/', $separator, $url);
	if ( $replace_dashes )
		$accion = str_replace('-', ' ', $accion);
	if ( $ucwords )
		$accion = ucwords($accion);
	$accion = ltrim($accion, ' > ');
	return $accion;
}

/**
* Devuelve el nombre de la vista de la barra de navegacion del modulo actual
* @return string
*/
// function get_nav_modulo_actual() {

// 	//	obtiene el modulo de navegacion
// 	$slug_modulo = Request::segment(1);
// 	$slug_sub_modulo = Request::segment(2);

// 	//	obtiene los modulos del usuario
// 	$modulos = Session::get('usuario.modulos_habilitados');

// 	//	si tiene modulos, por cada modulo verifica si es padre y le agrega el segmento actual (submodulo)
// 	if ( !empty($modulos) && count($modulos) > 0 ){
// 		// dd('modulos', $modulos, '$slug_modulo',$slug_modulo, '$slug_sub_modulo',$slug_sub_modulo);
// 		foreach ( $modulos as $modulo ){
// 			if(!is_array($modulo)){
// 				// si no es un array lo convertimos
// 				$modulo = (array) json_decode(stripslashes(json_encode($modulo)));
// 			}
// 			if(is_array($modulo)){
// 				if ( !empty($modulo['slug_padre']) && $modulo['slug_padre'] == $slug_modulo && $modulo['slug'] == $slug_modulo . '/' . $slug_sub_modulo ){
// 					$slug_modulo = Request::segment(1) . '.' . Request::segment(2);
// 				}
// 			}else{
// 				if ( $modulo->slug != null && $modulo->slug == $slug_modulo && $modulo->slug == $slug_modulo . '/' . $slug_sub_modulo ){
// 					$slug_modulo = Request::segment(1) . '.' . Request::segment(2);
// 				}
// 			}
// 		}
// 	}

// 	if ( empty($slug_modulo) || $slug_modulo == get_aplicacion_actual() || ( get_modulo_actual() == 'perfil' && get_aplicacion_actual() == 'empresas' ) ){
// 		$slug_modulo = get_usuario_modulo_actual_slug();
// 	}

// 	if ( view()->exists( str_replace('/', '.', $slug_modulo) . '.partials.nav') ){
// 		return str_replace('/', '.', $slug_modulo) . '.partials.nav';
// 	}

// 	return FALSE;
// }


