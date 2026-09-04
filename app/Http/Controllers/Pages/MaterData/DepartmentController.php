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
        'company_id' => 'Công ty',
        'shortName' => 'Tên viết tắt',
        'name' => 'Tên phòng ban',
    ];

    public function index()
    {
        $datas = DB::table(self::TABLE)
            ->leftJoin('companies', self::TABLE . '.company_id', '=', 'companies.id')
            ->select(
                self::TABLE . '.*',
                'companies.name as company_name',
                'companies.short_name as company_short'
            )
            ->orderBy('companies.name', 'asc')
            ->orderBy(self::TABLE . '.name', 'asc')
            ->get();

        session()->put(['title' => 'DỮ LIỆU GỐC - PHÒNG BAN']);

        return view('pages.materData.Department.list', [
            'datas' => $datas,
            // Mỗi phòng ban gắn vào một công ty; phạm vi Ngưỡng Tồn Trữ PL IV gói theo công ty
            'companies' => \App\Support\CompanyContext::options(),
            // Số lần thay đổi của từng dòng, hiện thành badge ở góc nút Sửa
            'historyCounts' => DataMasterHistory::counts(self::TABLE),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'shortName' => 'required|unique:deparments,shortName',
            'name' => 'required|unique:deparments,name',
        ], [
            'company_id.required' => 'Vui lòng chọn Công Ty',
            'company_id.exists' => 'Công Ty không hợp lệ.',
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

        DataMasterHistory::record(self::TABLE, $id, 'Thêm mới', 'Khai báo mới phòng ban: ' . $request->name . '.', self::FIELDS, $this->maps());

        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $current = DB::table(self::TABLE)->where('id', $request->id)->first();

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy phòng ban cần cập nhật!');
        }

        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'shortName' => 'required|unique:deparments,shortName,' . $request->id,
            'name' => 'required|unique:deparments,name,' . $request->id,
        ], [
            'company_id.required' => 'Vui lòng chọn Công Ty',
            'company_id.exists' => 'Công Ty không hợp lệ.',
            'name.required' => 'Vui lòng nhập Tên Phòng Ban',
            'name.unique' => 'Tên Phòng Ban đã tồn tại.',
            'shortName.required' => 'Vui lòng nhập Tên Viết Tắt',
            'shortName.unique' => 'Tên Viết Tắt đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $payload = $this->payload($request);
        $note = DataMasterHistory::note(self::FIELDS, $current, $payload, $this->maps());

        DB::table(self::TABLE)->where('id', $current->id)->update($payload + [
            'updated_at' => now(),
        ]);

        DataMasterHistory::record(self::TABLE, $current->id, 'Cập nhật', $note ?: 'Lưu lại nhưng nội dung không đổi.', self::FIELDS, $this->maps());

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
            self::FIELDS,
            $this->maps()
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
        return \App\Support\Signer::actor();
    }

    /** Bảng tra nhãn để lịch sử hiện tên công ty thay vì company_id. */
    private function maps(): array
    {
        return ['company_id' => DB::table('companies')->pluck('name', 'id')->all()];
    }

    private function payload(Request $request): array
    {
        return [
            'company_id' => (int) $request->company_id,
            'shortName' => trim((string) $request->shortName),
            'name' => trim((string) $request->name),
        ];
    }
}
