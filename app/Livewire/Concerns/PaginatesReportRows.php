<?php

namespace App\Livewire\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

trait PaginatesReportRows
{
    protected function paginateRows(Collection $rows, int $perPage = 12, string $pageName = 'page'): LengthAwarePaginator
    {
        $page = method_exists($this, 'getPage')
            ? $this->getPage($pageName)
            : Paginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => $pageName,
            ]
        );
    }
}
