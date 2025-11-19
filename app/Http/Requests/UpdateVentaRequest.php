<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVentaRequest extends FormRequest
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
            'fecha_hora' => 'required',
            'impuesto' => 'required',
            'total' => 'required|numeric',
            'cliente_id' => 'required|exists:clientes,id',
            'user_id' => 'required|exists:users,id'
        ];
    }
}
