<?php

namespace Tests\Unit;

use App\Exports\Reports\ReportWorkbookExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportWorkbookExportTest extends TestCase
{
    public function test_it_exports_formula_like_text_as_plain_text(): void
    {
        $contents = Excel::raw(
            new ReportWorkbookExport(
                'Customer Requests Report',
                ['Snapshot' => ['Matching Contracts' => 1]],
                ['Customer Search' => "====\nChange to under_review by Expert"],
                ['Notes'],
                [['=SUM(1,1)']],
                'Customer Requests'
            ),
            ExcelFormat::XLSX
        );

        $path = tempnam(sys_get_temp_dir(), 'report-export-');
        file_put_contents($path, $contents);

        $spreadsheet = IOFactory::load($path);
        $summarySheet = $spreadsheet->getSheetByName('Summary');
        $customerSearchRow = null;

        for ($row = 1; $row <= 50; $row++) {
            if ($summarySheet->getCell("A{$row}")->getValue() === 'Customer Search') {
                $customerSearchRow = $row;
                break;
            }
        }

        $this->assertNotNull($customerSearchRow, 'Customer Search row was not found in Summary sheet.');

        $this->assertSame("====\nChange to under_review by Expert", $summarySheet->getCell("B{$customerSearchRow}")->getValue());
        $this->assertSame(DataType::TYPE_STRING, $summarySheet->getCell("B{$customerSearchRow}")->getDataType());
        $this->assertSame('=SUM(1,1)', $spreadsheet->getSheetByName('Customer Requests')->getCell('A2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $spreadsheet->getSheetByName('Customer Requests')->getCell('A2')->getDataType());

        @unlink($path);
    }
}
