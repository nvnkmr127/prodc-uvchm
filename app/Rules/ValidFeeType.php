<?php

namespace App\Rules;

use App\Models\FeeCategory;
use Illuminate\Contracts\Validation\Rule;

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
