{{--
| SỬ DỤNG - TAB HOÁ CHẤT CHỜ HUỶ (BƯỚC 2 CỦA NGHIỆP VỤ HUỶ BỎ)
|
| BƯỚC 1 "Loại bỏ" làm ở tab Sổ sử dụng: lập phiếu loại Loại bỏ, hoá chất bị đánh dấu
|        loại bỏ và trừ tồn ngay, rồi rơi vào bảng "Hoá chất chờ huỷ" bên dưới.
| BƯỚC 2 "Huỷ hoá chất": chọn các phiếu chờ, gom thành MỘT đợt để xin quyết định huỷ
|        một lần từ TP. ĐBCL và Ban Giám Đốc. Có quyết định rồi thì in được biểu mẫu
|        QA/F/058-07 "Phiếu theo dõi và quyết định huỷ".
--}}

@php
    // $dspRoute khai báo ở list.blade.php để modal ở section "model" cũng dùng được
    $dspWaitingCount = $waitingDisposal->count();
@endphp

<div class="exp-pane {{ $activeTab === 'disposal' ? 'is-active' : '' }}" id="expPaneDisposal">

    <div class="md-toolbar">
        @perm('export_chemical_disposal_manage')
            <button type="button" class="btn btn-primary" id="btnDspCreate" disabled>
                <i class="fas fa-file-signature mr-1"></i> Xin quyết định huỷ (<span class="dsp-picked">0</span>)
            </button>
        @endperm
        <p class="hint">
            <i class="fas fa-info-circle mr-1"></i>
            Huỷ hoá chất đi <b>hai bước</b>. <b>Bước 1 - Loại bỏ</b>: lập phiếu <b>Loại bỏ</b> ở tab Sổ sử dụng, hoá
            chất trừ tồn ngay và về đây chờ. <b>Bước 2 - Huỷ</b>: gom nhiều phiếu thành một đợt, trình
            <b>TP. ĐBCL</b> và <b>Ban Giám Đốc</b>; có quyết định rồi thì in được biểu mẫu
            <b>{{ \App\Http\Controllers\Pages\Export\ChemicalDisposalController::FORM['form_no'] }}</b>.
        </p>
    </div>

    {{-- ---------- Hàng chờ huỷ ---------- --}}
    <h6 class="exp-req-title">
        <i class="fas fa-hourglass-half mr-1"></i> Hoá chất chờ huỷ
        <span class="md-sub">({{ $dspWaitingCount }} phiếu loại bỏ chưa gom vào đợt nào)</span>
    </h6>

    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover w-100 md-table dsp-waiting-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 46px">
                        <input type="checkbox" id="dspCheckAll" title="Chọn tất cả">
                    </th>
                    <th class="text-center" style="width: 55px">STT</th>
                    <th style="width: 125px">Mã Xuất Nhập</th>
                    <th>Tên Hoá Chất</th>
                    <th style="width: 110px">Số Lô</th>
                    <th style="width: 150px">Số PKN, OOS, BCSL</th>
                    <th class="text-right" style="width: 110px">Khối Lượng / Số Lượng</th>
                    <th class="text-center" style="width: 100px">Ngày Loại Bỏ</th>
                    <th>Lý Do Loại Bỏ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($waitingDisposal as $row)
                    <tr data-classification="{{ $expCls($row->category_id) }}">
                        <td class="text-center">
                            <input type="checkbox" class="dsp-pick" value="{{ $row->id }}">
                        </td>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td><span class="exp-code">{{ $row->code }}</span></td>
                        <td>
                            <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                            <div class="md-sub"><span class="md-tag">{{ $row->category_code ?: '—' }}</span></div>
                        </td>
                        <td class="md-sub">{{ $row->batch_no ?: '—' }}</td>
                        <td class="md-sub">
                            @if ($row->test_report_no)
                                {{ $row->test_report_no }}
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="exp-amount">{{ $expNum($row->amount) }}</span>
                            <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                        </td>
                        <td class="text-center md-sub">{{ $expDate($row->exported_date) }}</td>
                        <td class="md-sub">
                            @if ($row->purpose)
                                <span class="md-note" title="{{ $row->purpose }}">{{ $row->purpose }}</span>
                            @else
                                <span class="md-empty">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center md-empty py-4">
                            Không có hoá chất nào đang chờ huỷ. Lập phiếu loại <b>Loại bỏ</b> ở tab
                            <b>Sổ sử dụng hoá chất</b> để đánh dấu loại bỏ.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ---------- Các đợt xin quyết định huỷ ---------- --}}
    <h6 class="exp-req-title">
        <i class="fas fa-clipboard-check mr-1"></i> Các đợt xin quyết định huỷ
        <span class="md-sub">({{ $disposals->count() }} đợt, {{ $disposals->where('app_status', 'pending')->count() }}
            đang chờ quyết định)</span>
    </h6>

    @forelse ($disposals as $batch)
        <div class="dsp-card {{ $batch->status_id == 1 ? '' : 'is-locked' }}">

            <div class="dsp-card-head">
                <div>
                    <span class="dsp-code">{{ $batch->code }}</span>
                    <span class="dsp-status {{ $batch->app_status }}">
                        {{ $disposalStatuses[$batch->app_status] ?? $batch->app_status }}
                    </span>
                    @if ($batch->status_id != 1)
                        <span class="badge badge-danger ml-1">Đã khoá</span>
                    @endif
                    <div class="md-sub mt-1">
                        Tháng {{ str_pad($batch->period_month, 2, '0', STR_PAD_LEFT) }}.{{ $batch->period_year }}
                        · {{ $batch->item_count }} phiếu
                        @if ($batch->total_kg > 0)
                            · quy đổi {{ $expNum($batch->total_kg) }} kg
                        @endif
                        @if ($batch->not_convertible)
                            <span title="Đơn vị nhóm đếm hoặc thiếu tỉ trọng d nên không quy đổi được">
                                ({{ $batch->not_convertible }} dòng không quy đổi)
                            </span>
                        @endif
                        @if ($batch->decision_no)
                            · Quyết định số <b>{{ $batch->decision_no }}</b>
                        @endif
                    </div>
                </div>

                <div class="dsp-card-actions">
                    @if ($batch->printable)
                        <a href="{{ route($dspRoute . 'print', ['id' => $batch->id]) }}" target="_blank"
                            class="btn btn-sm btn-success" title="In Phiếu Theo Dõi Và Quyết Định Huỷ">
                            <i class="fas fa-print mr-1"></i> In phiếu
                        </a>
                        @perm('export_chemical_disposal_decide')
                            <button type="button" class="btn btn-sm btn-primary btn-dsp-complete"
                                data-batch="{{ json_encode([
                                    'id' => $batch->id,
                                    'code' => $batch->code,
                                    'solid_weight' => $batch->solid_weight,
                                    'liquid_weight' => $batch->liquid_weight,
                                    'handover_date' => $batch->handover_date,
                                    'handover_by' => $batch->handover_by,
                                    'receive_date' => $batch->receive_date,
                                    'receive_by' => $batch->receive_by,
                                    'label_date' => $batch->label_date,
                                    'label_by' => $batch->label_by,
                                    'destroy_date' => $batch->destroy_date,
                                    'destroy_by' => $batch->destroy_by,
                                    'suggest_kg' => round((float) $batch->total_kg, 4),
                                ]) }}">
                                <i class="fas fa-truck-ramp-box mr-1"></i> Giao nhận &amp; theo dõi huỷ
                            </button>
                        @endperm
                    @endif

                    @if ($batch->app_status === 'pending' && $batch->status_id == 1 && user_can('export_chemical_disposal_decide'))
                        <button type="button" class="btn btn-sm btn-primary btn-dsp-decide" data-id="{{ $batch->id }}"
                            data-code="{{ $batch->code }}" data-answer="approved">
                            <i class="fas fa-stamp mr-1"></i> Ghi quyết định huỷ
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary btn-dsp-decide" data-id="{{ $batch->id }}"
                            data-code="{{ $batch->code }}" data-answer="rejected">
                            <i class="fas fa-xmark mr-1"></i> Không duyệt
                        </button>
                    @endif

                    @if ($batch->editable && user_can('export_chemical_disposal_manage'))
                        <button type="button" class="btn btn-sm btn-warning btn-dsp-edit"
                            data-batch="{{ json_encode([
                                'id' => $batch->id,
                                'code' => $batch->code,
                                'period_month' => $batch->period_month,
                                'period_year' => $batch->period_year,
                                'summarized_by' => $batch->summarized_by,
                                'summarized_at' => $batch->summarized_at,
                                'chemical_staff' => $batch->chemical_staff,
                                'checked_at' => $batch->checked_at,
                            ]) }}">
                            <i class="fas fa-edit mr-1"></i> Sửa
                        </button>

                        <form class="form-dsp-add d-inline" action="{{ route($dspRoute . 'addItems') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="{{ $batch->id }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Thêm các phiếu đang chọn ở bảng chờ huỷ vào đợt này">
                                <i class="fas fa-plus mr-1"></i> Thêm phiếu đã chọn
                            </button>
                        </form>

                        <form class="form-md-confirm d-inline" action="{{ route($dspRoute . 'submit') }}" method="POST"
                            data-title="Trình duyệt đợt huỷ {{ $batch->code }}?"
                            data-text="Sau khi trình, danh sách {{ $batch->item_count }} phiếu sẽ bị khoá lại đúng như hồ sơ gửi TP. ĐBCL và Ban Giám Đốc.">
                            @csrf
                            <input type="hidden" name="id" value="{{ $batch->id }}">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-paper-plane mr-1"></i> Trình duyệt
                            </button>
                        </form>
                    @endif

                    @if (in_array($batch->app_status, ['draft', 'rejected'], true) && user_can('export_chemical_disposal_manage'))
                        <form class="form-md-confirm d-inline" action="{{ route($dspRoute . 'deActive') }}"
                            method="POST"
                            data-title="{{ $batch->status_id == 1 ? 'Khoá' : 'Mở khoá' }} đợt huỷ {{ $batch->code }}?"
                            data-text="{{ $batch->status_id == 1 ? 'Các phiếu trong đợt sẽ được trả về hàng chờ huỷ để gom lại đợt khác.' : 'Đợt sẽ hoạt động trở lại, các phiếu phải chọn và thêm lại.' }}"
                            data-danger="{{ $batch->status_id == 1 ? '1' : '' }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ $batch->id }}">
                            <button type="submit" class="btn btn-sm btn-{{ $batch->status_id == 1 ? 'secondary' : 'primary' }}"
                                title="{{ $batch->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                <i class="fas fa-{{ $batch->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($batch->app_status === 'rejected' && $batch->reject_reason)
                <div class="dsp-reject">
                    <i class="fas fa-triangle-exclamation mr-1"></i>
                    <b>Không được duyệt:</b> {{ $batch->reject_reason }}
                    Các phiếu đã được trả về hàng chờ huỷ.
                </div>
            @endif

            @if ($batch->item_count)
                <div class="table-responsive">
                    <table class="table table-bordered table-sm w-100 dsp-item-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th>Tên Hoá Chất, Chất Chuẩn</th>
                                <th style="width: 110px">Số Lô</th>
                                <th style="width: 150px">Số PKN, OOS, BCSL</th>
                                <th class="text-right" style="width: 130px">Khối Lượng / Số Lượng</th>
                                <th>Lý Do</th>
                                @if ($batch->editable)
                                    <th class="text-center" style="width: 60px">Gỡ</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($batch->items as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $item->chem_name ?: '—' }}</div>
                                        <div class="md-sub">
                                            <span class="md-tag">{{ $item->category_code ?: '—' }}</span>
                                            <span class="ml-1">{{ $item->code }}</span>
                                        </div>
                                    </td>
                                    <td class="md-sub">{{ $item->batch_no ?: '—' }}</td>
                                    <td class="md-sub">{{ $item->test_report_no ?: '—' }}</td>
                                    <td class="text-right">
                                        <span class="exp-amount">{{ $expNum($item->amount) }}</span>
                                        <span class="md-sub">{{ $item->unit }}</span>
                                        @if ($item->amount_kg !== null)
                                            <div class="md-sub">≈ {{ $expNum($item->amount_kg) }} kg</div>
                                        @endif
                                    </td>
                                    <td class="md-sub">{{ $item->purpose ?: '—' }}</td>
                                    @if ($batch->editable && user_can('export_chemical_disposal_manage'))
                                        <td class="text-center">
                                            <form class="form-md-confirm d-inline"
                                                action="{{ route($dspRoute . 'removeItem') }}" method="POST"
                                                data-title="Gỡ phiếu {{ $item->code }} khỏi đợt?"
                                                data-text="Phiếu sẽ quay lại bảng Hoá chất chờ huỷ và sửa lại được.">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $batch->id }}">
                                                <input type="hidden" name="export_id" value="{{ $item->id }}">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Gỡ khỏi đợt">
                                                    <i class="fas fa-xmark"></i>
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="dsp-empty-items">
                    Đợt này chưa có phiếu nào. Tích chọn ở bảng <b>Hoá chất chờ huỷ</b> rồi bấm
                    <b>Thêm phiếu đã chọn</b>.
                </div>
            @endif

            <div class="dsp-card-foot md-sub">
                Người tổng kết: <b>{{ $batch->summarized_by ?: '—' }}</b>
                @if ($batch->summarized_at)
                    ({{ $expDate($batch->summarized_at) }})
                @endif
                · NV quản lý hoá chất: <b>{{ $batch->chemical_staff ?: '—' }}</b>
                @if ($batch->qa_approved_by)
                    · TP. ĐBCL: <b>{{ $batch->qa_approved_by }}</b> ({{ $expDate($batch->qa_approved_at) }})
                @endif
                @if ($batch->director_approved_by)
                    · Ban Giám Đốc: <b>{{ $batch->director_approved_by }}</b>
                    ({{ $expDate($batch->director_approved_at) }})
                @endif
                @if ($batch->destroy_date)
                    · Đã huỷ ngày <b>{{ $expDate($batch->destroy_date) }}</b> - {{ $batch->destroy_by }}
                @endif
            </div>
        </div>
    @empty
        <div class="dsp-empty">
            Chưa có đợt xin quyết định huỷ nào. Tích chọn hoá chất ở bảng trên rồi bấm
            <b>Xin quyết định huỷ</b>.
        </div>
    @endforelse
</div>
