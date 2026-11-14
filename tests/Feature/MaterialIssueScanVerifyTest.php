<?php

namespace Tests\Feature;

use App\Http\Controllers\Pages\Export\MaterialExportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Quét xác nhận mã xuất nhập trước khi cấp phát vật tư
 * (MaterialExportController::issueScanError, màn hình MaterialExport/issueScanVerify).
 *
 * Chạy trên CSDL thật, chỉ đọc 2 mã xuất nhập có sẵn.
 */
class MaterialIssueScanVerifyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'wms',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
            'material.issue_scan_required' => true,
        ]);
    }

    private function check(array $importIds, array $scanCodes): ?string
    {
        $method = new \ReflectionMethod(MaterialExportController::class, 'issueScanError');
        $method->setAccessible(true);

        return $method->invoke(app(MaterialExportController::class), Request::create('/', 'POST', ['scan_codes' => $scanCodes]), $importIds);
    }

    private function lots(): array
    {
        $lots = DB::table('material_imports')->select('id', 'code')->whereNotNull('code')->limit(2)->get();

        if ($lots->count() < 2) {
            $this->markTestSkipped('CSDL chưa có đủ 2 mã xuất nhập vật tư.');
        }

        return $lots->all();
    }

    public function test_blocks_when_nothing_scanned(): void
    {
        [$a] = $this->lots();

        $error = $this->check([$a->id], []);

        $this->assertNotNull($error);
        $this->assertStringContainsString($a->code, $error);
    }

    public function test_blocks_when_only_some_lots_scanned(): void
    {
        [$a, $b] = $this->lots();

        $error = $this->check([$a->id, $b->id], [$a->code]);

        $this->assertNotNull($error);
        $this->assertStringContainsString($b->code, $error);
        $this->assertStringNotContainsString($a->code, $error);
    }

    public function test_passes_when_all_lots_scanned_ignoring_case_and_spaces(): void
    {
        [$a, $b] = $this->lots();

        $this->assertNull($this->check([$a->id, $b->id], [' '.strtolower($a->code).' ', $b->code]));
    }

    public function test_wrong_code_does_not_count(): void
    {
        [$a, $b] = $this->lots();

        $this->assertNotNull($this->check([$a->id], [$b->code]));
    }

    public function test_can_be_turned_off(): void
    {
        [$a] = $this->lots();
        config(['material.issue_scan_required' => false]);

        $this->assertNull($this->check([$a->id], []));
    }
}
