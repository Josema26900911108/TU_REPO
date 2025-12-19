<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class GenericExport implements FromCollection, WithHeadings
{
    protected $data;
    protected $headings;

    public function __construct(Collection $data, array $headings = [])
    {
        $this->data = $data;
        $this->headings = $headings;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headingsnostd(): array
    {
        if (!empty($this->headings)) {
            return $this->headings;
        }

        return $this->data->first() ? array_keys($this->data->first()->toArray()) : [];
    }

    public function headings(): array
{
    if (!empty($this->headings)) {
        return $this->headings;
    }

    $first = $this->data->first();

    if ($first instanceof \stdClass) {
        // convertir stdClass a array antes de obtener las claves
        return array_keys((array) $first);
    }

    return $first ? array_keys($first) : [];
}

}
