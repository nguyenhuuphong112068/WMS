<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * File đính kèm theo TỪNG MỤC dự trù hoá chất / chất chuẩn (báo giá, COA, catalogue...) - cùng
 * cơ chế với material_estimate_attachments của Dự Trù Vật Tư.
 *
 * Tên cột khoá ngoại theo đúng bảng mục dự trù của từng loại:
 *   chemical_estimate_attachments : estimate_list_id / estimate_item_id
 *   standard_estimate_attachments : standard_estimate_id / standard_estimate_item_id
 *
 * Xoá mềm bằng cột active (CFR Part 11): file gốc giữ nguyên trên đĩa để truy vết.
 */
return new class extends Migration
{
    private const TABLES = [
        'chemical_estimate_attachments' => ['estimate_list_id', 'estimate_item_id', 'chem_est_att'],
        'standard_estimate_attachments' => ['standard_estimate_id', 'standard_estimate_item_id', 'std_est_att'],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $name => [$listFk, $itemFk, $prefix]) {
            if (Schema::hasTable($name)) {
                continue;
            }

            Schema::create($name, function (Blueprint $table) use ($listFk, $itemFk, $prefix) {
                $table->id();
                $table->unsignedBigInteger($listFk);
                $table->unsignedBigInteger($itemFk)->nullable();
                $table->string('file_name');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('file_type', 100)->nullable();
                $table->string('note', 255)->nullable();
                $table->tinyInteger('active')->default(1);
                $table->string('deleted_by')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();

                $table->index($listFk, $prefix.'_list_id_index');
                $table->index($itemFk, $prefix.'_item_id_index');
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
