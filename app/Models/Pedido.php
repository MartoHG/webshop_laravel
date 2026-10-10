<?php

namespace App\Models;

use App\Enums\EstadoContacto;
use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MotivoCancelacion;
use App\Enums\Provincia;
use Database\Factories\PedidoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    /** @use HasFactory<PedidoFactory> */
    use HasFactory;

    protected $table = 'pedidos';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'pedido_origen_id',
        'nombre_cliente',
        'apellido_cliente',
        'dni_cliente',
        'email_cliente',
        'telefono_cliente',
        'provincia_envio',
        'localidad_envio',
        'codigo_postal_envio',
        'direccion_envio',
        'numero_envio',
        'direccion_adicional',
        'observaciones_envio',
        'expreso',
        'subtotal_productos',
        'costo_envio',
        'total_base',
        'estado_pedido',
        'estado_contacto',
        'estado_envio',
        'estado_pago',
        'huella_checkout',
    ];

    /** @var list<string> */
    protected $hidden = [
        'token_pago',
        'huella_checkout',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provincia_envio' => Provincia::class,
            'subtotal_productos' => 'decimal:2',
            'costo_envio' => 'decimal:2',
            'total_base' => 'decimal:2',
            'estado_pedido' => EstadoPedido::class,
            'estado_contacto' => EstadoContacto::class,
            'estado_envio' => EstadoEnvio::class,
            'estado_pago' => EstadoPago::class,
            'motivo_cancelacion' => MotivoCancelacion::class,
            'contactado_en' => 'datetime',
            'pago_habilitado_en' => 'datetime',
            'pagado_en' => 'datetime',
            'preparado_en' => 'datetime',
            'despachado_en' => 'datetime',
            'entregado_en' => 'datetime',
            'cancelado_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemPedido::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function pedidoOrigen(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_origen_id');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cancelado_por');
    }
}
