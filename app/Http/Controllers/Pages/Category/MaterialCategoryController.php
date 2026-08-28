<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * DANH MỤC - VẬT TƯ, TAB "DANH MỤC VẬT TƯ CÔNG TY"
 *
 * Màn hình này có 2 tab nằm chung một trang:
 * - Tab 1 "Danh Mục Vật Tư Công Ty": bản chất của vật tư, dùng chung toàn công ty (chính
 *   là controller này). Một dòng = một tổ hợp Tên vật tư + Nhà sản xuất.
 * - Tab 2 "Vật Tư Của Phòng": cách dùng riêng của từng phòng (phân loại, đơn vị tính,
 *   ngưỡng tồn), do DepartmentMaterialController xử lý.
 * Controller này dựng cả trang, nên index() lấy dữ liệu cho cả hai tab.
 *
 * PHÂN LOẠI và ĐƠN VỊ TÍNH không khai ở đây: mỗi phòng có bộ nhóm phân loại riêng và
 * nhập / xuất theo đơn vị của phòng mình, nên hai thứ đó nằm ở tab "Vật Tư Của Phòng"
 * (department_materials.classification_id / unit_id).
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", sửa lại bản ghi đã duyệt sẽ đưa về "Chờ duyệt".
 * Mọi thay đổi đều được chụp lại ở bảng material_category_histories.
 */
class MaterialCategoryController extends Controller
{
    private const TABLE = 'material_categories';
    private const HISTORY_TABLE = 'material_category_histories';
    private const LABEL = 'danh mục vật tư công ty';

    /** Các cột người dùng nhập, dùng chung cho validate - lưu - so sánh lịch sử. */
    private const FIELDS = [
        'material_names_id' => 'Tên vật tư',
        'manufacturers_id' => 'Nhà sản xuất',
        'technical_specification' => 'Thông tin kỹ thuật',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)
            ->leftJoin('material_names', self::TABLE . '.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', self::TABLE . '.manufacturers_id', '=', 'manufacturers.id')
            ->select(
                self::TABLE . '.*',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name'
            )
            ->orderBy('material_names.name', 'asc')
            ->get();

        session()->put(['title' => 'DANH MỤC - VẬT TƯ']);

        $departmentId = (int) (session('user')['selected_department_id'] ?? 0);

        // Tab 2 - Vật Tư Của Phòng: cùng một trang nhưng thao tác thêm/sửa/khoá vẫn gửi
        // về DepartmentMaterialController, ở đây chỉ dựng dữ liệu để hiển thị.
        $dmDatas = DepartmentMaterial::rowsOfDepartment($departmentId);

        return view('pages.category.MaterialCategory.list', [
            'datas' => $datas,
            'materialNames' => $this->options('material_names', $datas->pluck('material_names_id')->all()),
            'manufacturers' => $this->options('manufacturers', $datas->pluck('manufacturers_id')->all()),
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa thay vì một nút riêng
            'historyCounts' => $this->historyCounts(),
            /*
            | Danh mục dùng chung toàn công ty, nhưng mỗi phòng ban tự khai vật tư nào phòng
            | mình có dùng (bảng department_materials). Cột "Phòng Ban Đang Dùng" đọc từ đó.
            */
            'departmentsByCategory' => DepartmentMaterial::departmentsByCategory(),

            // Dữ liệu của tab Vật Tư Của Phòng, đặt tiền tố dm để không đụng biến của tab 1
            'dmDatas' => $dmDatas,
            'dmCategories' => DepartmentMaterial::categoryOptions($dmDatas->pluck('category_id')->all()),
            'dmClassifications' => DepartmentMaterial::classificationOptions(
                $departmentId,
                $dmDatas->pluck('classification_id')->all()
            ),
            'dmUnits' => DepartmentMaterial::unitOptions($dmDatas->pluck('unit_id')->all()),
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
            ->leftJoin('units', self::HISTORY_TABLE . '.unit_id', '=', 'units.id')
            ->select(
                self::HISTORY_TABLE . '.*',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name',
                'units.name as unit_name'
            )
            ->where(self::HISTORY_TABLE . '.material_category_id', $request->id)
            ->orderBy(self::HISTORY_TABLE . '.id', 'desc')
            ->get();

        return response()->json([
            'rows' => $rows->map(function ($row) {
                $snapshot = [
                    'Tên vật tư' => $row->material_name ?: '—',
                    'Nhà sản xuất' => $row->manufacturer_name ?: '—',
                    'Thông tin kỹ thuật' => $row->technical_specification ?: '—',
                ];

                // Đơn vị tính đã chuyển sang danh mục của phòng nên bản ghi mới không còn
                // ghi cột này. Ảnh chụp cũ vẫn hiện lại để không mất vết đã thay đổi.
                if ($row->unit_name) {
                    $snapshot['Đơn vị tính (trước khi chuyển về phòng)'] = $row->unit_name;
                }

                return [
                    'action' => $row->action,
                    'change_note' => $row->change_note,
                    'created_by' => $row->created_by ?: 'NA',
                    'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                    'snapshot' => $snapshot,
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
            'technical_specification' => $row->technical_specification,
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

            if ($field === 'technical_specification') {
                $parts[] = $title . ': '
                    . ($current->$field ?? '—') . ' -> '
                    . ($payload[$field] ?? '—');
            } else {
                $map = $labels[$field];
                $parts[] = $title . ': '
                    . ($map[$current->$field] ?? '—') . ' -> '
                    . ($map[$payload[$field]] ?? '—');
            }
        }

        return implode(' | ', $parts);
    }

    /** Bảng tra id -> tên của các nguồn dữ liệu gốc, dùng để viết mô tả thay đổi. */
    private function labelMaps(): array
    {
        return [
            'material_names_id' => DB::table('material_names')->pluck('name', 'id')->all(),
            'manufacturers_id' => DB::table('manufacturers')->pluck('name', 'id')->all(),
        ];
    }

    /** Chuỗi nhận diện một dòng danh mục, dùng cho Audit Trail. */
    private function describe(int $id): string
    {
        $row = DB::table(self::TABLE)
            ->leftJoin('material_names', self::TABLE . '.material_names_id', '=', 'material_names.id')
            ->leftJoin('manufacturers', self::TABLE . '.manufacturers_id', '=', 'manufacturers.id')
            ->select(
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name'
            )
            ->where(self::TABLE . '.id', $id)
            ->first();

        if (! $row) {
            return 'NA';
        }

        return implode(' | ', [
            $row->material_name ?: '—',
            $row->manufacturer_name ?: '—',
        ]);
    }

    /**
     * Không cho khai báo trùng cùng một tổ hợp Vật tư + Nhà sản xuất.
     */
    private function checkDuplicate($validator, Request $request, $ignoreId = null): void
    {
        $validator->after(function ($validator) use ($request, $ignoreId) {
            $exists = DB::table(self::TABLE)
                ->where('material_names_id', $request->material_names_id)
                ->where('manufacturers_id', $request->manufacturers_id)
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists();

            if ($exists) {
                $validator->errors()->add('material_names_id', 'Tổ hợp Vật tư - Nhà sản xuất này đã có trong danh mục.');
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
            'technical_specification' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function payload(Request $request): array
    {
        $techSpec = trim((string) $request->technical_specification);

        return [
            'material_names_id' => (int) $request->material_names_id,
            'manufacturers_id' => (int) $request->manufacturers_id,
            'technical_specification' => $techSpec === '' ? null : $techSpec,
        ];
    }

    private function messages(): array
    {
        return [
            'material_names_id.required' => 'Vui lòng chọn tên vật tư.',
            'material_names_id.exists' => 'Tên vật tư không hợp lệ.',
            'manufacturers_id.required' => 'Vui lòng chọn nhà sản xuất.',
            'manufacturers_id.exists' => 'Nhà sản xuất không hợp lệ.',
            'technical_specification.max' => 'Thông tin kỹ thuật tối đa 100 ký tự.',
        ];
    }
}
