<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\Cors;

// generales
use App\Http\Controllers\PruebasController;
use App\Http\Controllers\PusherController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UploadController;

// administrativos
use App\Http\Controllers\Admin\AlfabetaController;
use App\Http\Controllers\Admin\ComprobacionesController;
use App\Http\Controllers\Admin\InformesSistemaController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileDoctorController;
use App\Http\Controllers\Admin\ProfileSecretaryController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SistemaController;
use App\Http\Controllers\Admin\SolicitudSoporteController;
use App\Http\Controllers\Admin\UserController;

// autorizacion
use App\Http\Controllers\Auth\AuthController;

// mobile app de Sergio
use App\Http\Controllers\Mobile\MobileAfiliadoController;
use App\Http\Controllers\Mobile\MobileAuthController;
use App\Http\Controllers\Mobile\MobileConsultasController;
use App\Http\Controllers\Mobile\MobileExportarFormulariosCronicosController;
use App\Http\Controllers\Mobile\MobileFileController;
use App\Http\Controllers\Mobile\MobileGrupoFamiliarController;
use App\Http\Controllers\Mobile\MobilePreautorizacionesController;
use App\Http\Controllers\Mobile\MobileReintegroController;
use App\Http\Controllers\Mobile\Recetas\MobileExportarRecetaController;
use App\Http\Controllers\Mobile\Recetas\MobilePrescripcionController;
use App\Http\Controllers\Mobile\Recetas\MobileRecetaCertificadoController;
use App\Http\Controllers\Mobile\Recetas\MobileRecetasController;

// portal
use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalController;

// externos
use App\Http\Controllers\Externos\ExternalListadosController;
use App\Http\Controllers\Externos\ExternalFileController;
use App\Http\Controllers\Externos\Salud\Afiliaciones\ExternalAfiliacionController;
use App\Http\Controllers\Externos\Salud\Auditorias\ExternalAuditoriaEnTerrenoController;
use App\Http\Controllers\Externos\Salud\Coberturas\ExternalCoberturaEspecialController;
use App\Http\Controllers\Externos\Salud\Diagnosticos\ExternalDiagnosticoController;
use App\Http\Controllers\Externos\Salud\Recetas\ExternalRecetasController;
use App\Http\Controllers\Externos\Salud\Validaciones\ExternalAmbulatorioController;
use App\Http\Controllers\Externos\Salud\Validaciones\ExternalInternacionController;
use App\Http\Controllers\Externos\Salud\Validaciones\ExternalValidacionController;

// internos
use App\Http\Controllers\Internos\Afiliaciones\AfiliacionController;
use App\Http\Controllers\Internos\Afiliaciones\AfiliadoController;
use App\Http\Controllers\Internos\Afiliaciones\CredencialController;
use App\Http\Controllers\Internos\Afiliaciones\CuentaCorrienteController;
use App\Http\Controllers\Internos\Afiliaciones\FacturacionController;
use App\Http\Controllers\Internos\Afiliaciones\GrupoFamiliarController;
use App\Http\Controllers\Internos\Afiliaciones\HistoriaClinicaController;
use App\Http\Controllers\Internos\Afiliaciones\PersonaContactoController;
use App\Http\Controllers\Internos\Afiliaciones\PersonaController;
use App\Http\Controllers\Internos\Afiliaciones\PersonaDomicilioController;
use App\Http\Controllers\Internos\Afiliaciones\PlanController;
use App\Http\Controllers\Internos\Auditorias\AuditoriaMedicaController;
use App\Http\Controllers\Internos\Auditorias\AuditoriaTerrenoController;
use App\Http\Controllers\Internos\Coberturas\CoberturaEspecialController;
use App\Http\Controllers\Internos\Configuraciones\CarenciaController;
use App\Http\Controllers\Internos\Configuraciones\EstadoGrupoController;
use App\Http\Controllers\Internos\Configuraciones\MenuPrestacionalController;
use App\Http\Controllers\Internos\ConsultasExternasController;
use App\Http\Controllers\Internos\Consultorio\TurnosController;
use App\Http\Controllers\Internos\Diagnosticos\DiagnosticoController;
use App\Http\Controllers\Internos\Emails\EmailController;
use App\Http\Controllers\Internos\Emails\EmailsAfiliacionesController;
use App\Http\Controllers\Internos\Emails\EmailsConsultorioController;
use App\Http\Controllers\Internos\Emails\EmailsEncuestasController;
use App\Http\Controllers\Internos\Emails\EmailsExpedientesController;
use App\Http\Controllers\Internos\Emails\EmailsFormulariosController;
use App\Http\Controllers\Internos\Emails\EmailsRecetasController;
use App\Http\Controllers\Internos\Emails\EmailsSolicitudSoporteController;
use App\Http\Controllers\Internos\Emails\EmailsUsuariosController;
use App\Http\Controllers\Internos\Emails\EmailsValidacionesController;
use App\Http\Controllers\MicrosoftGraphAuthController;
use App\Http\Controllers\Internos\EncuestasController;
use App\Http\Controllers\Internos\EntornoFrontendController;
use App\Http\Controllers\Internos\Exportaciones\ExportarAfiliadoController;
use App\Http\Controllers\Internos\Exportaciones\ExportarCoberturaEspecialController;
use App\Http\Controllers\Internos\Exportaciones\ExportarFormulariosCronicosController;
use App\Http\Controllers\Internos\Exportaciones\ExportarHistoriaClinicaController;
use App\Http\Controllers\Internos\Exportaciones\ExportarInformeController;
use App\Http\Controllers\Internos\Exportaciones\ExportarPatologiaController;
use App\Http\Controllers\Internos\Exportaciones\ExportarPermisosController;
use App\Http\Controllers\Internos\Exportaciones\ExportarPrestacionController;
use App\Http\Controllers\Internos\Exportaciones\ExportarRecetaController;
use App\Http\Controllers\Internos\Exportaciones\ExportarRolesController;
use App\Http\Controllers\Internos\Exportaciones\ExportarTurnosController;
use App\Http\Controllers\Internos\Exportaciones\ExportarUsuariosController;
use App\Http\Controllers\Internos\Exportaciones\ExportarValidacionController;
use App\Http\Controllers\Internos\General\BajaController;
use App\Http\Controllers\Internos\General\FileController;
use App\Http\Controllers\Internos\General\InformesController;
use App\Http\Controllers\Internos\General\LocalidadController;
use App\Http\Controllers\Internos\General\PhoneMessagesController;
use App\Http\Controllers\Internos\General\TableroController;
use App\Http\Controllers\Internos\Internaciones\InternacionController;
use App\Http\Controllers\Internos\Listados\ABMActividadController;
use App\Http\Controllers\Internos\Listados\ABMConvenioController;
use App\Http\Controllers\Internos\Listados\ABMDocumentacionAfiliadoController;
use App\Http\Controllers\Internos\Listados\ABMEstadoCivilController;
use App\Http\Controllers\Internos\Listados\ABMGravamenController;
use App\Http\Controllers\Internos\Listados\ABMObraSocialController;
use App\Http\Controllers\Internos\Listados\ABMOrigenAfiliadoController;
use App\Http\Controllers\Internos\Listados\ABMOrigenMatriculaController;
use App\Http\Controllers\Internos\Listados\ABMParentescoController;
use App\Http\Controllers\Internos\Listados\ABMPatologiaController;
use App\Http\Controllers\Internos\Listados\ABMPlanController;
use App\Http\Controllers\Internos\Listados\ABMPromotorController;
use App\Http\Controllers\Internos\Listados\ABMRangoEdadController;
use App\Http\Controllers\Internos\Listados\ABMTipoBajaController;
use App\Http\Controllers\Internos\Listados\ABMTipoConceptoController;
use App\Http\Controllers\Internos\Listados\ABMTipoContactoController;
use App\Http\Controllers\Internos\Listados\ABMTipoDomicilioController;
use App\Http\Controllers\Internos\Listados\ABMTipoFacturaController;
use App\Http\Controllers\Internos\Listados\ListadosController;
use App\Http\Controllers\Internos\MensajeController;
use App\Http\Controllers\Internos\NotificacionController;
use App\Http\Controllers\Internos\Prestadores\PrestacionController;
use App\Http\Controllers\Internos\Prestadores\PrestacionesController;
use App\Http\Controllers\Internos\Prestadores\PrestadorController;
use App\Http\Controllers\Internos\Prestadores\PrestadoresController;
use App\Http\Controllers\Internos\ProgramasEspecialesController;
use App\Http\Controllers\Internos\Recetas\RecetasController;
use App\Http\Controllers\Internos\Recetas\PrescripcionController;
use App\Http\Controllers\Internos\Recetas\RecetaCertificadoController;
use App\Http\Controllers\Internos\UsuariosSqlserverController;
use App\Http\Controllers\Internos\Validaciones\CajaValidacionesController;
use App\Http\Controllers\Internos\Validaciones\ConsumosController;
use App\Http\Controllers\Internos\Validaciones\MovimientosValidacionController;
use App\Http\Controllers\Internos\Validaciones\ValidacionController;
use App\Http\Controllers\Internos\Validaciones\ValidacionesController;
use App\Http\Controllers\Internos\Validaciones\PreautorizacionesController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// consultar disonibilidad del sitio
Route::get('site', [SiteController::class, 'index']);

// rutas para plataforma
Route::group(['prefix' => 'plataforma'], 
    function() {
        // auth
        Route::group(['prefix' => 'auth',], 
            function() {
                // login
                Route::post('login', [AuthController::class, 'plataforma_login']);  // plataforma/auth/login  1.1.537-20250723
                // reseteo password
                Route::post('enviar-token-reseteo-password', [AuthController::class, 'plataforma_enviar_token_reseteo_password']);  // plataforma/auth/enviar-token-reseteo-clave  1.1.537-20250723
                Route::post('resetear-password', [AuthController::class, 'plataforma_resetear_password']); // plataforma/auth/resetear-clave  1.1.537-20250723
                
                Route::group(['middleware' => 'auth:api'], 
                    function(){
                        Route::post('password-change', [AuthController::class, 'plataforma_password_change']); // plataforma/auth/password-change  1.1.537-20250723
                    }
                );
            }
        );  
    }
);  

// rutas externas
Route::group(['prefix' => 'ext'], 
    function() {
        // auth
        Route::group(['prefix' => 'auth',], 
            function() {
                // login
                Route::post('login', [AuthController::class, 'external_login']);
                // salud/registro
                Route::group(['prefix' => 'salud'], 
                    function(){
                        Route::post('register', [AuthController::class, 'external_salud_register']); // ext/auth/salud/register
                    }
                );
                // logout, user, update-user, password-change
                Route::group(['middleware' => 'auth:api'], 
                    function(){
                        Route::get('user', [AuthController::class, 'external_user']); //ext/auth/user
                        Route::get('logout', [AuthController::class, 'external_logout']); //ext/auth/logout
                        Route::post('update-user', [AuthController::class, 'external_update_user']); //ext/auth/update-user
                        Route::post('password-change', [AuthController::class, 'external_password_change']); // ext/auth/password-change
                    }
                );
            }
        );

        Route::group(['prefix' => 'archivos'], 
            function() {
                Route::get('ver', [ExternalFileController::class, 'view']); // ext/archivos/ver
            }
        );

        Route::group(['middleware' => 'auth:api'], 
            function(){
                // salud
                Route::group(['prefix' => 'salud'], 
                    function(){
                        // afiliaciones
                        Route::group(['prefix' => 'afiliaciones'], 
                            function(){
                                Route::get('elegibilidad', [ExternalAfiliacionController::class, 'elegibilidad']); // ext/salud/afiliaciones/elegibilidad
                                Route::get('buscar-afiliado', [ExternalAfiliacionController::class, 'buscar_afiliado']); // ext/salud/afiliaciones/buscar-afiliado
                            }
                        );
                        // validaciones
                        Route::group(['prefix' => 'validaciones'], 
                            function() {
                                Route::post('ambulatorio', [ExternalAmbulatorioController::class, 'store']); // ext/salud/validaciones/ambulatorio
                                Route::post('ambulatorio-faba', [ExternalAmbulatorioController::class, 'ambulatorio_faba']); // ext/salud/validaciones/ambulatorio-faba
                                // Route::post('internacion', [ExternalInternacionController::class, 'store']); // ext/salud/validaciones/internacion
                                Route::post('anular', [ExternalValidacionController::class, 'anular']); // ext/salud/validaciones/anular
                                Route::get('consultar', [ExternalValidacionController::class, 'consultar']); // ext/salud/validaciones/consultar
                                Route::post('consultar-cobertura', [ExternalValidacionController::class, 'consultar_cobertura']); // ext/salud/validaciones/consultar-cobertura  AB-75  
                                Route::group(['prefix' => 'internacion'], 
                                    function(){
                                        Route::post('internacion', [ExternalInternacionController::class, 'store']); // ext/salud/validaciones/internacion
                                        Route::post('/', [ExternalInternacionController::class, 'store']); // ext/salud/validaciones/internacion
                                        Route::post('generar-egreso', [ExternalInternacionController::class, 'generar_egreso_automatico']); // ext/salud/validaciones/internacion/generar-egreso  AB-74      
                                    }
                                );
                            }
                        );
                        // Auditorias
                        Route::group(['prefix' => 'auditorias'], 
                            function(){
                                // en terreno
                                Route::group(['prefix' => 'terreno'], 
                                    function(){
                                        Route::post('actualizar', [ExternalAuditoriaEnTerrenoController::class, 'actualizar']); //ext/salud/auditorias/terreno/actualizar  AB-73
                                    }
                                );
                            }
                        );
                        // listados
                        Route::group(['prefix' => 'listados'], 
                            function(){
                                Route::get('tipos-documentos', [ExternalListadosController::class, 'consultar_tipos_documentos']); // ext/salud/listados/tipos-documentos
                            }
                        );
                        // diagnosticos
                        Route::group(['prefix' => 'diagnosticos'], 
                            function() {
                                Route::get('consultar', [ExternalDiagnosticoController::class, 'consultar']); // ext/salud/diagnosticos/consultar
                            } 
                        );
                        // coberturas especiales
                        Route::group(['prefix' => 'coberturas-especiales'], 
                            function() {
                                Route::get('consultar', [ExternalCoberturaEspecialController::class, 'consultar']); // ext/salud/coberturas-especiales/consultar
                                Route::get('consultar-medicamentos', [ExternalCoberturaEspecialController::class, 'consultar_medicamentos']); // ext/salud/coberturas-especiales/consultar-medicamentos
                            }
                        );
                        // recetas
                        Route::group(['prefix' => 'recetas'],
                            function(){
                                Route::get('buscar-diagnosticos', [ExternalRecetasController::class, 'buscar_diagnosticos']); // ext/salud/recetas/buscar-diagnosticos    
                                Route::get('buscar-medicamentos', [ExternalRecetasController::class, 'buscar_medicamentos']); // ext/salud/recetas/buscar-diagnosticos    
                                Route::post('generar-receta', [ExternalRecetasController::class, 'generar_receta']); // ext/salud/recetas/buscar-diagnosticos    
                            }
                        );
                    }
                );
            }
        );
    }
);

// rutas para comprobaciones sin middleware auth
Route::group(['prefix' => 'comprobar'], 
    function(){
        Route::post('email-existente', [ComprobacionesController::class, 'email_existente']);  // comprobar/email-existente  
        Route::post('usuario-existente', [ComprobacionesController::class, 'usuario_existente']);  // comprobar/usuario-existente  
        Route::post('nroDoc-existente', [ComprobacionesController::class, 'nroDoc_existente']);  // comprobar/nroDoc-existente  
    }
);

/**
 * Desde aquí tiene que ver con el proyecto nuevo
 * Se puede eliminar del entregable si no se entrega el proyecto nuevo
 */

// rutas para la aplicacion mobile de Sergio
Route::group(['prefix' => 'mobile'], 
    function() {
        // auth
        Route::group(['prefix' => 'auth',], 
            function() {
                // login
                Route::post('registrar-usuario-afiliado', [MobileAuthController::class, 'registrar_usuario_afiliado']); // mobile/auth/registrar-usuario-afiliado  // 1.1.455-20250508
                Route::get('verificar-email', [MobileAuthController::class, 'verificar_email']); // mobile/auth/verificar-email // 1.1.440-20250501
                Route::post('enviar-email-registro-afiliado', [EmailsUsuariosController::class, 'enviar_email_registro_afiliado']); // mobile/auth/enviar-email-registro-afiliado  ** sólo para pruebas **  // 1.1.440-20250501
                Route::post('login-afiliado', [MobileAuthController::class, 'login_mobile']); // mobile/auth/login-afiliado  // 1.1.440-20250501
                Route::post('enviar-token-reseteo-password-afiliado', [MobileAuthController::class, 'enviar_token_reseteo_password_afiliado']);  // mobile/auth/enviar-token-reseteo-password-afiliado  // 1.1.456-20250508
                Route::post('resetear-password-afiliado', [MobileAuthController::class, 'resetear_password_afiliado']); // mobile/auth/resetear-password-afiliado 
                Route::get('enviar-nombre-usuario', [MobileAuthController::class, 'enviar_nombre_usuario']);  // mobile/auth/enviar-nombre-usuario  // 1.1.548-20250801
                // logout, user, update-user, password-change
                Route::group(['middleware' => 'auth:api'], 
                    function(){
                        Route::get('consultar-dispositivos', [MobileAuthController::class, 'consultar_dispositivos']); // mobile/auth/consultar-dispositivos  // 1.1.625-20251120
                        Route::post('registrar-dispositivo', [MobileAuthController::class, 'registrar_dispositivo']); // mobile/auth/registrar-dispositivo  // 1.1.625-20251120
                        Route::get('logout-afiliado', [MobileAuthController::class, 'logout_afiliado']); // mobile/auth/logout-afiliado // 1.1.441-20250505
                        Route::post('cambiar-password-afiliado', [MobileAuthController::class, 'cambiar_password_afiliado']); // mobile/auth/cambiar-password-afiliado  // 1.1.446-20250505
                        Route::post('update-user-afiliado', [MobileAuthController::class, 'update_user_afiliado']); // mobile/auth/update-user-afiliado  // 1.1.442-20250505
                        Route::get('user-afiliado', [MobileAuthController::class, 'user_afiliado']); // mobile/auth/user-afiliado // 1.1.442-20250505
                        Route::post('actualizar-fcm-token', [MobileAuthController::class, 'actualizar_fcm_token']); // mobile/auth/actualizar-fcm-token  // 1.1.616-20251117
                    }
                );
            }
        );
        // logueado
        Route::group(['middleware' => 'auth:api'], 
            function(){
                Route::group(['prefix' => 'consultas'], 
                    function() {
                        Route::get('consultar-reintegro', [MobileConsultasController::class, 'consultar_reintegro']);  // mogile/consultas/consultar-reintegro  // 1.1.443-20250505
                        Route::get('consultar-recetas', [MobileConsultasController::class, 'consultar_recetas']);  // mogile/consultas/consultar-recetas  // 1.1.444-20250505
                        Route::get('consultar-validaciones', [MobileConsultasController::class, 'consultar_validaciones']);  // mogile/consultas/consultar-validaciones  // 1.1.444-20250505
                        Route::get('consultar-prestaciones-validacion', [MobileConsultasController::class, 'consultar_prestaciones_validacion']);  // mogile/consultas/consultar-prestaciones-validacion  // 1.1.450-20250508
                        Route::get('consultar-elegibilidad-afiliado', [MobileConsultasController::class, 'consultar_elegibilidad_afiliado']);  // mobile/consultas/consultar-elegibilidad-afiliado  // 1.1.451-20250508
                    }
                );
                Route::group(['prefix' => 'reintegros'],
                    function(){
                        Route::post('agregar-reintegro', [MobileReintegroController::class, 'agregar_reintegro']); // mobile/reintegros/agregar-reintegro  // 1.1.522-20250704
                    }
                );
                Route::group(['prefix' => 'afiliaciones'], 
                    function() {
                        Route::group(['prefix' => 'afiliado'],
                            function() {
                                Route::get('buscar-afiliado', [MobileAfiliadoController::class, 'buscar_afiliado']); // mobile/afiliaciones/afiliado/buscar-afiliado  // 1.1.445-20250505
                            }
                        );
                        Route::group(['prefix' => 'grupo-familiar'],
                            function() {
                                Route::get('buscar-grupo-familiar', [MobileGrupoFamiliarController::class, 'buscar_grupo_familiar']); // mobile/afiliaciones/grupo-familiar/buscar-grupo-familiar  // 1.1.445-20250505
                            }
                        );
                    }
                );
                Route::group(['prefix' => 'archivos'], 
                    function() {
                        Route::post('subir-archivo', [MobileFileController::class, 'subir_archivo']); // mebile/archivos/subir-archivo
                        Route::post('eliminar-archivo', [MobileFileController::class, 'eliminar_archivo']); // mebile/archivos/eliminar-archivo
                    }
                );
                Route::group(['prefix' => 'preautorizaciones'], 
                    function() {
                        Route::post('crear-preautorizacion', [MobilePreautorizacionesController::class, 'crear_preautorizacion']);  // mobile/preautorizaciones/crear-preautorizacion  // 1.1.583-20250924
                        Route::get('buscar-preautorizacion', [MobilePreautorizacionesController::class, 'buscar_preautorizacion']);  // mobile/preautorizaciones/buscar-preautorizacion  // 1.1.583-20250924
                        Route::get('buscar-tipo-preautorizacion', [MobilePreautorizacionesController::class, 'buscar_tipo_preautorizacion']);  // mobile/preautorizaciones/buscar-tipo-preautorizacion  // 1.1.583-20250924
                    }
                );
                Route::group(['prefix' => 'recetas'], 
                    function() {
                        Route::get('buscar-receta', [MobileRecetasController::class, 'buscar_receta']); // mobile/recetas/buscar 1.1.612-20251113
                        Route::get('diagnosticos', [MobileRecetasController::class, 'get_diagnosticos']); // mobile/recetas/diagnosticos 1.1.612-20251113
                        Route::get('financiadores', [MobileRecetasController::class,  'get_financiadores']); // mobile/recetas/financiadores  1.1.612-20251113
                        Route::get('generar-pdf-receta', [MobileExportarRecetaController::class, 'generar_pdf_receta']);  // mobile/recetas/generar-pdf-receta  1.1.612-20251113
                        Route::get('listar-recetas-emitidas', [MobileRecetasController::class, 'listar_recetas_emitidas']); // mobile/recetas/listar-recetas-emitidas  1.1.612-20251113 
                        Route::get('medicamentos', [MobileRecetasController::class, 'get_medicamentos']); // mobile/recetas/medicamentos 1.1.612-20251113
                        Route::get('medicos', [MobileRecetasController::class, 'get_medicos']); // mobile/recetas/medicos 1.1.612-20251113  
                        Route::post('anular-receta', [MobileRecetasController::class, 'anular_receta']); // int/recetas/anular-receta  1.1.612-20251113   
                        Route::post('generar-receta', [MobileRecetasController::class, 'generar_receta_medicamentos']); // mobile/recetas/generar-receta 1.1.612-20251113 
                        // prescripciones
                        Route::post('generar-prescripcion-practicas', [MobilePrescripcionController::class, 'generar_prescripcion_practicas']); // mobile/recetas/generar-prescripcion-practicas  1.1.612-20251113
                        Route::get('listar-prescripciones-emitidas', [MobilePrescripcionController::class, 'listar_prescripciones_emitidas']); // mobile/recetas/listar-prescripciones-emitidas  1.1.612-20251113
                        Route::get('practicas', [MobilePrescripcionController::class, 'get_practicas']); // mobile/recetas/practicas 1.1.612-20251113
                        Route::get('tipo-y-categoria-practicas', [MobilePrescripcionController::class, 'get_tipo_y_categoria_practicas']); // mobile/recetas/tipo-categoria-practicas  1.1.612-20251113
                        // certificados medicos
                        Route::post('generar-receta-certificado', [MobileRecetaCertificadoController::class, 'generar_receta_certificado']); // mobile/recetas/generar-receta-certificado  1.1.612-20251113
                        Route::get('listar-recetas-certificados-emitidos', [MobileRecetaCertificadoController::class, 'listar_recetas_certificados_emitidos']); // mobile/recetas/listar-recetas-certificados-emitidos  1.1.612-20251113
                    }
                );
                Route::group(['prefix' => 'formularios'],
                    function(){
                        Route::post('exportar-formulario-cronicos', [MobileExportarFormulariosCronicosController::class, 'exportar_formulario_cronicos']); // mobile/formularios/exportar-formulario-310-vacio  1.1.713-20260309
                        Route::post('exportar-recetario-tratamientos-cronicos', [MobileExportarFormulariosCronicosController::class, 'exportar_recetario_tratamientos_cronicos']); // mobile/formularios/exportar-recetario-tratamientos-cronicos-vacio  1.1.702-20260227
                    }
                );
            }
        );
    }
);

// rutas para portal 
Route::group(['prefix' => 'portal'],
    function() {
        // auth
        Route::group(['prefix' => 'auth'],
            function() {
                Route::post('login', [PortalAuthController::class, 'login']); // portal/auth/login  // 1.1.463-20250515
                Route::group(['middleware' => 'auth:api'], 
                    function() {
                        Route::post('cambiar-password', [PortalAuthController::class, 'cambiar_password']); // portal/auth/cambiar-password  // 1.1.463-20250515
                        Route::post('password-reset', [PortalAuthController::class, 'password_reset']); // portal/auth/password-reset  // 1.1.653-20251218
                    }
                );
            }
        );
        // logueado
    }
);

// rutas para administración del sitio new Bpay
Route::group(['prefix' => 'admin',
        'middleware' => 'auth:api'
    ], function(){
        // funciones administrativas
        Route::group(['prefix' => 'user'], 
            function() {
                Route::post('register', [UserController::class, 'store']);  // admin/user/register  
                Route::post('update', [UserController::class, 'update']);  // admin/user/update  
                Route::post('delete', [UserController::class, 'delete']);  // admin/user/delete  
                Route::post('restore', [UserController::class, 'restore']); // admin/user/restore  
                Route::post('destroy', [UserController::class, 'destroy']);  // admin/user/destroy  
                Route::post('password-reset', [UserController::class, 'password_reset']);  // admin/user/password-reset
                Route::get('list', [UserController::class, 'index']);  //admin/user/list 
                Route::get('consultar-usuario-sqlserver', [UserController::class, 'consultar_usuario_sqlserver']); // 1.1.655-20251218
                Route::get('buscar-usuarios', [UserController::class, 'buscar_usuarios']); // admin/user/buscar-usuarios   1.1.642-20251205
                Route::get('email-exist', [UserController::class, 'email_exist']);  // admin/user/email-exist
                Route::get('usuario-exist', [UserController::class, 'usuario_exist']);  // admin/user/usuario-exist
                Route::post('exportar-usuarios', [ExportarUsuariosController::class, 'exportar_usuarios']); // admin/user/exportar-usuarios  1.1.651-20251203
                Route::get('role', [RoleController::class, 'index']);  // admin/user/role
                Route::group(['prefix' => 'role'], 
                    function() {
                        Route::post('assign', [UserController::class, 'asignar_rol']);  // admin/user/role/assign
                        Route::post('remove', [UserController::class, 'quitar_rol']); // admin/user/role/remove
                    }
                );
                Route::get('permissions', [PermissionController::class, 'index']);
                Route::group(['prefix' => 'permission'], 
                    function() {
                        Route::post('assign', [UserController::class, 'asignar_permiso']); // admin/user/permission/assign
                        Route::post('revoke', [UserController::class, 'quitar_permiso']);  // admin/user/permission/revoke
                        Route::post('synchronize', [UserController::class, 'sincronizar_permisos']);  // admin/user/permission/synchronize
                    }
                );
                Route::post('cambiar-estado-perfil', [UserController::class, 'cambiar_estado_perfil']); //  /admin/user/cambiar-estado-perfil AB_63
                Route::group(['prefix' => 'profile'], 
                    function() {
                        Route::group(['prefix' => 'doctor'], 
                            function() {
                                Route::get('buscar-perfil-medico', [ProfileDoctorController::class, 'buscar_perfil_medico']); // /admin/user/profile/doctor/buscar-perfil-medico AB-63  
                                Route::post('completar-perfil-medico', [ProfileDoctorController::class, 'completar_perfil_medico']); //  /admin/user/profile/doctor/completar_perfil_medico AB-63  
                                Route::get('listar-medicos', [ProfileDoctorController::class, 'listar_medicos']); //  /admin/user/profile/doctor/listar-medicos AB-66  
                                Route::post('actualizar-perfil-medico', [ProfileDoctorController::class, 'actualizar_perfil_medico']);  // admin/user/profile/doctor/actualizar-perfil-pedico  
                                Route::post('actualizar-firma-registrada', [ProfileDoctorController::class, 'actualizar_firma_registrada']);  // admin/user/profile/doctor/actualizar-firma-registrada  1.1.547-20250801  
                                Route::post('asignar-matricula-medico', [ProfileDoctorController::class, 'asignar_matricula_medico']);  // admin/user/profile/doctor/asignar-matricula-medico  1.1.688-20260205 
                            }
                        );
                        Route::group([ 'prefix' => 'secretary'], 
                            function() {
                                Route::get('search', [ProfileSecretaryController::class, 'search']); // /admin/user/profile/secretary/search AB-64   
                                Route::post('complete', [ProfileSecretaryController::class, 'complete']); //  /admin/user/profile/secretary/complete AB-64    
                                Route::post('relate', [ProfileSecretaryController::class, 'relate']); //  /admin/user/profile/secretary/relate AB-64  
                                Route::get('list', [ProfileSecretaryController::class, 'list']); //  /admin/user/profile/secretary/list AB-67  
                            }
                        );
                    }
                );
            }
        );
        Route::group(['prefix' => 'role'], 
            function(){
                Route::get('search', [RoleController::class, 'index']);
                Route::post('store', [RoleController::class, 'store']);
                Route::post('update', [RoleController::class, 'update']);
                Route::post('destroy', [RoleController::class, 'destroy']);
                Route::get('consultar-asignacion', [RoleController::class, 'consultar_asignacion']); // admin/role/consultar-asignacion 1.1.670-20260105
                Route::post('exportar-roles', [ExportarRolesController::class, 'exportar_roles']); // admin/role/exportar-roles  1.1.657-20251219
                Route::post('permission/assign', [RoleController::class, 'asignar_permiso']);
                Route::post('permission/revoke', [RoleController::class, 'quitar_permiso']);
                Route::post('permission/synchronize', [RoleController::class, 'sincronizar_permisos']);
            }
        );
        Route::group(['prefix' => 'permission'], 
            function(){
                Route::get('search', [PermissionController::class, 'index']);
                Route::post('store', [PermissionController::class, 'store']);
                Route::post('update', [PermissionController::class, 'update']);
                Route::post('destroy', [PermissionController::class, 'destroy']);
                Route::post('exportar-permisos', [ExportarPermisosController::class, 'exportar_permisos']); // admin/permission/exportar-permisos  1.1.657-20251219
                Route::get('consultar-asignacion', [PermissionController::class, 'consultar_asignacion']); // admin/permission/consultar-asignacion
            }
        );
        Route::group([ 'prefix' => 'alfabeta'], 
            function(){
                Route::get('actualizar-alfabeta', [AlfabetaController::class, 'actualizar_alfabeta']);    // /api/admin/alfabeta/actualizar-alfabeta  AB-94  
                Route::get('consultar-alfabeta', [AlfabetaController::class, 'consultar_alfabeta']);    // /api/admin/alfabeta/consultar-alfabeta AB-94  
                Route::get('listar-laboratorios', [AlfabetaController::class, 'listar_laboratorios']);    // /api/admin/alfabeta/listar-laboratorios 1.1.690-20260205
            }
        );
        Route::group(['prefix' => 'sistema'], 
            function(){
                Route::get('listar-usuarios-conectados', [SistemaController::class, 'listar_usuarios_conectados']); // admin/sistema/listar-usuarios-conectados  
                Route::post('desconectar-masivo', [SistemaController::class, 'desconectar_masivo']); // admin/sistema/desconectar-masivo
                Route::post('desconectar-usuario', [SistemaController::class, 'desconectar_usuario']); // admin/sistema/desconectar-usuario
                Route::post('forzar-logout-masivo', [SistemaController::class, 'forzar_logout_masivo']); // admin/sistema/forzar-logout-masivo
                Route::post('forzar-logout-usuario', [SistemaController::class, 'forzar_logout_usuario']); // admin/sistema/forzar-logout-usuario
                Route::post('forzar-reload-masivo', [SistemaController::class, 'forzar_reload_masivo']); // admin/sistema/forzar-reload-masivo
                Route::post('forzar-reload-usuario', [SistemaController::class, 'forzar_reload_usuario']); // admin/sistema/forzar-reload-usuario
                Route::post('informar-nueva-version-front', [SistemaController::class, 'informar_nueva_version_front']); // admin/sistema/informar-nueva-version-front
                Route::group(['prefix' => 'solicitud-soporte'], 
                    function() {
                        Route::get('listar-solicitudes-soporte', [SolicitudSoporteController::class, 'listar_solicitudes_soporte']); // admin/sistema/solicitud-soporte/listar-solicitudes-soporte
                        Route::post('actualizar-solicitud-soporte', [SolicitudSoporteController::class, 'actualizar_solicitud_soporte']); // admin/sistema/solicitud-soporte/actualizar-solicitud-soporte 1.1.698-20260218
                    }
                );
                Route::group(['prefix' => 'estadisticas'],
                    function(){
                        Route::get('conexiones-realizadas', [SistemaController::class, 'conexiones_realizadas']);  // admin/sistema/estadisticas/recetas-emitidas
                    }
                );
                Route::group(['prefix' => 'informes-sistema'], 
                    function() {
                        Route::get('medicamentos-recetados', [InformesSistemaController::class, 'generar_informe_medicamentos_recetados']); // admin/sistema/informes-sistema/medicamentos-recetados  1.1.698-20260218
                    }
                );
            }
        );
    }
);

// Rutas internas de new bpay
Route::group(['prefix' => 'int'], 
    function() {
        Route::post('desencriptar', [AuthController::class, 'desencriptar']); // int/desencriptar
        Route::post('probar-sp', [PruebasController::class, 'probar_sp']); // int/ejecutar-sp
        // auth
        Route::group([ 'prefix' => 'auth'], 
            function() {
                // Route::post('consultar-sisa', [AuthController::class, 'consultar_sisa']); // int/auth/consultar-sisa
                Route::post('login', [AuthController::class, 'login'])->name('auth.login'); // int/auth/login
                Route::post('register-doctor', [AuthController::class, 'register_doctor']);  // int/auth/register-doctor
                // las siguientes rutas las usa plataforma para resetear password
                Route::post('enviar-token-reseteo-password', [AuthController::class, 'enviar_token_reseteo_password']);  // int auth/enviar-token-reseteo-clave  lo usa el proyecto viejo también
                Route::post('resetear-password', [AuthController::class, 'resetear_password']); // int/auth/resetear-clave  lo usa el proyecto viejo también
                // hasta aquí
                Route::post('enviar-email-registro-doctor', [EmailsUsuariosController::class, 'enviar_email_registro_doctor']); // int/auth/enviar-email-registro-doctor  ** sólo para pruebas **
                Route::group([
                        'middleware' => 'auth:api'
                    ], function() {
                        Route::post('validar-password-super-admin', [AuthController::class, 'validar_password_super_admin']); // int/auth/validar-password-super-admin
                        Route::get('check-password', [AuthController::class, 'check_password']); // int/auth/check-password
                        Route::get('logout', [AuthController::class, 'logout']); // int/auth/logout
                        Route::get('user', [AuthController::class, 'user']); // int/auth/user
                        Route::post('password-change', [AuthController::class, 'password_change']); // int/auth/password-change
                        Route::post('update-user', [AuthController::class, 'update_user']); // int/auth/update-user
                    }
                );
            }
        );
        // consultas externas sin auth:api
        Route::group(['prefix' => 'consultas-externas'], 
            function(){
                Route::post('consultar-sisa', [ConsultasExternasController::class, 'consultar_sisa']); // int/consultas-externas/consultar-sisa 1.1.510-20250623
            }
        );
        // encuestas
        Route::group(['prefix' => 'encuestas'], 
            function() {
                Route::post('guardar-encuesta-atencion', [EncuestasController::class, 'guardar_encuesta_atencion']); // int/encuestas/guardar-encuesta-atencion  1.1.523-20250707
            }
        );
        // // entorno frontend
        // Route::group(['prefix' => 'entorno-frontend'],
        //     function(){
        //         Route::get('cargar-entorno', [EntornoFrontendController::class, 'cargar_entorno']); // int/entorno-frontend/cargar-entorno  1.1.742-20260406
        //     }
        // );
        // afiliaciones, archivos, auditorias, coberturas-especiales, configuraciones, consultorio, etc ...
        Route::group([
                'middleware' => 'auth:api'  
            ], function() {
                // afiliaciones
                Route::group(['prefix' => 'afiliaciones'], 
                    function() {
                        // afiliacion
                        Route::group(['prefix' => 'afiliacion'], 
                            function(){
                                Route::group(['prefix' => 'documentacion'],
                                    function() {
                                        Route::get('buscar-documentacion', [AfiliacionController::class, 'buscar_documentacion']); // int/afiliaciones/afiliacion/documentacion/buscar-documentacion   
                                        Route::post('agregar-documentacion', [AfiliacionController::class, 'agregar_documentacion']); // int/afiliaciones/afiliacion/documentacion/agregar-documentacion   
                                        Route::post('baja-documentacion', [AfiliacionController::class, 'baja_documentacion']); // int/afiliaciones/afiliacion/documentacion/baja-documentacion   
                                        Route::post('marcar-documentacion-recibida', [AfiliacionController::class, 'marcar_documentacion_recibida']); // int/afiliaciones/afiliacion/documentacion/marcar-documentacion-recibida   
                                    }
                                );
                                Route::get('buscar-recien-nacidos', [AfiliacionController::class, 'buscar_recien_nacidos']); // int/afiliaciones/afiliacion/buscar-recien-nacidos   
                                Route::get('buscar-registro-historico', [AfiliacionController::class, 'buscar_registro_historico']); // int/afiliaciones/afiliacion/buscar-registro-historico  // AB-112   
                                Route::get('generar-certificado-afiliacion', [ExportarAfiliadoController::class, 'generar_certificado_afiliacion'])->middleware(Cors::class); // int/afiliaciones/afiliacion/generar-certificado-afiliacion
                                Route::post('agregar-recien-nacido', [AfiliacionController::class, 'agregar_recien_nacido']); // int/afiliaciones/afiliacion/agregar-recien_nacido   
                                Route::group(['prefix' => 'credenciales'],
                                    function() {
                                        Route::get('buscar-credenciales', [CredencialController::class, 'buscar_credenciales']); // int/afiliaciones/afiliacion/credenciales/buscar-credenciales  // 1.1.542-20250728
                                    }
                                );
                            }

                        );
                        // afiliado
                        Route::group(['prefix' => 'afiliado'], 
                            function(){
                                Route::get('buscar-afiliado', [AfiliadoController::class, 'buscar_afiliado']); // int/afiliaciones/afiliado/buscar-afiliado
                                Route::get('buscar-planes-afiliado', [PlanController::class, 'buscar_planes_afiliado']); // int/afiliaciones/afiliado/buscar-planes-afiliado  // AB-115  
                                Route::get('exportar-datos', [ExportarAfiliadoController::class, 'exportar_datos_afiliado']); // int/afiliaciones/afiliado/exportar-datos
                                Route::get('historial-afiliado', [AfiliadoController::class, 'historial_afiliado']); // int/afiliaciones/afiliado/historial-afiliado  // AB-102   
                                Route::post('actualizar-afiliado', [AfiliadoController::class, 'actualizar_afiliado']); // int/afiliaciones/afiliado/actuallizar-afiliado  // AB-56  
                                Route::post('agregar-afiliado', [AfiliadoController::class, 'agregar_afiliado']); // int/afiliaciones/afiliado/agregar-afiliado  // AB-56  
                                Route::post('cambiar-numero', [AfiliadoController::class, 'cambiar_numero']);  // int/afiliaciones/afiliado/cambiar-numero  // AB-103   
                                Route::post('cambiar-password-afiliado-mobile', [AfiliadoController::class, 'cambiar_password_afiliado_mobile']);  // int/afiliaciones/afiliado/cambiar-password-afiliado-mobile  // 1.1.630-20251128   
                                Route::post('cambiar-plan-afiliado', [PlanController::class, 'cambiar_plan_afiliado']);  // int/afiliaciones/afiliado/cambiar-plan-afiliado  // AB-115     
                            }
                        );
                        // grupo familiar
                        Route::group(['prefix' => 'grupo-familiar'],
                            function(){
                                Route::post('baja-grupo-familiar', [GrupoFamiliarController::class, 'baja_grupo_familiar']); // int/afiliaciones/grupo-familiar/baja-grupo-familiar   
                                Route::get('buscar-grupo-familiar', [GrupoFamiliarController::class, 'buscar_grupo_familiar']); // int/afiliaciones/grupo-familiar/buscar-grupo-familiar   // AB-88 
                                Route::post('cambiar-grupo-familiar', [GrupoFamiliarController::class, 'cambiar_grupo_familiar']); // int/afiliaciones/grupo-familiar/cambiar-grupo-familiar  // AB-98    
                                Route::post('nuevo-titular', [GrupoFamiliarController::class, 'nuevo_titular']); // int/afiliaciones/grupo-familiar/nuevo-titular  // AB-91       
                            }
                        );
                        // cuenta corriente
                        Route::group(['prefix' => 'cuenta-corriente'],
                            function(){
                                Route::get('buscar-detalle-cuenta-corriente', [CuentaCorrienteController::class, 'buscar_detalle_cuenta_corriente']); // int/afiliaciones/cuenta-corriente/buscar-detalle-cuenta-corriente  // 1.1.402-20250321
                                Route::get('buscar-facturas', [CuentaCorrienteController::class, 'buscar_facturas']); // int/afiliaciones/cuenta-corriente/buscar-facturas  // 1.1.406-20250326
                                Route::get('consultar-cuenta-corriente', [CuentaCorrienteController::class, 'consultar_cuenta_corriente']); // int/afiliaciones/cuenta-corriente/consultar-cuenta-corriente  // 1.1..402-20250321
                                Route::get('imprimir-estado-cuenta', [CuentaCorrienteController::class, 'imprimir_estado_cuenta']); // int/afiliaciones/cuenta-corriente/imprimir-estado-cuenta  // 1.1.407-20250327
                            }
                        );
                        // historia clinica
                        Route::group(['prefix' => 'historia-clinica'], 
                            function(){
                                Route::post('agregar-historia-clinica', [HistoriaClinicaController::class, 'agregar_historia_clinica']); // int/afiliaciones/historia-clinica/agregar-historia-clinica
                                Route::get('buscar-historia-clinica', [HistoriaClinicaController::class, 'buscar_historia_clinica']); // int/afiliaciones/historia-clinica/buscar-historia-clinica
                                Route::get('exportar-historia-clinica', [ExportarHistoriaClinicaController::class, 'exportar_historia_clinica']); // int/afiliaciones/historia-clinica/exportar-historia-clinica
                            }
                        );
                        // persona
                        Route::group(['prefix' => 'persona'], 
                            function(){
                                Route::get('buscar-persona', [PersonaController::class, 'buscar_persona']); // int/afiliaciones/persona/buscar-persona;  // AB-56  
                                Route::get('listar-contactos', [PersonaContactoController::class, 'listar_contactos']); // int/afiliaciones/persona/listar-contactos;  // AB-110       
                                Route::get('listar-domicilios', [PersonaDomicilioController::class, 'listar_domicilios']); // int/afiliaciones/persona/listar-domicilios;  // AB-94    
                                Route::get('listar-persona-patologias', [ABMPatologiaController::class, 'listar_persona_patologias']); // int/afiliaciones/persona/listar-persona-patologias;  // AB-113
                                Route::post('actualizar-persona', [PersonaController::class, 'actualizar_persona']); // int/afiliaciones/persona/actualizar-persona; // AB-56  
                                Route::post('agregar-contacto', [PersonaContactoController::class, 'agregar_contacto']); // int/afiliaciones/persona/agregar-contacto;  // AB-110       
                                Route::post('agregar-domicilio', [PersonaDomicilioController::class, 'agregar_domicilio']); // int/afiliaciones/persona/agregar-domicilio;  // AB-93    
                                Route::post('agregar-persona', [PersonaController::class, 'agregar_persona']); // int/afiliaciones/persona/agregar-persona; // AB-56  
                                Route::post('cambiar-documento', [PersonaController::class, 'cambiar_documento']); // int/afiliaciones/persona/cambiar-documento;  // AB-101    
                                Route::post('eliminar-persona-patologia', [ABMPatologiaController::class, 'eliminar_persona_patologia']); // int/afiliaciones/persona/eliminar-persona-patologia  AB-118           
                                Route::post('exportar-persona-patologia', [ExportarPatologiaController::class, 'exportar_persona_patologia']); // int/afiliaciones/persona/exportar-persona-patologia  AB-119           
                                Route::post('guardar-persona-patologia', [ABMPatologiaController::class, 'guardar_persona_patologia']); // int/afiliaciones/persona/guardar-persona-patologia  AB-117           
                            }
                        );
                        // facturacion
                        Route::group(['prefix' => 'facturacion'],
                            function() {
                                Route::get('generar-ejemplo-factura', [FacturacionController::class, 'generar_ejemplo_factura']); // int/afiliaciones/facturacion/generar-ejemplo-factura  AB-124      
                                Route::get('generar-factura-pdf', [FacturacionController::class, 'generar_factura_pdf']); // int/afiliaciones/facturacion/generar-pdf-factura  1.1.404-20250321
                                Route::get('simular-facturacion', [FacturacionController::class, 'simular_facturacion']); // int/afiliaciones/facturacion/simular-facturacion  AB-123   
                            }
                        );
                    }
                );
                // archivos
                Route::group(['prefix' => 'archivos'], 
                    function() {
                        Route::get('ver-validacion', [FileController::class, 'ver_validacion']); // int/archivos/ver
                        Route::get('ver', [FileController::class, 'view']); // int/archivos/ver
                        Route::post('eliminar', [FileController::class, 'destroy']); // int/archivos/eliminar
                        Route::post('subir', [FileController::class, 'upload']); // int/archivos/subir
                        Route::group(['prefix' => 'avatar'], 
                            function() {
                                Route::get('buscar', [FileController::class, 'buscar_avatar']); // int/archivos/avatar/buscar  AB-68    
                                Route::post('quitar', [FileController::class, 'quitar_avatar']); // int/archivos/avatar/quitar  AB-68    
                                Route::post('subir', [FileController::class, 'subir_avatar']); // int/archivos/avatar/subir  AB-68    
                            }
                        );
                        Route::group(['prefix' => 'firma-medicos'], 
                            function() {
                                Route::get('buscar-firma-medico', [FileController::class, 'buscar_firma_medico']); // int/archivos/firma-medicos/buscar-firma-medico 
                                Route::post('quitar-firma-medico', [FileController::class, 'quitar_firma_medico']); // int/archivos/firma-medicos/quitar-firma-medico 
                                Route::post('subir-firma-medico', [FileController::class, 'subir_firma_medico']); // int/archivos/firma-medicos/subir-firma-medico 
                                Route::post('descargar-firma-medico', [FileController::class, 'descargar_firma_medico']); // int/archivos/firma-medicos/descargar-firma-medico 1.1.730-20260326 
                            }
                        );
                        Route::group(['prefix' => 'manuales'],
                            function() {
                                Route::get('buscar-manual', [FileController::class, 'buscar_manual']); // int/archivos/manuales/buscar-manual  1.1.415-20250407
                                Route::get('listar-manuales', [FileController::class, 'listar_manuales']); // int/archivos/manuales/listar-manuales  1.1.422-20250410
                                Route::post('quitar-manual', [FileController::class, 'quitar_manual']); // int/archivos/manuales/quitar-manual   1.1.418-20250407
                                Route::post('subir-manual', [FileController::class, 'subir_manual']); // int/archivos/manuales/subir-manual   1.1.417-20250407
                            }
                        );
                    }
                );
                // auditorias
                Route::group(['prefix' => 'auditorias'],
                    function() {
                        Route::get('buscar-auditoria-terreno', [AuditoriaTerrenoController::class, 'buscar_auditoria_terreno']); // int/auditorias/buscar-auditoria-terreno   
                        Route::post('actualizar-auditoria-terreno', [AuditoriaTerrenoController::class, 'actualizar_auditoria_terreno']); // int/auditorias/actualizar-auditoria-terreno
                        Route::post('auditar-validacion', [AuditoriaMedicaController::class, 'auditar_validacion']); // int/auditorias/auditar-validacion
                        Route::post('autorizar-insumos', [AuditoriaMedicaController::class, 'autorizar_insumos']); // int/auditorias/autorizar-insumos // 1.1.470-20250526
                        Route::post('solicitar-auditoria-terreno', [AuditoriaTerrenoController::class, 'solicitar_auditoria_terreno']); // int/auditorias/solicitar-auditoria-terreno   
                    }
                );
                // coberturas_especiales
                Route::group(['prefix' => 'coberturas-especiales'], 
                    function() {
                        Route::get('afiliado-consultar', [CoberturaEspecialController::class, 'consultar_coberturas_especiales_afiliado']); // int/coberturas-especiales/afiliado-consultar
                        Route::get('afiliado-exportar', [ExportarCoberturaEspecialController::class, 'exportar_coberturas_especiales_afiliado']);  // int/coberturas-especiales/afiliado-exportar
                        Route::post('actualizar-cobertura-especial-afiliado', [CoberturaEspecialController::class, 'actualizar_cobertura_especial_afiliado']); // int/coberturas-especiales/actualizar-cobertura-especial-afiliado  1.1.514-20250627
                        Route::post('agregar-cobertura-especial-afiliado', [CoberturaEspecialController::class, 'agregar_cobertura_especial_afiliado']); // int/coberturas-especiales/agregar-cobertura-especial-afiliado  1.1.513-20250627
                    }
                );
                // configuraciones
                Route::group(['prefix' => 'configuraciones'],
                    function(){
                        // carencias
                        Route::group(['prefix' => 'carencias'],
                            function() {
                                Route::get('buscar-carencias', [CarenciaController::class, 'buscar_carencias']); // int/configuraciones/carencias/buscar-carencias  1.1.503-20250619 
                                Route::post('actualizar-carencia', [CarenciaController::class, 'actualizar_carencia']); // int/configuraciones/carencias/actualizar-carencia  1.1.505-20250619 
                                Route::post('agregar-carencia', [CarenciaController::class, 'agregar_carencia']); // int/configuraciones/carencias/agregar-carencia  1.1.504-20250619 
                            }
                        );
                        // menu prestacional
                        Route::group(['prefix' => 'menu-prestacional'], 
                            function() {
                                Route::get('buscar-tipo-prestacion', [MenuPrestacionalController::class, 'buscar_tipo_prestacion']); // int/configuraciones/menu-prestacional/buscar-tipo-prestacion  1.1.506-20250623
                                Route::get('listar-menu-prestacional', [MenuPrestacionalController::class, 'listar_menu_prestacional']); // int/configuraciones/menu-prestacional/listar-menu-prestacional  1.1.506-20250623
                                Route::post('actualizar-menu-prestacional', [MenuPrestacionalController::class, 'actualizar_menu_prestacional']); // int/configuraciones/menu-prestacional/actualizar-menu-prestacional  1.1.509-20250623
                                Route::post('agregar-menu-prestacional', [MenuPrestacionalController::class, 'agregar_menu_prestacional']); // int/configuraciones/menu-prestacional/agregar-menu-prestacional  1.1.507-20250623
                                Route::post('agregar-tipo-prestacion-menu', [MenuPrestacionalController::class, 'agregar_tipo_prestacion_menu']); // int/configuraciones/menu-prestacional/agregar-tipo-prestacion-menu  1.1.508-20250623
                                Route::post('quitar-tipo-prestacion-menu', [MenuPrestacionalController::class, 'quitar_tipo_prestacion_menu']); // int/configuraciones/menu-prestacional/quitar-tipo-prestacion-menu  1.1.508-20250623
                            }
                        );
                        Route::group(['prefix' => 'estados-grupos'],
                            function() {
                                Route::get('buscar-estados-grupos', [EstadoGrupoController::class, 'buscar_estados_grupos']); // int/configuraciones/estados-grupos/buscar-estados-grupos  1.1.526-20250711
                                Route::post('actualizar-estado-grupo', [EstadoGrupoController::class, 'actualizar_estado_grupo']); // int/configuraciones/estados-grupos/actualizar-estado-grupo  1.1.526-20250711
                                Route::post('agregar-estado-grupo', [EstadoGrupoController::class, 'agregar_estado_grupo']); // int/configuraciones/estados-grupos/agregar-estado-grupo  1.1.526-20250711
                            }
                        );
                        Route::group(['prefix' => 'entorno-frontend'],
                            function(){
                                Route::get('cargar-entorno', [EntornoFrontendController::class, 'cargar_entorno']); // int/configuraciones/entorno-frontend/cargar-entorno  1.1.742-20260406
                                Route::post('actualizar-entorno', [EntornoFrontendController::class, 'actualizar_entorno']); // int/configuraciones/entorno-frontend/actualizar-entorno  1.1.742-20260406
                            }
                        );
                    }
                );
                // consultorio
                Route::group(['prefix' => 'consultorio'], 
                    function(){
                        Route::group(['prefix' => 'turnos'], 
                            function(){
                                Route::get('buscar-turnos', [TurnosController::class, 'buscar_turnos']); // int/consultorio/turnos/buscar  AB-70
                                Route::post('eliminar-turno', [TurnosController::class, 'eliminar_turno']); // int/consultorio/turnos/eliminar_turno  AB-71  
                                Route::post('exportar-listado-turnos', [ExportarTurnosController::class, 'exportar_listado_turnos']);  // int/consultorio/turnos/exportar-listado-turnos AB-79    
                                Route::post('sincronizar-turno', [TurnosController::class, 'sincronizar_turno']); // int/consultorio/turnos/sincronizar_turno  AB-71  
                            }
                        );
                    }
                );
                // consultas externas con auth:api
                Route::group(['prefix' => 'consultas-externas'], 
                    function(){
                        Route::get('consultar-vademecum', [ConsultasExternasController::class, 'consultar_vademecum']); // int/consultas-externas/obtener-precio-medicamento  1.1.691-20260209
                        Route::get('consultar-padron-externo', [ConsultasExternasController::class, 'consultar_padron_externo']); // int/consultas-externas/consultar-padron-externo 1.1.728-20260325
                        Route::get('buscar-afiliado-padron-externo', [ConsultasExternasController::class, 'buscar_afiliado_padron_externo']); // int/consultas-externas/buscar_afiliado-padron-externo 1.1.732-20260327

                        // antiguas consultas a las apis de Silvio Quadri, que se mantienen por compatibilidad pero ya no se usan en el proyecto nuevo, salvo la de afiliado que la sigue usando plataforma
                        Route::get('afiliado', [ConsultasExternasController::class, 'afiliado_afilmed']); // int/consultas-externas/afiliado 1.1.510-20250623
                        Route::get('boletin-protectivo', [ConsultasExternasController::class, 'boletin_protectivo']); // int/consultas-externas/boletin-protectivo         
                        Route::get('cobranzas-afilmed', [ConsultasExternasController::class, 'cobranzas_afilmed']); // int/consultas-externas/cobranzas-afilmed
                        Route::get('cuenta-corriente-afilmed', [ConsultasExternasController::class, 'cuenta_corriente_afilmed']); // int/consultas-externas/cuenta-corriente-afilmed
                    }
                );
                // diagnosticos
                Route::group(['prefix' => 'diagnosticos'], 
                    function() {
                        Route::get('buscar', [DiagnosticoController::class, 'buscar_diagnosticos']); // int/diagnosticos/buscar  AB-76  
                    }
                );
                // enviar email
                Route::group(['prefix' => 'enviar-email'], 
                    function() {
                        Route::get('generar-certificado-emails-enviados', [EmailController::class, 'generar_certificado_emails_enviados']); // int/enviar-email/generar-certifiado-emails-enviados 
                        Route::get('listar-emails-enviados', [EmailController::class, 'listar_emails_enviados']); // int/enviar-email/listar-emails-enviados
                        Route::post('enviar-certificado-emails-enviados', [EmailController::class, 'enviar_email_certificado_emails_enviados']); // int/enviar-email/certificado-emails-enviados 
                        Route::group(['prefix' => 'afiliaciones'], 
                            function() {
                                Route::post('enviar-certificado-afiliacion', [EmailsAfiliacionesController::class, 'enviar_email_certificado_afiliacion']); // int/enviar-email/afiliaciones/certificado-afiliacion
                                Route::post('enviar-coberturas-especiales-afiliado', [EmailsAfiliacionesController::class, 'enviar_email_coberturas_especiales_afiliado']); // int/enviar-email/afiliaciones/coberturas-especiales-afiliado
                                Route::post('enviar-historia-clinica-afiliado', [EmailsAfiliacionesController::class, 'enviar_email_historia_clinica_afiliado']); // int/enviar-email/afiliaciones/historia-clinica-afiliado
                                Route::post('enviar-informacion-afiliados', [EmailsAfiliacionesController::class, 'enviar_email_informacion_afiliados']); // int/enviar-email/afiliaciones/informes-afiliado
                                Route::post('enviar-pedido-documentacion-afiliado', [EmailsAfiliacionesController::class, 'enviar_email_pedido_documentacion_afiliado']); // int/enviar-email/afiliaciones/enviar-pedido-documentacion-afiliado  1.1.397-20250317
                                Route::post('enviar-situacion-terapeutica-afiliado', [EmailsAfiliacionesController::class, 'enviar_email_situacion_terapeutica']); // int/enviar-email/afiliaciones/situacion-terapeutica-afiliado // AB-120   
                                // Route::post('enviar-facturas', [EmailsAfiliacionesController::class, 'enviar_email_facturas']);
                                // Route::post('enviar-cuenta-corriente', [EmailsAfiliacionesController::class, 'enviar_email_cuenta_corriente']);
                                // Route::post('enviar-recibos', [EmailsAfiliacionesController::class, 'enviar_email_recibos']);
                            }
                        );
                        Route::group(['prefix' => 'consultorio'], 
                            function(){
                                Route::post('enviar-listado-turnos-medicos', [EmailsConsultorioController::class, 'enviar_email_listado_turnos_medicos']); // int/enviar-email/consultorio/listado-turnos-medicos  AB-79    
                            }
                        );
                        Route::group(['prefix' => 'encuestas'], 
                            function() {
                                Route::post('enviar-encuesta-atencion', [EmailsEncuestasController::class, 'enviar_email_encuesta_atencion']); // int/enviar-email/encuestas/enviar-encuesta-atencion   1.1.521-20250704
                            }
                        );
                        Route::group(['prefix' => 'expedientes'], 
                            function() {
                                // Route::post('enviar-caratula-expediente', [EmailsExpedientesController::class, 'enviar_email_caratula_expediente']);
                                // Route::post('enviar-pedido-presupuesto', [EmailsExpedientesController::class, 'enviar_email_pedido_presupuesto_expediente']);
                            }
                        );
                        Route::group(['prefix' => 'formularios'], 
                            function() {
                                Route::post('enviar-formulario-cronicos', [EmailsFormulariosController::class, 'enviar_email_formularios_cronicos']); // int/enviar-email/formularios/enviar-formularios-cronicos   11.1.722-20260316
                            }
                        );
                        Route::group(['prefix' => 'recetas'], 
                            function(){
                                Route::post('enviar-certificado-generado', [EmailsRecetasController::class, 'enviar_email_certificado_generado']); // /int/enviar-email/recetas/enviar-certificado-generado 1.1.575-20250910
                                Route::post('enviar-prescripcion-generada', [EmailsRecetasController::class, 'enviar_email_prescripcion_generada']); // /int/enviar-email/recetas/enviar-prescripcion-generada 1.1.568-20250903
                                Route::post('enviar-receta-generada', [EmailsRecetasController::class, 'enviar_email_receta_generada']); // /int/enviar-email/recetas/enviar-receta-generada
                            }
                        );
                        Route::group(['prefix' => 'soporte'], 
                            function() {
                                Route::post('enviar-solicitud-soporte', [EmailsSolicitudSoporteController::class, 'enviar_solicitud_soporte']); // int/enviar-email/soporte/enviar-solicitud-soporte   1.1.420-20250409
                            }
                        );
                        Route::group(['prefix' => 'validaciones'], 
                            function() {
                                Route::post('enviar-lista-prestaciones', [EmailsValidacionesController::class, 'enviar_email_lista_prestaciones']);
                                Route::post('enviar-solicitud-informacion-preautorizacion', [EmailsValidacionesController::class, 'enviar_email_solicitud_informacion_preautorizacion']); // int/enviar-email/validaciones/enviar-solicitud-informacion-preautorizacion  1.1.627-20251127 
                                Route::post('enviar-validacion', [EmailsValidacionesController::class, 'enviar_email_validacion']); // int/enviar-email/validaciones/validacion AB-55 
                            }
                        );
                    }
                );
                // general
                Route::group(['prefix' => 'general'], 
                    function(){
                        // baja
                        Route::post('realizar-baja', [BajaController::class, 'realizar_baja']); // int/general/realizar-baja  AB-99   
                        // localidad
                        Route::group(['prefix' => 'localidad'], 
                            function(){
                                Route::get('buscar-localidad', [LocalidadController::class, 'buscar_localidad']);  // int/general/localidad/buscar-localidad  AB-97
                                // Route::post('agregar-localidad', [LocalidadController::class, 'agregar_localidad']); 
                                // Route::post('actualizar-localidad', [LocalidadController::class, 'actualizar_localidad']); 
                            }
                        );
                        // informes
                        Route::group(['prefix' => 'informes'],
                            function(){
                                Route::post('buscar-informe', [InformesController::class, 'buscar_informe']); // int/general/informes/buscar-informe AB-111   
                                Route::post('exportar-informe', [ExportarInformeController::class, 'exportar_informe']); // int/general/informes/exportar-informe AB-111   
                                Route::get('listar-informes', [InformesController::class, 'listar_informes']); // int/general/informes/listar-informes AB-111   
                                Route::get('listar-informes-usuario', [InformesController::class, 'listar_informes_usuario']); // int/general/informes/informes-usuario 1.1.672-20260106
                                Route::get('listar-usuarios-informes', [InformesController::class, 'listar_usuarios_informes']); // int/general/informes/usuarios-informes 1.1.672-20260106     
                                Route::post('habilitar-informe', [InformesController::class, 'habilitar_informe']); // int/general/informes/habilitar-informe 1.1.672-20260106     
                                Route::post('deshabilitar-informe', [InformesController::class, 'deshabilitar_informe']); // int/general/informes/deshabilitar-informe 1.1.672-20260106     
                            }
                        );
                        Route::group(['prefix' => 'tablero'],
                            function() {
                                Route::get('buscar-datos', [TableroController::class, 'buscar_datos']); // int/general/tablero/buscar-datos 1.1.733-20260330
                            }
                        );
                    }
                );
                // internaciones
                Route::group(['prefix' => 'internaciones'],
                    function() {
                        Route::get('abrir-internacion-vencida', [InternacionController::class, 'abrir_internacion_vencida']); // int/internaciones/abrir-internacion-vencida
                        Route::get('buscar-asociables-internacion', [PrestacionesController::class, 'buscar_asociables_internacion']); // int/internaciones/buscar-asociables-internacion   
                        Route::get('buscar-internacion-abierta', [InternacionController::class, 'buscar_internacion_abierta']); // int/internaciones/buscar-internacion-abierta       
                        Route::get('buscar-internacion', [InternacionController::class, 'buscar_internacion']); // int/internaciones/buscar-internacion
                        Route::get('consultar-acciones-internacion', [InternacionController::class, 'consultar_acciones_internacion']); // int/internaciones/consultar-acciones-internacion
                        Route::get('consultar-prestaciones-internacion', [PrestacionesController::class , 'consultar_prestaciones_internacion']);  // int/internaciones/consultar-prestaciones-internacion  
                        Route::post('asociar-validacion', [InternacionController::class, 'asociar_validacion']); // int/internaciones/asociar-validacion  
                        Route::post('emitir-prestaciones-internacion', [InternacionController::class, 'emitir_prestaciones_internacion']); // int/internaciones/emitir-prestaciones-internacion
                        Route::post('generar-egreso-internacion', [InternacionController::class, 'generar_egreso_internacion']); // int/internaciones/generar-egreso-internacion  
                        Route::post('generar-prorroga-automatica', [InternacionController::class, 'generar_prorroga_automatica']); // int/internaciones/generar-prorroga-automatica   
                    }
                );
                // listados
                Route::group(['prefix' => 'listados'], 
                    function(){
                        Route::get('get-all', [ListadosController::class, 'get_all']); // int/listados/get-all  AB-77  
                        Route::get('actividades', [ListadosController::class, 'listar_actividades']); // int/listados/actividades AB-57  
                        Route::get('conceptos', [ListadosController::class, 'listar_conceptos']); // int/listados/conceptos    
                        Route::get('convenios', [ABMConvenioController::class,  'buscar_convenio']); // int/listados/convenios AB-69    se deja por retrocompatibilidad
                        Route::get('criterios-agrupacion', [ListadosController::class, 'listar_criterios_agrupacion']); // int/listados/criterios-agrupacion  // 1.1.457-20250509     
                        Route::get('estados-afiliados', [ListadosController::class, 'listar_estados_afiliados']); // int/listados/estados-afiliados AB-109     
                        Route::get('estados-civiles', [ListadosController::class, 'listar_estados_civiles']); // int/listados/estados-civiles AB-57  
                        Route::get('estados-preautorizaciones', [ListadosController::class, 'listar_estados_preautorizaciones']); // int/listados/estados-preautorizaciones 1.1.640-20251205   
                        Route::get('estados-validaciones', [ListadosController::class, 'listar_estados_validaciones']); // int/listados/estados-validaciones AB-52  
                        Route::get('frecuencias-prestaciones', [ListadosController::class, 'listar_frecuencias_prestaciones']); // int/listados/frecuencias-prestaciones    
                        Route::get('motivos-movimientos-caja', [ListadosController::class, 'listar_motivos_movimientos_caja']); // int/listados/motivos-movimiento-caja 1.1.421-20250605
                        Route::get('nacionalidades', [ListadosController::class, 'listar_nacionalidades']); // int/listados/nacionalidades AB-57  
                        Route::get('origenes', [ABMOrigenAfiliadoController::class, 'listar_origen_afiliado']); // int/listados/origenes AB-57   se deja por retrocompatibilidad
                        Route::get('parentescos', [ABMParentescoController::class, 'listar_parentescos']); // int/listados/listar_parentescos 1.1.551-20250814
                        Route::get('planes', [ABMPlanController::class, 'buscar_plan']); // int/listados/planes AB-57  se deja por retrocompatibilidad
                        Route::get('promotores', [ABMPromotorController::class, 'listar_promotores']); // int/listados/promotores AB-57  
                        Route::get('provincias', [ListadosController::class, 'listar_provincias']); // int/listados/provincias AB-96     
                        Route::get('rango-edades', [ListadosController::class, 'listar_rango_edades']); // int/listados/rango-edades AB-60    
                        Route::get('sucursales', [ListadosController::class, 'listar_sucursales']); // int/listados/sucursales AB-52  
                        Route::get('tipos-afip', [ListadosController::class, 'listar_tipos_afip']); // int/listados/tipos-afip AB-87    
                        Route::get('tipos-baja', [ListadosController::class, 'listar_tipos_baja']); // int/listados/tipos-baja AB-100    
                        Route::get('tipos-contactos', [ListadosController::class, 'listar_tipos_contactos']); // int/listados/tipos-contactos AB-108     
                        Route::get('tipos-documentos', [ListadosController::class, 'listar_tipos_documentos']); // int/listados/tipos-documentos AB-57  
                        Route::get('tipos-domicilios', [ListadosController::class, 'listar_tipos_domicilios']); // int/listados/tipos-domicilios AB-92    
                        Route::get('tipos-facturacion', [ListadosController::class, 'listar_tipos_facturacion']); // int/listados/tipos-facturacion AB-87    
                        Route::get('tipos-internaciones', [ListadosController::class, 'listar_tipos_internaciones']); // int/listados/tipos-internaciones   
                        Route::get('tipos-prestacion', [ListadosController::class, 'listar_tipos_prestacion']); // int/listados/tipos-prestacion 
                        Route::get('usuarios-sqlserver', [ListadosController::class, 'listar_usuarios_sqlserver']); // int/listados/usuarios-sqlserver AB-52  
                        Route::get('zonas-domicilios', [ListadosController::class, 'listar_zonas_domicilios']); // int/listados/zonas-domicilios AB-57  
                        Route::get('zonas-prestacionales', [ListadosController::class, 'listar_zonas_prestacionales']); // int/listados/zonas-prestacionales AB-82    
                        // actividades
                        Route::group(['prefix' => 'actividad'],
                            function() {
                                Route::get('listar-actividades', [ABMActividadController::class,  'listar_actividades']); // int/listados/actividad/listar-actividades           // 1.1.678-20260114    
                                Route::post('actualizar-actividad', [ABMActividadController::class,  'actualizar_actividad']); // int/listados/actividad/actualizar-actividad    // 1.1.678-20260114  
                                Route::post('agregar-actividad', [ABMActividadController::class,  'agregar_actividad']); // int/listados/actividad/agregar-actividad             // 1.1.678-20260114
                            }
                        );
                        // convenios
                        Route::group(['prefix' => 'convenio'],
                            function() {
                                Route::get('buscar-convenio', [ABMConvenioController::class,  'buscar_convenio']); // int/listados/convenio/buscar-convenio     
                                Route::post('actualizar-convenio', [ABMConvenioController::class,  'actualizar_convenio']); // int/listados/convenio/actualizar-convenio     
                                Route::post('agregar-convenio', [ABMConvenioController::class,  'agregar_convenio']); // int/listados/convenio/agregar-convenio     
                                Route::post('sincronizar-convenio', [ABMConvenioController::class, 'sincronizar_convenio']); // int/listados/convenios/sincronizar-convenio   // 1.1.432-20250424
                            }
                        );
                        // documentacion afiliado
                        Route::group(['prefix' => 'documentacion-afiliado'],
                            function(){
                                Route::get('listar-documentacion', [ABMDocumentacionAfiliadoController::class, 'listar_documentacion']); // int/listados/documentacion-afiliado/listar-documentacion    
                                Route::post('actualizar-documentacion', [ABMDocumentacionAfiliadoController::class, 'actualizar_documentacion']); // int/listados/documentacion-afiliado/actualizar-documentacion    
                                Route::post('agregar-documentacion', [ABMDocumentacionAfiliadoController::class, 'agregar_documentacion']); // int/listados/documentacion-afiliado/agregar-documentacion    
                            }
                        );
                        // estados civiles
                        Route::group(['prefix' => 'estado-civil'],
                            function() {
                                Route::get('listar-estados-civiles', [ABMEstadoCivilController::class,  'listar_estados_civiles']); // int/listados/estado-civil/listar-estados-civiles           // 1.1.679-20260114    
                                Route::post('actualizar-estado-civil', [ABMEstadoCivilController::class,  'actualizar_estado_civil']); // int/listados/estado-civil/actualizar-estado-civil    // 1.1.679-20260114  
                                Route::post('agregar-estado-civil', [ABMEstadoCivilController::class,  'agregar_estado_civil']); // int/listados/estado-civil/agregar-estado-civil             // 1.1.679-20260114
                            }
                        );
                        Route::group(['prefix' => 'gravamen'],
                            function() {
                                Route::get('listar-gravamenes', [ABMGravamenController::class, 'listar_gravamenes']); // int/listados/gravamen/listar-gravamenes  1.1.528-20250715    
                                Route::post('actualizar-gravamen', [ABMGravamenController::class, 'actualizar_gravamen']); // int/listados/gravamen/actualizar-gravamen    
                                Route::post('agregar-gravamen', [ABMGravamenController::class, 'agregar_gravamen']); // int/listados/gravamen/agregar-gravamen     
                            }
                        );
                        // origen afiliados
                        Route::group(['prefix' => 'origen-afiliado'],
                            function() {
                                Route::get('listar-origen-afiliado', [ABMOrigenAfiliadoController::class, 'listar_origen_afiliado']); // int/listados/origen-afiliado/listar-origen-afiliado
                                Route::post('actualizar-origen-afiliado', [ABMOrigenAfiliadoController::class, 'actualizar_origen_afiliado']); // int/listados/origen-afiliado/actualizar-origen-afiliado
                                Route::post('agregar-origen-afiliado', [ABMOrigenAfiliadoController::class, 'agregar_origen_afiliado']); // int/listados/origen-afiliado/agregar-origen-afiliado
                            }
                        );
                        // origen matricula
                        Route::group(['prefix' => 'origen-matricula'], 
                            function(){
                                Route::get('buscar-origen-matricula', [ABMOrigenMatriculaController::class, 'buscar_origen_matricula']); // int/listados/origen-matricula/buscar-origen-matricula AB-107    
                                Route::post('actualizar-origen-matricula', [ABMOrigenMatriculaController::class, 'actualizar_origen_matricula']); // int/listados/origen-matricula/actualizar-origen-matricula AB-107    
                                Route::post('agregar-origen-matricula', [ABMOrigenMatriculaController::class, 'agregar_origen_matricula']); // int/listados/origen-matricula/agregar-origen-matricula AB-107     
                                Route::post('eliminar-origen-matricula', [ABMOrigenMatriculaController::class, 'eliminar_origen_matricula']); // int/listados/origen-matricula/eliminar-origen-matricula AB-107    
                            }
                        ); 
                        // obra social
                        Route::group(['prefix' => 'obra-social'], 
                            function() {
                                Route::get('buscar-obra-social', [ABMObraSocialController::class, 'buscar_obra_social']); // int/listados/obra-social/buscar-obra-social  AB-114   
                            }
                        ); 
                        // patologias
                        Route::group(['prefix' => 'patologia'], 
                            function(){
                                Route::get('buscar-prestaciones-patologia', [ABMPatologiaController::class, 'buscar_prestaciones_patologia']); // int/listados/patologia/buscar-prestaciones-patologia       
                                Route::get('listar-diagnosticos-patologia', [ABMPatologiaController::class, 'listar_diagnosticos_patologia']); // int/listados/patologia/listar-diagnosticos-patologia  AB-116     
                                Route::get('listar-documentacion-patologias', [ABMPatologiaController::class, 'listar_documentacion_patologias']); // int/listados/patologia/listar-documentacion-patologias  AB-116     
                                Route::get('listar-patologias', [ABMPatologiaController::class, 'listar_patologias']); // int/listados/patologia/listar-patologias  AB-116     
                                Route::get('listar-prestaciones-patologia', [ABMPatologiaController::class, 'listar_prestaciones_patologia']); // int/listados/patologia/listar-prestaciones-patologia       
                                Route::post('actualizar-patologia', [ABMPatologiaController::class, 'actualizar_patologia']); // int/listados/patologia/actualizar-patologia       
                                Route::post('agregar-diagnostico-patologia', [ABMPatologiaController::class, 'agregar_diagnostico_patologia']); // int/listados/patologia/agregar-diagnostico-patologia       
                                Route::post('agregar-documentacion-patologia', [ABMPatologiaController::class, 'agregar_documentacion_patologia']); // int/listados/patologias/agregar-documentacion-patologia   
                                Route::post('agregar-patologia', [ABMPatologiaController::class, 'agregar_patologia']); // int/listados/patologia/agregar-patologia       
                                Route::post('agregar-prestaciones-patologia', [ABMPatologiaController::class, 'agregar_prestaciones_patologia']); // int/listados/patologia/agregar-prestaciones-patologia       
                                Route::post('quitar-documentacion-patologia', [ABMPatologiaController::class, 'quitar_documentacion_patologia']); // int/listados/patologias/quitar-documentacion-patologia   
                            }
                        );
                        // parentescos
                        Route::group(['prefix' => 'parentescos'], 
                            function() {
                                Route::get('listar-parentescos', [ABMParentescoController::class, 'listar_parentescos']); // int/listados/parentescos/listar-parentescos  1.1.551-20250814   
                                Route::post('actualizar-parentesco', [ABMParentescoController::class, 'actualizar_parentesco']); // int/listados/parentescos/actualizar-parentesco  1.1.551-20250814   
                                Route::post('agregar-parentesco', [ABMParentescoController::class, 'agregar_parentesco']); // int/listados/parentescos/agregar-parentesco  1.1.551-20250814  
                            }
                        );
                        // planes
                        Route::group(['prefix' => 'plan'], 
                            function() {
                                Route::get('buscar-conceptos-plan', [ABMPlanController::class, 'buscar_conceptos_plan']); // int/listados/plan/buscar-conceptos-plan  
                                Route::get('buscar-plan', [ABMPlanController::class, 'buscar_plan']); // int/listados/plan/buscar-plan  
                                Route::post('actualizar-plan', [ABMPlanController::class, 'actualizar_plan']); // int/listados/plan/actualizar-plan
                                Route::post('agregar-plan', [ABMPlanController::class, 'agregar_plan']); // int/listados/plan/agregar-plan
                            }
                        );
                        // promotores
                        Route::group(['prefix' => 'promotores'], 
                            function() {
                                Route::get('buscar-promotor', [ABMPromotorController::class, 'buscar_promotor']); // int/listados/promotores/buscar-promotor  
                                Route::post('actualizar-promotor', [ABMPromotorController::class, 'actualizar_promotor']); // int/listados/promotores/actualizar-promotor
                                Route::post('agregar-promotor', [ABMPromotorController::class, 'agregar_promotor']); // int/listados/promotores/agregar-promotor
                            }
                        );
                        // rangos edad
                        Route::group(['prefix' => 'rangos-edad'], 
                            function() {
                                Route::get('buscar-rango-edad', [ABMRangoEdadController::class, 'buscar_rango_edad']); // int/listados/rango-edad/buscar-rango-edad  1.1.538-20250724   
                                Route::get('listar-rangos-edad', [ABMRangoEdadController::class, 'listar_rangos_edad']); // int/listados/rango-edad/listar-rangos-edad  1.1.538-20250724   
                                Route::post('agregar-rango-edad', [ABMRangoEdadController::class, 'agregar_rango_edad']); // int/listados/rango-edad/agregar-rango-edad  1.1.538-20250724  
                            }
                        );
                        
                        // tipos de baja
                        Route::group(['prefix' => 'tipos-baja'], 
                            function() {
                                Route::get('buscar-tipos-baja', [ABMTipoBajaController::class, 'buscar_tipos_baja']); // int/listados/tipos-baja/buscar-tipos-baja  1.1.535-20250718
                                Route::post('actualizar-tipo-baja', [ABMTipoBajaController::class, 'actualizar_tipo_baja']); // int/listados/tipos-baja/actualizar-tipo-baja  1.1.535-20250718
                                Route::post('agregar-tipo-baja', [ABMTipoBajaController::class, 'agregar_tipo_baja']); // int/listados/tipos-baja/agregar-tipo-baja  1.1.535-20250718
                            }
                        );
                        // tipos de conceptos
                        Route::group(['prefix' => 'tipo-concepto'], 
                            function() {
                                Route::get('buscar-gravamenes', [ABMTipoConceptoController::class, 'buscar_gravamenes']); // int/listados/tipo-concepto/buscar-gravamenes  1.1.530-20250716
                                Route::get('listar-tipos-conceptos', [ABMTipoConceptoController::class, 'listar_tipos_conceptos']); // int/listados/tipo-concepto/listar-tipos-conceptos  1.1.527-20250714 
                                Route::post('actualizar-tipo-concepto', [ABMTipoConceptoController::class, 'actualizar_tipo_concepto']); // int/listados/tipo-concepto/actualizar-tipo-concepto  1.1.532-20250717
                                Route::post('agregar-tipo-concepto', [ABMTipoConceptoController::class, 'agregar_tipo_concepto']); // int/listados/tipo-concepto/agregar-tipo-concepto  1.1.531-20250717 
                            }
                        );
                        // tipos de contactos
                        Route::group(['prefix' => 'tipo-contacto'], 
                            function() {
                                Route::get('listar-tipos-contactos', [ABMTipoContactoController::class, 'listar_tipos_contactos']); // int/listados/tipo-contacto/listar-tipos-contactos  1.1.707-20260304
                                Route::post('actualizar-tipo-contacto', [ABMTipoContactoController::class, 'actualizar_tipo_contacto']); // int/listados/tipo-contacto/actualizar-tipo-contacto  1.1.707-20260304
                                Route::post('agregar-tipo-contacto', [ABMTipoContactoController::class, 'agregar_tipo_contacto']); // int/listados/tipo-contacto/agregar-tipo-contacto  1.1.707-20260304
                            }
                        );
                        // tipos de domicilios
                        Route::group(['prefix' => 'tipo-domicilio'], 
                            function() {
                                Route::get('listar-tipos-domicilios', [ABMTipoDomicilioController::class, 'listar_tipos_domicilios']); // int/listados/tipo-domicilio/listar-tipos-domicilios  1.1.680-20260114
                                Route::post('actualizar-tipo-domicilio', [ABMTipoDomicilioController::class, 'actualizar_tipo_domicilio']); // int/listados/tipo-domicilio/actualizar-tipo-domicilio  1.1.680-20260114
                                Route::post('agregar-tipo-domicilio', [ABMTipoDomicilioController::class, 'agregar_tipo_domicilio']); // int/listados/tipo-domicilio/agregar-tipo-domicilio  1.1.680-20260114 
                            }
                        );
                        // tipos de factura
                        Route::group(['prefix' => 'tipos-factura'], 
                            function() {
                                Route::get('listar-tipos-factura', [ABMTipoFacturaController::class, 'listar_tipos_factura']); // int/listados/tipo-factura/listar-tipos-factura  1.1.536-20250717
                                Route::post('actualizar-tipo-factura', [ABMTipoFacturaController::class, 'actualizar_tipo_factura']); // int/listados/tipo-factura/actualizar-tipo-factura  1.1.536-20250718
                                Route::post('agregar-tipo-factura', [ABMTipoFacturaController::class, 'agregar_tipo_factura']); // int/listados/tipo-factura/agregar-tipo-factura  1.1.536-20250718
                            }
                        );
                    }
                );
                // mensajes
                Route::group(['prefix' => 'mensajes'], 
                    function() {
                        Route::get('buscar-estado-mensaje', [MensajeController::class, 'buscar_estado_mensaje']); // int/mensajes/buscar-estado-mensaje
                        Route::get('buscar-mensajes-no-leidos', [MensajeController::class, 'buscar_mensajes_no_leidos']); // int/mensajes/buscar-mensajes-no-leidos
                        Route::get('listar-mensajes', [MensajeController::class, 'listar_mensajes']); // int/mensajes/listar-mensajes 
                        Route::post('enviar-mensaje', [MensajeController::class, 'enviar_mensaje']); // int/mensajes/enviar-mensaje 
                        Route::post('marcar-mensaje-como', [MensajeController::class, 'marcar_mensaje_como']); // int/mensajes/marcar-mensaje-como
                    }
                );
                // notificaciones
                Route::group(['prefix' => 'notificaciones'], 
                    function() {
                        Route::get('buscar-notificaciones', [NotificacionController::class, 'buscar_notificaciones']); // int/notificaciones/buscar-notificaciones 
                        Route::get('marcar-como-leida', [NotificacionController::class, 'marcar_como_leida']); // int/notificaciones/marcar_como_leida 
                        Route::post('eliminar-notificacion', [NotificacionController::class, 'eliminar_notificacion']); // int/notificaciones/eliminar-notificacion
                    }
                );
                // prestacion
                Route::group(['prefix' => 'prestacion'], 
                    function() {
                        Route::get('buscar-practicas-prestadores', [PrestacionController::class, 'buscar_practicas_prestadores']); // int/prestacion/buscar-practicas-prestadores // AB-69
                        Route::get('buscar-prestaciones', [PrestacionController::class, 'buscar_prestaciones']); // int/prestacion/buscar-prestaciones // AB-69
                        Route::get('exportar-prestaciones', [ExportarPrestacionController::class, 'exportar_prestaciones']); // int/prestacion/exportar-prestaciones  
                        Route::get('verificar-carga-consulta', [PrestacionController::class, 'verificar_carga_consulta']); // int/prestacion/verificar-carga-consulta // AB-121    
                    }
                );
                // prestadores
                Route::group(['prefix' => 'prestadores'], 
                    function() {
                        Route::get('buscar-centros-atencion', [PrestadoresController::class, 'buscar_centros_atencion']); // int/prestadores/buscar-centros-atencion  AB-83   
                        Route::get('buscar-informacion-prestador', [PrestadoresController::class, 'buscar_informacion_prestador']); // int/prestadores/buscar-informacion-prestador  AB-78  
                        Route::get('buscar-prestador', [PrestadorController::class, 'buscar_prestador']); // int/prestadores/buscar-prestador            
                        Route::get('buscar-prestadores', [PrestadoresController::class, 'buscar_prestadores']); // int/prestadores/buscar-prestadores  AB-78  
                        Route::post('agregar-prestador', [PrestadorController::class, 'agregar_prestador']); // int/prestadores/agregar-prestador            
                    }
                );
                // programas especiales
                Route::group(['prefix' => 'programas-especiales'], 
                    function() {
                        Route::get('buscar-programas-especiales', [ProgramasEspecialesController::class, 'buscar_programas_especiales']); // int/programas-especiales/buscar-programas-especiales  1.1.724-20260319   
                        Route::group(['prefix' => 'formularios'],
                            function(){
                                Route::post('exportar-formulario-cronicos', [ExportarFormulariosCronicosController::class, 'exportar_formulario_cronicos']); // int/programas-especiales/formularios/exportar-formulario-cronicos  1.1.701-20260226
                                Route::post('exportar-recetario-tratamientos-cronicos', [ExportarFormulariosCronicosController::class, 'exportar_recetario_tratamientos_cronicos']); // int/programas-especiales/formularios/exportar-recetario-tratamientos-cronicos-vacio  1.1.702-20260227
                            }
                        );
                    }
                );
                // recetas
                Route::group(['prefix' => 'recetas'], 
                    function() {
                        Route::get('buscar-receta', [RecetasController::class, 'buscar_receta']); // int/recetas/buscar AB-58 
                        Route::get('diagnosticos', [RecetasController::class, 'get_diagnosticos']); // int/recetas/diagnosticos AB-58 
                        Route::get('financiadores', [RecetasController::class,  'get_financiadores']); // int/recetas/financiadores  
                        Route::get('generar-pdf-receta', [ExportarRecetaController::class, 'generar_pdf_receta']);  // int/recetas/generar-pdf-receta
                        Route::get('listar-recetas-emitidas', [RecetasController::class, 'listar_recetas_emitidas']); // int/recetas/listar-recetas-emitidas  AB-85  
                        Route::get('medicamentos', [RecetasController::class, 'get_medicamentos']); // int/recetas/medicamentos AB-58
                        Route::get('medicos', [RecetasController::class, 'get_medicos']); // int/recetas/medicos AB-61  
                        Route::post('anular-receta', [RecetasController::class, 'anular_receta']); // int/recetas/anular-receta     
                        Route::post('generar-receta', [RecetasController::class, 'generar_receta_medicamentos']); // int/recetas/generar-receta AB-58 
                        // prescripciones
                        Route::get('listar-prescripciones-emitidas', [PrescripcionController::class, 'listar_prescripciones_emitidas']); // int/recetas/listar-prescripciones-emitidas  1.1.566-20250828
                        Route::get('practicas', [PrescripcionController::class, 'get_practicas']); // int/recetas/practicas 1.1.558-20250818
                        Route::get('tipo-y-categoria-practicas', [PrescripcionController::class, 'get_tipo_y_categoria_practicas']); // int/recetas/tipo-categoria-practicas  1.1.558-20250818
                        Route::post('generar-prescripcion-practicas', [PrescripcionController::class, 'generar_prescripcion_practicas']); // int/recetas/generar-prescripcion-practicas  1.1.562-20250821
                        // certificados medicos
                        Route::get('listar-recetas-certificados-emitidos', [RecetaCertificadoController::class, 'listar_recetas_certificados_emitidos']); // int/recetas/listar-recetas-certificados-emitidos  1.1.574-20250908
                        Route::post('generar-receta-certificado', [RecetaCertificadoController::class, 'generar_receta_certificado']); // int/recetas/generar-receta-certificado  1.1.574-20250908
                    }
                );
                // usuarios sqlserver
                Route::group(['prefix' => 'usuarios-sqlserver'],
                    function() {
                        Route::get('buscar-auditor', [UsuariosSqlserverController::class, 'buscar_auditor']); // int/usuarios-sqlserver/buscar-auditor
                    }
                );
                // validaciones
                Route::group(['prefix' => 'validaciones'], 
                    function() {
                        Route::get('buscar-validaciones', [ValidacionesController::class, 'buscar_validaciones']); // int/validaciones/buscar-validaciones AB-52  
                        Route::get('consultar-consumos-liquidados', [ConsumosController::class, 'consultar_consumos_liquidados']); // int/validaciones/consultar-consumos-liquidados
                        Route::get('consultar-consumos', [ConsumosController::class, 'consultar_consumos_afiliado']); // int/validaciones/consultar-consumos
                        Route::get('consultar-prestaciones-validacion', [PrestacionesController::class, 'consultar_prestaciones_validacion']); //int/validaciones/consultar-prestaciones-validacion AB-53  
                        Route::get('exportar-validacion', [ExportarValidacionController::class, 'exportar_validacion']); // int/validacionesexportar-validacion AB-53  
                        Route::post('actualizar-validacion', [ValidacionController::class, 'actualizar_validacion']); // int/validaciones/actualizar-validacion    
                        Route::post('anular-pic', [ValidacionController::class, 'anular_pic']); // int/validaciones/anular-pic
                        Route::post('anular-validacion', [ValidacionController::class, 'anular_validacion']); // int/validaciones/anular-validacion    
                        Route::post('emitir-validacion', [ValidacionController::class, 'emitir_validacion']); // int/validaciones/emitir-validacion AB-122    
                        Route::post('guardar-observacion-validacion', [ValidacionController::class, 'guardar_observacion_validacion']); // int/validaciones/guardar-observacion-validacion 1.1.634-20251203
                        // movimientos
                        Route::group(['prefix' => 'movimientos'], 
                            function() {
                                Route::get('consultar-movimientos-validacion', [MovimientosValidacionController::class, 'consultar_movimientos_validacion']); // int/validaciones/movimientos/consultar-movimientos-validacion 1.1.414-20250526 
                                Route::post('insertar-movimiento-validacion', [MovimientosValidacionController::class, 'insertar_movimiento_validacion']); // int/validaciones/movimientos/insertar-movimiento-validacion 1.1.414-20250526 
                                Route::post('actualizar-movimiento-validacion', [MovimientosValidacionController::class, 'actualizar_movimiento_validacion']); // int/validaciones/movimientos/actualizar-movimiento-validacion 1.1..635-20251203  
                            }
                        );  
                        // caja
                        Route::group(['prefix' => 'caja'],
                            function(){
                                
                                Route::get('buscar-detalle-cierre-caja', [CajaValidacionesController::class, 'buscar_detalle_cierre_caja']); // int/validaciones/caja/buscar-detalle-cierre-caja 1.1.489-20250609
                                Route::get('consultar-cierres-caja', [CajaValidacionesController::class, 'consultar_cierres_caja']); // int/validaciones/caja/consultar-cierres-caja 1.1.488-20250609
                                Route::get('consultar-movimientos-caja', [CajaValidacionesController::class, 'consultar_movimientos_caja']); // int/validaciones/caja/consultar-movimientos-caja 1.1.48-20250602
                                Route::post('cerrar-caja', [CajaValidacionesController::class, 'cerrar_caja']); // int/validaciones/caja/cerrar-caja 1.1.486-20250605
                                Route::post('insertar-movimiento-caja', [CajaValidacionesController::class, 'insertar_movimiento_caja']); // int/validaciones/caja/insertar-movimiento-caja 1.1.480-20250602
                                Route::post('relacionar-factura-movimiento', [CajaValidacionesController::class, 'relacionar_factura_movimiento']); // int/validaciones/caja/relacionar-factura-movimiento 1.1.480-20250602
                            }
                        );
                        // preautorizaciones
                        Route::group(['prefix' => 'preautorizaciones'],
                            function(){
                                Route::get('listar-preautorizaciones', [PreautorizacionesController::class, 'listar_preautorizaciones']); // int/validaciones/preautorizaciones/listar-preautorizaciones 1.1.583-20250924
                                Route::post('actualizar-preautorizacion', [PreautorizacionesController::class, 'actualizar_preautorizacion']); // int/validaciones/preautorizaciones/actualizar-preautorizacion 1.1.583-20250924
                                Route::post('cambiar-estado-preautorizacion', [PreautorizacionesController::class, 'cambiar_estado_preautorizacion']); // int/validaciones/preautorizaciones/cambiar-estado-preautorizacion 1.1.638-20251204
                            }
                        );
                    }
                );

                // mensajes de telefono
                Route::group(['prefix' => 'phone-messages'], function(){
                    Route::post('enviar-mensaje-sms', [PhoneMessagesController::class, 'enviar_mensaje_sms']); // int/phone-messages/enviar-mensaje-sms 
                    Route::post('enviar-mensaje-whatsapp', [PhoneMessagesController::class, 'enviar_mensaje_whatsapp']); // int/phone-messages/enviar-mensaje-whatsapp 
                    Route::post('enviar-receta-whatsapp', [PhoneMessagesController::class, 'enviar_receta_whatsapp']); // int/phone-messages/enviar-receta-whatsapp   1.1.578-20250911  
                    Route::post('enviar-validacion-whatsapp', [PhoneMessagesController::class, 'enviar_validacion_whatsapp']); // int/phone-messages/enviar-validacion-whatsapp   1.1.581-20250918  
                });
            }
        );
        Route::group(['prefix' => 'pusher'], 
            function(){
                Route::post('auth', [PusherController::class, 'auth']); // int/pusher/auth  
            }
        );
        Route::group(['prefix' => 'pruebas', 'middleware' => 'auth:api'], //quitar  'middleware' => 'auth:api' para pruebas sin logueo
            function() {
                Route::post('emitir-pusher', [PruebasController::class, 'emitir_pusher']); // int/pruebas/emitir_pusher
                Route::post('emitir-cambio-version', [PruebasController::class, 'emitir_cambio_version']); // int/pruebas/emitir-cambio-version 
                Route::post('prueba-osef', [PruebasController::class, 'prueba_osef']); // int/pruebas/prueba-osef  // 1.1.708-20260304 sólo para pruebas
                Route::post('probar-codigo', [PruebasController::class, 'probar_codigo_post']); // int/pruebas/probar-codigo  // 1.1.720-20260313 sólo para pruebas
                Route::get('probar-codigo', [PruebasController::class, 'probar_codigo_get']); // int/pruebas/probar-codigo  // 1.1.720-20260313 sólo para pruebas
            }
        );
    }
);

// Rutas de Microsoft Graph (deshabilitadas - ahora usa Client Credentials Flow, no OAuth)
// Estas rutas ya no son necesarias con Client Credentials
/*
Route::group(['prefix' => 'msgraph'], 
    function() {
        Route::get('auth/redirect', [MicrosoftGraphAuthController::class, 'redirectToMicrosoft']); // OBSOLETO
        Route::get('auth/callback', [MicrosoftGraphAuthController::class, 'handleMicrosoftCallback']); // OBSOLETO
        Route::post('auth/revoke', [MicrosoftGraphAuthController::class, 'revokeAuthorization']); // OBSOLETO
    }
);
*/



