<?php

namespace App\Http\Controllers\Internos\Prestadores;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class PrestadorController extends ConexionSpController
{
    /**
     * Busca la información de un prestador
     */
    public function buscar_prestador(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/prestadores/buscar-prestador';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_prestador_Select';
        if(request('codigo') != null){
            $this->params['codigo'] = request('codigo');
            $this->params_sp['p_cod_prestador'] = request('codigo');
        }
        if(request('nombre') != null){
            $this->params['nombre'] = request('nombre');
            $this->params_sp['p_nombre'] = request('nombre');
        }
        if(request('cuit') != null){
            $this->params['cuit'] = request('cuit');
            $this->params_sp['p_cod_cuit'] = request('cuit');
        }
        return $this->ejecutar_sp_simple();
    }


    /**
     * Agrega un nuevo prestador
     */
    public function agregar_prestador(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/prestadores/agregar-prestador';
        $this->permiso_requerido = 'gestionar prestadores';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_prestador_insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->verificado = [
            'razon_social' => request('razon_social'), 
            'cuit' => request('cuit'),
            'matricula' => request('matricula'),
            'tipo_prestador' => request('tipo_prestador'),
            'tipo_matricula' => request('tipo_matricula'),
            'telefonos' => request('telefonos'),
            'email' => request('email')
        ];
        if(empty(request('razon_social')) 
            || empty(request('cuit')) 
            || empty(request('tipo_prestador'))
            || empty(request('matricula')) 
            || empty(request('tipo_matricula'))
            || empty(request('telefonos'))
            || empty(request('email'))
            ){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'razon_social' => request('razon_social'),
            'cuit' => request('cuit'),
            'tipo_prestador' => request('tipo_prestador'),
            'matricula' => request('matricula'),
            'tipo_matricula' => request('tipo_matricula'),
            'telefonos' => request('telefonos'),
            'email' => request('email')
        ];
        $this->params_sp = [
            'p_razon_social' => request('razon_social'),
            'p_cuit' => request('cuit'),
            'p_matricula' => request('matricula'),
            'p_id_tipo_prestador' => request('tipo_prestador') == 'prescriptor' ? 1 : 0,
            'p_matricula_provincia' => request('tipo_matricula') == 'MN' ? 1 : 0,
            'p_fec_alta' => Carbon::now()->format('Ymd'),
            'p_telefonos' => request('telefonos'),
            'email' => request('email')
        ];
        if(request('fecha_nacimiento') != null){
            $this->params['fecha_nacimiento'] = request('fecha_nacimiento');
            $this->params_sp['p_fec_nac'] = Carbon::parse(request('fecha_nacimiento'))->format('Ymd');
        }
        if(request('provincia_matricula') != null){
            $this->params['provincia_matricula'] = request('provincia_matricula');
            $this->params_sp['p_provincia'] = request('provincia_matricula');
        }
        if(request('localidad') != null){
            $this->params['localidad'] = request('localidad');
            $this->params_sp['p_localidad'] = request('localidad');
        }
        if(request('domicilio') != null){
            $this->params['domicilio'] = request('domicilio');
            $this->params_sp['p_domicilio'] = request('domicilio');
        }
        return $this->ejecutar_sp_simple();
    }
    
}