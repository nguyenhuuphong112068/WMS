{{--
|--------------------------------------------------------------------------
| DỰ TRÙ - KHỐI KHAI QUY TRÌNH KÝ DUYỆT (dùng trong modal Lập / Sửa phiếu)
|--------------------------------------------------------------------------
| Người lập tự xếp các bước ký; mỗi bước một người. BƯỚC CUỐI cố định là Ban Giám
| Đốc (ô chọn chỉ liệt kê người thuộc vai trò config('estimate.bod_roles')), không
| xoá được. Tối thiểu 2 bước: ít nhất 1 bước thường + 1 bước Ban Giám Đốc.
|
| Biến vào:
|   $signerOptions  - danh sách người ký chọn được (id, fullName, userName, role_name, role_names[], department_short)
|   $flowSigners    - mảng user_id đã khai (rỗng khi lập mới); phần tử cuối là người ký bước cuối
|   $flowErrorBag   - tên error bag ('createErrors' | 'updateErrors')
--}}
@php
    $flowSigners = array_values(array_filter((array) ($flowSigners ?? []), fn ($v) => (int) $v > 0));
    $esfBag = $errors->getBag($flowErrorBag ?? 'createErrors');

    $esfBodRoles = config('estimate.bod_roles');
    // Người ký bước cuối: có BẤT KỲ vai trò nào thuộc bod_roles (role chính hoặc role gán),
    // không phụ thuộc vai trò nào được đặt làm chính.
    $esfBodOptions = collect($signerOptions)->filter(fn ($p) => count(array_intersect($p->role_names ?? [], $esfBodRoles)) > 0)->values();

    // Bước cuối = người ký cuối cùng đã khai (nếu có), các bước còn lại là bước thường
    $esfLast = count($flowSigners) ? (int) end($flowSigners) : 0;
    $esfIntermediate = count($flowSigners) > 1 ? array_slice($flowSigners, 0, -1) : [];

    $esfLabel = function ($p) {
        $role = $p->role_name ?: implode(' / ', $p->role_names ?? []);
        return ($p->fullName ?: $p->userName)
            . ($role ? ' — ' . $role : '')
            . ($p->department_short ? ' · ' . $p->department_short : '');
    };
@endphp

<div class="form-group esf-box" data-has-bod="{{ $esfBodOptions->count() ? 1 : 0 }}">
    <label class="d-flex align-items-center justify-content-between mb-1">
        <span><i class="fas fa-tasks mr-1"></i> Quy Trình Ký Duyệt <span class="text-danger">*</span></span>
        <button type="button" class="btn btn-sm btn-outline-primary esf-add">
            <i class="fas fa-plus mr-1"></i> Thêm bước ký
        </button>
    </label>

    <div class="esf-steps">
        @foreach ($esfIntermediate as $signerId)
            <div class="esf-row">
                <span class="esf-no"></span>
                <select name="signers[]" class="form-control esf-user" required>
                    <option value="">-- Chọn người ký --</option>
                    @foreach ($signerOptions as $person)
                        <option value="{{ $person->id }}" title="{{ $esfLabel($person) }}" {{ (int) $signerId === (int) $person->id ? 'selected' : '' }}>
                            {{ $esfLabel($person) }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-danger esf-del" title="Xoá bước ký"><i class="fas fa-trash-alt"></i></button>
            </div>
        @endforeach

        {{-- Bước cuối cố định: Ban Giám Đốc --}}
        <div class="esf-row esf-bod-row">
            <span class="esf-no"></span>
            <select name="signers[]" class="form-control esf-user" required>
                <option value="">-- Chọn Ban Giám Đốc --</option>
                @foreach ($esfBodOptions as $person)
                    <option value="{{ $person->id }}" title="{{ $esfLabel($person) }}" {{ $esfLast === (int) $person->id ? 'selected' : '' }}>
                        {{ $esfLabel($person) }}
                    </option>
                @endforeach
            </select>
            <span class="esf-bod-tag"><i class="fas fa-stamp mr-1"></i>Bước cuối</span>
        </div>
    </div>

    {{-- Danh sách người ký đầy đủ - JS nhân bản khi thêm bước thường mới --}}
    <script type="text/template" class="esf-proto-options">
        <option value="">-- Chọn người ký --</option>
        @foreach ($signerOptions as $person)
            <option value="{{ $person->id }}" title="{{ $esfLabel($person) }}">{{ $esfLabel($person) }}</option>
        @endforeach
    </script>

    <div class="esf-warn text-danger small mt-1" hidden>
        <i class="fas fa-triangle-exclamation mr-1"></i>Quy trình ký duyệt phải có tối thiểu 2 bước.
    </div>

    @unless ($esfBodOptions->count())
        <div class="text-danger small mt-1">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            Công ty chưa có tài khoản nào thuộc vai trò {{ implode(' / ', $esfBodRoles) }} để ký bước cuối.
        </div>
    @endunless

    @if ($esfBag->has('signers'))
        <span class="md-error d-block">{{ $esfBag->first('signers') }}</span>
    @endif

    <span class="md-hint d-block mt-1">
        <i class="fas fa-info-circle mr-1"></i>
        Ký lần lượt từ trên xuống; bước cuối bắt buộc là Ban Giám Đốc. Khi bấm <b>Trình ký</b> phiếu
        chuyển tới người ký bước 1.
    </span>
</div>

<style>
    .esf-box .esf-steps {
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: var(--bg-neutral);
        padding: 10px 10px 2px;
    }

    .esf-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .esf-row .esf-no {
        flex: 0 0 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--primary);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .esf-row .esf-user {
        flex: 1 1 auto;
    }

    .esf-row .esf-del {
        flex: 0 0 auto;
        border-radius: var(--border-radius-md);
    }

    .esf-bod-row .esf-no {
        background: var(--primary-dark);
    }

    .esf-bod-tag {
        flex: 0 0 auto;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--primary-dark);
        background: var(--primary-soft);
        border: 1px solid var(--primary-lighter);
        border-radius: 999px;
        padding: 3px 10px;
        white-space: nowrap;
    }
</style>

<script>
    // Khối "model" render TRƯỚC khi nạp jQuery (xem layout/master.blade.php) nên phải đợi
    // DOMContentLoaded rồi mới dùng $.
    document.addEventListener('DOMContentLoaded', function () {
        // Partial này có thể được nhúng nhiều lần - chỉ gắn handler một lần
        if (window.__esfInit) return;
        window.__esfInit = true;

        function esfSync(box) {
            var $box = $(box);
            var $rows = $box.find('.esf-row');

            $rows.each(function (i) {
                $(this).find('.esf-no').text(i + 1);
            });

            // Cần tối thiểu 1 bước thường + 1 bước Ban Giám Đốc
            var intermediate = $box.find('.esf-row').not('.esf-bod-row').length;
            $box.find('.esf-warn').prop('hidden', intermediate >= 1);
            $box.find('.esf-del').prop('disabled', intermediate <= 1);
        }

        function esfAddRow($box) {
            var optionsHtml = $box.find('.esf-proto-options').html();
            var $row = $('<div class="esf-row">' +
                '<span class="esf-no"></span>' +
                '<select name="signers[]" class="form-control esf-user" required></select>' +
                '<button type="button" class="btn btn-outline-danger esf-del" title="Xoá bước ký"><i class="fas fa-trash-alt"></i></button>' +
                '</div>');
            $row.find('select').html(optionsHtml);
            $box.find('.esf-bod-row').before($row);
            esfSync($box);

            return $row;
        }

        // Dựng lại các bước ký của một phiếu khi mở modal Sửa (assets.blade.php gọi).
        window.esfLoad = function (box, signerIds) {
            var $box = $(box);
            signerIds = (signerIds || []).map(Number).filter(Boolean);

            $box.find('.esf-row').not('.esf-bod-row').remove();

            var bodId = signerIds.length ? signerIds[signerIds.length - 1] : '';
            var intermediate = signerIds.length > 1 ? signerIds.slice(0, -1) : [];

            intermediate.forEach(function (id) {
                esfAddRow($box).find('select').val(String(id));
            });

            if ($box.find('.esf-row').not('.esf-bod-row').length === 0) {
                esfAddRow($box);
            }

            $box.find('.esf-bod-row select').val(bodId ? String(bodId) : '');
            esfSync($box);
        };

        $(document).on('click', '.esf-add', function () {
            esfAddRow($(this).closest('.esf-box'));
        });

        $(document).on('click', '.esf-del', function () {
            var $box = $(this).closest('.esf-box');
            $(this).closest('.esf-row').remove();
            esfSync($box);
        });

        $('.esf-box').each(function () {
            var $box = $(this);
            // Lập phiếu mới: chưa có bước thường -> mở sẵn 1 dòng
            if ($box.find('.esf-row').not('.esf-bod-row').length === 0) {
                esfAddRow($box);
            }
            esfSync($box);
        });
    });
</script>
