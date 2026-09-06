<?php

use App\Support\MaterialCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỔI MÃ LÔ VẬT TƯ ĐÃ NHẬP SANG QUY TẮC MỚI.
 *
 * Quy tắc mới (App\Support\MaterialCode): "M" + "-" + id phòng ban (2 chữ số) + "-"
 * + đuôi ngẫu nhiên 10 ký tự  ->  M-04-7C9X8GQ4TK. Mọi mã dài bằng nhau (15 ký tự).
 *
 * Mã cũ có nhiều kiểu đầu ("VT-EN-...", "M-EN-...", "M-KTBT-NM2-...") vì phần giữa
 * lấy từ deparments.shortName (người dùng tự nhập, dài ngắn tuỳ ý, có cả dấu "-").
 * Điểm chung: 10 ký tự cuối luôn là đuôi ngẫu nhiên -> GIỮ NGUYÊN đuôi, chỉ dựng lại
 * phần đầu "M-<id phòng ban>-". Không tạo / sửa quan hệ nào, chỉ đổi định dạng.
 *
 * Phần id phòng ban lấy từ material_imports.department_id của chính lô đó. Các bảng
 * con chép lại mã dưới dạng chuỗi (material_exports.code, ...) được cập nhật theo,
 * đọc department_id qua import_id để mã trên phiếu xuất / lịch sử khớp với phiếu nhập.
 *
 * KHÔNG đụng: audittriallog và các cột change_note - đó là nhật ký ghi lại giá trị
 * tại thời điểm thao tác, phải giữ nguyên. Mã hoá chất (chemical_imports) không nằm
 * trong phạm vi lần này.
 */
return new class extends Migration
{
    /** [bảng, khoá ngoại trỏ material_imports.id, cột chứa mã dạng chuỗi] */
    private const CHILD_TABLES = [
        ['material_import_histories', 'material_import_id', 'code'],
        ['material_exports', 'import_id', 'code'],
        ['material_export_histories', 'import_id', 'code'],
        ['material_request_items', 'import_id', 'import_code'],
        ['material_balancings', 'import_id', 'code'],
        ['material_pick_lines', 'import_id', 'import_code'],
        ['material_stocktake_items', 'import_id', 'code'],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $this->reformatImports();

            foreach (self::CHILD_TABLES as [$table, $fk, $col]) {
                $this->reformatChild($table, $fk, $col);
            }
        });
    }

    public function down(): void
    {
        // Không hoàn tác: mã cũ có nhiều kiểu đầu khác nhau (shortName do người dùng
        // nhập), không dựng lại chính xác được. Mã mới đã là mã chính thức.
    }

    /** Bảng material_imports: id phòng ban lấy từ chính cột department_id của lô. */
    private function reformatImports(): void
    {
        $rows = DB::table('material_imports')
            ->select('id', 'code', 'department_id')
            ->get();

        foreach ($rows as $row) {
            $new = $this->rebuild($row->code, $row->department_id);

            if ($new !== null && $new !== $row->code) {
                DB::table('material_imports')->where('id', $row->id)->update(['code' => $new]);
            }
        }
    }

    /** Bảng con: id phòng ban đọc qua material_imports theo khoá ngoại. */
    private function reformatChild(string $table, string $fk, string $col): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $col)) {
            return;
        }

        $rows = DB::table($table)
            ->join('material_imports', "$table.$fk", '=', 'material_imports.id')
            ->whereNotNull("$table.$col")
            ->where("$table.$col", '<>', '')
            ->select("$table.id", "$table.$col as value", 'material_imports.department_id')
            ->get();

        foreach ($rows as $row) {
            $new = $this->rebuild($row->value, $row->department_id);

            if ($new !== null && $new !== $row->value) {
                DB::table($table)->where('id', $row->id)->update([$col => $new]);
            }
        }
    }

    /**
     * Dựng lại mã theo quy tắc mới, giữ nguyên 10 ký tự đuôi.
     * Trả null nếu chuỗi quá ngắn không có đủ đuôi để giữ.
     */
    private function rebuild(?string $code, $departmentId): ?string
    {
        $code = (string) $code;

        if (strlen($code) < MaterialCode::RANDOM_LENGTH) {
            return null;
        }

        $tail = substr($code, -MaterialCode::RANDOM_LENGTH);

        return MaterialCode::build((int) $departmentId, $tail);
    }
};
