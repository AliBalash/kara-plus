<?php

namespace App\Livewire\Pages\Panel\Expert;

use App\Models\Contract;
use App\Models\DiscountCode;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $title = 'داشبورد کارشناس';
    public $discountCodesCount;
    public $usedDiscountCodes;
    public $usageRate;
    public $averageDiscount;
    public $latestDiscountCodes;
    public $userDiscountCodes;
    public $userUsedDiscountCodes;
    public $lastUserDiscountCode;

    public $totalContracts;
    public $activeContracts;
    public $completedContracts;
    public $cancelledContracts;
    public $underReviewContracts;
    public $averageTotalPrice;
    public $latestContracts;
    public $lastUserContractStatus;

    public $topBrands;
    public $reservedCars;

    public function mount()
    {
        $this->discountCodesCount = DiscountCode::count();
        $this->usedDiscountCodes = DiscountCode::where('contacted', true)->count();
        $this->usageRate = $this->discountCodesCount > 0 ? round(($this->usedDiscountCodes / $this->discountCodesCount) * 100, 2) : 0;
        $this->averageDiscount = DiscountCode::avg('discount_percentage');
        $this->latestDiscountCodes = DiscountCode::orderBy('created_at', 'desc')->take(5)->get();
        $this->userDiscountCodes = DiscountCode::where('phone', Auth::user()->phone)->count();
        $this->userUsedDiscountCodes = DiscountCode::where('phone', Auth::user()->phone)->where('contacted', true)->count();
        $this->lastUserDiscountCode = DiscountCode::where('phone', Auth::user()->phone)->latest()->first();


        // آمار کلی قراردادها
        $this->totalContracts = Contract::count();
        $this->activeContracts = Contract::whereIn('current_status', ['assigned', 'under_review', 'delivery'])->count();
        $this->completedContracts = Contract::where('current_status', 'complete')->count();
        $this->cancelledContracts = Contract::where('current_status', 'cancelled')->count();
        $this->underReviewContracts = Contract::where('current_status', 'under_review')->count();

        // میانگین قیمت کل قراردادها
        $this->averageTotalPrice = Contract::avg('total_price');

        // دریافت آخرین ۵ قرارداد
        $this->latestContracts = Contract::orderBy('created_at', 'desc')->take(5)->get();

        // دریافت وضعیت آخرین قرارداد کاربر جاری
        $lastContract = Contract::where('user_id', Auth::id())->latest()->first();
        $this->lastUserContractStatus = $lastContract ? $lastContract->current_status : null;



        // دریافت ۳ خودرو با بیشترین قرارداد
        $topCars = Contract::selectRaw('car_id, COUNT(*) as total')
            ->groupBy('car_id')
            ->orderByDesc('total')
            ->take(3)
            ->with('car')
            ->get();
        // تبدیل لیست برای نمایش در ویو
        $this->topBrands = $topCars->map(function ($contract) {

            return [
                'brand' => $contract->car->carModel->fullName(), // فرض اینکه فیلد brand در مدل Car وجود دارد
                'total' => $contract->total
            ];
        });


        $this->reservedCars = \App\Models\Contract::with(['car.carModel'])
            ->where('current_status', 'reserved')
            ->latest()
            ->get();
    }


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
