@extends ('layout.master')

{{--
|--------------------------------------------------------------------------
| DỰ TRÙ - CHI TIẾT MỘT PHIẾU DỰ TRÙ
|--------------------------------------------------------------------------
| Dùng chung cho hai màn hình:
| - Dự Trù Hoá Chất   : $canEditItems = true khi phiếu còn Nháp / Bị từ chối
| - Tiếp Nhận Dự Trù  : luôn ở chế độ chỉ xem
|
| Biến vào: $list, $items, $histories, $categories, $units, $canEditItems,
|           $backRoute, $estRoute, $appStatuses, $signSteps, $receptionStatuses
--}}

@php
    $estIcon = 'fas fa-clipboard-check';
    $estNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $estStatusClass = match ($list->app_status) {
        'approved' => 'approved',
        'rejected' => 'rejected',
        default => 'pending',
    };
@endphp

@section('mainContent')
    @include('pages.estimate.shared.assets')

    <div class="content-wrapper">
        <div class="md-page">

            <div class="est-info">
                <div class="box">
                    <label>Trạng Thái Trình Ký</label>
                    <div class="val">
                        <span class="md-badge {{ $estStatusClass }}">
                            {{ $appStatuses[$list->app_status] ?? $list->app_status }}
                        </span>
                    </div>
                </div>

                <div class="box">
                    <label>Người Lập Phiếu</label>
                    <div class="val">{{ $list->updated_by ?: $list->created_by ?: '—' }}</div>
                    <div class="md-sub">
                        {{ $list->created_at ? \Carbon\Carbon::parse($list->created_at)->format('d/m/Y H:i') : '' }}
                    </div>
                </div>

                @foreach ($signs as $step)
                    @php
                        $stepWho = $step->signer_full_name ?: ($step->user_name ?: ($step->role_names ?: '—'));
                        $stepIsBod = (int) $step->step_no === (int) $signs->count();
                    @endphp
                    <div class="box">
                        <label>Bước {{ $step->step_no }}{{ $stepIsBod ? ' - Ban Giám Đốc' : '' }}</label>
                        <div class="val">{{ $stepWho }}</div>
                        <div class="md-sub">
                            @if ($step->status === 'signed')
                                Đã ký &middot; {{ $step->signed_at ? \Carbon\Carbon::parse($step->signed_at)->format('d/m/Y H:i') : '' }}
                            @elseif ($step->status === 'rejected')
                                <span class="text-danger">Đã từ chối</span>
                            @elseif ($list->app_status === 'pending_sign' && (int) $list->current_step === (int) $step->step_no)
                                <span class="text-primary">Đang chờ ký</span>
                            @else
                                Chưa ký
                            @endif
                        </div>
                    </div>
                @endforeach

            </div>

            @if ($list->app_status === 'rejected' && $list->reject_reason)
                <div class="est-reject-note">
                    <i class="fas fa-triangle-exclamation mr-1"></i>
                    <b>Bị từ chối ở bước {{ $list->reject_step }}</b>
                    bởi {{ $list->rejected_by ?: 'NA' }}
                    {{ $list->rejected_at ? '(' . \Carbon\Carbon::parse($list->rejected_at)->format('d/m/Y H:i') . ')' : '' }}:
                    {{ $list->reject_reason }}
                </div>
            @endif



            <div class="card md-card">
                <div class="card-body">

                    <div class="md-toolbar">
                        <div>
                            <a href="{{ $backRoute }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
                            </a>

                            @if ($canEditItems && user_can('estimate_chemical_update'))
                                <button type="button" class="btn btn-primary btn-est-item-create ml-1">
                                    <i class="fas fa-plus mr-1"></i> Thêm mặt hàng
                                </button>
                            @endif

                            <button type="button" class="btn btn-info ml-1 btn-est-history"
                                data-url="{{ route($estRoute . 'history', ['id' => $list->id]) }}"
                                data-title="Phiếu {{ $list->code }}">
                                <i class="fas fa-route mr-1"></i> Theo dõi trình ký
                            </button>
                            {{-- Bộ phận mua hàng (mở từ tab "Đơn dự trù đã được phê duyệt"): tiếp nhận phiếu vừa duyệt --}}
                            @if ($purchaserView ?? false)
                                @include('pages.estimate.shared.receptionAction')
                            @endif

                            {{-- Người ký được bước đang chờ: Đồng ý / Từ chối ngay trên trang chi tiết (đã bỏ khỏi dòng danh sách) --}}
                            @if ($list->app_status === 'pending_sign' && $canSignCurrent && $pendingSign && user_can('estimate_chemical_sign'))
                                @php $estIsLast = (int) $pendingSign->step_no >= (int) $list->sign_step_count; @endphp
                                <form class="form-md-confirm d-inline" data-require-password="1" action="{{ route($estRoute . 'signStep') }}"
                                    method="POST" data-title="Ký duyệt bước {{ $pendingSign->step_no }}/{{ $list->sign_step_count }} phiếu {{ $list->code }}?"
                                    data-text="{{ $estIsLast ? 'Phiếu sẽ được phê duyệt và chuyển bộ phận mua hàng tiếp nhận.' : 'Phiếu sẽ chuyển tới người ký bước ' . ($pendingSign->step_no + 1) . '.' }}">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $list->id }}">
                                    <button type="submit" class="btn btn-success ml-1">
                                        <i class="fas fa-{{ $estIsLast ? 'stamp' : 'signature' }} mr-1"></i> Đồng ý
                                    </button>
                                </form>
                                <button type="button" class="btn btn-outline-danger ml-1 btn-est-reject" data-id="{{ $list->id }}" data-code="{{ $list->code }}">
                                    <i class="fas fa-times mr-1"></i> Từ chối
                                </button>
                            @endif

                            <button type="button" class="btn btn-outline-primary ml-1" data-toggle="modal"
                                data-target="#classifyGuideModal">
                                <i class="fas fa-info-circle mr-1"></i> Chú thích Phụ lục &amp; Nhóm
                            </button>
                        </div>

                        <p class="hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            @if ($canEditItems && user_can('estimate_chemical_update'))
                                Mỗi mặt hàng khai được nhiều dòng số lượng cho nhiều tháng khác nhau.
                            @else
                                Phiếu đã trình ký nên chi tiết chỉ xem, không sửa được.
                            @endif
                        </p>
                    </div>

                    <div class="table-responsive">
                        <table id="mdTable" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 55px">STT</th>
                                    <th style="width: 200px">Hoá Chất</th>
                                    <th style="width: 130px">Nhóm Hoá Chất</th>
                                    <th>Thông Tin Kỹ Thuật</th>
                                    <th>Mục Đích Sử Dụng</th>
                                    <th style="width: 200px">Số Lượng Dự Trù</th>
                                    <th style="width: 150px">Ngày Mong Muốn</th>
                                    @if ($purchaserView ?? false)
                                        <th style="width: 150px">Ngày Hẹn Đáp Ứng</th>
                                    @endif
                                    <th style="width: 110px">Người Tạo</th>
                                    <th class="text-center" style="width: 150px">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr @if($item->status_id == 0) style="opacity: 0.6; background-color: #f8f9fa;" @endif>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $item->display_name ?: '—' }}</div>
                                            <div class="md-sub">
                                                @if ($item->category_id)
                                                    <span class="md-tag">{{ $item->category_code }}</span>
                                                    @if ($item->category_type)
                                                        <span class="ml-1">{{ $item->category_type }}</span>
                                                    @endif
                                                @else
                                                    <span class="est-outside">Ngoài danh mục</span>
                                                @endif
                                            </div>
                                            @foreach ($item->threshold_warnings ?? [] as $warning)
                                                <div class="est-threshold-alert is-compact level-{{ $warning['level'] }}">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>{{ $warning['message'] }}
                                                    <button type="button" class="btn-est-thr-detail"
                                                        data-params="{{ json_encode(['category_id' => $item->category_id, 'item_id' => $item->id]) }}"
                                                        title="Xem các lượng đóng góp: tồn theo lô, dự trù chưa hoàn thành, mặt hàng này">
                                                        <i class="fas fa-search-plus"></i> Chi tiết
                                                    </button>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @php
                                                // Nhóm NĐ 24/2026 suy theo mã danh mục; hoá chất ngoài danh mục không suy được
                                                $estClsCodes = $item->category_id
                                                    ? (($classificationCodes ?? [])[$item->category_id] ?? [])
                                                    : [];
                                            @endphp
                                            @if ($estClsCodes)
                                                <div class="est-cls-chips">
                                                    @foreach ($estClsCodes as $estClsCode)
                                                        <span class="est-cls-chip {{ \App\Support\ChemicalClassification::toneOfCode($estClsCode) }}"
                                                            title="{{ ($classificationLabels ?? [])[$estClsCode] ?? $estClsCode }}">{{ $estClsCode }}</span>
                                                    @endforeach
                                                </div>
                                                {{-- Nhóm 3..8 thuộc Phụ lục III: gắn nhãn cảnh báo cho người ký duyệt --}}
                                                @if (\App\Support\ChemicalClassification::isSpecialControl($estClsCodes))
                                                    <div class="est-cls-special mt-1">
                                                        <i class="fas fa-shield-alt mr-1"></i>{{ \App\Support\ChemicalClassification::SPECIAL_CONTROL_LABEL }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td class="md-sub">
                                            @if ($item->category_manufacturer_name)
                                                <div class="mb-1">NSX: <b>{{ $item->category_manufacturer_name }}</b></div>
                                            @endif
                                            <div>{{ $item->technical_information ?: '—' }}</div>
                                        </td>
                                        <td class="md-sub">
                                            <div class="mb-1">{{ $item->purpose ?: '—' }}</div>
                                        </td>
                                        <td>
                                            @forelse ($item->amounts as $amount)
                                                <span class="est-chip">
                                                    <b>{{ $estNum($amount->amount) }}
                                                        {{ $amount->unit_short_name ?: $amount->unit_name }}</b>
                                                    <span>&middot;
                                                        {{ \Carbon\Carbon::parse($amount->for_month_year)->format('m/Y') }}</span>
                                                </span>
                                            @empty
                                                <span class="md-empty">—</span>
                                            @endforelse
                                        </td>
                                        <td data-order="{{ $item->expected_delivery_date }}">
                                            @if ($item->expected_delivery_date)
                                                <div class="text-primary font-weight-bold">{{ \Carbon\Carbon::parse($item->expected_delivery_date)->format('d/m/Y') }}</div>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        {{-- Ngày hẹn đáp ứng: chỉ bộ phận mua hàng sửa, khi mở phiếu từ tab "Đơn dự trù đã được phê duyệt" --}}
                                        @if ($purchaserView ?? false)
                                        <td>
                                            @include('pages.estimate.shared.promisedDates', ['editable' => $list->app_status === 'approved'])
                                        </td>
                                        @endif
                                        <td class="md-sub">
                                            {{ $item->updated_by ?: $item->created_by ?: '—' }}
                                            <br><small>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : '' }}</small>
                                        </td>
                                        <td>
                                            @if ($canEditItems && user_can('estimate_chemical_update'))
                                                <div class="md-actions">
                                                    <button type="button" class="btn btn-sm btn-warning btn-est-item-edit"
                                                        title="Sửa"
                                                        data-row="{{ json_encode([
                                                            'id' => $item->id,
                                                            'category_id' => $item->category_id,
                                                            'chem_name' => $item->chem_name,
                                                            'technical_information' => $item->technical_information,
                                                            'purpose' => $item->purpose,
                                                            'amounts' => $item->amounts->map(fn($amount) => [
                                                                'amount' => rtrim(rtrim(number_format((float) $amount->amount, 4, '.', ''), '0'), '.'),
                                                                'unit_id' => $amount->unit_id,
                                                                'conversion_factor' => $amount->conversion_factor !== null
                                                                    ? rtrim(rtrim(number_format((float) $amount->conversion_factor, 6, '.', ''), '0'), '.')
                                                                    : '',
                                                                'for_month_year' => \Carbon\Carbon::parse($amount->for_month_year)->format('Y-m'),
                                                            ]),
                                                        ]) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <form class="form-md-confirm d-inline"
                                                        action="{{ route($estRoute . 'deleteItem') }}" method="POST"
                                                        data-title="Xoá mặt hàng khỏi phiếu?"
                                                        data-text="Mặt hàng &quot;{{ $item->display_name }}&quot; và toàn bộ số lượng theo tháng của nó sẽ bị xoá khỏi phiếu {{ $list->code }}."
                                                        data-danger="1">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xoá">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                            {{-- Hoàn thành / Huỷ mục đã duyệt (phòng đề nghị), xác nhận huỷ (bộ phận mua hàng) --}}
                                            @include('pages.estimate.shared.itemStatus', ['trackPermission' => 'estimate_chemical_tracking'])
                                            @include('pages.estimate.shared.itemChatButton', ['chatPermission' => 'estimate_chemical_chat'])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('model')
    @if ($canEditItems)
        @include('pages.estimate.ChemicalEstimate.itemCreate')
        @include('pages.estimate.ChemicalEstimate.itemUpdate')
        @include('pages.estimate.shared.categoryPicker')
    @endif

    @include('pages.estimate.shared.historyModal')
    @include('pages.estimate.ChemicalEstimate.reject')

    {{-- Nút "Chi tiết" của cảnh báo ngưỡng PL IV: các lượng đóng góp (tồn, dự trù chưa hoàn thành) --}}
    @include('pages.estimate.shared.thresholdDetailModal')

    {{-- Chú thích 4 Phụ lục + 10 nhóm NĐ 24/2026 cho cột "Nhóm Hoá Chất" --}}
    @include('pages.shared.classifyGuideModal')
@endsection
