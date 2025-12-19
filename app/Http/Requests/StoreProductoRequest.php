<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductoRequest extends FormRequest
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
    $id = $this->route('producto'); // ID del producto cuando estás editando

    return [
        'codigo' => [
            'required',
            'max:50',
            Rule::unique('productos')
                ->ignore($id) // evita error al editar
                ->where(fn($query) =>
                    $query->where('fkTienda', session('user_fkTienda'))
                ),
        ],

        'nombre' => [
            'required',
            'max:150',
            Rule::unique('productos')
                ->ignore($id) // evita error al editar
                ->where(fn($query) =>
                    $query->where('fkTienda', session('user_fkTienda'))
                ),
        ],

        'descripcion' => 'nullable|max:255',
        'fecha_vencimiento' => 'nullable|date',
        'img_path' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        'marca_id' => 'required|integer|exists:marcas,id',
        'presentacione_id' => 'required|integer|exists:presentaciones,id',
        'categorias' => 'required'
    ];
}

    public function attributes()
    {
        return [
            'marca_id' => 'marca',
            'presentacione_id' => 'presentación'
        ];
    }

    public function messages()
    {
        return [
            'codigo.required' => 'Se necesita un campo código'
        ];
    }
}
