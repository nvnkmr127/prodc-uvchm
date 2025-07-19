<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\FeeCategory;

class ValidFeeType implements Rule
{
    public function passes($attribute, $value)
    {
        return FeeCategory::where('category_type', $value)->exists();
    }

    public function message()
    {
        return 'The selected fee type is invalid.';
    }
}