<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * DANH MỤC - DANH MỤC VẬT TƯ
 *
 * Một dòng danh mục = một tổ hợp Tên vật tư + Nhà sản xuất + Nhà cung cấp + Đơn vị tính.
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", sửa lại bản ghi đã duyệt sẽ đưa về "Chờ duyệt".
 * Mọi thay đổi đều được chụp lại ở bảng material_category_histories.
 */
class MaterialCategoryController extends Controller
{
    private const TABLE = 'material_categories';
    private const HISTORY_TABLE = 'material_category_histories';
    private const LABEL = 'danh mục vật tư';

    /** Các cột người dùng nhập, dùng chung cho validate - lưu - so sánh lịch sử. */
    private const FIELDS = [
        'material_names_id' => 'Tên vật tư',
        'manufacturers_id' => 'Nhà sản xuất',
        'suppliers_id' => 'Nhà cung cấp',
        'unit_id' => 'Đơn vị tính',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)
            ->leftJoin('material_names', self::TABLE . '.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', self::TABLE . '.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('suppliers', self::TABLE . '.suppliers_id', '=', 'suppliers.id')
            ->leftJoin('units', self::TABLE . '.unit_id', '=', 'units.id')
            ->select(
                self::TABLE . '.*',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'suppliers.name as supplier_name',
                'units.name as unit_name',
                'units.short_name as unit_short_name'
            )
            ->orderBy('material_names.name', 'asc')
            ->get();

        session()->put(['title' => 'DANH MỤC - DANH MỤC VẬT TƯ']);

        return view('pages.category.MaterialCategory.list', [
            'datas' => $datas,
            'materialNames' => $this->options('material_names', $datas->pluck('material_names_id')->all()),
            'manufacturers' => $this->options('manufacturers', $datas->pluck('manufacturers_id')->all()),
            'suppliers' => $this->options('suppliers', $datas->pluck('suppliers_id')->all()),
            'units' => $this->options('units', $datas->pluck('unit_id')->all()),
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa thay vì một nút riêng
            'historyCounts' => $this->historyCounts(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        $this->checkDuplicate($validator, $request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
            'app_status' => 'pending',
            'status_id' => 1,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->writeHistory($id, 'Thêm mới', 'Khai báo mới ' . self::LABEL . '.');

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', $this->describe($id));

        return redirect()->back()->with('success', 'Đã thêm ' . self::LABEL . ' thành công! Bản ghi đang chờ duyệt.');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        $this->checkDuplicate($validator, $request, $current->id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = $this->changeNote($current, $payload);

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            // Sửa nội dung thì phải duyệt lại từ đầu
            'app_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->writeHistory($current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.');

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $note ?: 'Không đổi', $this->describe($current->id));

        return redirect()->back()->with('success', 'Cập nhật ' . self::LABEL . ' thành công! Bản ghi chuyển về chờ duyệt.');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần thay đổi trạng thái!');
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;
        $action = $newStatus == 1 ? 'Mở khoá' : 'Khoá';

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->writeHistory($current->id, $action, 'Trạng thái sử dụng: ' . ($current->status_id == 1 ? 'Hoạt động' : 'Đã khoá') . ' -> ' . ($newStatus == 1 ? 'Hoạt động' : 'Đã khoá'));

        AuditTrialController::log($action, self::TABLE, $current->id, 'status_id: ' . $current->status_id, 'status_id: ' . $newStatus);

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . self::LABEL . '!'
        );
    }

    public function approve(Request $request)
    {
        return $this->setApproval($request, 'approved');
    }

    public function reject(Request $request)
    {
        return $this->setApproval($request, 'rejected');
    }

    /** Trả về lịch sử thay đổi của một dòng danh mục cho modal xem lịch sử. */
    public function history(Request $request)
    {
        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('material_names', self::HISTORY_TABLE . '.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', self::HISTORY_TABLE . '.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('suppliers', self::HISTORY_TABLE . '.suppliers_id', '=', 'suppliers.id')
            ->leftJoin('units', self::HISTORY_TABLE . '.unit_id', '=', 'units.id')
            ->select(
                self::HISTORY_TABLE . '.*',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'suppliers.name as supplier_name',
                'units.name as unit_name'
            )
            ->where(self::HISTORY_TABLE . '.material_category_id', $request->id)
            ->orderBy(self::HISTORY_TABLE . '.id', 'desc')
            ->get();

        return response()->json([
            'rows' => $rows->map(function ($row) {
                return [
                    'action' => $row->action,
                    'change_note' => $row->change_note,
                    'created_by' => $row->created_by ?: 'NA',
                    'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                    'snapshot' => [
                        'Tên vật tư' => $row->material_name ?: '—',
                        'Nhà sản xuất' => $row->manufacturer_name ?: '—',
                        'Nhà cung cấp' => $row->supplier_name ?: '—',
                        'Đơn vị tính' => $row->unit_name ?: '—',
                    ],
                ];
            })->values(),
        ]);
    }

    /**
     * Số lần thay đổi của từng dòng danh mục: [material_category_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc khai báo chứ không phải một lần sửa. Badge trên
     * nút Sửa chỉ hiện khi dòng danh mục thật sự đã bị đổi ít nhất một lần.
     */
    private function historyCounts()
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('material_category_id', DB::raw('COUNT(*) as times'))
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('material_category_id')
            ->pluck('times', 'material_category_id');
    }

    /** Ghi nhận kết quả duyệt: ai duyệt, duyệt lúc nào. */
    private function setApproval(Request $request, string $appStatus)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần duyệt!');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => $appStatus,
            'approved_by' => $this->actor(),
            'approved_at' => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $action = $appStatus === 'approved' ? 'Phê duyệt' : 'Từ chối duyệt';

        $this->writeHistory($current->id, $action, 'Trạng thái duyệt: ' . $current->app_status . ' -> ' . $appStatus);

        AuditTrialController::log($action, self::TABLE, $current->id, 'app_status: ' . $current->app_status, 'app_status: ' . $appStatus);

        return redirect()->back()->with(
            'success',
            ($appStatus === 'approved' ? 'Đã duyệt ' : 'Đã từ chối ') . self::LABEL . '!'
        );
    }

    /**
     * Chụp lại giá trị bản ghi ngay sau khi thay đổi vào bảng lịch sử.
     */
    private function writeHistory(int $id, string $action, ?string $note): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'material_category_id' => $row->id,
            'action' => $action,
            'material_names_id' => $row->material_names_id,
            'manufacturers_id' => $row->manufacturers_id,
            'suppliers_id' => $row->suppliers_id,
            'unit_id' => $row->unit_id,
            'app_status' => $row->app_status,
            'status_id' => $row->status_id,
            'change_note' => $note,
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    /** Mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới". */
    private function changeNote($current, array $payload): string
    {
        $labels = $this->labelMaps();
        $parts = [];

        foreach (self::FIELDS as $field => $title) {
            if ((string) $current->$field === (string) $payload[$field]) {
                continue;
            }

            $map = $labels[$field];
            $parts[] = $title . ': '
                . ($map[$current->$field] ?? '—') . ' -> '
                . ($map[$payload[$field]] ?? '—');
        }

        return implode(' | ', $parts);
    }

    /** Bảng tra id -> tên của 4 nguồn dữ liệu gốc, dùng để viết mô tả thay đổi. */
    private function labelMaps(): array
    {
        return [
            'material_names_id' => DB::table('material_names')->pluck('name', 'id')->all(),
            'manufacturers_id' => DB::table('manufacturers')->pluck('name', 'id')->all(),
            'suppliers_id' => DB::table('suppliers')->pluck('name', 'id')->all(),
            'unit_id' => DB::table('units')->pluck('name', 'id')->all(),
        ];
    }

    /** Chuỗi nhận diện một dòng danh mục, dùng cho Audit Trail. */
    private function describe(int $id): string
    {
        $row = DB::table(self::TABLE)
            ->leftJoin('material_names', self::TABLE . '.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', self::TABLE . '.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('suppliers', self::TABLE . '.suppliers_id', '=', 'suppliers.id')
            ->leftJoin('units', self::TABLE . '.unit_id', '=', 'units.id')
            ->select(
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'suppliers.name as supplier_name',
                'units.name as unit_name'
            )
            ->where(self::TABLE . '.id', $id)
            ->first();

        if (! $row) {
            return 'NA';
        }

        return implode(' | ', [
            $row->material_name ?: '—',
            $row->manufacturer_name ?: '—',
            $row->supplier_name ?: '—',
            $row->unit_name ?: '—',
        ]);
    }

    /**
     * Không cho khai báo trùng cùng một tổ hợp Vật tư + NSX + NCC + ĐVT.
     */
    private function checkDuplicate($validator, Request $request, $ignoreId = null): void
    {
        $validator->after(function ($validator) use ($request, $ignoreId) {
            $exists = DB::table(self::TABLE)
                ->where('material_names_id', $request->material_names_id)
                ->where('manufacturers_id', $request->manufacturers_id)
                ->where('suppliers_id', $request->suppliers_id)
                ->where('unit_id', $request->unit_id)
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists();

            if ($exists) {
                $validator->errors()->add('material_names_id', 'Tổ hợp Vật tư - Nhà sản xuất - Nhà cung cấp - Đơn vị tính này đã có trong danh mục.');
            }
        });
    }

    /**
     * Chỉ cho chọn dữ liệu gốc đã duyệt và đang hoạt động.
     * Riêng những id đang được danh mục sử dụng thì vẫn giữ lại trong ô chọn
     * để màn hình cập nhật không bị mất giá trị cũ.
     */
    private function options(string $table, array $usedIds)
    {
        $usedIds = array_values(array_filter($usedIds));

        return DB::table($table)
            ->where(function ($query) use ($usedIds) {
                $query->where(function ($sub) {
                    $sub->where('status_id', 1)->where('app_status', 'approved');
                });

                if ($usedIds) {
                    $query->orWhereIn('id', $usedIds);
                }
            })
            ->orderBy('name', 'asc')
            ->get();
    }

    private function actor(): string
    {
        return session('user')['fullName'] ?? 'NA';
    }

    private function rules(): array
    {
        return [
            'material_names_id' => ['required', 'integer', 'exists:material_names,id'],
            'manufacturers_id' => ['required', 'integer', 'exists:manufacturers,id'],
            'suppliers_id' => ['required', 'integer', 'exists:suppliers,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
        ];
    }

    private function payload(Request $request): array
    {
        return [
            'material_names_id' => (int) $request->material_names_id,
            'manufacturers_id' => (int) $request->manufacturers_id,
            'suppliers_id' => (int) $request->suppliers_id,
            'unit_id' => (int) $request->unit_id,
        ];
    }

    private function messages(): array
    {
        return [
            'material_names_id.required' => 'Vui lòng chọn tên vật tư.',
            'material_names_id.exists' => 'Tên vật tư không hợp lệ.',
            'manufacturers_id.required' => 'Vui lòng chọn nhà sản xuất.',
            'manufacturers_id.exists' => 'Nhà sản xuất không hợp lệ.',
            'suppliers_id.required' => 'Vui lòng chọn nhà cung cấp.',
            'suppliers_id.exists' => 'Nhà cung cấp không hợp lệ.',
            'unit_id.required' => 'Vui lòng chọn đơn vị tính.',
            'unit_id.exists' => 'Đơn vị tính không hợp lệ.',
        ];
    }
}
