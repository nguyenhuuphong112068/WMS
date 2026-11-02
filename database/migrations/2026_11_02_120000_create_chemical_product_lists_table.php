<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DANH SÁCH HOÁ CHẤT THEO SẢN PHẨM (tab mới của Danh Mục Hoá Chất).
 *
 * Mỗi phòng ban khai các "gói" = 1 Tên sản phẩm (product_names, lưu dạng chữ như ô Mục Đích
 * của phiếu Sử Dụng) + 1 Chỉ tiêu kiểm (purposes) + các mã hoá chất của phòng cần dùng.
 * Modal Sử Dụng Hoá Chất chọn gói thì gợi ý luôn các hoá chất này.
 *
 * Quyền xem tab category_chemical_tab_product (PageTabs::seed) + quyền thao tác
 * category_chemical_product_manage.
 */
return new class extends Migration
{
    private const PERMISSION = 'category_chemical_product_manage';

    public function up(): void
    {
        if (! Schema::hasTable('chemical_product_lists')) {
            Schema::create('chemical_product_lists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('department_id');
                $table->string('product_name', 255);
                $table->unsignedBigInteger('purpose_id');
                $table->string('note', 500)->nullable();
                $table->tinyInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();

                $table->index('department_id');
                $table->index('purpose_id');
            });
        }

        if (! Schema::hasTable('chemical_product_list_items')) {
            Schema::create('chemical_product_list_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('list_id');
                $table->unsignedBigInteger('category_id');
                // Lượng dùng gợi ý cho một lần (đơn vị của phòng), trống = để người dùng tự nhập
                $table->decimal('amount', 15, 4)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('list_id');
                $table->index('category_id');
            });
        }

        PageTabs::seed();

        $parent = DB::table('permissions')->where('name', 'category_chemical_dept_manage')->first();

        if ($parent && ! DB::table('permissions')->where('name', self::PERMISSION)->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'permission_group' => $parent->permission_group,
                'name' => self::PERMISSION,
                'display_name' => 'Quản lý danh sách hoá chất theo sản phẩm',
                'description' => 'Thêm / sửa / khoá gói Sản phẩm - Chỉ tiêu và các hoá chất cần dùng của phòng',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Ai đang quản lý hoá chất của phòng thì quản lý luôn danh sách theo sản phẩm
            $roleIds = DB::table('role_permission')->where('permission_id', $parent->id)->pluck('role_id')
                ->merge(DB::table('roles')->where('name', 'Admin')->pluck('id'))
                ->unique();

            foreach ($roleIds as $roleId) {
                DB::table('role_permission')->insert(['role_id' => $roleId, 'permission_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', [self::PERMISSION, 'category_chemical_tab_product'])
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('chemical_product_list_items');
        Schema::dropIfExists('chemical_product_lists');
    }
};
