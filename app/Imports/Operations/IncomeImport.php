<?php

namespace App\Imports\Operations;

use App\Account;
use App\Action;
use App\Category;
use App\Contact;
use App\Operation;
use App\OperationHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Income layout import. The whole file is validated before anything is booked, and the booking is all
 * or nothing: every operation and donor it creates carries the id of its history_operations row, which
 * is what lists the import in the history and lets it be rolled back.
 */
class IncomeImport
{
    // Template columns in order: field => [header, words an older layout may use as header instead]
    const COLUMNS = [
        'date' => ['Fecha', ['fecha', 'date']],
        'donor' => ['Donador', ['donador', 'donante', 'donor', 'contacto', 'nombre']],
        'amount' => ['Importe', ['importe', 'monto', 'amount', 'cantidad', 'deposito']],
        'fee' => ['Fee %', ['fee', 'comision']],
        'category' => ['Clasificación', ['clasificacion', 'categoria', 'category']],
        'subcategory' => ['Subclasificación', ['subclasificacion', 'subcategoria', 'subcategory', 'sub']],
        'comment' => ['Comentarios', ['comentario', 'concepto', 'comment', 'descripcion', 'observacion']],
        'account' => ['Cuenta', ['cuenta', 'account', 'folio']],
        'co' => ['% Costo operativo', ['costo', 'operativo', 'retencion', 'retention', 'co']],
    ];

    const SHEET = 'Ingresos';
    const TYPE = 'incomes';
    // Pay method of every imported income
    const MODE_ID = 157;
    const UNCLASSIFIED = 'Sin Clasificación';
    // Messages returned to the browser, each list
    const MAX_MESSAGES = 300;

    private $companyId;
    private $errors = [];
    private $warnings = [];

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId ?? idCompany();
    }

    /**
     * Read and check the layout without booking anything.
     * The result's rows are what import() books; the rest is the report shown to the user.
     */
    public function validate($path, $createDonors = false, $fileHash = null)
    {
        $this->errors = [];
        $this->warnings = [];

        if ($fileHash) {
            $previous = OperationHistory::where([
                'company_id' => $this->companyId,
                'type' => self::TYPE,
                'file_hash' => $fileHash,
                'status' => OperationHistory::ACTIVE,
            ])->first();
            if ($previous) {
                $this->error(null, null, 'Este archivo ya se cargó el ' . date('d/m/Y H:i', strtotime($previous->date)) .
                    ' (carga #' . $previous->id . '). Para volver a cargarlo primero revierte esa carga desde el histórico.');
            }
        }

        try {
            $sheet = $this->sheet($path);
        } catch (\Throwable $e) {
            $this->error(null, null, 'No se pudo leer el archivo. Verifica que sea un Excel (.xlsx) válido.');
            return $this->result([], 0, 0);
        }

        try {
            return $this->readSheet($sheet, $createDonors);
        } finally {
            // the workbook and its sheets reference each other: free them now, not at the end of the request
            $sheet->getParent()->disconnectWorksheets();
        }
    }

    private function readSheet(Worksheet $sheet, $createDonors)
    {
        if (!$this->checkHeaders($sheet)) {
            return $this->result([], 0, 0);
        }

        $catalog = $this->catalog();
        $max = config('operations.income_import_max_rows');
        $rows = [];
        $dataRows = 0;
        $emptyRows = 0;
        $last = $sheet->getHighestDataRow();
        for ($number = 2; $number <= $last; $number++) {
            $values = $this->readRow($sheet, $number);
            if (is_null($values)) {
                $emptyRows++;
                continue;
            }
            if (++$dataRows > $max) {
                $this->error(null, null, 'El archivo tiene más de ' . number_format($max) . ' ingresos. Divídelo en varios archivos.');
                return $this->result([], 0, 0);
            }
            $row = $this->parseRow($number, $values, $catalog, $createDonors);
            if ($row) {
                $rows[] = $row;
            }
        }

        if (!$dataRows) {
            $this->error(null, null, 'El archivo no tiene ingresos: llena la hoja «' . self::SHEET . '» a partir de la fila 2.');
        }
        $this->checkDuplicates($rows);

        return $this->result($rows, $dataRows, $emptyRows);
    }

    /**
     * Book a validated layout in one transaction and return its history row.
     * $file: original_name, path (storage disk local) and hash of the uploaded file.
     */
    public function import(array $result, array $file)
    {
        if ($result['errors_total'] > 0 || empty($result['rows'])) {
            throw new \RuntimeException('El archivo tiene errores: no se importó ningún ingreso.');
        }

        return DB::transaction(function () use ($result, $file) {
            $summary = $result['summary'];
            $history = OperationHistory::create([
                'company_id' => $this->companyId,
                'type' => self::TYPE,
                'user_id' => authUser('id'),
                'status' => OperationHistory::ACTIVE,
                'original_name' => $file['original_name'],
                'file_path' => $file['path'],
                'file_hash' => $file['hash'],
                'period_from' => $summary['period_from'],
                'period_to' => $summary['period_to'],
            ]);
            $stamp = date('YmdHis');
            $importKey = 'incomes-' . $stamp . '-' . $history->id;

            $donors = [];
            $operations = 0;
            foreach ($result['rows'] as $row) {
                $donorId = $row['donor_id'];
                if (!$donorId) {
                    if (!isset($donors[$row['donor_key']])) {
                        $donors[$row['donor_key']] = Contact::create([
                            'name' => $row['donor_name'],
                            'category' => 'Donador',
                            'company_id' => $this->companyId,
                            'active' => 1,
                            'history_operation_id' => $history->id,
                        ])->id;
                    }
                    $donorId = $donors[$row['donor_key']];
                }

                // Links an income to its operating cost movements only (and not to the rest of the import)
                $reference = $row['co'] ? $stamp . '-' . $history->id . '-' . $row['row'] : null;
                $income = Operation::create([
                    'date' => $row['date'],
                    'contact_id' => $donorId,
                    'mode_id' => self::MODE_ID,
                    'project_id' => 0,
                    'type' => 'income',
                    'contact_type' => 'Donador',
                    'amount' => $this->decimal($row['amount'], 2),
                    'fee' => $this->decimal($row['fee'], 6),
                    'category' => $row['category'],
                    'subcategory' => $row['subcategory'],
                    'comment' => $row['comment'],
                    'account' => $row['account'],
                    'transfer' => 0,
                    'active' => 1,
                    'company_id' => $this->companyId,
                    'import_key' => $importKey,
                    'val_operating_cost' => $row['co_amount'],
                    'percentage_operating_cost' => $row['co'] ? $this->decimal($row['co'], 6) : null,
                    'transfer_reference' => $reference,
                    'history_operation_id' => $history->id,
                ]);
                $operations++;

                if ($row['co']) {
                    $this->bookOperatingCost($row, $donorId, $income, $importKey, $reference, $history->id);
                    $operations += 2;
                }
            }

            $history->fill([
                'import_key' => $importKey,
                'rows_count' => count($result['rows']),
                'operations_count' => $operations,
                'total_amount' => $summary['total_amount'],
                'total_fee' => $summary['total_fee'],
                'total_co' => $summary['total_co'],
                'donors_created' => count($donors),
            ])->save();

            $this->log('CREATION', 'Carga de ingresos #' . $history->id . ': ' . count($result['rows']) . ' ingresos', $history->id);

            return $history;
        });
    }

    /**
     * What rolling back an import would remove, shown before confirming it.
     */
    public static function impact(OperationHistory $history)
    {
        $operations = Operation::where(['history_operation_id' => $history->id, 'company_id' => $history->company_id]);
        $incomes = (clone $operations)->where(['type' => 'income', 'is_co' => 0]);

        return [
            'incomes' => (clone $incomes)->count(),
            'amount' => round((float) (clone $incomes)->sum(DB::raw('CAST(amount AS DECIMAL(20,2))')), 2),
            'operating_cost' => (clone $operations)->where('is_co', 1)->count(),
            'operations' => (clone $operations)->count(),
            // Changed in the operation form after the import: the rollback removes those changes too
            'edited' => (clone $operations)->whereColumn('updated_at', '>', 'created_at')->count(),
            'donors_created' => Contact::where('history_operation_id', $history->id)->where('active', 1)->count(),
            'donors_to_deactivate' => self::unusedDonors($history, true)->count(),
        ];
    }

    /**
     * Remove every operation an import booked and deactivate the donors it created that nothing else uses.
     */
    public static function revert(OperationHistory $history, $reason)
    {
        return DB::transaction(function () use ($history, $reason) {
            $history = OperationHistory::whereKey($history->id)->lockForUpdate()->firstOrFail();
            if ((int) $history->status !== OperationHistory::ACTIVE) {
                throw new \RuntimeException('Esta carga ya fue revertida.');
            }

            $operations = Operation::where(['history_operation_id' => $history->id, 'company_id' => $history->company_id])->delete();
            $donors = self::unusedDonors($history, false)->pluck('id');
            Contact::whereIn('id', $donors)->update(['active' => 0]);

            $history->fill([
                'status' => OperationHistory::REVERTED,
                'user_id_delete' => authUser('id'),
                'date_delete' => now(),
                'revert_reason' => $reason,
            ])->save();

            (new self($history->company_id))->log('ELIMINATION',
                'Carga de ingresos #' . $history->id . ' revertida: ' . $operations . ' operaciones, ' . count($donors) . ' donadores desactivados', $history->id);

            return ['operations' => $operations, 'donors' => count($donors)];
        });
    }

    // Donors the import created that no operation, project or user would use once it is rolled back.
    private static function unusedDonors(OperationHistory $history, $ignoreOwnOperations)
    {
        return Contact::select('contacts.id')
            ->where(['contacts.history_operation_id' => $history->id, 'contacts.active' => 1])
            ->whereNotExists(function ($query) use ($history, $ignoreOwnOperations) {
                $query->select(DB::raw(1))->from('operations as o')
                    ->where(function ($query) {
                        $query->whereColumn('o.contact_id', 'contacts.id')->orWhereColumn('o.donor_id', 'contacts.id');
                    });
                if ($ignoreOwnOperations) {
                    $query->where(function ($query) use ($history) {
                        $query->whereNull('o.history_operation_id')->orWhere('o.history_operation_id', '<>', $history->id);
                    });
                }
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))->from('projects as p')->whereColumn('p.donors', 'contacts.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))->from('users as u')->whereRaw('FIND_IN_SET(contacts.id, u.donors)');
            });
    }

    // Operating cost retained from an income: an outcome of the donor and an income of the reserve contact.
    private function bookOperatingCost(array $row, $donorId, Operation $income, $importKey, $reference, $historyId)
    {
        $common = [
            'date' => $row['date'],
            'contact_id' => config('operations.operating_cost_contact_id'),
            'donor_id' => $donorId,
            'mode_id' => self::MODE_ID,
            'project_id' => 0,
            'contact_type' => 'Donador',
            'amount' => $row['co_amount'],
            'fee' => $this->decimal($row['fee'], 6),
            'comment' => $row['comment'],
            'transfer_reference' => $reference,
            'is_co' => 1,
            'transfer' => 1,
            'active' => 1,
            'company_id' => $this->companyId,
            'import_key' => $importKey,
            'percentage_operating_cost' => $this->decimal($row['co'], 6),
            'movement_id' => $income->id,
            'history_operation_id' => $historyId,
        ];

        Operation::create($common + [
            'type' => 'outcome',
            'category' => $row['category'],
            'subcategory' => $row['subcategory'],
        ]);
        Operation::create($common + [
            'type' => 'income',
            'category' => 'RESERVA RETENCIÓN CO',
            'subcategory' => 'RESERVA RETENCIÓN CO',
            'monitoring' => 0,
        ]);
    }

    private function log($type, $name, $historyId)
    {
        $action = new Action;
        $action->fill([
            'type' => $type,
            'name' => $name,
            'tablename' => 'history_operations',
            'item' => $historyId,
            'user_id' => authUser('id'),
            'company_id' => $this->companyId,
        ])->save();
    }

    /*** READING ***/

    private function sheet($path)
    {
        // Data only: with styles the reader also expands every data validation of the template into one
        // object per cell (9 columns x 5000 rows), which runs out of memory
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $book = $reader->load($path);

        // The template has instruction and list sheets: read the income one, older layouts only have one sheet.
        return $book->getSheetByName(self::SHEET) ?? $book->getSheet(0);
    }

    private function checkHeaders(Worksheet $sheet)
    {
        $before = count($this->errors);
        $index = 1;
        foreach (self::COLUMNS as $field => [$label, $aliases]) {
            $column = Coordinate::stringFromColumnIndex($index++);
            $header = $sheet->cellExists($column . '1') ? $this->text($sheet->getCell($column . '1')->getValue()) : '';
            // The operating cost column may be missing or unnamed in older layouts
            if ($field == 'co' && $header === '') {
                continue;
            }
            if (!$this->headerMatches($field, $header, $aliases)) {
                $this->error(1, $field, $header === ''
                    ? 'Falta el encabezado «' . $label . '».'
                    : 'Se esperaba el encabezado «' . $label . '» y dice «' . $header . '».', $header);
            }
        }

        if (count($this->errors) > $before) {
            $first = $sheet->cellExists('A1') ? $sheet->getCell('A1')->getValue() : null;
            $hint = is_numeric($first)
                ? 'La primera fila parece un ingreso: la fila 1 debe tener los encabezados.'
                : 'Las columnas deben ir en el orden de la plantilla.';
            $this->error(null, null, $hint . ' Descarga la plantilla nueva y copia ahí tus ingresos.');
            return false;
        }
        return true;
    }

    private function headerMatches($field, $header, array $aliases)
    {
        $words = explode(' ', $this->key($header));
        // "Subclasificación" contains "clasificación": the classification column can't be the sub one
        if ($field == 'category' && Str::startsWith($this->key($header), 'sub')) {
            return false;
        }
        foreach ($aliases as $alias) {
            foreach ($words as $word) {
                if (strlen($alias) <= 3 ? $word === $alias : Str::startsWith($word, $alias)) {
                    return true;
                }
            }
        }
        return false;
    }

    // The value of every column, or null for an empty row
    private function readRow(Worksheet $sheet, $number)
    {
        $values = [];
        $empty = true;
        $index = 1;
        foreach (array_keys(self::COLUMNS) as $field) {
            $coordinate = Coordinate::stringFromColumnIndex($index++) . $number;
            if (!$sheet->cellExists($coordinate)) {
                $values[$field] = null;
                continue;
            }
            $cell = $sheet->getCell($coordinate);
            try {
                $value = $cell->getCalculatedValue();
            } catch (\Throwable $e) {
                $value = $cell->getOldCalculatedValue() ?? '#ERROR';
            }
            if ($value instanceof RichText) {
                $value = $value->getPlainText();
            }
            if (is_bool($value)) {
                $value = $value ? 'VERDADERO' : 'FALSO';
            }
            if (is_string($value)) {
                $value = $this->clean($value);
            }
            if (!is_null($value) && $value !== '') {
                $empty = false;
            }
            $values[$field] = $value;
        }
        return $empty ? null : $values;
    }

    /*** VALIDATION ***/

    private function parseRow($number, array $values, array &$catalog, $createDonors)
    {
        $before = count($this->errors);
        $row = ['row' => $number];

        $row['date'] = $this->parseDate($number, $values['date']);

        // DONOR
        $name = $this->text($values['donor']);
        if ($name === '') {
            $this->error($number, 'donor', 'El donador es obligatorio.');
        } elseif (mb_strlen($name) > 200) {
            $this->error($number, 'donor', 'El nombre del donador no puede pasar de 200 caracteres.', $name);
        } else {
            $row += ['donor_name' => $name, 'donor_key' => $this->key($name), 'donor_id' => null];
            $match = $this->findDonor($name, $catalog);
            if ($match['ids']) {
                $row['donor_id'] = $match['ids'][0];
                if (count($match['ids']) > 1) {
                    $this->warning($number, 'donor', 'Hay ' . count($match['ids']) . ' donadores llamados «' . $name . '» (#' .
                        implode(', #', $match['ids']) . '): se usará el #' . $match['ids'][0] . '.', $name);
                }
            } elseif ($createDonors) {
                // said once per donor, however many rows it has
                if (!isset($catalog['new'][$row['donor_key']])) {
                    $catalog['new'][$row['donor_key']] = true;
                    $this->warning($number, 'donor', 'El donador «' . $name . '» no existe: se creará al importar.' .
                        ($match['suggestion'] ? ' ¿No será «' . $match['suggestion'] . '»?' : ''), $name);
                }
            } else {
                $message = 'El donador «' . $name . '» no existe.';
                if ($match['other']) {
                    $message .= ' Hay un contacto con ese nombre pero es ' . $match['other'] . '.';
                }
                if ($match['suggestion']) {
                    $message .= ' ¿Quisiste decir «' . $match['suggestion'] . '»?';
                }
                $this->error($number, 'donor', $message . ' Corrige el nombre o marca «Crear los donadores que no existan».', $name);
            }
        }

        // AMOUNT
        $amount = $values['amount'];
        $parsed = $this->number($amount);
        if ($this->blank($amount)) {
            $this->error($number, 'amount', 'El importe es obligatorio.');
        } elseif (is_null($parsed)) {
            $this->error($number, 'amount', 'El importe «' . $this->text($amount) . '» no es un número.', $amount);
        } elseif (round($parsed, 2) <= 0) {
            $this->error($number, 'amount', 'El importe debe ser mayor a 0.', $amount);
        } else {
            $row['amount'] = round($parsed, 2);
        }

        // FEE
        $fee = $values['fee'];
        if ($this->blank($fee)) {
            $this->error($number, 'fee', 'El fee es obligatorio: escribe 0 si no aplica.');
        } elseif ($message = $this->invalidPercent($fee, 'El fee')) {
            $this->error($number, 'fee', $message, $fee);
        } else {
            $row['fee'] = $this->percent($fee);
            if ($row['fee'] > 0 && $catalog['fees'] && !in_array(round($row['fee'], 4), $catalog['fees'])) {
                $this->warning($number, 'fee', 'El fee de ' . $this->decimal($row['fee'], 4) . '% no está entre los configurados.', $fee);
            }
        }

        // CLASSIFICATION
        $this->parseCategory($number, $values, $catalog, $row);

        $row['comment'] = $this->text($values['comment']);

        // ACCOUNT: the bank account the deposit arrived at, as the layouts have always written it
        $account = $this->text($values['account']);
        if ($account === '') {
            $this->error($number, 'account', 'La cuenta es obligatoria.');
        } elseif (mb_strlen($account) > 50) {
            $this->error($number, 'account', 'La cuenta no puede pasar de 50 caracteres.', $account);
        } else {
            $row['account'] = $account;
            if (!isset($catalog['accounts'][$account])) {
                $this->warning($number, 'account', 'La cuenta «' . $account . '» no se ha usado en ningún ingreso anterior: revisa que sea correcta.', $account);
            }
        }

        // OPERATING COST
        $row['co'] = null;
        if (!$this->blank($values['co'])) {
            if ($message = $this->invalidPercent($values['co'], 'El % de costo operativo')) {
                $this->error($number, 'co', $message, $values['co']);
            } elseif ($this->percent($values['co']) > 0) {
                $row['co'] = $this->percent($values['co']);
            }
        }

        if (count($this->errors) > $before) {
            return null;
        }

        $row['fee_amount'] = round($row['amount'] * $row['fee'] / 100, 2);
        $row['co_amount'] = $row['co'] ? calculatedRetainAmount($row['amount'], $row['fee'], $row['co']) : null;
        return $row;
    }

    private function parseDate($number, $value)
    {
        if ($this->blank($value)) {
            $this->error($number, 'date', 'La fecha es obligatoria.');
            return null;
        }

        $date = null;
        if (is_int($value) || is_float($value)) {
            if ($value >= 1 && $value < 2958466) {
                $date = ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }
        } else {
            foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'Y/m/d', 'd/m/y'] as $format) {
                $parsed = \DateTime::createFromFormat('!' . $format, $this->text($value));
                // the round trip rejects dates that don't exist, such as 31/02/2026
                if ($parsed && $parsed->format($format) === $this->text($value)) {
                    $date = $parsed->format('Y-m-d');
                    break;
                }
            }
        }

        if (!$date) {
            $this->error($number, 'date', 'La fecha «' . $this->text($value) . '» no es válida: usa el formato dd/mm/aaaa.', $value);
            return null;
        }
        if ($date < '2000-01-01') {
            $this->error($number, 'date', 'La fecha ' . $this->displayDate($date) . ' es anterior al año 2000.', $value);
            return null;
        }
        if ($date > date('Y-m-d')) {
            $this->error($number, 'date', 'La fecha ' . $this->displayDate($date) . ' es futura.', $value);
            return null;
        }
        return $date;
    }

    private function parseCategory($number, array $values, array $catalog, array &$row)
    {
        $category = $this->text($values['category']);
        $subcategory = $this->text($values['subcategory']);
        $key = $this->key($category);

        if ($category === '') {
            $this->error($number, 'category', 'La clasificación es obligatoria.');
            return;
        }
        if ($key === $this->key(self::UNCLASSIFIED)) {
            $row['category'] = self::UNCLASSIFIED;
            $row['subcategory'] = null;
            if ($subcategory !== '') {
                $this->warning($number, 'subcategory', 'Con «' . self::UNCLASSIFIED . '» la subclasificación se deja vacía.', $subcategory);
            }
            return;
        }
        if (!isset($catalog['categories'][$key])) {
            $options = array_diff(array_keys($catalog['subcategories']), config('operations.system_income_categories'));
            $this->error($number, 'category', 'La clasificación «' . $category . '» no existe. Usa una de: ' .
                implode(', ', $options) . ' o ' . self::UNCLASSIFIED . '.', $category);
            return;
        }

        $canonical = $catalog['categories'][$key];
        if (in_array($canonical, config('operations.system_income_categories'))) {
            $this->error($number, 'category', 'La clasificación «' . $canonical . '» la registra el sistema: no se puede importar.', $category);
            return;
        }
        $row['category'] = $canonical;

        $options = $catalog['subcategories'][$canonical];
        if ($subcategory === '') {
            $this->error($number, 'subcategory', 'La subclasificación es obligatoria. Para «' . $canonical . '» usa una de: ' . implode(', ', $options) . '.');
            return;
        }
        foreach ($options as $option) {
            if ($this->key($option) === $this->key($subcategory)) {
                $row['subcategory'] = $option;
                return;
            }
        }
        $this->error($number, 'subcategory', 'La subclasificación «' . $subcategory . '» no corresponde a «' . $canonical .
            '». Usa una de: ' . implode(', ', $options) . '.', $subcategory);
    }

    // Same date, donor and amount: in the file itself or already booked
    private function checkDuplicates(array $rows)
    {
        $seen = [];
        foreach ($rows as $row) {
            $key = $row['date'] . '|' . $row['donor_key'] . '|' . $row['amount'];
            if (isset($seen[$key])) {
                $this->warning($row['row'], null, 'Igual a la fila ' . $seen[$key] . ' (fecha, donador e importe): revisa que no esté repetida.');
            } else {
                $seen[$key] = $row['row'];
            }
        }

        $donors = array_values(array_unique(array_filter(array_column($rows, 'donor_id'))));
        if (!$donors) {
            return;
        }
        $dates = array_column($rows, 'date');
        $existing = [];
        Operation::select('id', 'contact_id', 'date', 'amount', 'history_operation_id')
            ->where(['company_id' => $this->companyId, 'type' => 'income', 'is_co' => 0, 'active' => 1])
            ->whereIn('contact_id', $donors)
            ->whereBetween('date', [min($dates), max($dates)])
            ->where('amount', '<>', '')
            ->orderBy('id')
            ->each(function ($operation) use (&$existing) {
                $key = $operation->contact_id . '|' . $operation->date . '|' . round((float) $operation->amount, 2);
                $existing[$key] = $existing[$key] ?? $operation;
            });

        foreach ($rows as $row) {
            $operation = $row['donor_id'] ? ($existing[$row['donor_id'] . '|' . $row['date'] . '|' . $row['amount']] ?? null) : null;
            if ($operation) {
                $this->warning($row['row'], null, 'Ya existe un ingreso de este donador con la misma fecha e importe (#' . $operation->id .
                    ($operation->history_operation_id ? ', carga #' . $operation->history_operation_id : '') . '): revisa que no sea el mismo.');
            }
        }
    }

    /*** CATALOGS ***/

    private function catalog()
    {
        $categories = [];
        foreach (config('operations.income_categories') as $category => $subcategories) {
            $categories[$this->key($category)] = $category;
        }

        $contacts = Contact::select('id', 'name', 'category')
            ->where(['company_id' => $this->companyId, 'active' => 1])
            ->orderBy('id')
            ->get();

        // Accounts used a few times, so a one-off typo (9745 for 9975) doesn't become a known account
        $accounts = Operation::where(['company_id' => $this->companyId, 'type' => 'income'])
            ->whereNotNull('account')->where('account', '<>', '')
            ->groupBy('account')->havingRaw('COUNT(*) >= 3')->pluck('account')
            ->merge(Account::where(['company_id' => $this->companyId, 'active' => 1])->pluck('account'))
            ->map(fn ($account) => trim((string) $account))->filter()->unique()->sort()->flip()->all();

        $fees = Category::where(['category' => 'account', 'type' => 'fee', 'active' => 1, 'company_id' => $this->companyId])
            ->pluck('name')->filter(fn ($fee) => is_numeric($fee))->map(fn ($fee) => round((float) $fee, 4))->values()->all();

        return [
            'categories' => $categories,
            'subcategories' => config('operations.income_categories'),
            'donors' => $contacts->where('category', 'Donador'),
            'others' => $contacts->where('category', '<>', 'Donador'),
            'accounts' => $accounts,
            'fees' => $fees,
            // donors that will be created, by key
            'new' => [],
        ];
    }

    /**
     * Options the template offers: active donors, known accounts and the classifications an import can use.
     */
    public function lists()
    {
        $catalog = $this->catalog();
        return [
            'donors' => $catalog['donors']->pluck('name')->map(fn ($name) => $this->clean((string) $name))->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all(),
            'accounts' => array_map('strval', array_keys($catalog['accounts'])),
            'categories' => array_diff_key($catalog['subcategories'], array_flip(config('operations.system_income_categories'))),
        ];
    }

    /**
     * Donor ids for a name (an exact match first, then ignoring case, accents and punctuation), the
     * category of a non donor contact with that name, and the closest donor name to suggest.
     */
    private function findDonor($name, array $catalog)
    {
        $lower = mb_strtolower($name);
        $key = $this->key($name);
        $ids = $catalog['donors']->filter(fn ($donor) => mb_strtolower($this->clean((string) $donor->name)) === $lower)->pluck('id')->values()->all();
        if (!$ids) {
            $ids = $catalog['donors']->filter(fn ($donor) => $this->key($donor->name) === $key)->pluck('id')->values()->all();
        }
        if ($ids) {
            return ['ids' => $ids, 'other' => null, 'suggestion' => null];
        }

        $other = $catalog['others']->first(fn ($contact) => $this->key($contact->name) === $key);
        $suggestion = null;
        $best = 0;
        foreach ($catalog['donors'] as $donor) {
            similar_text($key, $this->key($donor->name), $percent);
            if ($percent > $best) {
                $best = $percent;
                $suggestion = $donor->name;
            }
        }
        return ['ids' => [], 'other' => $other ? $other->category : null, 'suggestion' => $best >= 80 ? $this->clean($suggestion) : null];
    }

    /*** VALUES ***/

    private function clean($text)
    {
        $text = str_replace(['_x000D_', "\xC2\xA0"], ' ', (string) $text);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private function text($value)
    {
        if (is_null($value)) {
            return '';
        }
        if (is_float($value) && floor($value) == $value && abs($value) < 1e15) {
            return sprintf('%.0f', $value);
        }
        return $this->clean((string) $value);
    }

    private function blank($value)
    {
        return is_null($value) || (is_string($value) && trim($value) === '');
    }

    // Lowercase, without accents or punctuation, single spaced: how names and headers are compared
    private function key($text)
    {
        $text = strtolower(Str::ascii($this->clean((string) $text)));
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', $text)));
    }

    // 1234.5, "1,234.50" and "$ 1,234.50" are numbers; "1.234,50" isn't (which is the decimal separator?)
    private function number($value)
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $text = str_replace(['$', ' '], '', $this->text($value));
        if (!preg_match('/^-?(\d+|\d{1,3}(,\d{3})+)(\.\d+)?$/', $text)) {
            return null;
        }
        return (float) str_replace(',', '', $text);
    }

    // A percentage written as 10 or "10%"
    private function percent($value)
    {
        $number = $this->number(str_replace('%', '', $this->text($value)));
        return is_null($number) ? null : round($number, 6);
    }

    private function invalidPercent($value, $label)
    {
        $percent = $this->percent($value);
        if (is_null($percent)) {
            return $label . ' «' . $this->text($value) . '» no es un número.';
        }
        if ($percent < 0 || $percent > 100) {
            return $label . ' debe estar entre 0 y 100.';
        }
        // A cell with percentage format holds 0.15 for 15%: without the format it can't be told from 0.15%
        if ($percent > 0 && $percent < 1 && strpos($this->text($value), '%') === false) {
            return $label . ' ' . $this->text($value) . ' parece una celda con formato de porcentaje: escribe el número sin formato (15 para 15%).';
        }
        return null;
    }

    private function decimal($value, $decimals)
    {
        $text = number_format((float) $value, $decimals, '.', '');
        return strpos($text, '.') === false ? $text : rtrim(rtrim($text, '0'), '.');
    }

    private function displayDate($date)
    {
        return date('d/m/Y', strtotime($date));
    }

    /*** REPORT ***/

    private function error($row, $field, $message, $value = null)
    {
        $this->errors[] = $this->message($row, $field, $message, $value);
    }

    private function warning($row, $field, $message, $value = null)
    {
        $this->warnings[] = $this->message($row, $field, $message, $value);
    }

    private function message($row, $field, $message, $value)
    {
        $index = $field ? array_search($field, array_keys(self::COLUMNS)) : false;
        return [
            'row' => $row,
            'column' => $index === false ? null : Coordinate::stringFromColumnIndex($index + 1),
            'field' => $field ? self::COLUMNS[$field][0] : null,
            'value' => is_null($value) ? null : $this->text($value),
            'message' => $message,
        ];
    }

    private function result(array $rows, $dataRows, $emptyRows)
    {
        $dates = array_column($rows, 'date');
        $categories = [];
        foreach ($rows as $row) {
            $key = $row['category'] . '|' . $row['subcategory'];
            $categories[$key] = $categories[$key] ?? ['category' => $row['category'], 'subcategory' => $row['subcategory'], 'count' => 0, 'amount' => 0];
            $categories[$key]['count']++;
            $categories[$key]['amount'] = round($categories[$key]['amount'] + $row['amount'], 2);
        }

        return [
            'rows' => $rows,
            'errors' => array_slice($this->errors, 0, self::MAX_MESSAGES),
            'errors_total' => count($this->errors),
            'warnings' => array_slice($this->warnings, 0, self::MAX_MESSAGES),
            'warnings_total' => count($this->warnings),
            'summary' => [
                'data_rows' => $dataRows,
                'valid_rows' => count($rows),
                'empty_rows' => $emptyRows,
                'total_amount' => round(array_sum(array_column($rows, 'amount')), 2),
                'total_fee' => round(array_sum(array_column($rows, 'fee_amount')), 2),
                'total_co' => round(array_sum(array_column($rows, 'co_amount')), 2),
                'with_co' => count(array_filter(array_column($rows, 'co'))),
                'period_from' => $dates ? min($dates) : null,
                'period_to' => $dates ? max($dates) : null,
                'donors_new' => array_values(array_unique(array_column(array_filter($rows, fn ($row) => !$row['donor_id']), 'donor_name'))),
                'categories' => array_values($categories),
            ],
        ];
    }
}
