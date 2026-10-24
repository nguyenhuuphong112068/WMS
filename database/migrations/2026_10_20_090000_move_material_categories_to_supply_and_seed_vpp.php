<?php

use App\Support\MaterialClassification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DANH MỤC VẬT TƯ CÔNG TY: TOÀN BỘ VỀ CUNG ỨNG + THÊM 10 VĂN PHÒNG PHẨM (HÀNH CHÁNH)
 *
 * 1. Mọi dòng material_categories hiện có đang khai Bộ Phận Mua Hàng khác Cung Ứng chuyển về
 *    Cung Ứng (supply). Mỗi dòng đổi ghi một dòng lịch sử "Cập nhật", trạng thái duyệt giữ nguyên.
 * 2. Thêm 10 danh mục văn phòng phẩm, Bộ Phận Mua Hàng = Hành Chánh (admin), ĐÃ DUYỆT để dùng
 *    ngay cho đề nghị VPP. Tên vật tư chưa có trong dữ liệu gốc thì tạo mới (đã duyệt).
 *    Nhận diện qua Thông tin kỹ thuật "VPP #nn" - chạy lại an toàn, dòng đã có thì bỏ qua.
 */
return new class extends Migration
{
    private const REASON = 'Chuyển danh mục vật tư công ty về Bộ phận mua hàng Cung Ứng';

    private const ACTOR = 'Hệ thống';

    private const TAG = 'VPP #';

    private const OFFICE_SUPPLIES = [
        'Giấy in A4 Double A 70gsm',
        'Bút bi Thiên Long TL-027 xanh',
        'Bút lông dầu Thiên Long PM-09',
        'Bút dạ quang Thiên Long HL-03',
        'Bìa hồ sơ còng 7cm',
        'Kẹp giấy đen 25mm',
        'Ghim bấm số 10',
        'Giấy note 3x3 inch',
        'Băng keo trong 5cm',
        'Sổ tay ghi chép A5',
    ];

    public function up(): void
    {
        $this->moveToSupply();
        $this->seedOfficeSupplies();
    }

    private function moveToSupply(): void
    {
        $rows = DB::table('material_categories')
            ->where(fn ($q) => $q->where('purchasing_department', '!=', 'supply')->orWhereNull('purchasing_department'))
            ->where('technical_specification', 'not like', self::TAG.'%')
            ->orderBy('id')->get();

        foreach ($rows as $row) {
            DB::transaction(function () use ($row) {
                DB::table('material_categories')->where('id', $row->id)->update([
                    'purchasing_department' => 'supply',
                    'updated_by' => self::ACTOR,
                    'updated_at' => now(),
                ]);

                DB::table('material_category_histories')->insert([
                    'material_category_id' => $row->id,
                    'action' => 'Cập nhật',
                    'code' => $row->code,
                    'material_names_id' => $row->material_names_id,
                    'manufacturers_id' => $row->manufacturers_id,
                    'part_number' => $row->part_number,
                    'technical_specification' => $row->technical_specification,
                    'classification' => $row->classification,
                    'purchasing_department' => 'supply',
                    'lead_time_days' => $row->lead_time_days,
                    'app_status' => $row->app_status,
                    'status_id' => $row->status_id,
                    'change_note' => 'Bộ phận mua hàng: '.(MaterialClassification::purchasingLabel($row->purchasing_department) ?: '—').' -> Cung Ứng',
                    'change_reason' => self::REASON,
                    'created_by' => self::ACTOR,
                    'created_at' => now(),
                ]);
            });
        }
    }

    private function seedOfficeSupplies(): void
    {
        $manufacturerIds = DB::table('manufacturers')
            ->where('status_id', 1)->where('app_status', 'approved')
            ->orderBy('id')->pluck('id')->all();

        if (! $manufacturerIds) {
            return;
        }

        $classification = MaterialClassification::encode([
            'price' => 'low',
            'importance' => 'low',
            'dangerous' => 'no',
            'qa_calibration' => 'no',
        ]);

        $nextNumber = (int) DB::table('material_categories')->where('code', 'like', 'M%')->pluck('code')
            ->map(fn ($code) => (int) substr($code, 1))->max() + 1;

        foreach (self::OFFICE_SUPPLIES as $i => $name) {
            $tag = self::TAG.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);

            if (DB::table('material_categories')->where('technical_specification', $tag)->exists()) {
                continue;
            }

            DB::transaction(function () use ($name, $tag, $i, $manufacturerIds, $classification, &$nextNumber) {
                $nameId = DB::table('material_names')->where('name', $name)->value('id')
                    ?? DB::table('material_names')->insertGetId([
                        'name' => $name,
                        'app_status' => 'approved',
                        'status_id' => 1,
                        'created_by' => self::ACTOR,
                        'approved_by' => self::ACTOR,
                        'approved_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                $row = [
                    'code' => 'M'.str_pad((string) $nextNumber++, 5, '0', STR_PAD_LEFT),
                    'material_names_id' => $nameId,
                    'manufacturers_id' => $manufacturerIds[$i % count($manufacturerIds)],
                    'technical_specification' => $tag,
                    'classification' => $classification,
                    'purchasing_department' => MaterialClassification::PURCHASING_ADMIN,
                    'lead_time_days' => 7,
                    'app_status' => 'approved',
                    'status_id' => 1,
                    'created_by' => self::ACTOR,
                    'approved_by' => self::ACTOR,
                    'approved_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $id = DB::table('material_categories')->insertGetId($row);

                DB::table('material_category_histories')->insert([
                    'material_category_id' => $id,
                    'code' => $row['code'],
                    'action' => 'Thêm mới',
                    'material_names_id' => $row['material_names_id'],
                    'manufacturers_id' => $row['manufacturers_id'],
                    'technical_specification' => $row['technical_specification'],
                    'classification' => $row['classification'],
                    'purchasing_department' => $row['purchasing_department'],
                    'lead_time_days' => $row['lead_time_days'],
                    'app_status' => $row['app_status'],
                    'status_id' => $row['status_id'],
                    'change_note' => 'Danh mục văn phòng phẩm, mã '.$row['code'].'.',
                    'created_by' => self::ACTOR,
                    'created_at' => now(),
                ]);
            });
        }
    }

    /** Xoá 10 dòng VPP (nếu chưa được dùng) và trả Bộ Phận Mua Hàng cũ cho các dòng đã đổi. */
    public function down(): void
    {
        $vppIds = DB::table('material_categories')->where('technical_specification', 'like', self::TAG.'%')->pluck('id');
        DB::table('material_category_histories')->whereIn('material_category_id', $vppIds)->delete();
        DB::table('material_categories')->whereIn('id', $vppIds)->delete();

        $histories = DB::table('material_category_histories')->where('change_reason', self::REASON)->get();

        foreach ($histories as $history) {
            $previous = DB::table('material_category_histories')
                ->where('material_category_id', $history->material_category_id)
                ->where('id', '<', $history->id)
                ->orderByDesc('id')->value('purchasing_department');

            DB::table('material_categories')->where('id', $history->material_category_id)
                ->update(['purchasing_department' => $previous ?? 'admin', 'updated_at' => now()]);
        }

        DB::table('material_category_histories')->where('change_reason', self::REASON)->delete();
    }
};
