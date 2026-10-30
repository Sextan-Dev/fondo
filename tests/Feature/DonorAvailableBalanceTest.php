<?php

namespace Tests\Feature;

use App\Contact;
use App\Http\Controllers\OperationController;
use App\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Importe Máximo of Nueva Operación: it must equal the balance of the donor in Saldo Donantes, money in projects
 * included. Works inside a rolled back transaction, like MigrationWorkflowTest.
 */
class DonorAvailableBalanceTest extends TestCase
{
    private Contact $donor;

    protected function setUp(): void
    {
        if (getenv('FONDO_MIGRATION_TEST') !== '1') {
            $this->markTestSkipped('Requires an isolated restored Fondo database.');
        }
        parent::setUp();
        $this->assertTrue(app()->environment('testing'));
        DB::beginTransaction();
        $this->donor = Contact::create(['name' => 'Donador saldo', 'category' => 'Donador', 'company_id' => 1, 'active' => 1]);
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

    private function project(): int
    {
        return DB::table('projects')->insertGetId(['name' => 'Proyecto saldo', 'active' => 1]);
    }

    private function operation(array $data): Operation
    {
        return Operation::create($data + ['date' => '2026-02-01', 'company_id' => 1, 'active' => 1, 'transfer' => 0, 'project_id' => 0,
            'category' => 'Corporativo', 'subcategory' => 'Nacional']);
    }

    private function income(float $amount, array $data = []): Operation
    {
        return $this->operation($data + ['type' => 'income', 'contact_id' => $this->donor->id, 'amount' => $amount]);
    }

    private function outcome(float $amount, array $data = []): Operation
    {
        return $this->operation($data + ['type' => 'outcome', 'contact_id' => 1, 'donor_id' => $this->donor->id, 'amount' => $amount]);
    }

    private function fund(int $project, float $amount, string $subcategory = 'Nacional'): void
    {
        $this->outcome($amount, ['transfer' => 1, 'contact_id' => $this->donor->id, 'subcategory' => $subcategory]);
        $this->income($amount, ['transfer' => 1, 'donor_id' => $this->donor->id, 'project_id' => $project, 'subcategory' => $subcategory]);
    }

    private function balance(string $resource, array $filters = []): array
    {
        $result = (new OperationController)->balanceAccount(Request::create('/', 'GET', $filters + [
            'contact_id' => $this->donor->id, 'resource' => $resource, 'category' => 'Corporativo',
        ]));
        $this->assertTrue($result['success']);

        return $result['items'];
    }

    private function available(string $subcategory = 'Nacional'): float
    {
        return (float) collect($this->balance('subcategories'))->firstWhere('subcategory', $subcategory)['totalAvailable'];
    }

    private function projects(): float
    {
        return (float) collect($this->balance('projects', ['subcategory' => 'Nacional']))->sum('totalAvailable');
    }

    public function test_outcome_of_a_project_that_received_nothing_reduces_the_available_balance(): void
    {
        $this->income(1000, ['fee' => '10']);
        $this->outcome(100);
        $this->outcome(300, ['project_id' => $this->project()]);

        $this->assertEqualsWithDelta(500, $this->available(), 0.001);
        $this->assertEqualsWithDelta(0, $this->projects(), 0.001);
    }

    public function test_money_in_a_project_stays_in_the_available_balance(): void
    {
        $project = $this->project();
        $this->income(1000);
        $this->fund($project, 400);
        $this->outcome(150, ['project_id' => $project]);

        $this->assertEqualsWithDelta(850, $this->available(), 0.001);
        $this->assertEqualsWithDelta(250, $this->projects(), 0.001);
    }

    public function test_each_classification_keeps_its_own_balance(): void
    {
        $project = $this->project();
        $this->income(1000);
        $this->income(1000, ['subcategory' => 'Internacional']);
        $this->fund($project, 400, 'Internacional');
        $this->outcome(100, ['project_id' => $project]);

        $this->assertEqualsWithDelta(900, $this->available(), 0.001);
        $this->assertEqualsWithDelta(1000, $this->available('Internacional'), 0.001);
    }

    public function test_transfers_received_keep_their_full_amount(): void
    {
        $this->income(1000, ['fee' => '10']);
        $this->income(500, ['transfer' => 1, 'donor_id' => 1, 'fee' => '10']);

        $this->assertEqualsWithDelta(1400, $this->available(), 0.001);
    }

    public function test_available_balance_can_go_below_zero(): void
    {
        $this->income(100);
        $this->outcome(250, ['project_id' => $this->project()]);

        $this->assertEqualsWithDelta(-150, $this->available(), 0.001);
    }
}
