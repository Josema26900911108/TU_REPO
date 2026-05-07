<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompraRequest extends FormRequest
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
        'proveedore_id' => 'required|exists:proveedores,id',
        'comprobante_id' => 'required|exists:comprobantes,id',
        'numero_comprobante' => [
            'required',
            'max:255',
            // Aplicamos la regla Unique pero con una excepción
            Rule::unique('compras', 'numero_comprobante')->whereNot('numero_comprobante', '0')
        ],
        'impuesto' => 'required',
        'fecha_hora' => 'required',
        'total' => 'required'
    ];
}
}
