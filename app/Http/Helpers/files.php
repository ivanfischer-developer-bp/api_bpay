<?php

/**
* Devuelve el path de almacenamiento
*/
function get_url_imagenes($archivo = '')
{
	// return env('APP_URL') . '/statics/images' . ( $archivo != '' ? '/' . $archivo : '' );
}

/**
* Devuelve el path de almacenamiento
*/
function get_path_imagenes($archivo = '')
{
	// return storage_path() . ( $archivo != '' ? '/' . $archivo : '' );
	return env('IMAGE_PATH') . ( $archivo != '' ? '/' . $archivo : '' );
}

/**
* Devuelve el path de estilos
*/
// function get_path_estilos($archivo = '')
// {
// 	return storage_path() . '/../public/statics/styles' . ( $archivo != '' ? '/' . $archivo : '' );
// }

/**
* Devuelve el path de scripts
*/
// function get_path_scripts($archivo = '')
// {
// 	return storage_path() . '/../public/statics/scripts' . ( $archivo != '' ? '/' . $archivo : '' );
// }

/**
* Devuelve el path de almacenamiento de app bpay-health
*/
function get_path_almacenamiento($archivo = '')
{
	// return storage_path() . '/uploads' . ( $archivo != '' ? '/' . $archivo : '' );
	return env('UPLOADS_PATH_EXTERNO') . ( $archivo != '' ? '/' . $archivo : '' );
}

/**
* Devuelve el path de almacenamiento
*/
function get_path_log($archivo = '')
{
	return storage_path() . '/log' . ( $archivo != '' ? '/' . $archivo : '' );
}

/**
* Devuelve el path de almacenamiento
*/
function get_url_archivo($archivo)
{
	$extension = $archivo;
	$nombre = $archivo;
	return oh_file_url() . '/' . $extension . '/' . $nombre;
}

/**
* Devuelve tipos de archivos habilitados
* @param string  $tipo (opcional) null by default
* @param string  $formato (opcional) 'array' by default. puede ser 'string'
* @param boolean $un_array (opcional) ??
* @return mixed
*/
function get_tipos_de_archivos_habilitados($tipo = NULL, $formato = 'array', $un_array = FALSE)
{
    //  allowed file types
    $tipos = array(
        'image' => array(
            'image/gif',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/x-png'
        ),
		'audio' => array(
			'application/ogg',
			'audio/mp3',
			'audio/mp4',
			'audio/mpeg',
			'audio/mpeg3',
			'audio/mpg',
			'audio/ogg',
			'audio/wav',
			'audio/wave',
			'audio/x-aiff',
			'audio/x-matroska',
			'audio/x-mpeg',
			'audio/x-ms-wma',
			'audio/x-pn-realaudio',
			'audio/x-pn-realaudio-plugin',
			'audio/x-realaudio',
			'audio/x-wav',
		),
		'video' => array(
			'application/ogg',
			'application/vnd.rn-realmedia',
			'application/vnd.rn-realmedia-vbr',
			'application/x-mplayer2',
			'application/x-troff-msvideo',
			'video/3gpp',
			'video/avi',
			'video/mp4',
			'video/mpeg',
			'video/msvideo',
			'video/quicktime',
			'video/x-matroska',
			'video/x-mpeg',
			'video/x-ms-asf',
			'video/x-ms-asf-plugin',
			'video/x-ms-wmv',
			'video/x-ms-wmx',
			'video/x-msvideo',
        	'application/x-troff-msvideo',
        	'video/mpeg',
        	'video/msvideo',
		),
        'document' => array(
			'application/excel',
			'application/msexcel',
			'application/msword',
			'application/octet-stream',
			'application/pdf',
			'application/powerpoint',
			'application/vnd.ms-excel',
			'application/vnd.ms-office',
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/x-download',
			'application/x-stuffit',
			'application/x-tar',
			'application/x-zip',
			'application/x-zip-compressed',
			'application/zip',
			'text/plain',
			'text/rtf',
			'text/html',
        	// 'application/excel',
        	// 'application/msword',
        	// 'application/vnd.ms-excel',
        	'application/x-excel',
        	'application/x-msexcel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
        ),
		'portable-documents' => array(
			'application/pdf',
		),
		'csv-file' => array(
			'text/csv',
			'text/plain',
		),
		'images-and-portables-documents' => array(
			'image/gif',
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/x-png',
			'application/pdf'
		),
    );

    //	if type is not set or file type doesn't exist
    if ( $tipo == NULL || $tipo == 'all' || $tipo == 'todos' || !isset($tipos[$tipo]) )
    {
    	//	gets all file types
    	$tipos = $tipos;
    }
    else
    {
    	//	gets specific file type
    	$tipos = $tipos[$tipo];
    }

    //	returns depending on format
    if ( $formato == 'string' )
    {
    	//	if has keys
    	if ( isset($tipos['document']) )
    	{
    		//	creates string
    		$arrays = array();

    		//	foreach file_types
    		foreach ( $tipos as $tipo_array )
    			$arrays = array_merge($arrays, $tipo_array);
    		$tipos = $arrays;
    	}

    	return implode(',', $tipos);
    }
    else
	{
		if ( !$un_array )
		{
    		return $tipos;
		}
		else
		{
			$return_tipos = [];
			foreach ( $tipos as $tipo_array )
				if ( is_array($tipo_array) )
				{
					foreach ( $tipo_array as $tipo )
						array_push($return_tipos, $tipo);
				}
				else
				{
					array_push($return_tipos, $tipo_array);
				}
			return $return_tipos;
		}
	}
}

/**
* Devuelve la extensión del archivo
* @param string $archivo Archivo
* @return string
*/
function get_extension_archivo($archivo)
{
	$array = explode('.', $archivo);
	return $array[count($array)-1];
}

/**
* Devuelve el nombre del archivo
* @param string $archivo Archivo
* @return string
*/
function get_nombre_archivo($archivo)
{
	$extension = get_extension_archivo($archivo);
	return str_replace('.' . $extension, '', $archivo);
}

/**
* $objeto mixed Image Object
* $nombre_nuevo string Nombre del archivo
* $remover_original boolean Si tiene que eliminar el original
*/
function get_guardar_imagen($objeto, $nuevo_nombre = '', $path = '', $remover_original = TRUE)
{
	//	datos obligatorios
	if ( empty($objeto) || empty($objeto['filename']) || !Storage::disk('uploads')->exists($objeto['filename']) )
		return FALSE;

	//	convierte los paths
	$filename = $objeto['filename'];
	$just_filename = pathinfo($filename, PATHINFO_FILENAME);
	$extension = pathinfo($filename, PATHINFO_EXTENSION);
	$dest_name = $nuevo_nombre == '' ? $objeto['filename'] : $nuevo_nombre . '.' . $extension;
	$full_path_source = Storage::disk('uploads')->getDriver()->getAdapter()->applyPathPrefix($filename);
	$full_path_dest = Storage::disk('public_assets')->getDriver()->getAdapter()->applyPathPrefix($dest_name);

	//	crea la carpeta de destino (si no existe)
	if ( !File::exists(dirname($full_path_dest)) )
		File::makeDirectory(dirname($full_path_dest), null, true);

	//	si debe eliminar el original
	if ( $remover_original )
	{
		$saved = File::move($full_path_source, $full_path_dest);
	}
	//	si tiene que copiar el original
	else
	{
		$saved = File::copy($full_path_source, $full_path_dest);
	}

	if ( $saved )
	{
		return $dest_name;
	}
	else
	{
		return FALSE;
	}
}
