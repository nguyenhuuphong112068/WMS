<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Support\DataMasterHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * DỮ LIỆU GỐC - PHÒNG BAN
 *
 * Bảng deparments (thiếu chữ t, sai chính tả từ đầu dự án) dùng cột isActive cho
 * trạng thái sử dụng, không có status_id như các bảng dữ liệu gốc dựng sau này.
 */
class DepartmentController extends Controller
{
    private const TABLE = 'deparments';

    /** Các cột người dùng nhập - dùng chung cho ảnh chụp và mô tả thay đổi của lịch sử. */
    private const FIELDS = [
        'shortName' => 'Tên viết tắt',
        'name' => 'Tên phòng ban',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)->orderBy('name', 'asc')->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - PHÒNG BAN']);

        return view('pages.materData.Department.list', [
            'datas' => $datas,
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shortName' => 'required|unique:deparments,shortName',
            'name' => 'required|unique:deparments,name',
        ], [
            'name.required' => 'Vui lòng nhập Tên Phòng Ban',
            'name.unique' => 'Tên Phòng Ban đã tồn tại.',
            'shortName.required' => 'Vui lòng nhập Tên Viết Tắt',
            'shortName.unique' => 'Tên Viết Tắt đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $id = DB::table(self::TABLE)->insertGetId($this->payload($request) + [
            'isActive' => 1,
            'prepareBy' => $this->actor(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới phòng ban: ' . $request->name . '.', self::FIELDS);

        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy phòng ban cần cập nhật!');
        }

        $validator = Validator::make($request->all(), [
            'shortName' => 'required|unique:deparments,shortName,' . $request->id,
            'name' => 'required|unique:deparments,name,' . $request->id,
        ], [
            'name.required' => 'Vui lòng nhập Tên Phòng Ban',
            'name.unique' => 'Tên Phòng Ban đã tồn tại.',
            'shortName.required' => 'Vui lòng nhập Tên Viết Tắt',
            'shortName.unique' => 'Tên Viết Tắt đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload);

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.', self::FIELDS);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy phòng ban cần thay đổi trạng thái!');
        }

        $newStatus = $current->isActive ? 0 : 1;

        DB::table(self::TABLE)->where('id', $current->id)->update([
            'isActive' => $newStatus,
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(
            self::TABLE,
            $current->id,
            $newStatus == 1 ? 'Mở khoá' : 'Khoá',
            DataMasterHistory::statusNote($current->isActive, $newStatus),
            self::FIELDS
        );

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }

    /** Trả về lịch sử thay đổi của một dòng cho modal xem lịch sử. */
    public function history(Request $request)
    {
        return response()->json([
            'rows' => DataMasterHistory::rows(self::TABLE, (int) $request->id),
        ]);
    }

    private function actor(): string
    {
        return session('user')['fullName'] ?? 'Admin';
    }

    private function payload(Request $request): array
    {
        return [
            'shortName' => trim((string) $request->shortName),
            'name' => trim((string) $request->name),
        ];
    }
}
