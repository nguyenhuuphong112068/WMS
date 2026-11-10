<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Concerns\StoresBatchItems;
use App\Http\Controllers\Concerns\UpdatesCategoryLeadTime;
use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentMaterial;
use App\Support\MaterialClassification;
use App\Support\MaterialPeriodicRequest;
use App\Support\TitleSuggestions;
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
 *   ngưỡng tồn, định khu), do DepartmentMaterialController xử lý.
 * Controller này dựng cả trang, nên index() lấy dữ liệu cho cả hai tab.
 *
 * PHÂN LOẠI khai ở đây, dùng chung toàn công ty: một vật tư mang nhiều tiêu chí cùng lúc
 * (giá / mức độ quan trọng / nguồn cấp phát / hàng nguy hiểm / cần hiệu chuẩn), lưu JSON ở
 * cột classification - xem App\Support\MaterialClassification. BỘ PHẬN MUA HÀNG và THỜI
 * GIAN ĐẶT HÀNG cũng vậy.
 *
 * Riêng ĐƠN VỊ TÍNH vẫn không khai ở đây: mỗi phòng nhập / xuất theo đơn vị của phòng mình
 * nên nằm ở tab "Vật Tư Của Phòng" (material_department_categories.unit_id).
 *
 * Modal Thêm mới là BẢNG NHIỀU DÒNG (items[i][...]): một lần lưu được nhiều mã, mã M00001...
 * cấp liên tiếp theo thứ tự dòng. Modal Cập nhật vẫn sửa từng dòng.
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", sửa lại bản ghi đã duyệt sẽ đưa về "Chờ duyệt".
 * Mọi thay đổi đều được chụp lại ở bảng material_category_histories.
 */
class MaterialCategoryController extends Controller
{
    use RequiresChangeReason;
    use StoresBatchItems;
    use VerifiesSignature;
    use UpdatesCategoryLeadTime;

    private const TABLE = 'material_categories';
    private const HISTORY_TABLE = 'material_category_histories';
    private const LABEL = 'danh mục vật tư công ty';

    /** Quyền điều chỉnh Thời gian đặt hàng - xem UpdatesCategoryLeadTime */
    private const LEAD_TIME_PERMISSION = 'category_material_lead_time';

    /** Tiền tố mã danh mục sinh tự động: M00001, M00002... */
    private const CODE_PREFIX = 'M';
    private const CODE_LENGTH = 5;

    /** Các cột người dùng nhập, dùng chung cho validate - lưu - so sánh lịch sử. */
    private const FIELDS = [
        'material_names_id' => 'Tên vật tư',
        'manufacturers_id' => 'Nhà sản xuất',
        'part_number' => 'Part Number',
        'technical_specification' => 'Thông tin kỹ thuật',
        'classification' => 'Phân loại',
        'purchasing_department' => 'Bộ phận mua hàng',
        'lead_time_days' => 'Thời gian đặt hàng (ngày)',
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

        // Tab 3, 4 - Danh sách đề nghị theo chu kỳ: bù các danh sách đã tới hạn trước khi
        // hiển thị (phòng khi máy chủ không chạy scheduler), thao tác gửi về PeriodicRequestController.
        // Không đọc lại lịch CAL ở đây (refreshCal: false) để tránh treo trang khi CAL chậm
        // - lịch CAL chỉ được cập nhật ở trang Sử Dụng Vật Tư hoặc lệnh scheduler hằng ngày.
        MaterialPeriodicRequest::generateDue($departmentId, refreshCal: false);
        $periodicLists = MaterialPeriodicRequest::listsOfDepartment($departmentId);

        return view('pages.category.MaterialCategory.list', [
            // Vào từ thẻ trang chủ "Mã mới chờ khai thời gian đặt hàng": mở sẵn hộp thoại điều chỉnh
            'leadTimeOpen' => $this->leadTimeOpen(),
            'datas' => $datas,
            // Ô Tên vật tư tìm qua AJAX (CategoryLookup), chỉ dựng sẵn option của giá trị vừa gửi bị lỗi
            // validate; dòng đang sửa gửi kèm nhãn qua material_names_id_text.
            'materialNames' => \App\Support\CategoryLookup::namesByIds('material', [old('material_names_id')]),
            // Nhãn tên vật tư của các dòng modal Thêm mới vừa báo lỗi (bảng nhiều dòng)
            'batchNames' => \App\Support\CategoryLookup::namesByIds(
                'material',
                collect((array) old('items', []))->filter(fn ($item) => is_array($item))->pluck('material_names_id')->all()
            ),
            'manufacturers' => $this->options('manufacturers', $datas->pluck('manufacturers_id')->all()),
            'nextCode' => $this->nextCode(),
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa thay vì một nút riêng
            'historyCounts' => $this->historyCounts(),
            /*
            | Danh mục dùng chung toàn công ty, nhưng mỗi phòng ban tự khai vật tư nào phòng
            | mình có dùng (bảng material_department_categories). Cột "Phòng Ban Đang Dùng" đọc từ đó.
            */
            'departmentsByCategory' => DepartmentMaterial::departmentsByCategory(),

            // Dữ liệu của tab Vật Tư Của Phòng, đặt tiền tố dm để không đụng biến của tab 1
            'dmDatas' => $dmDatas,
            'dmCategories' => DepartmentMaterial::categoryOptions($dmDatas->pluck('category_id')->all()),
            'dmUnits' => DepartmentMaterial::unitOptions($dmDatas->pluck('unit_id')->all()),
            // Phân loại riêng của phòng, khai ở Dữ Liệu Gốc → Phân Loại
            'dmClassifications' => DepartmentMaterial::classificationOptions(
                $departmentId,
                $dmDatas->pluck('classification_id')->all()
            ),
            // Định khu tìm qua AJAX như ô Tên vật tư, chỉ dựng sẵn option của giá trị old()
            'dmLocations' => \App\Support\CategoryLookup::locationsByIds([old('default_location_id')]),
            // ... và của các dòng modal Khai Vật Tư Cho Phòng vừa báo lỗi (bảng nhiều dòng)
            'dmBatchLocations' => \App\Support\CategoryLookup::locationsByIds(
                collect((array) old('items', []))->filter(fn ($item) => is_array($item))->pluck('default_location_id')->all()
            ),
            /*
            | Đơn vị các phòng KHÁC đang dùng cho từng mã + hệ số đã khai. Phòng khai đơn
            | vị lệch với phòng khác thì phải khai hệ số quy đổi, nếu không lúc CHUYỂN VẬT
            | TƯ LIÊN PHÒNG BAN hệ thống không đổi được số lượng giữa hai phòng.
            */
            'dmUnitsInUse' => \App\Support\CategoryUnitConversion::unitsInUseByCategory(
                \App\Support\CategoryUnitConversion::TYPE_MATERIAL,
                $departmentId
            ),
            'dmConversions' => \App\Support\CategoryUnitConversion::declaredByCategory(
                \App\Support\CategoryUnitConversion::TYPE_MATERIAL
            ),

            // Dữ liệu của 3 tab theo chu kỳ (đề nghị nội bộ / liên phòng ban, dự trù), tiền tố periodic
            'periodicInternalLists' => $periodicLists[MaterialPeriodicRequest::TYPE_INTERNAL],
            'periodicExternalLists' => $periodicLists[MaterialPeriodicRequest::TYPE_EXTERNAL],
            'periodicEstimateLists' => $periodicLists[MaterialPeriodicRequest::TYPE_ESTIMATE],
            'periodicInternalCategories' => MaterialPeriodicRequest::categoryOptions(MaterialPeriodicRequest::TYPE_INTERNAL, $departmentId),
            'periodicExternalCategories' => MaterialPeriodicRequest::categoryOptions(MaterialPeriodicRequest::TYPE_EXTERNAL, $departmentId),
            'periodicUnits' => DepartmentMaterial::unitOptions(),
            'periodicDepartments' => MaterialPeriodicRequest::departmentOptions($departmentId),
            'periodicGroups' => MaterialPeriodicRequest::groupOptions($departmentId),
            // Tiêu đề đã đặt ở từng loại danh sách - gợi ý cho ô Tiêu Đề
            'periodicTitles' => collect([MaterialPeriodicRequest::TYPE_INTERNAL, MaterialPeriodicRequest::TYPE_EXTERNAL, MaterialPeriodicRequest::TYPE_ESTIMATE])
                ->mapWithKeys(fn ($type) => [$type => TitleSuggestions::recent(MaterialPeriodicRequest::LIST_TABLE, $departmentId, 'title',
                    fn ($query) => $query->where('type', $type))])
                ->all(),
            /*
            | Đối tượng (dữ liệu gốc) cho ô chọn của danh sách nội bộ - chỉ những đối tượng ĐANG
            | GẮN vào danh sách trên trang (kể cả đã khoá) + lựa chọn vừa gửi bị lỗi validate.
            | Toàn bộ danh mục (2000+ dòng) không nhúng tĩnh vào trang nữa - modal "Dữ Liệu Gốc
            | - Đối Tượng" (objectPicker.blade.php) tìm qua AJAX, xem PeriodicRequestController::objects().
            */
            'periodicObjects' => MaterialPeriodicRequest::objectsByIds(
                $periodicLists[MaterialPeriodicRequest::TYPE_INTERNAL]->pluck('consumption_object_id')
                    ->push(old('consumption_object_id'))
                    ->all()
            ),
        ]);
    }

    /**
     * Thêm nhiều mã một lần từ bảng items[i][...] của modal Thêm mới. Một dòng lỗi thì không lưu
     * dòng nào, lỗi báo theo "Dòng N".
     */
    public function store(Request $request)
    {
        // Dòng để trống hoàn toàn (chưa chọn tên, nhà sản xuất, chưa nhập thông tin) thì bỏ qua -
        // phân loại không tính vì hàng Điền nhanh tự điền sẵn cho mọi dòng
        $items = $this->batchItems($request, fn ($item) => blank($item['material_names_id'] ?? null)
            && blank($item['manufacturers_id'] ?? null)
            && blank($item['part_number'] ?? null)
            && blank($item['technical_specification'] ?? null));

        $validator = Validator::make(
            ['items' => $items],
            $this->batchRules($this->rules()),
            $this->batchMessages($this->messages(), 'Vui lòng thêm ít nhất một dòng vật tư.')
        );

        $this->checkBatchDuplicates($validator, $items);

        if ($validator->fails()) {
            // Gửi lại đúng các dòng đã kiểm tra (đã bỏ dòng trống) để số "Dòng N" khớp với bảng
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput(['items' => $items]);
        }

        // Sinh mã và ghi bản ghi trong cùng một transaction để hai người thêm cùng lúc không trùng mã
        $ids = DB::transaction(function () use ($items) {
            $number = $this->nextNumber();
            $ids = [];

            foreach ($items as $item) {
                $ids[] = DB::table(self::TABLE)->insertGetId($this->payload($item) + [
                    'code' => $this->formatCode($number++),
                    'app_status' => 'pending',
                    // Modal Thêm không có ô Thời gian đặt hàng: báo người có quyền vào khai (thẻ trang chủ),
                    // trừ vật tư Hành Chánh - không cần thời gian đặt hàng
                    'lead_time_pending' => $this->leadTimeApplies($item['purchasing_department'] ?? null) ? 1 : 0,
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $ids;
        });

        $codes = DB::table(self::TABLE)->whereIn('id', $ids)->orderBy('id')->pluck('code', 'id');

        foreach ($ids as $id) {
            $this->writeHistory($id, 'Thêm mới', 'Khai báo mới ' . self::LABEL . ', mã ' . $codes[$id] . '.');

            AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', $this->describe($id));
        }

        $message = count($ids) === 1
            ? 'Đã thêm ' . self::LABEL . ' mã ' . $codes->first() . '! Bản ghi đang chờ duyệt.'
            : 'Đã thêm ' . count($ids) . ' ' . self::LABEL . ' (mã ' . $codes->first() . ' - ' . $codes->last() . ')! Các bản ghi đang chờ duyệt.';

        return redirect()->back()->with('success', $message);
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        $validator = Validator::make(
            $request->all(),
            $this->rules() + $this->changeReasonRules(),
            $this->messages() + $this->changeReasonMessages()
        );

        $this->checkDuplicate($validator, $request, $current->id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request->all());
        // Thời gian đặt hàng chỉ sửa qua updateLeadTime() (quyền riêng), ở đây giữ nguyên giá trị cũ
        $payload['lead_time_days'] = $current->lead_time_days;
        $note = $this->changeNote($current, $payload);

        if ($note === '') {
            return redirect()->back()->with('error', 'Chưa có thông tin nào thay đổi nên không lưu.')->withInput();
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            // Đổi sang Hành Chánh thì thôi chờ khai; đổi về Cung Ứng mà chưa có số ngày thì báo khai lại
            'lead_time_pending' => $this->leadTimeApplies($payload['purchasing_department'])
                ? (int) ($current->lead_time_pending || $current->lead_time_days === null)
                : 0,
            // Sửa nội dung thì phải duyệt lại từ đầu
            'app_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->writeHistory($current->id, 'Cập nhật', $note, $this->changeReason($request));

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $note ?: 'Không đổi', $this->describe($current->id));

        return redirect()->back()->with('success', 'Cập nhật ' . self::LABEL . ' thành công! Bản ghi chuyển về chờ duyệt.');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần thay đổi trạng thái!');
        }

        if ($stop = $this->guardChangeReason($request)) {
            return $stop;
        }

        $newStatus = $current->status_id == 1 ? 0 : 1;
        $action = $newStatus == 1 ? 'Mở khoá' : 'Khoá';

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'status_id' => $newStatus,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->writeHistory($current->id, $action, 'Trạng thái sử dụng: ' . ($current->status_id == 1 ? 'Hoạt động' : 'Đã khoá') . ' -> ' . ($newStatus == 1 ? 'Hoạt động' : 'Đã khoá'), $this->changeReason($request));

        AuditTrialController::log($action, self::TABLE, $current->id, 'status_id: ' . $current->status_id, 'status_id: ' . $newStatus);

        return redirect()->back()->with(
            'success',
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . self::LABEL . '!'
        );
    }

    public function approve(Request $request)
    {
        return $this->setApproval($request);
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
                    'Mã vật tư' => $row->code ?: '—',
                    'Tên vật tư' => $row->material_name ?: '—',
                    'Nhà sản xuất' => $row->manufacturer_name ?: '—',
                    'Part Number' => $row->part_number ?: '—',
                    'Thông tin kỹ thuật' => $row->technical_specification ?: '—',
                    'Phân loại' => MaterialClassification::describe($row->classification),
                    'Bộ phận mua hàng' => MaterialClassification::purchasingLabel($row->purchasing_department) ?: '—',
                    'Thời gian đặt hàng' => $row->lead_time_days === null ? '—' : $row->lead_time_days . ' ngày',
                ];

                // Đơn vị tính đã chuyển sang danh mục của phòng nên bản ghi mới không còn
                // ghi cột này. Ảnh chụp cũ vẫn hiện lại để không mất vết đã thay đổi.
                if ($row->unit_name) {
                    $snapshot['Đơn vị tính (trước khi chuyển về phòng)'] = $row->unit_name;
                }

                return [
                    'action' => $row->action,
                    'change_note' => $row->change_note,
                    'change_reason' => $row->change_reason,
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
    private function setApproval(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần duyệt!');
        }

        if ($stop = $this->guardSignature($request, self::TABLE, $current->id, 'Phê duyệt')) {
            return $stop;
        }

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'app_status' => 'approved',
            'approved_by' => $this->actor(),
            'approved_at' => now(),
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->writeHistory($current->id, 'Phê duyệt', 'Trạng thái duyệt: ' . $current->app_status . ' -> approved');

        AuditTrialController::log('Phê duyệt', self::TABLE, $current->id, 'app_status: ' . $current->app_status, 'app_status: approved');

        return redirect()->back()->with('success', 'Đã duyệt ' . self::LABEL . '!');
    }

    /**
     * Chụp lại giá trị bản ghi ngay sau khi thay đổi vào bảng lịch sử.
     */
    private function writeHistory(int $id, string $action, ?string $note, ?string $reason = null): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'material_category_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'material_names_id' => $row->material_names_id,
            'manufacturers_id' => $row->manufacturers_id,
            'part_number' => $row->part_number,
            'technical_specification' => $row->technical_specification,
            'classification' => $row->classification,
            'purchasing_department' => $row->purchasing_department,
            'lead_time_days' => $row->lead_time_days,
            'app_status' => $row->app_status,
            'status_id' => $row->status_id,
            'change_note' => $note,
            'change_reason' => $reason,
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

            if ($field === 'classification') {
                $parts[] = $title . ': '
                    . MaterialClassification::describe($current->$field) . ' -> '
                    . MaterialClassification::describe($payload[$field]);
            } elseif ($field === 'purchasing_department') {
                $parts[] = $title . ': '
                    . (MaterialClassification::purchasingLabel($current->$field) ?: '—') . ' -> '
                    . (MaterialClassification::purchasingLabel($payload[$field]) ?: '—');
            } elseif (in_array($field, ['part_number', 'technical_specification', 'lead_time_days'], true)) {
                $parts[] = $title . ': '
                    . (($current->$field === null || $current->$field === '') ? '—' : $current->$field) . ' -> '
                    . (($payload[$field] === null || $payload[$field] === '') ? '—' : $payload[$field]);
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
                self::TABLE . '.code',
                'material_names.name as material_name',
                'manufacturers.name as manufacturer_name'
            )
            ->where(self::TABLE . '.id', $id)
            ->first();

        if (! $row) {
            return 'NA';
        }

        return implode(' | ', [
            $row->code ?: '—',
            $row->material_name ?: '—',
            $row->manufacturer_name ?: '—',
        ]);
    }

    /**
     * Mã danh mục kế tiếp theo dạng M00001: lấy số lớn nhất đang có rồi cộng 1.
     *
     * Màn hình này chỉ khoá bản ghi (deActive) chứ không xoá, nên mã không bị dùng lại.
     */
    private function nextCode(): string
    {
        return $this->formatCode($this->nextNumber());
    }

    /** Phần số của mã kế tiếp; thêm nhiều dòng thì cộng dần từ số này. */
    private function nextNumber(): int
    {
        $numbers = DB::table(self::TABLE)
            ->where('code', 'like', self::CODE_PREFIX . '%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr($code, strlen(self::CODE_PREFIX)));

        return ($numbers->max() ?? 0) + 1;
    }

    private function formatCode(int $number): string
    {
        return self::CODE_PREFIX . str_pad((string) $number, self::CODE_LENGTH, '0', STR_PAD_LEFT);
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
     * Không cho trùng tổ hợp Vật tư + Nhà sản xuất: với danh mục đang có và giữa các dòng
     * trong cùng một lần thêm.
     */
    private function checkBatchDuplicates($validator, array $items): void
    {
        $validator->after(function ($validator) use ($items) {
            $key = fn ($item) => (int) ($item['material_names_id'] ?? 0) . '-' . (int) ($item['manufacturers_id'] ?? 0);

            $existing = DB::table(self::TABLE)
                ->whereIn('material_names_id', collect($items)->pluck('material_names_id')->filter()->map(fn ($id) => (int) $id)->all())
                ->get(['material_names_id', 'manufacturers_id'])
                ->mapWithKeys(fn ($row) => [$row->material_names_id . '-' . $row->manufacturers_id => true]);

            $seen = [];

            foreach ($items as $index => $item) {
                if (blank($item['material_names_id'] ?? null) || blank($item['manufacturers_id'] ?? null)) {
                    continue;
                }

                if (isset($existing[$key($item)])) {
                    $validator->errors()->add('items.' . $index . '.material_names_id', 'Tổ hợp Vật tư - Nhà sản xuất này đã có trong danh mục.');
                } elseif (isset($seen[$key($item)])) {
                    $validator->errors()->add('items.' . $index . '.material_names_id', 'Trùng Vật tư - Nhà sản xuất với dòng ' . ($seen[$key($item)] + 1) . '.');
                }

                $seen[$key($item)] ??= $index;
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
        return \App\Support\Signer::actor();
    }

    private function rules(): array
    {
        return [
            'material_names_id' => ['required', 'integer', 'exists:material_names,id'],
            'manufacturers_id' => ['required', 'integer', 'exists:manufacturers,id'],
            'part_number' => ['nullable', 'string', 'max:100'],
            'technical_specification' => ['nullable', 'string', 'max:100'],
        ] + MaterialClassification::rules();
    }

    /** Giá trị ghi DB của một vật tư - $input là $request->all() (Cập nhật) hoặc một dòng items[i] (Thêm mới). */
    private function payload(array $input): array
    {
        $techSpec = trim((string) ($input['technical_specification'] ?? ''));
        $partNumber = trim((string) ($input['part_number'] ?? ''));
        $purchasing = (string) ($input['purchasing_department'] ?? '');

        return [
            'material_names_id' => (int) ($input['material_names_id'] ?? 0),
            'manufacturers_id' => (int) ($input['manufacturers_id'] ?? 0),
            'part_number' => $partNumber === '' ? null : $partNumber,
            'technical_specification' => $techSpec === '' ? null : $techSpec,
            'classification' => MaterialClassification::encode($input['classification'] ?? null),
            'purchasing_department' => isset(MaterialClassification::PURCHASING_DEPARTMENTS[$purchasing])
                ? $purchasing
                : null,
            // Không nhận từ form: người có quyền khai sau qua updateLeadTime(), update() giữ giá trị cũ
            'lead_time_days' => null,
        ];
    }

    /** Vật tư Hành Chánh mua (kho VPP) không cần thời gian đặt hàng - xem UpdatesCategoryLeadTime. */
    private function leadTimeApplies(?string $purchasing): bool
    {
        return $purchasing !== MaterialClassification::PURCHASING_ADMIN;
    }

    private function messages(): array
    {
        return [
            'material_names_id.required' => 'Vui lòng chọn tên vật tư.',
            'material_names_id.exists' => 'Tên vật tư không hợp lệ.',
            'manufacturers_id.required' => 'Vui lòng chọn nhà sản xuất.',
            'manufacturers_id.exists' => 'Nhà sản xuất không hợp lệ.',
            'part_number.max' => 'Part Number tối đa 100 ký tự.',
            'technical_specification.max' => 'Thông tin kỹ thuật tối đa 100 ký tự.',
        ] + MaterialClassification::messages();
    }
}
