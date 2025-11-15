<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashRegisterRequest extends FormRequest
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
            'Nombre' => 'required|string|max:255', // Nombre de la caja
            'fkTienda' => 'required|exists:tienda,idTienda', // ID de la tienda que debe existir en la tabla 'tienda'
            'Estatus' => 'required|string|in:A,I,B,O,C'
            // Agrega más reglas según tus campos
        ];
    }

    /**
     * Customize the validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'Nombre.required' => 'El nombre es obligatorio.',
            'fkTienda.required' => 'La tienda es obligatoria.'
            // Agrega más mensajes personalizados según sea necesario
        ];
    }
}
