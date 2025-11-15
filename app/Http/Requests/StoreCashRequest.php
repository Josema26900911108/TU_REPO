<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashRequest extends FormRequest
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
            'Nombre' => 'required|unique:cash_registers,Nombre|max:150'
        ];
    }

    public function attributes()
    {
        return [
            'Nombre' => 'Nombre',
            'fkTienda'=>'fkTienda',
            'Estatus'=>'Estatus'
        ];
    }

    public function messages()
    {
        return [
            'Nombre.required' => 'El nombre es obligatorio'
        ];
    }
}
