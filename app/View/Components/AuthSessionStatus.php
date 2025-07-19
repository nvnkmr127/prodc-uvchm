<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AuthSessionStatus extends Component
{
    public function render(): View
    {
        return view('components.auth-session-status');
    }
}
