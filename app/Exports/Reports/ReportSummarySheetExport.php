<?php

namespace App\Exports\Reports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportSummarySheetExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    /**
     * @param  array<string, array<string, string|int|float>>  $summarySections
     * @param  array<string, string>  $filterSummary
     */
    public function __construct(
        private readonly string $title,
        private readonly array $summarySections,
        private readonly array $filterSummary
    ) {
    }

    public function array(): array
    {
        $rows = [
            [$this->title],
            ['Generated At', Carbon::now()->format('Y-m-d H:i')],
            [],
            ['Applied Filters'],
            ['Filter', 'Value'],
        ];

        foreach ($this->filterSummary as $label => $value) {
            $rows[] = [$label, $value];
        }

        foreach ($this->summarySections as $sectionTitle => $items) {
            $rows[] = [];
            $rows[] = [$sectionTitle];
            $rows[] = ['Metric', 'Value'];

            foreach ($items as $label => $value) {
                $rows[] = [$label, $value];
            }
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0F172A']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:B1');
                $sheet->freezePane('A5');

                for ($row = 1; $row <= $highestRow; $row++) {
                    $label = trim((string) $sheet->getCell("A{$row}")->getValue());
                    $value = trim((string) $sheet->getCell("B{$row}")->getValue());

                    if ($label !== '' && $value === '' && $row !== 1) {
                        $sheet->mergeCells("A{$row}:B{$row}");
                        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'E0F2FE'],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_LEFT,
                            ],
                        ]);
                    }
                }

                $sheet->getStyle('A2:B' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A5:B' . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A5:B5')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1D4ED8'],
                    ],
                ]);
            },
        ];
    }
}
