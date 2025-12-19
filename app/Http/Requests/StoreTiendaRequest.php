<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTiendaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'Nombre' => 'required|unique:tienda,Nombre|max:150',
            'descripcion' => 'nullable|max:255',
            'Direccion' => 'nullable|max:255'
        ];
    }

    public function attributes()
    {
        return [
            'Nombre' => 'Nombre',
            'telefono' => 'telefono',
            'Direccion' => 'Direccion',
            'descripcion' => 'descripcion'
        ];
    }

    public function messages()
    {
        return [
            'Nombre.required' => 'El nombre es obligatorio',
            'telefono.required' => 'El Telefono es obligatorio',
            'Direccion.required' => 'La direccion es obligatorio',
            'descripcion.required' => 'La descripcion es obligatorio'
        ];
    }
}
