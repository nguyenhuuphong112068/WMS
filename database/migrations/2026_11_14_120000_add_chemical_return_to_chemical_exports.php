<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TRẢ HOÁ CHẤT ACETONITRIL / METHANOL VỀ KHO (App\Support\ChemicalReturn).
 *
 * - chemical_exports.return_of_id: dòng type = 'return' (amount âm) trỏ về phiếu sử dụng gốc.
 * - Quyền export_chemical_return (khai báo / huỷ phiếu trả) + quyền xem tab
 *   export_chemical_tab_return (con của export_chemical_view), cấp cho Admin, Quản Lý Bộ Phân,
 *   Quản Lý Kho Hoá Chất, Tổ Trưởng Quản Lý Kho Hoá Chất, Quản Lý Tổ Chuyên Môn.
 */
return new class extends Migration
{
    private const PERMISSION = 'export_chemical_return';

    private const TAB_PERMISSION = 'export_chemical_tab_return';

    private const ROLES = [
        'Admin',
        'Quản Lý Bộ Phân',
        'Quản Lý Bộ Phận',
        'Quản Lý Kho Hóa Chất',
        'Quản Lý Kho Hoá Chất',
        'Tổ Tưởng Quản Lý Kho Hoá chất - Chất chuẩn',
        'Tổ Trưởng Quản Lý Kho Hoá chất - Chất chuẩn',
        'Quản Lý Tổ Chuyên Môn',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('chemical_exports', 'return_of_id')) {
            Schema::table('chemical_exports', function (Blueprint $table) {
                $table->unsignedBigInteger('return_of_id')->nullable()->after('quarantine_id')->index();
            });
        }

        // Loại dòng mới 'return' (trả về kho, số lượng âm)
        DB::statement("ALTER TABLE chemical_exports MODIFY `type` ENUM('export','cancel','transfer_out','return') NULL DEFAULT 'export'");

        $now = now();
        $viewId = DB::table('permissions')->where('name', 'export_chemical_view')->value('id');

        DB::table('permissions')->updateOrInsert(['name' => self::PERMISSION], [
            'permission_group' => 4,
            'parent_id' => null,
            'display_name' => 'Trả Hoá Chất Acetonitril / Methanol Về Kho',
            'description' => implode("\n", [
                'Khai báo trả lại tồn một lượng Acetonitril hoặc Methanol đã ghi sử dụng (tab Trả hoá chất Acetonitril hoặc Methanol về kho): lượng trả cộng lại đúng lô đã lấy ra, và huỷ phiếu trả đã khai.',
                'Lưu ý: tổng lượng trả của một phiếu sử dụng không vượt số lượng đã sử dụng; huỷ phiếu trả chỉ được khi lô còn đủ tồn để trừ lại.',
            ]),
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        DB::table('permissions')->updateOrInsert(['name' => self::TAB_PERMISSION], [
            'permission_group' => 4,
            'parent_id' => $viewId,
            'display_name' => 'Tab Trả hoá chất Acetonitril hoặc Methanol về kho',
            'description' => 'Xem tab Trả hoá chất Acetonitril hoặc Methanol về kho: các phiếu sử dụng Acetonitril / Methanol còn trả được và lịch sử các lần trả về kho.',
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        // Dòng trả về kho trong Sổ sử dụng không sửa / khoá ở đó
        DB::table('permissions')->where('name', 'export_chemical_update')->update([
            'description' => implode("\n", [
                'Sửa (lô, số lượng, người kiểm tra, số PKN / OOS / BCSL, mục đích) hoặc khoá / mở khoá một dòng ở tab Sổ sử dụng hoá chất, kể cả phiếu ngày cũ. Khoá thì số lượng được cộng trả lại tồn, mở khoá thì trừ lại; mỗi lần sửa được lưu lịch sử điều chỉnh.',
                'Lưu ý: dòng sinh từ phiếu cách ly chỉ xử lý ở tab Cách ly - Loại bỏ - Huỷ; dòng chuyển cho phòng khác không sửa được; dòng trả về kho chỉ huỷ ở tab Trả hoá chất Acetonitril hoặc Methanol về kho, phiếu sử dụng đã có lượng trả không khoá / đổi lô được và không sửa số lượng nhỏ hơn lượng đã trả.',
            ]),
            'updated_at' => $now,
        ]);

        $permissionIds = DB::table('permissions')
            ->whereIn('name', [self::PERMISSION, self::TAB_PERMISSION])
            ->pluck('id');
        $roleIds = DB::table('roles')->whereIn('name', self::ROLES)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', [self::PERMISSION, self::TAB_PERMISSION])->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        DB::table('chemical_exports')->where('type', 'return')->delete();
        DB::statement("ALTER TABLE chemical_exports MODIFY `type` ENUM('export','cancel','transfer_out') NULL DEFAULT 'export'");

        if (Schema::hasColumn('chemical_exports', 'return_of_id')) {
            Schema::table('chemical_exports', function (Blueprint $table) {
                $table->dropIndex(['return_of_id']);
                $table->dropColumn('return_of_id');
            });
        }
    }
};
