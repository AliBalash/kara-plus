<?php

namespace App\Livewire\Ai;

use Livewire\Component;

class GlobalRail extends Component
{
    public bool $open = false;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function render()
    {
        return view('livewire.ai.global-rail');
    }
}
