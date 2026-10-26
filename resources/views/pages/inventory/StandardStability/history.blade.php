{{--
| Nhật ký thay đổi của một phiếu đánh giá hạn dùng.
|
| Dữ liệu đổ sẵn từ Controller ($histories, đọc standard_stability_assessment_histories
| theo standard_stability_assessment_list.id) chứ không gọi AJAX: mở phiếu là có luôn,
| không phải chờ tải thêm.
|
| Bảng chỉ ghi thêm nên nội dung ở đây là bất biến - không có nút sửa / xoá.
--}}

@php
    /** Hành động -> icon + màu, để nhìn lướt là phân biệt được loại thay đổi. */
    $ssaHisStyle = fn($action) => match ($action) {
        'Lập phiếu' => ['fas fa-file-circle-plus', 'initial'],
        'Sửa phiếu' => ['fas fa-pen-to-square', 'initial'],
        'Thêm mốc' => ['fas fa-circle-plus', 'done'],
        'Sửa mốc' => ['fas fa-pen', 'running'],
        'Xoá mốc' => ['fas fa-trash', 'cancelled'],
        'Ghi kết quả' => ['fas fa-clipboard-check', 'done'],
        'Cấp phát chuẩn' => ['fas fa-hand-holding-droplet', 'running'],
        'Ngưng đánh giá' => ['fas fa-circle-stop', 'stopped'],
        'Đánh giá tiếp' => ['fas fa-play', 'done'],
        'Huỷ phiếu' => ['fas fa-ban', 'cancelled'],
        'Mở lại phiếu' => ['fas fa-rotate-left', 'initial'],
        default => ['fas fa-circle-info', 'initial'],
    };
@endphp

<div class="modal fade md-modal" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog ssa-modal-wide" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clock-rotate-left"></i> Lịch Sử Thay Đổi
                    <small class="md-sub ml-2">Phiếu của ống chuẩn {{ $list->import_code }}</small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="md-hint mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Mỗi dòng là một thay đổi đã xảy ra trên phiếu: lập phiếu, sửa đầu phiếu, thêm / sửa / xoá mốc,
                    ghi kết quả, huỷ và mở lại. Nhật ký <b>chỉ ghi thêm</b>, không sửa và không xoá được.
                    Mới nhất nằm trên cùng.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm ssa-history-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px">STT</th>
                                <th style="width: 140px">Thao Tác</th>
                                <th style="width: 130px">Mốc</th>
                                <th style="min-width: 280px">Trước Khi Đổi</th>
                                <th style="min-width: 280px">Sau Khi Đổi</th>
                                <th style="width: 150px">Người Thực Hiện</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($histories as $row)
                                @php [$hisIcon, $hisTone] = $ssaHisStyle($row->action); @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="ssa-badge {{ $hisTone }}">
                                            <i class="{{ $hisIcon }} mr-1"></i> {{ $row->action }}
                                        </span>
                                    </td>
                                    <td class="md-sub">{{ $row->target ?: '—' }}</td>
                                    <td class="md-sub">
                                        {{ $row->old_values ?: '—' }}
                                    </td>
                                    <td class="md-sub">
                                        {{ $row->new_values ?: '—' }}
                                        @if ($row->note)
                                            <div class="ssa-history-note">
                                                <i class="fas fa-angle-right mr-1"></i>{{ $row->note }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="md-sub">
                                        {{ $row->created_by ?: 'NA' }}
                                        <br><small>
                                            {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '' }}
                                        </small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center md-empty">Phiếu chưa có thay đổi nào được ghi
                                        nhận.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
