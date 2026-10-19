<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        } else {
            $this->merge([
                'is_active' => true,
            ]);
        }

        // Limpiar hoja de prefijos redundantes (ej: 'REM-A03' -> 'A03')
        if ($this->has('hoja')) {
            $hoja = trim((string) $this->input('hoja'));
            $hoja = preg_replace('/^REM[-_\s]*/i', '', $hoja);
            $this->merge(['hoja' => strtoupper($hoja)]);
        }
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'indicadores_id' => ['required', 'exists:indicadores,id'],
            'hoja' => ['required', 'string', 'max:50'],
            'celda_numerador' => ['required', 'string', 'max:255'],
            'celda_denominador' => ['nullable', 'string', 'max:255'],
            'seccion' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'indicadores_id' => 'indicador o meta sanitaria',
            'hoja' => 'hoja REM',
            'celda_numerador' => 'celda(s) del numerador',
            'celda_denominador' => 'celda(s) del denominador',
            'seccion' => 'sección de la hoja',
            'descripcion' => 'descripción de extracción',
            'is_active' => 'estado',
        ];
    }
}
