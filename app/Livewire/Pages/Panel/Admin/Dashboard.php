<?php

namespace App\Livewire\Pages\Panel\Admin;

use Livewire\Component;

class Dashboard extends Component
{
    public $title = 'داشبورد مدیریت';

    public function render()
    {
        return view('livewire.pages.panel.admin.dashboard')->with(['title' => $this->title]);
    }
}
