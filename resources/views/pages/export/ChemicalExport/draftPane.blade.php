{{--
| SỬ DỤNG - TAB "SỬ DỤNG HOÁ CHẤT" (tab đầu, khoá quyền vẫn là 'draft')
|
| Có nút "Sử dụng hoá chất" mở modal lập phiếu (create.blade.php) + danh sách Phiếu Tạm bên dưới.
|
| Hoá chất đã chọn ở picker "Tồn Kho Của Phòng" rồi bấm Lưu Tạm - CHỈ chứa dòng loại
| Sử dụng, chưa trừ kho. Loại bỏ / Chuyển kho luôn trừ kho ngay nên không bao giờ
| xuất hiện ở đây (xem picker.blade.php).
|
| MỖI NGƯỜI MỘT PHIẾU TẠM DUY NHẤT: mọi dòng người đó lưu tạm gom vào cùng một card,
| lưu tạm thêm lần nữa là nối tiếp vào phiếu đang có chứ không mở phiếu mới. Mã đợt
| (batch_code) chỉ còn là khoá kỹ thuật trong DB, không hiện ra màn hình.
--}}

@php
    $draftErrBag = $errors->getBag('draftErrors');

    // Phiếu Tạm là giỏ nháp riêng: chỉ người đã bấm Lưu Tạm mới dùng / xoá được phiếu
    // của mình, người khác trong phòng chỉ nhìn thấy (Controller chặn lại khi gửi form).
    $draftActor = \App\Support\Signer::actor();

    // Giá trị đổ vào ô nhập KHÔNG có dấu phân cách nghìn, tránh "1,234.5" gửi lên bị
    // đọc nhầm; các ô chỉ xem vẫn dùng $expNum (có dấu ",") cho dễ đọc.
    $draftRaw = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');

    // Phiếu của người đang đăng nhập luôn đứng đầu, người khác giữ nguyên thứ tự Controller trả về
    $drafts = $drafts->sortBy(fn ($rows, $owner) => $owner === $draftActor ? 0 : 1);
@endphp

<style>
    /* ---------- Card Phiếu Tạm theo từng người: viền đậm + thu gọn / phóng to ---------- */
    #expPaneDraft .draft-card {
        border: 1.5px solid var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    #expPaneDraft .draft-card:hover {
        border-color: var(--primary-light);
    }

    #expPaneDraft .draft-card.is-mine {
        border: 2px solid var(--primary);
        border-left-width: 5px;
    }

    #expPaneDraft .draft-card>.card-body {
        padding: 14px 18px;
    }

    #expPaneDraft .draft-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    #expPaneDraft .draft-card-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
        cursor: pointer;
        user-select: none;
    }

    #expPaneDraft .draft-toggle {
        width: 28px;
        height: 28px;
        padding: 0;
        margin-right: 6px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
        color: var(--primary-dark);
        transition: all 0.2s;
    }

    #expPaneDraft .draft-toggle:hover {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    #expPaneDraft .draft-toggle i {
        transition: transform 0.25s;
    }

    #expPaneDraft .draft-card.is-collapsed .draft-toggle i {
        transform: rotate(-90deg);
    }

    #expPaneDraft .draft-card-body {
        margin-top: 12px;
    }

    #expPaneDraft .draft-card.is-collapsed .draft-card-body {
        display: none;
    }
</style>

@tab('export_chemical', 'draft')
<div class="exp-pane {{ $activeTab === 'draft' ? 'is-active' : '' }}" id="expPaneDraft">

    <div class="md-toolbar">
        @perm('export_chemical_issue')
            <button type="button" class="btn btn-primary btn-md-create">
                <i class="fas fa-plus mr-1"></i> Sử dụng hoá chất
            </button>
        @endperm
    </div>

    @if ($draftErrBag->any())
        <div class="alert alert-danger">
            <b>Không dùng ngay được Phiếu Tạm:</b>
            <ul class="mb-0">
                @foreach ($draftErrBag->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @forelse ($drafts as $draftOwnerKey => $rows)
        @php
            $draftOwner = $draftOwnerKey ?: '—';
            $draftIsMine = $draftOwnerKey === $draftActor;
            $draftSavedAt = $rows->max('created_at');
        @endphp
        {{-- data-owner: khoá nhớ trạng thái thu gọn / phóng to của từng card trên trình duyệt người xem --}}
        <div class="card md-card mb-3 draft-card {{ $draftIsMine ? 'is-mine' : '' }}" data-owner="{{ $draftOwner }}">
            <div class="card-body">
                {{-- Cả card là MỘT form: các ô sửa của mọi dòng cùng gửi lên, nút nào bấm thì
                     JS đổi action + đặt only_id cho nút đó (xem khối script cuối file) --}}
                <form method="POST" action="{{ route($expRoute . 'draftFinalize') }}" class="draft-form">
                    @csrf
                    <input type="hidden" name="only_id" value="">

                <div class="draft-card-head">
                    <div class="draft-card-title" title="Thu gọn / phóng to">
                        <button type="button" class="btn draft-toggle" aria-label="Thu gọn / phóng to">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <i class="fas fa-user-clock mr-1 text-primary"></i>
                        <b>{{ $draftOwner }}</b>
                        @if ($draftIsMine)
                            <span class="badge badge-primary ml-1">Của tôi</span>
                        @endif
                        <span class="md-sub ml-2">
                            {{ $rows->count() }} dòng · Lưu tạm gần nhất
                            {{ \Carbon\Carbon::parse($draftSavedAt)->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <div class="md-actions">
                        @if (!$draftIsMine)
                            <span class="md-sub">
                                <i class="fas fa-lock mr-1"></i> Chỉ {{ $draftOwner }} thao tác được
                            </span>
                        @else
                            <button type="button" class="btn btn-sm btn-primary draft-action"
                                data-url="{{ route($expRoute . 'draftFinalize') }}"
                                data-title="Lưu toàn bộ Phiếu Tạm của bạn?"
                                data-text="Hệ thống kiểm tra lại tồn / hạn mức tại thời điểm này rồi ghi {{ $rows->count() }} dòng vào Sổ sử dụng, trừ kho ngay.">
                                <i class="fas fa-save mr-1"></i> Lưu Toàn Bộ
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary draft-action" data-danger="1"
                                data-url="{{ route($expRoute . 'draftDeleteBatch') }}"
                                data-title="Xoá cả Phiếu Tạm của bạn?"
                                data-text="Xoá {{ $rows->count() }} dòng khỏi Phiếu Tạm. Chưa trừ kho nên không ảnh hưởng tồn.">
                                <i class="fas fa-trash mr-1"></i> Xoá Cả Phiếu
                            </button>
                        @endif
                    </div>
                </div>

                <div class="table-responsive draft-card-body">
                    {{-- data-no-datatable: bảng có ô nhập, để DataTables gỡ dòng sang trang khác
                         thì các ô đang sửa rời khỏi DOM và không gửi lên được --}}
                    <table class="table table-bordered table-sm md-table mb-0" data-no-datatable>
                        <thead>
                            <tr>
                                <th>Mã Xuất Nhập</th>
                                <th>Hoá Chất</th>
                                <th style="width: 110px">Số Lô</th>
                                <th class="text-right" style="width: 110px">Số Lượng</th>
                                <th style="width: 150px">Người Kiểm Tra</th>
                                <th>Mục Đích Sử Dụng</th>
                                <th class="text-center" style="width: 110px">Ngày Lưu</th>
                                <th style="width: 150px">Người Lưu</th>
                                <th class="text-center" style="width: 110px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr @class(['draft-row-over' => $row->draft_over ?? false])>
                                    <td>
                                        <span class="exp-code">{{ $row->import_code ?: '—' }}</span>
                                        <div class="md-sub mt-1"><span class="md-tag">{{ $row->category_code ?: '—' }}</span></div>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">
                                            {{ $row->chem_name ?: '—' }}
                                            @if ($expIsSpecial($row->category_id))
                                                <span class="badge-special-control ml-1" title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">
                                                    <i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="md-sub">{{ $row->batch_no ?: '—' }}</td>
                                    @if ($draftIsMine)
                                        {{-- Chủ phiếu sửa thẳng trên bảng, bấm Lưu là dùng đúng
                                             giá trị vừa sửa --}}
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" inputmode="decimal"
                                                    name="items[{{ $row->id }}][amount]"
                                                    class="form-control form-control-sm text-right js-decimal{{ ($row->draft_over ?? false) ? ' is-invalid' : '' }}"
                                                    value="{{ $draftRaw($row->amount) }}" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        {{ $row->unit_short_name ?: $row->unit_name }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if ($row->draft_over ?? false)
                                                <div class="exp-required-note">
                                                    Vượt hạn mức {{ $expNum($row->draft_limit) }}, giảm bớt trước khi lưu
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($expBanned($row->category_id))
                                                <select name="items[{{ $row->id }}][checked_by]"
                                                    class="form-control form-control-sm" required>
                                                    <option value="">-- Chọn người kiểm tra --</option>
                                                    @foreach ($checkers as $checker)
                                                        <option value="{{ $checker->fullName }}"
                                                            {{ $row->checked_by === $checker->fullName ? 'selected' : '' }}>
                                                            {{ $checker->fullName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="md-empty">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $row->id }}][purpose]" maxlength="500" autocomplete="off"
                                                class="form-control form-control-sm js-product-ac" value="{{ $row->purpose }}"
                                                placeholder="Mục đích / lý do">
                                        </td>
                                    @else
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
                                    @endif
                                    <td class="text-center md-sub">
                                        {{ $expDate($row->created_at) }}
                                    </td>
                                    <td class="md-sub">{{ $row->created_by ?: '—' }}</td>
                                    <td class="text-center">
                                        @if ($draftIsMine)
                                            <button type="button" class="btn btn-sm btn-primary draft-action"
                                                data-id="{{ $row->id }}"
                                                data-url="{{ route($expRoute . 'draftFinalize') }}"
                                                data-title="Lưu dòng này vào Sổ sử dụng?"
                                                data-text="{{ $row->chem_name }} - mã {{ $row->import_code }}. Hệ thống kiểm tra lại tồn / hạn mức rồi ghi thật, trừ kho ngay."
                                                title="Lưu dòng này">
                                                <i class="fas fa-save"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger draft-action"
                                                data-id="{{ $row->id }}" data-danger="1"
                                                data-url="{{ route($expRoute . 'draftDeleteItem') }}"
                                                data-title="Xoá dòng này khỏi Phiếu Tạm?"
                                                data-text="{{ $row->chem_name }} - {{ $expNum($row->amount) }} {{ $row->unit_short_name ?: $row->unit_name }}."
                                                title="Xoá dòng này">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @else
                                            <i class="fas fa-lock md-sub" title="Chỉ {{ $draftOwner }} thao tác được"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </form>
            </div>
        </div>
    @empty
        <div class="text-center md-empty py-4">
            Chưa có Phiếu Tạm nào.
        </div>
    @endforelse
</div>
@endtab

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /*
        | THU GỌN / PHÓNG TO card từng người. Mặc định phiếu của mình mở, phiếu người khác thu gọn;
        | người xem đổi thì nhớ lại trên trình duyệt (localStorage, lỗi / chặn thì dùng mặc định).
        */
        var draftStoreKey = 'wms.chemicalExport.draftCollapsed';

        function draftLoadState() {
            try {
                return JSON.parse(localStorage.getItem(draftStoreKey) || '{}') || {};
            } catch (e) {
                return {};
            }
        }

        function draftSaveState(state) {
            try {
                localStorage.setItem(draftStoreKey, JSON.stringify(state));
            } catch (e) {}
        }

        var draftState = draftLoadState();
        var draftHasErrors = {{ $draftErrBag->any() ? 'true' : 'false' }};

        $('#expPaneDraft .draft-card').each(function() {
            var owner = String($(this).data('owner'));
            var collapsed = owner in draftState ? !!draftState[owner] : !$(this).hasClass('is-mine');

            // Vừa lưu lỗi thì luôn mở phiếu của mình để thấy dòng cần sửa
            if (draftHasErrors && $(this).hasClass('is-mine')) collapsed = false;

            $(this).toggleClass('is-collapsed', collapsed);
        });

        $(document).on('click', '#expPaneDraft .draft-card-title', function() {
            var $card = $(this).closest('.draft-card');

            $card.toggleClass('is-collapsed');
            draftState[String($card.data('owner'))] = $card.hasClass('is-collapsed');
            draftSaveState(draftState);
        });

        /*
        | Mọi nút của Phiếu Tạm đi chung MỘT form của card để phần người dùng đang sửa
        | trên bảng luôn được gửi kèm. Nút nào bấm thì đặt lại action + only_id của nút
        | đó rồi mới gửi - không dùng form-md-confirm vì hàm đó gọi form.submit() nên
        | mất tên/giá trị của nút bấm.
        */
        $(document).on('click', '#expPaneDraft .draft-action', function() {
            var $btn = $(this);
            var $form = $btn.closest('form.draft-form');

            // Ô nhập nằm trong card đang thu gọn thì trình duyệt không báo lỗi được - mở card ra trước
            $btn.closest('.draft-card').removeClass('is-collapsed');

            // Số lượng bỏ trống / người kiểm tra bắt buộc còn thiếu thì báo ngay tại ô.
            // Nút xoá không cần xét: đang xoá thì ô nhập còn thiếu cũng không sao.
            if (!$btn.data('danger') && !$form[0].reportValidity()) {
                return;
            }

            Swal.fire({
                title: $btn.data('title'),
                text: $btn.data('text'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: $btn.data('danger') ? '#DC2626' : '#2E7BC4',
                cancelButtonColor: '#94A3B8',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Huỷ'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $form.find('[name="only_id"]').val($btn.data('id') || '');
                $form.attr('action', $btn.data('url'));
                $form[0].submit();
            });
        });
    });
</script>
