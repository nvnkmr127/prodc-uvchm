<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class PrimaryButton extends Component
{
    public function render(): View
    {
        return view('components.primary-button');
    }
}
