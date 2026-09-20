{{--
| SỬ DỤNG VẬT TƯ - TAB "SOẠN VẬT TƯ CẤP PHÁT"
|
| Ba tab đề nghị nhìn theo PHIẾU, tab này nhìn theo VẬT TƯ: gom phần còn phải cấp của mọi
| đề nghị đã duyệt lại thành một dòng cho mỗi vật tư, kèm tồn còn hứa được và các lô nên
| lấy (thứ tự của App\Support\MaterialPicking). Người giữ kho soạn đủ hàng ở đây rồi mới
| sang tab đề nghị bấm cấp phát.
|
| Dữ liệu vào: $prepGroups, $prepShortageCount - xem MaterialExportController::preparationData().
--}}

@php
    $prepToday = now()->startOfDay();

    // Nhãn ngắn của 3 loại đề nghị - đúng tên 3 tab đề nghị bên cạnh
    $prepTypeLabel = ['periodic' => 'Định kỳ', 'risk_assessment' => 'ĐG rủi ro', 'regular' => 'Thường quy'];
@endphp

<style>
    /* ---------- Soạn vật tư cấp phát ---------- */
    .prep-lot {
        display: flex;
        align-items: baseline;
        gap: 6px;
        font-size: 0.78rem;
        line-height: 1.6;
    }

    .prep-lot+.prep-lot {
        border-top: 1px dashed var(--primary-lighter);
    }

    .prep-lot .prep-lot-code {
        font-weight: 700;
        color: var(--primary-dark);
        letter-spacing: 0.3px;
    }

    .prep-loc {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 1px 7px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-weight: 600;
        font-size: 0.72rem;
    }

    .prep-req {
        display: inline-block;
        margin: 0 4px 4px 0;
        padding: 1px 8px;
        border: 1px solid var(--primary-lighter);
        border-radius: 999px;
        background: #fff;
        color: var(--primary-dark);
        font-size: 0.72rem;
        font-weight: 600;
    }

    /* Một phiếu đề nghị đang chờ vật tư này: mã phiếu + số cần, dòng dưới là người lập / ngày */
    .prep-req-item {
        padding: 3px 0;
        font-size: 0.78rem;
        line-height: 1.5;
    }

    .prep-req-item+.prep-req-item {
        border-top: 1px dashed var(--primary-lighter);
    }

    .prep-req-item .prep-req-qty {
        font-weight: 700;
        color: var(--text-main);
    }

    .prep-req-item .prep-req-meta {
        color: #64748B;
        font-size: 0.72rem;
    }

    .prep-need {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .prep-due-late {
        color: #B91C1C;
        font-weight: 700;
    }
</style>

<div class="md-toolbar">
    <a href="{{ route($expRoute . 'prepPrint') }}" target="_blank" class="btn btn-primary">
        <i class="fas fa-print mr-1"></i> In phiếu soạn hàng
    </a>

    @if ($prepShortageCount > 0)
        <button type="button" class="btn btn-outline-warning text-dark btn-prep-short"
            style="border-color: #d97706; background-color: #fffbeb;"
            title="Chỉ hiện vật tư kho không đủ hàng để cấp">
            <i class="fas fa-exclamation-triangle mr-1 text-warning"></i> Thiếu hàng
            <span class="badge badge-warning ml-1" style="font-size: 0.8rem;">{{ $prepShortageCount }}</span>
        </button>
    @endif
</div>

<div class="table-responsive">
    <table id="mePrepTable" class="table table-bordered table-hover w-100 md-table">
        <thead>
            <tr>
                <th class="text-center" style="width:45px">STT</th>
                <th>Vật Tư</th>
                <th class="text-right" style="width:110px">Cần Soạn</th>
                <th class="text-right" style="width:110px">Tồn Khả Dụng</th>
                <th class="text-center" style="width:130px">Tình Trạng</th>
                <th class="text-center" style="width:110px">Cần Trước Ngày</th>
                <th style="width:250px">Đề Nghị Chờ</th>
                <th style="width:270px">Lô Nên Lấy</th>
                <th class="text-center" style="width:70px">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prepGroups as $g)
                @php
                    $shortage = (float) $g['shortage'];
                    $isShort = $shortage > 0.00005;
                    $due = $g['earliest_needed'] ? \Carbon\Carbon::parse($g['earliest_needed'])->startOfDay() : null;
                    $isLate = $due && $due->lt($prepToday);
                @endphp
                <tr data-prep-short="{{ $isShort ? 1 : 0 }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="font-weight-bold">{{ $g['name'] ?: '—' }}</div>
                        @if ($g['category_code'])
                            <div class="md-sub"><span class="md-tag">{{ $g['category_code'] }}</span></div>
                        @endif
                        @if ($g['specification'])
                            <div class="md-sub small text-muted">{{ $g['specification'] }}</div>
                        @endif
                        @unless ($g['category_id'])
                            <span class="badge badge-secondary">Ngoài danh mục</span>
                        @endunless
                    </td>
                    <td class="text-right" data-order="{{ $g['needed'] }}">
                        <span class="prep-need">{{ $expNum($g['needed']) }}</span>
                        <span class="md-sub">{{ $g['unit'] }}</span>
                        @if ($g['unit_mixed'])
                            <div><span class="badge badge-warning" title="Các phiếu khai đơn vị tính khác nhau">ĐVT khác nhau</span></div>
                        @endif
                    </td>
                    <td class="text-right" data-order="{{ $g['available'] }}">
                        {{ $expNum($g['available']) }} <span class="md-sub">{{ $g['unit'] }}</span>
                    </td>
                    <td class="text-center">
                        @if ($isShort)
                            <span class="badge badge-danger">Thiếu {{ $expNum($shortage) }} {{ $g['unit'] }}</span>
                        @else
                            <span class="badge badge-success">Đủ hàng</span>
                        @endif
                    </td>
                    <td class="text-center md-sub" data-order="{{ $g['earliest_needed'] ?: '9999-12-31' }}">
                        @if ($due)
                            <span class="{{ $isLate ? 'prep-due-late' : '' }}">{{ $due->format('d/m/Y') }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @foreach (array_slice($g['demands'], 0, 3) as $d)
                            @php
                                $dDue = $d['needed_date'] ? \Carbon\Carbon::parse($d['needed_date']) : null;
                            @endphp
                            <div class="prep-req-item">
                                <span class="prep-req">{{ $d['request_code'] }}</span>
                                <span class="prep-req-qty">{{ $expNum($d['left']) }} {{ $d['unit'] ?: $g['unit'] }}</span>
                                @if ((float) $d['issued'] > 0.00005)
                                    <span class="badge badge-warning">Cấp tiếp</span>
                                @endif
                                @if ($d['request_name'])
                                    <div class="prep-req-meta">{{ $d['request_name'] }}</div>
                                @endif
                                <div class="prep-req-meta">
                                    {{ $prepTypeLabel[$d['request_type']] ?? $d['request_type'] }} ·
                                    {{ $d['created_by'] ?: '—' }}
                                    @if ($dDue)
                                        · cần <span class="{{ $dDue->startOfDay()->lt($prepToday) ? 'prep-due-late' : '' }}">{{ $dDue->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if (count($g['demands']) > 3)
                            <div class="md-sub small">+{{ count($g['demands']) - 3 }} đề nghị nữa</div>
                        @endif
                    </td>
                    <td>
                        @forelse (array_slice($g['plan'], 0, 2) as $line)
                            <div class="prep-lot">
                                <span class="prep-lot-code">{{ $line['import_code'] }}</span>
                                <span class="prep-loc"><i class="fas fa-map-marker-alt"></i>{{ $line['location_code'] ?: 'Chưa xếp vị trí' }}</span>
                                <span>{{ $expNum($line['suggested_amount']) }} {{ $line['unit'] ?: $g['unit'] }}</span>
                            </div>
                        @empty
                            <span class="text-danger md-sub">Không còn lô nào còn hạn và còn hứa được</span>
                        @endforelse
                        @if (count($g['plan']) > 2)
                            <div class="md-sub small">+{{ count($g['plan']) - 2 }} lô nữa</div>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="md-actions">
                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                data-target="#prepDetailModal_{{ $loop->index }}" title="Chi tiết soạn hàng">
                                <i class="fas fa-list-ul"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">Không còn vật tư nào chờ cấp phát.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var table = $.fn.DataTable.isDataTable('#mePrepTable') ? $('#mePrepTable').DataTable() : null;

        if (!table) return;

        // Cần trước ngày sớm nhất lên đầu (DataTables mặc định sắp theo cột 1 - tên vật tư)
        table.order([5, 'asc']).draw();

        // Lọc nhanh các vật tư kho không đủ hàng - chỉ áp cho đúng bảng này
        var onlyShort = false;

        $.fn.dataTable.ext.search.push(function (settings, data, index) {
            if (settings.nTable.id !== 'mePrepTable' || !onlyShort) return true;

            return $(settings.aoData[index].nTr).attr('data-prep-short') === '1';
        });

        $(document).on('click', '.btn-prep-short', function () {
            onlyShort = !onlyShort;
            $(this).toggleClass('btn-warning', onlyShort).toggleClass('btn-outline-warning', !onlyShort);
            table.draw();
        });
    });
</script>
