<?php

namespace App\Http\Controllers\Pages\Database;

use App\Http\Controllers\Controller;
use App\Support\DatabaseSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CƠ SỞ DỮ LIỆU - xem cấu trúc bảng, trường và quan hệ dữ liệu theo code.
 *
 * Chỉ tài khoản Admin (middleware role:Admin ở route). Không sửa dữ liệu: mọi thông tin
 * dựng lại từ CSDL thật + mã nguồn ở mỗi lần mở trang (xem App\Support\DatabaseSchema).
 */
class DatabaseController extends Controller
{
    public function index(Request $request)
    {
        $schema = DatabaseSchema::build($request->boolean('refresh'));

        // Đường dẫn mở file trong VS Code - chỉ có ý nghĩa trên máy lập trình
        $editor = app()->environment('local')
            ? 'vscode://file/' . str_replace('\\', '/', base_path()) . '/'
            : null;

        session()->put(['title' => 'CƠ SỞ DỮ LIỆU']);

        return view('pages.database.list', [
            'schema' => $schema,
            'editor' => $editor,
        ]);
    }

    /** Dấu vân tay cấu trúc + code: trang so sánh định kỳ để báo "đã thay đổi" */
    public function signature()
    {
        return response()->json(['signature' => DatabaseSchema::signature()]);
    }

    /** Số dòng chính xác của một bảng (information_schema chỉ cho số ước lượng) */
    public function rows(Request $request)
    {
        $table = (string) $request->query('table', '');

        if (! in_array($table, DatabaseSchema::tableNames(), true)) {
            return response()->json(['message' => 'Bảng không tồn tại'], 404);
        }

        return response()->json([
            'table' => $table,
            'rows'  => DB::table($table)->count(),
        ]);
    }
}
