<?php

namespace App\Livewire\Concerns;

trait SearchesCustomerPhone
{
    protected function isCustomerPhoneSearch(string $search): bool
    {
        $digitsOnly = preg_replace('/\D+/', '', $search);

        return strlen($digitsOnly) > 5;
    }
}
