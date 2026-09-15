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
 * Ngày tạo trong chu kỳ (cycle_day_mode):
 * - fixed   : ngày cố định (cycle_day) - mọi danh sách.
 * - cal_due : chỉ danh sách nội bộ gắn Đối tượng đồng bộ từ phần mềm CAL có lịch Pending của tần
 *             suất đã chọn - ngày tạo = Sch_DueDate của lịch đó (tìm qua Inst_ID của thiết bị lớn
 *             + thiết bị con), next_run_date do MaterialPeriodicRequest::refreshCalSchedule() tính.
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
        $isCal = $payload['cycle_day_mode'] === MaterialPeriodicRequest::DAY_MODE_CAL_DUE;

        $id = DB::transaction(function () use ($request, $type, $departmentId, $payload, $isCal) {
            $id = DB::table(self::LIST_TABLE)->insertGetId($payload + [
                'type' => $type,
                'department_id' => $departmentId,
                // Theo hạn lịch CAL: ngày tạo đọc từ CAL ngay bên dưới
                'next_run_date' => $isCal ? null : MaterialPeriodicRequest::scheduleRunDate($payload),
                'generated_count' => 0,
                'status_id' => 1,
                'created_by' => $this->actor(),
                'created_user_id' => $this->userId(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->insertItems($id, $request);

            if ($isCal) {
                MaterialPeriodicRequest::refreshCalSchedule(DB::table(self::LIST_TABLE)->where('id', $id)->first());
            }

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
        $isCal = $payload['cycle_day_mode'] === MaterialPeriodicRequest::DAY_MODE_CAL_DUE;

        // Đổi đối tượng / tần suất = gắn với lịch CAL khác: bỏ mốc "đã tự tạo đề nghị theo lịch CAL"
        $linkChanged = $payload['consumption_object_id'] !== ($current->consumption_object_id === null ? null : (int) $current->consumption_object_id)
            || $payload['frequency'] !== $current->frequency;

        // Đổi lịch (cách chọn ngày / chu kỳ / số ngày / ngày trong chu kỳ / ngày bắt đầu) thì tính lại ngày tạo
        // kế tiếp, không đổi thì giữ nguyên lịch cũ
        $scheduleChanged = $linkChanged
            || $payload['cycle_day_mode'] !== MaterialPeriodicRequest::dayModeOf($current->cycle_day_mode)
            || $payload['cal_lead_days'] !== MaterialPeriodicRequest::calLeadDays($current)
            || $payload['periodic'] !== $current->periodic
            || $payload['cycle_day'] !== (int) $current->cycle_day
            || $payload['cycle_length'] !== ($current->cycle_length === null ? null : (int) $current->cycle_length)
            || $payload['start_date'] !== (string) $current->start_date
            || ! $current->next_run_date;

        if ($linkChanged) {
            $payload += ['last_cal_sch_id' => null, 'last_cal_due_date' => null];
        }

        if ($isCal) {
            // next_run_date đọc lại từ CAL ngay sau khi lưu (refreshCalSchedule)
            $payload['next_run_date'] = $current->next_run_date;
        } else {
            $payload += ['cal_sch_id' => null, 'cal_due_date' => null, 'cal_checked_at' => null];
            $payload['next_run_date'] = $scheduleChanged
                ? MaterialPeriodicRequest::scheduleRunDate($payload, $current->last_generated_at)
                : $current->next_run_date;
        }

        DB::beginTransaction();

        try {
            DB::table(self::LIST_TABLE)->where('id', $current->id)->update($payload + [
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            if ($isCal && $scheduleChanged) {
                MaterialPeriodicRequest::refreshCalSchedule(DB::table(self::LIST_TABLE)->where('id', $current->id)->first());
            }

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
                // Mở khoá danh sách theo hạn lịch CAL: đọc lại lịch ở lần mở trang kế tiếp
                'cal_checked_at' => null,
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

    /**
     * Tìm đối tượng (dữ liệu gốc) cho modal "Dữ Liệu Gốc - Đối Tượng" - AJAX, tối đa 50 dòng mỗi
     * lần thay vì nhúng cả danh mục (2000+ dòng) vào trang. Gọi lại mỗi khi gõ tìm / đổi bộ lọc.
     */
    public function objects(Request $request)
    {
        if (! user_can(self::PERMISSION)) {
            return response()->json(['items' => [], 'total' => 0, 'more' => false], 403);
        }

        return response()->json(MaterialPeriodicRequest::objectSearch(
            (string) $request->q,
            $request->type ?: null,
            $request->frequency ?: null,
            [(int) $request->keep_id],
            50,
            (int) $request->offset
        ));
    }

    /**
     * Lịch Pending bên CAL của đối tượng + tần suất đang chọn và ngày tạo đề nghị sẽ theo - AJAX cho
     * tuỳ chọn "Theo ngày đến hạn lịch CAL" ở modal thêm / sửa danh sách nội bộ. Sửa danh sách thì
     * gửi kèm list_id để tính tiếp từ mốc chu kỳ đã tự tạo đề nghị.
     */
    public function calSchedule(Request $request)
    {
        if (! user_can(self::PERMISSION)) {
            return response()->json(['available' => false, 'message' => 'Không có quyền.', 'schedules' => [], 'next' => null], 403);
        }

        $object = $request->filled('object_id')
            ? DB::table('consumption_objects')->where('id', (int) $request->object_id)->first()
            : null;

        return response()->json(MaterialPeriodicRequest::calSchedulePreview(
            $object,
            $request->frequency ?: null,
            $request->start_date ?: null,
            $request->filled('list_id') ? $this->findOwn((int) $request->list_id) : null,
            $request->filled('cal_lead_days') ? (int) $request->cal_lead_days : null
        ));
    }

    private function validator(Request $request, string $type, int $departmentId, $current = null)
    {
        $allowedCategoryIds = MaterialPeriodicRequest::categoryOptions($type, $departmentId)->pluck('id')->all();
        $object = null;
        $isInternal = $type === MaterialPeriodicRequest::TYPE_INTERNAL;
        $isCal = $isInternal && MaterialPeriodicRequest::dayModeOf($request->cycle_day_mode) === MaterialPeriodicRequest::DAY_MODE_CAL_DUE;

        if ($isInternal) {
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
            'periodic' => $isInternal
                ? ['nullable']
                : ['required', Rule::in(array_keys(MaterialPeriodicRequest::CYCLES))],
            'cycle_length' => [
                MaterialPeriodicRequest::hasCycleLength($periodic) ? 'required' : 'nullable',
                'integer',
                'min:1',
                'max:'.$lengthLimit,
            ],
            // Theo hạn lịch CAL: không dùng ngày cố định (ô cycle_day bị disabled nên không gửi lên)
            'cycle_day' => $isCal
                ? ['nullable']
                : [
                    'required',
                    'integer',
                    'min:1',
                    'max:'.($periodic === '' ? 9999 : MaterialPeriodicRequest::maxCycleDay($periodic, $request->cycle_length)),
                ],
            'cycle_day_mode' => ['nullable', Rule::in([MaterialPeriodicRequest::DAY_MODE_FIXED, MaterialPeriodicRequest::DAY_MODE_CAL_DUE])],
            // Theo hạn lịch CAL: tạo đề nghị trước hạn mấy ngày để chuẩn bị vật tư
            'cal_lead_days' => ['nullable', 'integer', 'min:0', 'max:'.MaterialPeriodicRequest::CAL_LEAD_DAYS_MAX],
            'start_date' => $startDateRules,
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'integer', 'distinct', Rule::in($allowedCategoryIds)],
            'items.*.requested_amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.requested_unit' => ['nullable', 'string', 'max:50'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.purpose' => ['nullable', 'string', 'max:500'],
        ];

        if (! $isInternal) {
            $rules['to_department_id'] = [
                'required',
                'integer',
                Rule::in(MaterialPeriodicRequest::departmentOptions($departmentId)->pluck('id')->all()),
            ];
        } else {
            // Danh sách nội bộ có thể không gắn với Đối tượng nào; giữ nguyên đối tượng cũ (dù đã khoá) thì cho qua
            $keepObject = $current && (int) $request->consumption_object_id === (int) $current->consumption_object_id;

            $rules['consumption_object_id'] = $keepObject
                ? ['nullable', 'integer']
                : ['nullable', 'integer', Rule::exists('consumption_objects', 'id')->where('status_id', 1)];

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

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($isCal) {
            $validator->after(function ($validator) use ($request, $object, $current) {
                if ($validator->errors()->hasAny(['consumption_object_id', 'frequency'])) {
                    return;
                }

                // Sửa danh sách đã theo hạn lịch CAL, giữ nguyên đối tượng + tần suất: cho lưu dù CAL đang
                // chưa có lịch Pending (đã tự tạo đề nghị chu kỳ này, chờ lịch chu kỳ sau)
                $unchangedCal = $current
                    && MaterialPeriodicRequest::dayModeOf($current->cycle_day_mode) === MaterialPeriodicRequest::DAY_MODE_CAL_DUE
                    && (int) $request->consumption_object_id === (int) $current->consumption_object_id
                    && (string) $request->frequency === (string) $current->frequency;

                if ($unchangedCal) {
                    return;
                }

                $preview = MaterialPeriodicRequest::calSchedulePreview(
                    $object,
                    (string) $request->frequency,
                    (string) $request->start_date,
                    $current,
                    $request->filled('cal_lead_days') ? (int) $request->cal_lead_days : null
                );

                if (! $preview['available']) {
                    $validator->errors()->add('cycle_day_mode', 'Không dùng được "Theo ngày đến hạn lịch CAL": '.$preview['message']);
                }
            });
        }

        return $validator;
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
            'cycle_day_mode.in' => 'Cách chọn ngày tạo đề nghị không hợp lệ.',
            'cal_lead_days.integer' => 'Số ngày tạo trước hạn phải là số nguyên.',
            'cal_lead_days.min' => 'Số ngày tạo trước hạn không được âm.',
            'cal_lead_days.max' => 'Số ngày tạo trước hạn tối đa '.MaterialPeriodicRequest::CAL_LEAD_DAYS_MAX.' ngày.',
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
        $isInternal = $type === MaterialPeriodicRequest::TYPE_INTERNAL;
        $dayMode = $isInternal
            ? MaterialPeriodicRequest::dayModeOf($request->cycle_day_mode)
            : MaterialPeriodicRequest::DAY_MODE_FIXED;

        return [
            'title' => trim((string) $request->title),
            'periodic' => $periodic,
            'cycle_length' => MaterialPeriodicRequest::hasCycleLength($periodic) ? (int) $request->cycle_length : null,
            // Theo hạn lịch CAL không dùng ngày cố định - giữ 1 cho cột NOT NULL
            'cycle_day' => $dayMode === MaterialPeriodicRequest::DAY_MODE_CAL_DUE ? 1 : (int) $request->cycle_day,
            'cycle_day_mode' => $dayMode,
            // Giữ nguyên giá trị đã nhập dù đang ở chế độ ngày cố định, để chọn lại CAL sau không mất
            'cal_lead_days' => $request->filled('cal_lead_days')
                ? max(0, min(MaterialPeriodicRequest::CAL_LEAD_DAYS_MAX, (int) $request->cal_lead_days))
                : MaterialPeriodicRequest::CAL_LEAD_DAYS_DEFAULT,
            'start_date' => \Carbon\Carbon::parse($request->start_date)->toDateString(),
            'to_department_id' => $isInternal ? null : (int) $request->to_department_id,
            'consumption_object_id' => $isInternal && $request->filled('consumption_object_id')
                ? (int) $request->consumption_object_id
                : null,
            'frequency' => $isInternal ? (string) $request->frequency : null,
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
