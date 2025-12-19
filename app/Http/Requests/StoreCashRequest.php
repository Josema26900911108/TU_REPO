<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $id = $this->route('cash_register');
        return [
            'Nombre' =>
            'required',
            'max:150',
            Rule::unique('cash_registers','Nombre')
            ->ignore($id)
            ->where(fn($query)=>
            $query->where('fkTienda',session('user_fkTienda'))
            ),
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
