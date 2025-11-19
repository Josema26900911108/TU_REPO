<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTecnicoRequest extends FormRequest
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
        /*
$tecnico = $this->route('tecnico');

    return [
        'fkTienda' => [
            'required',
            Rule::unique('tecnico', 'fkTienda')
                ->where(function ($query) use ($tecnico) {
                    return $query->where('id', '!=', $tecnico->id);
                }),
        ],
        'codigo' => 'required|max:80',
        'especialidad' => 'required|max:250',
    ];
*/
  return [
        'fkTienda' => 'required|exists:tienda,idTienda',
        'codigo' => 'required|max:80',
        'especialidad' => 'required|max:250',
    ];
    
    }
}
