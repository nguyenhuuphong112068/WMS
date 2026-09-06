<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ghi rõ trong phần diễn giải của 24 quyền "Dữ Liệu Gốc" là gồm những màn dữ liệu
 * gốc nào, để người phân quyền biết chính xác quyền áp cho loại nào.
 */
return new class extends Migration
{
    /** scope => danh sách màn dữ liệu gốc (đúng tên trên menu leftNAV) */
    private const SCOPE_ENTITIES = [
        'material' => 'Tên Vật Tư, Phân Loại Vật Tư',
        'chemical' => 'Tên Hoá Chất, Tên Hoạt Chất, Nhóm Nguy Hại Bảng B',
        'standard' => 'Tên Chuẩn, Chỉ Tiêu Kiểm',
        'common'   => 'Công Ty, Phòng Ban, Tổ, Tên Sản Phẩm, Nhà Sản Xuất, Nhà Cung Cấp, Trạng Thái, Định Khu, Quy Cách Đóng Gói, Đơn Vị Tính, Điều Kiện Bảo Quản',
    ];

    /** action => động từ trong câu diễn giải */
    private const ACTION_VERBS = [
        'view'     => 'Xem',
        'create'   => 'Thêm',
        'update'   => 'Sửa',
        'deActive' => 'Khoá / mở khoá',
        'approve'  => 'Duyệt',
        'reject'   => 'Từ chối',
    ];

    public function up(): void
    {
        foreach (self::SCOPE_ENTITIES as $scope => $entities) {
            foreach (self::ACTION_VERBS as $action => $verb) {
                DB::table('permissions')
                    ->where('name', "materData_{$scope}_{$action}")
                    ->update(['description' => "{$verb} dữ liệu gốc thuộc: {$entities}"]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::SCOPE_ENTITIES) as $scope) {
            foreach (self::ACTION_VERBS as $action => $verb) {
                DB::table('permissions')
                    ->where('name', "materData_{$scope}_{$action}")
                    ->update(['description' => "{$verb} bản ghi dữ liệu gốc"]);
            }
        }
    }
};
