<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes incomes with an impossible date (before 2000 or none, mostly 1970-01-01) that carry no money:
 * the old income import turned every empty row of a layout into one of them. Only rows whose amount and
 * operating cost add nothing to any sum are removed, and if any balance would still change the migration
 * fails without removing anything. Removed rows are kept in operations_purged, which down() restores.
 */
return new class extends Migration
{
    const BACKUP = 'operations_purged';
    const BATCH = '2026_10_27_000001_purge_incomes_with_invalid_date';
    const MIN_DATE = '2000-01-01';

    public function up()
    {
        if (!Schema::hasTable(self::BACKUP)) {
            DB::statement('CREATE TABLE ' . self::BACKUP . ' LIKE operations');
            Schema::table(self::BACKUP, function (Blueprint $table) {
                $table->timestamp('purged_at')->nullable();
                $table->string('purged_by', 100)->nullable()->index();
            });
        }

        $result = $this->purge();
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            echo PHP_EOL . '  Ingresos con fecha errónea eliminados: ' . $result['purged'] . ' (respaldo en ' . self::BACKUP . ').' . PHP_EOL;
            if ($result['skipped']) {
                echo '  Quedan ' . count($result['skipped']) . ' con fecha errónea porque tienen importe u otro movimiento ligado (corrígelos a mano): ' .
                    implode(', ', $result['skipped']) . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $this->restore();
        if (Schema::hasTable(self::BACKUP) && !DB::table(self::BACKUP)->exists()) {
            Schema::drop(self::BACKUP);
        }
    }

    /**
     * Moves the incomes to the backup table. Data only (no schema change), so a test can run it inside
     * its transaction. Returns how many were removed and the ids left because they have an amount.
     */
    public function purge()
    {
        return DB::transaction(function () {
            $rows = $this->invalidDates()
                ->whereRaw($this->addsNothing('o.amount'))
                ->whereRaw($this->addsNothing('o.val_operating_cost'))
                // another movement points at it (operating cost of an income): leave both
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))->from('operations as r')->whereColumn('r.movement_id', 'o.id');
                })
                ->get(['o.id', 'o.history_operation_id']);
            $skipped = $this->invalidDates()->whereNotIn('o.id', $rows->pluck('id'))->orderBy('o.id')->pluck('o.id')->all();
            if ($rows->isEmpty()) {
                return ['purged' => 0, 'skipped' => $skipped];
            }

            $before = $this->balances();
            $columns = $this->sharedColumns();
            foreach ($rows->pluck('id')->chunk(1000) as $ids) {
                DB::table(self::BACKUP)->insertUsing(
                    array_merge($columns, ['purged_at', 'purged_by']),
                    DB::table('operations')->select(array_merge($columns, [DB::raw('NOW()'), DB::raw("'" . self::BATCH . "'")]))->whereIn('id', $ids)
                );
                DB::table('operations')->whereIn('id', $ids)->delete();
            }
            if ($this->balances() !== $before) {
                throw new \RuntimeException('Eliminar los ingresos con fecha errónea cambiaría algún saldo: no se eliminó ninguno.');
            }

            $this->recount($rows->pluck('history_operation_id'));
            return ['purged' => $rows->count(), 'skipped' => $skipped];
        });
    }

    // Puts back what purge() removed
    public function restore()
    {
        if (!Schema::hasTable(self::BACKUP)) {
            return 0;
        }

        return DB::transaction(function () {
            $rows = DB::table(self::BACKUP)->where('purged_by', self::BATCH);
            $histories = (clone $rows)->pluck('history_operation_id');
            $count = (clone $rows)->count();
            if ($count) {
                $columns = $this->sharedColumns();
                DB::table('operations')->insertUsing($columns, (clone $rows)->select($columns));
                (clone $rows)->delete();
                $this->recount($histories);
            }
            return $count;
        });
    }

    private function invalidDates()
    {
        return DB::table('operations as o')
            ->where('o.type', 'income')
            ->where(function ($query) {
                $query->whereNull('o.date')->orWhere('o.date', '<', self::MIN_DATE);
            });
    }

    // Empty, zero or not a number: what the reports' sums count as 0
    private function addsNothing($column)
    {
        return '(IFNULL(' . $column . ', 0) + 0) = 0';
    }

    /**
     * Sums of amount, fee, monitoring and operating cost for every combination the balances group by
     * (company, type, contact, donor, project, classification, account...). Groups that add up to zero
     * are left out: removing rows that carry no money can only make such a group disappear.
     */
    private function balances()
    {
        $number = fn ($column) => '(IFNULL(' . $column . ', 0) + 0)';
        return DB::table('operations')
            ->selectRaw('company_id, type, active, transfer, is_co, contact_id, donor_id, project_id, account_id, category, subcategory,
                ROUND(SUM(' . $number('amount') . '), 2) AS amount,
                ROUND(SUM(' . $number('amount') . ' * ' . $number('fee') . ' / 100), 2) AS fee,
                ROUND(SUM(' . $number('amount') . ' * ' . $number('monitoring') . ' / 100), 2) AS monitoring,
                ROUND(SUM(' . $number('val_operating_cost') . '), 2) AS co')
            ->groupBy('company_id', 'type', 'active', 'transfer', 'is_co', 'contact_id', 'donor_id', 'project_id', 'account_id', 'category', 'subcategory')
            ->get()
            ->reject(fn ($row) => $row->amount == 0 && $row->fee == 0 && $row->monitoring == 0 && $row->co == 0)
            ->map(fn ($row) => json_encode($row))
            ->sort()
            ->values()
            ->all();
    }

    // Counts of the imports that lost (or got back) operations
    private function recount($histories)
    {
        $histories = collect($histories)->filter()->unique();
        if ($histories->isEmpty() || !Schema::hasColumn('history_operations', 'rows_count')) {
            return;
        }
        foreach ($histories as $id) {
            $operations = DB::table('operations')->where('history_operation_id', $id);
            DB::table('history_operations')->where('id', $id)->update([
                'rows_count' => (clone $operations)->where(['type' => 'income', 'is_co' => 0])->count(),
                'operations_count' => (clone $operations)->count(),
            ]);
        }
    }

    private function sharedColumns()
    {
        return array_values(array_intersect(Schema::getColumnListing('operations'), Schema::getColumnListing(self::BACKUP)));
    }
};
