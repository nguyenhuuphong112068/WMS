{{--
| SỬ DỤNG - TAB PHIẾU TẠM
|
| Các đợt hoá chất đã tick chọn ở picker "Chọn Nhiều Từ Tồn Kho" và bấm Lưu Tạm -
| CHỈ chứa dòng loại Sử dụng, chưa trừ kho. Loại bỏ / Chuyển kho luôn trừ kho ngay
| nên không bao giờ xuất hiện ở đây (xem picker.blade.php).
--}}

@php
    $draftErrBag = $errors->getBag('draftErrors');
    $draftErrBatch = session('draftErrorBatch');
@endphp

<div class="exp-pane {{ $activeTab === 'draft' ? 'is-active' : '' }}" id="expPaneDraft">

    <p class="hint">
        <i class="fas fa-info-circle mr-1"></i>
        Các đợt đã <b>Lưu Tạm</b> từ picker "Chọn Nhiều Từ Tồn Kho" - <b>chưa trừ kho</b>. Bấm
        <b>Dùng Ngay</b> để ghi thật vào Sổ sử dụng (kiểm tra lại tồn / hạn mức tại thời điểm bấm), hoặc
        <b>Xoá</b> nếu không dùng nữa.
    </p>

    @if ($draftErrBag->any())
        <div class="alert alert-danger">
            <b>Không dùng ngay được đợt {{ $draftErrBatch }}:</b>
            <ul class="mb-0">
                @foreach ($draftErrBag->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @forelse ($drafts as $batchCode => $rows)
        <div class="card md-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="exp-code">{{ $batchCode }}</span>
                        <span class="md-sub ml-2">
                            Lưu tạm lúc {{ \Carbon\Carbon::parse($rows->first()->created_at)->format('d/m/Y H:i') }}
                            bởi {{ $rows->first()->updated_by ?: $rows->first()->created_by ?: '—' }}
                        </span>
                    </div>
                    <div class="md-actions">
                        <form class="form-md-confirm d-inline"
                            action="{{ route($expRoute . 'draftFinalize') }}" method="POST"
                            data-title="Dùng ngay đợt {{ $batchCode }}?"
                            data-text="Hệ thống kiểm tra lại tồn / hạn mức tại thời điểm này rồi ghi {{ $rows->count() }} dòng vào Sổ sử dụng, trừ kho ngay.">
                            @csrf
                            <input type="hidden" name="batch_code" value="{{ $batchCode }}">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-check mr-1"></i> Dùng Ngay
                            </button>
                        </form>
                        <form class="form-md-confirm d-inline"
                            action="{{ route($expRoute . 'draftDeleteBatch') }}" method="POST"
                            data-title="Xoá cả đợt {{ $batchCode }}?"
                            data-text="Xoá {{ $rows->count() }} dòng của đợt này khỏi Phiếu Tạm. Chưa trừ kho nên không ảnh hưởng tồn."
                            data-danger="1">
                            @csrf
                            <input type="hidden" name="batch_code" value="{{ $batchCode }}">
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="fas fa-trash mr-1"></i> Xoá Cả Đợt
                            </button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm md-table mb-0">
                        <thead>
                            <tr>
                                <th>Mã Xuất Nhập</th>
                                <th>Hoá Chất</th>
                                <th style="width: 110px">Số Lô</th>
                                <th class="text-right" style="width: 110px">Số Lượng</th>
                                <th style="width: 150px">Người Kiểm Tra</th>
                                <th>Mục Đích Sử Dụng</th>
                                <th class="text-center" style="width: 70px">Xoá</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td><span class="exp-code">{{ $row->import_code ?: '—' }}</span></td>
                                    <td>
                                        <div class="font-weight-bold">{{ $row->chem_name ?: '—' }}</div>
                                        <div class="md-sub"><span class="md-tag">{{ $row->category_code ?: '—' }}</span></div>
                                    </td>
                                    <td class="md-sub">{{ $row->batch_no ?: '—' }}</td>
                                    <td class="text-right">
                                        <span class="exp-amount">{{ $expNum($row->amount) }}</span>
                                        <span class="md-sub">{{ $row->unit_short_name ?: $row->unit_name }}</span>
                                    </td>
                                    <td class="md-sub">{{ $row->checked_by ?: '—' }}</td>
                                    <td class="md-sub">
                                        @if ($row->purpose)
                                            <span class="md-note" title="{{ $row->purpose }}">{{ $row->purpose }}</span>
                                        @else
                                            <span class="md-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form class="form-md-confirm d-inline"
                                            action="{{ route($expRoute . 'draftDeleteItem') }}" method="POST"
                                            data-title="Xoá dòng này khỏi Phiếu Tạm?"
                                            data-text="{{ $row->chem_name }} - {{ $expNum($row->amount) }} {{ $row->unit_short_name ?: $row->unit_name }}."
                                            data-danger="1">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $row->id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center md-empty py-4">
            Chưa có đợt Phiếu Tạm nào. Mở modal <b>Sử dụng hoá chất</b>, bấm <b>Chọn Nhiều Từ Tồn Kho</b> rồi
            <b>Lưu Tạm</b> để tạo đợt.
        </div>
    @endforelse
</div>
