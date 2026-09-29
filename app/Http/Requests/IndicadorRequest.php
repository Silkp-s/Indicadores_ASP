<?php

namespace App\Http\Requests;

use App\Models\Indicador;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación compartida para crear y editar indicadores del catálogo.
 */
class IndicadorRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:20', Rule::unique('indicadores', 'codigo')->ignore($this->route('indicador'))],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(Indicador::TIPOS)],
            'numerador_description' => ['nullable', 'string', 'max:1000'],
            'denominador_description' => ['nullable', 'string', 'max:1000'],
            'target_value' => ['required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'periodicidad' => ['required', Rule::in(Indicador::PERIODICIDADES)],
            'fu' => ['required', Rule::in(Indicador::FUENTES)],
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
            'numerador_description' => 'numerador',
            'denominador_description' => 'denominador',
            'target_value' => 'meta',
            'fu' => 'fuente',
            'is_active' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'max' => 'El campo :attribute no puede superar :max caracteres.',
            'in' => 'El valor seleccionado en :attribute no es válido.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'decimal' => 'El campo :attribute puede tener como máximo 2 decimales.',
            'between' => 'El campo :attribute debe estar entre :min y :max.',
            'boolean' => 'El campo :attribute no es válido.',
            'codigo.unique' => 'Ya existe un indicador con ese código.',
        ];
    }
}
