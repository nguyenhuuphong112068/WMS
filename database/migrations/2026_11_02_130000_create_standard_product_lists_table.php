<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DANH SÁCH CHẤT CHUẨN THEO SẢN PHẨM (tab mới của Danh Mục Chất Chuẩn) - cùng khuôn với
 * chemical_product_lists (App\Support\ProductList).
 *
 * Mỗi phòng ban khai các "gói" = 1 Tên sản phẩm + 1 Chỉ tiêu kiểm (purposes) + các mã chất chuẩn
 * của phòng. Modal Đề nghị cấp phát chuẩn nội bộ chọn gói thì thêm sẵn các dòng đề nghị.
 *
 * Quyền xem tab category_standard_tab_product (PageTabs::seed) + quyền thao tác
 * category_standard_product_manage.
 */
return new class extends Migration
{
    private const PERMISSION = 'category_standard_product_manage';

    public function up(): void
    {
        if (! Schema::hasTable('standard_product_lists')) {
            Schema::create('standard_product_lists', function (Blueprint $table) {
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

        if (! Schema::hasTable('standard_product_list_items')) {
            Schema::create('standard_product_list_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('list_id');
                $table->unsignedBigInteger('category_id');
                // Số lượng đề nghị gợi ý (đơn vị của phòng), trống = để người dùng tự nhập
                $table->decimal('amount', 15, 4)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('list_id');
                $table->index('category_id');
            });
        }

        PageTabs::seed();

        $parent = DB::table('permissions')->where('name', 'category_standard_dept_manage')->first();

        if ($parent && ! DB::table('permissions')->where('name', self::PERMISSION)->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'permission_group' => $parent->permission_group,
                'name' => self::PERMISSION,
                'display_name' => 'Quản lý danh sách chất chuẩn theo sản phẩm',
                'description' => 'Thêm / sửa / khoá gói Sản phẩm - Chỉ tiêu và các chất chuẩn cần dùng của phòng',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Ai đang quản lý chất chuẩn của phòng thì quản lý luôn danh sách theo sản phẩm
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
            ->whereIn('name', [self::PERMISSION, 'category_standard_tab_product'])
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('standard_product_list_items');
        Schema::dropIfExists('standard_product_lists');
    }
};
