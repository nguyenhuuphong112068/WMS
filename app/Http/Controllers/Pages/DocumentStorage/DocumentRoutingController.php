<?php

namespace App\Http\Controllers\Pages\DocumentStorage;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DocumentRoutingController extends Controller
{
    public function index()
    {
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return redirect()->route('login')->with('error', 'Phiên làm việc hết hạn, vui lòng đăng nhập lại.');
        }

        try {
            $user           = session('user');
            $selectedDeptId = $user['selected_department_id'];
            $userDeptId     = $user['department_id'] ?? $selectedDeptId;

            $datas = DB::table('document_routings')
                ->where('document_routings.department_id', $selectedDeptId)
                ->leftJoin('deparments', 'document_routings.department_id', '=', 'deparments.id')
                ->leftJoin('locations', 'document_routings.final_location_id', '=', 'locations.id')
                ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
                ->leftJoin('rooms', 'locations.room_id', '=', 'rooms.id')
                ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
                ->select(
                    'document_routings.*',
                    'deparments.name as department_name',
                    'locations.name as location_name',
                    'warehouses.name as warehouse_name',
                    'rooms.name as room_name',
                    'shelves.name as shelf_name'
                )
                ->orderBy('document_routings.created_at', 'desc')
                ->get();

            // Lấy toàn bộ chặng của các phiếu để dựng timeline
            $routingIds = $datas->pluck('id');
            $stepsMap = DB::table('document_routing_steps')
                ->whereIn('routing_id', $routingIds)
                ->orderBy('step_no', 'asc')
                ->get()
                ->groupBy('routing_id');

            $inProgressCount = 0;
            $waitingCount    = 0;
            $completedCount  = 0;

            foreach ($datas as $data) {
                $data->steps = $stepsMap[$data->id] ?? collect();

                // Chặng cuối đang chờ người nhận xác nhận?
                $lastStep = $data->steps->last();
                $data->waiting_confirm = $lastStep && $lastStep->status === 'pending';
                $data->last_step_id    = $lastStep->id ?? null;

                if ($data->status === 'completed') {
                    $completedCount++;
                } elseif ($data->status === 'in_progress') {
                    $inProgressCount++;
                    if ($data->waiting_confirm) {
                        $waitingCount++;
                    }
                }
            }

            $departments = DB::table('deparments')->where('active', true)->get();

            $users = DB::table('user_management')
                ->where('isActive', true)
                ->select('id', 'fullName', 'userName', 'deparment')
                ->orderBy('fullName', 'asc')
                ->get();

            // Vị trí lưu trữ cho bước kết thúc
            $warehouses = DB::table('warehouses')->where('active', true)->get();
            $rooms      = DB::table('rooms')->where('active', true)->get();
            $shelves    = DB::table('shelves')->where('active', true)->get();
            $locations  = DB::table('locations')
                ->where('department_id', $userDeptId)
                ->where('status_id', 1)
                ->get();

            session()->put(['title' => 'LUÂN CHUYỂN HỒ SƠ']);

            return view('pages.DocumentStorage.Routing.list', [
                'datas'           => $datas,
                'departments'     => $departments,
                'users'           => $users,
                'warehouses'      => $warehouses,
                'rooms'           => $rooms,
                'shelves'         => $shelves,
                'locations'       => $locations,
                'inProgressCount' => $inProgressCount,
                'waitingCount'    => $waitingCount,
                'completedCount'  => $completedCount,
            ]);
        } catch (\Exception $e) {
            Log::error("DocumentRouting index error: " . $e->getMessage());
            return "Lỗi Server (500): " . $e->getMessage() . " tại dòng " . $e->getLine();
        }
    }

    /**
     * BƯỚC 1: Văn thư khởi tạo phiếu luân chuyển.
     * Người tiếp nhận hồ sơ từ bộ phận khác chính là user đang thao tác.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'nullable|max:50|unique:document_routings,code',
            'name'          => 'required|max:255',
            'received_date' => 'required|date',
        ], [
            'name.required'          => 'Tên tài liệu là bắt buộc.',
            'code.unique'            => 'Mã hồ sơ này đã tồn tại.',
            'received_date.required' => 'Vui lòng chọn ngày nhận.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $user       = session('user');
        $receiverId = $user['userId'];
        $receiver   = $user['fullName'];

        // Mã hồ sơ có thể bỏ trống -> tự sinh để vẫn tra cứu được
        $code = trim((string) $request->code);
        if ($code === '') {
            $code = $this->generateCode();
        }

        DB::beginTransaction();
        try {
            $routingId = DB::table('document_routings')->insertGetId([
                'code'                   => $code,
                'name'                   => $request->name,
                'department_id'          => $user['selected_department_id'],
                'created_by_user_id'     => $user['userId'],
                'receiver_user_id'       => $receiverId,
                'receiver_name'          => $receiver,
                'received_date'          => $request->received_date,
                'status'                 => 'in_progress',
                'current_step'           => 1,
                'current_holder_user_id' => $receiverId,
                'current_holder_name'    => $receiver,
                'note'                   => $request->note,
                'created_by'             => $user['fullName'],
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            // Chặng 1: tiếp nhận hồ sơ từ bộ phận khác về tay người khởi tạo.
            // from_user_id để trống vì bên gửi nằm ngoài luồng luân chuyển nội bộ.
            DB::table('document_routing_steps')->insert([
                'routing_id'       => $routingId,
                'step_no'          => 1,
                'from_user_id'     => null,
                'from_user_name'   => null,
                'to_user_id'       => $receiverId,
                'to_user_name'     => $receiver,
                'to_department_id' => $user['selected_department_id'],
                'handover_date'    => $request->received_date,
                'received_date'    => $request->received_date,
                'status'           => 'received',
                'note'             => $request->note,
                'created_by'       => $user['fullName'],
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DocumentRouting store error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi khi tạo phiếu: ' . $e->getMessage());
        }

        AuditTrialController::log('Create', 'document_routings', $routingId, 'NA', 'Khởi tạo phiếu luân chuyển: ' . $code . ' - ' . $request->name);

        return redirect()->back()->with('success', 'Đã khởi tạo phiếu luân chuyển ' . $code . '!');
    }

    /**
     * BƯỚC 2: Chuyển tài liệu sang người tiếp theo.
     */
    public function forward(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'routing_id'    => 'required',
            'to_user_id'    => 'required',
            'handover_date' => 'required|date',
        ], [
            'to_user_id.required'    => 'Vui lòng chọn người nhận tiếp theo.',
            'handover_date.required' => 'Vui lòng chọn ngày giao.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'forwardErrors')->withInput();
        }

        $user    = session('user');
        $routing = DB::table('document_routings')->where('id', $request->routing_id)->first();

        if (!$routing) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu luân chuyển.');
        }

        if ($routing->status !== 'in_progress') {
            return redirect()->back()->with('error', 'Phiếu đã kết thúc, không thể chuyển tiếp.');
        }

        $toUser = DB::table('user_management')->where('id', $request->to_user_id)->first();
        if (!$toUser) {
            return redirect()->back()->with('error', 'Người nhận không hợp lệ.');
        }

        if ((int) $routing->current_holder_user_id === (int) $toUser->id) {
            return redirect()->back()->with('error', 'Tài liệu đang ở chỗ người này rồi.');
        }

        $nextStep = ((int) $routing->current_step) + 1;

        DB::beginTransaction();
        try {
            DB::table('document_routing_steps')->insert([
                'routing_id'       => $routing->id,
                'step_no'          => $nextStep,
                'from_user_id'     => $routing->current_holder_user_id,
                'from_user_name'   => $routing->current_holder_name,
                'to_user_id'       => $toUser->id,
                'to_user_name'     => $toUser->fullName ?? $toUser->userName,
                'to_department_id' => $this->departmentIdOf($toUser),
                'handover_date'    => $request->handover_date,
                'received_date'    => null,
                'status'           => 'pending',
                'note'             => $request->note,
                'created_by'       => $user['fullName'],
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('document_routings')->where('id', $routing->id)->update([
                'current_step'           => $nextStep,
                'current_holder_user_id' => $toUser->id,
                'current_holder_name'    => $toUser->fullName ?? $toUser->userName,
                'updated_by'             => $user['fullName'],
                'updated_at'             => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DocumentRouting forward error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi khi chuyển tiếp: ' . $e->getMessage());
        }

        AuditTrialController::log(
            'Update',
            'document_routings',
            $routing->id,
            'Đang ở: ' . $routing->current_holder_name,
            'Chuyển tới: ' . ($toUser->fullName ?? $toUser->userName) . ' (chặng ' . $nextStep . ')'
        );

        return redirect()->back()->with('success', 'Đã chuyển tài liệu tới ' . ($toUser->fullName ?? $toUser->userName) . '!');
    }

    /**
     * Người nhận xác nhận đã nhận được tài liệu ở chặng đang chờ.
     */
    public function confirmReceive(Request $request)
    {
        $user = session('user');
        $step = DB::table('document_routing_steps')->where('id', $request->step_id)->first();

        if (!$step) {
            return redirect()->back()->with('error', 'Không tìm thấy chặng luân chuyển.');
        }

        if ($step->status === 'received') {
            return redirect()->back()->with('error', 'Chặng này đã được xác nhận trước đó.');
        }

        DB::table('document_routing_steps')->where('id', $step->id)->update([
            'received_date' => $request->received_date ?: now()->toDateString(),
            'status'        => 'received',
            'updated_at'    => now(),
        ]);

        DB::table('document_routings')->where('id', $step->routing_id)->update([
            'updated_by' => $user['fullName'],
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Update', 'document_routing_steps', $step->id, 'pending', 'received - ' . $step->to_user_name);

        return redirect()->back()->with('success', 'Đã xác nhận nhận tài liệu!');
    }

    /**
     * BƯỚC 3: Kết thúc luân chuyển và xác định vị trí lưu.
     */
    public function finish(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'routing_id'  => 'required',
            'location_id' => 'required',
        ], [
            'location_id.required' => 'Vui lòng chọn vị trí lưu trữ cuối cùng.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'finishErrors')->withInput();
        }

        $user    = session('user');
        $routing = DB::table('document_routings')->where('id', $request->routing_id)->first();

        if (!$routing) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu luân chuyển.');
        }

        if ($routing->status === 'completed') {
            return redirect()->back()->with('error', 'Phiếu này đã kết thúc rồi.');
        }

        DB::beginTransaction();
        try {
            // Chặng đang chờ xác nhận thì coi như đã nhận khi kết thúc
            DB::table('document_routing_steps')
                ->where('routing_id', $routing->id)
                ->where('status', 'pending')
                ->update([
                    'received_date' => now()->toDateString(),
                    'status'        => 'received',
                    'updated_at'    => now(),
                ]);

            DB::table('document_routings')->where('id', $routing->id)->update([
                'status'            => 'completed',
                'final_location_id' => $request->location_id,
                'completed_at'      => now(),
                'completed_by'      => $user['fullName'],
                'note'              => $request->note ?: $routing->note,
                'updated_by'        => $user['fullName'],
                'updated_at'        => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DocumentRouting finish error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi khi kết thúc phiếu: ' . $e->getMessage());
        }

        $location = DB::table('locations')->where('id', $request->location_id)->first();

        AuditTrialController::log(
            'Update',
            'document_routings',
            $routing->id,
            'in_progress',
            'Kết thúc - lưu tại: ' . ($location->name ?? $request->location_id)
        );

        return redirect()->back()->with('success', 'Đã kết thúc luân chuyển và lưu tại vị trí ' . ($location->name ?? '') . '!');
    }

    /**
     * Huỷ phiếu luân chuyển.
     */
    public function cancel(Request $request)
    {
        $user    = session('user');
        $routing = DB::table('document_routings')->where('id', $request->id)->first();

        if (!$routing) {
            return redirect()->back()->with('error', 'Không tìm thấy phiếu luân chuyển.');
        }

        if ($routing->status === 'completed') {
            return redirect()->back()->with('error', 'Phiếu đã kết thúc, không thể huỷ.');
        }

        DB::table('document_routings')->where('id', $routing->id)->update([
            'status'     => 'cancelled',
            'updated_by' => $user['fullName'],
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Update', 'document_routings', $routing->id, $routing->status, 'cancelled');

        return redirect()->back()->with('success', 'Đã huỷ phiếu luân chuyển!');
    }

    /**
     * Sinh mã hồ sơ tự động dạng HS-YYYYMM-0001.
     */
    private function generateCode(): string
    {
        $prefix = 'HS-' . date('Ym') . '-';

        $last = DB::table('document_routings')
            ->where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->value('code');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * user_management lưu phòng ban bằng shortName -> đổi sang id.
     */
    private function departmentIdOf($userRow)
    {
        if (empty($userRow->deparment)) {
            return null;
        }

        return DB::table('deparments')->where('shortName', $userRow->deparment)->value('id');
    }
}
