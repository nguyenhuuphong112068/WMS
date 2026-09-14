<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Concerns\RequiresChangeReason;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\MaterialPeriodicRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * DANH MỤC - VẬT TƯ, 2 TAB "DANH SÁCH VẬT TƯ ĐỀ NGHỊ ... THEO CHU KỲ"
 *
 * - Tab "Danh sách vật tư đề nghị nội bộ theo chu kỳ"       : type = internal
 * - Tab "Danh sách vật tư đề nghị liên phòng ban theo chu kỳ": type = external
 *
 * Mỗi danh sách là một đề nghị cấp phát lập sẵn. Đến ngày next_run_date hệ thống tự tạo
 * một đề nghị Lưu tạm (xem App\Support\MaterialPeriodicRequest), người đề nghị điều chỉnh
 * rồi trình ký / gửi đi ở màn Sử Dụng Vật Tư.
 *
 * Trang hiển thị do MaterialCategoryController::index() dựng; controller này nhận các thao
 * tác thêm / sửa / khoá / tạo đề nghị ngay và trả lịch sử thay đổi. Sửa / Khoá / Mở khoá
 * bắt buộc nhập lý do điều chỉnh như mọi màn Danh Mục.
 */
class PeriodicRequestController extends Controller
{
    use RequiresChangeReason;

    private const LIST_TABLE = MaterialPeriodicRequest::LIST_TABLE;

    private const ITEM_TABLE = MaterialPeriodicRequest::ITEM_TABLE;

    private const LABEL = 'danh sách đề nghị theo chu kỳ';

    private const PERMISSION = 'category_material_periodic_manage';

    public function store(Request $request)
    {
        $type = MaterialPeriodicRequest::typeOf($request->type);

        if ($stop = $this->guardPermission($type)) {
            return $stop;
        }

        $departmentId = $this->departmentId();
        $validator = $this->validator($request, $type, $departmentId);

        if ($validator->fails()) {
            return $this->backWithErrors($validator, $type, 'Create');
        }

        $payload = $this->payload($request, $type);

        $id = DB::transaction(function () use ($request, $type, $departmentId, $payload) {
            $id = DB::table(self::LIST_TABLE)->insertGetId($payload + [
                'type' => $type,
                'department_id' => $departmentId,
                'next_run_date' => MaterialPeriodicRequest::scheduleRunDate($payload),
                'generated_count' => 0,
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_user_id' => $this->userId(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->insertItems($id, $request);

            MaterialPeriodicRequest::writeHistory($id, 'Thêm mới', 'Khai báo mới '.self::LABEL.'.');

            return $id;
        });

        AuditTrialController::log('Thêm mới', self::LIST_TABLE, $id, 'NA', MaterialPeriodicRequest::describe($id));

        return redirect()->back()
            ->with('success', 'Đã thêm '.self::LABEL.' "'.$payload['title'].'"!')
            ->with('activeTab', $type);
    }

    public function update(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần cập nhật!');
        }

        $type = $current->type;

        if ($stop = $this->guardPermission($type)) {
            return $stop;
        }

        $validator = $this->validator($request, $type, $this->departmentId(), $current);

        if ($validator->fails()) {
            return $this->backWithErrors($validator, $type, 'Update');
        }

        $before = MaterialPeriodicRequest::snapshot($current->id);
        $payload = $this->payload($request, $type);

        // Đổi lịch (chu kỳ / số ngày / ngày trong chu kỳ / ngày bắt đầu) thì tính lại ngày tạo
        // kế tiếp, không đổi thì giữ nguyên lịch cũ
        $scheduleChanged = $payload['frequency'] !== $current->frequency
            || $payload['periodic'] !== $current->periodic
            || $payload['cycle_day'] !== (int) $current->cycle_day
            || $payload['cycle_length'] !== ($current->cycle_length === null ? null : (int) $current->cycle_length)
            || $payload['start_date'] !== (string) $current->start_date
            || ! $current->next_run_date;

        $payload['next_run_date'] = $scheduleChanged
            ? MaterialPeriodicRequest::scheduleRunDate($payload, $current->last_generated_at)
            : $current->next_run_date;

        DB::beginTransaction();

        try {
            DB::table(self::LIST_TABLE)->where('id', $current->id)->update($payload + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            // Không xoá cứng: bỏ hiệu lực các dòng cũ rồi ghi lại từ đầu
            DB::table(self::ITEM_TABLE)
                ->where('periodic_request_list_id', $current->id)
                ->where('active', 1)
                ->update(['active' => 0, 'updated_at' => now()]);

            $this->insertItems($current->id, $request);

            $note = MaterialPeriodicRequest::diffNote($before, MaterialPeriodicRequest::snapshot($current->id));

            if ($note === '') {
                DB::rollBack();

                return redirect()->back()
                    ->with('error', 'Chưa có thông tin nào thay đổi nên không lưu.')
                    ->with('activeTab', $type);
            }

            MaterialPeriodicRequest::writeHistory($current->id, 'Cập nhật', $note, $this->changeReason($request));

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        AuditTrialController::log('Cập nhật', self::LIST_TABLE, $current->id, $note, MaterialPeriodicRequest::describe($current->id));

        return redirect()->back()
            ->with('success', 'Cập nhật '.self::LABEL.' "'.$payload['title'].'" thành công!')
            ->with('activeTab', $type);
    }

    public function deActive(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current) {
            return redirect()->back()->with('error', 'Không tìm thấy '.self::LABEL.' cần thay đổi trạng thái!');
        }

        if ($stop = $this->guardPermission($current->type) ?? $this->guardChangeReason($request)) {
            return $stop;
        }

        $newStatus = (int) $current->status_id === 1 ? 0 : 1;
        $action = $newStatus === 1 ? 'Mở khoá' : 'Khoá';

        DB::transaction(function () use ($current, $newStatus, $action, $request) {
            DB::table(self::LIST_TABLE)->where('id', $current->id)->update([
                'status_id' => $newStatus,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            MaterialPeriodicRequest::writeHistory(
                $current->id,
                $action,
                'Trạng thái: '.($newStatus === 1 ? 'Đã khoá -> Đang dùng' : 'Đang dùng -> Đã khoá'),
                $this->changeReason($request)
            );
        });

        AuditTrialController::log($action, self::LIST_TABLE, $current->id, 'status_id: '.$current->status_id, 'status_id: '.$newStatus);

        return redirect()->back()
            ->with('success', ($newStatus === 1 ? 'Đã mở khoá ' : 'Đã khoá ').self::LABEL.' "'.$current->title.'"!')
            ->with('activeTab', $current->type);
    }

    /**
     * Tạo ngay một đề nghị Lưu tạm từ danh sách, không chờ tới chu kỳ. Lịch tự động giữ
     * nguyên; bộ đếm số lần đã tạo vẫn tăng.
     */
    public function generateNow(Request $request)
    {
        $current = $this->findOwn($request->id);

        if (! $current || (int) $current->status_id !== 1) {
            return redirect()->back()->with('error', 'Danh sách không tồn tại hoặc đã bị khoá nên không tạo được đề nghị!');
        }

        if ($stop = $this->guardPermission($current->type)) {
            return $stop;
        }

        $result = MaterialPeriodicRequest::generateNow((int) $current->id, $this->actor(), $this->userId());

        if (! $result) {
            return redirect()->back()
                ->with('error', 'Danh sách "'.$current->title.'" chưa có vật tư nào nên không tạo được đề nghị!')
                ->with('activeTab', $current->type);
        }

        $isExternal = $current->type === MaterialPeriodicRequest::TYPE_EXTERNAL;

        AuditTrialController::log(
            'Tạo đề nghị từ danh sách theo chu kỳ',
            self::LIST_TABLE,
            $current->id,
            'NA',
            'Tạo đề nghị '.$result['code'].' (Lưu tạm) từ danh sách "'.$current->title.'"'
        );

        return redirect()
            ->route('pages.export.materialExport.list', ['tab' => $isExternal ? 'transfer' : 'request'])
            ->with('success', 'Đã tạo đề nghị '.$result['code'].' ở trạng thái Lưu tạm. Kiểm tra, điều chỉnh rồi '
                .($isExternal ? 'gửi đề nghị.' : 'trình ký.'));
    }

    /** Lịch sử thay đổi của một danh sách cho modal lịch sử dùng chung. */
    public function history(Request $request)
    {
        $current = $this->findOwn($request->id);

        return response()->json([
            'rows' => $current ? MaterialPeriodicRequest::historyRows((int) $current->id) : [],
        ]);
    }

    private function validator(Request $request, string $type, int $departmentId, $current = null)
    {
        $allowedCategoryIds = MaterialPeriodicRequest::categoryOptions($type, $departmentId)->pluck('id')->all();
        $object = null;

        if ($type === MaterialPeriodicRequest::TYPE_INTERNAL) {
            $object = $request->filled('consumption_object_id')
                ? DB::table('consumption_objects')->where('id', (int) $request->consumption_object_id)->first()
                : null;

            // Danh sách nội bộ dùng tần suất của đối tượng: lịch suy từ mã tần suất, không nhận chu kỳ gửi từ form
            $schedule = MaterialPeriodicRequest::FREQUENCY_SCHEDULES[(string) $request->frequency] ?? [null, null];
            $request->merge(['periodic' => $schedule[0], 'cycle_length' => $schedule[1]]);
        }

        $periodic = (string) $request->periodic;
        $lengthLimit = MaterialPeriodicRequest::CYCLE_LENGTH_LIMITS[$periodic] ?? MaterialPeriodicRequest::CYCLE_MAX_LENGTH;

        // Ngày bắt đầu mới khai (hoặc vừa đổi) không được lùi về quá khứ; giữ nguyên ngày cũ thì cho qua
        $startDateRules = ['required', 'date'];
        if (! $current || (string) $request->start_date !== (string) $current->start_date) {
            $startDateRules[] = 'after_or_equal:today';
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            // Nội bộ: thiếu / sai tần suất thì chỉ báo lỗi ở ô tần suất, không báo trùng ở chu kỳ
            'periodic' => $type === MaterialPeriodicRequest::TYPE_INTERNAL
                ? ['nullable']
                : ['required', Rule::in(array_keys(MaterialPeriodicRequest::CYCLES))],
            'cycle_length' => [
                MaterialPeriodicRequest::hasCycleLength($periodic) ? 'required' : 'nullable',
                'integer',
                'min:1',
                'max:'.$lengthLimit,
            ],
            'cycle_day' => [
                'required',
                'integer',
                'min:1',
                'max:'.($periodic === '' ? 9999 : MaterialPeriodicRequest::maxCycleDay($periodic, $request->cycle_length)),
            ],
            'start_date' => $startDateRules,
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'integer', 'distinct', Rule::in($allowedCategoryIds)],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.purpose' => ['nullable', 'string', 'max:500'],
        ];

        if ($type === MaterialPeriodicRequest::TYPE_EXTERNAL) {
            $rules['to_department_id'] = [
                'required',
                'integer',
                Rule::in(MaterialPeriodicRequest::departmentOptions($departmentId)->pluck('id')->all()),
            ];
        } else {
            // Danh sách nội bộ gắn với một Đối tượng đang hoạt động; giữ nguyên đối tượng cũ (dù đã khoá) thì cho qua
            $keepObject = $current && (int) $request->consumption_object_id === (int) $current->consumption_object_id;

            $rules['consumption_object_id'] = $keepObject
                ? ['required', 'integer']
                : ['required', 'integer', Rule::exists('consumption_objects', 'id')->where('status_id', 1)];

            // Tần suất phải là một tần suất của đối tượng; giữ nguyên đối tượng thì tần suất đang dùng vẫn hợp lệ
            // dù đồng bộ CAL sau này đã bỏ tần suất đó khỏi đối tượng
            $allowedFrequencies = MaterialPeriodicRequest::objectFrequencies($object);

            if ($keepObject && $current->frequency) {
                $allowedFrequencies[] = $current->frequency;
            }

            $rules['frequency'] = ['required', Rule::in(array_values(array_unique($allowedFrequencies)))];
        }

        $messages = $this->messages($type);

        if ($current) {
            $rules += $this->changeReasonRules();
            $messages += $this->changeReasonMessages();
        }

        return Validator::make($request->all(), $rules, $messages);
    }

    private function messages(string $type): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề danh sách.',
            'title.max' => 'Tiêu đề tối đa 255 ký tự.',
            'periodic.required' => 'Vui lòng chọn chu kỳ.',
            'periodic.in' => 'Chu kỳ không hợp lệ.',
            'cycle_length.required' => 'Vui lòng nhập độ dài của chu kỳ (số ngày / số năm).',
            'cycle_length.integer' => 'Độ dài của chu kỳ phải là số nguyên.',
            'cycle_length.min' => 'Độ dài của chu kỳ tối thiểu là 1.',
            'cycle_length.max' => 'Chu kỳ theo số ngày tối đa '.MaterialPeriodicRequest::CYCLE_LENGTH_LIMITS['days']
                .' ngày, theo số năm tối đa '.MaterialPeriodicRequest::CYCLE_LENGTH_LIMITS['year'].' năm.',
            'frequency.required' => 'Vui lòng chọn tần suất đề nghị.',
            'frequency.in' => 'Tần suất đề nghị không nằm trong tần suất của đối tượng đã chọn.',
            'cycle_day.required' => 'Vui lòng chọn ngày tạo đề nghị trong chu kỳ.',
            'cycle_day.integer' => 'Ngày tạo đề nghị trong chu kỳ phải là số nguyên.',
            'cycle_day.min' => 'Ngày tạo đề nghị không nằm trong chu kỳ đã chọn.',
            'cycle_day.max' => 'Ngày tạo đề nghị không nằm trong chu kỳ đã chọn.',
            'start_date.required' => 'Vui lòng chọn ngày bắt đầu chu kỳ đầu tiên.',
            'start_date.date' => 'Ngày bắt đầu chu kỳ đầu tiên không hợp lệ.',
            'start_date.after_or_equal' => 'Ngày bắt đầu chu kỳ đầu tiên không được trước hôm nay.',
            'to_department_id.required' => 'Vui lòng chọn phòng cấp phát.',
            'to_department_id.in' => 'Phòng cấp phát không hợp lệ.',
            'consumption_object_id.required' => 'Vui lòng chọn đối tượng.',
            'consumption_object_id.integer' => 'Đối tượng không hợp lệ.',
            'consumption_object_id.exists' => 'Đối tượng không tồn tại hoặc đã bị khoá.',
            'items.required' => 'Vui lòng thêm ít nhất một vật tư.',
            'items.min' => 'Vui lòng thêm ít nhất một vật tư.',
            'items.*.category_id.required' => 'Vui lòng chọn vật tư cho mọi dòng.',
            'items.*.category_id.distinct' => 'Một vật tư chỉ được khai một dòng trong danh sách.',
            'items.*.category_id.in' => $type === MaterialPeriodicRequest::TYPE_INTERNAL
                ? 'Có vật tư chưa khai ở tab "Vật Tư Của Phòng" hoặc đã bị khoá.'
                : 'Có vật tư chưa được duyệt hoặc đã bị khoá trong danh mục công ty.',
            'items.*.requested_amount.required' => 'Vui lòng nhập số lượng đề nghị.',
            'items.*.requested_amount.numeric' => 'Số lượng đề nghị phải là số.',
            'items.*.requested_amount.min' => 'Số lượng đề nghị phải lớn hơn 0.',
            'items.*.product_name.max' => 'Thiết bị liên quan tối đa 255 ký tự.',
            'items.*.purpose.max' => 'Mục đích sử dụng tối đa 500 ký tự.',
        ];
    }

    private function payload(Request $request, string $type): array
    {
        $periodic = (string) $request->periodic;

        return [
            'title' => trim((string) $request->title),
            'periodic' => $periodic,
            'cycle_length' => MaterialPeriodicRequest::hasCycleLength($periodic) ? (int) $request->cycle_length : null,
            'cycle_day' => (int) $request->cycle_day,
            'start_date' => \Carbon\Carbon::parse($request->start_date)->toDateString(),
            'to_department_id' => $type === MaterialPeriodicRequest::TYPE_EXTERNAL ? (int) $request->to_department_id : null,
            'consumption_object_id' => $type === MaterialPeriodicRequest::TYPE_INTERNAL ? (int) $request->consumption_object_id : null,
            'frequency' => $type === MaterialPeriodicRequest::TYPE_INTERNAL ? (string) $request->frequency : null,
        ];
    }

    private function insertItems(int $listId, Request $request): void
    {
        foreach ((array) $request->items as $item) {
            DB::table(self::ITEM_TABLE)->insert([
                'periodic_request_list_id' => $listId,
                'category_id' => (int) $item['category_id'],
                'product_name' => $this->nullIfBlank($item['product_name'] ?? null),
                'purpose' => $this->nullIfBlank($item['purpose'] ?? null),
                'requested_amount' => (float) ($item['requested_amount'] ?? 0),
                'requested_unit' => $this->nullIfBlank($item['requested_unit'] ?? null),
                'active' => 1,
                'created_by' => $this->actor(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function backWithErrors($validator, string $type, string $mode)
    {
        return redirect()->back()
            ->withErrors($validator, MaterialPeriodicRequest::errorBag($type, $mode))
            ->withInput()
            ->with('error', $validator->errors()->first())
            ->with('activeTab', $type);
    }

    private function guardPermission(string $type)
    {
        if (user_can(self::PERMISSION)) {
            return null;
        }

        return redirect()->back()
            ->with('error', 'Bạn không có quyền quản lý danh sách đề nghị vật tư theo chu kỳ!')
            ->with('activeTab', $type);
    }

    /** Chỉ thao tác được trên danh sách của phòng ban đang chọn. */
    private function findOwn($id)
    {
        return DB::table(self::LIST_TABLE)
            ->where('id', $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    private function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    private function userId(): ?int
    {
        return (int) (session('user')['userId'] ?? 0) ?: null;
    }

    private function actor(): string
    {
        return \App\Support\Signer::actor();
    }

    private function nullIfBlank($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
