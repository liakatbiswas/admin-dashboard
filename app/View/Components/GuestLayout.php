<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GuestLayout extends Component
{
    public string $title;

    public array $crumbs;

    public bool $wide;

    /**     * Create a new component instance.
     */
    public function __construct(string $title = 'Dashboard', array $crumbs = [], bool $wide = false)
    {
        $this->wide = $wide;
        $this->title = $title;
        $this->crumbs = $crumbs;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('layouts.guest');
    }
}
