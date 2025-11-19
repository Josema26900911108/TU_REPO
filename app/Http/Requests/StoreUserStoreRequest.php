<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserStoreRequest extends FormRequest
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
            'fkUsuario' => 'required|exists:users,id',
            'fkTienda' => 'required|exists:tienda,idTienda'
        ];
    }

    public function attributes()
    {
        return [
            'fkUsuario' => 'fkUsuario',
            'fkTienda' => 'fkTienda',
            'Estatus' => 'Estatus',
            'FechaIngreso' => 'FechaIngreso',
            'FechaEgreso' => 'FechaEgreso',
            'FechaBaja' => 'FechaBaja',
            'FechaActualizacion' => 'FechaActualizacion',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ];
    }

    public function messages()
    {
        return [
            'fkUsuario.required' => 'Se necesita un seleccionar un usuario',
            'fkTienda.required' => 'Se necesita un seleccionar un usuario'
        ];
    }
}
