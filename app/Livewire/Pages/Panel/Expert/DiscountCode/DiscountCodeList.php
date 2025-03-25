<?php

namespace App\Livewire\Pages\Panel\Expert\DiscountCode;

use App\Models\DiscountCode;
use Livewire\Component;
use Livewire\WithPagination;

class DiscountCodeList extends Component
{
    use WithPagination;

    public $search = ''; // Search input

    // Query string for search input
    protected $queryString = ['search'];

    public function render()
    {
        // Fetching active discount codes that have a registery_at timestamp
        $discountCodes = DiscountCode::whereNotNull('registery_at') // Only those with 'registery_at'
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->orderBy('registery_at', 'desc') // Ordering by registration date
            ->paginate(10); // Paginated results
        return view('livewire.pages.panel.expert.discount-code.discount-code-list', compact('discountCodes'));
    }


    public function markAsContacted($id)
    {
        $discountCode = DiscountCode::find($id);
        if ($discountCode) {
            $discountCode->contacted = true;
            $discountCode->save();
            session()->flash('success', "Discount code marked as contacted.");
        }
    }

    public function markAsNotContacted($id)
    {
        $discountCode = DiscountCode::find($id);
        if ($discountCode) {
            $discountCode->contacted = false;
            $discountCode->save();
            session()->flash('success', "Discount code marked as not contacted.");
        }
    }


    // Method to perform action on a discount code (e.g., calling them)
    public function callDiscountCode($id)
    {
        // Action for calling the discount code
        session()->flash('success', "Calling discount code with ID: $id"); // You can replace this with the actual call logic
    }
}
