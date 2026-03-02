<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use App\Models\Conexion;

class AlfabetaActualizar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alfabeta:actualizar {parametro? : El nombre, prescripcion, principio activo, troquel o laboratorio del medicamento, si se omite se actualiza el padrón entero, puede ser costoso en recursos}'; // =null: La fecha desde la que se desea actualizar, si se omite se toma la fecha de hoy}';

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
        $fecha = Carbon::now();    
        $parametro = $this->argument('parametro');
        $this->info('Iniciando actualización...');
        $this->comment('Fecha y hora de inicio: '.$fecha);
        $this->line('parametro: '.$parametro);
    }

}

// $this->info('Texto en verde');           // Verde
// $this->error('Texto en rojo');           // Rojo
// $this->warn('Texto en amarillo');        // Amarillo/naranja
// $this->line('Texto normal');             // Color por defecto
// $this->comment('Texto en gris');         // Gris/comentario
// $this->question('¿Pregunta?');           // Cyan/azul
// $this->alert('¡Alerta!');                // Rojo con fondo
// $this->line('<fg=red>Texto rojo</>');    // Tags personalizados
// $this->line('<bg=blue>Fondo azul</>');   // Tags personalizados

// Log::emergency($message);
// Log::alert($message);
// Log::critical($message);
// Log::error($message);
// Log::warning($message);
// Log::notice($message);
// Log::info($message);
// Log::debug($message);