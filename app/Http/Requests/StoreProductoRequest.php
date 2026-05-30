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
    // Extraemos el ID correctamente, ya sea que venga como objeto o como número
    $productoId = $this->route('producto');
    $id = is_object($productoId) ? $productoId->id : $productoId;

    $fkTienda = session('user_fkTienda');

    return [
        'codigo' => [
            'required',
            'max:50',
            Rule::unique('productos', 'codigo')
                ->ignore($id)
                ->where(function ($query) use ($fkTienda) {
                    return $query->where('fkTienda', $fkTienda);
                }),
        ],

        'nombre' => [
            'required',
            'max:150',
            Rule::unique('productos', 'nombre')
                ->ignore($id)
                ->where(function ($query) use ($fkTienda) {
                    return $query->where('fkTienda', $fkTienda);
                }),
        ],

        'descripcion'       => 'nullable|max:255',
        'fecha_vencimiento' => 'nullable|date',
        'img_path' => 'nullable|string', // Cambia de 'image' a 'string'
        'marca_id'          => 'required|integer|exists:marcas,id',
        'presentacione_id'  => 'required|integer|exists:presentaciones,id',
        'categorias'        => 'required|array', // Aseguramos que sea un array
        'perecedero'        => 'boolean'
    ];
}

protected function prepareForValidation()
{
    // Si el campo img_path viene vacío o no es una cadena válida, lo forzamos a null
    if (empty($this->img_path) || !is_string($this->img_path)) {
        $this->merge([
            'img_path' => null,
        ]);
    }
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
