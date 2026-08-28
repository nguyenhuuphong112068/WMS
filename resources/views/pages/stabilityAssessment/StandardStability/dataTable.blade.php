@include('pages.stabilityAssessment.StandardStability.assets')

<div class="content-wrapper">
    <div class="md-page">

        <div class="card md-card">
            <div class="card-body">

                <div class="md-toolbar">
                    <button type="button" class="btn btn-primary btn-md-create">
                        <i class="fas fa-plus mr-1"></i> Lập phiếu đánh giá
                    </button>
                    <p class="hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Chỉ <b>{{ $assessGroupName }} ({{ $assessGroupCode }})</b> mới phải đánh giá hạn dùng, mỗi
                        ống chuẩn chỉ có <b>một phiếu còn hiệu lực</b>. Trạng thái phiếu tự chạy theo tiến độ các
                        mốc, mốc đến hạn trong <b>{{ $dueSoonDays }} ngày</b> được cảnh báo trên bảng.
                    </p>
                </div>

                {{-- Lọc nhanh theo trạng thái phiếu: mỗi dòng mang sẵn data-state --}}
                <div class="ssa-filters" data-table="#mdTable">
                    <button type="button" class="ssa-chip is-active" data-state="all">
                        <i class="fas fa-layer-group"></i> Tất cả
                        <span class="count">{{ $datas->count() }}</span>
                    </button>
                    @foreach ($statuses as $status)
                        <button type="button" class="ssa-chip" data-state="{{ $status }}">
                            <span class="ssa-badge {{ $ssaStatusClass($status) }}">{{ $status }}</span>
                            <span class="count">{{ $ssaStatusCounts[$status] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table id="mdTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th style="width: 165px">Mã Ống Chuẩn</th>
                                <th>Chất Chuẩn</th>
                                <th class="text-center" style="width: 110px">Ngày Bắt Đầu</th>
                                <th class="text-center" style="width: 90px"
                                    title="Khoảng cách giữa hai mốc đánh giá liên tiếp">Chu Kỳ</th>
                                <th class="text-center" style="width: 150px"
                                    title="Số mốc đã có kết quả trên tổng số mốc của phiếu">Tiến Độ Các Mốc</th>
                                <th class="text-center" style="width: 150px"
                                    title="Mốc chưa đánh giá có ngày đến hạn sớm nhất">Mốc Kế Tiếp</th>
                                <th class="text-center" style="width: 120px"
                                    title="Hạn dùng của nhà sản xuất / hạn dùng nội bộ của ống chuẩn">Hạn Dùng</th>
                                <th class="text-center" style="width: 125px">Trạng Thái</th>
                                <th style="width: 125px">Người Lập</th>
                                <th class="text-center" style="width: 135px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $row)
                                <tr data-state="{{ $row->status }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="ssa-code">{{ $row->import_code }}</span>
                                        <div class="md-sub">
                                            <span class="ssa-group-tag">{{ $ssaGroupName($row->group_code) }}</span>
                                            @if ($row->batch_no)
                                                <span class="ml-1">Lô {{ $row->batch_no }}</span>
                                            @endif
                                        </div>
                                        <div class="md-sub">Nhập {{ $ssaDate($row->imported_date) }}</div>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $row->standard_name ?: '—' }}</div>
                                        <div class="md-sub">
                                            <span class="md-tag">{{ $row->category_code ?: '—' }}</span>
                                            @if ($row->category_version)
                                                <span class="ml-1">v{{ $row->category_version }}</span>
                                            @endif
                                            @if ($row->manufacturer_short_name)
                                                <span class="ml-1">· {{ $row->manufacturer_short_name }}</span>
                                            @endif
                                        </div>
                                        @if ($row->note)
                                            <div class="md-sub">
                                                <span class="md-note" title="{{ $row->note }}">{{ $row->note }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center" data-order="{{ $row->start_date }}">
                                        {{ $ssaDate($row->start_date) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="ssa-timepoint">{{ $row->assessment_period }} th</span>
                                    </td>
                                    <td class="text-center" data-order="{{ $row->progress }}">
                                        @if ($row->item_total > 0)
                                            <span class="font-weight-bold">{{ $row->item_done }}/{{ $row->item_total }}</span>
                                            <span class="md-sub">mốc</span>
                                            <div class="ssa-progress {{ $row->progress >= 100 ? 'is-done' : '' }}">
                                                <span style="width: {{ $row->progress }}%"></span>
                                            </div>
                                        @else
                                            <span class="md-empty" title="Phiếu chưa khai mốc đánh giá nào">Chưa có mốc</span>
                                        @endif
                                    </td>
                                    <td class="text-center md-sub" data-order="{{ $row->next_due_date ?: '9999-12-31' }}">
                                        @if ($row->next_due_date)
                                            <div class="font-weight-bold">{{ $ssaDate($row->next_due_date) }}</div>
                                            @if ($row->item_overdue > 0)
                                                <span class="ssa-state ssa-state-overdue">Quá hạn
                                                    {{ $row->item_overdue }}</span>
                                            @elseif ($row->item_due > 0)
                                                <span class="ssa-state ssa-state-due">Sắp đến hạn
                                                    {{ $row->item_due }}</span>
                                            @else
                                                <span class="ssa-state ssa-state-waiting">Chưa tới hạn</span>
                                            @endif
                                        @elseif ($row->item_total > 0)
                                            <span class="ssa-state ssa-state-done">Xong hết các mốc</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center md-sub" data-order="{{ $row->expired_date ?: '9999-12-31' }}">
                                        {{ $ssaDate($row->expired_date) }}
                                        @if ($row->internal_expired_date)
                                            <div>Nội bộ {{ $ssaDate($row->internal_expired_date) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="ssa-badge {{ $ssaStatusClass($row->status) }}">{{ $row->status }}</span>
                                    </td>
                                    <td class="md-sub">
                                        {{ $row->created_by ?: '—' }}
                                        <br><small>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '' }}</small>
                                    </td>
                                    <td>
                                        <div class="md-actions">
                                            <a href="{{ route($ssaRoute . 'detail', ['id' => $row->id]) }}"
                                                class="btn btn-sm btn-primary" title="Xem các mốc đánh giá">
                                                <i class="fas fa-list-ul"></i>
                                            </a>

                                            @if ($row->status !== 'Huỷ')
                                                <button type="button" class="btn btn-sm btn-warning btn-md-edit"
                                                    title="Sửa đầu phiếu"
                                                    data-row="{{ json_encode([
                                                        'id' => $row->id,
                                                        'start_date' => substr((string) $row->start_date, 0, 10),
                                                        'assessment_period' => $row->assessment_period,
                                                        'note' => $row->note,
                                                    ]) }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif

                                            <form class="form-md-confirm d-inline" action="{{ route($ssaRoute . 'cancel') }}"
                                                method="POST"
                                                data-title="{{ $row->status === 'Huỷ' ? 'Mở lại' : 'Huỷ' }} {{ $ssaLabel }}?"
                                                data-text="{{ $row->status === 'Huỷ' ? 'Phiếu của ống chuẩn ' . $row->import_code . ' sẽ dùng lại bình thường, trạng thái tính lại theo các mốc đã đánh giá.' : 'Phiếu của ống chuẩn ' . $row->import_code . ' sẽ ngừng theo dõi, không ghi thêm kết quả đánh giá được nữa.' }}"
                                                data-danger="{{ $row->status === 'Huỷ' ? '' : '1' }}">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $row->id }}">
                                                <button type="submit"
                                                    class="btn btn-sm btn-{{ $row->status === 'Huỷ' ? 'primary' : 'secondary' }}"
                                                    title="{{ $row->status === 'Huỷ' ? 'Mở lại phiếu' : 'Huỷ phiếu' }}">
                                                    <i class="fas fa-{{ $row->status === 'Huỷ' ? 'rotate-left' : 'ban' }}"></i>
                                                </button>
                                            </form>
                                        </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bảng dùng chung sắp theo cột 1, riêng đây cần xem phiếu mới lập trước (cột 3 = Ngày Bắt Đầu)
        $('#mdTable').DataTable().order([3, 'desc']).draw();

        var table = $('#mdTable').DataTable();

        table.settings()[0].oLanguage.sEmptyTable =
            'Chưa có phiếu đánh giá hạn dùng nào. Bấm "Lập phiếu đánh giá" để bắt đầu theo dõi một ống chuẩn.';
        table.draw(false);
    });
</script>
