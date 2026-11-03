<?php

use App\Support\MaterDataPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gộp quyền Dữ Liệu Gốc theo thao tác: mỗi phạm vi (material / chemical / standard / common) còn
 *  - materData_<phạm vi>_manage : Thêm / Sửa / Khoá  (thay create, update, deActive)
 *  - materData_<phạm vi>_review : Duyệt / Từ chối    (thay approve, reject)
 * cùng quyền con từng màn hình (App\Support\MaterDataPermissions). Nhóm quyền / user có BẤT KỲ quyền cũ nào trong
 * một cụm thì được quyền gộp; quyền cũ (cả quyền con theo màn hình đã tạo ở migration trước) bị xoá.
 */
return new class extends Migration
{
    private const MERGE = [
        'manage' => ['label' => 'Thêm/Sửa/Khoá', 'verb' => 'Thêm, sửa, khoá / mở khoá', 'from' => ['create', 'update', 'deActive']],
        'review' => ['label' => 'Duyệt/Từ Chối', 'verb' => 'Duyệt, từ chối', 'from' => ['approve', 'reject']],
    ];

    private const SCOPES = ['material' => 'Vật Tư', 'chemical' => 'Hoá Chất', 'standard' => 'Chất Chuẩn', 'common' => 'Dùng Chung'];

    public function up(): void
    {
        $now = now();
        $oldIds = [];

        foreach (self::SCOPES as $scope => $scopeLabel) {
            $view = DB::table('permissions')->where('name', "materData_{$scope}_view")->first();
            if (! $view) {
                continue;
            }
            // "Xem dữ liệu gốc thuộc: Tên Vật Tư, Phân Loại Vật Tư" -> phần danh sách màn hình
            $covers = trim(explode(':', (string) $view->description, 2)[1] ?? '');

            foreach (self::MERGE as $action => $merge) {
                $from = DB::table('permissions')
                    ->whereIn('name', array_map(fn ($a) => "materData_{$scope}_{$a}", $merge['from']))
                    ->pluck('id');
                $oldIds = array_merge($oldIds, $from->all());

                $name = "materData_{$scope}_{$action}";
                $row = [
                    'permission_group' => $view->permission_group,
                    'display_name' => $merge['label'].' Dữ Liệu Gốc '.$scopeLabel,
                    'description' => $merge['verb'].' dữ liệu gốc'.($covers !== '' ? ' thuộc: '.$covers : ''),
                    'updated_at' => $now,
                ];
                $id = DB::table('permissions')->where('name', $name)->value('id');
                if ($id) {
                    DB::table('permissions')->where('id', $id)->update($row);
                } else {
                    $id = DB::table('permissions')->insertGetId($row + ['name' => $name, 'created_at' => $now]);
                }

                foreach (DB::table('role_permission')->whereIn('permission_id', $from)->distinct()->pluck('role_id') as $roleId) {
                    DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $id], []);
                }

                // Quyền riêng của user: có "Cho phép" ở quyền cũ nào thì cho phép, chỉ toàn "Từ chối" thì từ chối
                foreach (DB::table('user_permission')->whereIn('permission_id', $from)->get()->groupBy('user_id') as $userId => $rows) {
                    DB::table('user_permission')->updateOrInsert(
                        ['user_id' => $userId, 'permission_id' => $id],
                        ['is_denied' => $rows->every(fn ($r) => (bool) $r->is_denied) ? 1 : 0]
                    );
                }
            }
        }

        // Quyền con theo màn hình của quyền cũ (tạo ở migration 2026_11_03_100000)
        $oldIds = array_merge($oldIds, DB::table('permissions')->whereIn('parent_id', $oldIds)->pluck('id')->all());
        DB::table('role_permission')->whereIn('permission_id', $oldIds)->delete();
        DB::table('user_permission')->whereIn('permission_id', $oldIds)->delete();
        DB::table('permissions')->whereIn('id', $oldIds)->delete();

        // Quyền con manage / review của từng màn hình, cấp cho nhóm quyền đang có quyền gộp
        MaterDataPermissions::seed();
    }

    public function down(): void
    {
        // Không khôi phục được tách quyền đã gộp
    }
};
