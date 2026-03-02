<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class addPermissionConsultarCoberturasSeeder extends Seeder
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
        // permisos para prestadores y afiliados
        Permission::create(['guard_name' => 'api', 'name' => 'consultar coberturas especiales']); // no usado
        
        $super_admin = Role::where('name', 'like', 'super administrador')->first();
        $admin = Role::where('name', 'like', 'administrador')->first();
        $afiliado = Role::where('name', 'like', 'afiliado')->first();
        $prestador = Role::where('name', 'like', 'prestador')->first();
        // assign existing permissions to roles
        $super_admin->givePermissionTo(['consultar coberturas especiales']);
        $admin->givePermissionTo(['consultar coberturas especiales']);
        $afiliado->givePermissionTo(['consultar coberturas especiales']);
        $prestador->givePermissionTo(['consultar coberturas especiales']);
    }
}
