<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMDocumentacionAfiliadoController extends ConexionSpController
{
    /**
     * Obtiene un listado de documentacion de afiliados
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_documentacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/documentacion-afiliado/listar-documentacion';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_documentacion_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Añade un nueva documentación de afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_documentacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/documentacion-afiliado/agregar-documentacion';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_documentacion_insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario
        $this->verificado = [
            'n_documentacion' => request('n_documentacion')
        ];
        if(empty(request('n_documentacion'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_documentacion' => request('n_documentacion'),
            'obligatorio' => request('obligatorio'),
            'consumo' => request('consumo'),
            'vencimiento' => request('vencimiento'),
            'grupo' => request('grupo'),
            'me' => request('me'), // modifica edad
        ];
        $this->params_sp = [
            'n_documentacion' => $this->params['n_documentacion'],
            'obligatorio' => $this->params['obligatorio'],
            'consumo' => $this->params['consumo'],
            'vencimiento' => $this->params['vencimiento'],
            'grupo' => $this->params['grupo'],
            'me' => $this->params['me'],
            'fecha' => Carbon::now()->format('Ymd H:i:s'),
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza los datos de una documentación de afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_documentacion(Request $request)
    {
        
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/documentacion-afiliado/actualizar-documentacion';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_documentacion_update';
        $this->verificado = [
            'n_documentacion' => request('n_documentacion')
        ];
        if(empty(request('n_documentacion'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_documentacion' => request('id_documentacion'),
            'n_documentacion' => request('n_documentacion'),
            'obligatorio' => request('obligatorio'),
            'consumo' => request('consumo'),
            'vencimiento' => request('vencimiento'),
            'grupo' => request('grupo'),
            'me' => request('me') // modifica edad
        ];
        $this->params_sp = [
            'id_documentacion' => $this->params['id_documentacion'],
            'n_documentacion' => $this->params['n_documentacion'],
            'obligatorio' => $this->params['obligatorio'],
            'consumo' => $this->params['consumo'],
            'vencimiento' => $this->params['vencimiento'],
            'grupo' => $this->params['grupo'],
            'me' => $this->params['me']
        ];
        return $this->ejecutar_sp_simple();
    }
}