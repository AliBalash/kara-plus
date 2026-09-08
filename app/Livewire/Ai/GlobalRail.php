<?php

namespace App\Livewire\Ai;

use Livewire\Component;

class GlobalRail extends Component
{
    public bool $open = false;

    public string $contextTitle = 'Kara AI';

    public string $contextDescription = 'Open a supported workspace to see a page-aware insight.';

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
                $this->contextTitle = 'Contract workspace';
                $this->contextDescription = 'Page-aware brief for this contract — customer history, lifecycle, vehicle, documents and payments from the live database.';
                $this->presets = [[
                    'feature' => 'contract_brief',
                    'entity_id' => $cid,
                    'label' => 'Contract 360 · #' . $cid,
                ]];
                return;
            }
        }

        // 2) Payment workspace — صف پرداخت
        if (in_array($routeName, ['rental-requests.confirm-payment-list', 'rental-requests.payment.list', 'rental-requests.processed-payments', 'cashier.dashboard', 'payments.edit'], true) || $paymentId) {
            $this->contextTitle = 'Payment workspace';
            $this->contextDescription = 'Live payment queue — pending batches ranked by age and amount, directly from the database.';
            $this->presets = [[
                'feature' => 'payment_queue',
                'entity_id' => null,
                'label' => 'Payment priorities',
            ]];
            return;
        }

        // 3) Dashboard — ترکیبی از عملیات امروز + تغییرات همکاران
        if ($routeName === 'expert.dashboard') {
            $this->contextTitle = 'Operations workspace';
            $this->contextDescription = 'Today’s priorities and only business changes since your last login — no technical noise.';
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
                $this->contextTitle = 'Leads workspace';
                $this->contextDescription = 'Daily lead insights and recent business changes — from verified panel data.';
            } elseif (str_starts_with($routeName, 'car.')) {
                $this->contextTitle = 'Fleet workspace';
                $this->contextDescription = 'Fleet health and today’s operations — availability and attention items from the database.';
            } elseif (str_starts_with($routeName, 'customer.')) {
                $this->contextTitle = 'Customer workspace';
                $this->contextDescription = 'Customer operations and recent team activity — verified records only.';
            } else {
                $this->contextTitle = 'Expert workspace';
                $this->contextDescription = 'Page-aware operational brief — today’s priorities from the live database.';
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
        $this->contextTitle = 'Kara AI';
        $this->contextDescription = 'Open a supported workspace to see a page-aware insight, or check today’s operations.';
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
