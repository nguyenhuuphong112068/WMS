<?php

namespace App\Support\InitialData;

use App\Http\Controllers\Pages\MaterData\ConsumptionObjectController;
use App\Http\Controllers\Pages\MaterData\ZoneController;
use App\Support\MaterialClassification;
use App\Support\ZoneType;
use Illuminate\Support\Facades\DB;

/**
 * KHỞI TẠO DỮ LIỆU BAN ĐẦU - CẤU TRÚC FILE EXCEL
 *
 * Nguồn sự thật duy nhất cho file Excel khởi tạo dữ liệu: tên sheet, thứ tự cột, cột bắt
 * buộc, kiểu dữ liệu, danh sách chọn. Cả bộ sinh file mẫu (InitialDataTemplate) lẫn bộ
 * nhập vào DB (InitialDataImporter) cùng đọc ở đây nên file mẫu và bộ nhập không lệch nhau.
 *
 * Thứ tự sheet = thứ tự nhập: sheet sau chỉ tham chiếu tới sheet đứng trước nó (hoặc dữ liệu
 * đã có sẵn trong DB).
 *
 * Bố cục mỗi sheet dữ liệu:
 *   dòng 1 : tiêu đề sheet
 *   dòng 2 : mô tả ngắn
 *   dòng 3 : tên cột (cột bắt buộc có dấu *)
 *   dòng 4 : gợi ý / ví dụ cho từng cột
 *   dòng 5+: dữ liệu khách hàng điền
 *
 * Kiểu cột (type):
 *   text   : chuỗi, giới hạn `max` ký tự
 *   int    : số nguyên, giới hạn `min` / `maxValue`
 *   num    : số thực, giới hạn `min` / `gt` (lớn hơn hẳn) / `maxValue`
 *   date   : ngày dd/mm/yyyy
 *   list   : chọn trong danh sách cố định `list` (nhãn hiển thị => giá trị lưu DB)
 *   ref    : chọn giá trị đã khai ở sheet khác (`ref` = tên vùng chọn trong refs())
 *   multi  : nhiều giá trị cách nhau dấu phẩy / chấm phẩy, mỗi giá trị thuộc `list`
 *   color  : mã màu #RRGGBB
 *   calc   : cột công thức do file mẫu tự tính, bộ nhập bỏ qua
 *   auto   : mã danh mục tự sinh bằng công thức (`prefix` + 5 số), người điền không nhập; bộ nhập
 *            đọc làm khoá tham chiếu trong file và cấp mã thật lúc nạp
 *
 * Dòng dữ liệu đã có trong hệ thống được điền sẵn ở đầu mỗi sheet (khoá, nền xám); số dòng
 * điền sẵn ghi ở sheet ẩn LIST_SHEET để bộ nhập bỏ qua đúng các dòng đó.
 */
class InitialDataSchema
{
    /** Phiên bản cấu trúc file. Đổi cột / sheet thì tăng số này để chặn file mẫu cũ. */
    public const VERSION = 'WMS-INITIAL-DATA-V2';

    /** Tên sheet ẩn chứa danh sách chọn cố định + phiên bản file. */
    public const LIST_SHEET = '_DS';

    /** Sheet hướng dẫn đầu file. */
    public const GUIDE_SHEET = 'Hướng dẫn';

    public const TITLE_ROW = 1;

    public const DESC_ROW = 2;

    public const HEADER_ROW = 3;

    public const HINT_ROW = 4;

    public const FIRST_DATA_ROW = 5;

    /** Số dòng tối đa được gắn danh sách chọn / công thức sẵn trong file mẫu. */
    public const MAX_ROWS = 5000;

    /** Ô ở sheet LIST_SHEET bắt đầu ghi số dòng điền sẵn của từng sheet (dạng "key:số dòng"). */
    public const PREFILL_CELL_ROW = 4;

    /**
     * Mục ghi kèm trong danh sách trên ("@department:<tên viết tắt>") khi file là file riêng của
     * một phòng ban: phòng đó chỉ được khai dữ liệu của chính mình.
     */
    public const SCOPE_KEY = '@department';

    /**
     * Sheet chỉ Admin khai ở file toàn hệ thống. File của phòng ban ẩn hẳn các sheet này (vẫn giữ
     * trong file làm nguồn ô chọn Vai trò / Tổ ở sheet Người dùng) và bộ nạp chặn dòng mới.
     */
    public const ADMIN_ONLY_SHEETS = ['companies', 'departments', 'roles'];

    /** Mục ghi kèm ("@stage:<số>") khi file chỉ thu thập dữ liệu của một giai đoạn. */
    public const STAGE_KEY = '@stage';

    /**
     * Các giai đoạn tập hợp dữ liệu, nạp theo thứ tự: giai đoạn sau chọn lại dữ liệu đã nạp ở
     * giai đoạn trước (lấy từ DB lúc tải file).
     */
    public const STAGES = [
        1 => 'Tổ chức (Admin)',
        2 => 'Người dùng & Tổ - Nhóm',
        3 => 'Dữ liệu gốc & danh mục',
        4 => 'Tồn đầu kỳ',
    ];

    /**
     * Giai đoạn luôn là MỘT file toàn hệ thống dùng chung cho mọi phòng ban: không chia theo phòng
     * ban / module (tổ chức do Admin khai; người dùng các phòng cùng điền một file).
     */
    public const SHARED_STAGES = [1, 2];

    /**
     * Sheet thuộc giai đoạn 1 (tổ chức - Admin chuẩn bị) / 2 (người dùng + tổ - nhóm, các phòng bổ sung
     * tổ còn thiếu ngay trong file người dùng) / 4 (tồn đầu kỳ); sheet còn
     * lại (dữ liệu gốc, tên hàng, danh mục, định khu, hàng của phòng) là giai đoạn 3.
     */
    public const STAGE_SHEETS = [
        'companies' => 1,
        'departments' => 1,
        'roles' => 1,
        'groups' => 2,
        'users' => 2,
        'stock_materials' => 4,
        'stock_chemicals' => 4,
        'stock_standards' => 4,
    ];

    /** Mục ghi kèm ("@module:<module>") khi file chỉ thu thập dữ liệu của một module. */
    public const MODULE_KEY = '@module';

    /** Module tách file thu thập riêng. */
    public const MODULES = ['material' => 'Vật tư', 'chemical' => 'Hoá chất', 'standard' => 'Chất chuẩn'];

    /**
     * Sheet (dữ liệu + tra cứu) thuộc riêng module nào; sheet không có ở đây là sheet dùng chung,
     * có mặt trong file của mọi module (tổ chức, đơn vị, nhà sản xuất, sản phẩm, định khu, quy đổi).
     */
    public const MODULE_SHEETS = [
        'packaging' => ['standard'],
        'storage' => ['chemical', 'standard'],
        'suppliers' => ['chemical', 'standard'],
        'purposes' => ['standard'],
        'consumption' => ['material'],
        'dept_class' => ['material'],
        'material_names' => ['material'],
        'material_cats' => ['material'],
        'dept_materials' => ['material'],
        'stock_materials' => ['material'],
        'chem_names' => ['chemical'],
        'chem_comp' => ['chemical'],
        'chemical_cats' => ['chemical'],
        'dept_chemicals' => ['chemical'],
        'stock_chemicals' => ['chemical'],
        'ref_active_ingredients' => ['chemical'],
        'ref_hazard_categories' => ['chemical'],
        'standard_names' => ['standard'],
        'standard_cats' => ['standard'],
        'dept_standards' => ['standard'],
        'stock_standards' => ['standard'],
    ];

    /** Đánh dấu ghi vào ghi chú các phiếu nhập tồn đầu kỳ - dùng để chặn nhập trùng lần hai. */
    public const OPENING_NOTE = '[TỒN ĐẦU KỲ]';

    /** Ký tự ngăn mã và tên trong ô chọn danh mục: "M00001 | Găng tay nitrile (ANSELL)". */
    public const REF_SEPARATOR = ' | ';

    /**
     * Danh sách chọn cố định: [tên danh sách => [nhãn hiển thị => giá trị lưu DB]].
     */
    public static function lists(): array
    {
        $classification = [];
        foreach (MaterialClassification::CRITERIA as $key => $criterion) {
            $classification['cls_'.$key] = array_flip($criterion['options']);
        }

        $consumptionTypes = [];
        foreach (ConsumptionObjectController::TYPES as $key => $type) {
            $consumptionTypes[$type['label']] = $key;
        }

        $unitGroups = [];
        foreach (config('unit.groups') as $key => $group) {
            $unitGroups[$group['label']] = $key;
        }

        $standardGroups = [];
        foreach (config('standard.groups') as $key => $group) {
            $standardGroups[$key.' - '.$group['name']] = $key;
        }

        $safetyWarnings = [];
        foreach (config('chemical.safety_warnings') as $key => $label) {
            $safetyWarnings[$key.' - '.$label] = $key;
        }

        return [
            'yes_no' => ['Có' => 1, 'Không' => 0],
            'zone_type' => array_flip(ZoneType::TYPES),
            'item_type' => array_flip(ZoneController::LOCATION_TYPES),
            'unit_group' => $unitGroups,
            'purchasing' => array_flip(MaterialClassification::PURCHASING_DEPARTMENTS),
            'consumption_type' => $consumptionTypes,
            'frequency' => array_flip(ConsumptionObjectController::FREQUENCIES),
            'chem_type' => array_combine(config('chemical.types'), config('chemical.types')),
            'category_type' => ['Vật tư' => 'material', 'Hoá chất' => 'chemical', 'Chất chuẩn' => 'standard'],
            'expiry_type' => [
                'Hạn dùng xác định' => 'Specify',
                'Chưa xác định (Check online)' => 'check online',
                'Cần Retest định kỳ' => 'retest',
                'Cần đánh giá lại' => 'Requires_re-evaluation',
            ],
            'standard_form' => ['Dạng Bột Rời' => 'Dạng Bột Rời', 'Dạng Bột Mịn' => 'Dạng Bột Mịn', 'Dạng Sệt' => 'Dạng Sệt'],
            'standard_group' => $standardGroups,
            'safety_warning' => $safetyWarnings,
        ] + $classification;
    }

    /**
     * Vùng chọn tham chiếu sang sheet khác: [tên vùng => [sheet key, cột nguồn]].
     * Cột nguồn là khoá cột của sheet dữ liệu, hoặc chỉ số cột (0 = A) của sheet tra cứu;
     * lấy từ dòng FIRST_DATA_ROW trở xuống.
     */
    public static function refs(): array
    {
        return [
            'R_CONG_TY' => ['companies', 'short_name'],
            'R_PHONG_BAN' => ['departments', 'short_name'],
            'R_TO_NHOM' => ['groups', 'name'],
            'R_VAI_TRO' => ['roles', 'name'],
            'R_DVT' => ['units', 'short_name'],
            'R_DKBQ' => ['storage', 'name'],
            'R_NSX' => ['manufacturers', 'short_name'],
            'R_NCC' => ['suppliers', 'name'],
            'R_MUC_DICH' => ['purposes', 'name'],
            'R_PL_PHONG' => ['dept_class', 'name'],
            'R_TEN_VT' => ['material_names', 'name'],
            'R_TEN_HC' => ['chem_names', 'name'],
            'R_TEN_CC' => ['standard_names', 'name'],
            'R_DM_VT' => ['material_cats', 'display'],
            'R_DM_HC' => ['chemical_cats', 'display'],
            'R_DM_CC' => ['standard_cats', 'display'],
            'R_KHO' => ['warehouses', 'code'],
            'R_KE' => ['shelves', 'code'],
            'R_COT' => ['columns', 'code'],
            'R_TANG' => ['tiers', 'code'],
            'R_VI_TRI' => ['locations', 'code'],
            'R_HOAT_CHAT' => ['ref_active_ingredients', 0],
            'R_BANG_B' => ['ref_hazard_categories', 0],
        ];
    }

    /**
     * Toàn bộ sheet dữ liệu theo đúng thứ tự nhập.
     *
     * Mỗi sheet: title (tên sheet, tối đa 31 ký tự), table (bảng DB chính), group (nhóm hiển
     * thị trong hướng dẫn), desc (mô tả), columns.
     * Mỗi cột: key, label, type, req, max, width, hint, list/ref, ...
     */
    public static function sheets(): array
    {
        static $cache = null;

        return $cache ??= self::buildSheets();
    }

    private static function buildSheets(): array
    {
        $dept = ['key' => 'department', 'label' => 'Phòng ban', 'type' => 'ref', 'ref' => 'R_PHONG_BAN', 'req' => true, 'width' => 16, 'hint' => 'Chọn tên viết tắt phòng ban'];
        $zoneType = ['key' => 'zone_type', 'label' => 'Phân loại khu', 'type' => 'list', 'list' => 'zone_type', 'width' => 18, 'hint' => 'Dự Phòng / Sử Dụng / Biệt Trữ...'];
        $color = ['key' => 'color', 'label' => 'Màu hiển thị', 'type' => 'color', 'width' => 13, 'hint' => 'VD: #16A34A (để trống = màu theo phân loại)'];
        $leadTime = ['key' => 'lead_time_days', 'label' => 'Thời gian đặt hàng (ngày)', 'type' => 'int', 'min' => 0, 'maxValue' => MaterialClassification::MAX_LEAD_TIME_DAYS, 'width' => 14, 'hint' => 'VD: 30'];
        $shelfLife = ['key' => 'shelf_life_months', 'label' => 'Hạn dùng mặc định (tháng)', 'type' => 'int', 'min' => 1, 'maxValue' => 1200, 'width' => 14, 'hint' => 'VD: 24'];
        $minStock = ['key' => 'min_stock', 'label' => 'Tồn tối thiểu', 'type' => 'num', 'min' => 0, 'width' => 12, 'hint' => 'Theo đơn vị của phòng'];
        $maxStock = ['key' => 'max_stock', 'label' => 'Tồn tối đa', 'type' => 'num', 'min' => 0, 'width' => 12, 'hint' => 'Phải ≥ tồn tối thiểu'];
        $defaultLocation = ['key' => 'default_location', 'label' => 'Vị trí mặc định', 'type' => 'ref', 'ref' => 'R_VI_TRI', 'width' => 16, 'hint' => 'Mã vị trí ở sheet 26, cùng phòng ban'];
        $note = ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'text', 'max' => 500, 'width' => 30, 'hint' => ''];
        $stockLocation = ['key' => 'location', 'label' => 'Vị trí lưu', 'type' => 'ref', 'ref' => 'R_VI_TRI', 'req' => true, 'width' => 16, 'hint' => 'Mã vị trí ở sheet 26, cùng phòng ban'];
        $autoCode = fn (string $prefix) => ['key' => 'code', 'label' => 'Mã DM (tự sinh)', 'type' => 'auto', 'prefix' => $prefix, 'width' => 12, 'hint' => 'Tự sinh khi điền tên, không nhập'];
        $packs = ['key' => 'packs', 'label' => 'Số kiện / chai (tách mã)', 'type' => 'int', 'min' => 1, 'maxValue' => 1000, 'width' => 13, 'hint' => 'Để trống = 1. Mỗi kiện 1 mã xuất nhập'];

        return [
            // ================================================================ TỔ CHỨC
            'companies' => [
                'title' => '01.Công ty',
                'table' => 'companies',
                'group' => 'Tổ chức',
                'desc' => 'Công ty / nhà máy sử dụng hệ thống. Mã công ty (CT00001...) do hệ thống tự sinh.',
                'columns' => [
                    ['key' => 'short_name', 'label' => 'Tên viết tắt', 'type' => 'text', 'req' => true, 'max' => 50, 'width' => 22, 'hint' => 'VD: STELLA-NM1 (không trùng)'],
                    ['key' => 'name', 'label' => 'Tên công ty', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 55, 'hint' => 'Tên đầy đủ, không trùng'],
                ],
            ],
            'departments' => [
                'title' => '02.Phòng ban',
                'table' => 'deparments',
                'group' => 'Tổ chức',
                'desc' => 'Phòng ban / bộ phận. Tên viết tắt dùng làm tiền tố mã ống chuẩn nên nên ngắn, không dấu cách.',
                'columns' => [
                    ['key' => 'short_name', 'label' => 'Tên viết tắt', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 16, 'hint' => 'VD: QC1 (không trùng)'],
                    ['key' => 'name', 'label' => 'Tên phòng ban', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 40, 'hint' => 'VD: Kiểm tra chất lượng 1'],
                    ['key' => 'company', 'label' => 'Công ty', 'type' => 'ref', 'ref' => 'R_CONG_TY', 'req' => true, 'width' => 22, 'hint' => 'Chọn ở sheet 01'],
                    ['key' => 'eng_short_name', 'label' => 'Tên viết tắt tiếng Anh', 'type' => 'text', 'max' => 50, 'width' => 16, 'hint' => 'VD: QC'],
                    ['key' => 'is_common', 'label' => 'Phòng ban chung', 'type' => 'list', 'list' => 'yes_no', 'width' => 14, 'hint' => 'Có = BGĐ, Cung ứng... (không có kho). Trống = Không'],
                ],
            ],
            'groups' => [
                'title' => '03.Tổ - Nhóm',
                'table' => 'groups',
                'group' => 'Tổ chức',
                'desc' => 'Tổ / nhóm trực thuộc từng phòng ban. Cùng phòng ban không được trùng tên.',
                'columns' => [
                    $dept,
                    ['key' => 'name', 'label' => 'Tên tổ / nhóm', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 35, 'hint' => 'VD: Tổ Hoá lý'],
                ],
            ],
            'roles' => [
                'title' => '04.Vai trò',
                'table' => 'roles',
                'group' => 'Tổ chức',
                'desc' => 'Vai trò (nhóm quyền). Dòng đã có sẵn trong hệ thống được giữ nguyên. Quyền chi tiết của từng vai trò cấu hình sau trên màn hình Phân Quyền.',
                'columns' => [
                    ['key' => 'name', 'label' => 'Tên vai trò', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 35, 'hint' => 'VD: Quản Lý Kho Vật Tư'],
                    ['key' => 'description', 'label' => 'Mô tả', 'type' => 'text', 'max' => 255, 'width' => 55, 'hint' => ''],
                ],
            ],
            'users' => [
                'title' => '05.Người dùng',
                'table' => 'user_management',
                'group' => 'Tổ chức',
                'desc' => 'Tài khoản đăng nhập, đăng nhập bằng mã số nhân viên. KHÔNG ghi mật khẩu vào file: người nhập khai mật khẩu khởi tạo chung lúc nhập, người dùng bắt buộc đổi ở lần đăng nhập đầu.',
                'columns' => [
                    ['key' => 'user_name', 'label' => 'Mã số nhân viên', 'type' => 'text', 'req' => true, 'min_len' => 5, 'max' => 10, 'width' => 16, 'hint' => 'Dùng làm tên đăng nhập. 5-10 ký tự, không trùng'],
                    ['key' => 'full_name', 'label' => 'Họ và tên', 'type' => 'text', 'req' => true, 'min_len' => 5, 'max' => 255, 'width' => 28, 'hint' => 'VD: Nguyễn Văn An'],
                    $dept,
                    ['key' => 'mail', 'label' => 'Email', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 28, 'hint' => 'Không có thì ghi NA'],
                    ['key' => 'role_1', 'label' => 'Vai trò chính', 'type' => 'ref', 'ref' => 'R_VAI_TRO', 'req' => true, 'width' => 26, 'hint' => 'Chọn trong danh sách vai trò'],
                    ['key' => 'role_2', 'label' => 'Vai trò 2', 'type' => 'ref', 'ref' => 'R_VAI_TRO', 'width' => 22, 'hint' => ''],
                    ['key' => 'role_3', 'label' => 'Vai trò 3', 'type' => 'ref', 'ref' => 'R_VAI_TRO', 'width' => 22, 'hint' => ''],
                    ['key' => 'group', 'label' => 'Tổ / nhóm', 'type' => 'ref', 'ref' => 'R_TO_NHOM', 'width' => 22, 'hint' => 'Tổ thuộc đúng phòng ban ở trên'],
                ],
            ],

            // ================================================================ DỮ LIỆU GỐC DÙNG CHUNG
            'units' => [
                'title' => '06.Đơn vị tính',
                'table' => 'units',
                'group' => 'Dữ liệu gốc dùng chung',
                'desc' => 'Đơn vị tính. Hệ số = 1 đơn vị này bằng bao nhiêu đơn vị gốc của nhóm (g / ml / đơn vị). Đã điền sẵn bộ đơn vị chuẩn, bổ sung thêm nếu cần.',
                'columns' => [
                    ['key' => 'short_name', 'label' => 'Ký hiệu', 'type' => 'text', 'req' => true, 'max' => 100, 'width' => 12, 'hint' => 'VD: kg (không trùng)'],
                    ['key' => 'name', 'label' => 'Tên đơn vị', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 24, 'hint' => 'VD: Ki-lô-gam (không trùng)'],
                    ['key' => 'unit_group', 'label' => 'Nhóm đơn vị', 'type' => 'list', 'list' => 'unit_group', 'req' => true, 'width' => 18, 'hint' => 'Khối lượng / Thể tích / Đếm'],
                    ['key' => 'factor_to_base', 'label' => 'Hệ số về đơn vị gốc', 'type' => 'num', 'gt' => 0, 'req' => true, 'width' => 16, 'hint' => 'VD: kg = 1000 (gốc g)'],
                ],
            ],
            'packaging' => [
                'title' => '07.Quy cách đóng gói',
                'table' => 'packaging_specifications',
                'group' => 'Dữ liệu gốc dùng chung',
                'desc' => 'Quy cách đóng gói thường gặp.',
                'columns' => [
                    ['key' => 'name', 'label' => 'Quy cách đóng gói', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 40, 'hint' => 'VD: Thùng 10 hộp'],
                ],
            ],
            'storage' => [
                'title' => '08.Điều kiện bảo quản',
                'table' => 'storage_conditions',
                'group' => 'Dữ liệu gốc dùng chung',
                'desc' => 'Điều kiện bảo quản dùng cho danh mục hoá chất / chất chuẩn.',
                'columns' => [
                    ['key' => 'name', 'label' => 'Điều kiện bảo quản', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 34, 'hint' => 'VD: Tủ lạnh (2-8°C)'],
                    ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'text', 'max' => 500, 'width' => 40, 'hint' => ''],
                ],
            ],
            'manufacturers' => [
                'title' => '09.Nhà sản xuất',
                'table' => 'manufacturers',
                'group' => 'Dữ liệu gốc dùng chung',
                'desc' => 'Nhà sản xuất / nguồn gốc của vật tư, hoá chất, chất chuẩn.',
                'columns' => [
                    ['key' => 'short_name', 'label' => 'Tên viết tắt', 'type' => 'text', 'req' => true, 'max' => 100, 'width' => 18, 'hint' => 'VD: MERCK (không trùng)'],
                    ['key' => 'name', 'label' => 'Tên nhà sản xuất', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 45, 'hint' => 'Tên đầy đủ (không trùng)'],
                ],
            ],
            'suppliers' => [
                'title' => '10.Nhà cung cấp',
                'table' => 'suppliers',
                'group' => 'Dữ liệu gốc dùng chung',
                'desc' => 'Nhà cung cấp (bên bán hàng).',
                'columns' => [
                    ['key' => 'name', 'label' => 'Tên nhà cung cấp', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 40, 'hint' => 'Không trùng'],
                    ['key' => 'address', 'label' => 'Địa chỉ', 'type' => 'text', 'max' => 500, 'width' => 40, 'hint' => ''],
                    ['key' => 'tax_no', 'label' => 'Mã số thuế', 'type' => 'text', 'max' => 100, 'width' => 16, 'hint' => ''],
                    ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'text', 'max' => 1000, 'width' => 30, 'hint' => ''],
                ],
            ],
            'purposes' => [
                'title' => '11.Mục đích sử dụng',
                'table' => 'purposes',
                'group' => 'Dữ liệu gốc dùng chung',
                'desc' => 'Mục đích sử dụng / chỉ tiêu kiểm nghiệm của chất chuẩn.',
                'columns' => [
                    ['key' => 'name', 'label' => 'Mục đích / chỉ tiêu', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 34, 'hint' => 'VD: Định lượng'],
                ],
            ],
            'products' => [
                'title' => '12.Tên sản phẩm',
                'table' => 'product_names',
                'group' => 'Dữ liệu gốc dùng chung',
                'desc' => 'Tên thành phẩm của nhà máy (dùng khi đề nghị cấp phát theo sản phẩm).',
                'columns' => [
                    ['key' => 'name', 'label' => 'Tên sản phẩm', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 50, 'hint' => 'Không trùng'],
                ],
            ],
            'consumption' => [
                'title' => '13.Đối tượng tiêu hao',
                'table' => 'consumption_objects',
                'group' => 'Dữ liệu gốc dùng chung',
                'desc' => 'Thiết bị / đối tượng tiêu hao vật tư định kỳ. Không trùng bộ (Loại + Mã + Tần suất).',
                'columns' => [
                    ['key' => 'type', 'label' => 'Loại đối tượng', 'type' => 'list', 'list' => 'consumption_type', 'req' => true, 'width' => 22, 'hint' => 'Chọn trong danh sách'],
                    ['key' => 'code', 'label' => 'Mã', 'type' => 'text', 'req' => true, 'max' => 50, 'width' => 16, 'hint' => 'VD: TB-001'],
                    ['key' => 'name', 'label' => 'Tên', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 36, 'hint' => 'VD: Máy HPLC 01'],
                    ['key' => 'location', 'label' => 'Vị trí đặt', 'type' => 'text', 'max' => 255, 'width' => 22, 'hint' => ''],
                    ['key' => 'frequency', 'label' => 'Tần suất', 'type' => 'list', 'list' => 'frequency', 'req' => true, 'width' => 16, 'hint' => 'VD: Hằng tháng'],
                ],
            ],
            'dept_class' => [
                'title' => '14.Phân loại của phòng',
                'table' => 'department_classification',
                'group' => 'Dữ liệu gốc dùng chung',
                'desc' => 'Phân loại vật tư riêng của từng phòng ban (dùng ở sheet 27). Cùng phòng không trùng tên.',
                'columns' => [
                    $dept,
                    ['key' => 'name', 'label' => 'Tên phân loại', 'type' => 'text', 'req' => true, 'max' => 100, 'width' => 34, 'hint' => 'VD: Dụng cụ thuỷ tinh'],
                ],
            ],

            // ================================================================ TÊN HÀNG
            'material_names' => [
                'title' => '15.Tên vật tư',
                'table' => 'material_names',
                'group' => 'Tên vật tư / hoá chất / chất chuẩn',
                'desc' => 'Tên vật tư (không kèm nhà sản xuất). Không trùng.',
                'columns' => [
                    ['key' => 'name', 'label' => 'Tên vật tư', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 55, 'hint' => 'VD: Găng tay nitrile size M'],
                ],
            ],
            'chem_names' => [
                'title' => '16.Tên hoá chất',
                'table' => 'chem_names',
                'group' => 'Tên vật tư / hoá chất / chất chuẩn',
                'desc' => 'Tên hoá chất (không kèm nhà sản xuất). Thành phần hoạt chất khai ở sheet 17.',
                'columns' => [
                    ['key' => 'name', 'label' => 'Tên hoá chất', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 55, 'hint' => 'VD: Acid sulfuric 98%'],
                ],
            ],
            'chem_comp' => [
                'title' => '17.Thành phần hoá chất',
                'table' => 'chem_name_active_ingredient',
                'group' => 'Tên vật tư / hoá chất / chất chuẩn',
                'desc' => 'Mỗi dòng 1 thành phần: hoặc 1 hoạt chất (Bảng A, tra sheet TC.Hoạt chất) hoặc 1 nhóm nguy hại (Bảng B - chỉ cho hỗn hợp ≥ 2 hoạt chất, có thành phần nhóm 9).',
                'columns' => [
                    ['key' => 'chem_name', 'label' => 'Tên hoá chất', 'type' => 'ref', 'ref' => 'R_TEN_HC', 'req' => true, 'width' => 45, 'hint' => 'Chọn ở sheet 16'],
                    ['key' => 'ai_code', 'label' => 'Mã hoạt chất (Bảng A)', 'type' => 'ref', 'ref' => 'R_HOAT_CHAT', 'width' => 18, 'hint' => 'VD: A00001'],
                    ['key' => 'content_percent', 'label' => 'Hàm lượng (% KL)', 'type' => 'num', 'min' => 0, 'maxValue' => 100, 'width' => 14, 'hint' => 'VD: 98'],
                    ['key' => 'hazard_code', 'label' => 'Mã nhóm nguy hại (Bảng B)', 'type' => 'ref', 'ref' => 'R_BANG_B', 'width' => 18, 'hint' => 'VD: B00001'],
                ],
            ],
            'standard_names' => [
                'title' => '18.Tên chất chuẩn',
                'table' => 'standard_names',
                'group' => 'Tên vật tư / hoá chất / chất chuẩn',
                'desc' => 'Tên chất chuẩn. Không trùng.',
                'columns' => [
                    ['key' => 'name', 'label' => 'Tên chất chuẩn', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 45, 'hint' => 'VD: Paracetamol RS'],
                    ['key' => 'active_ingredient_name', 'label' => 'Tên hoạt chất', 'type' => 'text', 'max' => 255, 'width' => 30, 'hint' => 'VD: Paracetamol'],
                    ['key' => 'cas_no', 'label' => 'Số CAS', 'type' => 'text', 'max' => 100, 'width' => 16, 'hint' => 'VD: 103-90-2'],
                ],
            ],

            // ================================================================ DANH MỤC CÔNG TY
            'material_cats' => [
                'title' => '19.DM vật tư',
                'table' => 'material_categories',
                'group' => 'Danh mục công ty',
                'desc' => 'Danh mục vật tư = Tên vật tư + Nhà sản xuất. Mã danh mục (M00001...) tự sinh nối tiếp mã đã có khi điền tên, các sheet sau chọn lại theo mã này.',
                'columns' => [
                    $autoCode('M'),
                    ['key' => 'material_name', 'label' => 'Tên vật tư', 'type' => 'ref', 'ref' => 'R_TEN_VT', 'req' => true, 'width' => 40, 'hint' => 'Chọn ở sheet 15'],
                    ['key' => 'manufacturer', 'label' => 'Nhà sản xuất', 'type' => 'ref', 'ref' => 'R_NSX', 'req' => true, 'width' => 16, 'hint' => 'Chọn ở sheet 09'],
                    ['key' => 'technical_specification', 'label' => 'Thông tin kỹ thuật', 'type' => 'text', 'max' => 100, 'width' => 24, 'hint' => 'VD: Size M, hộp 100 cái'],
                    ['key' => 'cls_price', 'label' => 'Theo giá trị', 'type' => 'list', 'list' => 'cls_price', 'req' => true, 'width' => 11, 'hint' => 'Thấp / Cao'],
                    ['key' => 'cls_importance', 'label' => 'Mức độ quan trọng', 'type' => 'list', 'list' => 'cls_importance', 'req' => true, 'width' => 12, 'hint' => 'Thấp / Cao'],
                    ['key' => 'cls_dangerous', 'label' => 'Hàng nguy hiểm', 'type' => 'list', 'list' => 'cls_dangerous', 'req' => true, 'width' => 11, 'hint' => 'Có / Không'],
                    ['key' => 'purchasing_department', 'label' => 'Bộ phận mua hàng', 'type' => 'list', 'list' => 'purchasing', 'req' => true, 'width' => 13, 'hint' => 'Cung Ứng / Hành Chánh'],
                    ['key' => 'cls_qa_calibration', 'label' => 'Cần hiệu chuẩn trước khi dùng', 'type' => 'list', 'list' => 'cls_qa_calibration', 'req' => true, 'width' => 13, 'hint' => 'Có / Không'],
                    $leadTime,
                    ['key' => 'display', 'label' => 'Hiển thị chọn (tự động)', 'type' => 'calc', 'width' => 40, 'hidden' => true, 'formula' => '=IF(A{r}="","",A{r}&" | "&B{r}&" ("&C{r}&")")'],
                ],
            ],
            'chemical_cats' => [
                'title' => '20.DM hoá chất',
                'table' => 'chemical_categories',
                'group' => 'Danh mục công ty',
                'desc' => 'Danh mục hoá chất = Tên hoá chất + Loại + Nhà sản xuất. Mã danh mục (C00001...) tự sinh nối tiếp mã đã có khi điền tên.',
                'columns' => [
                    $autoCode('C'),
                    ['key' => 'chem_name', 'label' => 'Tên hoá chất', 'type' => 'ref', 'ref' => 'R_TEN_HC', 'req' => true, 'width' => 40, 'hint' => 'Chọn ở sheet 16'],
                    ['key' => 'type', 'label' => 'Loại', 'type' => 'list', 'list' => 'chem_type', 'free' => true, 'max' => 30, 'width' => 18, 'hint' => 'Chọn hoặc tự gõ (≤ 30 ký tự)'],
                    ['key' => 'manufacturer', 'label' => 'Nhà sản xuất', 'type' => 'ref', 'ref' => 'R_NSX', 'req' => true, 'width' => 16, 'hint' => 'Chọn ở sheet 09'],
                    ['key' => 'density', 'label' => 'Tỉ trọng d (g/ml)', 'type' => 'num', 'gt' => 0, 'maxValue' => 999999, 'width' => 12, 'hint' => 'VD: 1.84'],
                    ['key' => 'ai_content_percent', 'label' => 'Hàm lượng hoạt chất (%)', 'type' => 'num', 'gt' => 0, 'maxValue' => 100, 'width' => 13, 'hint' => 'VD: 98'],
                    $shelfLife,
                    ['key' => 'storage_condition', 'label' => 'Điều kiện bảo quản', 'type' => 'ref', 'ref' => 'R_DKBQ', 'width' => 24, 'hint' => 'Chọn ở sheet 08'],
                    ['key' => 'doc_no', 'label' => 'Số tài liệu', 'type' => 'text', 'max' => 20, 'width' => 14, 'hint' => 'VD: QC-SOP-012'],
                    ['key' => 'safety_warning', 'label' => 'Cảnh báo an toàn', 'type' => 'multi', 'list' => 'safety_warning', 'width' => 26, 'hint' => 'Mã cách nhau dấu phẩy: TOXIC, FLAMMABLE'],
                    $leadTime,
                    ['key' => 'display', 'label' => 'Hiển thị chọn (tự động)', 'type' => 'calc', 'width' => 40, 'hidden' => true, 'formula' => '=IF(A{r}="","",A{r}&" | "&B{r}&IF(C{r}="",""," - "&C{r})&" ("&D{r}&")")'],
                ],
            ],
            'standard_cats' => [
                'title' => '21.DM chất chuẩn',
                'table' => 'standard_categories',
                'group' => 'Danh mục công ty',
                'desc' => 'Danh mục chất chuẩn = Tên chất chuẩn + Nguồn gốc/NSX. Mã danh mục (S00001...) tự sinh nối tiếp mã đã có khi điền tên. Version tự tăng theo cùng Tên + NSX.',
                'columns' => [
                    $autoCode('S'),
                    ['key' => 'standard_name', 'label' => 'Tên chất chuẩn', 'type' => 'ref', 'ref' => 'R_TEN_CC', 'req' => true, 'width' => 36, 'hint' => 'Chọn ở sheet 18'],
                    ['key' => 'manufacturer', 'label' => 'Nguồn gốc / NSX', 'type' => 'ref', 'ref' => 'R_NSX', 'req' => true, 'width' => 16, 'hint' => 'Chọn ở sheet 09'],
                    ['key' => 'cas_no', 'label' => 'Số CAS', 'type' => 'text', 'max' => 50, 'width' => 14, 'hint' => ''],
                    ['key' => 'density', 'label' => 'Tỉ trọng d (g/ml)', 'type' => 'num', 'gt' => 0, 'maxValue' => 999999, 'width' => 12, 'hint' => ''],
                    ['key' => 'storage_condition', 'label' => 'Điều kiện bảo quản', 'type' => 'ref', 'ref' => 'R_DKBQ', 'width' => 24, 'hint' => 'Chọn ở sheet 08'],
                    ['key' => 'groups', 'label' => 'Phân nhóm chuẩn', 'type' => 'multi', 'list' => 'standard_group', 'req' => true, 'width' => 20, 'hint' => 'Mã cách nhau dấu phẩy: PRS, IMPRS'],
                    $shelfLife,
                    ['key' => 'doc_no', 'label' => 'Số tài liệu', 'type' => 'text', 'max' => 20, 'width' => 14, 'hint' => ''],
                    $note,
                    $leadTime,
                    ['key' => 'display', 'label' => 'Hiển thị chọn (tự động)', 'type' => 'calc', 'width' => 40, 'hidden' => true, 'formula' => '=IF(A{r}="","",A{r}&" | "&B{r}&" ("&C{r}&")")'],
                ],
            ],

            // ================================================================ ĐỊNH KHU
            'warehouses' => [
                'title' => '22.Kho - Phòng',
                'table' => 'warehouses',
                'group' => 'Định khu kho',
                'desc' => 'Cấp 1 định khu: kho / phòng. Mã không trùng trong toàn hệ thống.',
                'columns' => [
                    $dept,
                    ['key' => 'code', 'label' => 'Mã kho', 'type' => 'text', 'req' => true, 'max' => 50, 'width' => 14, 'hint' => 'VD: QC1-K01'],
                    ['key' => 'name', 'label' => 'Tên kho / phòng', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 30, 'hint' => 'VD: Kho hoá chất'],
                    $zoneType,
                    $color,
                ],
            ],
            'shelves' => [
                'title' => '23.Kệ - Tủ',
                'table' => 'shelves',
                'group' => 'Định khu kho',
                'desc' => 'Cấp 2 định khu: kệ / tủ, thuộc 1 kho. Mã không trùng trong toàn hệ thống.',
                'columns' => [
                    $dept,
                    ['key' => 'code', 'label' => 'Mã kệ / tủ', 'type' => 'text', 'req' => true, 'max' => 50, 'width' => 14, 'hint' => 'VD: QC1-K01-T01'],
                    ['key' => 'name', 'label' => 'Tên kệ / tủ', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 26, 'hint' => 'VD: Tủ hoá chất 1'],
                    ['key' => 'warehouse', 'label' => 'Mã kho', 'type' => 'ref', 'ref' => 'R_KHO', 'width' => 14, 'hint' => 'Chọn ở sheet 22'],
                    $zoneType,
                    $color,
                ],
            ],
            'columns' => [
                'title' => '24.Cột',
                'table' => 'columns',
                'group' => 'Định khu kho',
                'desc' => 'Cấp 3 định khu: cột của kệ. Khai mã kệ thì kho tự lấy theo kệ.',
                'columns' => [
                    $dept,
                    ['key' => 'code', 'label' => 'Mã cột', 'type' => 'text', 'req' => true, 'max' => 50, 'width' => 16, 'hint' => 'VD: QC1-T01-C1'],
                    ['key' => 'name', 'label' => 'Tên cột', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 20, 'hint' => 'VD: Cột 1'],
                    ['key' => 'warehouse', 'label' => 'Mã kho', 'type' => 'ref', 'ref' => 'R_KHO', 'width' => 14, 'hint' => 'Có thể bỏ trống nếu có mã kệ'],
                    ['key' => 'shelf', 'label' => 'Mã kệ / tủ', 'type' => 'ref', 'ref' => 'R_KE', 'width' => 16, 'hint' => 'Chọn ở sheet 23'],
                    $zoneType,
                    $color,
                ],
            ],
            'tiers' => [
                'title' => '25.Tầng',
                'table' => 'tiers',
                'group' => 'Định khu kho',
                'desc' => 'Cấp 4 định khu: tầng. Khai cấp cha gần nhất là đủ, các cấp trên tự suy ra.',
                'columns' => [
                    $dept,
                    ['key' => 'code', 'label' => 'Mã tầng', 'type' => 'text', 'req' => true, 'max' => 50, 'width' => 16, 'hint' => 'VD: QC1-T01-C1-T1'],
                    ['key' => 'name', 'label' => 'Tên tầng', 'type' => 'text', 'req' => true, 'max' => 255, 'width' => 20, 'hint' => 'VD: Tầng 1'],
                    ['key' => 'warehouse', 'label' => 'Mã kho', 'type' => 'ref', 'ref' => 'R_KHO', 'width' => 14, 'hint' => ''],
                    ['key' => 'shelf', 'label' => 'Mã kệ / tủ', 'type' => 'ref', 'ref' => 'R_KE', 'width' => 16, 'hint' => ''],
                    ['key' => 'column', 'label' => 'Mã cột', 'type' => 'ref', 'ref' => 'R_COT', 'width' => 16, 'hint' => ''],
                    $zoneType,
                    $color,
                ],
            ],
            'locations' => [
                'title' => '26.Vị trí',
                'table' => 'locations',
                'group' => 'Định khu kho',
                'desc' => 'Cấp 5 định khu: vị trí (ô) đặt hàng - nơi gắn tồn kho. Loại lưu trữ để trống = dùng chung cả 3 loại hàng.',
                'columns' => [
                    $dept,
                    ['key' => 'code', 'label' => 'Mã vị trí', 'type' => 'text', 'req' => true, 'max' => 50, 'width' => 18, 'hint' => 'VD: QC1-A01 (không trùng)'],
                    ['key' => 'warehouse', 'label' => 'Mã kho', 'type' => 'ref', 'ref' => 'R_KHO', 'width' => 14, 'hint' => ''],
                    ['key' => 'shelf', 'label' => 'Mã kệ / tủ', 'type' => 'ref', 'ref' => 'R_KE', 'width' => 16, 'hint' => ''],
                    ['key' => 'column', 'label' => 'Mã cột', 'type' => 'ref', 'ref' => 'R_COT', 'width' => 16, 'hint' => ''],
                    ['key' => 'tier', 'label' => 'Mã tầng', 'type' => 'ref', 'ref' => 'R_TANG', 'width' => 16, 'hint' => ''],
                    ['key' => 'item_type', 'label' => 'Loại lưu trữ', 'type' => 'list', 'list' => 'item_type', 'width' => 14, 'hint' => 'Trống = dùng chung'],
                    $zoneType,
                    $color,
                ],
            ],

            // ================================================================ HÀNG CỦA PHÒNG
            'dept_materials' => [
                'title' => '27.Vật tư của phòng',
                'table' => 'material_department_categories',
                'group' => 'Khai báo hàng của phòng',
                'desc' => 'Phòng nào dùng / quản lý vật tư nào, theo đơn vị nào. Phải khai ở đây thì mới nhập tồn được.',
                'columns' => [
                    $dept,
                    ['key' => 'category', 'label' => 'Danh mục vật tư', 'type' => 'ref', 'ref' => 'R_DM_VT', 'req' => true, 'width' => 50, 'hint' => 'Chọn ở sheet 19'],
                    ['key' => 'unit', 'label' => 'Đơn vị tính của phòng', 'type' => 'ref', 'ref' => 'R_DVT', 'req' => true, 'width' => 12, 'hint' => 'Chọn ở sheet 06'],
                    ['key' => 'classification', 'label' => 'Phân loại của phòng', 'type' => 'ref', 'ref' => 'R_PL_PHONG', 'width' => 22, 'hint' => 'Sheet 14, cùng phòng ban'],
                    $minStock,
                    $maxStock,
                    $defaultLocation,
                    $note,
                ],
            ],
            'dept_chemicals' => [
                'title' => '28.Hoá chất của phòng',
                'table' => 'chemical_department_categories',
                'group' => 'Khai báo hàng của phòng',
                'desc' => 'Phòng nào dùng / quản lý hoá chất nào, theo đơn vị nào. Phải khai ở đây thì mới nhập tồn được.',
                'columns' => [
                    $dept,
                    ['key' => 'category', 'label' => 'Danh mục hoá chất', 'type' => 'ref', 'ref' => 'R_DM_HC', 'req' => true, 'width' => 50, 'hint' => 'Chọn ở sheet 20'],
                    ['key' => 'unit', 'label' => 'Đơn vị tính của phòng', 'type' => 'ref', 'ref' => 'R_DVT', 'req' => true, 'width' => 12, 'hint' => 'Chọn ở sheet 06'],
                    ['key' => 'pack_weight_kg', 'label' => 'KL 1 đơn vị (kg)', 'type' => 'num', 'gt' => 0, 'width' => 13, 'hint' => 'Chỉ khi ĐVT là chai/can...'],
                    ['key' => 'shelf_life_months', 'label' => 'Hạn dùng sau mở (tháng)', 'type' => 'int', 'min' => 1, 'maxValue' => 1200, 'width' => 13, 'hint' => 'VD: 6'],
                    ['key' => 'storage_condition', 'label' => 'Điều kiện bảo quản', 'type' => 'ref', 'ref' => 'R_DKBQ', 'width' => 22, 'hint' => 'Trống = theo danh mục'],
                    $minStock,
                    $maxStock,
                    $defaultLocation,
                    $note,
                ],
            ],
            'dept_standards' => [
                'title' => '29.Chất chuẩn của phòng',
                'table' => 'standard_department_categories',
                'group' => 'Khai báo hàng của phòng',
                'desc' => 'Phòng nào dùng / quản lý chất chuẩn nào, theo đơn vị nào. Phải khai ở đây thì mới nhập tồn được.',
                'columns' => [
                    $dept,
                    ['key' => 'category', 'label' => 'Danh mục chất chuẩn', 'type' => 'ref', 'ref' => 'R_DM_CC', 'req' => true, 'width' => 50, 'hint' => 'Chọn ở sheet 21'],
                    ['key' => 'unit', 'label' => 'Đơn vị tính của phòng', 'type' => 'ref', 'ref' => 'R_DVT', 'req' => true, 'width' => 12, 'hint' => 'Chọn ở sheet 06'],
                    ['key' => 'shelf_life_months', 'label' => 'Hạn dùng sau mở (tháng)', 'type' => 'int', 'min' => 1, 'maxValue' => 1200, 'width' => 13, 'hint' => ''],
                    $minStock,
                    $maxStock,
                    $defaultLocation,
                    $note,
                ],
            ],
            'conversions' => [
                'title' => '30.Quy đổi đơn vị',
                'table' => 'category_unit_conversions',
                'group' => 'Khai báo hàng của phòng',
                'desc' => 'Chỉ cần khi cùng 1 mã hàng mà các phòng dùng đơn vị khác nhóm (VD: phòng A dùng "chai", phòng B dùng "ml"). Ý nghĩa: 1 [Từ đơn vị] = [Hệ số] [Sang đơn vị].',
                'columns' => [
                    ['key' => 'category_type', 'label' => 'Loại hàng', 'type' => 'list', 'list' => 'category_type', 'req' => true, 'width' => 13, 'hint' => 'Vật tư / Hoá chất / Chất chuẩn'],
                    ['key' => 'category_code', 'label' => 'Mã DM', 'type' => 'text', 'req' => true, 'max' => 50, 'width' => 12, 'hint' => 'VD: C00001'],
                    ['key' => 'from_unit', 'label' => 'Từ đơn vị', 'type' => 'ref', 'ref' => 'R_DVT', 'req' => true, 'width' => 12, 'hint' => 'VD: chai'],
                    ['key' => 'to_unit', 'label' => 'Sang đơn vị', 'type' => 'ref', 'ref' => 'R_DVT', 'req' => true, 'width' => 12, 'hint' => 'VD: ml'],
                    ['key' => 'factor', 'label' => 'Hệ số', 'type' => 'num', 'gt' => 0, 'req' => true, 'width' => 12, 'hint' => 'VD: 500'],
                    $note,
                ],
            ],

            // ================================================================ TỒN ĐẦU KỲ
            'stock_materials' => [
                'title' => '31.Tồn đầu - Vật tư',
                'table' => 'material_imports',
                'group' => 'Tồn đầu kỳ',
                'desc' => 'Tồn kho thực tế lúc bắt đầu dùng hệ thống. Ngày nhập = ngày nhập file. Lô được ghi nhận ĐẠT kiểm tra và cộng ngay vào tồn.',
                'columns' => [
                    $dept,
                    ['key' => 'category', 'label' => 'Danh mục vật tư', 'type' => 'ref', 'ref' => 'R_DM_VT', 'req' => true, 'width' => 50, 'hint' => 'Đã khai ở sheet 27 cho phòng này'],
                    ['key' => 'amount', 'label' => 'Số lượng mỗi kiện', 'type' => 'num', 'gt' => 0, 'req' => true, 'width' => 12, 'hint' => 'Theo ĐVT của phòng (sheet 27)'],
                    $packs,
                    ['key' => 'batch_no', 'label' => 'Số lô', 'type' => 'text', 'max' => 100, 'width' => 14, 'hint' => ''],
                    ['key' => 'invoice_number', 'label' => 'Số hoá đơn', 'type' => 'text', 'max' => 100, 'width' => 14, 'hint' => ''],
                    ['key' => 'invoice_date', 'label' => 'Ngày hoá đơn', 'type' => 'date', 'width' => 13, 'hint' => 'dd/mm/yyyy'],
                    ['key' => 'expired_date', 'label' => 'Hạn sử dụng', 'type' => 'date', 'width' => 13, 'hint' => 'dd/mm/yyyy'],
                    $stockLocation,
                    ['key' => 'purpose', 'label' => 'Mục đích', 'type' => 'text', 'max' => 500, 'width' => 24, 'hint' => ''],
                    $note,
                ],
            ],
            'stock_chemicals' => [
                'title' => '32.Tồn đầu - Hoá chất',
                'table' => 'chemical_imports',
                'group' => 'Tồn đầu kỳ',
                'desc' => 'Tồn hoá chất thực tế. Mỗi chai / can 1 mã xuất nhập: điền "Số kiện" = số chai cùng thông tin.',
                'columns' => [
                    $dept,
                    ['key' => 'category', 'label' => 'Danh mục hoá chất', 'type' => 'ref', 'ref' => 'R_DM_HC', 'req' => true, 'width' => 50, 'hint' => 'Đã khai ở sheet 28 cho phòng này'],
                    ['key' => 'amount', 'label' => 'Lượng còn mỗi chai', 'type' => 'num', 'gt' => 0, 'req' => true, 'width' => 12, 'hint' => 'Theo ĐVT của phòng (sheet 28)'],
                    $packs,
                    ['key' => 'batch_no', 'label' => 'Số lô', 'type' => 'text', 'max' => 100, 'width' => 14, 'hint' => ''],
                    ['key' => 'supplier', 'label' => 'Nhà cung cấp', 'type' => 'ref', 'ref' => 'R_NCC', 'width' => 24, 'hint' => 'Chọn ở sheet 10'],
                    ['key' => 'invoice_number', 'label' => 'Số hoá đơn', 'type' => 'text', 'max' => 100, 'width' => 14, 'hint' => ''],
                    ['key' => 'invoice_date', 'label' => 'Ngày hoá đơn', 'type' => 'date', 'width' => 13, 'hint' => 'dd/mm/yyyy'],
                    ['key' => 'expired_date', 'label' => 'Hạn sử dụng (NSX)', 'type' => 'date', 'width' => 13, 'hint' => 'dd/mm/yyyy'],
                    ['key' => 'internal_expired_date', 'label' => 'Hạn dùng nội bộ (đã mở)', 'type' => 'date', 'width' => 13, 'hint' => 'Chỉ chai đã mở nắp'],
                    ['key' => 'is_microbiological_chemicals', 'label' => 'Hoá chất vi sinh', 'type' => 'list', 'list' => 'yes_no', 'width' => 11, 'hint' => 'Trống = Không'],
                    $stockLocation,
                    $note,
                ],
            ],
            'stock_standards' => [
                'title' => '33.Tồn đầu - Chất chuẩn',
                'table' => 'standard_imports',
                'group' => 'Tồn đầu kỳ',
                'desc' => 'Tồn chất chuẩn thực tế, mỗi ống 1 mã ống chuẩn (tự sinh: tên viết tắt phòng + nhóm + yymm + số thứ tự).',
                'columns' => [
                    $dept,
                    ['key' => 'category', 'label' => 'Danh mục chất chuẩn', 'type' => 'ref', 'ref' => 'R_DM_CC', 'req' => true, 'width' => 46, 'hint' => 'Đã khai ở sheet 29 cho phòng này'],
                    ['key' => 'group', 'label' => 'Nhóm chuẩn', 'type' => 'list', 'list' => 'standard_group', 'width' => 22, 'hint' => 'Trống = nhóm đầu tiên của DM'],
                    ['key' => 'amount', 'label' => 'Lượng còn mỗi ống', 'type' => 'num', 'gt' => 0, 'req' => true, 'width' => 12, 'hint' => 'Theo ĐVT của phòng (sheet 29)'],
                    $packs,
                    ['key' => 'expiry_type', 'label' => 'Kiểu hạn dùng', 'type' => 'list', 'list' => 'expiry_type', 'req' => true, 'width' => 24, 'hint' => 'Hạn dùng xác định / Check online...'],
                    ['key' => 'expired_date', 'label' => 'Hạn sử dụng', 'type' => 'date', 'width' => 13, 'hint' => 'Bắt buộc trừ kiểu Check online'],
                    ['key' => 'retest_interval_months', 'label' => 'Chu kỳ retest (tháng)', 'type' => 'int', 'min' => 1, 'maxValue' => 1200, 'width' => 12, 'hint' => 'Chỉ kiểu Retest'],
                    ['key' => 'internal_expired_date', 'label' => 'Hạn dùng nội bộ (đã mở)', 'type' => 'date', 'width' => 13, 'hint' => 'Chỉ ống đã mở'],
                    ['key' => 'batch_no', 'label' => 'Số lô', 'type' => 'text', 'max' => 100, 'width' => 14, 'hint' => ''],
                    ['key' => 'coa_no', 'label' => 'Số COA', 'type' => 'text', 'max' => 100, 'width' => 14, 'hint' => ''],
                    ['key' => 'potency', 'label' => 'Hàm lượng công bố', 'type' => 'text', 'max' => 100, 'width' => 14, 'hint' => 'VD: 99.8%'],
                    ['key' => 'moisture', 'label' => 'Độ ẩm', 'type' => 'text', 'max' => 100, 'width' => 10, 'hint' => ''],
                    ['key' => 'weight_controlled', 'label' => 'Kiểm soát khối lượng', 'type' => 'list', 'list' => 'yes_no', 'width' => 12, 'hint' => 'Trống = Không'],
                    ['key' => 'standard_form', 'label' => 'Dạng chuẩn', 'type' => 'list', 'list' => 'standard_form', 'width' => 14, 'hint' => 'Chỉ khi kiểm soát KL'],
                    ['key' => 'requires_aliquot', 'label' => 'Cần chia nhỏ', 'type' => 'list', 'list' => 'yes_no', 'width' => 11, 'hint' => 'Trống = Không'],
                    ['key' => 'supplier', 'label' => 'Nhà cung cấp', 'type' => 'ref', 'ref' => 'R_NCC', 'width' => 22, 'hint' => 'Chọn ở sheet 10'],
                    ['key' => 'purposes', 'label' => 'Mục đích sử dụng', 'type' => 'text', 'max' => 500, 'width' => 26, 'hint' => 'Tên ở sheet 11, cách nhau dấu ;'],
                    ['key' => 'invoice_number', 'label' => 'Số hoá đơn', 'type' => 'text', 'max' => 100, 'width' => 14, 'hint' => ''],
                    $stockLocation,
                    $note,
                ],
            ],
        ];
    }

    /**
     * Sheet tra cứu (chỉ đọc) lấy từ dữ liệu hệ thống đã seed sẵn, để khách hàng chọn mã.
     */
    public static function referenceSheets(): array
    {
        return [
            'ref_active_ingredients' => [
                'title' => 'TC.Hoạt chất (Bảng A)',
                'desc' => 'Danh mục hoạt chất Phụ lục IV Bảng A - NĐ 24/2026/NĐ-CP đã có sẵn trong hệ thống. Chỉ để tra mã, không sửa.',
                'columns' => [
                    ['label' => 'Mã', 'width' => 11],
                    ['label' => 'Tên hoạt chất', 'width' => 45],
                    ['label' => 'Tên tiếng Anh', 'width' => 40],
                    ['label' => 'Số CAS', 'width' => 16],
                    ['label' => 'Ngưỡng tồn trữ (kg)', 'width' => 14],
                ],
            ],
            'ref_hazard_categories' => [
                'title' => 'TC.Nhóm nguy hại (Bảng B)',
                'desc' => 'Danh mục nhóm nguy hại hỗn hợp Phụ lục IV Bảng B đã có sẵn trong hệ thống. Chỉ để tra mã, không sửa.',
                'columns' => [
                    ['label' => 'Mã', 'width' => 11],
                    ['label' => 'Nhóm', 'width' => 8],
                    ['label' => 'Tên nhóm nguy hại', 'width' => 80],
                    ['label' => 'Ngưỡng (kg)', 'width' => 12],
                ],
            ],
        ];
    }

    /** Số thứ tự lớn nhất của mã dạng <prefix><số> đang có trong bảng (0 nếu chưa có). */
    public static function maxCodeNumber(string $table, string $prefix): int
    {
        return (int) (DB::table($table)->where('code', 'like', $prefix.'%')->pluck('code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix)))
            ->max() ?? 0);
    }

    /** Sheet có trong file của module $module (null = file đủ mọi module). */
    public static function inModule(string $sheetKey, ?string $module): bool
    {
        return $module === null || ! isset(self::MODULE_SHEETS[$sheetKey]) || in_array($module, self::MODULE_SHEETS[$sheetKey], true);
    }

    /** Giai đoạn của một sheet (sheet không có trong STAGE_SHEETS thuộc giai đoạn 3). */
    public static function stageOf(string $sheetKey): int
    {
        return self::STAGE_SHEETS[$sheetKey] ?? 3;
    }

    /**
     * Sheet NHẬP được của file (module $module, giai đoạn $stage), giữ đúng thứ tự nhập.
     * null = không lọc theo tiêu chí đó.
     */
    public static function sheetsFor(?string $module, ?int $stage = null): array
    {
        return array_filter(
            self::sheets(),
            fn ($key) => self::inModule($key, $module) && ($stage === null || self::stageOf($key) === $stage),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Mọi sheet có trong file: [key => 'edit' | 'source'], theo thứ tự nhập. 'source' = sheet
     * giai đoạn trước mà sheet nhập được chọn tới (ô chọn tham chiếu) - điền sẵn từ DB, ẩn và khoá.
     */
    public static function fileSheets(?string $module, ?int $stage = null): array
    {
        $edit = self::sheetsFor($module, $stage);
        $refs = self::refs();
        $sources = [];

        foreach ($edit as $sheet) {
            foreach ($sheet['columns'] as $col) {
                if ($col['type'] === 'ref' && ! isset($edit[$refs[$col['ref']][0]])) {
                    $sources[$refs[$col['ref']][0]] = true;
                }
            }
        }

        $roles = [];
        foreach (array_keys(self::sheets() + self::referenceSheets()) as $key) {
            if (isset($edit[$key])) {
                $roles[$key] = 'edit';
            } elseif (isset($sources[$key])) {
                $roles[$key] = 'source';
            }
        }

        return $roles;
    }

    /** Sheet có cột Phòng ban: file của phòng ban chỉ được khai đúng phòng đó. */
    public static function hasDepartmentColumn(array $sheet): bool
    {
        return collect($sheet['columns'])->contains(fn ($col) => $col['key'] === 'department');
    }

    /** Chữ cái cột Excel của cột thứ $index (0 = A). */
    public static function letter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
    }

    /** Chữ cái cột của một khoá cột trong sheet dữ liệu; sheet tra cứu thì truyền chỉ số cột. */
    public static function columnLetter(string $sheetKey, string|int $column): string
    {
        if (is_int($column)) {
            return self::letter($column);
        }

        foreach (self::sheets()[$sheetKey]['columns'] as $index => $spec) {
            if ($spec['key'] === $column) {
                return self::letter($index);
            }
        }

        throw new \InvalidArgumentException("Không có cột {$column} trong sheet {$sheetKey}.");
    }

    /** Tên sheet Excel của một sheet key (sheet dữ liệu hoặc sheet tra cứu). */
    public static function sheetTitle(string $sheetKey): string
    {
        return (self::sheets() + self::referenceSheets())[$sheetKey]['title'];
    }

    /** Tiêu đề cột hiển thị ở dòng HEADER_ROW (cột bắt buộc có dấu *). */
    public static function headerLabel(array $column): string
    {
        return $column['label'].(! empty($column['req']) ? ' *' : '');
    }
}
