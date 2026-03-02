<?php namespace App\Models;

use App\Models\Modelo;

class Conexion extends Modelo
{
    function __construct($connection = null, $id_key = '')
    {
        //  establece la base de datos
        if(is_null($connection)){
            return 'No se estableció la base de datos a utilizar.';
        } else {
            $this->database = $connection;
        }

        //  establece el nombre del campo que contiene la primary key
        $this->idKey = $id_key;
    }
}