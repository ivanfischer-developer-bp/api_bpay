<?php

namespace App\Models\SoftwareVersions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class FrontSoftwareVersion extends Model
{
    use HasApiTokens, HasFactory;

    protected $connection = 'software_versions';
    protected $table = 'new_bpay';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'id', 
        'version_number',
        'tarea',
        'acciones',
        'observaciones',
        'version_notes',
        'ambientes',
        'desarrollador',
        'tiempo',
        'publicar'
    ];

    protected $casts = [
        'publicar' => 'boolean',
        'fecha' => 'date:Y-m-d',
    ];

    protected function asDateTime($value)
    {
        if (is_string($value) && preg_match('/:\w{2}$/', $value)) {
            // Fix legacy ODBC format: "Apr 15 2026 12:00:00:AM" -> "Apr 15 2026 12:00:00 AM"
            $value = preg_replace('/:([APap][Mm])$/', ' $1', $value);
        }
        return parent::asDateTime($value);
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}