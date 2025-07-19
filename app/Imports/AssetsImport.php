<?php

namespace App\Imports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;


class AssetsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // This method is called for each row in the spreadsheet
        return new Asset([
            'name'              => $row['name'],
            'asset_category_id' => $row['asset_category_id'],
            'purchase_date'     => $row['purchase_date'],
            'cost'              => $row['cost'],
            'serial_number'     => $row['serial_number'],
            'status'            => 'available', // Set a default status
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.asset_category_id' => 'required|integer|exists:asset_categories,id',
            '*.purchase_date' => 'required|date',
            '*.cost' => 'required|numeric|min:0',
            '*.serial_number' => 'nullable|string|max:255|unique:assets,serial_number',
        ];
    }
}