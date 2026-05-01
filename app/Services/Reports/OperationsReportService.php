<?php

namespace App\Services\Reports;

use App\Models\Car;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Support\ContractStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OperationsReportService
{
    private const CUSTOMER_REQUEST_DATE_FIELDS = ['created_at', 'pickup_date', 'return_date'];

    private const BALANCE_DATE_FIELDS = ['created_at', 'pickup_date', 'return_date'];

    private const PAYMENT_DATE_FIELDS = ['payment_date', 'created_at'];

    private const EXTRA_PAYMENT_TYPES = [
        'fine',
        'parking',
        'damage',
        'salik',
        'salik_4_aed',
        'salik_6_aed',
        'salik_other_revenue',
        'carwash',
        'fuel',
        'no_deposit_fee',
    ];

    public function customerRequests(array $filters = []): array
    {
        $filters = $this->normalizeCustomerRequestFilters($filters);

        $contracts = Contract::query()
            ->with([
                'customer',
                'car.carModel',
                'payments',
                'charges',
                'pickupDocument.user',
                'pickupDocument.tarsApprover',
                'pickupDocument.kardoApprover',
                'returnDocument.user',
                'user',
                'agent',
                'deliveryDriver',
                'returnDriver',
                'statuses.user',
            ])
            ->tap(fn (Builder $query) => $this->applyContractSearch($query, $filters['search']))
            ->when(
                $filters['status'] !== 'all',
                fn (Builder $query) => $query->where('current_status', $filters['status'])
            )
            ->when(
                $filters['agent_id'] !== null,
                fn (Builder $query) => $query->where('agent_id', $filters['agent_id'])
            )
            ->when(
                $filters['kardo'] === 'required',
                fn (Builder $query) => $query->where('kardo_required', true)
            )
            ->when(
                $filters['kardo'] === 'not_required',
                fn (Builder $query) => $query->where('kardo_required', false)
            )
            ->tap(fn (Builder $query) => $this->applyDateRange(
                $query,
                $filters['date_field'],
                $filters['date_from'],
                $filters['date_to']
            ))
            ->get()
            ->sortByDesc(fn (Contract $contract) => $this->timestampValue($contract->{$filters['date_field']} ?? $contract->created_at))
            ->values();

        $rows = $contracts->map(fn (Contract $contract) => $this->mapCustomerRequestRow($contract))->values();

        $statusBreakdown = $rows
            ->countBy('status_label')
            ->sortDesc();

        $summary = [
            'matching_contracts' => $rows->count(),
            'unique_customers' => $rows->pluck('customer_id')->filter()->unique()->count(),
            'gross_contract_value' => round((float) $rows->sum('total_price'), 2),
            'recorded_payments' => round((float) $rows->sum('net_payments'), 2),
            'outstanding_balance' => round((float) $rows->sum('remaining_balance_positive'), 2),
            'average_rental_days' => round((float) $rows->avg('duration_days'), 1),
            'pickup_docs_completed' => $rows->where('pickup_document_completed', 'Yes')->count(),
            'return_docs_completed' => $rows->where('return_document_completed', 'Yes')->count(),
        ];

        return [
            'filters' => $filters,
            'filter_summary' => [
                'Customer Search' => $filters['search'] !== '' ? $filters['search'] : 'All customers',
                'Date Basis' => Str::headline(str_replace('_', ' ', $filters['date_field'])),
                'Date From' => $filters['date_from'] ?? 'Open',
                'Date To' => $filters['date_to'] ?? 'Open',
                'Status' => $filters['status'] === 'all' ? 'All statuses' : ContractStatus::label($filters['status']),
                'KARDO' => match ($filters['kardo']) {
                    'required' => 'Required only',
                    'not_required' => 'Not required only',
                    default => 'All contracts',
                },
            ],
            'summary' => $summary,
            'summary_sections' => [
                'Snapshot' => [
                    'Matching Contracts' => $summary['matching_contracts'],
                    'Unique Customers' => $summary['unique_customers'],
                    'Gross Contract Value (AED)' => $summary['gross_contract_value'],
                    'Recorded Payments (AED)' => $summary['recorded_payments'],
                    'Outstanding Balance (AED)' => $summary['outstanding_balance'],
                    'Average Rental Days' => $summary['average_rental_days'],
                    'Pickup Documents Completed' => $summary['pickup_docs_completed'],
                    'Return Documents Completed' => $summary['return_docs_completed'],
                ],
                'Status Mix' => $statusBreakdown->all(),
            ],
            'rows' => $rows,
            'export_headings' => [
                'Contract ID',
                'Request Date',
                'Pickup Date',
                'Return Date',
                'Rental Days',
                'Status',
                'Customer',
                'Phone',
                'Nationality',
                'Passport',
                'License',
                'Car',
                'Plate',
                'Fleet',
                'Pickup Location',
                'Return Location',
                'Submitted By',
                'Sales Agent',
                'Assigned Expert',
                'Delivery Driver',
                'Return Driver',
                'Pickup Recorded By',
                'Return Recorded By',
                'Licensed Driver',
                'Agreement Number',
                'TARS Approved At',
                'TARS Approved By',
                'KARDO Approved At',
                'KARDO Approved By',
                'Pickup Fuel Level',
                'Pickup Mileage',
                'Return Fuel Level',
                'Return Mileage',
                'Pickup Document Completed',
                'Return Document Completed',
                'Operational Timeline',
                'Total Contract AED',
                'Recorded Payments AED',
                'Security Deposit Paid AED',
                'Discounts AED',
                'Refunds AED',
                'Extras Paid AED',
                'Outstanding AED',
                'Security Hold',
                'Payment On Delivery',
                'KARDO',
                'Insurance',
                'Driver Service Hours',
                'Driver Service Cost AED',
                'Driving License Option',
                'Driving License Cost AED',
                'Charge Total AED',
                'Charge Breakdown',
                'Payment Breakdown',
                'Security Deposit Note',
                'Notes',
            ],
            'export_rows' => $rows->map(function (array $row): array {
                return [
                    $row['contract_id'],
                    $row['request_date'],
                    $row['pickup_date'],
                    $row['return_date'],
                    $row['duration_days'],
                    $row['status_label'],
                    $row['customer_name'],
                    $row['customer_phone'],
                    $row['customer_nationality'],
                    $row['passport_number'],
                    $row['license_number'],
                    $row['car_name'],
                    $row['plate_number'],
                    $row['ownership'],
                    $row['pickup_location'],
                    $row['return_location'],
                    $row['submitted_by'],
                    $row['sales_agent'],
                    $row['assigned_expert'],
                    $row['delivery_driver'],
                    $row['return_driver'],
                    $row['pickup_recorded_by'],
                    $row['return_recorded_by'],
                    $row['licensed_driver_name'],
                    $row['agreement_number'],
                    $row['tars_approved_at'],
                    $row['tars_approved_by'],
                    $row['kardo_approved_at'],
                    $row['kardo_approved_by'],
                    $row['pickup_fuel_level'],
                    $row['pickup_mileage'],
                    $row['return_fuel_level'],
                    $row['return_mileage'],
                    $row['pickup_document_completed'],
                    $row['return_document_completed'],
                    $row['status_timeline'],
                    $row['total_price'],
                    $row['net_payments'],
                    $row['security_deposit_paid'],
                    $row['discounts'],
                    $row['refunds'],
                    $row['extras_paid'],
                    $row['remaining_balance'],
                    $row['deposit_hold'],
                    $row['payment_on_delivery'],
                    $row['kardo_required'],
                    $row['selected_insurance'],
                    $row['driver_service_hours'],
                    $row['driver_service_cost'],
                    $row['driving_license_option'],
                    $row['driving_license_cost'],
                    $row['charge_total'],
                    $row['charge_breakdown'],
                    $row['payment_breakdown'],
                    $row['security_deposit_note'],
                    $row['notes'],
                ];
            })->all(),
            'extra_sheets' => [
                [
                    'title' => 'Status Timeline',
                    'headings' => [
                        'Contract ID',
                        'Customer',
                        'Vehicle',
                        'Status',
                        'Changed At',
                        'Changed By',
                        'Notes',
                    ],
                    'rows' => $this->buildCustomerRequestTimelineRows($contracts),
                    'accentColor' => '1D4ED8',
                ],
                [
                    'title' => 'Payment Ledger',
                    'headings' => [
                        'Contract ID',
                        'Customer',
                        'Vehicle',
                        'Payment Date',
                        'Type',
                        'Method',
                        'Currency',
                        'Amount',
                        'Amount AED',
                        'Approval',
                        'Paid',
                        'Refundable',
                        'Description',
                        'Note',
                    ],
                    'rows' => $this->buildCustomerRequestPaymentRows($contracts),
                    'accentColor' => '7C3AED',
                ],
                [
                    'title' => 'Charge Breakdown',
                    'headings' => [
                        'Contract ID',
                        'Customer',
                        'Vehicle',
                        'Charge Title',
                        'Charge Type',
                        'Amount AED',
                        'Description',
                    ],
                    'rows' => $this->buildCustomerRequestChargeRows($contracts),
                    'accentColor' => 'B45309',
                ],
            ],
        ];
    }

    public function customerBalances(array $filters = []): array
    {
        $filters = $this->normalizeCustomerBalanceFilters($filters);

        $customers = Customer::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $like = $this->likeValue($filters['search']);
                $isPhone = $this->looksLikePhone($filters['search']);

                $query->where(function (Builder $customerQuery) use ($like, $isPhone) {
                    $customerQuery->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('passport_number', 'like', $like)
                        ->orWhere('license_number', 'like', $like);

                    if ($isPhone) {
                        $customerQuery->orWhere('phone', 'like', $like)
                            ->orWhere('messenger_phone', 'like', $like);
                    }
                });
            })
            ->whereHas('contracts', function (Builder $query) use ($filters) {
                $query->includedInCustomerBalance();

                $this->applyDateRange(
                    $query,
                    $filters['date_field'],
                    $filters['date_from'],
                    $filters['date_to']
                );
            })
            ->with([
                'contracts' => function ($query) use ($filters) {
                    $query->includedInCustomerBalance();

                    $this->applyDateRange(
                        $query,
                        $filters['date_field'],
                        $filters['date_from'],
                        $filters['date_to']
                    );

                    $query->with(['payments', 'car.carModel']);
                },
            ])
            ->get();

        $rows = $customers
            ->map(fn (Customer $customer) => $this->mapCustomerBalanceRow($customer))
            ->filter(function (array $row) use ($filters) {
                return match ($filters['balance_status']) {
                    'open' => $row['status'] === 'open',
                    'overdue' => $row['status'] === 'overdue',
                    'settled' => $row['status'] === 'settled',
                    'credit' => $row['status'] === 'credit',
                    default => true,
                };
            })
            ->sortByDesc(fn (array $row) => [$row['outstanding_balance'], $row['latest_contract_timestamp']])
            ->values();

        $portfolioBreakdown = $rows->countBy('status_label')->sortDesc();

        $summary = [
            'matching_customers' => $rows->count(),
            'customers_with_open_balance' => $rows->whereIn('status', ['open', 'overdue'])->count(),
            'overdue_customers' => $rows->where('status', 'overdue')->count(),
            'total_outstanding' => round((float) $rows->sum('outstanding_balance'), 2),
            'customer_credit' => round((float) $rows->sum('credit_balance'), 2),
            'gross_contract_value' => round((float) $rows->sum('gross_contract_value'), 2),
        ];

        return [
            'filters' => $filters,
            'filter_summary' => [
                'Customer Search' => $filters['search'] !== '' ? $filters['search'] : 'All customers',
                'Date Basis' => Str::headline(str_replace('_', ' ', $filters['date_field'])),
                'Date From' => $filters['date_from'] ?? 'Open',
                'Date To' => $filters['date_to'] ?? 'Open',
                'Balance Status' => match ($filters['balance_status']) {
                    'open' => 'Open balance',
                    'overdue' => 'Overdue',
                    'settled' => 'Settled',
                    'credit' => 'Credit',
                    default => 'All balances',
                },
            ],
            'summary' => $summary,
            'summary_sections' => [
                'Portfolio' => [
                    'Matching Customers' => $summary['matching_customers'],
                    'Customers With Open Balance' => $summary['customers_with_open_balance'],
                    'Overdue Customers' => $summary['overdue_customers'],
                    'Total Outstanding (AED)' => $summary['total_outstanding'],
                    'Customer Credit (AED)' => $summary['customer_credit'],
                    'Gross Contract Value (AED)' => $summary['gross_contract_value'],
                ],
                'Balance Mix' => $portfolioBreakdown->all(),
            ],
            'rows' => $rows,
            'export_headings' => [
                'Customer ID',
                'Customer',
                'Phone',
                'Nationality',
                'Contracts',
                'Active Contracts',
                'Gross Contract Value AED',
                'Recorded Payments AED',
                'Outstanding AED',
                'Overdue AED',
                'Credit AED',
                'Deposits AED',
                'Extras AED',
                'Latest Contract Date',
                'Latest Contract Status',
                'Top Car',
                'Open Contract IDs',
                'Balance Status',
            ],
            'export_rows' => $rows->map(function (array $row): array {
                return [
                    $row['customer_id'],
                    $row['customer_name'],
                    $row['phone'],
                    $row['nationality'],
                    $row['contracts_count'],
                    $row['active_contracts'],
                    $row['gross_contract_value'],
                    $row['recorded_payments'],
                    $row['outstanding_balance'],
                    $row['overdue_balance'],
                    $row['credit_balance'],
                    $row['deposits_paid'],
                    $row['extras_paid'],
                    $row['latest_contract_date'],
                    $row['latest_contract_status'],
                    $row['top_car'],
                    $row['open_contract_ids'],
                    $row['status_label'],
                ];
            })->all(),
        ];
    }

    public function fleetPerformance(array $filters = []): array
    {
        $filters = $this->normalizeFleetFilters($filters);

        $cars = Car::query()
            ->with([
                'carModel',
                'currentContract.customer',
                'contracts' => function ($query) use ($filters) {
                    $query->with(['customer', 'payments'])
                        ->whereNotIn('current_status', ['cancelled', 'rejected']);

                    $this->applyContractWindow($query, $filters['date_from'], $filters['date_to']);
                },
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $like = $this->likeValue($filters['search']);

                $query->where(function (Builder $carQuery) use ($like) {
                    $carQuery->where('plate_number', 'like', $like)
                        ->orWhereHas('carModel', function (Builder $modelQuery) use ($like) {
                            $modelQuery->where('brand', 'like', $like)
                                ->orWhere('model', 'like', $like);
                        });
                });
            })
            ->when(
                $filters['ownership'] !== 'all',
                fn (Builder $query) => $query->where('ownership_type', $filters['ownership'])
            )
            ->whereHas('contracts', function (Builder $query) use ($filters) {
                $query->whereNotIn('current_status', ['cancelled', 'rejected']);
                $this->applyContractWindow($query, $filters['date_from'], $filters['date_to']);
            })
            ->get();

        $windowDays = $this->windowDays($filters['date_from'], $filters['date_to']);

        $rows = $cars
            ->map(fn (Car $car) => $this->mapFleetPerformanceRow($car, $filters['date_from'], $filters['date_to'], $windowDays))
            ->sortByDesc(fn (array $row) => [$row['revenue'], $row['booked_days'], $row['contracts_count']])
            ->values();

        $ownershipBreakdown = $rows->countBy('ownership')->sortDesc();

        $summary = [
            'cars_in_report' => $rows->count(),
            'contract_count' => (int) $rows->sum('contracts_count'),
            'unique_customers' => (int) $rows->sum('unique_customers'),
            'revenue' => round((float) $rows->sum('revenue'), 2),
            'booked_days' => round((float) $rows->sum('booked_days'), 1),
            'average_utilization' => round((float) $rows->avg('utilization_pct'), 1),
        ];

        return [
            'filters' => $filters,
            'filter_summary' => [
                'Vehicle Search' => $filters['search'] !== '' ? $filters['search'] : 'Entire fleet',
                'Date From' => $filters['date_from'] ?? 'Open',
                'Date To' => $filters['date_to'] ?? 'Open',
                'Fleet Scope' => $filters['ownership'] === 'all' ? 'All fleets' : Str::headline(str_replace('_', ' ', $filters['ownership'])),
            ],
            'summary' => $summary,
            'summary_sections' => [
                'Fleet Snapshot' => [
                    'Cars In Report' => $summary['cars_in_report'],
                    'Contract Count' => $summary['contract_count'],
                    'Unique Customers' => $summary['unique_customers'],
                    'Revenue (AED)' => $summary['revenue'],
                    'Booked Days' => $summary['booked_days'],
                    'Average Utilization (%)' => $summary['average_utilization'],
                ],
                'Ownership Mix' => $ownershipBreakdown->all(),
            ],
            'rows' => $rows,
            'export_headings' => [
                'Car ID',
                'Vehicle',
                'Plate',
                'Fleet',
                'Availability',
                'Contracts',
                'Unique Customers',
                'Revenue AED',
                'Recorded Payments AED',
                'Booked Days',
                'Utilization %',
                'Average Contract AED',
                'Average Daily Revenue AED',
                'Last Pickup',
                'Last Return',
                'Current Customer',
            ],
            'export_rows' => $rows->map(function (array $row): array {
                return [
                    $row['car_id'],
                    $row['car_name'],
                    $row['plate_number'],
                    $row['ownership'],
                    $row['availability'],
                    $row['contracts_count'],
                    $row['unique_customers'],
                    $row['revenue'],
                    $row['recorded_payments'],
                    $row['booked_days'],
                    $row['utilization_pct'],
                    $row['average_contract_value'],
                    $row['average_daily_revenue'],
                    $row['last_pickup_date'],
                    $row['last_return_date'],
                    $row['current_customer'],
                ];
            })->all(),
        ];
    }

    public function paymentCollections(array $filters = []): array
    {
        $filters = $this->normalizePaymentFilters($filters);

        $payments = Payment::query()
            ->with(['customer', 'contract.customer', 'contract.car.carModel', 'car.carModel', 'user'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $like = $this->likeValue($filters['search']);
                $isPhone = $this->looksLikePhone($filters['search']);

                $query->where(function (Builder $paymentQuery) use ($like, $isPhone) {
                    $paymentQuery->where('description', 'like', $like)
                        ->orWhere('note', 'like', $like)
                        ->orWhereHas('contract', fn (Builder $contractQuery) => $contractQuery->where('contracts.id', 'like', $like))
                        ->orWhereHas('car', function (Builder $carQuery) use ($like) {
                            $carQuery->where('plate_number', 'like', $like)
                                ->orWhereHas('carModel', function (Builder $modelQuery) use ($like) {
                                    $modelQuery->where('brand', 'like', $like)
                                        ->orWhere('model', 'like', $like);
                                });
                        })
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($like, $isPhone) {
                            $customerQuery->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('passport_number', 'like', $like);

                            if ($isPhone) {
                                $customerQuery->orWhere('phone', 'like', $like);
                            }
                        });
                });
            })
            ->when(
                $filters['payment_type'] !== 'all',
                fn (Builder $query) => $query->where('payment_type', $filters['payment_type'])
            )
            ->when(
                $filters['approval_status'] !== 'all',
                fn (Builder $query) => $query->where('approval_status', $filters['approval_status'])
            )
            ->when(
                $filters['payment_state'] === 'paid',
                fn (Builder $query) => $query->where('is_paid', true)
            )
            ->when(
                $filters['payment_state'] === 'unpaid',
                fn (Builder $query) => $query->where('is_paid', false)
            )
            ->when(
                $filters['payment_method'] !== 'all',
                fn (Builder $query) => $query->where('payment_method', $filters['payment_method'])
            )
            ->tap(fn (Builder $query) => $this->applyDateRange(
                $query,
                $filters['date_field'],
                $filters['date_from'],
                $filters['date_to']
            ))
            ->get()
            ->sortByDesc(fn (Payment $payment) => $this->timestampValue($payment->{$filters['date_field']} ?? $payment->payment_date))
            ->values();

        $rows = $payments->map(fn (Payment $payment) => $this->mapPaymentRow($payment))->values();

        $typeBreakdown = $rows
            ->groupBy('payment_type_label')
            ->map(fn (Collection $items) => round((float) $items->sum('amount_in_aed'), 2))
            ->sortDesc();

        $summary = [
            'payment_records' => $rows->count(),
            'net_recorded_payments' => round((float) $rows->sum('net_amount_for_summary'), 2),
            'approved_amount' => round((float) $rows->where('approval_status', 'approved')->sum('amount_in_aed'), 2),
            'pending_approval_amount' => round((float) $rows->where('approval_status', 'pending')->sum('amount_in_aed'), 2),
            'unpaid_amount' => round((float) $rows->where('is_paid', false)->sum('amount_in_aed'), 2),
            'refundable_amount' => round((float) $rows->where('is_refundable', true)->sum('amount_in_aed'), 2),
        ];

        return [
            'filters' => $filters,
            'filter_summary' => [
                'Search' => $filters['search'] !== '' ? $filters['search'] : 'All payments',
                'Date Basis' => Str::headline(str_replace('_', ' ', $filters['date_field'])),
                'Date From' => $filters['date_from'] ?? 'Open',
                'Date To' => $filters['date_to'] ?? 'Open',
                'Payment Type' => $filters['payment_type'] === 'all' ? 'All types' : Str::headline(str_replace('_', ' ', $filters['payment_type'])),
                'Approval' => $filters['approval_status'] === 'all' ? 'All approvals' : Str::headline($filters['approval_status']),
                'Settlement' => match ($filters['payment_state']) {
                    'paid' => 'Paid only',
                    'unpaid' => 'Unpaid only',
                    default => 'Paid + unpaid',
                },
            ],
            'summary' => $summary,
            'summary_sections' => [
                'Collection Snapshot' => [
                    'Payment Records' => $summary['payment_records'],
                    'Net Recorded Payments (AED)' => $summary['net_recorded_payments'],
                    'Approved Amount (AED)' => $summary['approved_amount'],
                    'Pending Approval (AED)' => $summary['pending_approval_amount'],
                    'Unpaid Amount (AED)' => $summary['unpaid_amount'],
                    'Refundable Amount (AED)' => $summary['refundable_amount'],
                ],
                'Type Mix (AED)' => $typeBreakdown->all(),
            ],
            'rows' => $rows,
            'export_headings' => [
                'Payment ID',
                'Payment Date',
                'Contract ID',
                'Customer',
                'Phone',
                'Car',
                'Plate',
                'Processed By',
                'Payment Type',
                'Method',
                'Currency',
                'Original Amount',
                'Amount AED',
                'Approval Status',
                'Paid',
                'Refundable',
                'Description',
                'Note',
            ],
            'export_rows' => $rows->map(function (array $row): array {
                return [
                    $row['payment_id'],
                    $row['payment_date'],
                    $row['contract_id'],
                    $row['customer_name'],
                    $row['customer_phone'],
                    $row['car_name'],
                    $row['plate_number'],
                    $row['processed_by'],
                    $row['payment_type_label'],
                    $row['payment_method_label'],
                    $row['currency'],
                    $row['amount'],
                    $row['amount_in_aed'],
                    $row['approval_status_label'],
                    $row['is_paid_label'],
                    $row['is_refundable_label'],
                    $row['description'],
                    $row['note'],
                ];
            })->all(),
        ];
    }

    private function mapCustomerRequestRow(Contract $contract): array
    {
        $payments = $contract->payments ?? collect();
        $charges = $contract->charges ?? collect();

        $rentalPaid = $this->sumPayments($payments, ['rental_fee']);
        $securityDepositPaid = $this->sumPayments($payments, ['security_deposit']);
        $discounts = $this->sumPayments($payments, ['discount']);
        $refunds = $this->sumPayments($payments, ['payment_back']);
        $extrasPaid = $this->sumPayments($payments, self::EXTRA_PAYMENT_TYPES);

        $netPayments = round($rentalPaid + $securityDepositPaid + $extrasPaid - $refunds, 2);
        $remainingBalance = round((float) $contract->calculateRemainingBalance($payments), 2);
        $driverServiceHours = $this->numericMeta($contract->meta, 'driver_hours');
        $driverServiceCost = $this->numericMeta($contract->meta, 'driver_service_cost');
        $drivingLicenseCost = $this->numericMeta($contract->meta, 'driving_license_cost');
        $chargeBreakdown = $charges->map(function ($charge) {
            $label = trim((string) $charge->title);
            $description = trim((string) ($charge->description ?? ''));

            return $description !== ''
                ? sprintf('%s (%s): %.2f', $label, $description, (float) $charge->amount)
                : sprintf('%s: %.2f', $label, (float) $charge->amount);
        })->implode(' | ');

        $insuranceCharge = $charges->first(fn ($charge) => $charge->type === 'insurance');
        $pickupDocument = $contract->pickupDocument;
        $returnDocument = $contract->returnDocument;
        $statusTimeline = $contract->statuses
            ->sortBy('created_at')
            ->map(function ($status) {
                $label = ContractStatus::label($status->status);
                $when = $this->formatDateTime($status->created_at);
                $who = $status->user?->fullName() ?? 'System';

                return "{$label} ({$when} / {$who})";
            })
            ->implode(' | ');

        return [
            'contract_id' => $contract->id,
            'customer_id' => $contract->customer?->id,
            'request_date' => $this->formatDateTime($contract->created_at),
            'pickup_date' => $this->formatDateTime($contract->pickup_date),
            'return_date' => $this->formatDateTime($contract->return_date),
            'duration_days' => $this->durationDays($contract->pickup_date, $contract->return_date),
            'status' => $contract->current_status,
            'status_label' => ContractStatus::label($contract->current_status),
            'customer_name' => $contract->customer?->fullName() ?? '—',
            'customer_phone' => $contract->customer?->phone ?? '—',
            'customer_nationality' => $contract->customer?->nationality ?: '—',
            'passport_number' => $contract->customer?->passport_number ?: '—',
            'license_number' => $contract->customer?->license_number ?: '—',
            'car_name' => $contract->car?->modelName() ?? '—',
            'plate_number' => $contract->car?->plate_number ?? '—',
            'ownership' => $contract->car?->ownershipLabel() ?? '—',
            'pickup_location' => $contract->pickup_location ?: '—',
            'return_location' => $contract->return_location ?: '—',
            'submitted_by' => $contract->submitted_by_name ?: 'Website',
            'sales_agent' => $contract->agent?->name ?? '—',
            'assigned_expert' => $contract->user?->fullName() ?? 'Unassigned',
            'delivery_driver' => $contract->deliveryDriver?->fullName() ?? '—',
            'return_driver' => $contract->returnDriver?->fullName() ?? '—',
            'pickup_recorded_by' => $pickupDocument?->user?->fullName() ?? '—',
            'return_recorded_by' => $returnDocument?->user?->fullName() ?? '—',
            'licensed_driver_name' => $contract->licensed_driver_name ?: '—',
            'agreement_number' => $pickupDocument?->agreement_number ?: '—',
            'tars_approved_at' => $this->formatDateTime($pickupDocument?->tars_approved_at),
            'tars_approved_by' => $pickupDocument?->tarsApprover?->fullName() ?? '—',
            'kardo_approved_at' => $this->formatDateTime($pickupDocument?->kardo_approved_at),
            'kardo_approved_by' => $pickupDocument?->kardoApprover?->fullName() ?? '—',
            'pickup_fuel_level' => $pickupDocument?->fuelLevel ?: '—',
            'pickup_mileage' => $pickupDocument?->mileage ?: '—',
            'return_fuel_level' => $returnDocument?->fuelLevel ?: '—',
            'return_mileage' => $returnDocument?->mileage ?: '—',
            'pickup_document_completed' => $pickupDocument ? 'Yes' : 'No',
            'return_document_completed' => $returnDocument ? 'Yes' : 'No',
            'status_timeline' => $statusTimeline !== '' ? $statusTimeline : '—',
            'total_price' => round((float) ($contract->total_price ?? 0), 2),
            'net_payments' => $netPayments,
            'security_deposit_paid' => $securityDepositPaid,
            'discounts' => $discounts,
            'refunds' => $refunds,
            'extras_paid' => $extrasPaid,
            'remaining_balance' => $remainingBalance,
            'remaining_balance_positive' => max($remainingBalance, 0),
            'deposit_hold' => $this->formatDepositDetails($contract->deposit_category, $contract->deposit),
            'payment_on_delivery' => $contract->payment_on_delivery ? 'Yes' : 'No',
            'kardo_required' => $contract->kardo_required ? 'Required' : 'Not required',
            'selected_insurance' => $insuranceCharge?->title
                ?? $this->humanizeInsurance((string) data_get($contract->meta, 'selected_insurance', ''))
                ?? 'Basic / none',
            'driver_service_hours' => $driverServiceHours,
            'driver_service_cost' => $driverServiceCost,
            'driving_license_option' => $this->humanizeOption((string) data_get($contract->meta, 'driving_license_option', '')),
            'driving_license_cost' => $drivingLicenseCost,
            'charge_total' => round((float) $charges->sum('amount'), 2),
            'charge_breakdown' => $chargeBreakdown !== '' ? $chargeBreakdown : '—',
            'payment_breakdown' => $this->formatPaymentBreakdown(
                $rentalPaid,
                $securityDepositPaid,
                $extrasPaid,
                $discounts,
                $refunds
            ),
            'security_deposit_note' => (string) data_get($contract->meta, 'security_deposit_note', '—') ?: '—',
            'notes' => $this->mergeNotes([
                $contract->notes,
                $pickupDocument?->note ? 'Pickup: ' . $pickupDocument->note : null,
                $pickupDocument?->driver_note ? 'Pickup Driver Note: ' . $pickupDocument->driver_note : null,
                $returnDocument?->note ? 'Return: ' . $returnDocument->note : null,
                $returnDocument?->driver_note ? 'Return Driver Note: ' . $returnDocument->driver_note : null,
            ]),
        ];
    }

    private function buildCustomerRequestTimelineRows(Collection $contracts): array
    {
        return $contracts
            ->flatMap(function (Contract $contract) {
                $vehicle = $contract->car?->fullName() ?? '—';
                $customer = $contract->customer?->fullName() ?? '—';

                return $contract->statuses
                    ->sortBy('created_at')
                    ->map(function ($status) use ($contract, $vehicle, $customer) {
                        return [
                            $contract->id,
                            $customer,
                            $vehicle,
                            ContractStatus::label($status->status),
                            $this->formatDateTime($status->created_at),
                            $status->user?->fullName() ?? 'System',
                            $status->notes ?: '—',
                        ];
                    });
            })
            ->values()
            ->all();
    }

    private function buildCustomerRequestPaymentRows(Collection $contracts): array
    {
        return $contracts
            ->flatMap(function (Contract $contract) {
                $vehicle = $contract->car?->fullName() ?? '—';
                $customer = $contract->customer?->fullName() ?? '—';

                return $contract->payments
                    ->sortBy('payment_date')
                    ->map(function (Payment $payment) use ($contract, $vehicle, $customer) {
                        return [
                            $contract->id,
                            $customer,
                            $vehicle,
                            $this->formatDateTime($payment->payment_date),
                            Str::headline(str_replace('_', ' ', $payment->payment_type)),
                            Str::headline($payment->payment_method),
                            $payment->currency,
                            round((float) $payment->amount, 2),
                            round((float) ($payment->amount_in_aed ?? $payment->amount ?? 0), 2),
                            Str::headline($payment->approval_status),
                            $payment->is_paid ? 'Yes' : 'No',
                            $payment->is_refundable ? 'Yes' : 'No',
                            $payment->description ?: '—',
                            $payment->note ?: '—',
                        ];
                    });
            })
            ->values()
            ->all();
    }

    private function buildCustomerRequestChargeRows(Collection $contracts): array
    {
        return $contracts
            ->flatMap(function (Contract $contract) {
                $vehicle = $contract->car?->fullName() ?? '—';
                $customer = $contract->customer?->fullName() ?? '—';

                return $contract->charges
                    ->map(function ($charge) use ($contract, $vehicle, $customer) {
                        return [
                            $contract->id,
                            $customer,
                            $vehicle,
                            $charge->title,
                            $charge->type ?: '—',
                            round((float) $charge->amount, 2),
                            $charge->description ?: '—',
                        ];
                    });
            })
            ->values()
            ->all();
    }

    private function mapCustomerBalanceRow(Customer $customer): array
    {
        $contracts = $customer->contracts ?? collect();
        $latestContract = $contracts
            ->sortByDesc(fn (Contract $contract) => $this->timestampValue($contract->pickup_date ?? $contract->created_at))
            ->first();

        $metrics = $contracts->map(function (Contract $contract) {
            $payments = $contract->payments ?? collect();
            $remainingBalance = round((float) $contract->calculateRemainingBalance($payments), 2);
            $rentalPaid = $this->sumPayments($payments, ['rental_fee']);
            $depositPaid = $this->sumPayments($payments, ['security_deposit']);
            $extrasPaid = $this->sumPayments($payments, self::EXTRA_PAYMENT_TYPES);
            $refunds = $this->sumPayments($payments, ['payment_back']);

            return [
                'contract_id' => $contract->id,
                'car_name' => $contract->car?->fullName() ?? '—',
                'remaining_balance' => $remainingBalance,
                'outstanding' => max($remainingBalance, 0),
                'credit' => abs(min($remainingBalance, 0)),
                'recorded_payments' => round($rentalPaid + $depositPaid + $extrasPaid - $refunds, 2),
                'deposits_paid' => $depositPaid,
                'extras_paid' => $extrasPaid,
                'is_active' => in_array($contract->current_status, ['pending', 'assigned', 'under_review', 'reserved', 'delivery', 'agreement_inspection', 'awaiting_return', 'payment', 'returned'], true),
                'is_overdue' => max($remainingBalance, 0) > 0
                    && $contract->return_date instanceof Carbon
                    && $contract->return_date->isPast(),
                'pickup_date' => $contract->pickup_date,
            ];
        });

        $outstanding = round((float) $metrics->sum('outstanding'), 2);
        $credit = round((float) $metrics->sum('credit'), 2);
        $overdue = round((float) $metrics->where('is_overdue', true)->sum('outstanding'), 2);

        $status = 'settled';
        if ($overdue > 0) {
            $status = 'overdue';
        } elseif ($outstanding > 0) {
            $status = 'open';
        } elseif ($credit > 0) {
            $status = 'credit';
        }

        $topCar = $metrics
            ->countBy('car_name')
            ->sortDesc()
            ->keys()
            ->first() ?? '—';

        return [
            'customer_id' => $customer->id,
            'customer_name' => $customer->fullName(),
            'phone' => $customer->phone ?? '—',
            'nationality' => $customer->nationality ?: '—',
            'contracts_count' => $contracts->count(),
            'active_contracts' => $metrics->where('is_active', true)->count(),
            'gross_contract_value' => round((float) $contracts->sum('total_price'), 2),
            'recorded_payments' => round((float) $metrics->sum('recorded_payments'), 2),
            'outstanding_balance' => $outstanding,
            'overdue_balance' => $overdue,
            'credit_balance' => $credit,
            'deposits_paid' => round((float) $metrics->sum('deposits_paid'), 2),
            'extras_paid' => round((float) $metrics->sum('extras_paid'), 2),
            'latest_contract_date' => $this->formatDateTime($latestContract?->pickup_date ?? $latestContract?->created_at),
            'latest_contract_status' => $latestContract ? ContractStatus::label($latestContract->current_status) : '—',
            'latest_contract_timestamp' => $this->timestampValue($latestContract?->pickup_date ?? $latestContract?->created_at),
            'top_car' => $topCar,
            'open_contract_ids' => $metrics->where('outstanding', '>', 0)->pluck('contract_id')->implode(', ') ?: '—',
            'status' => $status,
            'status_label' => match ($status) {
                'overdue' => 'Overdue',
                'open' => 'Open balance',
                'credit' => 'Credit',
                default => 'Settled',
            },
        ];
    }

    private function mapFleetPerformanceRow(Car $car, ?string $dateFrom, ?string $dateTo, ?int $windowDays): array
    {
        $contracts = ($car->contracts ?? collect())
            ->sortBy(fn (Contract $contract) => $this->timestampValue($contract->pickup_date))
            ->values();

        $revenue = round((float) $contracts->sum('total_price'), 2);
        $recordedPayments = round((float) $contracts->sum(function (Contract $contract) {
            $payments = $contract->payments ?? collect();

            return $this->sumPayments($payments, array_merge(['rental_fee', 'security_deposit'], self::EXTRA_PAYMENT_TYPES))
                - $this->sumPayments($payments, ['payment_back']);
        }), 2);

        $bookedDays = round((float) $contracts->sum(
            fn (Contract $contract) => $this->overlapDays($contract->pickup_date, $contract->return_date, $dateFrom, $dateTo)
        ), 1);

        $averageDailyRevenue = $bookedDays > 0 ? round($revenue / $bookedDays, 2) : 0.0;
        $utilization = $windowDays && $windowDays > 0 ? round(min(($bookedDays / $windowDays) * 100, 100), 1) : 0.0;
        $lastContract = $contracts->last();

        return [
            'car_id' => $car->id,
            'car_name' => $car->modelName(),
            'plate_number' => $car->plate_number ?? '—',
            'ownership' => $car->ownershipLabel(),
            'availability' => $car->isAvailable() ? 'Ready' : Str::headline(str_replace('_', ' ', $car->status ?? 'offline')),
            'contracts_count' => $contracts->count(),
            'unique_customers' => $contracts->pluck('customer_id')->filter()->unique()->count(),
            'revenue' => $revenue,
            'recorded_payments' => $recordedPayments,
            'booked_days' => $bookedDays,
            'utilization_pct' => $utilization,
            'average_contract_value' => $contracts->count() > 0 ? round($revenue / $contracts->count(), 2) : 0.0,
            'average_daily_revenue' => $averageDailyRevenue,
            'last_pickup_date' => $this->formatDateTime($lastContract?->pickup_date),
            'last_return_date' => $this->formatDateTime($lastContract?->return_date),
            'current_customer' => $car->currentContract?->customer?->fullName() ?? '—',
        ];
    }

    private function mapPaymentRow(Payment $payment): array
    {
        $amountInAed = round((float) ($payment->amount_in_aed ?? $payment->amount ?? 0), 2);

        return [
            'payment_id' => $payment->id,
            'payment_date' => $this->formatDateTime($payment->payment_date),
            'contract_id' => $payment->contract_id ?? '—',
            'customer_name' => $payment->customer?->fullName() ?? $payment->contract?->customer?->fullName() ?? '—',
            'customer_phone' => $payment->customer?->phone ?? $payment->contract?->customer?->phone ?? '—',
            'car_name' => $payment->car?->modelName() ?? $payment->contract?->car?->modelName() ?? '—',
            'plate_number' => $payment->car?->plate_number ?? $payment->contract?->car?->plate_number ?? '—',
            'processed_by' => $payment->user?->fullName() ?? '—',
            'payment_type' => $payment->payment_type,
            'payment_type_label' => Str::headline(str_replace('_', ' ', $payment->payment_type)),
            'payment_method_label' => Str::headline($payment->payment_method),
            'currency' => $payment->currency,
            'amount' => round((float) $payment->amount, 2),
            'amount_in_aed' => $amountInAed,
            'approval_status' => $payment->approval_status,
            'approval_status_label' => Str::headline($payment->approval_status),
            'is_paid' => (bool) $payment->is_paid,
            'is_paid_label' => $payment->is_paid ? 'Yes' : 'No',
            'is_refundable' => (bool) $payment->is_refundable,
            'is_refundable_label' => $payment->is_refundable ? 'Yes' : 'No',
            'description' => $payment->description ?: '—',
            'note' => $payment->note ?: '—',
            'net_amount_for_summary' => $payment->payment_type === 'payment_back' ? -$amountInAed : $amountInAed,
        ];
    }

    private function normalizeCustomerRequestFilters(array $filters): array
    {
        $dateField = (string) ($filters['date_field'] ?? 'created_at');

        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'date_field' => in_array($dateField, self::CUSTOMER_REQUEST_DATE_FIELDS, true) ? $dateField : 'created_at',
            'date_from' => $this->normalizeDateString($filters['date_from'] ?? null),
            'date_to' => $this->normalizeDateString($filters['date_to'] ?? null),
            'status' => trim((string) ($filters['status'] ?? 'all')) ?: 'all',
            'agent_id' => isset($filters['agent_id']) && $filters['agent_id'] !== '' ? (int) $filters['agent_id'] : null,
            'kardo' => trim((string) ($filters['kardo'] ?? 'all')) ?: 'all',
        ];
    }

    private function normalizeCustomerBalanceFilters(array $filters): array
    {
        $dateField = (string) ($filters['date_field'] ?? 'pickup_date');

        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'date_field' => in_array($dateField, self::BALANCE_DATE_FIELDS, true) ? $dateField : 'pickup_date',
            'date_from' => $this->normalizeDateString($filters['date_from'] ?? null),
            'date_to' => $this->normalizeDateString($filters['date_to'] ?? null),
            'balance_status' => trim((string) ($filters['balance_status'] ?? 'all')) ?: 'all',
        ];
    }

    private function normalizeFleetFilters(array $filters): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'date_from' => $this->normalizeDateString($filters['date_from'] ?? null),
            'date_to' => $this->normalizeDateString($filters['date_to'] ?? null),
            'ownership' => trim((string) ($filters['ownership'] ?? 'all')) ?: 'all',
        ];
    }

    private function normalizePaymentFilters(array $filters): array
    {
        $dateField = (string) ($filters['date_field'] ?? 'payment_date');

        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'date_field' => in_array($dateField, self::PAYMENT_DATE_FIELDS, true) ? $dateField : 'payment_date',
            'date_from' => $this->normalizeDateString($filters['date_from'] ?? null),
            'date_to' => $this->normalizeDateString($filters['date_to'] ?? null),
            'payment_type' => trim((string) ($filters['payment_type'] ?? 'all')) ?: 'all',
            'approval_status' => trim((string) ($filters['approval_status'] ?? 'all')) ?: 'all',
            'payment_state' => trim((string) ($filters['payment_state'] ?? 'all')) ?: 'all',
            'payment_method' => trim((string) ($filters['payment_method'] ?? 'all')) ?: 'all',
        ];
    }

    private function applyContractSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = $this->likeValue($search);
        $isPhone = $this->looksLikePhone($search);

        $query->where(function (Builder $contractQuery) use ($like, $isPhone) {
            $contractQuery->where('contracts.id', 'like', $like)
                ->orWhere('submitted_by_name', 'like', $like)
                ->orWhereHas('customer', function (Builder $customerQuery) use ($like, $isPhone) {
                    $customerQuery->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('passport_number', 'like', $like)
                        ->orWhere('license_number', 'like', $like);

                    if ($isPhone) {
                        $customerQuery->orWhere('phone', 'like', $like)
                            ->orWhere('messenger_phone', 'like', $like);
                    }
                })
                ->orWhereHas('car', function (Builder $carQuery) use ($like) {
                    $carQuery->where('plate_number', 'like', $like)
                        ->orWhereHas('carModel', function (Builder $modelQuery) use ($like) {
                            $modelQuery->where('brand', 'like', $like)
                                ->orWhere('model', 'like', $like);
                        });
                });
        });
    }

    private function applyDateRange(Builder|Relation $query, string $column, ?string $from, ?string $to): void
    {
        if ($from !== null) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate($column, '<=', $to);
        }
    }

    private function applyContractWindow(Builder|Relation $query, ?string $from, ?string $to): void
    {
        if ($to !== null) {
            $query->whereDate('pickup_date', '<=', $to);
        }

        if ($from !== null) {
            $query->where(function (Builder $windowQuery) use ($from) {
                $windowQuery->whereNull('return_date')
                    ->orWhereDate('return_date', '>=', $from);
            });
        }
    }

    private function likeValue(string $search): string
    {
        return '%' . trim($search) . '%';
    }

    private function looksLikePhone(string $search): bool
    {
        $digitsOnly = preg_replace('/\D+/', '', $search);

        return strlen((string) $digitsOnly) >= 6;
    }

    private function sumPayments(Collection $payments, array $types): float
    {
        return round((float) $payments
            ->whereIn('payment_type', $types)
            ->sum(fn (Payment $payment) => (float) ($payment->amount_in_aed ?? $payment->amount ?? 0)), 2);
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i');
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d H:i');
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value)->format('Y-m-d H:i');
        }

        return '—';
    }

    private function durationDays(mixed $start, mixed $end): int
    {
        if (! $start || ! $end) {
            return 0;
        }

        $startAt = $start instanceof Carbon ? $start : Carbon::parse($start);
        $endAt = $end instanceof Carbon ? $end : Carbon::parse($end);

        return max(1, (int) ceil($startAt->diffInMinutes($endAt, false) / 1440));
    }

    private function formatDepositDetails(?string $category, mixed $detail): string
    {
        if (($category === null || $category === '') && ($detail === null || $detail === '')) {
            return '—';
        }

        $label = match ($category) {
            'cash_aed' => 'Cash (AED)',
            'cheque' => 'Cheque',
            'transfer_cash_irr' => 'Transfer / Cash (IRR)',
            default => 'Security Hold',
        };

        if ($category === 'cash_aed' && is_numeric($detail)) {
            return sprintf('%s - %.2f AED', $label, (float) $detail);
        }

        $text = is_scalar($detail) ? trim((string) $detail) : '';

        return $text !== '' ? sprintf('%s - %s', $label, $text) : $label;
    }

    private function humanizeInsurance(string $value): ?string
    {
        $normalized = trim($value);

        if ($normalized === '' || $normalized === 'basic_insurance') {
            return null;
        }

        return Str::headline(str_replace('_', ' ', str_replace('_insurance', '', $normalized))) . ' Insurance';
    }

    private function humanizeOption(string $value): string
    {
        $normalized = trim($value);

        return $normalized !== '' ? Str::headline(str_replace('_', ' ', $normalized)) : '—';
    }

    private function numericMeta(?array $meta, string $key): float
    {
        $value = data_get($meta, $key);

        return is_numeric($value) ? round((float) $value, 2) : 0.0;
    }

    private function formatPaymentBreakdown(
        float $rentalPaid,
        float $securityDepositPaid,
        float $extrasPaid,
        float $discounts,
        float $refunds
    ): string {
        return sprintf(
            'Rental %.2f | Deposit %.2f | Extras %.2f | Discounts %.2f | Refunds %.2f',
            $rentalPaid,
            $securityDepositPaid,
            $extrasPaid,
            $discounts,
            $refunds
        );
    }

    private function mergeNotes(array $notes): string
    {
        $merged = collect($notes)
            ->filter(fn ($note) => is_string($note) && trim($note) !== '')
            ->map(fn ($note) => trim($note))
            ->implode(' | ');

        return $merged !== '' ? $merged : '—';
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        return Carbon::parse($string)->toDateString();
    }

    private function timestampValue(mixed $value): int
    {
        if ($value instanceof Carbon) {
            return $value->timestamp;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value)->timestamp;
        }

        return 0;
    }

    private function overlapDays(mixed $start, mixed $end, ?string $windowStart, ?string $windowEnd): float
    {
        if (! $start) {
            return 0.0;
        }

        $startAt = $start instanceof Carbon ? $start->copy() : Carbon::parse($start);
        $endAt = $end
            ? ($end instanceof Carbon ? $end->copy() : Carbon::parse($end))
            : $startAt->copy();

        $rangeStart = $windowStart ? Carbon::parse($windowStart)->startOfDay() : $startAt->copy()->startOfDay();
        $rangeEnd = $windowEnd ? Carbon::parse($windowEnd)->endOfDay() : $endAt->copy()->endOfDay();

        $effectiveStart = $startAt->greaterThan($rangeStart) ? $startAt : $rangeStart;
        $effectiveEnd = $endAt->lessThan($rangeEnd) ? $endAt : $rangeEnd;

        if ($effectiveEnd->lessThan($effectiveStart)) {
            return 0.0;
        }

        return round(max(1, ceil($effectiveStart->diffInMinutes($effectiveEnd) / 1440)), 1);
    }

    private function windowDays(?string $from, ?string $to): ?int
    {
        if ($from === null || $to === null) {
            return null;
        }

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        return max(1, $start->diffInDays($end) + 1);
    }
}
