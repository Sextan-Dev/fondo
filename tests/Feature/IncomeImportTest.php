<?php

namespace Tests\Feature;

use App\Contact;
use App\Operation;
use App\OperationHistory;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Income layout import: template, check, confirm, history and rollback. Runs against a restored
 * Fondo database inside a transaction that is rolled back, like MigrationWorkflowTest.
 */
class IncomeImportTest extends TestCase
{
    const HEADERS = ['Fecha', 'Donador', 'Importe', 'Fee %', 'Clasificación', 'Subclasificación', 'Comentarios', 'Cuenta', '% Costo operativo'];

    private User $operator;
    private string $token;
    private Contact $donor;
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        if (getenv('FONDO_MIGRATION_TEST') !== '1') {
            $this->markTestSkipped('Requires an isolated restored Fondo database.');
        }
        parent::setUp();
        $this->assertTrue(app()->environment('testing'));
        DB::beginTransaction();
        Storage::fake('local');
        $this->operator = User::create([
            'user' => 'import_'.bin2hex(random_bytes(6)),
            'name' => 'Import test',
            'email' => 'import@example.test',
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'company_id' => 1,
            'group_id' => DB::table('user_groups')->whereRaw('LOWER(name) = ?', ['admin'])->value('id'),
            'role_id' => DB::table('user_roles')->where('slug', 'admin')->value('id'),
            'menu_ids' => '', 'active' => 1, 'action_delete' => 1,
        ]);
        $this->token = JWTAuth::fromUser($this->operator);
        $this->donor = Contact::create(['name' => 'Donador Importación '.bin2hex(random_bytes(4)), 'category' => 'Donador', 'company_id' => 1, 'active' => 1]);
    }

    protected function tearDown(): void
    {
        if (isset($this->app)) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    private function api(string $path, array $payload = [])
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token)->post('/api/'.$path, $payload, ['Accept' => 'application/json']);
    }

    // An xlsx with the template headers and these rows; returns its path
    private function layout(array $rows, ?array $headers = null): string
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray(array_merge([$headers ?? self::HEADERS], $rows), null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'fondo-income-').'.xlsx';
        $this->temporaryFiles[] = $path;
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        return $path;
    }

    private function upload(string $path): UploadedFile
    {
        return new UploadedFile($path, 'LAYOUT PRUEBA.xlsx', null, null, true);
    }

    private function row(array $changes = []): array
    {
        $row = ['10/02/2026', $this->donor->name, 1000, 10, 'AVN', 'AVN', 'Donativos de prueba', '9975', null];
        foreach ($changes as $index => $value) {
            $row[$index] = $value;
        }
        return $row;
    }

    private function book(array $rows, bool $createDonors = false): OperationHistory
    {
        return $this->bookFile($this->layout($rows), $createDonors);
    }

    private function bookFile(string $path, bool $createDonors = false): OperationHistory
    {
        $check = $this->api('operation/import/incomes/check', ['incomes' => $this->upload($path), 'create_donors' => $createDonors ? 1 : 0]);
        $this->assertTrue($check->json('canImport'), json_encode($check->json('result.errors')));
        $confirm = $this->api('operation/import/incomes/confirm', ['token' => $check->json('token')]);
        $this->assertTrue($confirm->json('success') === true, (string) $confirm->json('msg'));
        return OperationHistory::findOrFail($confirm->json('history.id'));
    }

    public function test_template_has_the_import_columns_and_lists(): void
    {
        $response = $this->api('operation/import/incomes/template');
        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'fondo-template-').'.xlsx';
        $this->temporaryFiles[] = $path;
        $this->temporaryFiles[] = $response->baseResponse->getFile()->getPathname();
        copy($response->baseResponse->getFile()->getPathname(), $path);

        // Data only: loading the validations creates one object per cell of their 5000 rows
        $book = IOFactory::createReader('Xlsx')->setReadDataOnly(true)->load($path);
        $this->assertSame(['Ingresos', 'Instrucciones', 'Listas'], $book->getSheetNames());
        $this->assertSame([self::HEADERS], $book->getSheetByName('Ingresos')->rangeToArray('A1:I1'));
        $this->assertContains($this->donor->name, array_column($book->getSheetByName('Listas')->toArray(), 0));
        $book->disconnectWorksheets();
        $zip = new \ZipArchive();
        $zip->open($path);
        $this->assertSame(9, substr_count($zip->getFromName('xl/worksheets/sheet1.xml'), '<dataValidation '));
        $zip->close();

        // An empty template is not an import
        $this->actingAs($this->operator);
        $result = (new \App\Imports\Operations\IncomeImport())->validate($path);
        $this->assertSame(1, $result['errors_total']);
    }

    public function test_a_layout_with_errors_books_nothing_and_reports_each_row(): void
    {
        $operations = Operation::count();
        $response = $this->api('operation/import/incomes/check', ['incomes' => $this->upload($this->layout([
            $this->row(),
            $this->row([0 => '31/02/2026']),
            $this->row([1 => 'Donador que no existe '.bin2hex(random_bytes(3))]),
            $this->row([2 => 'mil']),
            $this->row([3 => null]),
            array_fill(0, 9, null),
            $this->row([4 => 'Corporativo', 5 => 'AVN']),
            $this->row([4 => 'Costo Operativo', 5 => 'Costo Operativo']),
            $this->row([7 => null]),
            $this->row([8 => 150]),
        ]))]);

        $response->assertOk()->assertJson(['success' => true, 'canImport' => false, 'token' => null]);
        $errors = collect($response->json('result.errors'))->map(fn ($error) => $error['row'].$error['column'])->all();
        $this->assertSame(['3A', '4B', '5C', '6D', '8F', '9E', '10H', '11I'], $errors);
        $this->assertSame(1, $response->json('result.summary.empty_rows'));
        $this->assertSame($operations, Operation::count());
        $this->assertFalse(OperationHistory::where('user_id', $this->operator->id)->exists());
        $this->assertSame([], Storage::disk('local')->files('imports/pending'));
    }

    public function test_wrong_headers_are_rejected_before_reading_rows(): void
    {
        $response = $this->api('operation/import/incomes/check', ['incomes' => $this->upload($this->layout(
            [$this->row()],
            ['Fecha', 'Donador', 'Importe', 'Fee', 'Subclasificación', 'Clasificación', 'Comentarios', 'Cuenta', '']
        ))]);
        $this->assertFalse($response->json('canImport'));
        $this->assertSame([1, 1, null], array_column($response->json('result.errors'), 'row'));
        $this->assertSame(0, $response->json('result.summary.data_rows'));
    }

    public function test_confirmed_layout_is_booked_and_stamped_with_its_import(): void
    {
        $newDonor = 'Donador Nuevo '.bin2hex(random_bytes(4));
        $history = $this->book([
            $this->row([0 => 46063, 2 => '$1,500.00', 3 => '15%', 4 => 'corporativo', 5 => 'NACIONAL', 8 => 10]),
            $this->row([1 => $newDonor, 2 => 250.5, 3 => 0, 7 => 4578]),
            $this->row([1 => $newDonor, 2 => 100]),
        ], true);

        $this->assertSame(1, (int) $history->company_id);
        $this->assertSame('incomes', $history->type);
        $this->assertSame(3, (int) $history->rows_count);
        $this->assertSame(5, (int) $history->operations_count);
        $this->assertEqualsWithDelta(1850.5, (float) $history->total_amount, 0.001);
        $this->assertEqualsWithDelta(235, (float) $history->total_fee, 0.001);
        $this->assertSame(1, (int) $history->donors_created);
        $this->assertSame(['2026-02-10', '2026-02-10'], [$history->period_from, $history->period_to]);
        Storage::disk('local')->assertExists($history->file_path);
        $this->assertSame([], Storage::disk('local')->files('imports/pending'));

        $operations = Operation::where('history_operation_id', $history->id)->get();
        $this->assertCount(5, $operations);
        $income = $operations->firstWhere('amount', '1500');
        $this->assertSame(['Corporativo', 'Nacional', '15', '9975'], [$income->category, $income->subcategory, $income->fee, $income->account]);
        $this->assertEqualsWithDelta(calculatedRetainAmount(1500, 15, 10), (float) $income->val_operating_cost, 0.001);
        // Each income's operating cost movements share a reference with it alone
        $this->assertCount(3, $operations->where('transfer_reference', $income->transfer_reference));
        $this->assertCount(2, $operations->where('movement_id', $income->id)->where('is_co', 1));

        $created = Contact::where('history_operation_id', $history->id)->get();
        $this->assertSame([$newDonor], $created->pluck('name')->all());
        $this->assertCount(2, $operations->where('contact_id', $created->first()->id));
        $this->assertSame('4578', $operations->firstWhere('amount', '250.5')->account);
    }

    public function test_the_same_file_cannot_be_booked_twice(): void
    {
        $path = $this->layout([$this->row()]);
        $first = $this->api('operation/import/incomes/check', ['incomes' => $this->upload($path)]);
        $this->assertTrue($this->api('operation/import/incomes/confirm', ['token' => $first->json('token')])->json('success'));
        // the token was used up with its file
        $this->assertFalse($this->api('operation/import/incomes/confirm', ['token' => $first->json('token')])->json('success'));

        $again = $this->api('operation/import/incomes/check', ['incomes' => $this->upload($path)]);
        $this->assertFalse($again->json('canImport'));
        $this->assertStringContainsString('ya se cargó', $again->json('result.errors.0.message'));
    }

    public function test_confirmation_needs_the_users_own_valid_token(): void
    {
        $check = $this->api('operation/import/incomes/check', ['incomes' => $this->upload($this->layout([$this->row()]))]);
        $this->assertFalse($this->api('operation/import/incomes/confirm', ['token' => 'forged'])->json('success'));

        $other = User::create(['user' => 'import_other_'.bin2hex(random_bytes(4)), 'name' => 'Other', 'email' => 'other@example.test',
            'password' => Hash::make('x'), 'company_id' => 1, 'group_id' => $this->operator->group_id, 'role_id' => $this->operator->role_id,
            'menu_ids' => '', 'active' => 1]);
        $this->token = JWTAuth::fromUser($other);
        $this->assertFalse($this->api('operation/import/incomes/confirm', ['token' => $check->json('token')])->json('success'));
    }

    public function test_reverting_removes_the_imports_operations_and_unused_donors(): void
    {
        $newDonor = 'Donador Reversión '.bin2hex(random_bytes(4));
        $path = $this->layout([$this->row([8 => 10]), $this->row([1 => $newDonor])]);
        $history = $this->bookFile($path, true);
        $untouched = Operation::create(['date' => '2026-02-10', 'type' => 'income', 'amount' => 5, 'contact_id' => $this->donor->id, 'active' => 1, 'company_id' => 1]);

        $show = $this->api('operation/history/show', ['id' => $history->id])->json('impact');
        $this->assertSame(['incomes' => 2, 'operating_cost' => 2, 'operations' => 4, 'donors_to_deactivate' => 1],
            array_intersect_key($show, array_flip(['incomes', 'operating_cost', 'operations', 'donors_to_deactivate'])));

        $this->assertFalse($this->api('operation/history/delete', ['id' => $history->id, 'reason' => ''])->json('success'));
        $this->operator->update(['action_delete' => 0]);
        $this->assertFalse($this->api('operation/history/delete', ['id' => $history->id, 'reason' => 'Layout equivocado'])->json('success'));
        $this->operator->update(['action_delete' => 1]);

        $response = $this->api('operation/history/delete', ['id' => $history->id, 'reason' => 'Layout equivocado']);
        $this->assertTrue($response->json('success') === true, (string) $response->json('msg'));
        $this->assertSame(0, Operation::where('history_operation_id', $history->id)->count());
        $this->assertNotNull($untouched->fresh());
        $this->assertSame(0, (int) Contact::where('name', $newDonor)->value('active'));
        $this->assertSame(1, (int) $this->donor->fresh()->active);
        $history->refresh();
        $this->assertSame([0, 'Layout equivocado', $this->operator->id], [(int) $history->status, $history->revert_reason, (int) $history->user_id_delete]);

        $this->assertFalse($this->api('operation/history/delete', ['id' => $history->id, 'reason' => 'Otra vez'])->json('success'));
        // reverted, the same file can be booked again
        $this->assertTrue($this->api('operation/import/incomes/check', ['incomes' => $this->upload($path), 'create_donors' => 1])->json('canImport'));
    }

    public function test_a_created_donor_used_after_the_import_stays_active(): void
    {
        $newDonor = 'Donador Usado '.bin2hex(random_bytes(4));
        $history = $this->book([$this->row([1 => $newDonor])], true);
        $donor = Contact::where('history_operation_id', $history->id)->firstOrFail();
        Operation::create(['date' => '2026-03-01', 'type' => 'income', 'amount' => 10, 'contact_id' => $donor->id, 'active' => 1, 'company_id' => 1]);

        $this->assertSame(0, $this->api('operation/history/show', ['id' => $history->id])->json('impact.donors_to_deactivate'));
        $this->api('operation/history/delete', ['id' => $history->id, 'reason' => 'Prueba de donador usado']);
        $this->assertSame(1, (int) $donor->fresh()->active);
    }

    public function test_operation_list_shows_one_import_whatever_the_period(): void
    {
        $history = $this->book([$this->row(), $this->row([0 => '15/01/2025', 2 => 300])]);
        $response = $this->api('operation/select', ['category' => 'all', 'project' => 'all', 'contact' => 'all',
            'from' => '2026-10-01', 'to' => '2026-10-31', 'history' => $history->id, 'length' => 10]);
        $this->assertSame(2, $response->json('recordsFiltered'));
        $this->assertSame([$history->id, $history->id], array_map('intval', array_column($response->json('data'), 'history_operation_id')));
    }

    public function test_history_lists_only_the_companys_imports(): void
    {
        $own = $this->book([$this->row()]);
        $foreign = OperationHistory::create(['company_id' => 999999, 'type' => 'incomes', 'user_id' => $this->operator->id, 'status' => 1, 'original_name' => 'ajeno.xlsx']);
        $ids = array_column($this->api('operation/history/select', ['from' => '2000-01-01', 'to' => '2100-01-01', 'length' => -1])->json('data'), 'id');
        $this->assertContains($own->id, array_map('intval', $ids));
        $this->assertNotContains($foreign->id, array_map('intval', $ids));
        $this->assertFalse($this->api('operation/history/show', ['id' => $foreign->id])->json('success'));
        $this->assertFalse($this->api('operation/history/delete', ['id' => $foreign->id, 'reason' => 'No es mío'])->json('success'));
    }

    public function test_one_step_upload_books_valid_layouts_only(): void
    {
        $operations = Operation::count();
        $invalid = $this->api('operation/import/masive/incomes', ['incomes' => $this->upload($this->layout([$this->row([2 => 'mil'])]))]);
        $this->assertFalse($invalid->json('success'));
        $this->assertStringContainsString('Fila 2, Importe', $invalid->json('msg'));
        $this->assertSame($operations, Operation::count());

        $valid = $this->api('operation/import/masive/incomes', ['incomes' => $this->upload($this->layout([$this->row([1 => 'Donador Legacy '.bin2hex(random_bytes(3))])]))]);
        $this->assertTrue($valid->json('success') === true, (string) $valid->json('msg'));
        $this->assertSame($operations + 1, Operation::count());
    }

    public function test_earlier_imports_are_linked_to_their_operations(): void
    {
        $history = OperationHistory::create(['user_id' => $this->operator->id, 'status' => 1, 'date' => '2024-03-05 10:00:09',
            'import_key' => 'incomes_20240305100002.xlsx', 'original_name' => 'antiguo.xlsx']);
        $donor = Contact::create(['name' => 'Donador antiguo '.bin2hex(random_bytes(3)), 'category' => 'Donador', 'company_id' => 1, 'active' => 1]);
        DB::table('contacts')->where('id', $donor->id)->update(['created_at' => '2024-03-05 10:00:05']);
        foreach ([['income', 0, '100', '10'], ['income', 0, '50', '0'], ['outcome', 1, '5', '10']] as [$type, $co, $amount, $fee]) {
            Operation::create(['date' => '2024-02-01', 'type' => $type, 'is_co' => $co, 'amount' => $amount, 'fee' => $fee,
                'contact_id' => $donor->id, 'active' => 1, 'company_id' => 1, 'import_key' => 'incomes-20240305100004']);
        }

        Artisan::call('imports:link-history');
        $history->refresh();
        $this->assertSame(['incomes', 1, 'incomes-20240305100004', 2, 3, 1], [$history->type, (int) $history->company_id,
            $history->import_key, (int) $history->rows_count, (int) $history->operations_count, (int) $history->donors_created]);
        $this->assertEqualsWithDelta(150, (float) $history->total_amount, 0.001);
        $this->assertEqualsWithDelta(10, (float) $history->total_fee, 0.001);
        $this->assertSame(3, Operation::where('history_operation_id', $history->id)->count());
        $this->assertSame($history->id, (int) $donor->fresh()->history_operation_id);
    }
}
