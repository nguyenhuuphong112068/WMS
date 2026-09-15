<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Support\CategoryLookup;
use Illuminate\Http\Request;

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

    /** ?type=material|chemical|standard&q=&page= : định khu của phòng ban đang chọn */
    public function locations(Request $request)
    {
        $type = (string) $request->type;
        $departmentId = (int) (session('user')['selected_department_id'] ?? 0);

        if (! in_array($type, CategoryLookup::ITEM_TYPES, true) || ! $departmentId) {
            return response()->json(CategoryLookup::emptyResponse(), 422);
        }

        return response()->json(
            CategoryLookup::searchLocations($type, $departmentId, (string) $request->q, (int) $request->page)
        );
    }

    /** Toàn bộ tên hoá chất cho bảng "Chọn Tên Hoá Chất Từ Dữ Liệu Gốc" - nạp khi mở bảng lần đầu */
    public function chemicalNames()
    {
        return response()->json(['rows' => CategoryLookup::chemicalPickerRows()]);
    }
}
