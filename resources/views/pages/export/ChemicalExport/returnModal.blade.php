{{--
| SỬ DỤNG HOÁ CHẤT - MODAL "TRẢ HOÁ CHẤT VỀ KHO" (tab Trả hoá chất Acetonitril hoặc Methanol về kho)
| Mở từ nút [Trả về kho] trên dòng phiếu sử dụng (.btn-ret-open, data-row). Lượng trả <= lượng còn trả được.
| Ngày trả + người trả do hệ thống ghi (now() / người đăng nhập).
--}}

@php
    $rtBag = $errors->getBag('returnErrors');
@endphp

@if (user_can(\App\Support\ChemicalReturn::PERMISSION))
    <div class="modal fade md-modal" id="retModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-undo-alt mr-2"></i>Trả hoá chất về kho</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form action="{{ route($expRoute . 'returnStore') }}" method="POST">
                    @csrf
                    <input type="hidden" name="export_id">
                    <div class="modal-body">
                        <div class="rt-summary">
                            <div><small>Mã xuất nhập</small><b class="exp-code rt-code">—</b></div>
                            <div><small>Hoá chất</small><b class="rt-chem">—</b></div>
                            <div><small>Ngày sử dụng</small><b class="rt-date">—</b></div>
                            <div><small>Người sử dụng</small><b class="rt-by">—</b></div>
                            <div><small>Đã sử dụng</small><b class="rt-used">—</b></div>
                            <div><small>Đã trả</small><b class="rt-returned">—</b></div>
                            <div><small>Còn trả được</small><b class="rt-returnable text-success">—</b></div>
                        </div>

                        <div class="form-group">
                            <label>Lượng trả về kho <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" inputmode="decimal" name="amount" value="{{ old('amount') }}"
                                    class="form-control js-decimal {{ $rtBag->has('amount') ? 'is-invalid' : '' }}" required autocomplete="off">
                                <div class="input-group-append"><span class="input-group-text rt-unit">—</span></div>
                            </div>
                            @if ($rtBag->has('amount'))
                                <div class="invalid-feedback d-block">{{ $rtBag->first('amount') }}</div>
                            @endif
                            @if ($rtBag->has('export_id'))
                                <div class="invalid-feedback d-block">{{ $rtBag->first('export_id') }}</div>
                            @endif
                            <div class="invalid-feedback rt-over">Lượng trả vượt lượng còn trả được.</div>
                        </div>

                        <div class="form-group mb-0">
                            <label>Lý do trả về <span class="text-danger">*</span></label>
                            <textarea name="note" rows="2" maxlength="500" required class="form-control {{ $rtBag->has('note') ? 'is-invalid' : '' }}">{{ old('note') }}</textarea>
                            @if ($rtBag->has('note'))
                                <div class="invalid-feedback d-block">{{ $rtBag->first('note') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-undo-alt mr-1"></i> Trả về kho</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .rt-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            border-radius: var(--border-radius-md, 8px);
            background: var(--primary-soft);
        }

        .rt-summary small {
            display: block;
            color: var(--primary-dark);
            opacity: 0.8;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var rtCurrent = null;

            function rtCheck() {
                var $m = $('#retModal');
                var raw = String($m.find('[name="amount"]').val() || '').replace(/,/g, '');
                var value = parseFloat(raw);
                var over = rtCurrent && !isNaN(value) && value > rtCurrent.returnable + 0.00005;

                $m.find('[name="amount"]').toggleClass('is-invalid', !!over);
                $m.find('.rt-over').toggleClass('d-block', !!over);
                $m.find('button[type="submit"]').prop('disabled', !!over);
            }

            function rtOpen(r, keepInput) {
                var $m = $('#retModal');
                rtCurrent = r;

                $m.find('[name="export_id"]').val(r.id);
                $m.find('.rt-code').text(r.code || '—');
                $m.find('.rt-chem').text((r.chem_name || '—') + (r.batch_no ? ' · Lô ' + r.batch_no : ''));
                $m.find('.rt-date').text(r.date || '—');
                $m.find('.rt-by').text(r.by || '—');
                $m.find('.rt-used').text((r.used || '0') + ' ' + (r.unit || ''));
                $m.find('.rt-returned').text((r.returned || '0') + ' ' + (r.unit || ''));
                $m.find('.rt-returnable').text((r.returnable_text || '0') + ' ' + (r.unit || ''));
                $m.find('.rt-unit').text(r.unit || '—');

                if (!keepInput) {
                    $m.find('[name="amount"]').val('').removeClass('is-invalid');
                    $m.find('[name="note"]').val('').removeClass('is-invalid');
                    $m.find('.invalid-feedback.d-block').not('.rt-over').remove();
                }

                rtCheck();
                $m.modal('show');
            }

            $(document).on('click', '.btn-ret-open', function () {
                rtOpen($(this).data('row') || {}, false);
            });

            $(document).on('input', '#retModal [name="amount"]', rtCheck);

            @if ($rtBag->any())
                (function () {
                    var oldId = @json((string) old('export_id'));
                    var $btn = $('.btn-ret-open').filter(function () {
                        return String(($(this).data('row') || {}).id) === oldId;
                    }).first();

                    if ($btn.length) rtOpen($btn.data('row') || {}, true);
                })();
            @endif
        });
    </script>
@endif
