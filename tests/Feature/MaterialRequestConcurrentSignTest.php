<?php

namespace Tests\Feature;

use App\Http\Controllers\Pages\Export\MaterialExportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Ký đồng thời đề nghị cấp phát vật tư nội bộ (MaterialExportController::requestSign):
 * mọi bước còn chờ ký được ngay, bước sau ký thì bước trước được duyệt theo (auto_signed).
 *
 * Chạy trên CSDL thật trong một transaction, rollback sau mỗi test.
 */
class MaterialRequestConcurrentSignTest extends TestCase
{
    private const PASSWORD = 'TestSign@123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'wms',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();

        parent::tearDown();
    }

    /** Phiếu 2 bước: bước 1 = $u1, bước 2 = $u2. */
    private function makeRequest(): array
    {
        $users = DB::table('user_management')->select('id', 'fullName', 'userName', 'deparment_id')->limit(2)->get();

        if ($users->count() < 2) {
            $this->markTestSkipped('CSDL chưa có đủ 2 người dùng.');
        }

        [$u1, $u2] = $users->all();
        DB::table('user_management')->whereIn('id', [$u1->id, $u2->id])->update(['passWord' => Hash::make(self::PASSWORD)]);

        $departmentId = (int) ($u1->deparment_id ?: DB::table('deparments')->value('id'));

        $listId = DB::table('material_request_lists')->insertGetId([
            'code' => 'TEST_SIGN_'.uniqid(),
            'department_id' => $departmentId,
            'name' => 'Test ký đồng thời',
            'type' => 'regular',
            'app_status' => 'pending_sign',
            'sign_step_count' => 2,
            'current_step' => 1,
            'status_id' => 1,
            'created_by' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([1 => $u1, 2 => $u2] as $step => $user) {
            $signId = DB::table('material_request_signs')->insertGetId([
                'request_list_id' => $listId, 'step_no' => $step, 'user_name' => $user->fullName,
                'role_names' => 'Bước '.$step, 'status' => 'pending', 'active' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('material_request_sign_candidates')->insert([
                'sign_id' => $signId, 'user_id' => $user->id, 'user_name' => $user->fullName, 'active' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return [$listId, $departmentId, $u1, $u2];
    }

    private function signAs($user, int $departmentId, int $listId, string $action = 'requestSign')
    {
        session(['user' => [
            'userId' => $user->id,
            'userName' => $user->userName,
            'fullName' => $user->fullName,
            'selected_department_id' => $departmentId,
        ]]);

        $request = Request::create('/', 'POST', [
            'request_list_id' => $listId,
            'sign_password' => self::PASSWORD,
            'reject_reason' => 'Không đạt',
        ]);
        $request->setLaravelSession(app('session.store'));

        return app(MaterialExportController::class)->{$action}($request);
    }

    private function statuses(int $listId): array
    {
        return DB::table('material_request_signs')->where('request_list_id', $listId)->orderBy('step_no')->pluck('status')->all();
    }

    public function test_last_step_signs_first_and_auto_approves_earlier_step(): void
    {
        [$listId, $dept, , $u2] = $this->makeRequest();

        $this->signAs($u2, $dept, $listId);

        $this->assertSame(['auto_signed', 'signed'], $this->statuses($listId));
        $list = DB::table('material_request_lists')->find($listId);
        $this->assertSame('approved', $list->app_status);
        $this->assertSame('waiting', $list->issue_status);
        $this->assertNull($list->current_step);
    }

    public function test_first_step_signs_then_request_waits_for_step_two(): void
    {
        [$listId, $dept, $u1, $u2] = $this->makeRequest();

        $this->signAs($u1, $dept, $listId);

        $this->assertSame(['signed', 'pending'], $this->statuses($listId));
        $list = DB::table('material_request_lists')->find($listId);
        $this->assertSame('pending_sign', $list->app_status);
        $this->assertSame(2, (int) $list->current_step);

        $this->signAs($u2, $dept, $listId);

        $this->assertSame(['signed', 'signed'], $this->statuses($listId));
        $this->assertSame('approved', DB::table('material_request_lists')->where('id', $listId)->value('app_status'));
    }

    public function test_later_step_can_reject_before_earlier_step_signs(): void
    {
        [$listId, $dept, , $u2] = $this->makeRequest();

        $this->signAs($u2, $dept, $listId, 'requestReject');

        $this->assertSame(['pending', 'rejected'], $this->statuses($listId));
        $list = DB::table('material_request_lists')->find($listId);
        $this->assertSame('rejected', $list->app_status);
        $this->assertSame('2', (string) $list->reject_step);
    }
}
