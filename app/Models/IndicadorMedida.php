<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('indicador_medidas')]
#[Fillable([
    'indicadores_id',
    'establecimientos_id',
    'ano',
    'mes',
    'numerador_value',
    'denominador_value',
    'porcentaje_cumplimiento',
    'status',
])]
class IndicadorMedida extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = [
        'año',
        'valor_numerador',
        'valor_denominador',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ano' => 'integer',
            'mes' => 'integer',
            'numerador_value' => 'decimal:2',
            'denominador_value' => 'decimal:2',
            'porcentaje_cumplimiento' => 'decimal:2',
        ];
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class, 'indicadores_id');
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'establecimientos_id');
    }

    public function getAñoAttribute(): ?int
    {
        return $this->ano;
    }

    public function setAñoAttribute(?int $value): void
    {
        $this->attributes['ano'] = $value;
    }

    public function getValorNumeradorAttribute(): ?float
    {
        return isset($this->attributes['numerador_value']) ? (float) $this->attributes['numerador_value'] : null;
    }

    public function setValorNumeradorAttribute(mixed $value): void
    {
        $this->attributes['numerador_value'] = $value;
    }

    public function getValorDenominadorAttribute(): ?float
    {
        return isset($this->attributes['denominador_value']) ? (float) $this->attributes['denominador_value'] : null;
    }

    public function setValorDenominadorAttribute(mixed $value): void
    {
        $this->attributes['denominador_value'] = $value;
    }

    public function getEstadoAttribute(): ?string
    {
        return $this->attributes['status'] ?? null;
    }

    public function setEstadoAttribute(?string $value): void
    {
        $this->attributes['status'] = $value;
    }
}
