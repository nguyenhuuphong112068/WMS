<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Concerns\StoresBatchItems;
use App\Http\Controllers\Concerns\UpdatesCategoryLeadTime;
use App\Http\Controllers\Concerns\VerifiesSignature;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\ActiveIngredientThreshold;
use App\Support\CategoryUnitConversion;
use App\Support\CompanyContext;
use App\Support\DepartmentChemical;
use App\Support\MixtureHazardThreshold;
use App\Support\ProductList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DANH MỤC - HOÁ CHẤT, TAB "DANH MỤC HOÁ CHẤT CÔNG TY"
 *
 * Màn hình này có 2 tab nằm chung một trang:
 * - Tab 1 "Danh Mục Hoá Chất Công Ty": bản chất của chất, dùng chung toàn công ty (chính là controller này).
 * - Tab 2 "Hoá Chất Của Phòng": cách dùng riêng của từng phòng, do DepartmentChemicalController xử lý.
 * Controller này dựng cả trang, nên index() lấy dữ liệu cho cả hai tab.
 *
 * PHÂN LOẠI 10 nhóm theo Nghị định 24/2026/NĐ-CP KHÔNG còn khai ở đây: suy tự động từ dữ
 * liệu gốc "Tên Hoạt Chất" + "Tên Hoá Chất" (App\Support\ChemicalClassification). Cột
 * chemical_categories.classification đã bị bỏ; bảng chỉ hiển thị nhóm suy được.
 *
 * ĐƠN VỊ TÍNH không khai ở đây: mỗi phòng nhập / xuất theo đơn vị của phòng mình nên
 * đơn vị nằm ở tab "Hoá Chất Của Phòng" (chemical_department_categories.unit_id).
 *
 * Dữ liệu mới tạo ở trạng thái "Chờ duyệt", sửa lại bản ghi đã duyệt sẽ đưa về "Chờ duyệt".
 * Mọi thay đổi đều được chụp lại ở bảng chemical_category_histories.
 *
 * "Khai báo trên Cổng thông tin quốc gia" không sửa qua update(): mỗi lần khai báo (Mã hồ sơ +
 * Code nhà sản xuất) ghi một dòng qua declarePortal(), khai báo được nhiều lần; sửa từng lần qua updatePortal() (bắt lý do, ghi Audit Trail).
 *
 * Modal Thêm mới là BẢNG NHIỀU DÒNG (items[i][...], trait StoresBatchItems): một lần lưu được
 * nhiều mã, mã C00001... cấp liên tiếp theo thứ tự dòng. Modal Cập nhật vẫn sửa từng dòng.
 */
class ChemicalCategoryController extends Controller
{
    use RequiresChangeReason;
    use StoresBatchItems;
    use VerifiesSignature;
    use UpdatesCategoryLeadTime;

    private const TABLE = 'chemical_categories';
    private const HISTORY_TABLE = 'chemical_category_histories';
    private const LABEL = 'danh mục hoá chất công ty';

    /** Quyền điều chỉnh Thời gian đặt hàng - xem UpdatesCategoryLeadTime */
    private const LEAD_TIME_PERMISSION = 'category_chemical_lead_time';

    /** Tiền tố mã danh mục sinh tự động: C00001, C00002... */
    private const CODE_PREFIX = 'C';
    private const CODE_LENGTH = 5;

    /** Các cột người dùng nhập, dùng chung cho so sánh lịch sử. Mã danh mục không sửa được nên không nằm ở đây. */
    private const FIELDS = [
        'type' => 'Loại',
        'chem_names_id' => 'Tên hoá chất',
        'manufacturers_id' => 'Nhà sản xuất',
        'density' => 'Tỉ trọng d (g/ml)',
        'ai_content_percent' => 'Hàm lượng hoạt chất (%)',
        'shelf_life_months' => 'Hạn dùng mặc định (tháng)',
        'storage_condition_id' => 'Điều kiện bảo quản',
        'doc_no' => 'Số tài liệu',
        'safety_warning' => 'Cảnh báo an toàn',
        'lead_time_days' => 'Thời gian đặt hàng (ngày)',
    ];

    public function index()
    {
        // Nhóm NĐ 24/2026 suy theo từng mã danh mục - quyết định xét Bảng A hay Bảng B
        $categoryGroups = \App\Support\ChemicalClassification::codesByCategory();

        // Ngưỡng Tồn Trữ PL IV: mỗi mã danh mục CHỈ xét MỘT bảng, không xét cả hai.
        //   - Đơn chất nhóm 9  -> Bảng A (ActiveIngredientThreshold)
        //   - Hỗn hợp nhóm 10  -> Bảng B (MixtureHazardThreshold)
        // Bảng A vốn trả về cả mã danh mục hỗn hợp có chứa hoạt chất nhóm 9; lọc bỏ để mã
        // đó chỉ còn hiện ở Bảng B theo đúng nhóm 10 suy được.
        $thresholdsA = ActiveIngredientThreshold::forCategories(CompanyContext::currentId());
        $thresholdsB = MixtureHazardThreshold::forCategories(CompanyContext::currentId());

        foreach (array_keys($thresholdsA) as $catId) {
            $groups = $categoryGroups[$catId] ?? [];

            if (in_array('N10', $groups, true) || ! in_array('N9', $groups, true)) {
                unset($thresholdsA[$catId]);
            }
        }

        foreach (array_keys($thresholdsB) as $catId) {
            if (! in_array('N10', $categoryGroups[$catId] ?? [], true)) {
                unset($thresholdsB[$catId]);
            }
        }

        $datas = DB::table(self::TABLE)
            ->leftJoin('chem_names', self::TABLE . '.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', self::TABLE . '.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('storage_conditions', self::TABLE . '.storage_condition_id', '=', 'storage_conditions.id')
            ->select(
                self::TABLE . '.*',
                'chem_names.name as chem_name',
                'manufacturers.name as manufacturer_name',
                'manufacturers.short_name as manufacturer_short_name',
                'storage_conditions.name as storage_condition_name'
            )
            // Số CAS gộp của các hoạt chất trong hỗn hợp (chem_names gắn nhiều hoạt chất qua pivot)
            ->selectSub(DepartmentChemical::casNoSubquery(self::TABLE . '.chem_names_id'), 'cas_no')
            ->orderBy(self::TABLE . '.code', 'asc')
            ->get();

        session()->put(['title' => 'DANH MỤC - HOÁ CHẤT']);

        $departmentId = (int) (session('user')['selected_department_id'] ?? 0);

        // Tab 2 - Hoá Chất Của Phòng: cùng một trang nhưng thao tác thêm/sửa/khoá vẫn
        // gửi về DepartmentChemicalController, ở đây chỉ dựng dữ liệu để hiển thị.
        $dcDatas = DepartmentChemical::rowsOfDepartment($departmentId);

        // Các dòng modal Thêm mới (bảng nhiều dòng) vừa báo lỗi - của tab 1 hoặc tab 2
        $oldItems = collect((array) old('items', []))->filter(fn ($item) => is_array($item));

        return view('pages.category.ChemicalCategory.list', [
            // Vào từ thẻ trang chủ "Mã mới chờ khai thời gian đặt hàng": mở sẵn hộp thoại điều chỉnh
            'leadTimeOpen' => $this->leadTimeOpen(),
            'datas' => $datas,
            // Ô Tên hoá chất tìm qua AJAX (CategoryLookup), chỉ dựng sẵn option của giá trị vừa gửi bị
            // lỗi validate; dòng đang sửa gửi kèm nhãn qua chem_names_id_text.
            'chemNames' => \App\Support\CategoryLookup::namesByIds('chemical', [old('chem_names_id')]),
            'batchNames' => \App\Support\CategoryLookup::namesByIds('chemical', $oldItems->pluck('chem_names_id')->all()),
            'manufacturers' => $this->options('manufacturers', $datas->pluck('manufacturers_id')->all()),
            'storageConditions' => $this->options('storage_conditions', $datas->pluck('storage_condition_id')->all()),
            // Nhóm NĐ 24/2026 suy tự động theo từng mã danh mục (không còn tick tay)
            'classificationCodes' => $categoryGroups,
            'classificationLabels' => \App\Support\ChemicalClassification::labels(),
            // Mã nhóm NĐ 24/2026 (N1..N10) của các tên hoá chất danh mục đang mang - cho ô xem nhanh
            // nhóm phân loại ở modal Cập nhật. Tên chọn mới lấy nhóm từ kết quả tìm / bảng chọn (AJAX).
            'chemNameGroups' => \App\Support\CategoryLookup::chemicalGroupCodes(
                $datas->pluck('chem_names_id')->push(old('chem_names_id'))->merge($oldItems->pluck('chem_names_id'))->all()
            ),
            'types' => config('chemical.types'),
            'safetyWarnings' => config('chemical.safety_warnings'),
            'nextCode' => $this->previewNextCode(),
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa thay vì một nút riêng
            'historyCounts' => $this->historyCounts(),
            // TẠM THỜI: mã hoá chất bên Access CMS 3.0 để đối chiếu dữ liệu chuyển đổi, đối chiếu xong thì gỡ
            'accessCodes' => \App\Support\AccessLegacyCode::byChemicalCategory(),
            // Các lần khai báo Cổng thông tin quốc gia (Mã hồ sơ + Code NSX), mới nhất trước
            'portalDeclarations' => \App\Support\ChemicalPortalDeclaration::byCategory(),
            // Đối chiếu tồn trữ với ngưỡng Phụ lục IV NĐ 24/2026, theo mã danh mục. Phạm vi
            // cộng tồn gói trong công ty của phòng ban đang chọn.
            'thresholds' => $thresholdsA,
            'thresholdsB' => $thresholdsB,
            /*
            | Danh mục dùng chung toàn công ty, nhưng mỗi phòng ban tự khai chất nào phòng
            | mình có dùng (bảng chemical_department_categories). Cột "Phòng Ban Đang Dùng" đọc từ đó.
            */
            'departmentsByCategory' => DepartmentChemical::departmentsByCategory(),

            // Dữ liệu của tab Hoá Chất Của Phòng, đặt tiền tố dc để không đụng biến của tab 1
            'dcDatas' => $dcDatas,
            // Badge "Tương kỵ cùng tầng" ở cột Định Khu: [id dòng => hoá chất tương kỵ cùng phạm vi]
            'dcConflicts' => \App\Support\ChemicalCompatibility::conflictsByDepartmentRows($dcDatas),
            'dcCategories' => DepartmentChemical::categoryOptions($dcDatas->pluck('category_id')->all()),
            // Định khu tìm qua AJAX, chỉ dựng sẵn option của giá trị old()
            'dcLocations' => \App\Support\CategoryLookup::locationsByIds([old('default_location_id')]),
            'dcBatchLocations' => \App\Support\CategoryLookup::locationsByIds($oldItems->pluck('default_location_id')->all()),
            'dcStorageConditions' => DepartmentChemical::storageConditionOptions(),
            'dcUnits' => DepartmentChemical::unitOptions($dcDatas->pluck('unit_id')->all()),
            /*
            | Đơn vị các phòng KHÁC đang dùng cho từng hoá chất. Phòng đang khai chọn đơn
            | vị lệch với danh sách này thì modal bắt khai thêm hệ số quy đổi, để lúc
            | chuyển kho hệ thống đổi được số lượng qua lại.
            */
            'dcUnitsInUse' => CategoryUnitConversion::unitsInUseByCategory(
                CategoryUnitConversion::TYPE_CHEMICAL,
                $departmentId
            ),
            'dcConversions' => CategoryUnitConversion::declaredByCategory(CategoryUnitConversion::TYPE_CHEMICAL),

            // Tab 3 - Danh sách hoá chất theo sản phẩm (ChemicalProductListController): gói Sản phẩm +
            // Chỉ tiêu, hoá chất chọn trong số đã khai cho phòng (đang dùng)
            'plLists' => ProductList::rowsOfDepartment(ProductList::CHEMICAL, $departmentId),
            'plPurposes' => DB::table('purposes')->where('status_id', 1)->orderBy('name')->get(['id', 'name']),
            'plOptions' => ProductList::options(ProductList::CHEMICAL, $dcDatas),
        ]);
    }

    /**
     * Thêm nhiều mã một lần từ bảng items[i][...] của modal Thêm mới. Một dòng lỗi thì không lưu
     * dòng nào, lỗi báo theo "Dòng N".
     */
    public function store(Request $request)
    {
        // Dòng chưa chọn tên, nhà sản xuất, chưa nhập loại thì coi là để trống - các ô hàng Điền nhanh
        // tự điền sẵn (điều kiện bảo quản, cảnh báo...) không tính
        $items = $this->batchItems($request, fn ($item) => blank($item['chem_names_id'] ?? null)
            && blank($item['manufacturers_id'] ?? null)
            && blank($item['type'] ?? null));

        $validator = Validator::make(
            ['items' => $items],
            $this->batchRules($this->rules()),
            $this->batchMessages($this->messages(), 'Vui lòng thêm ít nhất một dòng hoá chất.')
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
                    // Modal Thêm không có ô Thời gian đặt hàng: báo người có quyền vào khai (thẻ trang chủ)
                    'lead_time_pending' => 1,
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

            AuditTrialController::log('Thêm mới', self::TABLE, $id, 'NA', 'Thêm ' . self::LABEL . ': ' . $codes[$id]);
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
            // Sửa nội dung thì phải duyệt lại từ đầu
            'app_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->writeHistory($current->id, 'Cập nhật', $note, $this->changeReason($request));

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $note ?: 'Không đổi', $current->code);

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
            ($newStatus == 1 ? 'Đã mở khoá ' : 'Đã khoá ') . self::LABEL . ' ' . $current->code . '!'
        );
    }

    public function approve(Request $request)
    {
        return $this->setApproval($request);
    }

    /**
     * Khai báo trên Cổng thông tin quốc gia - được khai báo NHIỀU LẦN.
     *
     * Mỗi lần khai báo là một dòng ở chemical_portal_declarations (Mã hồ sơ khai báo + Code nhà
     * sản xuất), sửa lại qua updatePortal(). Các cột national_portal_* trên danh mục giữ giá trị
     * của lần khai báo mới nhất. Không nằm trong update() nên không bắt duyệt lại danh mục; bù
     * lại phải nhập mật khẩu ký xác nhận khi khai báo.
     */
    public function declarePortal(Request $request)
    {
        if (! user_can('category_chemical_update')) {
            return redirect()->back()->with('error', 'Bạn không có quyền cập nhật ' . self::LABEL . '!');
        }

        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy ' . self::LABEL . ' cần cập nhật!');
        }

        $declarationNo = trim((string) $request->input('declaration_no'));
        $manufacturerCode = trim((string) $request->input('manufacturer_code'));

        if ($declarationNo === '') {
            return redirect()->back()->with('error', 'Vui lòng nhập Mã hồ sơ khai báo trên Cổng thông tin quốc gia!');
        }

        if ($manufacturerCode === '') {
            return redirect()->back()->with('error', 'Vui lòng nhập Code nhà sản xuất!');
        }

        if (mb_strlen($declarationNo) > 100 || mb_strlen($manufacturerCode) > 100) {
            return redirect()->back()->with('error', 'Mã hồ sơ khai báo và Code nhà sản xuất không được vượt quá 100 ký tự!');
        }

        if ($stop = $this->guardSignature($request, self::TABLE, $current->id, 'Khai báo Cổng thông tin quốc gia')) {
            return $stop;
        }

        $declarationTable = \App\Support\ChemicalPortalDeclaration::TABLE;

        $duplicate = DB::transaction(function () use ($current, $declarationTable, $declarationNo, $manufacturerCode) {
            // Khoá dòng danh mục: hai người bấm cùng lúc không ghi trùng một lần khai báo
            DB::table(self::TABLE)->where('id', $current->id)->lockForUpdate()->first();

            $exists = DB::table($declarationTable)
                ->where('chemical_category_id', $current->id)
                ->where('status_id', 1)
                ->where('declaration_no', $declarationNo)
                ->where('manufacturer_code', $manufacturerCode)
                ->exists();

            if ($exists) {
                return true;
            }

            DB::table($declarationTable)->insert([
                'chemical_category_id' => $current->id,
                'declaration_no' => $declarationNo,
                'manufacturer_code' => $manufacturerCode,
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table(self::TABLE)->where('id', $current->id)->update([
                'national_portal_declared' => 1,
                'national_portal_declaration_no' => $declarationNo,
                'national_portal_manufacturer_code' => $manufacturerCode,
                'national_portal_declared_by' => $this->actor(),
                'national_portal_declared_at' => now(),
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            return false;
        });

        if ($duplicate) {
            return redirect()->back()->with('error', 'Mã ' . $current->code . ' đã có lần khai báo với Mã hồ sơ ' . $declarationNo . ' và Code nhà sản xuất ' . $manufacturerCode . '.');
        }

        $detail = 'Mã hồ sơ: ' . $declarationNo . ', Code nhà sản xuất: ' . $manufacturerCode;

        $this->writeHistory($current->id, 'Khai báo Cổng TTQG', 'Khai báo trên Cổng thông tin quốc gia (' . $detail . ')');

        AuditTrialController::log(
            'Khai báo Cổng thông tin quốc gia',
            self::TABLE,
            $current->id,
            'national_portal_declaration_no: ' . ($current->national_portal_declaration_no ?: '—') . ', national_portal_manufacturer_code: ' . ($current->national_portal_manufacturer_code ?? '—'),
            'national_portal_declaration_no: ' . $declarationNo . ', national_portal_manufacturer_code: ' . $manufacturerCode
        );

        return redirect()->back()->with('success', 'Đã ghi nhận khai báo Cổng thông tin quốc gia cho ' . self::LABEL . ' ' . $current->code . ' (' . $detail . ')!');
    }

    /**
     * Sửa một lần khai báo Cổng thông tin quốc gia đã ghi (Mã hồ sơ + Code nhà sản xuất).
     *
     * Cùng quyền với khai báo (category_chemical_update), bắt buộc lý do điều chỉnh như Sửa
     * danh mục; ghi lịch sử danh mục + Audit Trail giá trị cũ -> mới. Sửa lần mới nhất thì
     * đồng bộ lại các cột national_portal_* trên danh mục.
     */
    public function updatePortal(Request $request)
    {
        if (! user_can('category_chemical_update')) {
            return redirect()->back()->with('error', 'Bạn không có quyền cập nhật ' . self::LABEL . '!');
        }

        $declarationTable = \App\Support\ChemicalPortalDeclaration::TABLE;

        $declaration = DB::table($declarationTable)
            ->where('id', $request->declaration_id)
            ->where('status_id', 1)
            ->first();
        $current = $declaration ? DB::table(self::TABLE)->where('id', $declaration->chemical_category_id)->first() : null;

        if (! $declaration || ! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy lần khai báo Cổng thông tin quốc gia cần sửa!');
        }

        $declarationNo = trim((string) $request->input('declaration_no'));
        $manufacturerCode = trim((string) $request->input('manufacturer_code'));

        if ($declarationNo === '') {
            return redirect()->back()->with('error', 'Vui lòng nhập Mã hồ sơ khai báo trên Cổng thông tin quốc gia!');
        }

        if ($manufacturerCode === '') {
            return redirect()->back()->with('error', 'Vui lòng nhập Code nhà sản xuất!');
        }

        if (mb_strlen($declarationNo) > 100 || mb_strlen($manufacturerCode) > 100) {
            return redirect()->back()->with('error', 'Mã hồ sơ khai báo và Code nhà sản xuất không được vượt quá 100 ký tự!');
        }

        if ($stop = $this->guardChangeReason($request)) {
            return $stop;
        }

        $old = 'Mã hồ sơ: ' . ($declaration->declaration_no ?: '—') . ', Code nhà sản xuất: ' . ($declaration->manufacturer_code ?: '—');
        $new = 'Mã hồ sơ: ' . $declarationNo . ', Code nhà sản xuất: ' . $manufacturerCode;

        if ($declarationNo === (string) $declaration->declaration_no && $manufacturerCode === (string) $declaration->manufacturer_code) {
            return redirect()->back()->with('warning', 'Thông tin khai báo không có thay đổi.');
        }

        $duplicate = DB::transaction(function () use ($current, $declaration, $declarationTable, $declarationNo, $manufacturerCode) {
            DB::table(self::TABLE)->where('id', $current->id)->lockForUpdate()->first();

            $exists = DB::table($declarationTable)
                ->where('chemical_category_id', $current->id)
                ->where('status_id', 1)
                ->where('id', '<>', $declaration->id)
                ->where('declaration_no', $declarationNo)
                ->where('manufacturer_code', $manufacturerCode)
                ->exists();

            if ($exists) {
                return true;
            }

            DB::table($declarationTable)->where('id', $declaration->id)->update([
                'declaration_no' => $declarationNo,
                'manufacturer_code' => $manufacturerCode,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Các cột trên danh mục luôn phản ánh lần khai báo mới nhất
            $latest = DB::table($declarationTable)
                ->where('chemical_category_id', $current->id)
                ->where('status_id', 1)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($latest && (int) $latest->id === (int) $declaration->id) {
                DB::table(self::TABLE)->where('id', $current->id)->update([
                    'national_portal_declaration_no' => $declarationNo,
                    'national_portal_manufacturer_code' => $manufacturerCode,
                ]);
            }

            return false;
        });

        if ($duplicate) {
            return redirect()->back()->with('error', 'Mã ' . $current->code . ' đã có lần khai báo khác với Mã hồ sơ ' . $declarationNo . ' và Code nhà sản xuất ' . $manufacturerCode . '.');
        }

        $declaredAt = $declaration->created_at ? \Carbon\Carbon::parse($declaration->created_at)->format('d/m/Y H:i') : '—';

        $this->writeHistory(
            $current->id,
            'Sửa khai báo Cổng TTQG',
            'Sửa lần khai báo Cổng thông tin quốc gia ngày ' . $declaredAt . ': ' . $old . ' -> ' . $new,
            $this->changeReason($request)
        );

        AuditTrialController::log(
            'Sửa khai báo Cổng thông tin quốc gia',
            $declarationTable,
            $declaration->id,
            'declaration_no: ' . ($declaration->declaration_no ?: '—') . ', manufacturer_code: ' . ($declaration->manufacturer_code ?: '—'),
            'declaration_no: ' . $declarationNo . ', manufacturer_code: ' . $manufacturerCode . ' (Mã danh mục ' . $current->code . '; lý do: ' . $this->changeReason($request) . ')'
        );

        return redirect()->back()->with('success', 'Đã cập nhật khai báo Cổng thông tin quốc gia của ' . self::LABEL . ' ' . $current->code . ' (' . $new . ')!');
    }

    /** Trả về lịch sử thay đổi của một dòng danh mục cho modal xem lịch sử. */
    public function history(Request $request)
    {
        // Gồm cả nhãn mã cũ (FLAMMABLE, CORROSIVE) còn nằm trong ảnh chụp lịch sử trước khi tách mã
        $safetyWarnings = \App\Support\ChemicalCompatibility::labels();

        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('chem_names', self::HISTORY_TABLE . '.chem_names_id', '=', 'chem_names.id')
            ->leftJoin('manufacturers', self::HISTORY_TABLE . '.manufacturers_id', '=', 'manufacturers.id')
            ->leftJoin('units', self::HISTORY_TABLE . '.unit_id', '=', 'units.id')
            ->leftJoin('storage_conditions', self::HISTORY_TABLE . '.storage_condition_id', '=', 'storage_conditions.id')
            ->select(
                self::HISTORY_TABLE . '.*',
                'chem_names.name as chem_name',
                'manufacturers.name as manufacturer_name',
                'units.name as unit_name',
                'storage_conditions.name as storage_condition_name'
            )
            ->where(self::HISTORY_TABLE . '.chemical_category_id', $request->id)
            ->orderBy(self::HISTORY_TABLE . '.id', 'desc')
            ->get();

        return response()->json([
            'rows' => $rows->map(function ($row) use ($safetyWarnings) {
                // classification chỉ còn trong ảnh chụp lịch sử cũ (cột giữ lại ở bảng history)
                $codes = $this->decodeCodes($row->classification ?? null);
                $warningCodes = $this->decodeCodes($row->safety_warning);

                $snapshot = [
                    'Mã danh mục' => $row->code ?: '—',
                    'Loại' => $row->type ?: '—',
                    'Tên hoá chất' => $row->chem_name ?: '—',
                    'Nhà sản xuất' => $row->manufacturer_name ?: '—',
                    'Tỉ trọng d (g/ml)' => $this->formatDensity($row->density),
                    'Hàm lượng hoạt chất (%)' => $this->formatDensity($row->ai_content_percent),
                    'Hạn dùng (tháng)' => $row->shelf_life_months ?: '—',
                    'Điều kiện bảo quản' => $row->storage_condition_name ?: '—',
                    'Thời gian đặt hàng' => $row->lead_time_days === null ? '—' : $row->lead_time_days . ' ngày',
                    'Phân loại' => $codes ? implode(', ', $codes) : '—',
                    'Cảnh báo an toàn' => $warningCodes
                        ? implode(', ', array_map(fn ($code) => $safetyWarnings[$code] ?? $code, $warningCodes))
                        : '—',
                    // Ảnh chụp trước khi có cột này là NULL
                    'Khai báo Cổng TT quốc gia' => $row->national_portal_declared === null
                        ? '—'
                        : ($row->national_portal_declared ? 'Đã khai báo' : 'Chưa khai báo'),
                    'Mã hồ sơ khai báo' => $row->national_portal_declaration_no ?: '—',
                    'Code nhà sản xuất (khai báo)' => ($row->national_portal_manufacturer_code ?? null) ?: '—',
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
     * JSON cho modal "xem chi tiết" của cột Ngưỡng Tồn Trữ PL IV.
     *
     * Trả về, cho một mã danh mục hoá chất, toàn bộ dữ liệu đã đóng góp tạo nên hai con số
     * hiển thị trên bảng:
     *   - "Tồn thực tế" : bảng tồn hiện tại theo mã × phòng (quy ra kg) + phần chưa quy đổi được.
     *   - "Tồn cao nhất": diễn biến từng chứng từ nhập / xuất / cân đối theo ngày, luỹ kế kg,
     *                     đánh dấu đúng dòng làm tồn chạm đỉnh.
     * Phạm vi cộng tồn gói trong công ty của phòng ban đang chọn.
     */
    public function thresholdDetail(Request $request)
    {
        $categoryId = (int) $request->id;
        $companyId = CompanyContext::currentId();

        $category = DB::table(self::TABLE)
            ->leftJoin('chem_names', self::TABLE . '.chem_names_id', '=', 'chem_names.id')
            ->where(self::TABLE . '.id', $categoryId)
            ->select(self::TABLE . '.code', 'chem_names.name as chem_name')
            ->first();

        if (! $category) {
            return response()->json(['ok' => false, 'reason' => 'Không tìm thấy mã danh mục hoá chất.']);
        }

        // Cùng luật với bảng danh sách: nhóm 10 -> chỉ Bảng B, đơn chất nhóm 9 -> chỉ Bảng A.
        $groups = \App\Support\ChemicalClassification::codesByCategory()[$categoryId] ?? [];
        $isN10 = in_array('N10', $groups, true);
        $isN9 = in_array('N9', $groups, true);

        $tableA = $isN9 && ! $isN10
            ? array_map(
                fn ($row) => $this->thresholdDetailPayload($row, 'A'),
                ActiveIngredientThreshold::detailForCategory($categoryId, $companyId)
            )
            : [];

        $tableBRow = $isN10 ? MixtureHazardThreshold::detailForCategory($categoryId, $companyId) : null;

        return response()->json([
            'ok' => true,
            'category_code' => $category->code,
            'chem_name' => $category->chem_name ?: '—',
            'warn_percent' => (int) round(ActiveIngredientThreshold::warnRatio() * 100),
            'tableA' => $tableA,
            'tableB' => $tableBRow ? $this->thresholdDetailPayload($tableBRow, 'B') : null,
        ]);
    }

    /** Gom một dòng đánh giá ngưỡng (Bảng A hoặc B) thành mảng đã format sẵn cho modal. */
    private function thresholdDetailPayload($row, string $table): array
    {
        $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.') ?: '0';
        $signed = fn ($v) => ((float) $v > 0 ? '+' : '') . $num($v);
        $qty = fn ($v, $u) => (abs((float) $v) < 1e-9 ? '—' : $num($v) . ($u ? ' ' . $u : ''));
        $typeLabels = ['import' => 'Nhập', 'export' => 'Xuất', 'cancel' => 'Huỷ bỏ', 'balancing' => 'Cân đối'];
        $levelLabels = ['ok' => 'Trong ngưỡng', 'warn' => 'Đã sắp chạm ngưỡng', 'exceeded' => 'Đã vượt ngưỡng'];
        $curLevelLabels = ['ok' => 'Trong ngưỡng', 'warn' => 'Sắp chạm ngưỡng', 'exceeded' => 'Vượt ngưỡng'];

        // Số mã xuất nhập gộp lại thành hai con số hiển thị
        $onhandCount = count($row->onhand_rows);
        $importRefs = [];
        foreach ($row->timeline as $t) {
            if ($t->type === 'import') {
                $importRefs[$t->ref] = true;
            }
        }

        return [
            'table' => $table,
            'title' => $table === 'A' ? $row->ai_name : $row->chem_name,
            'subtitle' => $table === 'A'
                ? ($row->ai_code ?: '')
                : ('Nhóm nguy hại: ' . implode(', ', $row->hazard_labels) . ' · ngưỡng thấp nhất nhóm ' . ($row->strictest_group ?? '—')),
            'threshold_kg' => $num($table === 'A' ? $row->threshold_kg : $row->min_threshold_kg),
            'total_kg' => $num($row->total_kg),
            'peak_kg' => $num($row->peak_kg),
            'peak_date' => $row->peak_date ? \Carbon\Carbon::parse($row->peak_date)->format('d/m/Y') : '—',
            'ratio_percent' => isset($row->ratio) && $row->ratio !== null ? (int) round($row->ratio * 100) . '%' : '—',
            'peak_ratio_percent' => isset($row->peak_ratio) && $row->peak_ratio !== null ? (int) round($row->peak_ratio * 100) . '%' : '—',
            // level = theo ĐỈNH (mức chính); current_level = theo tồn hiện tại
            'level' => $row->level ?? 'ok',
            'level_label' => $levelLabels[$row->level ?? 'ok'] ?? ($row->level ?? ''),
            'current_level' => $row->current_level ?? ($row->level ?? 'ok'),
            'current_level_label' => $curLevelLabels[$row->current_level ?? 'ok'] ?? ($row->current_level ?? ''),
            'has_unconvertible' => ! empty($row->unconvertible),
            // "Tồn thực tế = tổng tồn còn lại của N mã xuất nhập"
            'onhand_count' => $onhandCount,
            // "Tồn cao nhất dựng lại từ M chứng từ, trong đó K lần nhập"
            'timeline_count' => count($row->timeline),
            'import_count' => count($importRefs),
            'by_department' => array_map(fn ($d) => [
                'department_name' => $d->department_name,
                'kg' => $num($d->kg),
            ], $row->by_department),
            'onhand_rows' => array_map(fn ($o) => [
                'ref' => $o->ref ?? '',
                'date' => isset($o->date) && $o->date ? \Carbon\Carbon::parse($o->date)->format('d/m/Y') : '—',
                'category_code' => $o->category_code,
                'chem_name' => $o->chem_name ?? '',
                'department_name' => $o->department_name,
                'imported' => $qty($o->imported ?? 0, $o->unit_short),
                'balanced' => isset($o->balanced) ? ($signed($o->balanced) === '0' ? '—' : $signed($o->balanced) . ($o->unit_short ? ' ' . $o->unit_short : '')) : '—',
                'exported' => $qty($o->exported ?? 0, $o->unit_short),
                'on_hand' => $num($o->on_hand_unit) . ($o->unit_short ? ' ' . $o->unit_short : ''),
                'on_hand_kg' => $num($o->on_hand_kg),
            ], $row->onhand_rows),
            'unconvertible' => array_map(fn ($u) => [
                'category_code' => $u->category_code,
                'chem_name' => $u->chem_name ?? '',
                'reason' => $u->reason,
            ], $row->unconvertible),
            'timeline' => array_map(fn ($t) => [
                'date' => \Carbon\Carbon::parse($t->date)->format('d/m/Y'),
                'type_label' => $typeLabels[$t->type] ?? $t->type,
                'ref' => $t->ref,
                'category_code' => $t->category_code,
                'department_name' => $t->department_name,
                'delta' => $signed($t->delta_unit) . ($t->unit_short ? ' ' . $t->unit_short : ''),
                'delta_kg' => $signed($t->delta_kg),
                'running_kg' => $num($t->running_kg),
                'is_peak' => (bool) $t->is_peak,
            ], $row->timeline),
        ];
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

        return redirect()->back()->with(
            'success',
            'Đã duyệt ' . self::LABEL . ' ' . $current->code . '!'
        );
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
            'chemical_category_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'type' => $row->type,
            'chem_names_id' => $row->chem_names_id,
            'manufacturers_id' => $row->manufacturers_id,
            'density' => $row->density,
            'ai_content_percent' => $row->ai_content_percent,
            'shelf_life_months' => $row->shelf_life_months,
            'storage_condition_id' => $row->storage_condition_id,
            'doc_no' => $row->doc_no,
            // Cột giữ lại cho ảnh chụp cũ; bản ghi mới không còn phân loại thủ công
            'classification' => null,
            'safety_warning' => $row->safety_warning,
            'lead_time_days' => $row->lead_time_days,
            'national_portal_declared' => $row->national_portal_declared,
            'national_portal_declaration_no' => $row->national_portal_declaration_no,
            'national_portal_manufacturer_code' => $row->national_portal_manufacturer_code,
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
            $old = (string) $current->$field;
            $new = (string) $payload[$field];

            if ($old === $new) {
                continue;
            }

            // DB trả về 1.0400 còn form gửi lên 1.04, phải so sánh theo giá trị số
            if ($field === 'density' || $field === 'ai_content_percent') {
                if ((float) $old === (float) $new) {
                    continue;
                }

                $parts[] = $title . ': ' . $this->formatDensity($current->$field) . ' -> ' . $this->formatDensity($payload[$field]);
                continue;
            }

            if ($field === 'safety_warning') {
                $parts[] = $title . ': '
                    . (implode(', ', $this->decodeCodes($current->$field)) ?: '—') . ' -> '
                    . (implode(', ', $this->decodeCodes($payload[$field])) ?: '—');
                continue;
            }

            if (isset($labels[$field])) {
                $parts[] = $title . ': '
                    . ($labels[$field][$current->$field] ?? '—') . ' -> '
                    . ($labels[$field][$payload[$field]] ?? '—');
                continue;
            }

            $parts[] = $title . ': ' . ($old === '' ? '—' : $old) . ' -> ' . ($new === '' ? '—' : $new);
        }

        return implode(' | ', $parts);
    }

    /** Bảng tra id -> tên của các nguồn dữ liệu gốc, dùng để viết mô tả thay đổi. */
    private function labelMaps(): array
    {
        return [
            'chem_names_id' => DB::table('chem_names')->pluck('name', 'id')->all(),
            'manufacturers_id' => DB::table('manufacturers')->pluck('name', 'id')->all(),
            'storage_condition_id' => DB::table('storage_conditions')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Mã danh mục kế tiếp theo dạng C00001: lấy số lớn nhất đang có rồi cộng 1.
     *
     * Lưu ý: màn hình này chỉ khoá bản ghi (deActive) chứ không xoá, nên mã không bị dùng lại.
     * Nếu xoá cứng bản ghi có mã lớn nhất bằng tay dưới DB thì mã đó sẽ được cấp lại.
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
     * Không cho trùng tổ hợp Tên hoá chất - Loại - Nhà sản xuất: với danh mục đang có và giữa các
     * dòng trong cùng một lần thêm (Loại so không phân biệt khoảng trắng đầu / cuối).
     */
    private function checkBatchDuplicates($validator, array $items): void
    {
        $validator->after(function ($validator) use ($items) {
            $key = fn ($nameId, $manufacturerId, $type) => (int) $nameId . '|' . (int) $manufacturerId . '|' . mb_strtolower(trim((string) $type));

            $existing = DB::table(self::TABLE)
                ->whereIn('chem_names_id', collect($items)->pluck('chem_names_id')->filter()->map(fn ($id) => (int) $id)->all())
                ->get(['chem_names_id', 'manufacturers_id', 'type'])
                ->mapWithKeys(fn ($row) => [$key($row->chem_names_id, $row->manufacturers_id, $row->type) => true]);

            $seen = [];

            foreach ($items as $index => $item) {
                if (blank($item['chem_names_id'] ?? null) || blank($item['manufacturers_id'] ?? null)) {
                    continue;
                }

                $itemKey = $key($item['chem_names_id'], $item['manufacturers_id'], $item['type'] ?? '');

                if (isset($existing[$itemKey])) {
                    $validator->errors()->add('items.' . $index . '.chem_names_id', 'Tổ hợp Tên hoá chất - Loại - Nhà sản xuất này đã có trong danh mục.');
                } elseif (isset($seen[$itemKey])) {
                    $validator->errors()->add('items.' . $index . '.chem_names_id', 'Trùng Tên hoá chất - Loại - Nhà sản xuất với dòng ' . ($seen[$itemKey] + 1) . '.');
                }

                $seen[$itemKey] ??= $index;
            }
        });
    }

    /** Mã dự kiến hiển thị trên form thêm mới (chỉ để xem, mã thật sinh lúc lưu). */
    private function previewNextCode(): string
    {
        return $this->nextCode();
    }

    /**
     * Không cho khai báo trùng cùng một tổ hợp Tên hoá chất + Loại + Nhà sản xuất.
     * Cột type cho phép rỗng nên phải so sánh riêng trường hợp NULL.
     *
     * Lúc sửa: dữ liệu cũ khai trước khi có luật này có thể đang trùng sẵn, chặn luôn thì
     * không ai sửa được các dòng đó nữa. Vì vậy chỉ kiểm tra khi người dùng thật sự đổi
     * một trong ba trường của tổ hợp, giữ nguyên tổ hợp cũ thì cho lưu.
     */
    private function checkDuplicate($validator, Request $request, $ignoreId = null): void
    {
        $validator->after(function ($validator) use ($request, $ignoreId) {
            $type = trim((string) $request->type);

            if ($ignoreId && $this->sameIdentity($ignoreId, (int) $request->chem_names_id, (int) $request->manufacturers_id, $type)) {
                return;
            }

            $exists = DB::table(self::TABLE)
                ->where('chem_names_id', $request->chem_names_id)
                ->where('manufacturers_id', $request->manufacturers_id)
                ->when($type === '', fn ($query) => $query->whereNull('type'))
                ->when($type !== '', fn ($query) => $query->where('type', $type))
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists();

            if ($exists) {
                $validator->errors()->add('chem_names_id', 'Tổ hợp Tên hoá chất - Loại - Nhà sản xuất này đã có trong danh mục.');
            }
        });
    }

    /** Dòng đang sửa có giữ nguyên tổ hợp Tên hoá chất + Loại + Nhà sản xuất hay không. */
    private function sameIdentity($id, int $chemNamesId, int $manufacturersId, string $type): bool
    {
        $current = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $current) {
            return false;
        }

        return (int) $current->chem_names_id === $chemNamesId
            && (int) $current->manufacturers_id === $manufacturersId
            && trim((string) $current->type) === $type;
    }

    /** Bỏ số 0 thừa ở cuối: 1.0400 -> 1.04 */
    private function formatDensity($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $text = (string) $value;

        return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
    }

    /**
     * Số lần thay đổi của từng dòng danh mục: [chemical_category_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc khai báo chứ không phải một lần sửa. Badge trên
     * nút Sửa chỉ hiện khi dòng danh mục thật sự đã bị đổi ít nhất một lần (sửa, khoá,
     * mở khoá, duyệt, từ chối...).
     */
    private function historyCounts()
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('chemical_category_id', DB::raw('COUNT(*) as times'))
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('chemical_category_id')
            ->pluck('times', 'chemical_category_id');
    }

    /** Chuỗi JSON mảng mã (classification hoặc safety_warning) -> mảng mã. */
    private function decodeCodes($value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
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

    /** Mã danh mục sinh tự động nên không nằm trong danh sách kiểm tra. */
    private function rules(): array
    {
        return [
            'type' => ['nullable', 'max:30'],
            'chem_names_id' => ['required', 'integer', 'exists:chem_names,id'],
            'manufacturers_id' => ['required', 'integer', 'exists:manufacturers,id'],
            'density' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
            'ai_content_percent' => ['nullable', 'numeric', 'gt:0', 'max:100'],
            'shelf_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'storage_condition_id' => ['nullable', 'integer', 'exists:storage_conditions,id'],
            'safety_warning' => ['nullable', 'array'],
            'safety_warning.*' => [Rule::in(array_keys(config('chemical.safety_warnings')))],
        ] + \App\Support\MaterialClassification::leadTimeRules();
    }

    /** Giá trị ghi DB - $input là $request->all() (Cập nhật) hoặc một dòng items[i] (Thêm mới). */
    private function payload(array $input): array
    {
        $type = trim((string) ($input['type'] ?? ''));
        $density = trim((string) ($input['density'] ?? ''));
        $aiContentPercent = trim((string) ($input['ai_content_percent'] ?? ''));
        $shelfLife = trim((string) ($input['shelf_life_months'] ?? ''));
        $storageConditionId = trim((string) ($input['storage_condition_id'] ?? ''));

        $selectedWarnings = (array) ($input['safety_warning'] ?? []);
        $warningCodes = array_values(array_intersect(array_keys(config('chemical.safety_warnings')), $selectedWarnings));

        return [
            'type' => $type === '' ? null : $type,
            'chem_names_id' => (int) ($input['chem_names_id'] ?? 0),
            'manufacturers_id' => (int) ($input['manufacturers_id'] ?? 0),
            'density' => $density === '' ? null : $density,
            'ai_content_percent' => $aiContentPercent === '' ? null : $aiContentPercent,
            'shelf_life_months' => $shelfLife === '' ? null : (int) $shelfLife,
            'storage_condition_id' => $storageConditionId === '' ? null : (int) $storageConditionId,
            'safety_warning' => $warningCodes ? json_encode($warningCodes, JSON_UNESCAPED_UNICODE) : null,
            // Không nhận từ form: người có quyền khai sau qua updateLeadTime(), update() giữ giá trị cũ
            'lead_time_days' => null,
        ];
    }

    private function messages(): array
    {
        return [
            'type.max' => 'Loại tối đa 30 ký tự.',
            'chem_names_id.required' => 'Vui lòng chọn tên hoá chất.',
            'chem_names_id.exists' => 'Tên hoá chất không hợp lệ.',
            'manufacturers_id.required' => 'Vui lòng chọn nhà sản xuất.',
            'manufacturers_id.exists' => 'Nhà sản xuất không hợp lệ.',
            'density.numeric' => 'Tỉ trọng phải là số.',
            'density.gt' => 'Tỉ trọng phải lớn hơn 0.',
            'ai_content_percent.numeric' => 'Hàm lượng hoạt chất phải là số.',
            'ai_content_percent.gt' => 'Hàm lượng hoạt chất phải lớn hơn 0.',
            'ai_content_percent.max' => 'Hàm lượng hoạt chất tối đa 100%.',
            'shelf_life_months.integer' => 'Hạn dùng phải là số tháng nguyên.',
            'shelf_life_months.min' => 'Hạn dùng tối thiểu 1 tháng.',
            'shelf_life_months.max' => 'Hạn dùng tối đa 1200 tháng (100 năm).',
            'storage_condition_id.exists' => 'Điều kiện bảo quản không hợp lệ.',
            'safety_warning.*.in' => 'Cảnh báo an toàn không hợp lệ.',
        ] + \App\Support\MaterialClassification::leadTimeMessages();
    }
}
