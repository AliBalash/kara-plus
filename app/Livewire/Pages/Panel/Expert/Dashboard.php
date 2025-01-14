<?php

namespace App\Livewire\Pages\Panel\Expert;

use Livewire\Component;

class Dashboard extends Component
{
    public $title = 'داشبورد کارشناس';

    public function render()
    {
        return view('livewire.pages.panel.expert.dashboard')->with(['title' => $this->title]);
    }

    public $count = 1;
 
    public function increment()
    {
        $this->count++;
    }
 
    public function decrement()
    {
        $this->count--;
    }
}
