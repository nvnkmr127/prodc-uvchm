<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class NavLink extends Component
{
    public function render(): View
    {
        return view('components.nav-link');
    }
}
