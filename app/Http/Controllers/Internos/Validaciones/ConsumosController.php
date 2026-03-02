<?php

namespace App\Http\Controllers\Internos\Validaciones;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

use App\Http\Controllers\ConexionSpController;

class ConsumosController extends ConexionSpController
{
    /**
     * Obtiene los consumos de un afiliado mediante AWEB_TraerConsumos
     * Endpoint interno AWEB_TraerConsumos
     * @param tipo_documento string en el request, tipo de documento del afiliado a consultar los consumos
     * @param numero_documento number en el request, numero de documento del afiliado a consultar los consumos
     * @param id_grupo number en el request, id del grupo, requierido si grupo_familiar_completo es 1
     * @param grupo_familiar_commpleto boolean, bit en el request, 0 o 1 indica si se van a buscar los consumos 
     * de todo el grupo familiar o sólo del afiliado
     */
    public function consultar_consumos_afiliado(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/validaciones/consultar-consumos';
        $this->permiso_requerido = 'consultar consumos afiliado';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_TraerConsumos';
        
        $this->params = [
            'tipo_documento' => request('tipo_documento'),
            'numero_documento' => request('numero_documento'),
            'id_grupo' => request('id_grupo'),
            'grupo_familiar_completo' => request('grupo_familiar_completo') !== NULL ? intVal(request('grupo_familiar_completo')) : 0,
            'prestadores_web' => request('prestadores_web') !== NULL ? intVal(request('prestadores_web')) : 0,
        ];
        $this->params_sp = [
            'tipo_documento' => $this->params['tipo_documento'],
            'numero_documento' => $this->params['numero_documento'],
            'id_grupo' => $this->params['id_grupo'],
            'grupo_familiar' => $this->params['grupo_familiar_completo'],
            'prestadores_web' => $this->params['prestadores_web']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Obtiene los consumos liquidados de un afiliado mediante AWEB_TraerConsumosLiquidados
     * Endpoint interno AWEB_TraerConsumos
     * @param tipo_documento string en el request, tipo de documento del afiliado a consultar los consumos
     * @param numero_documento number en el request, numero de documento del afiliado a consultar los consumos
     * @param id_grupo number en el request, id del grupo, requierido si grupo_familiar_completo es 1
     * @param grupo_familiar_commpleto boolean, bit en el request, 0 o 1 indica si se van a buscar los consumos 
     * de todo el grupo familiar o sólo del afiliado
     */
    public function consultar_consumos_liquidados(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/validaciones/consultar-consumos-liquidados';
        $this->permiso_requerido = 'consultar consumos afiliado';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_TraerConsumosLiquidados';
        
        $this->params = [
            'tipo_documento' => request('tipo_documento'),
            'numero_documento' => request('numero_documento'),
            'id_grupo' => request('id_grupo'),
            'grupo_familiar_completo' => request('grupo_familiar_completo') !== NULL ? intVal(request('grupo_familiar_completo')) : 0,
        ];
        $this->params_sp = [
            'tipo_documento' => $this->params['tipo_documento'],
            'numero_documento' => $this->params['numero_documento'],
            'id_grupo' => $this->params['id_grupo'],
            'grupo_familiar' => $this->params['grupo_familiar_completo']
        ];
        return $this->ejecutar_sp_simple();
    }
}