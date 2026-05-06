<?php

namespace App\Exports\Reports;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class ReportValueBinder extends StringValueBinder
{
    public function __construct()
    {
        $this->setNullConversion(false);
        $this->setBooleanConversion(false);
        $this->setNumericConversion(false);
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            $value = StringHelper::sanitizeUTF8($value);

            if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true) && !is_numeric($value)) {
                $cell->getStyle()->setQuotePrefix(true);
                $cell->setValueExplicit($value, DataType::TYPE_STRING);

                return true;
            }
        }

        return parent::bindValue($cell, $value);
    }
}
