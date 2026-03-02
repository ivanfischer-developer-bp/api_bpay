<?php

/**
* Inicia una transacción
* @param string $db Base de Datos
*/
function get_begin_transaction($db = 'admin')
{
    DB::connection($db)->beginTransaction();
}

/**
* Completa una transacción
* @param string $db Base de Datos
*/
function get_commit($db = 'admin')
{
    DB::connection($db)->commit();
}

/**
* Cancela una transacción
* @param string $db Base de Datos
*/
function get_rollback($db = 'admin')
{
    DB::connection($db)->rollBack();
}

/**
* Elimina registros con WHERE simple
* @param string $db Base de Datos
* @param string $table Tabla
* @param array $where Key => Value del valor a eliminar
* @return boolean
*/
function get_eliminar_registros($db = 'admin', $table, $where)
{
    return DB::connection($db)->table($table)->where($where['key'], '=', $where['value'])->delete();
}

/**
* Valida si un insert se hizo correctamente
* Esta funcion es para ejecuciones al metodo insert de los objetos
* @param mixed $res Respuesta de la ejecución
* @param string $campo Nombre del campo (opcional)
* @return boolean
*/
function get_validar_insert($res, $campo = 'id')
{
    if ( !empty($res) && !empty($res[$campo]) && $res[$campo] > 0 )
        return TRUE;
    return FALSE;
}

/**
* Valida si un insert se hizo correctamente
* Esta funcion es para ejecuciones de un SP de Insert con el metodo ejecutar_sp
* @param mixed $res Respuesta de la ejecución
* @param string $campo Nombre del campo (opcional)
* @return boolean
*/
function get_validar_ejecucion_insert($res, $campo = 'id')
{
    if ( !empty($res) && !empty($res[0]) && !empty($res[0]->{$campo}) && $res[0]->{$campo} > 0 )
        return TRUE;
    return FALSE;
}

/**
* Valida si un update se hizo correctamente
* Esta funcion es para ejecuciones al metodo update de los objetos
* @param mixed $res Respuesta de la ejecución
* @param string $campo Nombre del campo (opcional)
* @return boolean
*/
function get_validar_update($res, $campo = 'filas')
{
    if ( !empty($res) && !empty($res[$campo]) && $res[$campo] > 0 )
        return TRUE;
    return FALSE;
}

/**
* Valida si un update se hizo correctamente
* Esta funcion es para ejecuciones de un SP de Update con el metodo ejecutar_sp
* @param mixed $res Respuesta de la ejecución
* @param string $campo Nombre del campo (opcional)
* @return boolean
*/
function get_validar_ejecucion_update($res, $campo = 'filas')
{
    if ( !empty($res) && !empty($res[0]) && !empty($res[0]->{$campo}) && $res[0]->{$campo} > 0 )
        return TRUE;
    return FALSE;
}
