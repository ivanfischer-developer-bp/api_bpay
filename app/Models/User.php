<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
// use Laravel\Sanctum\HasApiTokens;

use App\Models\Mensaje;
use App\Models\ProfileDoctor;
use App\Models\ProfileSecretary;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'id', 
        'apellido',
        'company_name',
        'email',
        'id_prestador',
        'id_usuario_sqlserver',
        'name',
        'nombre',
        'nro_doc',
        'password',
        'perfil_completo',
        'role',
        'tipo_doc',
        'usuario',
        'connected',
        'id_sesion_activa'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Return the role names of the user
     */
    public function getRoles()
    {
        return $this->getRoleNames();
    }

    /**
     * Return the permissions of the user
     */
    public function getPermissions()
    {
        return $this->getAllPermissions();
    }

    /**
     * Return if the user is admin
     */
    public function es_super_admin()
    {
        return $this->hasRole('super administrador') ? true : false;
    }

    /**
     * Return if the user is admin
     */
    public function es_admin()
    {
        return $this->hasRole('administrador') ? true : false;
    }

    /**
     * Return if the user is afiliado
     */
    public function es_afiliado()
    {
        return $this->hasRole('afiliado') ? true : false;
    }

    /**
     * Return if the user is prestador
     */
    public function es_prestador()
    {
        return $this->hasRole('prestador') ? true : false;
    }

    /**
     * Return if the user is prestador
     */
    public function es_supervisor()
    {
        return $this->hasRole('supervisor') ? true : false;
    }

    public function profile_doctor(): HasOne{
        return $this->hasOne(ProfileDoctor::class);
    }

    public function profile_secretary(): HasOne{
        return $this->hasOne(ProfileSecretary::class);
    }

    /**
     * The mensajes that belong to the user.
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class)->withPivot('leido', 'abierto', 'ejecutado', 'mostrado')->withTimestamps();
    }
    
}
