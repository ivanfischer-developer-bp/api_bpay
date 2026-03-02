<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use App\Models\User;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions for api
        // permisos de super admin
        Permission::create(['guard_name' => 'api', 'name' => 'eliminar usuarios']); // sólo super_admin
        Permission::create(['guard_name' => 'api', 'name' => 'resetear password']); // sólo super_admin
        // permisos comunes a todos los usuarios
        Permission::create(['guard_name' => 'api', 'name' => 'cambiar password']);
        // permisos administrativos
        Permission::create(['guard_name' => 'api', 'name' => 'registrar usuarios']);
        Permission::create(['guard_name' => 'api', 'name' => 'listar usuarios']);
        Permission::create(['guard_name' => 'api', 'name' => 'actualizar usuarios']);
        Permission::create(['guard_name' => 'api', 'name' => 'crear roles']);
        Permission::create(['guard_name' => 'api', 'name' => 'listar roles']);
        Permission::create(['guard_name' => 'api', 'name' => 'actualizar roles']);
        Permission::create(['guard_name' => 'api', 'name' => 'asignar roles']);
        Permission::create(['guard_name' => 'api', 'name' => 'borrar roles']);
        Permission::create(['guard_name' => 'api', 'name' => 'crear permisos']);
        Permission::create(['guard_name' => 'api', 'name' => 'listar permisos']);
        Permission::create(['guard_name' => 'api', 'name' => 'actualizar permisos']);
        Permission::create(['guard_name' => 'api', 'name' => 'asignar permisos']);
        Permission::create(['guard_name' => 'api', 'name' => 'borrar permisos']);
        // permisos para los usuarios
        // permisos para prestadores
        Permission::create(['guard_name' => 'api', 'name' => 'anular validaciones']);
        Permission::create(['guard_name' => 'api', 'name' => 'crear validacion ambulatorio']);
        Permission::create(['guard_name' => 'api', 'name' => 'crear validacion internacion']);
        Permission::create(['guard_name' => 'api', 'name' => 'consultar elegibilidad afiliaciones']);
        Permission::create(['guard_name' => 'api', 'name' => 'listar diagnosticos']);
        // permisos para afiliados
        Permission::create(['guard_name' => 'api', 'name' => 'consultar afiliado']);
        // permisos para prestadores y afiliados
        Permission::create(['guard_name' => 'api', 'name' => 'consultar validaciones']);
        Permission::create(['guard_name' => 'api', 'name' => 'consultar estado validaciones']); // no usado
        Permission::create(['guard_name' => 'api', 'name' => 'consultar listados']); 
        Permission::create(['guard_name' => 'api', 'name' => 'buscar afiliado']); 
        Permission::create(['guard_name' => 'api', 'name' => 'consultar coberturas especiales']); 
        Permission::create(['guard_name' => 'api', 'name' => 'gestionar afiliaciones']); 
        Permission::create(['guard_name' => 'api', 'name' => 'gestionar validaciones']); 
        Permission::create(['guard_name' => 'api', 'name' => 'gestionar expedientes']); 
        Permission::create(['guard_name' => 'api', 'name' => 'realizar consultas']); 
        Permission::create(['guard_name' => 'api', 'name' => 'consultar afiliados']); 
        Permission::create(['guard_name' => 'api', 'name' => 'exportar datos']); 
        Permission::create(['guard_name' => 'api', 'name' => 'consultar consumos afiliado']); 
        Permission::create(['guard_name' => 'api', 'name' => 'ver historia clinica']); 
        Permission::create(['guard_name' => 'api', 'name' => 'agregar historia clinica']); 
        Permission::create(['guard_name' => 'api', 'name' => 'gestionar historia clinica']); 

        // create super admin role
        $super_admin = Role::create(['guard_name' => 'api', 'name' => 'super administrador']);
        $super_admin->givePermissionTo(Permission::all());

        // create roles and assign existing permissions
        $admin = Role::create(['guard_name' => 'api', 'name' => 'administrador']);
        $admin->givePermissionTo([
            'cambiar password',
            'registrar usuarios',
            'listar usuarios',
            'actualizar usuarios',
            'crear roles',
            'listar roles',
            'actualizar roles',
            'asignar roles',
            'borrar roles',
            'crear permisos',
            'listar permisos',
            'actualizar permisos',
            'asignar permisos',
            'borrar permisos',
            'consultar validaciones',
            'consultar estado validaciones',
            'anular validaciones',
            'crear validacion ambulatorio',
            'crear validacion internacion',
            'consultar elegibilidad afiliaciones',
            'listar diagnosticos',
            'consultar afiliado',
            'consultar listados',
            'buscar afiliado',
            'consultar coberturas especiales',
            'gestionar afiliaciones',
            'gestionar validaciones',
            'gestionar expedientes',
            'realizar consultas',
            'consultar afiliados',
            'exportar datos',
            'consultar consumos afiliado',
            'ver historia clinica',
            'agregar historia clinica',
            'gestionar historia clinica'
        ]);

        // afiliado
        $afiliado = Role::create(['guard_name' => 'api', 'name' => 'afiliado']);
        $afiliado->givePermissionTo([
            'cambiar password',
            'consultar validaciones',
            'consultar estado validaciones',
            'consultar afiliado'
        ]);

        // prestador
        $prestador = Role::create(['guard_name' => 'api', 'name' => 'prestador']);
        $prestador->givePermissionTo([
            'cambiar password',
            'consultar validaciones',
            'consultar estado validaciones',
            'anular validaciones',
            'crear validacion ambulatorio',
            'crear validacion internacion',
            'consultar elegibilidad afiliaciones',
            'listar diagnosticos'
        ]);

        $usuario = Role::create(['guard_name' => 'api', 'name' => 'usuario']);
        $usuario->givePermissionTo([
            'cambiar password',
            'listar diagnosticos',
            'consultar listados',
            'realizar consultas',
            'exportar datos'
        ]);

        $basico = Role::create(['guard_name' => 'api', 'name' => 'basico']);
        $basico->givePermissionTo([
            'cambiar password',
            'listar diagnosticos',
            'consultar listados',
            'realizar consultas'
        ]);

        $auditor = Role::create(['guard_name' => 'api', 'name' => 'auditor']);
        $auditor->givePermissionTo([
            'cambiar password',
            'listar diagnosticos',
            'consultar listados',
            'realizar consultas'
        ]);

        $delegacion = Role::create(['guard_name' => 'api', 'name' => 'delegacion']);
        $delegacion->givePermissionTo([
            'cambiar password',
            'listar diagnosticos',
            'consultar listados',
            'realizar consultas',
            'consultar afiliado',
            'consultar validaciones',
            'consultar estado validaciones',
            'buscar afiliado',
            'consultar coberturas especiales',
            'consultar afiliados',
            'exportar datos',
            'consultar consumos afiliado'
        ]);

        $medico = Role::create(['guard_name' => 'api', 'name' => 'medico']);
        $medico->givePermissionTo([
            'buscar afiliado',
            'exportar datos',
            'ver historia clinica',
            'agregar historia clinica',
            'gestionar historia clinica'
        ]);

        // creamos usuarios
        // 1
        $user1 = User::create([
            'email' => 'ivanfischer76@gmail.com',
            'name' => 'Iván Fischer',
            'company_name' => NULL,
            'id_prestador' => NULL,
            'password' => Hash::make('wsxdr5tgbhu'),
        ]);
        $user1->assignRole($super_admin);
        // 2
        $user2 = User::create([
            'email' => 'pablo.rojas.paulazzo@gmail.com',
            'name' => 'Pablo Rojas Paulazzo',
            'company_name' => NULL,
            'id_prestador' => NULL,
            'password' => Hash::make('wsxdr5tgbhu'),
        ]);
        $user2->assignRole($super_admin);
        // 3
        $user3 = User::create([
            'email' => 'servega@gmail.com',
            'name' => 'Sergio Vega',
            'company_name' => NULL,
            'id_prestador' => NULL,
            'password' => Hash::make('wsxdr5tgbhu'),
        ]);
        $user3->assignRole($super_admin);
        // 4
        $user4 = User::create([
            'email' => 'test_admin@email.com',
            'name' => 'Test Admin ',
            'company_name' => NULL,
            'id_prestador' => NULL,
            'password' => Hash::make('abcd1234'),
        ]);
        $user4->assignRole($admin);
        // 5
        $user5 = User::create([
            'email' => 'test_afiliado@email.com',
            'name' => 'Test Afiliado',
            'company_name' => NULL,
            'id_prestador' => NULL,
            'password' => Hash::make('abcd1234'),
        ]);
        $user5->assignRole($afiliado);
        // 6
        $user6 = User::create([
            'email' => 'test_prestador_453@email.com',
            'name' => 'Test Prestador 453',
            'company_name' => 'Hospital Britanico',
            'id_prestador' => 453,
            'password' => Hash::make('abcd1234'),
        ]);
        $user6->assignRole($prestador);
        // 7
        $user7 = User::create([
            'email' => 'test_prestador_558@email.com',
            'name' => 'Test Prestador 558',
            'company_name' => 'Hospital Italiano',
            'id_prestador' => 558,
            'password' => Hash::make('abcd1234'),
        ]);
        $user7->assignRole($prestador);
        // 8
        $user8 = User::create([
            'email' => 'test_delegacion@email.com',
            'name' => 'Test Delegacion',
            'company_name' => NULL,
            'id_prestador' => NULL,
            'password' => Hash::make('abcd1234'),
        ]);
        $user8->assignRole($delegacion);
        // 9
        $user9 = User::create([
            'email' => 'test_medico@email.com',
            'name' => 'Test Medico',
            'company_name' => NULL,
            'id_prestador' => NULL,
            'password' => Hash::make('abcd1234'),
        ]);
        $user9->assignRole($medico);
        // 10
        $user10 = User::create([
            'email' => 'test_auditor@email.com',
            'name' => 'Test Auditor',
            'company_name' => NULL,
            'id_prestador' => NULL,
            'password' => Hash::make('abcd1234'),
        ]);
        $user10->assignRole($auditor);
        // 11
        $user11 = User::create([
            'email' => 'test_basico@email.com',
            'name' => 'Test Basico',
            'company_name' => NULL,
            'id_prestador' => NULL,
            'password' => Hash::make('abcd1234'),
        ]);
        $user11->assignRole($basico);
        // 1008
        // $user1008 = User::create([
            //     'email' => 'silvioq@gmail.com',
            //     'name' => 'Silvio Quadri',
            //     'company_name' => NULL,
            //     'id_prestador' => NULL,
            //     'password' => Hash::make('aP_casa2022!')
            // ]);
        // $user1008->assignRole($prestador);
            
        }
}
