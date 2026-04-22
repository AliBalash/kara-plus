<?php

namespace App\Livewire\Pages\Panel\Expert\DiscountCode;

use App\Models\DiscountCode;
use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class DiscountCodeList extends Component
{
    use WithPagination;
    use InteractsWithToasts;

    protected static ?bool $discountCodesTableExists = null;

    public $search = '';
    public $searchInput = '';

    protected $queryString = ['search'];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        if (! $this->hasDiscountCodesTable()) {
            $discountCodes = new LengthAwarePaginator(
                collect(),
                0,
                10,
                Paginator::resolveCurrentPage(),
                ['path' => Paginator::resolveCurrentPath()]
            );

            return view('livewire.pages.panel.expert.discount-code.discount-code-list', compact('discountCodes'));
        }

        $discountCodes = DiscountCode::whereNotNull('registery_at')
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where(function ($scoped) use ($likeSearch) {
                    $scoped->where('name', 'like', $likeSearch)
                        ->orWhere('phone', 'like', $likeSearch);
                });
            })
            ->orderBy('registery_at', 'desc') // Ordering by registration date
            ->paginate(10); // Paginated results
        return view('livewire.pages.panel.expert.discount-code.discount-code-list', compact('discountCodes'));
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }


    public function markAsContacted($id)
    {
        if (! $this->hasDiscountCodesTable()) {
            $this->toast('error', 'Discount code storage is not available.');
            return;
        }

        $discountCode = DiscountCode::find($id);
        if ($discountCode) {
            $discountCode->contacted = true;
            $discountCode->save();
            $this->toast('success', 'Discount code marked as contacted.');
        }
    }

    public function markAsNotContacted($id)
    {
        if (! $this->hasDiscountCodesTable()) {
            $this->toast('error', 'Discount code storage is not available.');
            return;
        }

        $discountCode = DiscountCode::find($id);
        if ($discountCode) {
            $discountCode->contacted = false;
            $discountCode->save();
            $this->toast('success', 'Discount code marked as not contacted.');
        }
    }


    // Method to perform action on a discount code (e.g., calling them)
    public function callDiscountCode($id)
    {
        // Action for calling the discount code
        $this->toast('info', "Calling discount code with ID: $id");
    }

    protected function hasDiscountCodesTable(): bool
    {
        if (self::$discountCodesTableExists !== null) {
            return self::$discountCodesTableExists;
        }

        self::$discountCodesTableExists = Schema::hasTable((new DiscountCode())->getTable());

        return self::$discountCodesTableExists;
    }
}
