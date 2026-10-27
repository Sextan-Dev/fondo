<?php

namespace Tests\Feature;

use App\Contact;
use App\Operation;
use App\OperationHistory;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Migration 2026_10_27_000001: incomes with an impossible date and no money go, everything else stays and no
 * balance moves. Runs its data part (purge/restore) inside a rolled back transaction, like MigrationWorkflowTest.
 */
class PurgeInvalidIncomeDatesTest extends TestCase
{
    private $migration;
    private Contact $donor;

    protected function setUp(): void
    {
        if (getenv('FONDO_MIGRATION_TEST') !== '1') {
            $this->markTestSkipped('Requires an isolated restored Fondo database.');
        }
        parent::setUp();
        $this->assertTrue(app()->environment('testing'));
        DB::beginTransaction();
        $this->migration = require base_path('database/migrations/2026_10_27_000001_purge_incomes_with_invalid_date.php');
        $this->donor = Contact::create(['name' => 'Donador fecha errónea', 'category' => 'Donador', 'company_id' => 1, 'active' => 1]);
    }

    protected function tearDown(): void
    {
        if (isset($this->app)) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        parent::tearDown();
    }

    private function operation(array $data): Operation
    {
        return Operation::create($data + ['type' => 'income', 'contact_id' => $this->donor->id, 'company_id' => 1, 'active' => 1,
            'category' => 'AVN', 'subcategory' => 'AVN', 'fee' => '10', 'import_key' => 'incomes-20240101000000']);
    }

    private function donorBalance(): array
    {
        return array_map('floatval', (array) DB::table('operations')
            ->selectRaw('SUM(IFNULL(amount, 0) + 0) AS amount, SUM((IFNULL(amount, 0) + 0) * (IFNULL(fee, 0) + 0) / 100) AS fee, SUM(IFNULL(val_operating_cost, 0) + 0) AS co')
            ->where('contact_id', $this->donor->id)->orWhere('donor_id', $this->donor->id)
            ->first());
    }

    public function test_only_moneyless_incomes_with_an_impossible_date_are_removed(): void
    {
        $history = OperationHistory::create(['company_id' => 1, 'type' => 'incomes', 'status' => 1, 'rows_count' => 4, 'operations_count' => 4]);
        $empty = $this->operation(['date' => '1970-01-01', 'amount' => '', 'history_operation_id' => $history->id]);
        $zero = $this->operation(['date' => null, 'amount' => '0']);
        $withMoney = $this->operation(['date' => '1970-01-01', 'amount' => '500', 'history_operation_id' => $history->id]);
        $linked = $this->operation(['date' => '1970-01-01', 'amount' => '']);
        $this->operation(['date' => '2024-02-01', 'amount' => '10', 'movement_id' => $linked->id, 'is_co' => 1, 'type' => 'outcome']);
        $valid = $this->operation(['date' => '2024-02-01', 'amount' => '100', 'history_operation_id' => $history->id]);
        $outcome = $this->operation(['date' => '1970-01-01', 'amount' => '', 'type' => 'outcome']);
        $this->operation(['date' => '2024-02-01', 'amount' => '', 'history_operation_id' => $history->id]);
        $balance = $this->donorBalance();

        $result = $this->migration->purge();

        $this->assertSame($balance, $this->donorBalance());
        $this->assertNull(Operation::find($empty->id));
        $this->assertNull(Operation::find($zero->id));
        foreach ([$withMoney, $linked, $valid, $outcome] as $kept) {
            $this->assertNotNull(Operation::find($kept->id));
        }
        $this->assertContains($withMoney->id, $result['skipped']);
        $this->assertContains($linked->id, $result['skipped']);
        $this->assertSame(2, DB::table('operations_purged')->whereIn('id', [$empty->id, $zero->id])->count());
        $history->refresh();
        $this->assertSame([3, 3], [(int) $history->rows_count, (int) $history->operations_count]);

        // down() puts them back as they were
        $this->assertGreaterThanOrEqual(2, $this->migration->restore());
        $restored = Operation::find($empty->id);
        $this->assertSame(['1970-01-01', '', $history->id], [$restored->date, $restored->amount, (int) $restored->history_operation_id]);
        $this->assertNotNull(Operation::find($zero->id));
        $this->assertSame(0, DB::table('operations_purged')->whereIn('id', [$empty->id, $zero->id])->count());
        $this->assertSame([4, 4], [(int) $history->fresh()->rows_count, (int) $history->fresh()->operations_count]);
    }

    public function test_nothing_to_remove_changes_nothing(): void
    {
        $this->operation(['date' => '2024-02-01', 'amount' => '100']);
        $count = Operation::count();
        $purged = DB::table('operations_purged')->count();
        $result = $this->migration->purge();
        $this->assertSame([0, $count, $purged], [$result['purged'], Operation::count(), DB::table('operations_purged')->count()]);
    }
}
