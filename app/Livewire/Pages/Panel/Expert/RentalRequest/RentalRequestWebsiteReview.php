<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Livewire\Concerns\SearchesCustomerPhone;
use App\Models\Contract;
use App\Services\Reservations\ReviewReservationApprovalService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * A deliberately separate work queue for public website requests.  Requests
 * remain here until an expert resolves every live issue and explicitly turns
 * them into a reserving contract.
 */
class RentalRequestWebsiteReview extends Component
{
    use InteractsWithToasts;
    use SearchesCustomerPhone;
    use WithPagination;

    public string $search = '';
    public string $searchInput = '';
    public string $assignmentFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'assignmentFilter' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function updatedAssignmentFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'assignmentFilter']);
        $this->searchInput = '';
        $this->resetPage();
    }

    public function claim(int $contractId): void
    {
        try {
            app(ReviewReservationApprovalService::class)->claim($contractId, (int) auth()->id());
            $this->toast('success', 'This website request is now assigned to you for review.');
        } catch (ValidationException $exception) {
            $this->toast('error', $this->validationMessage($exception), false);
        } catch (Throwable $exception) {
            Log::error('Unable to claim website reservation request.', [
                'contract_id' => $contractId,
                'exception' => $exception,
            ]);
            $this->toast('error', 'The request could not be assigned. Please try again.', false);
        }
    }

    public function approveAndOpen(int $contractId)
    {
        try {
            $contract = app(ReviewReservationApprovalService::class)->approve($contractId, (int) auth()->id());
            session()->flash('success', 'Website request approved and assigned to you.');

            return redirect()->route('rental-requests.edit', $contract->id);
        } catch (ValidationException $exception) {
            $this->toast('error', $this->validationMessage($exception), false);
        } catch (Throwable $exception) {
            Log::error('Unable to approve website reservation request from review queue.', [
                'contract_id' => $contractId,
                'exception' => $exception,
            ]);
            $this->toast('error', 'The request could not be approved. Please review it again.', false);
        }

        return null;
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';
        $isPhoneSearch = $this->isCustomerPhoneSearch($search);
        $expertId = (int) auth()->id();

        $queue = Contract::query()
            ->with(['customer', 'car.carModel', 'requestedCar.carModel', 'user'])
            ->where('current_status', Contract::STATUS_REVIEW_PENDING)
            ->where('intake_source', Contract::INTAKE_SOURCE_WEBSITE)
            ->when($search !== '', function ($query) use ($likeSearch, $isPhoneSearch): void {
                $query->where(function ($scopedQuery) use ($likeSearch, $isPhoneSearch): void {
                    $scopedQuery
                        ->whereReferenceLike($likeSearch)
                        ->orWhereHas('customer', function ($customerQuery) use ($likeSearch, $isPhoneSearch): void {
                            $customerQuery
                                ->where('first_name', 'like', $likeSearch)
                                ->orWhere('last_name', 'like', $likeSearch)
                                ->orWhere('email', 'like', $likeSearch);

                            if ($isPhoneSearch) {
                                $customerQuery->orWhere('phone', 'like', $likeSearch);
                            }
                        })
                        ->orWhereHas('car', function ($carQuery) use ($likeSearch): void {
                            $carQuery
                                ->where('plate_number', 'like', $likeSearch)
                                ->orWhereHas('carModel', function ($modelQuery) use ($likeSearch): void {
                                    $modelQuery->where('brand', 'like', $likeSearch)
                                        ->orWhere('model', 'like', $likeSearch);
                                });
                        });
                });
            })
            ->when($this->assignmentFilter === 'mine', fn ($query) => $query->where('user_id', $expertId))
            ->when($this->assignmentFilter === 'unassigned', fn ($query) => $query->whereNull('user_id'))
            ->when($this->assignmentFilter === 'others', fn ($query) => $query->whereNotNull('user_id')->where('user_id', '!=', $expertId))
            ->orderByDesc('created_at');

        $requests = $queue->paginate(12);
        $reviewService = app(ReviewReservationApprovalService::class);
        $diagnosticsById = $requests->getCollection()
            ->mapWithKeys(fn (Contract $contract): array => [$contract->id => $reviewService->diagnostics($contract)])
            ->all();

        $summaryBase = Contract::query()
            ->where('current_status', Contract::STATUS_REVIEW_PENDING)
            ->where('intake_source', Contract::INTAKE_SOURCE_WEBSITE);

        return view('livewire.pages.panel.expert.rental-request.rental-request-website-review', [
            'requests' => $requests,
            'diagnosticsById' => $diagnosticsById,
            'summary' => [
                'total' => (clone $summaryBase)->count(),
                'unassigned' => (clone $summaryBase)->whereNull('user_id')->count(),
                'mine' => (clone $summaryBase)->where('user_id', $expertId)->count(),
            ],
            'expertId' => $expertId,
        ]);
    }

    private function validationMessage(ValidationException $exception): string
    {
        return collect($exception->errors())->flatten()->first() ?? 'The request could not be updated.';
    }
}
