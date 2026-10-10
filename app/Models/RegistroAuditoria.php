<?php

namespace App\Models;

use Database\Factories\RegistroAuditoriaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class RegistroAuditoria extends Model
{
    /** @use HasFactory<RegistroAuditoriaFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'registros_auditoria';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'accion',
        'entidad',
        'entidad_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Los registros de auditoría no se pueden modificar.');
        });

        static::deleting(function (): void {
            throw new LogicException('Los registros de auditoría no se pueden eliminar.');
        });
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
