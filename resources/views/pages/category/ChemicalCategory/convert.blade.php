{{--
| Công cụ quy đổi đơn vị cho một hoá chất trong danh mục.
| Phép tính chạy ở server (App\Support\UnitConverter) chứ không viết lại bằng JS,
| để màn nhập/xuất sau này dùng chung đúng một công thức.
--}}

<div class="modal fade md-modal" id="convertModal" tabindex="-1" role="dialog"
    data-url="{{ route($mdRoute . 'convert') }}">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-balance-scale"></i>
                    Quy Đổi Đơn Vị
                    <small class="cat-convert-subtitle md-sub ml-2"></small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                <input type="hidden" class="cat-convert-id">

                <div class="row">
                    <div class="col-5">
                        <div class="form-group">
                            <label>Số Lượng</label>
                            <input type="number" class="form-control cat-convert-qty" step="0.000001" min="0.000001"
                                value="1">
                        </div>
                    </div>

                    <div class="col-7">
                        <div class="form-group">
                            <label>Từ Đơn Vị</label>
                            <select class="form-control cat-convert-from">
                                @foreach ($units as $option)
                                    <option value="{{ $option->id }}">{{ $option->short_name }} -
                                        {{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Sang Đơn Vị</label>
                    <select class="form-control cat-convert-to">
                        @foreach ($units as $option)
                            <option value="{{ $option->id }}">{{ $option->short_name }} - {{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" class="btn btn-primary btn-block cat-convert-run">
                    <i class="fas fa-exchange-alt mr-1"></i> Quy đổi
                </button>

                <div class="cat-convert-result mt-3"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
    .cat-convert-out {
        background: var(--primary-soft);
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        padding: 14px;
        text-align: center;
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .cat-convert-err {
        background: #FEE2E2;
        border: 1px solid #FCA5A5;
        border-radius: var(--border-radius-md);
        padding: 11px 13px;
        font-size: 0.85rem;
        color: #B91C1C;
    }

    .cat-convert-note {
        margin-top: 7px;
        font-size: 0.8rem;
        color: #94a3b8;
        text-align: center;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $modal = $('#convertModal');

        $(document).on('click', '.btn-cat-convert', function() {
            $modal.find('.cat-convert-id').val($(this).data('id'));
            $modal.find('.cat-convert-subtitle').text($(this).data('title') || '');
            $modal.find('.cat-convert-result').empty();
            $modal.find('.cat-convert-qty').val(1);

            // Danh mục không còn khai đơn vị gốc (đơn vị nằm ở từng phòng), nên để người
            // dùng tự chọn cặp đơn vị cần quy đổi.
            $modal.modal('show');
        });

        $(document).on('click', '.cat-convert-run', function() {
            var params = new URLSearchParams({
                id: $modal.find('.cat-convert-id').val(),
                from: $modal.find('.cat-convert-from').val(),
                to: $modal.find('.cat-convert-to').val(),
                quantity: $modal.find('.cat-convert-qty').val()
            });

            var $out = $modal.find('.cat-convert-result')
                .html('<div class="cat-convert-note"><i class="fas fa-spinner fa-spin"></i> Đang tính...</div>');

            fetch($modal.data('url') + '?' + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    $out.empty();

                    if (!data.ok) {
                        $('<div>').addClass('cat-convert-err').text(data.reason).appendTo($out);
                        return;
                    }

                    $('<div>').addClass('cat-convert-out').text(data.text).appendTo($out);

                    if (data.note) {
                        $('<div>').addClass('cat-convert-note').text(data.note).appendTo($out);
                    }
                })
                .catch(function() {
                    $out.html('').append(
                        $('<div>').addClass('cat-convert-err').text('Không tính được, vui lòng thử lại.')
                    );
                });
        });
    });
</script>
