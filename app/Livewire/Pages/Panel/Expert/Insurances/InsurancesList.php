<?php

namespace App\Livewire\Pages\Panel\Expert\Insurances;

use App\Models\Car;
use Livewire\Component;
use App\Models\Insurance;
use App\Livewire\Concerns\InteractsWithToasts;
use Livewire\WithPagination;

class InsurancesList extends Component
{
    public $insurances; // ویژگی باید قابل مشاهده باشد
    use WithPagination;
    use InteractsWithToasts;

    public function mount() {}

    public function loadInsurances()
    {
        // بارگذاری بیمه‌ها به صورت ساده بدون paginator
    }

    public function deleteInsurance($id)
    {
        // حذف بیمه
        $insurance = Insurance::find($id);
        if ($insurance) {
            $insurance->delete();
            $this->toast('success', 'Insurance deleted successfully!');
            $this->loadInsurances(); // به روز رسانی لیست
        }
    }

    public function render()
    {
        // بارگذاری لیست بیمه‌ها هنگام بارگذاری کامپوننت
        $insurancelist = Insurance::with('car')->paginate(10);
        return view('livewire.pages.panel.expert.insurances.insurances-list', compact('insurancelist'));
    }
}
