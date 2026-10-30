<?php

namespace Tests\Feature;

use App\Contact;
use App\Operation;
use App\Project;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MigrationWorkflowTest extends TestCase
{
    private User $operator;
    private string $password;
    private string $token;
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        if (getenv('FONDO_MIGRATION_TEST') !== '1') {
            $this->markTestSkipped('Requires an isolated restored Fondo database.');
        }
        parent::setUp();
        $this->assertTrue(app()->environment('testing'));
        DB::beginTransaction();
        Mail::fake();
        $this->password = bin2hex(random_bytes(16));
        $this->operator = User::create([
            'user' => 'migration_'.bin2hex(random_bytes(6)),
            'name' => 'Migration test',
            'email' => 'migration@example.test',
            'password' => Hash::make($this->password),
            'company_id' => 1,
            'group_id' => DB::table('user_groups')->whereRaw('LOWER(name) = ?', ['admin'])->value('id'),
            'role_id' => DB::table('user_roles')->where('slug', 'admin')->value('id'),
            'menu_ids' => '', 'active' => 1, 'action_delete' => 1,
        ]);
        $this->token = JWTAuth::fromUser($this->operator);
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

    private function api(string $method, string $path, array $payload = [])
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->json($method, '/api/'.$path, $payload);
    }

    private function form(string $type = 'income'): array
    {
        $donor = Contact::create(['name' => 'Migration donor', 'category' => 'Donador', 'company_id' => 1, 'active' => 1]);
        return [
            'type' => $type, 'date' => '2026-10-20', 'amount' => '1234.56',
            'contact_id' => $donor->id, 'contact_type' => 'Donador',
            'project_id' => 0, 'mode_id' => 157, 'reference' => '001234',
            'category' => 'Migration test', 'subcategory' => 'Test',
            'comment' => 'Migration test', 'fee' => 5, 'monitoring' => 0,
            'retain_operating_cost' => 0, 'percentage_operating_cost' => 10,
            'origin_donor_id' => $donor->id, 'origin_project_id' => 0,
            'origin_category' => 'Migration test', 'origin_subcategory' => 'Test',
        ];
    }

    public function test_login_preserves_privileges_and_hides_credentials(): void
    {
        $response = $this->postJson('/api/authenticate', ['user' => $this->operator->user, 'password' => $this->password]);
        $this->assertSame(200, $response->status());
        $this->assertTrue($response->json('success') === true, 'Login failed');
        $this->assertNotEmpty($response->json('token'));
        $this->assertIsArray($response->json('user.privileges.panelNavigation'));
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->assertArrayNotHasKey('remember_token', $response->json('user'));
    }

    public function test_disabled_users_cannot_log_in_or_use_existing_tokens(): void
    {
        $this->operator->update(['active' => 0]);
        $this->postJson('/api/authenticate', ['user' => $this->operator->user, 'password' => $this->password])->assertStatus(401);
        $this->api('POST', 'get/session')->assertStatus(401);
    }

    public function test_user_management_preserves_admin_access_and_blocks_self_promotion(): void
    {
        $this->api('GET', 'user/data')->assertOk()->assertJson(['success' => true]);
        $restrictedGroup = \App\UserGroup::create(['name' => 'Migration restricted', 'company_id' => 1, 'active' => 1, 'menu_ids' => '14']);
        $this->operator->update(['group_id' => $restrictedGroup->id, 'menu_ids' => '14']);
        $this->operator->unsetRelation('group');
        $this->api('POST', 'user/store', ['user' => 'unauthorized'])->assertStatus(403);
        $this->api('POST', 'user/update', ['id' => $this->operator->id, 'role_id' => 1])->assertStatus(403);
        $this->api('POST', 'user/delete', ['user' => $this->operator->id])->assertStatus(403);
        $this->api('POST', 'user/show', ['user' => $this->operator->id])->assertStatus(403);
        $this->assertSame(1, (int) $this->operator->fresh()->active);
    }

    public function test_private_operations_require_a_valid_token(): void
    {
        $this->postJson('/api/operation/show', ['item' => 1])->assertStatus(401);
        $this->withHeader('Authorization', 'Bearer invalid')->postJson('/api/operation/show', ['item' => 1])->assertStatus(401);
    }

    public function test_existing_contacts_and_projects_can_be_loaded(): void
    {
        foreach ([['GET', 'contact/index'], ['GET', 'contact/data'], ['POST', 'project/data'], ['POST', 'operation/data']] as [$method, $path]) {
            $response = $this->api($method, $path);
            $this->assertSame(200, $response->status(), $path);
            $this->assertTrue($response->json('success') === true, $path.' failed');
        }
    }

    public function test_income_can_be_created_edited_and_deleted_without_changing_other_rows(): void
    {
        $form = $this->form();
        $count = Operation::count();
        $response = $this->api('POST', 'operation/store', ['form' => json_encode($form)]);
        $this->assertTrue($response->json('success') === true, 'Income creation failed');
        $this->assertSame($count + 1, Operation::count());
        $operation = Operation::where('contact_id', $form['contact_id'])->firstOrFail();
        $this->assertEqualsWithDelta(1234.56, (float) $operation->amount, 0.00001);
        $this->assertSame('001234', $operation->reference);
        $form['donor_id'] = $form['contact_id'];
        $form['amount'] = '2345.67';
        $response = $this->api('POST', 'operation/update', ['id' => $operation->id, 'form' => json_encode($form), 'filesOld' => '[]']);
        $this->assertTrue($response->json('success') === true, 'Income update failed');
        $this->assertEqualsWithDelta(2345.67, (float) $operation->fresh()->amount, 0.00001);
        $response = $this->api('POST', 'operation/delete', ['id' => $operation->id]);
        $this->assertTrue($response->json('success') === true, 'Income deletion failed');
        $this->assertSame($count, Operation::count());
    }

    public function test_outcome_preserves_donor_and_amount(): void
    {
        $form = $this->form('outcome');
        $response = $this->api('POST', 'operation/store', ['form' => json_encode($form)]);
        $this->assertTrue($response->json('success') === true, 'Outcome creation failed');
        $operation = Operation::where('contact_id', $form['contact_id'])->firstOrFail();
        $this->assertSame('outcome', $operation->type);
        $this->assertEquals($form['contact_id'], $operation->donor_id);
        $this->assertEqualsWithDelta(1234.56, (float) $operation->amount, 0.00001);
    }

    public function test_transfer_creates_balanced_paired_entries(): void
    {
        $form = $this->form('transfer');
        $form += ['target_contact_id' => $form['contact_id'], 'target_project_id' => 0,
            'target_category' => 'Migration target', 'target_subcategory' => 'Test', 'target_monitoring' => 0];
        $response = $this->api('POST', 'operation/store', ['form' => json_encode($form)]);
        $this->assertTrue($response->json('success') === true, 'Transfer failed');
        $rows = Operation::where('contact_id', $form['contact_id'])->get();
        $this->assertCount(2, $rows);
        $this->assertSame($rows[0]->transfer_reference, $rows[1]->transfer_reference);
        $this->assertEqualsWithDelta(0, $rows->sum(fn ($row) => $row->type === 'income' ? $row->amount : -$row->amount), 0.00001);
    }

    public function test_excel_income_import_preserves_dates_decimals_and_leading_zeroes(): void
    {
        $name = 'Migration donor '.bin2hex(random_bytes(5));
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Date', 'Donor', 'Amount', 'Fee', 'Category', 'Subcategory', 'Comment', 'Account', 'Retention'],
            [46315, $name, 1234.56, '5%', 'AVN', 'AVN', 'Migration import', '00123456789', 0],
        ]);
        $sheet->setCellValueExplicit('H2', '00123456789', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $path = tempnam(sys_get_temp_dir(), 'fondo-import-').'.xlsx';
        $this->temporaryFiles[] = $path;
        (new Xlsx($spreadsheet))->save($path);
        $this->actingAs($this->operator);
        $import = new \App\Imports\Operations\IncomeImport();
        $import->import($import->validate($path, true), ['original_name' => 'layout.xlsx', 'path' => null, 'hash' => null]);
        $donor = Contact::where('name', $name)->firstOrFail();
        $operation = Operation::where('contact_id', $donor->id)->firstOrFail();
        $this->assertSame('2026-10-20', $operation->date);
        $this->assertEqualsWithDelta(1234.56, (float) $operation->amount, 0.00001);
        $this->assertSame('00123456789', $operation->account);
        $this->assertEquals(5, $operation->fee);
    }

    public function test_retention_rounding_is_preserved(): void
    {
        $this->assertEqualsWithDelta(86.36, calculatedRetainAmount(1000, 5, 10), 0.00001);
        $this->assertEqualsWithDelta(0, calculatedRetainAmount(1000, 5, 0), 0.00001);
    }

    public function test_financial_endpoints_require_authentication(): void
    {
        foreach ([['GET', 'operation/balance/account'], ['POST', 'operation/balance/accounts/details'],
            ['POST', 'donor/operations/info'], ['POST', 'project/collect/monitoring']] as [$method, $path]) {
            $response = $this->json($method, '/api/'.$path);
            $this->assertSame(401, $response->status(), $path);
        }
    }

    public function test_financial_reports_load_from_the_restored_data(): void
    {
        $requests = [
            ['operation/select', ['category' => 'all', 'project' => 'all', 'contact' => 'all', 'from' => '2026-09-01', 'to' => '2026-10-22', 'length' => 10]],
            ['operation/donors/monitoring/select', ['month' => '2026-10', 'length' => 10]],
            ['operation/history/select', ['from' => '2026-01-01', 'to' => '2026-10-22', 'length' => 10]],
        ];
        foreach ($requests as [$path, $payload]) {
            $response = $this->api('POST', $path, $payload);
            $this->assertSame(200, $response->status(), $path);
            $this->assertTrue(is_array($response->json('data')), $path.' has no tabular result');
            $this->assertTrue(is_numeric($response->json('recordsTotal')), $path.' has no row count');
        }
    }

    public function test_sql_filter_input_cannot_expand_the_result_set(): void
    {
        $response = $this->api('POST', 'operation/select', [
            'category' => 'all', 'project' => 'all', 'contact' => '0 OR 1=1',
            'from' => '2026-09-01', 'to' => '2026-10-22',
        ]);
        $this->assertSame(200, $response->status());
        $this->assertSame(0, $response->json('recordsFiltered'));
    }

    public function test_delete_permission_is_enforced(): void
    {
        $form = $this->form();
        $operation = Operation::create(['date' => $form['date'], 'type' => 'income', 'amount' => 10,
            'contact_id' => $form['contact_id'], 'active' => 1, 'company_id' => 1]);
        $this->operator->update(['action_delete' => 0]);
        $response = $this->api('POST', 'operation/delete', ['id' => $operation->id]);
        $this->assertFalse($response->json('success'));
        $this->assertNotNull($operation->fresh());
    }

    public function test_logout_revokes_the_token(): void
    {
        $response = $this->api('POST', 'logout');
        $this->assertTrue($response->json('success') === true, 'Logout failed');
        $this->api('POST', 'operation/show', ['item' => 1])->assertStatus(401);
    }

    public function test_guests_cannot_guess_private_report_ids(): void
    {
        $this->postJson('/api/report/donor', ['id' => 'randomhe51'])->assertStatus(401);
        $this->postJson('/api/report/ally', ['id' => 'randomca681'])->assertStatus(401);
        $this->postJson('/api/report/project', ['projectId' => 1])->assertStatus(401);
    }

    public function test_project_report_only_opens_the_users_projects(): void
    {
        // A user tied to a donor only reaches that donor's projects, as in the project list
        $own = Project::where('active', 1)->where('donors', '>', 0)->first(['id', 'donors']);
        $other = Project::where('active', 1)->where('donors', '<>', $own->donors)->value('id');
        $this->operator->update(['donors' => (string) $own->donors]);
        $this->api('POST', 'report/project', ['projectId' => $own->id])->assertJson(['success' => true]);
        $this->api('POST', 'report/project', ['projectId' => $other])->assertJson(['success' => false]);
    }

    public function test_activity_report_opens_its_project_with_that_activity_only(): void
    {
        $project = Project::where('active', 1)->value('id');
        $task = \App\Task::create(['name' => 'Migration activity', 'project_id' => $project, 'status' => 'working', 'active' => 1, 'company_id' => 1]);
        \App\Task::create(['name' => 'Other activity', 'project_id' => $project, 'status' => 'done', 'active' => 1, 'company_id' => 1]);
        $report = $this->api('POST', 'report/project', ['taskId' => $task->id]);
        $this->assertSame($project, $report->json('project.id'));
        $this->assertSame([$task->id], array_column($report->json('tasks'), 'id'));
    }

    public function test_project_report_survives_operations_without_amount(): void
    {
        // amount and fee are varchar: an empty one used to stop the report with a TypeError
        $project = Project::where('active', 1)->value('id');
        Operation::create(['date' => '2026-10-20', 'type' => 'income', 'amount' => '', 'fee' => '',
            'project_id' => $project, 'active' => 1, 'company_id' => 1]);
        $this->api('POST', 'report/project', ['projectId' => $project])->assertJson(['success' => true]);
    }

    public function test_donors_balance_report_splits_each_source_into_month_and_previous_sums(): void
    {
        $donor = Contact::create(['name' => 'Migration donor', 'category' => 'Donador', 'company_id' => 1, 'active' => 1]);
        $fund = Contact::create(['name' => 'Migration fund', 'category' => 'Donador', 'company_id' => 1, 'active' => 1]);
        $operation = fn (array $data) => Operation::create($data + [
            'category' => 'Migration test', 'subcategory' => 'Test', 'project_id' => 0, 'transfer' => 0, 'active' => 1, 'company_id' => 1,
        ]);
        $operation(['type' => 'income', 'date' => '2026-09-10', 'amount' => '1000', 'fee' => '5', 'contact_id' => $donor->id]);
        $operation(['type' => 'income', 'date' => '2026-10-05', 'amount' => '500', 'fee' => '10', 'contact_id' => $donor->id, 'val_operating_cost' => '45.5']);
        $operation(['type' => 'income', 'date' => '2026-10-20', 'amount' => '300', 'contact_id' => $donor->id, 'val_operating_cost' => '30']);
        $operation(['type' => 'income', 'date' => '2026-11-02', 'amount' => '999', 'contact_id' => $donor->id]);
        $operation(['type' => 'outcome', 'date' => '2026-09-12', 'amount' => '30', 'donor_id' => $donor->id]);
        $operation(['type' => 'outcome', 'date' => '2026-10-12', 'amount' => '120', 'donor_id' => $donor->id]);
        // A transfer to the fund: sent by the donor, received by the fund
        $operation(['type' => 'outcome', 'date' => '2026-10-15', 'amount' => '200', 'transfer' => 1, 'donor_id' => $donor->id, 'contact_id' => $fund->id]);
        $operation(['type' => 'income', 'date' => '2026-10-15', 'amount' => '200', 'transfer' => 1, 'donor_id' => $donor->id, 'contact_id' => $fund->id]);

        $rows = collect($this->api('POST', 'donors/balance/report', ['month' => '2026-10'])->assertOk()->json('data'));
        $this->assertCount(1, $rows->where('id', $donor->id));
        $row = $rows->firstWhere('id', $donor->id);
        $this->assertSame(['Migration donor', 'Migration test', 'Test'], [$row['donor'], $row['category'], $row['subcategory']]);
        $expected = [
            'incomesPrev' => 950, 'sumIncomes' => 800, 'sumIncomesFee' => 750, 'sumFee' => 50,
            'sumOutcomesPrev' => 30, 'sumOutcomes' => 120, 'sumTransfersSendings' => 200, 'sumTransfersSendingsPrev' => 0,
            'sumTransfersReceipt' => 0,
        ];
        foreach ($expected as $field => $value) {
            $this->assertEqualsWithDelta($value, $row[$field], 0.00001, $field);
        }
        // Retención CO shows the month's first income, not the sum of them, as it always has
        $this->assertSame('45.5', $row['val_operating_cost']);
        $this->assertEqualsWithDelta(200, $rows->firstWhere('id', $fund->id)['sumTransfersReceipt'], 0.00001);
    }

    public function test_donors_balance_report_only_lists_the_users_donors(): void
    {
        $donors = Contact::where(['category' => 'Donador', 'active' => 1])->limit(2)->pluck('id');
        $this->operator->update(['donors' => $donors->implode(',')]);
        $rows = $this->api('POST', 'donors/balance/report', ['month' => '2026-10'])->assertOk()->json('data');
        $this->assertNotEmpty($rows);
        $this->assertEmpty(array_diff(array_column($rows, 'id'), $donors->all()));

        // None of the user's donors is active any more: an empty report, not an SQL error
        $this->operator->update(['donors' => '999999999']);
        $this->api('POST', 'donors/balance/report', ['month' => '2026-10'])->assertOk()->assertExactJson(['data' => []]);

        $this->api('POST', 'donors/balance/report', ['month' => '10/2026'])->assertStatus(422);
    }

    public function test_donors_balance_report_leaves_out_the_remnant_funds(): void
    {
        $rows = $this->api('POST', 'donors/balance/report', ['month' => '2026-10'])->assertOk()->json('data');
        $this->assertNotEmpty($rows);
        $this->assertEmpty(array_intersect(array_column($rows, 'id'), [363, 537]));
    }

    public function test_existing_internal_report_links_remain_usable(): void
    {
        $contact = Contact::create(['name' => 'Migration donor', 'category' => 'Donador', 'company_id' => 1, 'active' => 1]);
        $request = new \Illuminate\Http\Request(['id' => 'existing-prefixhe5'.$contact->id]);
        $request->headers->set('Authorization', 'Bearer '.$this->token);
        $this->assertSame($contact->id, \App\Support\ReportAccess::resolve($request, 'donor'));
    }

    public function test_report_notification_contains_a_server_signed_link(): void
    {
        $contact = Contact::create(['name' => 'Migration donor', 'category' => 'Donador',
            'email' => 'migration@example.test', 'company_id' => 1, 'active' => 1]);
        $response = $this->api('POST', 'contact/send/notification', ['id' => $contact->id, 'link' => 'untrusted-client-value']);
        $this->assertTrue($response->json('success') === true, 'Notification preparation failed');
        Mail::assertSent(\App\Mail\NotificationDonorUpdateProject::class, function ($mail) use ($contact) {
            $link = $mail->dataMessage['link'];
            $this->assertSame($contact->id, \App\Support\ReportAccess::resolve(new \Illuminate\Http\Request(['id' => $link]), 'donor'));
            $this->assertStringContainsString('/report/donor/'.$link, $mail->render());
            return $mail->hasTo('migration@example.test');
        });
    }
    public function test_other_organizations_accounts_are_never_visible_or_mutable(): void
    {
        $account = \App\Account::create(['name' => 'Private account fixture', 'company_id' => 999999, 'active' => 1]);
        $this->api('POST', 'account/show', ['item' => $account->id])->assertJson(['success' => false]);
        $this->api('POST', 'account/delete', ['item' => $account->id])->assertJson(['success' => false]);
        $this->assertSame(1, (int) $account->fresh()->active);
        $contact = \App\Contact::create(['name' => 'Private contact fixture', 'company_id' => 999999, 'active' => 1]);
        $this->api('DELETE', 'contact/destroy', ['id' => $contact->id])->assertJson(['success' => false]);
        $this->assertNotNull($contact->fresh());
    }

    public function test_operations_and_donor_notifications_cannot_cross_organizations(): void
    {
        $operation = \App\Operation::create(['name' => 'Private operation fixture', 'company_id' => 999999, 'active' => 1]);
        $this->api('POST', 'operation/delete', ['id' => $operation->id])->assertJson(['success' => false]);
        $this->assertNotNull($operation->fresh());
        $contact = \App\Contact::create(['name' => 'Private donor fixture', 'email' => 'private@example.test', 'company_id' => 999999, 'active' => 1]);
        $this->api('POST', 'contact/send/notification', ['id' => $contact->id])->assertJson(['success' => false]);
        Mail::assertNotSent(\App\Mail\NotificationDonorUpdateProject::class);
    }

    public function test_import_history_deletion_requires_delete_permission(): void
    {
        $this->operator->update(['action_delete' => 0]);
        $this->api('POST', 'operation/history/delete', ['id' => 999999])->assertJson(['success' => false]);
    }

}
