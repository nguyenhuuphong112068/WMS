<?php

namespace App\Support\InitialData;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\MaterialClassification;
use App\Support\StandardCode;
use App\Support\VppClassification;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * SINH FILE EXCEL MẪU KHỞI TẠO DỮ LIỆU
 *
 * Dựng toàn bộ file từ InitialDataSchema: sheet Hướng dẫn, các sheet dữ liệu (tiêu đề, mô tả,
 * tên cột, gợi ý, ô chọn liên kết giữa các sheet), sheet tra cứu hoạt chất / nhóm nguy hại lấy
 * từ DB và sheet ẩn chứa danh sách chọn cố định.
 *
 * Ô chọn tham chiếu dùng Name dạng OFFSET + COUNTA nên danh sách co giãn theo số dòng khách
 * hàng đã điền, không hiện hàng nghìn dòng trống.
 *
 * Dữ liệu gốc / danh mục / định khu đang có trong hệ thống được điền sẵn ở đầu từng sheet làm
 * nguồn chọn cho các sheet sau; các dòng này và cột Mã DM tự sinh bị khoá (bảo vệ sheet).
 */
class InitialDataTemplate
{
    // Bảng màu xanh dương nhạt của dự án (xem layout/css.blade.php)
    private const PRIMARY = '2E7BC4';

    private const PRIMARY_DARK = '1F5E9E';

    private const PRIMARY_LIGHT = '5AA0DE';

    private const PRIMARY_SOFT = 'EAF3FC';

    private const TEXT_MAIN = '2D3748';

    private const MUTED = '64748B';

    private const DANGER = 'DC2626';

    /** Nền dòng điền sẵn từ hệ thống (khoá, chỉ để chọn lại). */
    private const PREFILL_FILL = 'EEF2F6';

    /** Dòng cuối có công thức / ô chọn của từng sheet (= số dòng điền sẵn + MAX_ROWS). */
    private static array $lastRows = [];

    /** Số dòng điền sẵn của từng sheet dữ liệu. */
    private static array $prefillCounts = [];

    /** Phòng ban của file riêng (id, shortName); null = file toàn hệ thống. */
    private static ?object $department = null;

    /** Module của file riêng (material / vpp / chemical / standard); null = file đủ 4 module. */
    private static ?string $module = null;

    /** Giai đoạn của file riêng (InitialDataSchema::STAGES); null = file đủ 3 giai đoạn. */
    private static ?int $stage = null;

    /** Sheet có trong file: [key => 'edit' | 'source'] (xem InitialDataSchema::fileSheets). */
    private static array $roles = [];

    /** Màu tab theo nhóm sheet, để khách hàng nhìn thanh tab là biết đang ở phần nào. */
    private const GROUP_TAB_COLORS = [
        'Tổ chức' => '1F5E9E',
        'Dữ liệu gốc dùng chung' => '2E7BC4',
        'Tên vật tư / hoá chất / chất chuẩn' => '17B8D4',
        'Danh mục công ty' => '0F766E',
        'Định khu kho' => '7C3AED',
        'Khai báo hàng của phòng' => 'F59E0B',
        'Tồn đầu kỳ' => '16A34A',
    ];

    /**
     * @param  int|null  $departmentId  file riêng của một phòng ban (gửi song song cho nhiều phòng
     *                                  điền): chỉ điền sẵn + chỉ cho khai dữ liệu của phòng đó
     * @param  string|null  $module  file thu thập riêng một module (InitialDataSchema::MODULES):
     *                               chỉ gồm sheet dùng chung + sheet của module đó
     * @param  int|null  $stage  file thu thập riêng một giai đoạn (InitialDataSchema::STAGES): sheet
     *                           giai đoạn trước cần làm nguồn ô chọn được kèm theo dạng ẩn + khoá
     */
    public static function save(string $path, ?int $departmentId = null, ?string $module = null, ?int $stage = null): void
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $writer = new Xlsx(self::build($departmentId, $module, $stage));
        // Giữ nguyên công thức để Excel tự tính lúc mở, không tính trước bằng PHP
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
    }

    public static function build(?int $departmentId = null, ?string $module = null, ?int $stage = null): Spreadsheet
    {
        // Giai đoạn Tổ chức / Người dùng là một file chung cho mọi phòng ban: bỏ qua phòng ban, module
        $shared = in_array($stage, InitialDataSchema::SHARED_STAGES, true);
        $module = $shared ? null : $module;

        self::$module = $module;
        self::$stage = $stage;
        self::$roles = InitialDataSchema::fileSheets($module, $stage);
        self::$lastRows = [];
        self::$prefillCounts = [];
        // GĐ2 vẫn chia module chung nhưng nhận phạm vi phòng ban: điền sẵn tổ + người dùng của phòng đó
        self::$department = $departmentId && $stage !== 1
            ? DB::table('deparments')->where('id', $departmentId)->first(['id', 'shortName'])
            : null;

        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('WMS')
            ->setTitle('Khởi tạo dữ liệu ban đầu WMS')
            ->setSubject(InitialDataSchema::VERSION)
            ->setDescription('File mẫu tập hợp dữ liệu gốc, danh mục và tồn đầu kỳ để nạp vào hệ thống WMS.');

        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $guide = $book->getActiveSheet();
        $guide->setTitle(InitialDataSchema::GUIDE_SHEET);

        $lists = $book->createSheet();
        $lists->setTitle(InitialDataSchema::LIST_SHEET);
        self::buildListSheet($book, $lists);

        foreach (array_intersect_key(InitialDataSchema::sheets(), self::$roles) as $key => $sheet) {
            $ws = $book->createSheet();
            $ws->setTitle($sheet['title']);
            self::buildDataSheet($ws, $key, $sheet);
            if (self::hidden($key)) {
                $ws->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
            } elseif (self::$roles[$key] === 'extra') {
                // Sheet bổ sung (giai đoạn trước): tab xám để tách khỏi sheet chính của giai đoạn
                $ws->getTabColor()->setRGB(self::MUTED);
            }
        }

        foreach (self::referenceSheets() as $key => $sheet) {
            $ws = $book->createSheet();
            $ws->setTitle($sheet['title']);
            self::buildReferenceSheet($ws, $key, $sheet);
        }

        // Bộ nhập đọc số dòng điền sẵn để bỏ qua đúng các dòng đó
        $row = InitialDataSchema::PREFILL_CELL_ROW;
        $lists->setCellValue('A'.($row - 1), 'PREFILL');
        foreach (self::$prefillCounts as $key => $count) {
            $lists->setCellValueExplicit('A'.$row++, $key.':'.$count, DataType::TYPE_STRING);
        }
        if (self::$department) {
            $lists->setCellValueExplicit('A'.$row++, InitialDataSchema::SCOPE_KEY.':'.self::$department->shortName, DataType::TYPE_STRING);
        }
        if ($module) {
            $lists->setCellValueExplicit('A'.$row++, InitialDataSchema::MODULE_KEY.':'.$module, DataType::TYPE_STRING);
        }
        if ($stage) {
            $lists->setCellValueExplicit('A'.$row, InitialDataSchema::STAGE_KEY.':'.$stage, DataType::TYPE_STRING);
        }

        self::defineRefNames($book);

        // Sheet ẩn đã khoá toàn bộ, không cần ô chọn
        foreach (array_intersect_key(InitialDataSchema::sheets(), self::$roles) as $key => $sheet) {
            if (! self::hidden($key)) {
                self::addValidations($book->getSheetByName($sheet['title']), $key, $sheet);
            }
        }

        self::buildGuide($guide);

        // Sheet danh sách để cuối và ẩn hẳn, khách hàng không cần thấy
        $book->setIndexByName(InitialDataSchema::LIST_SHEET, $book->getSheetCount() - 1);
        $lists->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
        $book->setActiveSheetIndex(0);

        return $book;
    }

    /** Sheet chỉ Admin khai, trong file của phòng ban: ẩn + khoá. */
    private static function adminOnly(string $key): bool
    {
        return self::$department !== null && in_array($key, InitialDataSchema::ADMIN_ONLY_SHEETS, true);
    }

    /**
     * Sheet ẩn hẳn + khoá: sheet tổ chức chỉ làm nguồn ô chọn, hoặc sheet chỉ Admin khai trong
     * file của phòng ban. Sheet giai đoạn trước khác ('extra') vẫn hiện để bổ sung khi thiếu.
     */
    private static function hidden(string $key): bool
    {
        return (self::$roles[$key] ?? null) === 'source' || self::adminOnly($key);
    }

    /** Sheet nhập được và hiện ra trong file. */
    private static function visibleSheets(): array
    {
        return array_filter(
            array_intersect_key(InitialDataSchema::sheets(), self::$roles),
            fn ($key) => ! self::hidden($key),
            ARRAY_FILTER_USE_KEY
        );
    }

    /** Sheet tra cứu có trong file (theo module, giai đoạn). */
    private static function referenceSheets(): array
    {
        return array_intersect_key(InitialDataSchema::referenceSheets(), self::$roles);
    }

    // ------------------------------------------------------------------ SHEET HƯỚNG DẪN

    private static function buildGuide(Worksheet $ws): void
    {
        $ws->getTabColor()->setRGB(self::DANGER);
        $ws->setShowGridlines(false);
        $ws->getColumnDimension('A')->setWidth(6);
        $ws->getColumnDimension('B')->setWidth(30);
        $ws->getColumnDimension('C')->setWidth(30);
        $ws->getColumnDimension('D')->setWidth(95);

        $ws->setCellValue('A1', 'FILE KHỞI TẠO DỮ LIỆU BAN ĐẦU - HỆ THỐNG QUẢN LÝ VẬT TƯ TIÊU HAO (CMS)');
        $ws->mergeCells('A1:D1');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::PRIMARY);
        $ws->getRowDimension(1)->setRowHeight(28);

        $ws->setCellValue('A2', 'Phiên bản cấu trúc: '.InitialDataSchema::VERSION.'  ·  Ngày tạo file: '.now()->format('d/m/Y'));
        $ws->mergeCells('A2:D2');
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED);

        $visible = self::visibleSheets();
        $info = self::guideInfo();

        $row = 4;
        $row = self::guideHeading($ws, $row, 'A. FILE NÀY DÙNG ĐỂ LÀM GÌ');
        foreach ($info['about'] as [$label, $text]) {
            $row = self::guideField($ws, $row, $label, $text);
        }

        $row++;
        $row = self::guideHeading($ws, $row, 'B. CÁC BƯỚC ĐIỀN');
        $no = 1;
        $extra = array_filter($visible, fn ($key) => self::$roles[$key] === 'extra', ARRAY_FILTER_USE_KEY);
        foreach (array_diff_key($visible, $extra) as $key => $sheet) {
            $row = self::guideLine($ws, $row, 'B'.$no++, $sheet['title'].': '.(self::GUIDE_STEPS[$key] ?? $sheet['desc']));
        }
        if ($extra) {
            $row = self::guideLine($ws, $row, 'B'.$no++, 'Nếu thiếu dữ liệu giai đoạn trước (danh mục, vị trí, tên hàng...) thì bổ sung ngay ở các SHEET BỔ SUNG (tab màu xám, xem mục B+), không cần chờ cập nhật hệ thống.');
        }
        $row = self::guideLine($ws, $row, 'B'.$no, 'Lưu file, gửi lại cho Admin. Admin tổng hợp file của các phòng ban, làm sạch dữ liệu rồi nạp lên hệ thống.');

        if ($extra) {
            $row++;
            $row = self::guideHeading($ws, $row, 'B+. SHEET BỔ SUNG (CHỈ ĐIỀN KHI CÒN THIẾU)');
            $row = self::guideLine($ws, $row, '', 'Dòng nền xám là dữ liệu đã có trên hệ thống. Dòng mới thêm ở đây được nạp lên hệ thống cùng file và chọn được ngay ở các sheet chính. Điền theo thứ tự sheet từ trái sang phải.');
            foreach ($extra as $key => $sheet) {
                $row = self::guideLine($ws, $row, '+', $sheet['title'].': '.(self::GUIDE_STEPS[$key] ?? $sheet['desc']));
            }
        }

        $row++;
        $row = self::guideHeading($ws, $row, 'C. QUY TẮC CHUNG');
        foreach (self::commonRules($visible) as $i => $text) {
            $row = self::guideLine($ws, $row, ($i + 1).'.', $text);
        }

        if ($info['notes']) {
            $row++;
            $row = self::guideHeading($ws, $row, 'D. LƯU Ý RIÊNG CỦA FILE NÀY');
            foreach ($info['notes'] as $i => $text) {
                $row = self::guideLine($ws, $row, ($i + 1).'.', $text);
            }
        }

        $row++;
        $row = self::guideHeading($ws, $row, 'E. DANH SÁCH SHEET');
        $row = self::guideSheetList($ws, $row, $visible);

        $lists = InitialDataSchema::lists();
        $multi = [];
        $examples = [];
        if (isset($visible['chemical_cats'])) {
            $multi[] = 'Cảnh báo an toàn (sheet 20): '.implode('; ', array_keys($lists['safety_warning']));
            $examples[] = '"TOXIC, CORROSIVE"';
        }
        if (isset($visible['vpp_cats'])) {
            $multi[] = 'Nhóm dự trù VPP (sheet 34): '.implode('; ', array_keys($lists['vpp_group']));
        }
        if (isset($visible['standard_cats']) || isset($visible['stock_standards'])) {
            $multi[] = 'Phân nhóm chuẩn (sheet 21, 33): '.implode('; ', array_keys($lists['standard_group']));
            $examples[] = '"PRS, IMPRS"';
        }
        if (isset($visible['users'])) {
            foreach (array_keys(InitialDataSchema::ROLE_COLUMNS) as $column) {
                $label = collect(InitialDataSchema::sheets()['users']['columns'])->firstWhere('key', $column)['label'];
                $multi[] = $label.' (sheet 05): '.(implode('; ', array_keys($lists[$column])) ?: '(chưa có vai trò)');
            }
            $examples[] = '"Người dùng Hoá chất, Quản Lý Kho Hóa Chất"';
        }
        if ($multi) {
            $row++;
            $row = self::guideHeading($ws, $row, 'F. MÃ DÙNG CHO CỘT NHIỀU GIÁ TRỊ');
            foreach ($multi as $text) {
                $row = self::guideLine($ws, $row, '', $text);
            }
            $row = self::guideLine($ws, $row, '', 'Nhiều giá trị cách nhau dấu phẩy; danh sách dạng "MÃ - tên" chỉ cần ghi phần mã. VD: '.implode(' hoặc ', $examples).'.');
        }

        $ws->getStyle('A4:D'.$row)->getFont()->getColor()->setRGB(self::TEXT_MAIN);
        $ws->freezePane('A4');
    }

    /** Cách điền từng sheet, hiện ở mục "Các bước điền" theo đúng các sheet có trong file. */
    private const GUIDE_STEPS = [
        'companies' => 'khai công ty / nhà máy (tên viết tắt + tên đầy đủ). Mã công ty hệ thống tự sinh.',
        'departments' => 'khai phòng ban, chọn công ty. Tên viết tắt ngắn, không dấu cách (dùng làm tiền tố mã ống chuẩn). Phòng ban không có kho (BGĐ, Cung ứng...) chọn "Phòng ban chung" = Có.',
        'roles' => 'vai trò đang có đã điền sẵn; chỉ thêm vai trò mới khi cần. Quyền chi tiết của vai trò cấu hình sau trên màn hình Phân Quyền.',
        'groups' => 'xem các tổ đã có của phòng mình (nền xám), thêm tổ còn thiếu: chọn phòng ban + ghi tên tổ.',
        'users' => 'mỗi người một dòng: Mã số nhân viên (dùng đăng nhập), họ tên, phòng ban, email, tổ và phân quyền theo module (chung / Vật tư / VPP / Hoá chất / Chất chuẩn; ít nhất một vai trò, mỗi ô ghi được nhiều vai trò cách dấu phẩy).',
        'units' => 'đơn vị đã có điền sẵn; chỉ thêm đơn vị mới, khai nhóm đơn vị và hệ số quy về đơn vị gốc (g / ml / đơn vị).',
        'packaging' => 'thêm quy cách đóng gói còn thiếu.',
        'storage' => 'thêm điều kiện bảo quản còn thiếu (VD: Tủ lạnh 2-8°C).',
        'manufacturers' => 'thêm nhà sản xuất còn thiếu (tên viết tắt + tên đầy đủ, không trùng).',
        'suppliers' => 'thêm nhà cung cấp còn thiếu.',
        'purposes' => 'thêm mục đích sử dụng / chỉ tiêu kiểm nghiệm của chất chuẩn còn thiếu.',
        'products' => 'thêm tên thành phẩm còn thiếu.',
        'consumption' => 'thêm thiết bị / đối tượng tiêu hao vật tư định kỳ còn thiếu (loại, mã, tên, tần suất).',
        'dept_class' => 'thêm nhóm phân loại vật tư riêng của phòng (dùng ở sheet 27).',
        'material_names' => 'thêm tên vật tư / VPP chưa có (chỉ tên, KHÔNG kèm nhà sản xuất).',
        'chem_names' => 'thêm tên hoá chất chưa có (chỉ tên, KHÔNG kèm nhà sản xuất).',
        'chem_comp' => 'khai thành phần cho tên hoá chất mới: mỗi dòng 1 hoạt chất (tra mã ở sheet TC.Hoạt chất, kèm hàm lượng %) hoặc 1 nhóm nguy hại Bảng B.',
        'standard_names' => 'thêm tên chất chuẩn chưa có (kèm tên hoạt chất, số CAS nếu có).',
        'material_cats' => 'mỗi dòng = Tên vật tư + Nhà sản xuất + các tiêu chí phân loại. KHÔNG nhập Mã DM: điền tên là mã tự hiện.',
        'chemical_cats' => 'mỗi dòng = Tên hoá chất + Loại + Nhà sản xuất. KHÔNG nhập Mã DM: điền tên là mã tự hiện.',
        'vpp_cats' => 'mỗi dòng = Tên VPP (chọn ở sheet 15) + Nhà sản xuất + Đơn vị tính + Nhóm dự trù. KHÔNG nhập Mã DM: điền tên là mã tự hiện.',
        'standard_cats' => 'mỗi dòng = Tên chất chuẩn + Nguồn gốc / NSX + nhóm chuẩn. KHÔNG nhập Mã DM: điền tên là mã tự hiện.',
        'warehouses' => 'định khu cấp 1: kho / phòng của phòng ban. Mã không trùng toàn hệ thống.',
        'shelves' => 'định khu cấp 2: kệ / tủ, chọn mã kho.',
        'columns' => 'định khu cấp 3: cột, chọn mã kệ (kho tự suy theo kệ).',
        'tiers' => 'định khu cấp 4: tầng, chỉ cần chọn cấp cha gần nhất.',
        'locations' => 'định khu cấp 5: vị trí đặt hàng (nơi gắn tồn kho), chọn cấp cha gần nhất; loại lưu trữ để trống = dùng chung.',
        'dept_materials' => 'chọn danh mục vật tư phòng dùng, đơn vị tính của phòng, tồn tối thiểu / tối đa, vị trí mặc định.',
        'dept_vpp' => 'chọn danh mục VPP phòng dùng, tồn tối thiểu / tối đa, vị trí mặc định (đơn vị tính theo danh mục VPP).',
        'dept_chemicals' => 'chọn danh mục hoá chất phòng dùng, đơn vị tính của phòng, tồn tối thiểu / tối đa, vị trí mặc định.',
        'dept_standards' => 'chọn danh mục chất chuẩn phòng dùng, đơn vị tính của phòng, tồn tối thiểu / tối đa, vị trí mặc định.',
        'conversions' => 'chỉ điền khi cùng một mã hàng mà các phòng dùng đơn vị khác nhóm (VD: chai và ml): 1 [Từ đơn vị] = [Hệ số] [Sang đơn vị].',
        'stock_materials' => 'mỗi dòng 1 nhóm kiện giống hệt nhau: danh mục, số lượng mỗi kiện, số kiện, lô, hạn dùng, vị trí lưu.',
        'stock_vpp' => 'mỗi dòng 1 nhóm kiện giống hệt nhau: danh mục VPP, số lượng mỗi kiện, số kiện, lô, hạn dùng, vị trí lưu.',
        'stock_chemicals' => 'mỗi dòng 1 nhóm chai / can giống hệt nhau: danh mục, lượng còn mỗi chai, số chai, lô, hạn dùng, vị trí lưu.',
        'stock_standards' => 'mỗi dòng 1 nhóm ống giống hệt nhau: danh mục, nhóm chuẩn, lượng còn mỗi ống, số ống, kiểu hạn dùng, vị trí lưu.',
    ];

    /**
     * Phần mô tả file (mục đích, người điền, điều kiện, bước tiếp theo) + lưu ý riêng theo
     * giai đoạn / module / phòng ban của file.
     *
     * @return array{about: array<int, array{0: string, 1: string}>, notes: array<int, string>}
     */
    private static function guideInfo(): array
    {
        $stage = self::$stage;
        $label = $stage ? 'Giai đoạn '.$stage.' - '.InitialDataSchema::STAGES[$stage] : 'File đầy đủ (cả 4 giai đoạn)';
        if (self::$module) {
            $label .= ' · Module '.InitialDataSchema::MODULES[self::$module];
        }
        if (self::$department) {
            $label .= ' · Phòng '.self::$department->shortName;
        }
        $ownDepartment = self::$department ? 'Phòng '.self::$department->shortName.' - chỉ khai dữ liệu của phòng mình.' : null;

        $about = match ($stage) {
            1 => [
                ['Mục đích', 'Khai khung tổ chức dùng chung toàn hệ thống: công ty, phòng ban, vai trò.'],
                ['Người điền', 'Admin.'],
                ['Cần có trước', 'Không.'],
                ['Bước tiếp theo', 'Nạp xong thì tải file Giai đoạn 2 (Người dùng & Tổ - Nhóm) và Giai đoạn 3 (Dữ liệu gốc & danh mục) gửi các phòng ban - hai giai đoạn này làm song song được.'],
            ],
            2 => [
                ['Mục đích', 'Khai tài khoản người dùng của các phòng ban và bổ sung tổ - nhóm còn thiếu.'],
                ['Người điền', $ownDepartment
                    ? $ownDepartment.' Tổ và người dùng phòng đã có trên hệ thống được điền sẵn (dòng xám): người dùng sửa được thông tin / phân quyền, thêm người / tổ mới bên dưới.'
                    : 'Các phòng ban - MỘT file chung: mỗi phòng điền tổ và người dùng của phòng mình vào cùng file này.'],
                ['Cần có trước', 'Giai đoạn 1 đã nạp.'],
                ['Bước tiếp theo', 'Admin nạp file và nhập mật khẩu khởi tạo chung; người dùng đăng nhập bằng mã số nhân viên và phải đổi mật khẩu ở lần đầu.'],
            ],
            3 => [
                ['Mục đích', 'Khai dữ liệu gốc (đơn vị, nhà sản xuất, tên hàng, định khu kho...), danh mục công ty và hàng hoá phòng ban sử dụng.'],
                ['Người điền', $ownDepartment ?? 'Các phòng ban.'],
                ['Cần có trước', 'Giai đoạn 1 đã nạp.'],
                ['Bước tiếp theo', 'Nạp xong thì tải file Giai đoạn 4 (Tồn đầu kỳ) - file đó chỉ chọn được danh mục và vị trí đã nạp ở giai đoạn này.'],
            ],
            4 => [
                ['Mục đích', 'Nhập tồn kho thực tế tại thời điểm bắt đầu dùng hệ thống.'],
                ['Người điền', $ownDepartment ?? 'Các phòng ban có kho.'],
                ['Cần có trước', 'Giai đoạn 3 đã nạp: danh mục phòng đã khai (sheet 27 - 29, 35) và vị trí kho (sheet 26).'],
                ['Bước tiếp theo', 'Đây là giai đoạn cuối. Mỗi phòng ban chỉ nạp tồn đầu kỳ MỘT lần.'],
            ],
            default => [
                ['Mục đích', 'Khai toàn bộ dữ liệu ban đầu trong một file: tổ chức, người dùng, dữ liệu gốc, danh mục, định khu, tồn đầu kỳ.'],
                ['Người điền', $ownDepartment ?? 'Admin (tổng hợp từ các phòng ban).'],
                ['Cần có trước', 'Không.'],
                ['Bước tiếp theo', 'Nạp một lần. Khi nhiều phòng cùng điền, nên dùng file theo từng giai đoạn.'],
            ],
        };
        array_unshift($about, ['Loại file', $label]);
        if ($stage > 1) {
            $about[] = ['Dữ liệu sẵn có', self::hasExtra()
                ? 'Dữ liệu giai đoạn trước lấy theo hệ thống lúc tải file (dòng nền xám). Còn thiếu thì bổ sung trực tiếp vào các sheet bổ sung (tab màu xám) trong file này - dữ liệu bổ sung được nạp lên hệ thống cùng file.'
                : 'Dữ liệu giai đoạn trước (phòng ban, vai trò) lấy theo hệ thống lúc tải file; còn thiếu thì báo Admin.'];
        }

        $stockRules = [
            'Ghi tồn kho THỰC TẾ theo đơn vị tính của phòng đã khai cho danh mục đó (sheet 27 / 28 / 29 / 35). Chỉ nhập tồn được cho danh mục phòng đã khai.',
            '"Số kiện" = số kiện / chai / ống giống hệt nhau; mỗi kiện được cấp 1 mã xuất nhập riêng để in nhãn. VD: 3 chai Methanol 4L còn nguyên → Lượng mỗi chai = 4, Số kiện = 3.',
            'Chai / ống đã mở dở ghi riêng 1 dòng với lượng còn lại thực tế.',
            'Ngày nhập kho = ngày nạp file (không điền tay). Lô tồn đầu được ghi nhận ĐẠT kiểm tra và cộng ngay vào tồn kho.',
            'Vị trí lưu bắt buộc, phải là vị trí cùng phòng ban, loại lưu trữ trống hoặc đúng loại hàng.',
            'Mỗi phòng ban chỉ nạp tồn đầu kỳ MỘT lần; nạp lại bị chặn để tránh cộng trùng tồn.',
        ];
        $catKeys = ['material_cats' => 'M00001', 'vpp_cats' => 'P00001', 'chemical_cats' => 'C00001', 'standard_cats' => 'S00001'];
        $cats = array_intersect_key($catKeys, self::visibleSheets()) ?: $catKeys;
        $codeRule = 'Mã DM ('.self::titles(array_keys($cats)).') KHÔNG nhập tay: file tự sinh '.implode(' / ', $cats)
            .'... nối tiếp mã đang có khi điền tên; các sheet sau chọn lại theo mã này. Nhiều phòng điền song song có thể trùng mã trong file - lúc nạp hệ thống cấp mã thật theo thứ tự nạp và vẫn trỏ đúng danh mục đã khai.';
        $userRules = [
            'Mã số nhân viên dùng làm tên đăng nhập: 5 - 10 ký tự, không trùng. Mã đã có tài khoản thì giữ nguyên, không ghi đè.',
            'KHÔNG ghi mật khẩu vào file: Admin nhập mật khẩu khởi tạo chung lúc nạp.',
            'Tổ / nhóm phải thuộc đúng phòng ban của người dùng. Tổ mới thêm ở sheet 03 chọn được ngay ở sheet Người dùng.',
            'Không có email thì ghi NA.',
        ];

        $notes = match ($stage) {
            1 => ['Công ty / phòng ban / vai trò đã có trên hệ thống (nền xám) giữ nguyên, không sửa trong file. Tên viết tắt không trùng.'],
            2 => $userRules,
            3 => array_values(array_filter([
                self::hasVisible(['material_names', 'chem_names', 'standard_names'])
                    ? 'Thêm tên hàng mới ở '.self::titles(['material_names', 'chem_names', 'standard_names']).' trước, rồi mới tạo danh mục công ty ở '
                        .self::titles(['material_cats', 'vpp_cats', 'chemical_cats', 'standard_cats']).' từ tên đó.'
                    : null,
                self::hasVisible(['material_cats', 'vpp_cats', 'chemical_cats', 'standard_cats']) ? $codeRule : null,
                'Tên hàng / danh mục đã có (nền xám) thì chọn lại, không khai lại. Dòng mới trùng tổ hợp đã có sẽ được dùng lại bản cũ.',
                self::hasVisible(['warehouses']) ? 'Định khu khai từ cấp lớn đến nhỏ: Kho → Kệ / Tủ → Cột → Tầng → Vị trí.' : null,
            ])),
            4 => $stockRules,
            default => array_merge($userRules, [$codeRule], $stockRules),
        };

        return ['about' => $about, 'notes' => $notes];
    }

    /** Quy tắc nhập liệu chung cho mọi loại file. */
    private static function commonRules(array $visible): array
    {
        $hasPrefill = (bool) array_filter(array_intersect_key(self::$prefillCounts, $visible));

        return array_values(array_filter([
            'Điền theo đúng thứ tự các bước ở mục B: sheet sau chỉ chọn lại được dữ liệu đã khai ở sheet trước hoặc đã có trên hệ thống.',
            $hasPrefill
                ? 'Dòng NỀN XÁM ở đầu sheet là dữ liệu đang có trên hệ thống, đã khoá - chỉ để xem / chọn lại. Điền dữ liệu mới ở các dòng trắng ngay bên dưới.'
                : null,
            isset($visible['users'])
                ? 'Sheet 05 Người dùng: dòng nền xám là tài khoản đang có - SỬA ĐƯỢC họ tên, phòng ban, email, phân quyền, tổ (cột Mã số nhân viên khoá); khi nạp hệ thống cập nhật theo file. Người mới điền ở dòng trắng bên dưới.'
                : null,
            'Dòng 3 là tên cột, dòng 4 là gợi ý / ví dụ. KHÔNG đổi tên sheet, KHÔNG chèn / xoá / đổi thứ tự cột.',
            'Cột có dấu * (nền xanh đậm) là bắt buộc; cột còn lại để trống nếu không có thông tin.',
            'Điền liền mạch từ trên xuống, không bỏ dòng trống xen giữa (danh sách chọn ở sheet sau bị cắt tại dòng trống).',
            'Ô có mũi tên chọn: nên chọn từ danh sách thay vì gõ tay. Có thể dán (paste) giá trị nhưng phải trùng khớp chính xác.',
            'Ngày ghi dạng dd/mm/yyyy (VD: 31/12/2027). Số thập phân dùng dấu chấm hoặc theo định dạng số của Excel.',
            'Sheet không có dữ liệu mới thì để trống, hệ thống bỏ qua.',
        ]));
    }

    /** Tên các sheet đang hiện trong file, trong số $keys (VD: "15.Tên vật tư, 16.Tên hoá chất"). */
    private static function titles(array $keys): string
    {
        $titles = array_column(array_intersect_key(self::visibleSheets(), array_flip($keys)), 'title');

        return $titles ? implode(', ', $titles) : 'sheet '.implode(' / ', array_map(fn ($k) => strtok(InitialDataSchema::sheetTitle($k), '.'), $keys));
    }

    private static function hasExtra(): bool
    {
        return in_array('extra', self::$roles, true);
    }

    private static function hasVisible(array $keys): bool
    {
        return (bool) array_intersect_key(self::visibleSheets(), array_flip($keys));
    }

    /** Một dòng "Nhãn: nội dung" trong phần mô tả file. */
    private static function guideField(Worksheet $ws, int $row, string $label, string $text): int
    {
        $ws->setCellValue('A'.$row, $label);
        $ws->mergeCells('A'.$row.':B'.$row);
        $ws->setCellValue('C'.$row, $text);
        $ws->mergeCells('C'.$row.':D'.$row);
        $ws->getStyle('A'.$row)->getFont()->setBold(true)->getColor()->setRGB(self::PRIMARY_DARK);
        $ws->getStyle('A'.$row.':D'.$row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $ws->getRowDimension($row)->setRowHeight(15 * max(1, (int) ceil(mb_strlen($text) / 120)));

        return $row + 1;
    }

    private static function guideSheetList(Worksheet $ws, int $row, array $visible): int
    {
        foreach (['STT', 'Sheet', 'Nhóm', 'Nội dung'] as $i => $label) {
            $ws->setCellValue(InitialDataSchema::letter($i).$row, $label);
        }
        self::styleHeader($ws, 'A'.$row.':D'.$row, self::PRIMARY);
        $row++;

        $no = 1;
        $firstListRow = $row;
        foreach ($visible as $sheet) {
            $ws->setCellValue('A'.$row, $no++);
            $ws->setCellValue('B'.$row, $sheet['title']);
            $ws->getCell('B'.$row)->getHyperlink()->setUrl("sheet://'".$sheet['title']."'!A5");
            $ws->getStyle('B'.$row)->getFont()->setUnderline(true)->getColor()->setRGB(self::PRIMARY);
            $ws->setCellValue('C'.$row, $sheet['group']);
            $ws->setCellValue('D'.$row, $sheet['desc']);
            $ws->getStyle('A'.$row.':D'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $ws->getStyle('C'.$row)->getFont()->getColor()->setRGB(self::GROUP_TAB_COLORS[$sheet['group']] ?? self::TEXT_MAIN);
            $row++;
        }
        foreach (self::referenceSheets() as $sheet) {
            $ws->setCellValue('A'.$row, '—');
            $ws->setCellValue('B'.$row, $sheet['title']);
            $ws->setCellValue('C'.$row, 'Tra cứu (chỉ đọc)');
            $ws->setCellValue('D'.$row, $sheet['desc']);
            $ws->getStyle('A'.$row.':D'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $row++;
        }
        $ws->getStyle('A'.$firstListRow.':D'.($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D6E4F2');

        return $row;
    }

    private static function guideHeading(Worksheet $ws, int $row, string $text): int
    {
        $ws->setCellValue('A'.$row, $text);
        $ws->mergeCells('A'.$row.':D'.$row);
        $ws->getStyle('A'.$row)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB(self::PRIMARY_DARK);
        $ws->getStyle('A'.$row.':D'.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::PRIMARY_LIGHT);

        return $row + 1;
    }

    private static function guideLine(Worksheet $ws, int $row, string $no, string $text): int
    {
        $ws->setCellValue('A'.$row, $no);
        $ws->setCellValue('B'.$row, $text);
        $ws->mergeCells('B'.$row.':D'.$row);
        $ws->getStyle('A'.$row.':D'.$row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $ws->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        // Ô gộp không tự giãn chiều cao: ước lượng theo độ dài chữ trên bề rộng ~150 ký tự
        $ws->getRowDimension($row)->setRowHeight(15 * max(1, (int) ceil(mb_strlen($text) / 150)));

        return $row + 1;
    }

    // ------------------------------------------------------------------ SHEET DỮ LIỆU

    private static function buildDataSheet(Worksheet $ws, string $key, array $sheet): void
    {
        $columns = $sheet['columns'];
        $last = InitialDataSchema::letter(count($columns) - 1);
        $first = InitialDataSchema::FIRST_DATA_ROW;
        $prefill = self::prefill($key);
        $newRow = $first + count($prefill);
        $maxRow = $newRow + InitialDataSchema::MAX_ROWS - 1;
        $editable = InitialDataSchema::EDITABLE_PREFILL_SHEETS[$key] ?? null;
        // Sheet sửa được: bộ nạp đọc cả dòng điền sẵn, ô chọn / kiểm tra áp từ dòng đầu
        self::$prefillCounts[$key] = $editable === null ? count($prefill) : 0;
        self::$lastRows[$key] = $maxRow;

        $ws->getTabColor()->setRGB(self::GROUP_TAB_COLORS[$sheet['group']] ?? self::PRIMARY);

        $ws->setCellValue('A1', mb_strtoupper(preg_replace('/^\d+\./', '', $sheet['title'])));
        $ws->mergeCells('A1:'.$last.'1');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::PRIMARY);
        $ws->getRowDimension(1)->setRowHeight(22);

        $ws->setCellValue('A2', $sheet['desc']);
        $ws->mergeCells('A2:'.$last.'2');
        $ws->getStyle('A2')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED);
        $ws->getRowDimension(2)->setRowHeight(32);

        foreach ($columns as $i => $col) {
            $letter = InitialDataSchema::letter($i);

            // Định dạng gắn cho CẢ CỘT (một style của cột) thay vì từng ô 5.000 dòng - áp
            // trước khi ghi ô để ô tiêu đề / gợi ý vẫn giữ style riêng ghi đè lên sau đó.
            $columnFormat = match ($col['type']) {
                'int' => '0',
                'num' => '#,##0.####',
                'date' => 'dd/mm/yyyy',
                'calc', 'auto' => null,
                // Mã như "001" phải giữ nguyên số 0 đầu: ép định dạng chữ
                default => NumberFormat::FORMAT_TEXT,
            };
            if ($columnFormat) {
                $ws->getStyle($letter.':'.$letter)->getNumberFormat()->setFormatCode($columnFormat);
            }

            $ws->setCellValue($letter.InitialDataSchema::HEADER_ROW, InitialDataSchema::headerLabel($col));
            $ws->setCellValue($letter.InitialDataSchema::HINT_ROW, $col['hint'] ?? '');
            $ws->getColumnDimension($letter)->setWidth($col['width'] ?? 16);

            self::styleHeader(
                $ws,
                $letter.InitialDataSchema::HEADER_ROW,
                ! empty($col['req']) ? self::PRIMARY_DARK : (in_array($col['type'], ['calc', 'auto'], true) ? self::MUTED : self::PRIMARY_LIGHT)
            );

            if ($col['type'] === 'calc') {
                for ($r = $first; $r <= $maxRow; $r++) {
                    $ws->setCellValue($letter.$r, str_replace('{r}', (string) $r, $col['formula']));
                }
            }

            if ($col['type'] === 'auto') {
                // Mã mới = số lớn nhất đang có + thứ tự dòng đã điền tên (cột B) tính từ dòng mới đầu tiên
                $base = InitialDataSchema::maxCodeNumber($sheet['table'], $col['prefix']);
                for ($r = $newRow; $r <= $maxRow; $r++) {
                    $ws->setCellValue($letter.$r, '=IF(B'.$r.'="","","'.$col['prefix'].'"&TEXT('.$base.'+COUNTA(B$'.$newRow.':B'.$r.'),"00000"))');
                }
                $ws->getStyle($letter.$newRow.':'.$letter.$maxRow)->getFont()->setBold(true)->getColor()->setRGB(self::PRIMARY_DARK);
            }

            if (! empty($col['hidden'])) {
                $ws->getColumnDimension($letter)->setVisible(false);
            }
        }

        $hint = 'A'.InitialDataSchema::HINT_ROW.':'.$last.InitialDataSchema::HINT_ROW;
        $ws->getStyle($hint)->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB(self::MUTED);
        $ws->getStyle($hint)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::PRIMARY_SOFT);
        $ws->getStyle($hint)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $ws->getRowDimension(InitialDataSchema::HEADER_ROW)->setRowHeight(32);
        $ws->getRowDimension(InitialDataSchema::HINT_ROW)->setRowHeight(26);

        $ws->freezePane('B'.$first);
        $ws->setAutoFilter('A'.InitialDataSchema::HEADER_ROW.':'.$last.InitialDataSchema::HEADER_ROW);

        $rows = array_merge($prefill, $prefill ? [] : self::seedRows($key));
        foreach ($rows as $offset => $values) {
            foreach ($values as $i => $value) {
                if ($value === null || $value === '' || $columns[$i]['type'] === 'calc') {
                    continue;
                }
                $ws->setCellValueExplicit(
                    InitialDataSchema::letter($i).($first + $offset),
                    $value,
                    is_numeric($value) && in_array($columns[$i]['type'], ['num', 'int'], true)
                        ? DataType::TYPE_NUMERIC
                        : DataType::TYPE_STRING
                );
            }
        }

        self::protect($ws, $columns, $prefill ? $newRow - 1 : null, $last, self::hidden($key), $editable);
    }

    /**
     * Khoá dòng điền sẵn từ hệ thống + cột Mã DM tự sinh / cột công thức. Vùng nhập mới mở khoá
     * theo style của cả cột (không tạo hàng nghìn ô). $readOnly = khoá cả sheet (sheet chỉ Admin
     * khai, trong file của phòng ban).
     */
    private static function protect(Worksheet $ws, array $columns, ?int $lastPrefillRow, string $last, bool $readOnly = false, ?array $editableKeys = null): void
    {
        $hasAuto = collect($columns)->contains(fn ($c) => $c['type'] === 'auto');
        if ($lastPrefillRow === null && ! $hasAuto && ! $readOnly) {
            return;
        }

        foreach ($columns as $i => $col) {
            if (! $readOnly && ! in_array($col['type'], ['calc', 'auto'], true)) {
                $letter = InitialDataSchema::letter($i);
                $ws->getStyle($letter.':'.$letter)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
            }
        }

        // Tiêu đề, gợi ý và dòng điền sẵn khoá lại (ghi đè style cột); sheet sửa được chỉ khoá cột khoá
        $ws->getStyle('A1:'.$last.($editableKeys === null ? ($lastPrefillRow ?? InitialDataSchema::HINT_ROW) : InitialDataSchema::HINT_ROW))
            ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        if ($editableKeys !== null && $lastPrefillRow !== null) {
            foreach ($columns as $i => $col) {
                if (in_array($col['key'], $editableKeys, true)) {
                    $letter = InitialDataSchema::letter($i);
                    $ws->getStyle($letter.InitialDataSchema::FIRST_DATA_ROW.':'.$letter.$lastPrefillRow)
                        ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                }
            }
        }

        if ($lastPrefillRow !== null) {
            $range = 'A'.InitialDataSchema::FIRST_DATA_ROW.':'.$last.$lastPrefillRow;
            $ws->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::PREFILL_FILL);
            // Dòng sửa được giữ chữ thường để phân biệt với dòng khoá (chữ xám)
            if ($editableKeys === null) {
                $ws->getStyle($range)->getFont()->getColor()->setRGB(self::MUTED);
            }
        }

        // Vẫn cho lọc, chỉnh độ rộng cột / cao dòng và xoá dòng mới điền nhầm
        $ws->getProtection()
            ->setSheet(true)
            ->setAutoFilter(false)
            ->setSort(false)
            ->setFormatColumns(false)
            ->setFormatRows(false)
            ->setDeleteRows(false);
    }

    private static function styleHeader(Worksheet $ws, string $range, string $fill): void
    {
        $style = $ws->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fill);
        $style->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('FFFFFF');
    }

    /**
     * Dữ liệu điền sẵn: dữ liệu gốc / danh mục / định khu hệ thống đang có, làm nguồn chọn
     * cho các sheet sau. Bộ nhập bỏ qua các dòng này (đã có trong DB).
     *
     * Riêng "Đơn vị tính" khi DB còn trống thì ghi bộ đơn vị chuẩn vào vùng nhập mới (chưa có
     * trong DB nên bộ nhập phải nạp), xem seedRows().
     *
     * @return array<int, array<int, string|null>> dòng => [giá trị theo thứ tự cột]
     */
    private static function prefill(string $key): array
    {
        $table = InitialDataSchema::sheets()[$key]['table'];
        if (! Schema::hasTable($table)) {
            return [];
        }

        $lists = InitialDataSchema::lists();
        $label = fn (string $list, $value) => array_flip($lists[$list])[$value] ?? null;
        $dept = fn () => DB::table('deparments')->pluck('shortName', 'id');
        $code = fn (string $zoneTable) => DB::table($zoneTable)->pluck('code', 'id');
        $makers = fn () => DB::table('manufacturers')->pluck('short_name', 'id');
        $storages = fn () => DB::table('storage_conditions')->pluck('name', 'id');

        $rows = match ($key) {
            'companies' => self::active($table)->orderBy('code')->get()
                ->map(fn ($r) => [$r->short_name, $r->name]),
            'departments' => (function () use ($table) {
                $companies = DB::table('companies')->pluck('short_name', 'id');

                return DB::table($table)->where('isActive', 1)->orderBy('id')->get()
                    ->map(fn ($r) => [$r->shortName, $r->name, $companies[$r->company_id] ?? null, $r->EngshortName, (int) $r->is_general === 0 ? 'Có' : 'Không']);
            })(),
            'groups', 'dept_class' => (function () use ($table, $dept) {
                $depts = $dept();

                return self::ownDepartment(self::active($table))->orderBy('department_id')->orderBy('name')->get()
                    ->map(fn ($r) => [$depts[$r->department_id] ?? null, $r->name]);
            })(),
            'users' => self::prefillUsers($dept()),
            'roles' => DB::table($table)->orderBy('id')->get()
                ->map(fn ($r) => [$r->name, $r->description]),
            'units' => self::active($table)->orderBy('unit_group')->orderBy('factor_to_base')->get()
                ->map(fn ($r) => [$r->short_name, $r->name, $label('unit_group', $r->unit_group), self::number($r->factor_to_base)]),
            'packaging', 'purposes', 'products', 'material_names', 'chem_names' => self::active($table)->orderBy('name')->get()
                ->map(fn ($r) => [$r->name]),
            'storage' => self::active($table)->orderBy('name')->get()
                ->map(fn ($r) => [$r->name, $r->note]),
            'manufacturers' => self::active($table)->orderBy('short_name')->get()
                ->map(fn ($r) => [$r->short_name, $r->name]),
            'suppliers' => self::active($table)->orderBy('name')->get()
                ->map(fn ($r) => [$r->name, $r->address, $r->tax_no, $r->note]),
            'consumption' => self::active($table)->orderBy('type')->orderBy('code')->get()
                ->map(fn ($r) => [$label('consumption_type', $r->type), $r->code, $r->name, $r->location, $label('frequency', $r->frequency)]),
            'standard_names' => self::active($table)->orderBy('name')->get()
                ->map(fn ($r) => [$r->name, $r->active_ingredient_name, $r->cas_no]),
            'material_cats' => (function () use ($table, $makers, $label) {
                $names = DB::table('material_names')->pluck('name', 'id');
                $makerNames = $makers();

                return self::active($table)->orderBy('code')->get()->map(function ($r) use ($names, $makerNames, $label) {
                    $cls = MaterialClassification::decode($r->classification);
                    $criterion = fn (string $key) => isset($cls[$key]) ? $label('cls_'.$key, $cls[$key]) : null;

                    return [
                        $r->code, $names[$r->material_names_id] ?? null, $makerNames[$r->manufacturers_id] ?? null, $r->technical_specification,
                        $criterion('price'), $criterion('importance'), $criterion('dangerous'),
                        $label('purchasing', $r->purchasing_department), $criterion('qa_calibration'), $r->lead_time_days, null,
                    ];
                });
            })(),
            'vpp_cats' => (function () use ($table, $makers, $label) {
                $names = DB::table('material_names')->pluck('name', 'id');
                $makerNames = $makers();
                $unitNames = DB::table('units')->pluck('short_name', 'id');

                return self::active($table)->orderBy('code')->get()->map(function ($r) use ($names, $makerNames, $unitNames, $label) {
                    $group = VppClassification::decode($r->classification)[VppClassification::GROUP_KEY] ?? null;

                    return [
                        $r->code, $names[$r->material_names_id] ?? null, $makerNames[$r->manufacturers_id] ?? null,
                        $unitNames[$r->unit_id] ?? null, $group !== null ? $label('vpp_group', $group) : null,
                        $label('purchasing', $r->purchasing_department), null,
                    ];
                });
            })(),
            'chemical_cats' => (function () use ($table, $makers, $storages) {
                $names = DB::table('chem_names')->pluck('name', 'id');
                $makerNames = $makers();
                $storageNames = $storages();

                return self::active($table)->orderBy('code')->get()->map(fn ($r) => [
                    $r->code, $names[$r->chem_names_id] ?? null, $r->type, $makerNames[$r->manufacturers_id] ?? null,
                    self::numberOrNull($r->density), self::numberOrNull($r->ai_content_percent), $r->shelf_life_months,
                    $storageNames[$r->storage_condition_id] ?? null, $r->doc_no,
                    implode(', ', (array) json_decode((string) $r->safety_warning, true)), $r->lead_time_days, null,
                ]);
            })(),
            'standard_cats' => (function () use ($table, $makers, $storages) {
                // standard_categories.chem_names_id trỏ tới standard_names (tên cột giữ từ bản cũ)
                $names = DB::table('standard_names')->pluck('name', 'id');
                $makerNames = $makers();
                $storageNames = $storages();

                return self::active($table)->orderBy('code')->get()->map(fn ($r) => [
                    $r->code, $names[$r->chem_names_id] ?? null, $makerNames[$r->manufacturers_id] ?? null, $r->cas_no,
                    self::numberOrNull($r->density), $storageNames[$r->storage_condition_id] ?? null,
                    implode(', ', StandardCode::decodeGroups($r->groups)), $r->shelf_life_months, $r->doc_no, $r->note,
                    $r->lead_time_days, null,
                ]);
            })(),
            'warehouses', 'shelves', 'columns', 'tiers', 'locations' => (function () use ($key, $table, $dept, $code, $label) {
                $depts = $dept();
                $parents = [];
                foreach (['warehouse' => 'warehouses', 'shelf' => 'shelves', 'column' => 'columns', 'tier' => 'tiers'] as $level => $zoneTable) {
                    $parents[$level] = $zoneTable === $table ? collect() : $code($zoneTable);
                }

                return self::ownDepartment(self::active($table))->orderBy('department_id')->orderBy('code')->get()
                    ->map(function ($r) use ($key, $depts, $parents, $label) {
                        $parentCodes = [];
                        foreach (['warehouse', 'shelf', 'column', 'tier'] as $level) {
                            if (property_exists($r, $level.'_id')) {
                                $parentCodes[] = $parents[$level][$r->{$level.'_id'}] ?? null;
                            }
                        }
                        $zone = [$label('zone_type', $r->zone_type), $r->color];

                        return $key === 'locations'
                            ? array_merge([$depts[$r->department_id] ?? null, $r->code], $parentCodes, [$label('item_type', $r->item_type)], $zone)
                            : array_merge([$depts[$r->department_id] ?? null, $r->code, $r->name], $parentCodes, $zone);
                    });
            })(),
            default => collect(),
        };

        return $rows->map(fn ($row) => array_map(fn ($v) => $v === null ? null : (string) $v, $row))->all();
    }

    /**
     * Người dùng đang hoạt động (của phòng nếu là file phòng ban): vai trò xếp vào đúng cột Phân quyền
     * theo roleLists() (vai trò chính đứng đầu), tổ = tổ chính.
     *
     * @param  \Illuminate\Support\Collection  $depts  id => shortName
     */
    private static function prefillUsers($depts): \Illuminate\Support\Collection
    {
        $users = DB::table('user_management')->where('isActive', 1)
            ->when(self::$department, fn ($q) => $q->where('deparment_id', self::$department->id))
            ->orderBy('deparment_id')->orderBy('userName')->get();
        if ($users->isEmpty()) {
            return collect();
        }

        $roleNames = DB::table('roles')->pluck('name', 'id');
        $groupNames = DB::table('groups')->pluck('name', 'id');
        $userRoles = DB::table('user_role')->whereIn('user_id', $users->pluck('id'))->orderBy('id')->get()->groupBy('user_id');
        // Cột đầu tiên chứa vai trò (vai trò nhiều loại phụ trách chỉ ghi một lần)
        $columnOf = [];
        foreach (InitialDataSchema::roleLists() as $column => $names) {
            foreach ($names as $name) {
                $columnOf[$name] ??= $column;
            }
        }

        return $users->map(function ($u) use ($depts, $roleNames, $groupNames, $userRoles, $columnOf) {
            $roles = collect([$u->role_id])->merge(($userRoles[$u->id] ?? collect())->pluck('role_id'))
                ->filter()->unique()->map(fn ($id) => $roleNames[$id] ?? null)->filter();
            $cells = array_fill_keys(array_keys(InitialDataSchema::ROLE_COLUMNS), []);
            foreach ($roles as $name) {
                $cells[$columnOf[$name] ?? 'role_general'][] = $name;
            }

            return array_merge(
                [$u->userName, $u->fullName, $depts[$u->deparment_id] ?? null, $u->mail ?: 'NA'],
                array_map(fn ($names) => $names ? implode(', ', $names) : null, array_values($cells)),
                [$groupNames[$u->group_id] ?? null],
            );
        });
    }

    /** Bản ghi đang sử dụng (và đã duyệt nếu bảng có luồng duyệt). */
    private static function active(string $table): \Illuminate\Database\Query\Builder
    {
        return DB::table($table)
            ->where('status_id', 1)
            ->when(Schema::hasColumn($table, 'app_status'), fn ($q) => $q->where('app_status', 'approved'));
    }

    /** File của phòng ban: dữ liệu theo phòng (tổ, phân loại, định khu) chỉ lấy của phòng đó. */
    private static function ownDepartment(\Illuminate\Database\Query\Builder $query): \Illuminate\Database\Query\Builder
    {
        return $query->when(self::$department, fn ($q) => $q->where('department_id', self::$department->id));
    }

    private static function numberOrNull($value): ?string
    {
        return $value === null ? null : self::number($value);
    }

    /** Dòng gợi ý ghi sẵn vào vùng nhập mới khi sheet chưa có dữ liệu hệ thống. */
    private static function seedRows(string $key): array
    {
        if ($key !== 'units') {
            return [];
        }

        $groupLabels = array_flip(InitialDataSchema::lists()['unit_group']);

        return array_map(fn ($u) => [$u[0], $u[1], $groupLabels[$u[2]], self::number($u[3])], self::defaultUnits());
    }

    /** Bộ đơn vị chuẩn khi DB chưa có đơn vị nào - trùng với database/seeders/UnitSeeder. */
    private static function defaultUnits(): array
    {
        return [
            ['µg', 'Vi-crô-gam', 'mass', 0.000001], ['mg', 'Mi-li-gam', 'mass', 0.001], ['g', 'Gam', 'mass', 1],
            ['kg', 'Ki-lô-gam', 'mass', 1000], ['tấn', 'Tấn', 'mass', 1000000],
            ['µl', 'Vi-crô-lít', 'volume', 0.001], ['ml', 'Mi-li-lít', 'volume', 1], ['L', 'Lít', 'volume', 1000],
            ['cái', 'Cái', 'count', 1], ['bộ', 'Bộ', 'count', 1], ['đôi', 'Đôi', 'count', 1], ['chai', 'Chai', 'count', 1],
            ['lọ', 'Lọ', 'count', 1], ['ống', 'Ống', 'count', 1], ['can', 'Can', 'count', 1], ['hộp', 'Hộp', 'count', 1],
            ['thùng', 'Thùng', 'count', 1], ['bao', 'Bao', 'count', 1], ['túi', 'Túi', 'count', 1], ['gói', 'Gói', 'count', 1],
            ['cuộn', 'Cuộn', 'count', 1], ['tờ', 'Tờ', 'count', 1], ['m', 'Mét', 'count', 1],
        ];
    }

    private static function number($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
    }

    // ------------------------------------------------------------------ SHEET TRA CỨU

    private static function buildReferenceSheet(Worksheet $ws, string $key, array $sheet): void
    {
        $columns = $sheet['columns'];
        $last = InitialDataSchema::letter(count($columns) - 1);
        $first = InitialDataSchema::FIRST_DATA_ROW;

        $ws->getTabColor()->setRGB(self::MUTED);
        $ws->setCellValue('A1', mb_strtoupper(preg_replace('/^TC\./', 'Tra cứu: ', $sheet['title'])));
        $ws->mergeCells('A1:'.$last.'1');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::PRIMARY);
        $ws->setCellValue('A2', $sheet['desc']);
        $ws->mergeCells('A2:'.$last.'2');
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED);

        foreach ($columns as $i => $col) {
            $letter = InitialDataSchema::letter($i);
            $ws->setCellValue($letter.InitialDataSchema::HEADER_ROW, $col['label']);
            $ws->getColumnDimension($letter)->setWidth($col['width']);
        }
        self::styleHeader($ws, 'A'.InitialDataSchema::HEADER_ROW.':'.$last.InitialDataSchema::HEADER_ROW, self::MUTED);

        $rows = match ($key) {
            'ref_active_ingredients' => Schema::hasTable('active_ingredients')
                ? DB::table('active_ingredients')->where('status_id', 1)->where('app_status', 'approved')->orderBy('code')
                    ->get()->map(fn ($r) => [$r->code, $r->name, $r->name_en, $r->cas_no, $r->threshold_kg !== null ? self::number($r->threshold_kg) : ''])->all()
                : [],
            'ref_hazard_categories' => Schema::hasTable('mixture_hazard_categories')
                ? DB::table('mixture_hazard_categories')->where('status_id', 1)->where('app_status', 'approved')->orderBy('code')
                    ->get()->map(fn ($r) => [$r->code, $r->hazard_group, str_replace("\n", ' ', $r->name), self::number($r->threshold_kg)])->all()
                : [],
            default => [],
        };

        foreach ($rows as $offset => $values) {
            foreach ($values as $i => $value) {
                $ws->setCellValueExplicit(InitialDataSchema::letter($i).($first + $offset), (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $ws->freezePane('A'.$first);
        $ws->setAutoFilter('A'.InitialDataSchema::HEADER_ROW.':'.$last.max(InitialDataSchema::HEADER_ROW, $first + count($rows) - 1));
        $ws->getProtection()->setSheet(true)->setAutoFilter(false)->setSort(false);
    }

    // ------------------------------------------------------------------ DANH SÁCH CHỌN

    private static function buildListSheet(Spreadsheet $book, Worksheet $ws): void
    {
        $ws->setCellValue('A1', 'VERSION');
        $ws->setCellValue('A2', InitialDataSchema::VERSION);

        $index = 1;
        foreach (InitialDataSchema::lists() as $name => $items) {
            $letter = InitialDataSchema::letter($index++);
            $ws->setCellValue($letter.'1', $name);

            $row = 2;
            foreach (array_keys($items) as $label) {
                $ws->setCellValueExplicit($letter.$row++, (string) $label, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }

            $book->addNamedRange(new NamedRange(
                self::listName($name),
                $ws,
                '$'.$letter.'$2:$'.$letter.'$'.max(2, $row - 1)
            ));
        }
    }

    private static function listName(string $list): string
    {
        return 'L_'.strtoupper($list);
    }

    /**
     * Name cho các vùng chọn tham chiếu. Dùng OFFSET để danh sách chỉ dài đúng số dòng đã
     * điền; cột công thức (hiển thị "mã | tên") trả "" ở dòng trống nên đếm bằng COUNTIF "?*".
     */
    private static function defineRefNames(Spreadsheet $book): void
    {
        $sheets = InitialDataSchema::sheets();
        $first = InitialDataSchema::FIRST_DATA_ROW;

        foreach (InitialDataSchema::refs() as $name => [$sheetKey, $column]) {
            // Sheet nguồn không có trong file (khác module / giai đoạn): không sheet nào trong file chọn tới nó
            if (! isset(self::$roles[$sheetKey])) {
                continue;
            }
            $title = InitialDataSchema::sheetTitle($sheetKey);
            $letter = InitialDataSchema::columnLetter($sheetKey, $column);
            $isCalc = is_string($column) && collect($sheets[$sheetKey]['columns'])->firstWhere('key', $column)['type'] === 'calc';

            $quoted = "'".str_replace("'", "''", $title)."'";
            $max = self::$lastRows[$sheetKey] ?? $first + InitialDataSchema::MAX_ROWS - 1;
            $span = $quoted.'!$'.$letter.'$'.$first.':$'.$letter.'$'.$max;
            $count = $isCalc ? 'COUNTIF('.$span.',"?*")' : 'COUNTA('.$span.')';

            $book->addDefinedName(\PhpOffice\PhpSpreadsheet\DefinedName::createInstance(
                $name,
                null,
                '=OFFSET('.$quoted.'!$'.$letter.'$'.$first.',0,0,MAX(1,'.$count.'),1)'
            ));
        }
    }

    private static function addValidations(Worksheet $ws, string $key, array $sheet): void
    {
        // Ô chọn chỉ gắn cho vùng nhập mới, dòng điền sẵn đã khoá
        $first = InitialDataSchema::FIRST_DATA_ROW + self::$prefillCounts[$key];
        $max = self::$lastRows[$key];

        foreach ($sheet['columns'] as $i => $col) {
            $letter = InitialDataSchema::letter($i);
            $range = $letter.$first.':'.$letter.$max;
            $label = $col['label'];

            // File của phòng ban: cột Phòng ban chỉ chọn được đúng phòng đó
            if (self::$department && $col['key'] === 'department') {
                $ws->setDataValidation($range, self::validation(DataValidation::TYPE_LIST, '"'.str_replace(['"', ','], '', self::$department->shortName).'"', $label));

                continue;
            }

            $validation = match ($col['type']) {
                'list' => self::validation(DataValidation::TYPE_LIST, '='.self::listName($col['list']), $label, ! empty($col['free'])),
                'ref' => self::validation(DataValidation::TYPE_LIST, '='.$col['ref'], $label),
                // Ô nhiều giá trị: chọn một giá trị trong danh sách hoặc gõ nhiều giá trị cách dấu phẩy
                'multi' => self::validation(DataValidation::TYPE_LIST, '='.self::listName($col['list']), $label, true),
                'int' => self::numberValidation(DataValidation::TYPE_WHOLE, $col, $label),
                'num' => self::numberValidation(DataValidation::TYPE_DECIMAL, $col, $label),
                'date' => self::dateValidation($label),
                'text' => isset($col['max']) ? self::lengthValidation($col, $label) : null,
                default => null,
            };

            if ($validation) {
                $ws->setDataValidation($range, $validation);
            }
        }
    }

    private static function validation(string $type, string $formula, string $label, bool $free = false): DataValidation
    {
        $v = new DataValidation();
        $v->setType($type);
        $v->setAllowBlank(true);
        $v->setShowDropDown(true);
        $v->setShowErrorMessage(true);
        // Cột "free" (Loại hoá chất) chỉ gợi ý, vẫn cho gõ giá trị khác
        $v->setErrorStyle($free ? DataValidation::STYLE_INFORMATION : DataValidation::STYLE_STOP);
        $v->setErrorTitle($label);
        $v->setError('Vui lòng chọn giá trị trong danh sách.');
        $v->setFormula1($formula);

        return $v;
    }

    private static function numberValidation(string $type, array $col, string $label): ?DataValidation
    {
        $min = $col['gt'] ?? $col['min'] ?? null;
        $maxValue = $col['maxValue'] ?? null;

        if ($min === null && $maxValue === null) {
            return null;
        }

        $v = new DataValidation();
        $v->setType($type);
        $v->setAllowBlank(true);
        $v->setShowErrorMessage(true);
        $v->setErrorStyle(DataValidation::STYLE_STOP);
        $v->setErrorTitle($label);

        if (isset($col['gt'])) {
            $v->setOperator(DataValidation::OPERATOR_GREATERTHAN);
            $v->setFormula1((string) $col['gt']);
            $v->setError('Giá trị phải lớn hơn '.$col['gt'].($maxValue !== null ? ' (tối đa '.$maxValue.')' : '').'.');
        } elseif ($maxValue !== null) {
            $v->setOperator(DataValidation::OPERATOR_BETWEEN);
            $v->setFormula1((string) ($min ?? 0));
            $v->setFormula2((string) $maxValue);
            $v->setError('Giá trị phải từ '.($min ?? 0).' đến '.$maxValue.'.');
        } else {
            $v->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
            $v->setFormula1((string) $min);
            $v->setError('Giá trị phải từ '.$min.' trở lên.');
        }

        return $v;
    }

    private static function dateValidation(string $label): DataValidation
    {
        $v = new DataValidation();
        $v->setType(DataValidation::TYPE_DATE);
        $v->setOperator(DataValidation::OPERATOR_BETWEEN);
        // 01/01/2000 - 31/12/2100 theo số ngày của Excel
        $v->setFormula1('36526');
        $v->setFormula2('73415');
        $v->setAllowBlank(true);
        $v->setShowErrorMessage(true);
        $v->setErrorStyle(DataValidation::STYLE_STOP);
        $v->setErrorTitle($label);
        $v->setError('Vui lòng nhập ngày hợp lệ dạng dd/mm/yyyy.');

        return $v;
    }

    private static function lengthValidation(array $col, string $label): DataValidation
    {
        $v = new DataValidation();
        $v->setType(DataValidation::TYPE_TEXTLENGTH);
        $v->setOperator(DataValidation::OPERATOR_LESSTHANOREQUAL);
        $v->setFormula1((string) $col['max']);
        $v->setAllowBlank(true);
        $v->setShowErrorMessage(true);
        $v->setErrorStyle(DataValidation::STYLE_STOP);
        $v->setErrorTitle($label);
        $v->setError('Tối đa '.$col['max'].' ký tự.');

        return $v;
    }
}
