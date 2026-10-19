<?php

namespace App\Http\Requests;

use App\Models\Indicador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IaapsMetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->has('periodicidad') || blank($this->input('periodicidad'))) {
            $merge['periodicidad'] = 'Mensual';
        }

        if (! $this->has('fu') || blank($this->input('fu'))) {
            $merge['fu'] = 'REM-A';
        }

        if ($this->has('estado')) {
            $estado = $this->input('estado');
            $merge['is_active'] = $estado === 'Activo' || $estado === '1' || $estado === 1 || $this->boolean('estado');
        } elseif ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        } else {
            $merge['is_active'] = true;
        }

        if ($this->has('meta') && ! $this->has('target_value')) {
            $merge['target_value'] = $this->input('meta');
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $indicadorId = $this->route('indicador') instanceof Indicador
            ? $this->route('indicador')->id
            : $this->route('indicador');

        return [
            'codigo' => ['required', 'string', 'max:20', Rule::unique('indicadores', 'codigo')->ignore($indicadorId)],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(['Meta Sanitaria', 'IAAPS'])],
            'target_value' => ['required', 'numeric', 'between:0,100'],
            'periodicidad' => ['required', Rule::in(Indicador::PERIODICIDADES)],
            'fu' => ['required', Rule::in(Indicador::FUENTES)],
            'numerador_description' => ['nullable', 'string', 'max:1000'],
            'denominador_description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'nombre' => 'nombre del indicador',
            'tipo' => 'tipo de indicador',
            'target_value' => 'meta',
            'meta' => 'meta',
            'periodicidad' => 'periodicidad',
            'fu' => 'fuente de información',
            'numerador_description' => 'descripción del numerador',
            'denominador_description' => 'descripción del denominador',
            'is_active' => 'estado',
        ];
    }
}
