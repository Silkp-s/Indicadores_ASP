<?php

namespace App\Http\Requests;

use App\Models\Establecimiento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación compartida para crear y editar establecimientos de salud.
 */
class EstablecimientoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo_deis' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('establecimientos', 'codigo_deis')->ignore($this->route('establecimiento')),
            ],
            'tipo' => ['required', 'string', Rule::in(Establecimiento::TIPOS)],
            'poblacion_inscrita' => ['required', 'integer', 'min:0', 'max:10000000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del establecimiento',
            'codigo_deis' => 'código DEIS',
            'tipo' => 'tipo de recinto',
            'poblacion_inscrita' => 'población inscrita',
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
            'min' => 'El campo :attribute debe ser al menos :min.',
            'in' => 'El valor seleccionado en :attribute no es válido.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'boolean' => 'El campo :attribute no es válido.',
            'codigo_deis.unique' => 'Ya existe un establecimiento registrado con ese código DEIS.',
        ];
    }
}
