<?php

namespace App\Console\Commands;

use App\Contact;
use App\Operation;
use App\OperationHistory;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Income imports booked before history_operation_id existed: their history row kept the uploaded file
 * name (incomes_YmdHis.xlsx) and their operations another key (incomes-YmdHis, a few seconds later),
 * so rolling them back removed nothing. This finds each import's operations and donors and stamps them.
 */
class LinkIncomeImports extends Command
{
    protected $signature = 'imports:link-history {--dry-run : Show what would be linked without saving anything}';

    protected $description = 'Link earlier income imports to the operations and donors they created, so they can be rolled back';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $rows = [];
        $linked = 0;

        foreach (OperationHistory::whereNull('type')->orderBy('id')->get() as $history) {
            $result = $this->link($history, $dryRun);
            $linked += $result['key'] ? 1 : 0;
            $rows[] = [$history->id, $history->original_name, $result['key'] ?: '-', $result['incomes'], $result['operations'], $result['donors'], $result['note']];
        }

        $this->table(['Carga', 'Archivo', 'Clave de los registros', 'Ingresos', 'Registros', 'Donadores', 'Nota'], $rows);
        $this->info(($dryRun ? 'Se enlazarían ' : 'Se enlazaron ') . $linked . ' de ' . count($rows) . ' cargas.');
        return 0;
    }

    private function link(OperationHistory $history, $dryRun)
    {
        $result = ['key' => null, 'incomes' => 0, 'operations' => 0, 'donors' => 0, 'note' => ''];
        if (!preg_match('/^incomes_(\d{14})\.xlsx$/', (string) $history->import_key, $match)) {
            return ['note' => 'Clave no reconocida: ' . $history->import_key] + $result;
        }

        $companyId = User::where('id', $history->user_id)->value('company_id') ?: 1;
        // The file was stamped before the import began and the history row saved after it ended
        $from = Carbon::createFromFormat('YmdHis', $match[1]);
        $to = Carbon::parse($history->date)->addSeconds(5);
        if ($to->lt($from)) {
            $to = $from->copy()->addMinutes(15);
        }

        $keys = Operation::where('company_id', $companyId)
            ->whereNull('history_operation_id')
            ->whereBetween('import_key', ['incomes-' . $from->format('YmdHis'), 'incomes-' . $to->format('YmdHis')])
            ->distinct()
            ->pluck('import_key');
        if ($keys->count() != 1) {
            return ['note' => $keys->isEmpty() ? 'Sin registros' : 'Varias claves posibles: ' . $keys->implode(', ')] + $result;
        }

        $key = $keys->first();
        $operations = Operation::where(['company_id' => $companyId, 'import_key' => $key]);
        $incomes = (clone $operations)->where(['type' => 'income', 'is_co' => 0]);
        // Donors created while the import ran and used by it
        $donors = Contact::where(['company_id' => $companyId, 'category' => 'Donador'])
            ->whereNull('history_operation_id')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('id', (clone $incomes)->select('contact_id'))
            ->pluck('id');

        $totals = (clone $incomes)->selectRaw('
                COUNT(*) AS incomes,
                SUM(CAST(IF(amount = "", 0, amount) AS DECIMAL(20,2))) AS amount,
                SUM(CAST(IF(amount = "", 0, amount) AS DECIMAL(20,2)) * CAST(IF(IFNULL(fee, "") = "", 0, fee) AS DECIMAL(20,6)) / 100) AS fee,
                SUM(CAST(IF(IFNULL(val_operating_cost, "") = "", 0, val_operating_cost) AS DECIMAL(20,2))) AS co,
                MIN(IF(amount = "", NULL, date)) AS period_from,
                MAX(IF(amount = "", NULL, date)) AS period_to
            ')->first();
        $count = (clone $operations)->count();

        if (!$dryRun) {
            DB::transaction(function () use ($history, $operations, $donors, $totals, $count, $companyId, $key) {
                (clone $operations)->update(['history_operation_id' => $history->id]);
                Contact::whereIn('id', $donors)->update(['history_operation_id' => $history->id]);
                $history->fill([
                    'company_id' => $companyId,
                    'type' => 'incomes',
                    'import_key' => $key,
                    'rows_count' => $totals->incomes,
                    'operations_count' => $count,
                    'total_amount' => round((float) $totals->amount, 2),
                    'total_fee' => round((float) $totals->fee, 2),
                    'total_co' => round((float) $totals->co, 2),
                    'donors_created' => $donors->count(),
                    'period_from' => $totals->period_from,
                    'period_to' => $totals->period_to,
                ])->save();
            });
        }

        return ['key' => $key, 'incomes' => $totals->incomes, 'operations' => $count, 'donors' => $donors->count(), 'note' => ''];
    }
}
