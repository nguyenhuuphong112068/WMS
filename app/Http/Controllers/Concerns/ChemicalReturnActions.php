<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\ChemicalReturn;
use App\Support\DepartmentChemical;
use App\Support\ListRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * SỬ DỤNG HOÁ CHẤT - TAB "TRẢ HOÁ CHẤT ACETONITRIL HOẶC METHANOL VỀ KHO".
 *
 * Dùng trong ChemicalExportController (cần logHistory(), lockImport(), number(), actor(),
 * departmentId()). Lượng trả lưu thành dòng chemical_exports type = 'return' với amount ÂM
 * và return_of_id = phiếu sử dụng gốc - xem App\Support\ChemicalReturn.
 */
trait ChemicalReturnActions
{
    /** Tiền tố tham số lọc / phân trang của tab. */
    private const RETURN_PREFIX = 'ret_';

    /** Hai nhóm xem của tab: phiếu sử dụng còn trả được / lịch sử trả về kho. */
    public const RETURN_VIEWS = ['usage', 'history'];

    /** Dữ liệu tab Trả hoá chất về kho cho index(). */
    private function returnPaneData(int $departmentId, Request $request): array
    {
        $range = ListRange::of($request, self::RETURN_PREFIX);
        $keyword = ListRange::keyword($request, self::RETURN_PREFIX);
        $perPage = ListRange::perPage($request, self::RETURN_PREFIX);
        $view = in_array($request->input('rview'), self::RETURN_VIEWS, true) ? $request->input('rview') : 'usage';
        $categoryIds = ChemicalReturn::categoryIds() ?: [0];

        $base = fn () => DB::table(self::TABLE)
            ->join('chemical_imports', self::TABLE.'.import_id', '=', 'chemical_imports.id')
            ->leftJoin('chemical_categories', 'chemical_imports.category_id', '=', 'chemical_categories.id')
            ->leftJoin('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->tap(fn ($query) => DepartmentChemical::joinUnit($query, $departmentId, 'chemical_imports.category_id'))
            ->where(self::TABLE.'.department_id', $departmentId)
            ->whereIn('chemical_imports.category_id', $categoryIds)
            ->tap(ListRange::dateFilter(self::TABLE.'.exported_date', $range))
            ->tap(ListRange::search([
                'chemical_imports.code',
                'chemical_imports.batch_no',
                'chemical_categories.code',
                'chem_names.name',
                self::TABLE.'.purpose',
                self::TABLE.'.exported_by',
            ], $keyword));

        $columns = [
            self::TABLE.'.*',
            'chemical_imports.category_id',
            'chemical_imports.batch_no',
            'chemical_imports.status_id as import_status_id',
            'chemical_categories.code as category_code',
            'chem_names.name as chem_name',
            'units.short_name as unit_short_name',
            'units.name as unit_name',
        ];

        if ($view === 'history') {
            $rows = $base()
                ->leftJoin(self::TABLE.' as sources', self::TABLE.'.return_of_id', '=', 'sources.id')
                ->select(array_merge($columns, [
                    'sources.amount as source_amount',
                    'sources.exported_date as source_date',
                    'sources.exported_by as source_by',
                    'sources.purpose as source_purpose',
                ]))
                ->where(self::TABLE.'.type', ChemicalReturn::TYPE)
                ->orderByDesc(self::TABLE.'.created_at')
                ->orderByDesc(self::TABLE.'.id')
                ->paginate($perPage, ['*'], ListRange::pageName(self::RETURN_PREFIX))
                ->withQueryString();
        } else {
            $rows = $base()
                ->select($columns)
                ->where(self::TABLE.'.type', self::ISSUE_TYPE)
                ->where(self::TABLE.'.status_id', 1)
                ->orderByDesc(self::TABLE.'.exported_date')
                ->orderByDesc(self::TABLE.'.id')
                ->paginate($perPage, ['*'], ListRange::pageName(self::RETURN_PREFIX))
                ->withQueryString();

            $exportIds = collect($rows->items())->pluck('id')->all();
            $returned = ChemicalReturn::returnedByExport($exportIds);

            // Lý do trả về của các lần trả còn hiệu lực, theo thứ tự trả
            $reasons = $exportIds
                ? DB::table(self::TABLE)
                    ->whereIn('return_of_id', $exportIds)
                    ->where('type', ChemicalReturn::TYPE)
                    ->where('status_id', 1)
                    ->whereNotNull('purpose')
                    ->orderBy('id')
                    ->get(['return_of_id', 'purpose'])
                    ->groupBy('return_of_id')
                : collect();

            foreach ($rows as $row) {
                $row->returned = (float) ($returned[$row->id] ?? 0);
                $row->returnable = max((float) $row->amount - $row->returned, 0);
                $row->return_reasons = collect($reasons[$row->id] ?? [])->pluck('purpose')->all();
            }
        }

        return [
            'rows' => $rows,
            'range' => $range,
            'keyword' => $keyword,
            'perPage' => $perPage,
            'view' => $view,
        ];
    }

    /** Khai báo trả một lượng Acetonitril / Methanol của một phiếu sử dụng về kho. */
    public function returnStore(Request $request)
    {
        if (! user_can(ChemicalReturn::PERMISSION)) {
            return redirect()->back()->with('error', 'Bạn không có quyền trả hoá chất về kho!');
        }

        $departmentId = $this->departmentId();

        $validator = Validator::make($request->all(), [
            'export_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'note' => ['required', 'max:500'],
        ], [
            'export_id.required' => 'Vui lòng chọn phiếu sử dụng cần trả về kho.',
            'amount.required' => 'Vui lòng nhập lượng trả về kho.',
            'amount.numeric' => 'Lượng trả về kho phải là số.',
            'amount.min' => 'Lượng trả về kho phải lớn hơn 0.',
            'note.required' => 'Vui lòng nhập lý do trả về.',
            'note.max' => 'Lý do trả về tối đa 500 ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'returnErrors')->withInput()->with('activeTab', 'return');
        }

        try {
            $code = DB::transaction(function () use ($request, $departmentId) {
                $source = DB::table(self::TABLE)
                    ->where('id', $request->export_id)
                    ->where('department_id', $departmentId)
                    ->lockForUpdate()
                    ->first();

                $fail = fn (string $message) => throw ValidationException::withMessages(['amount' => $message]);

                if (! $source || $source->type !== self::ISSUE_TYPE || (int) $source->status_id !== 1) {
                    $fail('Phiếu sử dụng không tồn tại, đã bị khoá hoặc không phải phiếu Sử dụng.');
                }

                $this->lockImport($source->import_id);
                $import = DB::table('chemical_imports')->where('id', $source->import_id)->first();

                if (! $import || (int) $import->status_id !== 1) {
                    $fail('Lô '.$source->code.' đã bị khoá nên không trả về kho được.');
                }

                if (! ChemicalReturn::isReturnableCategory($import->category_id)) {
                    $fail('Chỉ trả về kho được hoá chất Acetonitril hoặc Methanol.');
                }

                $amount = (float) $request->amount;
                $returnable = max((float) $source->amount - ChemicalReturn::returnedOf((int) $source->id), 0);

                if ($amount > $returnable + ChemicalReturn::EPSILON) {
                    $fail('Lượng trả vượt lượng đã sử dụng: phiếu này chỉ còn trả được tối đa '.$this->number($returnable).'.');
                }

                $note = trim((string) $request->note);

                $id = DB::table(self::TABLE)->insertGetId([
                    'code' => $source->code,
                    'import_id' => $source->import_id,
                    'department_id' => $departmentId,
                    'amount' => -$amount,
                    'type' => ChemicalReturn::TYPE,
                    'return_of_id' => $source->id,
                    'exported_date' => now()->format('Y-m-d'),
                    'exported_by' => $this->actor(),
                    'purpose' => $note === '' ? null : $note,
                    'status_id' => 1,
                    'created_by' => $this->actor(),
                    'created_at' => now(),
                ]);

                $detail = 'Trả về kho '.$this->number($amount).' từ phiếu sử dụng ngày '
                    .\Carbon\Carbon::parse($source->exported_date)->format('d/m/Y')
                    .' ('.$this->number((float) $source->amount).')'.($note !== '' ? '. Lý do trả về: '.$note : '');

                $this->logHistory($id, 'Thêm mới', $detail);
                AuditTrialController::log('Trả về kho', self::TABLE, $id, $source->code, $detail);

                return $source->code;
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator, 'returnErrors')->withInput()->with('activeTab', 'return');
        }

        return redirect()->back()->with('success', 'Đã trả hoá chất của mã '.$code.' về kho!')->with('activeTab', 'return');
    }

    /** Huỷ một phiếu trả về kho: khoá dòng trả, tồn của lô bị trừ lại đúng lượng đã trả. */
    public function returnCancel(Request $request)
    {
        if (! user_can(ChemicalReturn::PERMISSION)) {
            return redirect()->back()->with('error', 'Bạn không có quyền huỷ phiếu trả hoá chất về kho!');
        }

        $departmentId = $this->departmentId();

        $error = DB::transaction(function () use ($request, $departmentId) {
            $row = DB::table(self::TABLE)
                ->where('id', $request->id)
                ->where('department_id', $departmentId)
                ->where('type', ChemicalReturn::TYPE)
                ->lockForUpdate()
                ->first();

            if (! $row || (int) $row->status_id !== 1) {
                return 'Không tìm thấy phiếu trả về kho còn hiệu lực cần huỷ!';
            }

            $this->lockImport($row->import_id);
            $import = DB::table('chemical_imports')->where('id', $row->import_id)->first();

            // Tồn thật của lô (không chặn ở 0): đã đem dùng tiếp lượng trả thì không huỷ được
            $stock = $import
                ? (float) $import->amount
                    + (float) DB::table('chemical_balancings')->where('import_id', $import->id)->where('status_id', 1)->sum('balancing_amount')
                    - (float) DB::table(self::TABLE)->where('import_id', $import->id)->where('status_id', 1)->sum('amount')
                : 0;
            $returned = -(float) $row->amount;

            if ($stock + ChemicalReturn::EPSILON < $returned) {
                return 'Không huỷ được: lô '.$row->code.' chỉ còn tồn '.$this->number(max($stock, 0))
                    .' trong khi phiếu trả là '.$this->number($returned).' (lượng trả đã được sử dụng tiếp).';
            }

            DB::table(self::TABLE)->where('id', $row->id)->update([
                'status_id' => 0,
                'updated_by' => $this->actor(),
                'updated_at' => now(),
            ]);

            $reason = trim((string) $request->change_reason);
            $note = 'Huỷ phiếu trả về kho '.$this->number($returned).($reason !== '' ? '. Lý do: '.$reason : '');

            $this->logHistory($row->id, 'Huỷ trả về kho', $note);
            AuditTrialController::log('Huỷ trả về kho', self::TABLE, $row->id, $row->code, $note);

            return null;
        });

        if ($error !== null) {
            return redirect()->back()->with('error', $error)->with('activeTab', 'return');
        }

        return redirect()->back()->with('success', 'Đã huỷ phiếu trả về kho, tồn của lô đã được trừ lại!')->with('activeTab', 'return');
    }

    /**
     * Chặn ở Sổ sử dụng: dòng trả về kho không sửa / khoá ở đó; phiếu sử dụng đã có lượng trả
     * không khoá, không đổi lô được.
     *
     * @return \Illuminate\Http\RedirectResponse|null null nghĩa là được phép đi tiếp
     */
    private function returnGuard($current, string $action)
    {
        if ($current->type === ChemicalReturn::TYPE) {
            return redirect()->back()->with(
                'error',
                'Dòng trả về kho '.$current->code.' không '.$action.' được ở Sổ sử dụng. Huỷ phiếu trả ở tab "Trả hoá chất Acetonitril hoặc Methanol về kho".'
            );
        }

        return null;
    }
}
