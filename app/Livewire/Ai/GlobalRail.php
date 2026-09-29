<?php

namespace App\Livewire\Ai;

use App\Models\Contract;
use Livewire\Component;

class GlobalRail extends Component
{
    public bool $open = false;

    public string $contextTitle = 'Overview';

    /** @var array<int, array{feature: string, entity_id: int|null, label: string}> */
    public array $presets = [];

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        $route = request()->route();
        $routeName = $route?->getName() ?? '';
        $contractId = $route?->parameter('contractId');
        $paymentId = $route?->parameter('paymentId');
        $customerId = $route?->parameter('customerId');
        $carId = $route?->parameter('carId');

        // 1) Contract pages — always page-aware, جمع‌آوری دیتای قرارداد از DB و تحلیل AI
        $contractRoutes = [
            'rental-requests.edit', 'rental-requests.details', 'rental-requests.history',
            'rental-requests.extend', 'rental-requests.payment', 'rental-requests.pickup-document',
            'rental-requests.return-document', 'rental-requests.agreement-inspection',
            'rental-requests.tars-approval', 'rental-requests.kardo-approval', 'rental-requests.balance-transfer',
            'rental-requests.inspection',
        ];
        if ((is_numeric($contractId) && $contractId) || in_array($routeName, $contractRoutes, true)) {
            // fallback: try to extract contractId from any param
            $cid = is_numeric($contractId) ? (int) $contractId : null;
            if ($cid) {
                $this->contextTitle = 'Contract #'.$cid;
                $this->presets = [[
                    'feature' => 'contract_brief',
                    'entity_id' => $cid,
                    'label' => 'Contract 360 · #'.$cid,
                ]];
                if ($routeName === 'rental-requests.edit' && ! auth()->user()?->hasRole('driver') && Contract::whereKey($cid)->where('current_status', Contract::STATUS_REVIEW_PENDING)->where('intake_source', Contract::INTAKE_SOURCE_WEBSITE)->exists()) {
                    array_unshift($this->presets, ['feature' => 'reservation_triage', 'entity_id' => $cid, 'label' => 'Website request review · #'.$cid]);
                }
                if (in_array($routeName, ['rental-requests.payment', 'rental-requests.balance-transfer'], true)) {
                    array_unshift($this->presets, ['feature' => 'contract_finance', 'entity_id' => $cid, 'label' => 'Contract ledger · #'.$cid]);
                }

                return;
            }
        }

        if (is_numeric($customerId) && in_array($routeName, ['customer.detail', 'customer.history', 'customer.debt'], true)) {
            $this->contextTitle = 'Customer #'.(int) $customerId;
            $this->presets = [[
                'feature' => 'customer_brief',
                'entity_id' => (int) $customerId,
                'label' => 'Customer 360 · #'.(int) $customerId,
            ]];

            return;
        }

        if (is_numeric($carId) && in_array($routeName, ['car.detail', 'car.edit'], true)) {
            $this->contextTitle = 'Vehicle #'.(int) $carId;
            $this->presets = [[
                'feature' => 'vehicle_brief',
                'entity_id' => (int) $carId,
                'label' => 'Vehicle 360 · #'.(int) $carId,
            ]];

            return;
        }

        // 2) Payment workspace — صف پرداخت
        if (in_array($routeName, ['rental-requests.confirm-payment-list', 'rental-requests.payment.list', 'rental-requests.processed-payments', 'cashier.dashboard', 'payments.edit'], true) || $paymentId) {
            $this->contextTitle = 'Payments';
            $this->presets = [[
                'feature' => 'payment_queue',
                'entity_id' => null,
                'label' => 'Payment priorities',
            ]];

            return;
        }

        if ($routeName === 'rental-requests.website-review') {
            $this->contextTitle = 'Website requests';
            $this->presets = [['feature' => 'reservation_queue', 'entity_id' => null, 'label' => 'Website review priorities']];

            return;
        }

        if ($routeName === 'car.list') {
            $this->contextTitle = 'Fleet';
            $this->presets = [['feature' => 'fleet_outlook', 'entity_id' => null, 'label' => 'Fleet next 7 days']];

            return;
        }

        // 3) Dashboard — ترکیبی از عملیات امروز + تغییرات همکاران
        if ($routeName === 'expert.dashboard') {
            $this->contextTitle = 'Today';
            $this->presets = [
                ['feature' => 'dashboard_operations', 'entity_id' => null, 'label' => 'Today’s operations'],
                ['feature' => 'changes_since_login', 'entity_id' => null, 'label' => 'What changed since you were away'],
            ];

            return;
        }

        // 4) Generic expert pages — keep the copilot useful everywhere (کاربر خواست دکمه کشویی همه‌جا کار کند)
        // Leads, customers, cars, reports, agents, etc → show dashboard_operations as the most relevant daily assistant
        if (str_starts_with($routeName, 'expert.') || str_starts_with($routeName, 'rental-requests.') || str_starts_with($routeName, 'reports.') || str_starts_with($routeName, 'leads.') || str_starts_with($routeName, 'customer.') || str_starts_with($routeName, 'car.')) {
            // Tailor title per section but keep data source verified
            if (str_starts_with($routeName, 'leads.')) {
                $this->contextTitle = 'Leads';
            } elseif (str_starts_with($routeName, 'car.')) {
                $this->contextTitle = 'Fleet';
            } elseif (str_starts_with($routeName, 'customer.')) {
                $this->contextTitle = 'Customers';
            } else {
                $this->contextTitle = 'Today';
            }

            // For generic pages show a single most-useful insight to avoid noise
            if (in_array($routeName, ['rental-requests.list', 'rental-requests.reserved', 'rental-requests.awaiting.pickup', 'rental-requests.awaiting.return'], true)) {
                $this->presets = [
                    ['feature' => 'dashboard_operations', 'entity_id' => null, 'label' => 'Today’s operations'],
                ];
            } else {
                $this->presets = [
                    ['feature' => 'dashboard_operations', 'entity_id' => null, 'label' => 'Today’s operations'],
                    ['feature' => 'changes_since_login', 'entity_id' => null, 'label' => 'Team changes'],
                ];
            }

            return;
        }

        // 5) Fallback — never show empty, keep copilot ready
        $this->contextTitle = 'Today';
        $this->presets = [
            ['feature' => 'dashboard_operations', 'entity_id' => null, 'label' => 'Today’s operations'],
        ];
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
