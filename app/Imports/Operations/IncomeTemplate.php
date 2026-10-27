<?php

namespace App\Imports\Operations;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Income layout template, built on every download so its donor, account and classification lists
 * are the current ones. Its columns are the ones IncomeImport reads.
 */
class IncomeTemplate
{
    const LISTS = 'Listas';
    const HELP = 'Instrucciones';
    const HEADER_COLOR = '1F2937';

    // field => [width, number format, required, what to write, example]
    const GUIDE = [
        'date' => [13, 'dd/mm/yyyy', true, 'Fecha del depósito en formato dd/mm/aaaa. No puede ser futura.', '10/02/2026'],
        'donor' => [45, null, true, 'Nombre del donador como está en el sistema (están en la hoja «Listas»). Si no existe, al importar puedes pedir que se cree.', 'FUNDACIÓN EJEMPLO AC'],
        'amount' => [15, '#,##0.00', true, 'Importe del depósito, mayor a 0 y sin signo de moneda.', '1089.50'],
        'fee' => [9, '0.##', true, 'Porcentaje de fee de 0 a 100. Escribe 0 si no aplica.', '10'],
        'category' => [24, null, true, 'Clasificación del ingreso, de la lista.', 'Corporativo'],
        'subcategory' => [30, null, true, 'Subclasificación: la lista depende de la clasificación elegida.', 'Nacional'],
        'comment' => [40, null, false, 'Texto libre.', 'Donativos del mes de febrero'],
        'account' => [12, '@', true, 'Cuenta bancaria donde llegó el depósito, como siempre se ha escrito (por ejemplo los últimos 4 dígitos).', '9975'],
        'co' => [19, '0.##', false, 'Solo si el ingreso retiene costo operativo: su porcentaje de 0 a 100. El sistema registra la retención.', '10'],
    ];

    private $companyId;

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId ?? idCompany();
    }

    public function build()
    {
        $lists = (new IncomeImport($this->companyId))->lists();
        $book = new Spreadsheet();
        $book->getProperties()->setTitle('Plantilla de ingresos')->setCreator(config('app.name'));

        $sheet = $book->getActiveSheet()->setTitle(IncomeImport::SHEET);
        $this->help($book->createSheet()->setTitle(self::HELP));
        $ranges = $this->lists($book->createSheet()->setTitle(self::LISTS), $lists);
        $this->incomes($sheet, $ranges);

        $book->setActiveSheetIndex(0);
        return $book;
    }

    private function incomes(Worksheet $sheet, array $ranges)
    {
        $last = config('operations.income_import_max_rows') + 1;
        $index = 1;
        foreach (IncomeImport::COLUMNS as $field => [$label]) {
            $column = Coordinate::stringFromColumnIndex($index++);
            [$width, $format, $required, $hint] = self::GUIDE[$field];
            $sheet->setCellValue($column . '1', $label);
            $sheet->getColumnDimension($column)->setWidth($width);
            if ($format) {
                // As the column's style: formatting the rows one by one would create every cell
                $style = clone $sheet->getParent()->getDefaultStyle();
                $style->getNumberFormat()->setFormatCode($format);
                $sheet->getParent()->addCellXf($style);
                $sheet->getColumnDimension($column)->setXfIndex($style->getIndex());
            }

            $validation = $this->validation($field, $ranges);
            $validation->setAllowBlank(!$required)
                ->setShowInputMessage(true)
                ->setPromptTitle($label . ($required ? ' (obligatorio)' : ' (opcional)'))
                ->setPrompt($hint)
                ->setShowErrorMessage(true)
                ->setErrorTitle($label);
            $sheet->setDataValidation($column . '2:' . $column . $last, $validation);
        }

        $header = $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count(IncomeImport::COLUMNS)) . '1');
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_COLOR);
        $header->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
        $sheet->setSelectedCell('A2');
    }

    private function validation($field, array $ranges)
    {
        $validation = new DataValidation();
        switch ($field) {
            case 'date':
                return $validation->setType(DataValidation::TYPE_DATE)
                    ->setOperator(DataValidation::OPERATOR_BETWEEN)
                    ->setFormula1((string) ExcelDate::formattedPHPToExcel(2000, 1, 1))
                    ->setFormula2('TODAY()')
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setError('Escribe una fecha dd/mm/aaaa desde el año 2000 y que no sea futura.');
            case 'amount':
                return $validation->setType(DataValidation::TYPE_DECIMAL)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHAN)
                    ->setFormula1('0')
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setError('El importe debe ser un número mayor a 0.');
            case 'fee':
            case 'co':
                return $validation->setType(DataValidation::TYPE_DECIMAL)
                    ->setOperator(DataValidation::OPERATOR_BETWEEN)
                    ->setFormula1('0')
                    ->setFormula2('100')
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setError('Escribe un porcentaje de 0 a 100, sin el signo %.');
            case 'donor':
                // Information only: a donor that doesn't exist yet can be created when importing
                return $this->listValidation($validation, $ranges['donors'])
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setError('Este donador no está en el sistema. Al importar podrás pedir que se cree.');
            case 'account':
                return $this->listValidation($validation, $ranges['accounts'])
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setError('Esta cuenta no se ha usado antes: revisa que sea correcta.');
            case 'category':
                return $this->listValidation($validation, $ranges['categories'])
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setError('Elige una clasificación de la lista.');
            case 'subcategory':
                // The subclassifications of the classification in column E: its column of the list sheet
                $match = 'MATCH($E2,' . $ranges['subcategory_headers'] . ',0)-1';
                $formula = 'OFFSET(' . $ranges['subcategory_start'] . ',0,' . $match . ',COUNTA(OFFSET(' .
                    $ranges['subcategory_start'] . ',0,' . $match . ',' . $ranges['subcategory_height'] . ',1)),1)';
                return $validation->setType(DataValidation::TYPE_LIST)
                    ->setShowDropDown(true)
                    ->setFormula1($formula)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setError('Elige una subclasificación de la lista; primero elige la clasificación.');
            default:
                return $validation->setType(DataValidation::TYPE_NONE);
        }
    }

    private function listValidation(DataValidation $validation, $range)
    {
        return $validation->setType(DataValidation::TYPE_LIST)->setShowDropDown(true)->setFormula1($range);
    }

    // Writes the option lists and returns the ranges the validations point at
    private function lists(Worksheet $sheet, array $lists)
    {
        $sheet->setCellValue('A1', 'Donadores')->setCellValue('B1', 'Cuentas')->setCellValue('C1', 'Clasificaciones');
        // As text: a name starting with = or an account with leading zeros stays as written
        foreach ($lists['donors'] as $i => $name) {
            $sheet->setCellValueExplicit('A' . ($i + 2), $name, DataType::TYPE_STRING);
        }
        foreach (array_values($lists['accounts']) as $i => $account) {
            $sheet->setCellValueExplicit('B' . ($i + 2), $account, DataType::TYPE_STRING);
        }

        $categories = array_keys($lists['categories']);
        $categories[] = IncomeImport::UNCLASSIFIED;
        $sheet->fromArray(array_map(fn ($name) => [$name], $categories), null, 'C2', true);

        // One column per classification, D onwards, headed by its name
        $height = 1;
        $index = 4;
        foreach ($lists['categories'] as $category => $subcategories) {
            $column = Coordinate::stringFromColumnIndex($index++);
            $sheet->setCellValue($column . '1', $category);
            $sheet->fromArray(array_map(fn ($name) => [$name], $subcategories), null, $column . '2', true);
            $sheet->getColumnDimension($column)->setWidth(24);
            $height = max($height, count($subcategories));
        }
        $lastColumn = Coordinate::stringFromColumnIndex($index - 1);

        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(26);
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $quoted = "'" . self::LISTS . "'!";
        return [
            'donors' => $quoted . '$A$2:$A$' . max(2, count($lists['donors']) + 1),
            'accounts' => $quoted . '$B$2:$B$' . max(2, count($lists['accounts']) + 1),
            'categories' => $quoted . '$C$2:$C$' . (count($categories) + 1),
            'subcategory_headers' => $quoted . '$D$1:$' . $lastColumn . '$1',
            'subcategory_start' => $quoted . '$D$2',
            'subcategory_height' => $height,
        ];
    }

    private function help(Worksheet $sheet)
    {
        $sheet->setCellValue('A1', 'Plantilla de ingresos');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $lines = [
            'Llena la hoja «' . IncomeImport::SHEET . '»: un ingreso por fila, a partir de la fila 2. No cambies los encabezados ni el orden de las columnas.',
            'Las filas vacías se ignoran. Antes de guardar nada, el sistema revisa todo el archivo y muestra los errores por fila y columna: si hay alguno, no se importa ningún ingreso.',
            'Cada carga queda en el histórico de cargas, desde donde se puede revertir completa.',
        ];
        foreach ($lines as $i => $line) {
            $sheet->setCellValue('A' . ($i + 3), $line);
        }

        $first = count($lines) + 4;
        $sheet->fromArray(['Columna', 'Encabezado', 'Obligatoria', 'Qué escribir', 'Ejemplo'], null, 'A' . $first);
        $header = $sheet->getStyle('A' . $first . ':E' . $first);
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_COLOR);

        $row = $first + 1;
        $index = 1;
        foreach (IncomeImport::COLUMNS as $field => [$label]) {
            [, , $required, $hint, $example] = self::GUIDE[$field];
            $sheet->fromArray([Coordinate::stringFromColumnIndex($index++), $label, $required ? 'Sí' : 'No', $hint], null, 'A' . $row);
            $sheet->setCellValueExplicit('E' . $row, $example, DataType::TYPE_STRING);
            $row++;
        }
        $sheet->setCellValue('A' . ($row + 1), 'Generada el ' . date('d/m/Y H:i') . ': las listas de donadores y cuentas son las de ese momento.');

        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(90);
        $sheet->getColumnDimension('E')->setWidth(28);
        $sheet->getStyle('D' . $first . ':D' . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A' . $first . ':E' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }
}
