{{--
|--------------------------------------------------------------------------
| SỬ DỤNG - TỒN KHO CỦA PHÒNG (chọn nhiều hoá chất để đưa vào modal Sử Dụng Hoá Chất)
|--------------------------------------------------------------------------
| Bảng THUẦN DUYỆT/CHỌN - không có ô nhập nào ở đây. Đúng tập dữ liệu $imports mà ô
| chọn phiếu nhập cũ từng dùng (chỉ hiện phiếu selectable: còn hạn dùng, còn tồn > 0,
| đã xác định hạn dùng nội bộ). Chọn xong bấm "Thêm Vào Danh Sách" thì các dòng được
| đẩy sang bảng của modal #createModal (window.expAddRow) - Số Lượng / Người Kiểm Tra /
| Mục Đích nhập ở BÊN ĐÓ, không nhập ở đây.
|
| Xếp chồng lên trên #createModal (không đóng modal cha) - giống quy ước picker của
| Vật Tư / Chuẩn (issueLotPickerModal, inventoryPickerModal): z-index tĩnh cao hơn,
| đóng lại thì trả class modal-open cho <body> vì Bootstrap 4 gỡ mất khi đóng modal con.
--}}

<style>
    #expPickerModal .table-responsive { max-height: 58vh; overflow-y: auto; }
</style>

<div class="modal fade md-modal" id="expPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 90vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                    <i class="fas fa-boxes-stacked mr-2"></i> Tồn Kho Của Phòng
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-3">
                <div class="row align-items-center mb-2">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" id="expPickerSearch" class="form-control"
                                placeholder="Tìm theo tên hoá chất, mã danh mục, số lô...">
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <span class="badge badge-primary px-3 py-2" id="expPickerCount" style="font-size: 0.86rem;">
                            <i class="fas fa-check-circle mr-1"></i> Đã chọn: 0 hoá chất
                        </span>
                    </div>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 0.86rem;">
                        <thead class="bg-light sticky-top">
                            <tr class="text-center">
                                <th style="width: 40px"><input type="checkbox" id="expPickerCheckAll" title="Chọn tất cả"></th>
                                <th style="width: 140px" class="text-left">Mã Xuất Nhập</th>
                                <th style="min-width: 190px" class="text-left">Tên Hoá Chất</th>
                                <th style="width: 130px" class="text-right">Số Lượng Tồn</th>
                                <th style="width: 110px">Định Khu</th>
                                <th style="width: 110px">Hạn Dùng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($imports->where('selectable', true) as $import)
                                <tr class="exp-picker-row"
                                    data-import-id="{{ $import->id }}"
                                    data-search="{{ \Illuminate\Support\Str::lower($import->chem_name.' '.$import->category_code.' '.$import->code.' '.$import->batch_no) }}">
                                    <td class="text-center align-middle">
                                        <input type="checkbox" class="exp-picker-check" value="{{ $import->id }}">
                                    </td>
                                    <td class="align-middle"><span class="exp-code">{{ $import->code }}</span></td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold">{{ $import->chem_name ?: '—' }}</div>
                                        <div class="md-sub">
                                            <span class="md-tag">{{ $import->category_code ?: '—' }}</span>
                                            @if ($import->batch_no)
                                                <span class="ml-1">Lô {{ $import->batch_no }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-right align-middle">
                                        {{ $expNum($import->remaining) }} {{ $import->unit_short_name }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $import->location_code ?: '—' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $import->expired_date ? $expDate($import->expired_date) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Phòng chưa có phiếu nhập nào còn tồn để chọn.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Không hiện hoá chất đã hết hạn hoặc chưa xác định hạn dùng nội bộ.
                </small>
                <div>
                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="expPickerConfirm">
                        <i class="fas fa-plus mr-1"></i> Thêm Vào Danh Sách
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    // Dữ liệu đầy đủ của từng phiếu nhập (khoá theo id) để dựng dòng trong modal Sử Dụng Hoá Chất
    $expImportDataJs = $imports->where('selectable', true)->mapWithKeys(function ($import) {
        return [
            $import->id => [
                'code' => $import->code,
                'chem_name' => $import->chem_name,
                'category_code' => $import->category_code,
                'batch_no' => $import->batch_no,
                'remaining' => round((float) $import->remaining, 4),
                'max_issue' => round((float) $import->max_amount, 4),
                'unit' => $import->unit_short_name,
                'expired_date' => $import->expired_date ? \Carbon\Carbon::parse($import->expired_date)->format('d/m/Y') : null,
            ],
        ];
    });

    // Danh sách người kiểm tra để dựng ô chọn cho mỗi dòng mới thêm
    $expCheckerOptionsJs = $checkers->map(function ($checker) {
        return ['value' => $checker->fullName, 'label' => $checker->fullName];
    });
@endphp

<script>
    window.expImportData = {!! json_encode($expImportDataJs) !!};
    window.expCheckerOptions = {!! json_encode($expCheckerOptionsJs) !!};

    document.addEventListener('DOMContentLoaded', function() {
        var $modal = $('#expPickerModal');

        function updateCount() {
            var count = $modal.find('.exp-picker-check:checked').length;
            $('#expPickerCount').html('<i class="fas fa-check-circle mr-1"></i> Đã chọn: ' + count + ' hoá chất');
        }

        $(document).on('change', '.exp-picker-check', updateCount);

        $('#expPickerCheckAll').on('change', function() {
            $modal.find('.exp-picker-row:visible .exp-picker-check').prop('checked', this.checked);
            updateCount();
        });

        $('#expPickerSearch').on('input', function() {
            var term = ($(this).val() || '').toLowerCase().trim();

            $modal.find('.exp-picker-row').each(function() {
                $(this).toggle(!term || ($(this).data('search') || '').toString().indexOf(term) !== -1);
            });
        });

        $(document).on('click', '.btn-open-exp-picker', function() {
            $modal.find('.exp-picker-check').prop('checked', false);
            $('#expPickerCheckAll').prop('checked', false);
            $('#expPickerSearch').val('').trigger('input');
            updateCount();
            $modal.modal('show');
        });

        // Bootstrap 4 gỡ modal-open khi đóng modal con - trả lại để #createModal còn cuộn được
        $modal.on('hidden.bs.modal', function() {
            if ($('.modal.show').length) {
                $('body').addClass('modal-open');
            }
        });

        $('#expPickerConfirm').on('click', function() {
            var ids = $modal.find('.exp-picker-check:checked').map(function() {
                return $(this).val();
            }).get();

            if (!ids.length) {
                alert('Vui lòng chọn ít nhất một hoá chất trong danh mục tồn.');
                return;
            }

            ids.forEach(function(id) {
                if (window.expAddRow) {
                    window.expAddRow(id);
                }
            });

            $modal.modal('hide');
        });

        // Quét mã / gõ mã ở modal Sử Dụng Hoá Chất: shared/assets.blade.php tra mã xong
        // thì set giá trị vào ô ẩn [name="import_id"] rồi trigger('change') - nghe ở đây
        // để thêm luôn thành 1 dòng, không cần đụng vào shared/assets.blade.php.
        $(document).on('change', '#createModal input[name="import_id"]', function() {
            var id = $(this).val();

            if (id && window.expAddRow) {
                window.expAddRow(id);
            }

            $(this).val('');
        });
    });
</script>
