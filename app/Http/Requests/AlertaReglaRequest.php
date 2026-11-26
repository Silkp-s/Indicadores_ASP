<?php

namespace App\Http\Requests;

use App\Models\AlertaRegla;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlertaReglaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('parametros') && is_array($this->input('parametros'))) {
            // Descarta los parámetros vacíos y normaliza los valores a numéricos.
            $parametros = array_filter(
                array_map(fn ($valor) => ($valor === '' || $valor === null) ? null : $valor, $this->input('parametros')),
                fn ($valor) => $valor !== null,
            );

            $this->merge(['parametros' => array_map(fn ($valor) => (float) $valor, $parametros)]);
        } else {
            $this->merge(['parametros' => []]);
        }
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'severidad' => ['required', Rule::in(AlertaRegla::SEVERIDADES)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'parametros' => ['array'],
            'parametros.*' => ['numeric'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'severidad' => 'severidad',
            'descripcion' => 'descripción',
            'parametros' => 'umbrales',
        ];
    }
}
