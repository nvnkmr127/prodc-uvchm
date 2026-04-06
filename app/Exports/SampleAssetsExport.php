<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class SampleAssetsExport implements WithHeadings
{
    /**
    * @return array
    */
    public function headings(): array
    {
        // These headings MUST match the keys expected by your AssetsImport class
        return [
            'name',
            'asset_code',
            'asset_category_id',
            'location',
            'quantity',
            'condition',
            'purchase_date',
            'purchase_price',
        ];
    }
}