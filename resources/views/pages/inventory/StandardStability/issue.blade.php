@php
    /*
    |--------------------------------------------------------------------------
    | CẤP PHÁT CHUẨN THEO TỪNG CHỈ TIÊU KIỂM CỦA MỘT MỐC
    |--------------------------------------------------------------------------
    | Mỗi chỉ tiêu của mốc được tick riêng "đã cấp phát chuẩn" kèm ghi chú riêng, vì
    | chuẩn thường cấp làm nhiều lần chứ không cấp một thể cho cả mốc.
    |
    | Các dòng chỉ tiêu do JS dựng khi mở modal, lấy từ data-row của đúng mốc vừa bấm.
    | Ô tick và ô ghi chú đặt tên THEO TÊN CHỈ TIÊU (issued[] / notes[<tên>]) chứ không
    | theo vị trí, để Controller ghép lại đúng chỉ tiêu kể cả khi danh sách chỉ tiêu của
    | mốc vừa bị sửa ở nơi khác.
    |
    | Biến vào: $ssaRoute, $items, $testingNoteLength
    */

    $bag = $errors->getBag('issueErrors');

    // Form lỗi validate thì modal mở lại, lấy sẵn mốc cũ để tiêu đề không bị trống
    $ssaIssueItem = $bag->any() ? $items->firstWhere('id', (int) old('id')) : null;
@endphp

<div class="modal fade md-modal" id="issueModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-hand-holding-droplet"></i> Cấp Phát Chuẩn Cho Chỉ Tiêu Kiểm
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($ssaRoute . 'issueTestings') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label>Mốc Đánh Giá</label>
                            <input type="text" class="form-control ssa-readonly ssa-is-name" readonly
                                value="{{ $ssaIssueItem ? $ssaIssueItem->name . ' (T' . $ssaIssueItem->timepoint . ')' : '' }}">
                        </div>

                        <div class="form-group col-md-5">
                            <label>Ngày Đến Hạn</label>
                            <input type="text" class="form-control ssa-readonly ssa-is-due" readonly
                                value="{{ $ssaIssueItem && $ssaIssueItem->due_date ? \Carbon\Carbon::parse($ssaIssueItem->due_date)->format('d/m/Y') : '' }}">
                        </div>
                    </div>

                    <div class="ssa-issue-head">
                        <span><i class="fas fa-vials mr-1"></i> Chỉ tiêu kiểm của mốc này</span>
                        <span class="ssa-issue-count"></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm ssa-issue-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 44px" class="text-center"
                                        title="Tick khi đã cấp phát chuẩn cho chỉ tiêu này">
                                        <i class="fas fa-check"></i>
                                    </th>
                                    <th style="width: 230px">Chỉ Tiêu Kiểm</th>
                                    <th>Ghi Chú Cấp Phát</th>
                                </tr>
                            </thead>
                            <tbody class="ssa-issue-rows"></tbody>
                        </table>
                    </div>

                    @if ($bag->any())
                        <div class="mt-2">
                            @foreach ($bag->all() as $message)
                                <span class="md-error d-block">{{ $message }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="md-hint mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Tick vào chỉ tiêu đã <b>cấp phát chuẩn</b> cho người kiểm, ghi chú tối đa
                        {{ $testingNoteLength }} ký tự cho mỗi chỉ tiêu (số ống đã cấp, ngày cấp, người nhận...).
                        Bỏ tick thì phần ghi chú vẫn giữ lại. Mọi lần ghi đều lưu trong
                        <b>Lịch sử thay đổi</b> của phiếu.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu cấp phát
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* Dựng các dòng chỉ tiêu của một mốc vào bảng trong modal */
        function buildRows(testings) {
            var $rows = $('#issueModal .ssa-issue-rows').empty();
            var issued = 0;

            if (!testings.length) {
                $rows.append(
                    '<tr><td colspan="3" class="text-center md-empty py-3">' +
                    'Mốc này chưa chọn chỉ tiêu kiểm nào - hãy sửa mốc để chọn chỉ tiêu trước.' +
                    '</td></tr>');
            }

            testings.forEach(function(testing) {
                var name = testing.name || '';
                var note = testing.note || '';

                if (testing.issued) issued++;

                // Tên chỉ tiêu do người dùng khai nên phải escape trước khi ghép vào HTML
                var $row = $('<tr></tr>');
                var $tick = $('<td class="text-center"></td>');
                var $name = $('<td class="ssa-issue-name"></td>').text(name);
                var $note = $('<td></td>');

                $('<input type="checkbox" class="ssa-issue-check">')
                    .attr('name', 'issued[]').val(name).prop('checked', !!testing.issued)
                    .appendTo($tick);

                $('<input type="text" class="form-control form-control-sm">')
                    .attr('name', 'notes[' + name + ']')
                    .attr('maxlength', {{ $testingNoteLength }})
                    .attr('placeholder', 'Ví dụ: cấp 2 ống ngày ' + new Date().toLocaleDateString('vi-VN'))
                    .val(note)
                    .appendTo($note);

                $row.append($tick).append($name).append($note).appendTo($rows);
            });

            $('#issueModal .ssa-issue-count').text(
                testings.length ? issued + '/' + testings.length + ' chỉ tiêu đã cấp phát' : '');
        }

        /* Cho phần mở lại sau lỗi validate dựng lại bảng bằng cùng một hàm */
        $('#issueModal .ssa-issue-rows').on('ssa:rebuild', function(e, testings) {
            buildRows(testings || []);
        });

        /* Mở modal cấp phát: đổ dữ liệu của đúng mốc vừa bấm */
        $(document).on('click', '.btn-ssa-issue', function() {
            var row = $(this).data('row') || {};
            var $form = $('#issueModal').find('form');

            $form.find('.md-error').remove();

            $form.find('[name="id"]').val(row.id);
            $form.find('.ssa-is-name').val((row.name || '') + ' (T' + row.timepoint + ')');
            $form.find('.ssa-is-due').val(row.due_date || '');

            buildRows(row.testings || []);

            $('#issueModal').modal('show');
        });

        /* Bỏ tick vẫn giữ ghi chú, nhưng đếm lại ngay cho khớp với những gì đang thấy */
        $(document).on('change', '#issueModal .ssa-issue-check', function() {
            var total = $('#issueModal .ssa-issue-check').length;
            var issued = $('#issueModal .ssa-issue-check:checked').length;

            $('#issueModal .ssa-issue-count').text(
                total ? issued + '/' + total + ' chỉ tiêu đã cấp phát' : '');
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /*
            | Mở lại sau lỗi validate: dựng lại các dòng chỉ tiêu từ mốc đang sửa, rồi đắp
            | đè những gì người dùng vừa gõ (old input) để không phải nhập lại từ đầu.
            */
            var testings = @json($ssaIssueItem ? $ssaIssueItem->testing_list : []);
            var issued = @json((array) old('issued', []));
            var notes = @json((array) old('notes', []));

            testings = testings.map(function(testing) {
                return {
                    name: testing.name,
                    issued: issued.indexOf(testing.name) !== -1,
                    note: notes[testing.name] || ''
                };
            });

            $('#issueModal .ssa-issue-rows').trigger('ssa:rebuild', [testings]);
            $('#issueModal').modal('show');
        });
    </script>
@endif
