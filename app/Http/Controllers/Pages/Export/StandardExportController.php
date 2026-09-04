<?php

namespace App\Http\Controllers\Pages\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\DepartmentStandard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * SỬ DỤNG - SỬ DỤNG CHẤT CHUẨN
 *
 * Ghi nhận từng lần lấy chất chuẩn ra khỏi kho từ một ống chuẩn cụ thể:
 * sử dụng cho phép thử (type = export) hoặc huỷ bỏ (type = cancel).
 *
 * Mã ống chuẩn không sinh mới - lấy đúng mã của phiếu nhập được xuất ra.
 * Phiếu chỉ khoá (deActive) chứ không xoá cứng; phiếu đã khoá không trừ tồn.
 *
 * Ba quy tắc của nghiệp vụ xuất chất chuẩn:
 * - Chỉ được CHỌN ống còn hạn sử dụng và còn tồn > 0.
 * - Chất chuẩn có khai hạn dùng mặc định thì phải xác định HẠN DÙNG NỘI BỘ (hạn sau
 *   khi mở ống) ở màn hình Tồn Kho trước khi dùng.
 * - Được XUẤT VƯỢT tồn tối đa 5% để bù sai số cân đong. Phần vượt làm tồn bị âm,
 *   xử lý bằng chức năng Cân Đối ở màn hình Tồn Kho Chất Chuẩn.
 */
class StandardExportController extends Controller
{
    private const TABLE = 'standard_exports';

    private const HISTORY_TABLE = 'standard_export_histories';

    private const LABEL = 'phiếu sử dụng chất chuẩn';

    /** Các trường được theo dõi thay đổi, dùng làm nhãn trong lịch sử điều chỉnh. */
    private const FIELDS = [
        'code' => 'Mã ống chuẩn',
        'amount' => 'Số lượng',
        'type' => 'Loại phiếu',
        'product_name' => 'Tên sản phẩm',
        'batch_no' => 'Số lô',
        'testing' => 'Chỉ tiêu',
        'reason' => 'Lý do loại bỏ',
    ];

    /** Sai số cho phép khi so số lượng xuất với tồn (cột decimal 15,4). */
    private const EPSILON = 0.00005;

    /** Được xuất vượt tồn còn lại tối đa ngần này (5%). */
    private const OVER_ISSUE_RATIO = 0.05;

    public const TYPES = [
        'export' => 'Sử dụng',
        'cancel' => 'Loại bỏ',
    ];

    public function index(Request $request)
    {
        $departmentId = $this->departmentId();

        $datas = DB::table(self::TABLE)
            ->leftJoin('standard_imports', self::TABLE.'.import_id', '=', 'standard_imports.id')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            // Đơn vị tính khai ở danh mục chất chuẩn CỦA PHÒNG, không còn ở danh mục chung
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_imports.category_id'))
            ->leftJoin('groups', self::TABLE.'.group_id', '=', 'groups.id')
            ->select(
                self::TABLE.'.*',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_categories.groups',
                'standard_names.name as standard_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'standard_imports.amount as import_amount',
                'standard_imports.batch_no as standard_batch_no',
                'standard_imports.expired_date',
                'standard_imports.group_code',
                'groups.name as group_name'
            )
            ->where(self::TABLE.'.department_id', $departmentId)
            ->orderBy(self::TABLE.'.created_at', 'desc')
            ->orderBy(self::TABLE.'.id', 'desc')
            ->get();

        // Danh sách Đề nghị cấp phát chuẩn của các Tổ
        $requests = DB::table('standard_request_lists')
            ->leftJoin('groups', 'standard_request_lists.group_id', '=', 'groups.id')
            ->select(
                'standard_request_lists.*',
                'groups.name as group_name'
            )
            ->where('standard_request_lists.department_id', $departmentId)
            ->orderBy('standard_request_lists.created_at', 'desc')
            ->get();

        $requestItems = DB::table('standard_request_items')
            ->leftJoin('standard_request_lists', 'standard_request_items.request_list_id', '=', 'standard_request_lists.id')
            ->leftJoin('standard_categories', 'standard_request_items.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('standard_imports', 'standard_request_items.import_id', '=', 'standard_imports.id')
            ->leftJoin('locations', 'standard_imports.location_id', '=', 'locations.id')
            ->leftJoin('analysts', 'standard_request_items.analyst_id', '=', 'analysts.id')
            ->leftJoin('purposes', 'standard_request_items.purpose_id', '=', 'purposes.id')
            ->leftJoin('suppliers', 'standard_request_items.supplier_id', '=', 'suppliers.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->where('standard_request_items.active', 1)
            ->select(
                'standard_request_items.*',
                'standard_names.name as standard_name',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_imports.batch_no',
                'standard_imports.expired_date as import_expired_date',
                'locations.code as location_code',
                'analysts.name as analyst_name',
                'purposes.name as purpose_name',
                DB::raw('COALESCE(suppliers.name, manufacturers.name) as supplier_name')
            )
            ->where('standard_request_lists.department_id', $departmentId)
            ->get()
            ->groupBy('request_list_id');

        $groups = DB::table('groups')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $productNames = DB::table('product_names')
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $analysts = DB::table('analysts')
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $packagingSpecs = DB::table('packaging_specifications')
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $purposes = DB::table('purposes')
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $suppliers = DB::table('suppliers')
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $units = DB::table('units')
            ->where('status_id', 1)
            ->orderBy('name')
            ->get();

        $standardCategories = DB::table('standard_categories')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_categories.id'))
            ->select(
                'standard_categories.id',
                'standard_categories.code',
                'standard_categories.version',
                'standard_categories.manufacturers_id',
                'standard_names.name as standard_name',
                'manufacturers.name as manufacturer_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where('standard_categories.status_id', 1)
            ->orderBy('standard_names.name')
            ->get();

        $availableImports = $this->importOptions($departmentId);

        // Tính tồn kho theo từng chất chuẩn từ $availableImports (chuẩn xác 100% như màn hình Tồn kho)
        $inventoryByCategory = $availableImports->groupBy('category_id')
            ->map(function ($group) {
                return [
                    'total_remaining' => (float) $group->sum('remaining'),
                    'total_tubes' => (int) $group->where('remaining', '>', 0.00005)->count(),
                    'latest_import' => $group->first(),
                ];
            });

        $departmentStandardInventory = $standardCategories->map(function ($cat) use ($inventoryByCategory) {
            $inv = $inventoryByCategory[$cat->id] ?? null;
            $latest = $inv['latest_import'] ?? null;
            $unitName = $cat->unit_short_name ?: $cat->unit_name;

            $spec = '—';
            $purId = null;
            $purName = '—';
            $criteriaNames = [];

            if ($latest) {
                if (!empty($latest->amount)) {
                    $spec = (float) $latest->amount . ' ' . $unitName;
                }
                if (!empty($latest->purpose_id)) {
                    $pVal = $latest->purpose_id;
                    $ids = is_array($pVal) ? $pVal : json_decode((string) $pVal, true);
                    if (!is_array($ids)) {
                        $ids = is_numeric($pVal) ? [(int) $pVal] : [];
                    }
                    if (!empty($ids)) {
                        $purId = (int) $ids[0];
                        $criteriaNames = DB::table('purposes')->whereIn('id', $ids)->pluck('name')->toArray();
                        $purName = implode(', ', $criteriaNames);
                    }
                }
            }

            $cat->specification = $spec;
            $cat->manufacturer_id = $cat->manufacturers_id ?? null;
            $cat->purpose_id = $purId;
            $cat->purpose_name = $purName;
            $cat->criteria_names = $criteriaNames;
            $cat->total_remaining = $inv['total_remaining'] ?? 0;
            $cat->total_tubes = $inv['total_tubes'] ?? 0;

            return $cat;
        });

        session()->put(['title' => 'SỬ DỤNG - SỬ DỤNG CHẤT CHUẨN']);

        $activeTab = $request->input('tab');
        if (!in_array($activeTab, ['book', 'request'])) {
            $activeTab = 'book';
        }

        return view('pages.export.StandardExport.list', [
            'datas' => $datas,
            'requests' => $requests,
            'requestItems' => $requestItems,
            'groups' => $groups,
            'productNames' => $productNames,
            'analysts' => $analysts,
            'packagingSpecs' => $packagingSpecs,
            'standardCategories' => $standardCategories,
            'departmentStandardInventory' => $departmentStandardInventory,
            'availableImports' => $availableImports,
            'imports' => $availableImports,
            'purposes' => $purposes,
            'suppliers' => $suppliers,
            'units' => $units,
            'checkers' => $this->checkerOptions($departmentId),
            'types' => self::TYPES,
            'standardGroups' => config('standard.groups'),
            'overIssuePercent' => (int) round(self::OVER_ISSUE_RATIO * 100),
            'adjustCounts' => $this->adjustCounts($departmentId),
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * TRA ỐNG THEO MÃ ỐNG CHUẨN - quét mã vạch trên nhãn hoặc gõ tay mã.
     *
     * Trả về đúng ống của phòng ban đang đứng kèm cờ có xuất được hay không. Điều kiện
     * xuất được lấy nguyên từ importOptions() - cùng một nguồn với ô chọn phiếu nhập
     * trên form, để quét mã và chọn tay không bao giờ cho hai kết quả khác nhau.
     */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->code);

        if ($code === '') {
            return response()->json(['ok' => false, 'reason' => 'Vui lòng quét mã vạch trên nhãn hoặc nhập mã ống chuẩn.']);
        }

        $import = $this->importOptions($this->departmentId())->firstWhere('code', $code);

        if (! $import) {
            return response()->json([
                'ok' => false,
                'reason' => 'Không tìm thấy mã ống chuẩn "'.$code.'" trong kho của phòng ban này, '
                    .'hoặc phiếu nhập đã bị khoá.',
            ]);
        }

        return response()->json([
            'ok' => (bool) $import->selectable,
            'id' => $import->id,
            'code' => $import->code,
            // Khoá chem_name giữ đúng tên mà pages/export/shared/assets.blade.php đang đọc,
            // để phần quét mã dùng chung một đoạn JS với màn Sử Dụng Hoá Chất.
            'chem_name' => $import->standard_name ?: '—',
            'category_code' => $import->category_code ?: '—',
            'batch_no' => $import->batch_no ?: '—',
            'remaining' => $this->number($import->remaining).' '.($import->unit_short_name ?: ''),
            'expired_date' => $import->expired_date
                ? \Carbon\Carbon::parse($import->expired_date)->format('d/m/Y')
                : '—',
            'reason' => $import->selectable ? null : $this->notSelectableReason($import),
        ]);
    }

    /** Vì sao một ống không xuất được, viết đúng cách xử lý tiếp theo cho người dùng. */
    private function notSelectableReason($import): string
    {
        if ($import->expired) {
            return 'Ống chuẩn '.$import->code.' đã hết hạn sử dụng ngày '
                .\Carbon\Carbon::parse($import->expired_date)->format('d/m/Y')
                .' nên không xuất ra sử dụng được.';
        }

        if ($import->waiting_internal) {
            return 'Ống chuẩn '.$import->code.' chưa xác định hạn dùng nội bộ. Vào màn hình Tồn Kho Chất Chuẩn, '
                .'tab "Chưa Xác Định Hạn Nội Bộ" để xác định trước.';
        }

        return 'Ống chuẩn '.$import->code.' đã hết tồn, vui lòng quét ống khác.';
    }

    /**
     * LẤY DANH SÁCH CHUẨN ĐÃ ĐƯỢC CẤP PHÁT CHO MỘT TỔ (AJAX)
     *
     * Nhân viên thuộc tổ chỉ được nhìn thấy và sử dụng chuẩn đã được cấp phát cho tổ mình.
     */
    public function getIssuedStandards(Request $request)
    {
        $groupId = (int) $request->group_id;
        $departmentId = $this->departmentId();

        if (!$groupId) {
            return response()->json(['standards' => []]);
        }

        // Các request_items đã được cấp phát cho tổ này
        $issuedItems = DB::table('standard_request_items')
            ->join('standard_request_lists', 'standard_request_items.request_list_id', '=', 'standard_request_lists.id')
            ->leftJoin('standard_imports', 'standard_request_items.import_id', '=', 'standard_imports.id')
            ->leftJoin('locations', 'standard_imports.location_id', '=', 'locations.id')
            ->leftJoin('standard_categories', 'standard_request_items.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_request_items.category_id'))
            ->leftJoin('analysts', 'standard_request_items.analyst_id', '=', 'analysts.id')
            ->leftJoin('purposes', 'standard_request_items.purpose_id', '=', 'purposes.id')
            ->leftJoin('suppliers', 'standard_request_items.supplier_id', '=', 'suppliers.id')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->where('standard_request_items.active', 1)
            ->select(
                'standard_request_items.*',
                'standard_imports.amount as import_amount',
                'standard_imports.potency',
                'standard_imports.moisture',
                'standard_imports.standard_form',
                'standard_imports.expiry_type',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                'standard_names.name as standard_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name',
                'standard_imports.batch_no',
                'standard_imports.expired_date as import_expired_date',
                'locations.code as location_code',
                'analysts.name as analyst_name',
                'purposes.name as purpose_name',
                DB::raw('COALESCE(suppliers.name, manufacturers.name) as supplier_name')
            )
            ->where('standard_request_lists.department_id', $departmentId)
            ->where('standard_request_lists.group_id', $groupId)
            ->where('standard_request_items.active', 1)
            ->where('standard_request_items.status', 'issued')
            ->whereNotNull('standard_request_items.import_id')
            // Ẩn ống chuẩn đã được lập phiếu sử dụng: mỗi ống cấp phát cho tổ chỉ dùng
            // một lần, tránh người sau chọn lại đúng ống người trước đã xuất.
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from(self::TABLE)
                    ->whereColumn(self::TABLE.'.request_item_id', 'standard_request_items.id')
                    ->where(self::TABLE.'.type', 'export');
            })
            ->orderBy('standard_request_items.issued_at', 'desc')
            ->get();

        $importIds = $issuedItems->pluck('import_id')->filter()->unique();
        $attachments = DB::table('standard_import_attachments')
            ->whereIn('standard_import_id', $importIds)
            ->select('id', 'standard_import_id', 'file_name')
            ->get()
            ->groupBy('standard_import_id');

        $used = $this->sumByImport(self::TABLE, 'amount', $departmentId);
        $balanced = $this->sumByImport('standard_balancings', 'balancing_amount', $departmentId);

        $issuedItems->transform(function ($item) use ($attachments, $used, $balanced) {
            $item->attachments = $attachments->get($item->import_id, collect())->values();
            
            $amount = (float) $item->import_amount;
            $itemUsed = (float) ($used[$item->import_id] ?? 0);
            $itemBalanced = (float) ($balanced[$item->import_id] ?? 0);
            $item->actual_remaining = max($amount + $itemBalanced - $itemUsed, 0);

            return $item;
        });

        return response()->json(['standards' => $issuedItems]);
    }

    /**
     * LẤY THÔNG TIN CATEGORY (QUI CÁCH, NSX, MỤC ĐÍCH)
     */
    public function getCategoryInfo(Request $request)
    {
        $categoryId = (int) $request->category_id;
        $departmentId = $this->departmentId();

        $category = DB::table('standard_categories')
            ->leftJoin('manufacturers', 'standard_categories.manufacturers_id', '=', 'manufacturers.id')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_categories.id'))
            ->select(
                'standard_categories.*',
                'manufacturers.name as manufacturer_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            ->where('standard_categories.id', $categoryId)
            ->first();

        if (!$category) {
            return response()->json([]);
        }

        $latestImport = DB::table('standard_imports')
            ->leftJoin('suppliers', 'standard_imports.supplier_id', '=', 'suppliers.id')
            ->select('standard_imports.*', 'suppliers.name as supplier_name')
            ->where('standard_imports.category_id', $categoryId)
            ->where('standard_imports.department_id', $departmentId)
            ->orderBy('standard_imports.id', 'desc')
            ->first();

        $unitName = $category->unit_short_name ?: $category->unit_name;
        $specification = '';
        $purposeId = null;
        $purposeName = '';

        if ($latestImport) {
            if ($latestImport->amount) {
                $specification = (float)$latestImport->amount . ' ' . $unitName;
            }

            if ($latestImport->purpose_id) {
                $pVal = $latestImport->purpose_id;
                $ids = is_array($pVal) ? $pVal : json_decode((string)$pVal, true);
                if (!is_array($ids)) {
                    $ids = is_numeric($pVal) ? [(int)$pVal] : [];
                }
                if (!empty($ids)) {
                    $purposeId = (int)$ids[0];
                    $purposeName = DB::table('purposes')->whereIn('id', $ids)->pluck('name')->implode(', ');
                }
            }
        }

        // NSX/Nguồn gốc: lấy từ nhà sản xuất trong danh mục hoặc nhà cung cấp ở phiếu nhập
        $supplierName = ($latestImport && $latestImport->supplier_name) ? $latestImport->supplier_name : ($category->manufacturer_name ?: '—');
        $supplierId = ($latestImport && $latestImport->supplier_id) ? $latestImport->supplier_id : $category->manufacturers_id;

        return response()->json([
            'supplier_id' => $supplierId,
            'supplier_name' => $supplierName,
            'purpose_id' => $purposeId,
            'purpose_name' => $purposeName ?: '—',
            'specification' => $specification ?: '—',
            'unit' => $unitName ?: '—',
        ]);
    }

    /**
     * TỔ TẠO ĐỀ NGHỊ CẤP PHÁT CHUẨN
     */
    public function requestStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'group_id' => ['required', 'exists:groups,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:standard_categories,id'],
            'items.*.import_id' => ['nullable'],
            'items.*.specification' => ['nullable', 'string', 'max:100'],
            'items.*.supplier_id' => ['nullable'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.purpose_id' => ['nullable'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.test_criteria' => ['nullable'],
            'items.*.analyst_id' => ['nullable'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'group_id.required' => 'Vui lòng chọn Tổ đề nghị.',
            'group_id.exists' => 'Tổ được chọn không tồn tại.',
            'items.required' => 'Vui lòng thêm ít nhất một chất chuẩn đề nghị.',
            'items.min' => 'Vui lòng thêm ít nhất một chất chuẩn đề nghị.',
            'items.*.category_id.required' => 'Vui lòng chọn chất chuẩn.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'requestCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'request');
        }

        $actionType = $request->input('action_type', 'send');
        $isDraft = $actionType === 'draft';
        $status = $isDraft ? 'draft' : 'pending';

        $deptStr = str_pad($departmentId, 2, '0', STR_PAD_LEFT);
        $groupStr = str_pad($request->group_id, 2, '0', STR_PAD_LEFT);
        $dateStr = date('dmy');
        $prefix = $deptStr . $groupStr . $dateStr . '_';

        $latestCode = DB::table('standard_request_lists')
            ->where('code', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->value('code');

        $seq = 1;
        if ($latestCode) {
            $parts = explode('_', $latestCode);
            $seq = (int) end($parts) + 1;
        }

        $code = $prefix . str_pad($seq, 2, '0', STR_PAD_LEFT);

        $listId = DB::table('standard_request_lists')->insertGetId([
            'code' => $code,
            'department_id' => $departmentId,
            'group_id' => (int) $request->group_id,
            'status' => $status,
            'note' => $this->nullIfBlank($request->note),
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($request->items as $item) {
            DB::table('standard_request_items')->insert([
                'request_list_id' => $listId,
                'category_id' => (int) $item['category_id'],
                'import_id' => !empty($item['import_id']) ? (int) $item['import_id'] : null,
                'import_code' => !empty($item['import_id']) ? DB::table('standard_imports')->where('id', $item['import_id'])->value('code') : null,
                'specification' => $this->nullIfBlank($item['specification'] ?? null),
                'supplier_id' => !empty($item['supplier_id']) ? (int) $item['supplier_id'] : null,
                'requested_amount' => (float) $item['requested_amount'],
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'purpose_id' => !empty($item['purpose_id']) ? (int) $item['purpose_id'] : null,
                'product_name' => $this->nullIfBlank($item['product_name'] ?? null),
                'test_criteria' => is_array($item['test_criteria'] ?? null) ? implode(', ', array_filter($item['test_criteria'])) : $this->nullIfBlank($item['test_criteria'] ?? null),
                'analyst_id' => !empty($item['analyst_id']) ? (int) $item['analyst_id'] : null,
                'status' => $status,
                'note' => $this->nullIfBlank($item['note'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $groupName = DB::table('groups')->where('id', $request->group_id)->value('name') ?: 'Tổ';

        AuditTrialController::log(
            $isDraft ? 'Lưu tạm đề nghị cấp phát chuẩn' : 'Tạo đề nghị cấp phát chuẩn',
            'standard_request_lists',
            $listId,
            'NA',
            ($isDraft ? 'Lưu tạm đề nghị ' : 'Tạo đề nghị cấp phát ') . $code . ' cho ' . $groupName . ' (' . count($request->items) . ' mục)'
        );

        $msg = $isDraft
            ? 'Đã lưu tạm đề nghị cấp phát chuẩn ' . $code . '! Bạn có thể gửi đề nghị khi sẵn sàng.'
            : 'Đã gửi đề nghị cấp phát chuẩn ' . $code . ' thành công!';

        return redirect()->route('pages.export.standardExport.list', ['tab' => 'request'])
            ->with('success', $msg);
    }

    /**
     * CẬP NHẬT / ĐIỀU CHỈNH ĐỀ NGHỊ ĐÃ LƯU TẠM
     */
    public function requestUpdate(Request $request)
    {
        $departmentId = $this->departmentId();
        $listId = (int) $request->request_list_id;

        $req = DB::table('standard_request_lists')
            ->where('id', $listId)
            ->where('department_id', $departmentId)
            ->first();

        if (!$req || $req->status !== 'draft') {
            return redirect()->back()->with('error', 'Chỉ có thể điều chỉnh phiếu đề nghị đang ở trạng thái Lưu tạm!');
        }

        $validator = Validator::make($request->all(), [
            'request_list_id' => ['required', 'exists:standard_request_lists,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:standard_categories,id'],
            'items.*.import_id' => ['nullable'],
            'items.*.specification' => ['nullable', 'string', 'max:100'],
            'items.*.supplier_id' => ['nullable'],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.purpose_id' => ['nullable'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.test_criteria' => ['nullable'],
            'items.*.analyst_id' => ['nullable'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'group_id.required' => 'Vui lòng chọn Tổ đề nghị.',
            'group_id.exists' => 'Tổ được chọn không tồn tại.',
            'items.required' => 'Vui lòng thêm ít nhất một chất chuẩn đề nghị.',
            'items.min' => 'Vui lòng thêm ít nhất một chất chuẩn đề nghị.',
            'items.*.category_id.required' => 'Vui lòng chọn chất chuẩn.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'requestCreateErrors')
                ->with('error', $validator->errors()->first())
                ->withInput()
                ->with('activeTab', 'request');
        }

        $actionType = $request->input('action_type', 'draft');
        $isDraft = $actionType === 'draft';
        $status = $isDraft ? 'draft' : 'pending';

        DB::table('standard_request_lists')->where('id', $req->id)->update([
            'group_id' => (int) $request->group_id,
            'status' => $status,
            'note' => $this->nullIfBlank($request->note),
            'updated_at' => now(),
        ]);

        // Recreate items
        // Không xoá cứng: bỏ hiệu lực các mục cũ (active = 0).
        DB::table('standard_request_items')->where('request_list_id', $req->id)->update([
            'active' => 0,
            'updated_at' => now(),
        ]);

        foreach ($request->items as $item) {
            DB::table('standard_request_items')->insert([
                'request_list_id' => $req->id,
                'category_id' => (int) $item['category_id'],
                'import_id' => !empty($item['import_id']) ? (int) $item['import_id'] : null,
                'import_code' => !empty($item['import_id']) ? DB::table('standard_imports')->where('id', $item['import_id'])->value('code') : null,
                'specification' => $this->nullIfBlank($item['specification'] ?? null),
                'supplier_id' => !empty($item['supplier_id']) ? (int) $item['supplier_id'] : null,
                'requested_amount' => (float) $item['requested_amount'],
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'purpose_id' => !empty($item['purpose_id']) ? (int) $item['purpose_id'] : null,
                'product_name' => $this->nullIfBlank($item['product_name'] ?? null),
                'test_criteria' => is_array($item['test_criteria'] ?? null) ? implode(', ', array_filter($item['test_criteria'])) : $this->nullIfBlank($item['test_criteria'] ?? null),
                'analyst_id' => !empty($item['analyst_id']) ? (int) $item['analyst_id'] : null,
                'status' => $status,
                'note' => $this->nullIfBlank($item['note'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $groupName = DB::table('groups')->where('id', $request->group_id)->value('name') ?: 'Tổ';

        AuditTrialController::log(
            $isDraft ? 'Cập nhật đề nghị cấp phát chuẩn' : 'Gửi đề nghị cấp phát chuẩn sau cập nhật',
            'standard_request_lists',
            $req->id,
            'draft',
            ($isDraft ? 'Cập nhật đề nghị ' : 'Gửi đề nghị ') . $req->code . ' cho ' . $groupName . ' (' . count($request->items) . ' mục)'
        );

        $msg = $isDraft
            ? 'Đã cập nhật lưu tạm đề nghị ' . $req->code . ' thành công!'
            : 'Đã cập nhật và gửi đề nghị ' . $req->code . ' thành công!';

        return redirect()->route('pages.export.standardExport.list', ['tab' => 'request'])
            ->with('success', $msg);
    }

    /**
     * GỬI ĐỀ NGHỊ ĐÃ LƯU TẠM
     */
    public function requestSend(Request $request)
    {
        $listId = (int) $request->request_list_id;
        $req = DB::table('standard_request_lists')
            ->where('id', $listId)
            ->where('department_id', $this->departmentId())
            ->first();

        if (!$req || $req->status !== 'draft') {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị lưu tạm cần gửi!');
        }

        DB::table('standard_request_lists')->where('id', $req->id)->update([
            'status' => 'pending',
            'updated_at' => now(),
        ]);

        DB::table('standard_request_items')->where('request_list_id', $req->id)->where('status', 'draft')->update([
            'status' => 'pending',
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Gửi đề nghị cấp phát chuẩn',
            'standard_request_lists',
            $req->id,
            'draft',
            'Gửi đề nghị cấp phát: ' . $req->code
        );

        return redirect()->route('pages.export.standardExport.list', ['tab' => 'request'])
            ->with('success', 'Đã gửi đề nghị cấp phát mã ' . $req->code . ' thành công!');
    }

    /**
     * THỦ KHO / QUẢN LÝ KHO CẤP PHÁT CHUẨN CHO TỔ
     */
    public function issueStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'exists:standard_request_items,id'],
            'import_id' => ['required', 'exists:standard_imports,id'],
            'issued_amount' => ['required', 'numeric', 'min:0.0001'],
            'issued_unit' => ['nullable', 'string', 'max:50'],
            'return_standard' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'item_id.required' => 'Không tìm thấy mục đề nghị cần cấp phát.',
            'import_id.required' => 'Vui lòng chọn ống chuẩn trong kho để cấp phát.',
            'issued_amount.required' => 'Vui lòng nhập số lượng cấp phát.',
            'issued_amount.min' => 'Số lượng cấp phát phải lớn hơn 0.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }
            return redirect()->back()
                ->withErrors($validator, 'issueErrors')
                ->withInput()
                ->with('activeTab', 'request');
        }

        $item = DB::table('standard_request_items')->where('id', $request->item_id)->where('active', 1)->first();
        if (!$item) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy mục đề nghị!']);
            }
            return redirect()->back()->with('error', 'Không tìm thấy mục đề nghị!');
        }

        $import = DB::table('standard_imports')->where('id', $request->import_id)->where('department_id', $departmentId)->first();
        if (!$import) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy ống chuẩn trong kho phòng ban này!']);
            }
            return redirect()->back()->with('error', 'Không tìm thấy ống chuẩn trong kho phòng ban này!');
        }

        // Thời điểm cấp phát luôn là lúc bấm Cấp Phát, không nhận giá trị từ form
        $issuedAt = now();

        $waitingInternal = (int) ($import->shelf_life_months ?? 0) > 0 && ! $import->internal_expired_date;
        if ($waitingInternal) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Ống chuẩn chưa xác định hạn dùng nội bộ, không được cấp phát!']);
            }
            return redirect()->back()->with('error', 'Ống chuẩn chưa xác định hạn dùng nội bộ, không được cấp phát!');
        }

        $isExpired = $import->expired_date && now()->startOfDay()->gt(\Carbon\Carbon::parse($import->expired_date));
        if ($isExpired) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Ống chuẩn đã hết hạn sử dụng, không được cấp phát!']);
            }
            return redirect()->back()->with('error', 'Ống chuẩn đã hết hạn sử dụng, không được cấp phát!');
        }

        DB::table('standard_request_items')->where('id', $item->id)->update([
            'import_id' => (int) $import->id,
            'import_code' => $import->code,
            'issued_amount' => (float) $request->issued_amount,
            'issued_unit' => $this->nullIfBlank($request->issued_unit ?? $item->requested_unit),
            'issued_by' => $this->actor(),
            'issued_at' => $issuedAt,
            'return_standard' => $request->boolean('return_standard', false),
            'status' => 'issued',
            'note' => $this->nullIfBlank($request->note ?? $item->note),
            'updated_at' => now(),
        ]);

        // Cập nhật trạng thái phiếu đề nghị tổng (completed nếu đã cấp hết, partial nếu cấp 1 phần)
        $allItems = DB::table('standard_request_items')->where('request_list_id', $item->request_list_id)->where('active', 1)->get();
        $pendingCount = $allItems->where('status', 'pending')->count();
        $issuedCount = $allItems->where('status', 'issued')->count();

        $newListStatus = $pendingCount === 0 ? 'completed' : ($issuedCount > 0 ? 'partial' : 'pending');

        DB::table('standard_request_lists')->where('id', $item->request_list_id)->update([
            'status' => $newListStatus,
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Cấp phát chuẩn',
            'standard_request_items',
            $item->id,
            'pending',
            'Cấp ống ' . $import->code . ' số lượng ' . $request->issued_amount
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cấp phát ống chuẩn ' . $import->code . ' thành công!',
                'data' => [
                    'issued_amount' => (float) $request->issued_amount,
                    'issued_unit' => $this->nullIfBlank($request->issued_unit ?? $item->requested_unit),
                    'return_standard' => $request->boolean('return_standard', false),
                    'issued_by' => $this->actor(),
                    'issued_at' => $issuedAt->format('d/m/Y H:i'),
                    'import_code' => $import->code,
                    'batch_no' => $import->batch_no,
                    'location' => DB::table('locations')->where('id', $import->location_id)->value('code'),
                ]
            ]);
        }

        return redirect()->route('pages.export.standardExport.list', ['tab' => 'request'])
            ->with('success', 'Đã cấp phát ống chuẩn ' . $import->code . ' thành công!');
    }

    /**
     * LƯU NHÁP THÔNG TIN CẤP PHÁT CHO TẤT CẢ CÁC MỤC TRONG PHIẾU
     */
    public function issueDraftStore(Request $request)
    {
        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'request_list_id' => ['required', 'exists:standard_request_lists,id'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:standard_request_items,id'],
            'items.*.import_id' => ['nullable'],
            'items.*.issued_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.issued_unit' => ['nullable', 'string', 'max:50'],
            'items.*.return_standard' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $listId = $request->request_list_id;
        $req = DB::table('standard_request_lists')->where('id', $listId)->where('department_id', $departmentId)->first();
        if (!$req) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy phiếu đề nghị.']);
        }

        foreach ($request->items as $itemData) {
            $itemId = (int)$itemData['id'];
            $importId = !empty($itemData['import_id']) ? (int)$itemData['import_id'] : null;
            
            $importCode = null;
            if ($importId) {
                $importCode = DB::table('standard_imports')->where('id', $importId)->value('code');
            }

            DB::table('standard_request_items')->where('id', $itemId)->where('request_list_id', $listId)->update([
                'import_id' => $importId,
                'import_code' => $importCode,
                'issued_amount' => !empty($itemData['issued_amount']) ? (float)$itemData['issued_amount'] : null,
                'issued_unit' => $this->nullIfBlank($itemData['issued_unit'] ?? null),
                'return_standard' => !empty($itemData['return_standard']) ? 1 : 0,
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu nháp thông tin cấp phát thành công!'
        ]);
    }

    /**
     * TỪ CHỐI CẤP PHÁT MỤC ĐỀ NGHỊ
     */
    public function requestReject(Request $request)
    {
        $item = DB::table('standard_request_items')->where('id', $request->item_id)->where('active', 1)->first();
        if (!$item) {
            return redirect()->back()->with('error', 'Không tìm thấy mục đề nghị!');
        }

        DB::table('standard_request_items')->where('id', $item->id)->update([
            'status' => 'rejected',
            'note' => $this->nullIfBlank($request->note),
            'updated_at' => now(),
        ]);

        $allItems = DB::table('standard_request_items')->where('request_list_id', $item->request_list_id)->where('active', 1)->get();
        $pendingCount = $allItems->where('status', 'pending')->count();
        $issuedCount = $allItems->where('status', 'issued')->count();

        $newListStatus = $pendingCount === 0 ? ($issuedCount > 0 ? 'completed' : 'rejected') : 'partial';

        DB::table('standard_request_lists')->where('id', $item->request_list_id)->update([
            'status' => $newListStatus,
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Từ chối cấp phát',
            'standard_request_items',
            $item->id,
            $item->status,
            'Từ chối cấp phát' . ($request->filled('note') ? ': ' . $request->note : '')
        );

        return redirect()->route('pages.export.standardExport.list', ['tab' => 'request'])
            ->with('success', 'Đã từ chối mục đề nghị cấp phát.');
    }

    public function store(Request $request)
    {
        $departmentId = $this->departmentId();

        $import = $this->findImport($request->import_id, $departmentId);

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());
        $this->checkImport($validator, $request, $import);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request, $import) + [
            'department_id' => $departmentId,
            'created_by' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logHistory($id, 'Thêm mới');

        AuditTrialController::log(
            'Thêm mới',
            self::TABLE,
            $id,
            'NA',
            self::TYPES[$request->type].' chất chuẩn, mã ống chuẩn: '.$import->code.', số lượng: '.$request->amount
        );

        return redirect()->back()->with('success', 'Đã ghi nhận '.self::LABEL.' cho ống chuẩn '.$import->code.'!');
    }

    public function update(Request $request)
    {
        $departmentId = $this->departmentId();

        $current = DB::table(self::TABLE)
            ->where('id', $request->id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        $import = $this->findImport($request->import_id, $departmentId);

        $validator = Validator::make($request->all(), $this->rules($departmentId), $this->messages());
        // Bỏ qua chính bản ghi đang sửa khi tính tồn, nếu không số lượng cũ bị trừ hai lần.
        // Giữ nguyên ống cũ thì không xét lại điều kiện hạn dùng / còn tồn, phiếu đã ghi rồi,
        // chỉ khi ĐỔI sang ống khác mới coi là một lần chọn mới.
        $this->checkImport($validator, $request, $import, (int) $current->id, (int) $current->import_id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request, $import);

        // Dựng mô tả thay đổi TRƯỚC khi ghi đè, lúc này còn cả giá trị cũ lẫn mới
        $note = $this->changeNote($current, $payload, $request->adjust_reason);

        if ($note === '') {
            return redirect()->back()->with('error', 'Không có thông tin nào thay đổi nên chưa cập nhật '.self::LABEL.'.');
        }

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_by' => $this->actor(),
            'updated_at' => now(),
        ]);

        $this->logHistory($current->id, 'Cập nhật', $note);

        AuditTrialController::log('Cập nhật', self::TABLE, $current->id, $current->code, $note);

        return redirect()->back()->with('success', 'Cập nhật '.self::LABEL.' thành công!');
    }

    /**
     * Số lần ĐIỀU CHỈNH của từng phiếu: [standard_export_id => số lần].
     *
     * Bỏ dòng "Thêm mới" vì đó là lúc lập phiếu chứ không phải một lần chỉnh sửa.
     */
    private function adjustCounts(int $departmentId)
    {
        return DB::table(self::HISTORY_TABLE)
            ->select('standard_export_id', DB::raw('COUNT(*) as times'))
            ->whereIn('standard_export_id', function ($query) use ($departmentId) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $departmentId);
            })
            ->where('action', '<>', 'Thêm mới')
            ->groupBy('standard_export_id')
            ->pluck('times', 'standard_export_id');
    }

    /** Trả về lịch sử điều chỉnh của một phiếu sử dụng cho modal xem lịch sử. */
    public function history(Request $request)
    {
        $rows = DB::table(self::HISTORY_TABLE)
            ->leftJoin('standard_imports', self::HISTORY_TABLE.'.import_id', '=', 'standard_imports.id')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $this->departmentId(), 'standard_imports.category_id'))
            ->select(
                self::HISTORY_TABLE.'.*',
                'standard_names.name as standard_name',
                'units.short_name as unit_short_name',
                'units.name as unit_name'
            )
            // Chỉ cho xem lịch sử của phiếu thuộc phòng ban đang chọn
            ->whereIn(self::HISTORY_TABLE.'.standard_export_id', function ($query) {
                $query->select('id')
                    ->from(self::TABLE)
                    ->where('department_id', $this->departmentId());
            })
            ->where(self::HISTORY_TABLE.'.standard_export_id', $request->id)
            ->orderBy(self::HISTORY_TABLE.'.id', 'desc')
            ->get();

        return response()->json([
            'rows' => $rows->map(function ($row) {
                $unit = $row->unit_short_name ?: $row->unit_name;

                return [
                    'action' => $row->action,
                    'change_note' => $row->change_note,
                    'created_by' => $row->created_by ?: 'NA',
                    'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
                    'snapshot' => [
                        'Mã ống chuẩn' => $row->code ?: '—',
                        'Chất chuẩn' => $row->standard_name ?: '—',
                        'Số lượng' => $row->amount !== null ? $this->number((float) $row->amount).' '.$unit : '—',
                        'Loại phiếu' => self::TYPES[$row->type] ?? ($row->type ?: '—'),
                        'Tên sản phẩm' => $row->product_name ?: '—',
                        'Số lô SP' => $row->batch_no ?: '—',
                        'Chỉ tiêu' => $row->testing ?: '—',
                        'Lý do loại bỏ' => $row->reason ?: '—',
                    ],
                ];
            })->values(),
        ]);
    }

    /**
     * Chụp lại giá trị của phiếu NGAY SAU khi thay đổi vào bảng lịch sử.
     *
     * Đọc lại từ DB thay vì dùng payload để ảnh chụp luôn khớp đúng những gì đã lưu.
     */
    private function logHistory($id, string $action, ?string $note = null): void
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        if (! $row) {
            return;
        }

        DB::table(self::HISTORY_TABLE)->insert([
            'standard_export_id' => $row->id,
            'action' => $action,
            'code' => $row->code,
            'import_id' => $row->import_id,
            'amount' => $row->amount,
            'type' => $row->type,
            'product_name' => $row->product_name ?? null,
            'batch_no' => $row->batch_no ?? null,
            'testing' => $row->testing ?? null,
            'reason' => $row->reason ?? null,
            'change_note' => $note,
            'created_by' => $this->actor(),
            'created_at' => now(),
        ]);
    }

    /**
     * Mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới".
     *
     * Trả về chuỗi rỗng khi không có gì đổi, để màn hình không ghi một dòng lịch sử trống.
     * Lý do người dùng nhập được đặt lên đầu chuỗi.
     */
    private function changeNote($current, array $payload, ?string $reason = null): string
    {
        $parts = [];

        foreach (self::FIELDS as $field => $title) {
            $old = $current->$field;
            $new = $payload[$field] ?? null;

            // amount lấy từ DB là chuỗi "10.5000" còn form gửi "10.5", phải so theo giá trị số
            if ($field === 'amount') {
                if (abs((float) $old - (float) $new) < self::EPSILON) {
                    continue;
                }

                $parts[] = $title.': '.$this->number((float) $old).' -> '.$this->number((float) $new);

                continue;
            }

            if ((string) $old === (string) $new) {
                continue;
            }

            if ($field === 'type') {
                $parts[] = $title.': '.(self::TYPES[$old] ?? '—').' -> '.(self::TYPES[$new] ?? '—');

                continue;
            }

            $parts[] = $title.': '.($old === null || $old === '' ? '—' : $old).' -> '.($new === null || $new === '' ? '—' : $new);
        }

        if (! $parts) {
            return '';
        }

        $reason = trim((string) $reason);

        return ($reason !== '' ? 'Lý do: '.$reason.' | ' : '').implode(' | ', $parts);
    }

    private function historyDate($value): string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    }

    /**
     * Ống chuẩn của phòng ban đang chọn, còn hiệu lực.
     *
     * Kèm sẵn tồn còn lại / hạn mức xuất để form hiển thị mà không phải hỏi DB theo
     * từng phiếu. Cờ selectable = còn hạn dùng VÀ còn tồn > 0 VÀ đã có hạn nội bộ khi
     * cần: modal Thêm mới chỉ hiện ống selectable, modal Cập Nhật giữ cả ống không
     * selectable để phiếu xuất cũ còn chọn lại được đúng ống của nó.
     */
    private function importOptions(int $departmentId)
    {
        $used = $this->sumByImport(self::TABLE, 'amount', $departmentId);
        $balanced = $this->sumByImport('standard_balancings', 'balancing_amount', $departmentId);
        $today = now()->startOfDay();

        $query = DB::table('standard_imports')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id')
            ->leftJoin('standard_names', 'standard_categories.chem_names_id', '=', 'standard_names.id')
            ->leftJoin('locations', 'standard_imports.location_id', '=', 'locations.id')
            ->tap(fn ($query) => DepartmentStandard::joinUnit($query, $departmentId, 'standard_imports.category_id'));

        // Hạn dùng nội bộ lấy theo cấu hình của phòng ban đang chọn
        $imports = DepartmentStandard::join($query, $departmentId, 'standard_imports.category_id')
            ->select(
                'standard_imports.id',
                'standard_imports.code',
                'standard_imports.category_id',
                'standard_imports.purpose_id',
                'standard_imports.supplier_id',
                'standard_imports.amount',
                'standard_imports.batch_no',
                'standard_imports.expired_date',
                'standard_imports.expiry_type',
                'standard_imports.internal_expired_date',
                'standard_imports.group_code',
                'standard_imports.potency',
                'standard_imports.moisture',
                'standard_imports.standard_form',
                'standard_categories.code as category_code',
                'standard_categories.version as category_version',
                DepartmentStandard::shelfLifeColumn(),
                'standard_names.name as standard_name',
                'units.short_name as unit_short_name',
                'locations.code as location_code'
            )
            ->where('standard_imports.department_id', $departmentId)
            ->where('standard_imports.status_id', 1)
            ->orderBy('standard_imports.imported_date', 'desc')
            ->orderBy('standard_imports.id', 'desc')
            ->get();

        $importIds = $imports->pluck('id')->filter()->unique();
        $attachments = DB::table('standard_import_attachments')
            ->whereIn('standard_import_id', $importIds)
            ->select('id', 'standard_import_id', 'file_name')
            ->get()
            ->groupBy('standard_import_id');

        return $imports->map(function ($import) use ($used, $balanced, $today, $attachments) {
            $import->attachments = $attachments->get($import->id, collect())->values();
            $import->used = (float) ($used[$import->id] ?? 0);
            $import->balanced = (float) ($balanced[$import->id] ?? 0);
            $import->remaining = max((float) $import->amount + $import->balanced - $import->used, 0);
            $import->max_amount = $this->maxIssuable($import->remaining);

            $import->expired = $import->expired_date
                && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt($today);

            // Chất chuẩn có hạn dùng mặc định mà chưa xác định hạn nội bộ thì chưa được dùng
            $import->waiting_internal = (int) ($import->shelf_life_months ?? 0) > 0
                && ! $import->internal_expired_date;

            $import->selectable = ! $import->expired
                && ! $import->waiting_internal
                && $import->remaining > self::EPSILON;

            return $import;
        });
    }



    /** Người kiểm tra: user đang hoạt động của phòng ban đang chọn. */
    private function checkerOptions(int $departmentId)
    {
        // user_management.deparment_id là FK trỏ thẳng deparments.id
        return DB::table('user_management')
            ->select('user_management.userName', 'user_management.fullName')
            ->where('user_management.deparment_id', $departmentId)
            ->where('user_management.isActive', 1)
            ->orderBy('user_management.fullName', 'asc')
            ->get();
    }

    /** Tổng một cột số theo từng ống chuẩn trong phòng ban: [import_id => tổng]. */
    private function sumByImport(string $table, string $column, int $departmentId)
    {
        $query = DB::table($table)
            ->select('import_id', DB::raw('SUM(`'.$column.'`) as total'))
            ->where('department_id', $departmentId);

        if ($table !== self::TABLE) {
            $query->where('status_id', 1);
        }

        return $query->groupBy('import_id')
            ->pluck('total', 'import_id');
    }

    /**
     * Tồn còn lại của một ống chuẩn, có thể bỏ qua một phiếu xuất đang được sửa.
     *
     * Tồn = số lượng nhập + số đã cân đối - số đã xuất (kể cả phần huỷ bỏ).
     */
    private function remaining($import, ?int $ignoreExportId = null): float
    {
        $query = DB::table(self::TABLE)
            ->where('import_id', $import->id);

        if ($ignoreExportId) {
            $query->where('id', '<>', $ignoreExportId);
        }

        $balanced = (float) DB::table('standard_balancings')
            ->where('import_id', $import->id)
            ->where('status_id', 1)
            ->sum('balancing_amount');

        return max((float) $import->amount + $balanced - (float) $query->sum('amount'), 0);
    }

    /** Hạn mức được xuất: tồn còn lại cộng thêm phần vượt cho phép. */
    private function maxIssuable(float $remaining): float
    {
        return $remaining * (1 + self::OVER_ISSUE_RATIO);
    }

    private function findImport($importId, int $departmentId)
    {
        // Kèm shelf_life_months (theo cấu hình phòng ban) để kiểm tra hạn dùng nội bộ khi xuất
        $query = DB::table('standard_imports')
            ->leftJoin('standard_categories', 'standard_imports.category_id', '=', 'standard_categories.id');

        return DepartmentStandard::join($query, $departmentId, 'standard_imports.category_id')
            ->select('standard_imports.*', DepartmentStandard::shelfLifeColumn())
            ->where('standard_imports.id', $importId)
            ->where('standard_imports.department_id', $departmentId)
            ->where('standard_imports.status_id', 1)
            ->first();
    }

    /**
     * Kiểm tra ống chuẩn được chọn và số lượng xuất.
     *
     * @param  int|null  $ignoreExportId  phiếu xuất đang sửa, không tính vào tồn
     * @param  int|null  $currentImportId  ống bản ghi đang giữ; giữ nguyên ống này thì
     *                                     không xét lại điều kiện hạn dùng / còn tồn
     */
    private function checkImport($validator, Request $request, $import, ?int $ignoreExportId = null, ?int $currentImportId = null): void
    {
        $validator->after(function ($validator) use ($request, $import, $ignoreExportId, $currentImportId) {
            if (! $import) {
                $validator->errors()->add('import_id', 'Ống chuẩn được chọn không tồn tại hoặc đã bị khoá.');

                return;
            }

            $remaining = $this->remaining($import, $ignoreExportId);

            // Chỉ chặn khi người dùng CHỌN một ống khác với ống bản ghi đang giữ
            if ((int) $import->id !== (int) $currentImportId) {
                if ($import->expired_date && \Carbon\Carbon::parse($import->expired_date)->startOfDay()->lt(now()->startOfDay())) {
                    $validator->errors()->add(
                        'import_id',
                        'Ống chuẩn '.$import->code.' đã hết hạn sử dụng ngày '
                        .\Carbon\Carbon::parse($import->expired_date)->format('d/m/Y').', không được xuất ra sử dụng.'
                    );

                    return;
                }

                if ($remaining <= self::EPSILON) {
                    $validator->errors()->add('import_id', 'Ống chuẩn '.$import->code.' đã hết tồn, vui lòng chọn ống khác.');

                    return;
                }

                // Chất chuẩn có khai báo hạn dùng mặc định thì phải xác định hạn dùng nội bộ
                // (hạn sau khi mở ống) trước khi dùng.
                if ((int) ($import->shelf_life_months ?? 0) > 0 && ! $import->internal_expired_date) {
                    $validator->errors()->add(
                        'import_id',
                        'Ống chuẩn '.$import->code.' chưa xác định hạn dùng nội bộ nên chưa được sử dụng. '
                        .'Vào màn hình Tồn Kho Chất Chuẩn, tab "Chưa Xác Định Hạn Nội Bộ" để xác định trước.'
                    );

                    return;
                }
            }

            if (! is_numeric($request->amount)) {
                return;
            }

            if ($request->type !== 'export') {
                $limit = $this->maxIssuable($remaining);

                if ((float) $request->amount > $limit + self::EPSILON) {
                    $validator->errors()->add(
                        'amount',
                        'Ống chuẩn '.$import->code.' còn '.$this->number($remaining).'. Được xuất vượt tối đa '
                        .(int) round(self::OVER_ISSUE_RATIO * 100).'%, tức không quá '.$this->number($limit).'.'
                    );
                }
            }
        });
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }

    private function rules(int $departmentId): array
    {
        return [
            'group_id' => ['required', 'exists:groups,id'],
            'import_id' => ['required', 'exists:standard_imports,id'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'type' => ['required', 'in:'.implode(',', array_keys(self::TYPES))],
            'product_name' => ['nullable', 'max:255'],
            'batch_no' => ['nullable', 'max:100'],
            'testing' => ['nullable', 'max:255'],
            'reason' => ['nullable', 'max:500'],
            'request_item_id' => ['nullable', 'exists:standard_request_items,id'],
            // Chỉ ghi vào lịch sử điều chỉnh, không lưu thành cột của standard_exports
            'adjust_reason' => ['nullable', 'max:500'],
        ];
    }

    private function payload(Request $request, $import): array
    {
        return [
            'code' => $import->code,
            'import_id' => (int) $import->id,
            'group_id' => (int) $request->group_id,
            'amount' => (float) $request->amount,
            'type' => $request->type,
            'product_name' => $this->nullIfBlank($request->product_name),
            'batch_no' => $this->nullIfBlank($request->batch_no),
            'testing' => $this->nullIfBlank($request->testing),
            'reason' => $this->nullIfBlank($request->reason),
            'request_item_id' => $request->filled('request_item_id') ? (int) $request->request_item_id : null,
        ];
    }

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function requestDestroy(Request $request)
    {
        $departmentId = $this->departmentId();

        $req = DB::table('standard_request_lists')
            ->where('id', $request->request_list_id)
            ->where('department_id', $departmentId)
            ->first();

        if (! $req) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu đề nghị này.')->with('activeTab', 'request');
        }

        if ($req->status !== 'draft') {
            return redirect()->back()->with('error', 'Chỉ có thể huỷ phiếu đang ở trạng thái Lưu tạm.')->with('activeTab', 'request');
        }

        DB::table('standard_request_lists')->where('id', $req->id)->update([
            'status' => 'canceled',
            'updated_at' => now(),
        ]);

        AuditTrialController::log(
            'Huỷ đề nghị',
            'standard_request_lists',
            $req->id,
            $req->code,
            'Đã huỷ đề nghị cấp phát chuẩn đang lưu tạm'
        );

        return redirect()->back()->with('success', 'Đã huỷ phiếu đề nghị ' . $req->code . ' thành công!')->with('activeTab', 'request');
    }

    private function messages(): array
    {
        return [
            'group_id.required' => 'Vui lòng chọn Tổ sử dụng.',
            'group_id.exists' => 'Tổ được chọn không tồn tại.',
            'import_id.required' => 'Vui lòng chọn ống chuẩn đã được cấp phát.',
            'import_id.exists' => 'Ống chuẩn được chọn không tồn tại.',
            'amount.required' => 'Vui lòng nhập số lượng.',
            'amount.numeric' => 'Số lượng phải là số.',
            'amount.min' => 'Số lượng phải lớn hơn 0.',
            'type.required' => 'Vui lòng chọn loại phiếu.',
            'type.in' => 'Loại phiếu không hợp lệ.',
            'adjust_reason.max' => 'Lý do điều chỉnh tối đa 500 ký tự.',
        ];
    }
}
