<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Support\CategoryLookup;
use App\Support\ChemicalCompatibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TRA CỨU DỮ LIỆU GỐC CHO Ô CHỌN (AJAX) - DÙNG CHUNG 3 TRANG DANH MỤC
 *
 * Ô chọn Tên vật tư / Tên hoá chất / Tên chất chuẩn và Định khu không nhúng cả danh mục vào trang
 * nữa mà gọi các action này khi gõ tìm - xem App\Support\CategoryLookup.
 */
class CategoryLookupController extends Controller
{
    /** ?source=material|chemical|standard&q=&page= : tên dữ liệu gốc đã duyệt, đang hoạt động */
    public function names(Request $request)
    {
        $source = (string) $request->source;

        if (! isset(CategoryLookup::NAME_TABLES[$source])) {
            return response()->json(CategoryLookup::emptyResponse(), 422);
        }

        return response()->json(
            CategoryLookup::searchNames($source, (string) $request->q, (int) $request->page)
        );
    }

    /** ?q=&page= : tên sản phẩm đang hoạt động (Dữ Liệu Gốc), id chính là tên vì nơi dùng lưu dạng chữ */
    public function productNames(Request $request)
    {
        return response()->json(CategoryLookup::searchProductNames((string) $request->q, (int) $request->page));
    }

    /**
     * ?type=material|chemical|standard|all&q=&page= : định khu của phòng ban đang chọn.
     * type=all không lọc loại hàng; only_zone=quarantine chỉ lấy khu Biệt Trữ (modal Nhập).
     */
    public function locations(Request $request)
    {
        $type = (string) $request->type;
        $departmentId = (int) (session('user')['selected_department_id'] ?? 0);

        if ((! in_array($type, CategoryLookup::ITEM_TYPES, true) && $type !== 'all') || ! $departmentId) {
            return response()->json(CategoryLookup::emptyResponse(), 422);
        }

        // zone_filter: chip lọc nhanh theo phân loại định khu ('__none' = chưa phân loại)
        $zone = $request->filled('zone_filter') ? (string) $request->zone_filter : null;
        $zone = $zone === '__none' ? '' : $zone;
        $onlyZone = $request->filled('only_zone') ? (string) $request->only_zone : null;

        $result = CategoryLookup::searchLocations($type, $departmentId, (string) $request->q, (int) $request->page, $zone, $onlyZone);

        // Ô Định khu của modal "Khai Hoá Chất Cho Phòng" gửi kèm hoá chất đang khai: định khu nằm
        // cùng kệ/tủ với hoá chất tương kỵ bị khoá lại, kèm câu giải thích.
        if ($type === 'chemical' && (int) $request->category_id > 0) {
            $result['results'] = ChemicalCompatibility::annotateLocationOptions($result['results'], (int) $request->category_id);
        }

        return response()->json($result);
    }

    /**
     * ?category_id=&location_id= : đối chiếu tương kỵ khi đặt một hoá chất vào định khu (Sơ đồ lưu trữ
     * GHS) - cho khung cảnh báo dưới ô Định khu. Chỉ tra định khu của phòng ban đang chọn.
     */
    public function chemicalCompatibility(Request $request)
    {
        $departmentId = (int) (session('user')['selected_department_id'] ?? 0);
        $locationId = (int) $request->location_id;
        $categoryId = (int) $request->category_id;

        $inDepartment = $locationId && DB::table('locations')
            ->where('id', $locationId)
            ->where('department_id', $departmentId)
            ->exists();

        // Không đủ dữ liệu để xét thì trả "chưa xét" để khung cảnh báo tự ẩn, không báo lỗi
        if (! $inDepartment || ! $categoryId) {
            return response()->json(['checked' => false]);
        }

        return response()->json(['checked' => true] + ChemicalCompatibility::checkPlacement($categoryId, $locationId));
    }

    /** ?q=&page= : hoạt chất đã duyệt cho ô chọn nhiều của modal Tên Hoá Chất */
    public function activeIngredients(Request $request)
    {
        return response()->json(
            CategoryLookup::searchActiveIngredients((string) $request->q, (int) $request->page)
        );
    }

    /** ?scope=import|undeclared|approved&q=&page= : mã hoá chất theo phòng ban đang chọn */
    public function chemicalCategories(Request $request)
    {
        $scope = (string) $request->scope;
        $departmentId = (int) (session('user')['selected_department_id'] ?? 0);

        if (! in_array($scope, CategoryLookup::CHEMICAL_SCOPES, true) || ! $departmentId) {
            return response()->json(CategoryLookup::emptyResponse(), 422);
        }

        return response()->json(
            CategoryLookup::searchChemicalCategories($scope, $departmentId, (string) $request->q, (int) $request->page)
        );
    }

    /** Toàn bộ tên hoá chất cho bảng "Chọn Tên Hoá Chất Từ Dữ Liệu Gốc" - nạp khi mở bảng lần đầu */
    public function chemicalNames()
    {
        return response()->json(['rows' => CategoryLookup::chemicalPickerRows()]);
    }
}
