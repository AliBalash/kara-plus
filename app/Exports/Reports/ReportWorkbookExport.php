<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  array<string, array<string, string|int|float>>  $summarySections
     * @param  array<string, string>  $filterSummary
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, string|int|float|null>>  $rows
     * @param  array<int, array{title: string, headings: array<int, string>, rows: array<int, array<int, string|int|float|null>>, accentColor?: string}>  $extraSheets
     */
    public function __construct(
        private readonly string $title,
        private readonly array $summarySections,
        private readonly array $filterSummary,
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $dataSheetTitle = 'Data',
        private readonly array $extraSheets = []
    ) {
    }

    public function sheets(): array
    {
        $sheets = [
            new ReportSummarySheetExport($this->title, $this->summarySections, $this->filterSummary),
            new ReportDataSheetExport($this->dataSheetTitle, $this->headings, $this->rows),
        ];

        foreach ($this->extraSheets as $sheet) {
            $sheets[] = new ReportDataSheetExport(
                $sheet['title'],
                $sheet['headings'],
                $sheet['rows'],
                $sheet['accentColor'] ?? '0F766E'
            );
        }

        return $sheets;
    }
}
