<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentStandard;
use App\Support\StandardCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DANH MỤC - CHẤT CHUẨN, TAB "DANH MỤC CHẤT CHUẨN CÔNG TY"
 *
 * Màn hình này có 2 tab nằm chung một trang:
 * - Tab 1 "Danh Mục Chất Chuẩn Công Ty": bản chất của chất chuẩn, dùng chung toàn công
 *   ty (chính là controller này).
 * - Tab 2 "Chất Chuẩn Của Phòng": cách dùng riêng của từng phòng, do
 *   DepartmentStandardController xử lý.
 * Controller này dựng cả trang, nên index() lấy dữ liệu cho cả hai tab.
 *
 * Cột groups lưu danh sách mã nhóm chuẩn dạng JSON, ví dụ ["PRS","VKN"].
 * Danh sách mã đầy đủ khai báo tại config/standard.php.
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", sửa lại bản ghi đã duyệt sẽ đưa về
 * "Chờ duyệt". Mọi thay đổi đều được chụp lại ở bảng standard_category_histories.
 */
class StandardCategoryController extends Controller
{
    private const TABLE = 'standard_categories';

    private const HISTORY_TABLE = 'standard_category_histories';

    private const LABEL = 'danh mục chất chuẩn công ty';

    /** Tiền tố mã chất chuẩn sinh tự động: S00001, S00002... */
    private const CODE_PREFIX = 'S';

    private const CODE_LENGTH = 5;

    /** Các cột người dùng nhập, dùng chung cho so sánh lịch sử. Mã danh mục không sửa được nên không nằm ở đây. */
    private const FIELDS = [
        'chem_names_id' => 'Tên chất chuẩn',
        'cas_no' => 'Số CAS',
        'manufacturers_id' => 'Nguồn gốc / Nhà sản xuất',
        'unit_id' => 'Đơn vị tính',
        'storage_condition_id' => 'Điều kiện bảo quản',
        'version' => 'Version',
        'groups' => 'Phân nhóm chuẩn',
        'shelf_life_months' => 'Hạn dùng mặc định (tháng)',
        'doc_no' => 'Số tài liệu',
        'note' => 'Ghi chú',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)
            ->leftJoin('chem_names', self::TABLE.'.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', self::TABLE.'.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', self::TABLE.'.unit_id', '=', 'units.id')
            ->leftJoin('storage_conditions', self::TABLE.'.storage_condition_id', '=', 'storage_conditions.id')
            ->select(
                self::TABLE.'.*',
                'chem_names.name as standard_name',
                'chem_names.cas_no as name_cas_no',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'units.name as unit_name',
                'units.short_name as unit_short_name',
                'storage_conditions.name as storage_condition_name'
            )
            ->orderBy(self::TABLE.'.code', 'asc')
            ->get();

        session()->put(['title' => 'DANH MỤC - CHẤT CHUẨN']);

        $departmentId = (int) (session('user')['selected_department_id'] ?? 0);

        // Tab 2 - Chất Chuẩn Của Phòng: cùng một trang nhưng thao tác thêm/sửa/khoá vẫn
        // gửi về DepartmentStandardController, ở đây chỉ dựng dữ liệu để hiển thị.
        $dsDatas = DepartmentStandard::rowsOfDepartment($departmentId);

        return view('pages.category.StandardCategory.list', [
            'datas' => $datas,
            'chemNames' => $this->options('chem_names', $datas->pluck('chem_names_id')->all()),
            'manufacturers' => $this->options('manufacturers', $datas->pluck('manufacturers_id')->all()),
            'units' => $this->options('units', $datas->pluck('unit_id')->all()),
            'storageConditions' => $this->options('storage_conditions', $datas->pluck('storage_condition_id')->all()),
            'groups' => config('standard.groups'),
            'nextCode' => $this->nextCode(),
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa thay vì một nút riêng
            'historyCounts' => $this->historyCounts(),
            /*
            | Danh mục dùng chung toàn công ty, nhưng mỗi phòng ban tự khai chất chuẩn nào
            | phòng mình có dùng (bảng department_standards). Cột "Phòng Ban Đang Dùng"
            | đọc từ đó - chính là cột QC / QC1 / QC2 / AD của danh mục giấy.
            */
            'departmentsByCategory' => DepartmentStandard::departmentsByCategory(),

            // Dữ liệu của tab Chất Chuẩn Của Phòng, đặt tiền tố ds để không đụng biến của tab 1
            'dsDatas' => $dsDatas,
            'dsCategories' => DepartmentStandard::categoryOptions($dsDatas->pluck('category_id')->all()),
            'dsLocations' => DepartmentStandard::locationOptions($departmentId),
            'dsStorageConditions' => DepartmentStandard::storageConditionOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        $this->checkDuplicate($validator, $request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        // Sinh mã và ghi bản ghi trong cùng một transaction để hai người thêm cùng lúc không trùng mã
        $id = DB::transaction(function () use ($request) {
            return DB::table(self::TABLE)->insertGetId($this->payload($request) + [
                'code' => $this->nextCode(),
                'app_status' => 'pending',
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $code = DB::table(self::TABLE)->where('id', $id)->value('code');

        $this->writeHistory($id, 'Thêm mới', 'Khai báo mới '.self::LABEL.', mã '.$code.'.');

        AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm '.self::LABEL.': '.$code);

        return redirect()->back()->with('success', 'Đã thêm '.self::LABEL.' mã '.$code.'! Bản ghi đang chờ duyệt.');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
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

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $note ?: 'Không đổi', $current->code);

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công! Bản ghi chuyển về chờ duyệt.');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;
        $action = $newStatus == 1 ? 'Mở khoá' : 'Khoá';

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->writeHistory(
            $current->id,
            $action,
            'Trạng thái sử dụng: '.($current->status_id == 1 ? 'Hoạt động' : 'Đã khoá')
            .' -> '.($newStatus == 1 ? 'Hoạt động' : 'Đã khoá')
        );

        AuditTrialController::log($action, self::TABLE, $current->id, 'status_id: '.$current->status_id, 'status_id: '.$newStatus);

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' '.$current->code.'!'
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
            ->leftJoin('chem_names', self::HISTORY_TABLE.'.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', self::HISTORY_TABLE.'.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', self::HISTORY_TABLE.'.unit_id', '=', 'units.id')
            ->leftJoin('storage_conditions', self::HISTORY_TABLE.'.storage_condition_id', '=', 'storage_conditions.id')
            ->select(
                self::HISTORY_TABLE.'.*',
                'chem_names.name as standard_name',
                'manufacturers.name as manufacturer_name',
                'units.name as unit_name',
                'storage_conditions.name as storage_condition_name'
            )
            ->where(self::HISTORY_TABLE.'.standard_category_id', $request->id)
            ->orderBy(self::HISTORY_TABLE.'.id', 'desc')
            ->get();

        return response()->json([
            'rows' => $rows->map(function ($row) {
                $groups = StandardCode::decodeGroups($row->groups);

                return [
                    'action' => $row->action,
                    'change_note' => $row->change_note,
                    'created_by' => $row->created_by ?: 'NA',
                    'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                    'snapshot' => [
                        'Mã chất chuẩn' => $row->code ?: '—',
                        'Tên chất chuẩn' => $row->standard_name ?: '—',
                        'Số CAS' => $row->cas_no ?: '—',
                        'Nguồn gốc / NSX' => $row->manufacturer_name ?: '—',
                        'Đơn vị tính' => $row->unit_name ?: '—',
                        'Điều kiện bảo quản' => $row->storage_condition_name ?: '—',
                        'Version' => $row->version !== null ? (string) $row->version : '—',
                        'Phân nhóm chuẩn' => $groups
                            ? implode(', ', array_map(fn ($key) => StandardCode::groupLabel($key), $groups))
                            : '—',
                        'Hạn dùng (tháng)' => $row->shelf_life_months ?: '—',
                        'Số tài liệu' => $row->doc_no ?: '—',
                        'Ghi chú' => $row->note ?: '—',
                    ],
                ];
            })->values(),
        ]);
    }

    /** Ghi nhận kết quả duyệt: ai duyệt, duyệt lúc nào. */
    private function setApproval(Request $request, string $appStatus)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần duyệt!');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => $appStatus,
            'approved_by' => $this->actor(),
            'approved_at' => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $action = $appStatus === 'approved' ? 'Phê duyệt' : 'Từ chối duyệt';

        $this->writeHistory($current->id, $action, 'Trạng thái duyệt: '.$current->app_status.' -> '.$appStatus);

        AuditTrialController::log($action, self::TABLE, $current->id, 'app_status: '.$current->app_status, 'app_status: '.$appStatus);

        return redirect()->back()->with(
            'success',
            ($appStatus === 'approved' ? 'Đã duyệt ' : 'Đã từ chối ').self::LABEL.' '.$current->code.'!'
        );
    }

    /** Chụp lại giá trị bản ghi ngay sau khi thay đổi vào bảng lịch sử. */
    private function writeHistory(int $id, string $action, ?string $note): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'standard_category_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'chem_names_id' => $row->chem_names_id,
            'cas_no' => $row->cas_no,
            'manufacturers_id' => $row->manufacturers_id,
            'unit_id' => $row->unit_id,
            'storage_condition_id' => $row->storage_condition_id,
            'version' => $row->version,
            'groups' => $row->groups,
            'shelf_life_months' => $row->shelf_life_months,
            'doc_no' => $row->doc_no,
            'note' => $row->note,
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
            $old = (string) $current->$field;
            $new = (string) $payload[$field];

            if ($old === $new) {
                continue;
            }

            if ($field === 'groups') {
                $parts[] = $title.': '
                    .($this->groupNames($current->$field) ?: '—').' -> '
                    .($this->groupNames($payload[$field]) ?: '—');

                continue;
            }

            if (isset($labels[$field])) {
                $parts[] = $title.': '
                    .($labels[$field][$current->$field] ?? '—').' -> '
                    .($labels[$field][$payload[$field]] ?? '—');

                continue;
            }

            $parts[] = $title.': '.($old === '' ? '—' : $old).' -> '.($new === '' ? '—' : $new);
        }

        return implode(' | ', $parts);
    }

    /** Chuỗi JSON mã nhóm -> "Chuẩn Chính (PRS), Chuẩn Viện (VKN)". */
    private function groupNames($value): string
    {
        return implode(', ', array_map(
            fn ($key) => StandardCode::groupLabel($key),
            StandardCode::decodeGroups($value)
        ));
    }

    /** Bảng tra id -> tên của các nguồn dữ liệu gốc, dùng để viết mô tả thay đổi. */
    private function labelMaps(): array
    {
        return [
            'chem_names_id' => DB::table('chem_names')->pluck('name', 'id')->all(),
            'manufacturers_id' => DB::table('manufacturers')->pluck('name', 'id')->all(),
            'unit_id' => DB::table('units')->pluck('name', 'id')->all(),
            'storage_condition_id' => DB::table('storage_conditions')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Mã chất chuẩn kế tiếp theo dạng S00001: lấy số lớn nhất đang có rồi cộng 1.
     *
     * Màn hình này chỉ khoá bản ghi (deActive) chứ không xoá, nên mã không bị dùng lại.
     */
    private function nextCode(): string
    {
        $numbers = DB::table(self::TABLE)
            ->where('code', 'like', self::CODE_PREFIX.'%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen(self::CODE_PREFIX)));

        $next = ($numbers->max() ?? 0) + 1;

        return self::CODE_PREFIX.str_pad((string) $next, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Không cho khai báo trùng cùng một tổ hợp Tên + Nguồn gốc + Version.
     *
     * Version mới của cùng một chất chuẩn là một dòng danh mục riêng (giá trị công bố
     * khác nhau), nên version nằm trong bộ khoá kiểm tra trùng.
     */
    private function checkDuplicate($validator, Request $request, $ignoreId = null): void
    {
        $validator->after(function ($validator) use ($request, $ignoreId) {
            $exists = DB::table(self::TABLE)
                ->where('chem_names_id', $request->chem_names_id)
                ->where('manufacturers_id', $request->manufacturers_id)
                ->where('version', (int) $request->version)
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'chem_names_id',
                    'Tổ hợp Tên chất chuẩn - Nguồn gốc/NSX - Version này đã có trong danh mục.'
                );
            }
        });
    }

    /**
     * Số lần thay đổi của từng dòng danh mục: [standard_category_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc khai báo chứ không phải một lần sửa.
     */
    private function historyCounts()
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('standard_category_id', DB::raw('COUNT(*) as times'))
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('standard_category_id')
            ->pluck('times', 'standard_category_id');
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

    /** Mã chất chuẩn sinh tự động nên không nằm trong danh sách kiểm tra. */
    private function rules(): array
    {
        return [
            'chem_names_id' => ['required', 'integer', 'exists:chem_names,id'],
            'cas_no' => ['nullable', 'max:50'],
            'manufacturers_id' => ['required', 'integer', 'exists:manufacturers,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'storage_condition_id' => ['nullable', 'integer', 'exists:storage_conditions,id'],
            'version' => ['required', 'integer', 'min:0', 'max:999'],
            // Phải chọn ít nhất một nhóm: nhóm chuẩn quyết định mã ống chuẩn lúc nhập,
            // chất chuẩn không có nhóm thì không nhập kho được.
            'groups' => ['required', 'array', 'min:1'],
            'groups.*' => [Rule::in(array_keys(config('standard.groups')))],
            'shelf_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'doc_no' => ['nullable', 'max:20'],
            'note' => ['nullable', 'max:500'],
        ];
    }

    private function payload(Request $request): array
    {
        // Giữ đúng thứ tự khai báo trong config để chuỗi JSON luôn ổn định
        $selected = (array) $request->input('groups', []);
        $groups = array_values(array_intersect(array_keys(config('standard.groups')), $selected));

        return [
            'chem_names_id' => (int) $request->chem_names_id,
            'cas_no' => $this->nullIfBlank($request->cas_no),
            'manufacturers_id' => (int) $request->manufacturers_id,
            'unit_id' => (int) $request->unit_id,
            'storage_condition_id' => $request->storage_condition_id ? (int) $request->storage_condition_id : null,
            'version' => (int) $request->version,
            'groups' => $groups ? json_encode($groups, JSON_UNESCAPED_UNICODE) : null,
            'shelf_life_months' => $this->nullIfBlank($request->shelf_life_months) === null
                ? null
                : (int) $request->shelf_life_months,
            'doc_no' => $this->nullIfBlank($request->doc_no),
            'note' => $this->nullIfBlank($request->note),
        ];
    }

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function messages(): array
    {
        return [
            'chem_names_id.required' => 'Vui lòng chọn tên chất chuẩn.',
            'chem_names_id.exists' => 'Tên chất chuẩn không hợp lệ.',
            'cas_no.max' => 'Số CAS tối đa 50 ký tự.',
            'manufacturers_id.required' => 'Vui lòng chọn nguồn gốc / nhà sản xuất.',
            'manufacturers_id.exists' => 'Nguồn gốc / nhà sản xuất không hợp lệ.',
            'unit_id.required' => 'Vui lòng chọn đơn vị tính.',
            'unit_id.exists' => 'Đơn vị tính không hợp lệ.',
            'storage_condition_id.exists' => 'Điều kiện bảo quản không hợp lệ.',
            'version.required' => 'Vui lòng nhập version.',
            'version.integer' => 'Version phải là số nguyên.',
            'version.min' => 'Version nhỏ nhất là 0.',
            'version.max' => 'Version tối đa là 999.',
            'groups.required' => 'Vui lòng chọn ít nhất một phân nhóm chuẩn.',
            'groups.min' => 'Vui lòng chọn ít nhất một phân nhóm chuẩn.',
            'groups.*.in' => 'Phân nhóm chuẩn không hợp lệ.',
            'shelf_life_months.integer' => 'Hạn dùng phải là số tháng nguyên.',
            'shelf_life_months.min' => 'Hạn dùng tối thiểu 1 tháng.',
            'shelf_life_months.max' => 'Hạn dùng tối đa 1200 tháng (100 năm).',
            'doc_no.max' => 'Số tài liệu tối đa 20 ký tự.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }
}
