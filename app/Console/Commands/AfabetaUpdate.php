<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use App\Models\Conexion;

class AfabetaUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:alfabeta {fecha? : La fecha desde la que se desea actualizar, con formato yyyy-mm-dd, si se omite se toma la fecha de hoy}'; // =null: La fecha desde la que se desea actualizar, si se omite se toma la fecha de hoy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the drug price list in alfabeta';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $ch = curl_init();
            $fecha = $this->argument('fecha');
            if(!$fecha){
                // $fecha = Carbon::parse('2024-04-01');
                $f = Carbon::today();
                $f = $f->toAtomString();
                $f = str_replace(':', '%3A', $f);
                $f = str_replace('+', '%2B', $f);
            }else{
                $f = Carbon::parse($fecha);
                $f = $f->toAtomString();
                $f = str_replace(':', '%3A', $f);
                $f = str_replace('+', '%2B', $f);
            } 
            $url = env('API_ALFABETA').'productos.json?fechaActualizacion='.$f;
            Log::channel('alfabeta')->info('Ejecución desde lína de comando con php artisan update:alfabeta --------------');
            $this->info('Consultando api '.$url.' ...');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'accept: application/json',
            ));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            $resp = $response;
            Log::channel('alfabeta')->info('Fecha: '.$fecha.' Respuesta '.$url.': '.substr($resp, 0, 60).'..."');
            $this->info('Fecha: '.$fecha.' Respuesta API: '.substr($resp, 0, 100));
            if($response != null){
                curl_close($ch);
                $r = (array) json_decode($response);
                if(is_array($r)){
                    $count = sizeof($r);
                }
                Log::channel('alfabeta')->info('Cantidad de registros obtenidos: '.$count);
                $this->info('Cantidad de registros obtenidos: '.$count);
                $this->params = [
                    'p_fecha_proceso' => Carbon::now()->format('Ymd'),
                    'p_json' => json_encode($r)
                ];
                $this->info('Ejecutando Stored Procedure ...');
                $r = (array) json_decode($response);
                if(is_array($r)){
                    $count = sizeof($r);
                }
                $params = [
                    'p_fecha_proceso' => Carbon::now()->format('Ymd'),
                    'p_json' => json_encode($r)
                ];
                $sp_params = '';
                $sp_query_params = array();
                if (is_array($params) && count($params) > 0 && $params != '') {
                    //	arma el string de parametros
                    foreach ($params as $key => $value) {
                        $sp_params .= '@' . $key . ' = ?, ';
                        array_push($sp_query_params, $value);
                    }
                    //	limpia la ultima coma
                    $sp_params = rtrim($sp_params, ', ');
                }
                if(env('AMBIENTE') == 'local'){
                    Log::channel('alfabeta')->info('Ejecucion en '.env('AMBIENTE'));
                    $this->info('Entorno de ejecución: '.env('AMBIENTE'));
                    // Log::channel('alfabeta')->info(DB::connection('alfabeta')->select('SET NOCOUNT ON; EXEC sp_dummy'));
                    $ret = DB::connection('alfabeta')->select('SET NOCOUNT ON; EXEC sp_procesar_alfabeta ' . $sp_params, $sp_query_params);
                }else{
                    Log::channel('alfabeta')->info('Ejecucion en '.env('AMBIENTE'));
                    $this->info('Entorno de ejecución: '.env('AMBIENTE'));
                    $ret = DB::connection('alfabeta')->select('EXEC sp_procesar_alfabeta ' . $sp_params, $sp_query_params);
                }
                // Log::channel('alfabeta')->info(DB::connection('alfabeta')->getQueryLog());
                Log::channel('alfabeta')->info('Datos Actualizados: '.json_encode($ret[0]->resultado));
                $this->info('Datos Actualizados: '.json_encode($ret[0]->resultado));
                $this->info('Procedimiento finalizado');
                $this->newLine(1);
            }else{
                Log::channel('alfabeta')->info('No se insertaron datos en alfabeta, la url no devolvió datos.');
                $this->info('No se insertaron datos en alfabeta, la url no devolvió datos.');
                $this->info('Operacion finalizada');
                $this->newLine(1);
            }
        } catch (\Throwable $th) {
            Log::channel('alfabeta')->error('Line: '.$th->getLine().' - Error al ejecutar procedimiento: '.$th->getMessage());
            $this->newLine(1);
            $this->error('Line: '.$th->getLine().' - Error en procedimiento, consultar en /storage/logs/alfabeta.log');
            $this->newLine(1);
        } finally {
            if(is_resource($ch)){
                curl_close( $ch );
            }
            Log::channel('alfabeta')->info('..................');
        }
        return ;
    }
}

// Log::emergency($message);
// Log::alert($message);
// Log::critical($message);
// Log::error($message);
// Log::warning($message);
// Log::notice($message);
// Log::info($message);
// Log::debug($message);


// sp_procesar_alfabeta
// @p_fecha_proceso,
// @p_json

// sp_procesar_alfabeta
// @p_codigo int,
// @p_troquel int,
// @p_nombre varchar(50),
// @p_presentacion varchar(50),
// @p_importado bit,
// @p_heladera bit,
// @p_iva bit,
// @p_laboratorio varchar(50),
// @p_tipo_venta int,
// @p_tamano varchar(50),
// @p_gravado bit,
// @p_fecha_ultima_actualizacion datetime,
// @p_ultimo_precio decimal(13,2),
// @p_ultimo_precio_vigencia datetime,
// @p_codigo_barras varchar(50),
// @p_fecha_proceso datetime
// @p_json nvarchar(max)=null


// parametros para el sp
// {
//     "codigo": 0,
//     "troquel": 0,
//     "estado": "string",
//     "nombre": "string",
//     "presentacion": "string",
//     "tipoVenta": id,
//     "tamanio": id,
//     "monodroga": id,
//     "fechaUltimaActualizacion": "2024-05-13T13:25:18.440Z",
//     "ultimoPrecio": "string",
//     "ultimoPrecioVigencia": "2024-05-13T13:25:18.440Z"
// }