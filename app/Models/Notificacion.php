<?php

namespace App\Models;

use Database\Factories\NotificacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notificación persistente de una acción realizada por un usuario.
 */
#[Table('notificaciones')]
#[Fillable(['user_id', 'tipo', 'mensaje', 'leida_en'])]
class Notificacion extends Model
{
    /** @use HasFactory<NotificacionFactory> */
    use HasFactory;

    /** Tipos de notificación soportados. */
    public const TIPOS = ['success', 'error', 'warning', 'info'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'leida_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Limita la consulta a las notificaciones no leídas.
     */
    #[Scope]
    protected function noLeidas(Builder $query): void
    {
        $query->whereNull('leida_en');
    }
}
