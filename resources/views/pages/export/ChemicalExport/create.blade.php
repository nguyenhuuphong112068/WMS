@php
    $bag = $errors->getBag('createErrors');
    // Số lô còn dùng được - hiện trên nút "Tồn Kho Của Phòng"
    $expSelectableCount = $imports->where('selectable', true)->count();
@endphp

<style>
    /* ==========================================================
     | MODAL SỬ DỤNG HOÁ CHẤT
     | Khối 1: chọn hoá chất (quét mã | tồn kho của phòng)
     | Khối 2: bảng hoá chất đã chọn, thông tin lô tách cột, Số Lượng kiểm tra ngay khi gõ
     ========================================================== */
    #createModal .exp-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 10px;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    #createModal .exp-step {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--primary);
        color: #fff;
        font-size: 0.75rem;
    }

    #createModal .exp-count-pill {
        padding: 2px 10px;
        border-radius: 999px;
        background: var(--primary-soft);
        border: 1px solid var(--primary-lighter);
        color: var(--primary-dark);
        font-size: 0.75rem;
        text-transform: none;
        letter-spacing: 0;
    }

    /* ---------- Khối 1: 2 cách chọn hoá chất ---------- */
    #createModal .exp-pick-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(240px, 300px);
        gap: 14px;
        margin-bottom: 22px;
    }

    #createModal .exp-pick-grid .exp-scan {
        margin-bottom: 0;
    }

    #createModal .exp-method {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 12px 14px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--primary-soft);
    }

    #createModal .exp-method>label {
        display: block;
        margin-bottom: 6px;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    #createModal .exp-method .btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }

    #createModal .exp-method .btn:hover {
        transform: translateY(-1px);
    }

    #createModal .exp-method .exp-method-badge {
        padding: 1px 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.25);
        font-size: 0.75rem;
    }

    @media (max-width: 767.98px) {
        #createModal .exp-pick-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ---------- Khối 2: bảng hoá chất đã chọn ---------- */
    #expRowsTable {
        min-width: 1200px;
        font-size: 0.86rem;
    }

    #expRowsTable thead th {
        white-space: nowrap;
        vertical-align: middle;
    }

    #expRowsTable td {
        vertical-align: top;
        padding: 8px;
    }

    #expRowsTable td.exp-cell-mid {
        vertical-align: middle;
    }

    #expRowsTable .exp-row-no {
        color: var(--primary-dark);
        font-weight: 700;
        text-align: center;
    }

    #expRowsTable .exp-chem-name {
        font-weight: 700;
        color: var(--text-main);
        line-height: 1.35;
    }

    #expRowsTable .exp-code {
        font-family: Consolas, monospace;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    #expRowsTable .exp-num {
        text-align: right;
        white-space: nowrap;
        font-weight: 600;
    }

    #expRowsTable .exp-unit {
        color: #64748B;
        font-weight: 400;
    }

    #expRowsTable .exp-date-soon {
        color: #B45309;
        font-size: 0.75rem;
    }

    #expRowsTable tr.is-banned {
        background: #FEF7E6;
    }

    /* Ô Số Lượng + dòng trạng thái kiểm tra ngay khi gõ */
    #expRowsTable .exp-amount .input-group-text {
        min-width: 44px;
        justify-content: center;
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-size: 0.78rem;
    }

    #expRowsTable .exp-amount .form-control {
        text-align: right;
        font-weight: 700;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    #expRowsTable .exp-amount .form-control.is-warn {
        border-color: #F59E0B;
    }

    /* Cột Số Lượng Dùng giữ độ rộng cố định, câu báo dài thì xuống dòng */
    #expRowsTable td.exp-amount {
        width: 230px;
        min-width: 200px;
        max-width: 260px;
    }

    #expRowsTable .exp-amt-status {
        max-width: 250px;
        white-space: normal;
        overflow-wrap: anywhere;
        margin-top: 4px;
        font-size: 0.74rem;
        line-height: 1.35;
        color: #64748B;
    }

    #expRowsTable .exp-amt-status.is-ok {
        color: #16A34A;
    }

    #expRowsTable .exp-amt-status.is-warn {
        color: #B45309;
    }

    #expRowsTable .exp-amt-status.is-bad {
        color: #DC2626;
        font-weight: 600;
    }

    #expRowsTable .exp-amt-status .exp-amt-reserved {
        display: block;
        color: #B45309;
    }

    #expRowsTable .exp-required-note {
        margin-top: 4px;
        font-size: 0.72rem;
        color: #B91C1C;
    }

    #expRowsTable .exp-purpose {
        min-height: calc(1.8125rem + 2px);
        resize: none;
        overflow: hidden;
        line-height: 1.4;
    }

    /* Danh sách gợi ý tên sản phẩm - gắn vào <body>, định vị fixed theo ô đang gõ để không bị
       khung cuộn ngang của bảng cắt mất */
    .exp-ac-menu {
        position: fixed;
        z-index: 2000;
        max-height: 260px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-md, 0 8px 24px rgba(15, 23, 42, 0.12));
        font-size: 0.86rem;
    }

    .exp-ac-menu .exp-ac-item {
        padding: 7px 12px;
        cursor: pointer;
        color: var(--text-main);
        transition: background 0.15s;
    }

    .exp-ac-menu .exp-ac-item i {
        color: var(--primary-light);
    }

    .exp-ac-menu .exp-ac-item:hover,
    .exp-ac-menu .exp-ac-item.is-active {
        background: var(--primary-soft);
        color: var(--primary-dark);
    }

    .exp-ac-menu .exp-ac-more {
        padding: 6px 12px;
        font-size: 0.75rem;
        color: #64748B;
        text-align: center;
    }

    #createModal .exp-empty {
        padding: 28px 16px;
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--bg-neutral);
        color: #64748B;
        text-align: center;
    }

    #createModal .exp-empty i {
        display: block;
        margin-bottom: 8px;
        font-size: 1.6rem;
        color: var(--primary-lighter);
    }
</style>

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 94vw;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $expIcon }}"></i> Sử Dụng Hoá Chất</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'storeBatch') }}" method="POST" id="expCreateForm">
                @csrf
                <input type="hidden" name="mode" class="exp-mode-input" value="use">
                {{-- Chỉ lập phiếu Sử dụng - hoá chất hỏng đi quy trình Cách ly ở Tồn Kho Hoá Chất --}}
                <input type="hidden" name="type" value="export">
                {{-- Kênh nhận kết quả quét mã: shared/assets.blade.php tự set giá trị field này khi
                     tra mã thành công, JS của picker.blade.php lắng nghe change để thêm dòng --}}
                <input type="hidden" name="import_id">

                <div class="modal-body">
                    @if ($bag->any())
                        <div class="alert alert-danger">
                            <b>Không lưu được, kiểm tra lại:</b>
                            <ul class="mb-0">
                                @foreach ($bag->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ===== KHỐI 1: CHỌN HOÁ CHẤT CẦN DÙNG ===== --}}
                    <div class="exp-section-title">
                        <span class="exp-step">1</span> Chọn hoá chất cần dùng
                    </div>

                    <div class="exp-pick-grid">
                        {{-- Cách 1: quét mã vạch trên nhãn lô (máy đọc mã rời, camera, hoặc gõ tay mã) --}}
                        <div class="exp-scan scan-box">
                            <label><i class="fas fa-barcode mr-1"></i> Cách 1 · Quét mã xuất nhập</label>
                            <div class="exp-scan-row">
                                <input type="text" class="form-control exp-scan-input" autocomplete="off"
                                    data-url="{{ route($expRoute . 'lookup') }}"
                                    placeholder="Đưa máy quét vào mã vạch trên nhãn, hoặc gõ mã rồi nhấn Enter">
                                <button type="button" class="btn btn-outline-primary btn-camera-scan" title="Quét bằng camera">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <button type="button" class="btn btn-primary btn-exp-scan">
                                    <i class="fas fa-search mr-1"></i> Tra mã
                                </button>
                            </div>
                            <div class="exp-scan-result"></div>
                        </div>

                        {{-- Cách 2: chọn nhiều lô từ danh mục tồn kho của phòng --}}
                        <div class="exp-method">
                            <label><i class="fas fa-boxes-stacked mr-1"></i> Cách 2 · Tồn kho của phòng</label>
                            <button type="button" class="btn btn-primary btn-open-exp-picker"
                                title="Chọn 1 hoặc nhiều lô còn tồn, còn hạn dùng của phòng">
                                <i class="fas fa-list-check"></i> Chọn Từ Tồn Kho
                                <span class="exp-method-badge">{{ number_format($expSelectableCount) }} lô</span>
                            </button>
                        </div>
                    </div>

                    {{-- ===== KHỐI 2: HOÁ CHẤT ĐÃ CHỌN ===== --}}
                    <div class="exp-section-title">
                        <span class="exp-step">2</span> Hoá chất đã chọn
                        <span class="exp-count-pill" id="expRowsCount">0 dòng</span>
                    </div>

                    <div class="table-responsive" id="expRowsWrap" style="display: none">
                        {{-- data-no-datatable: dòng ở đây do JS thêm/bớt, để DataTables ôm vào thì
                             phần đếm dòng / phân trang sai và sắp xếp lại làm mất dòng đang nhập --}}
                        <table class="table table-bordered table-sm md-table mb-0" id="expRowsTable" data-no-datatable>
                            <thead>
                                <tr>
                                    <th style="width: 40px" class="text-center">#</th>
                                    <th style="min-width: 210px">Tên Hoá Chất</th>
                                    <th style="width: 90px">Mã DM</th>
                                    <th style="width: 150px">Mã Xuất Nhập</th>
                                    <th style="width: 110px">Số Lô</th>
                                    <th style="width: 120px" class="text-right">Tồn Còn</th>
                                    <th style="width: 100px" class="text-center">Hạn Dùng</th>
                                    <th style="width: 230px; max-width: 260px">Số Lượng Dùng <span class="text-danger">*</span></th>
                                    {{-- Cột chỉ hiện khi trong danh sách có hoá chất thuộc Nhóm HC Cấm --}}
                                    <th class="exp-col-checker" style="width: 210px; display: none"
                                        title="Hoá chất cấm bắt buộc có Quản Lý Kho Hoá Chất của phòng kiểm tra">
                                        Người Kiểm Tra <span class="text-danger">*</span>
                                    </th>
                                    <th style="min-width: 240px" title="Chọn tên sản phẩm từ Dữ Liệu Gốc hoặc gõ mục đích khác">
                                        Mục Đích / Lý Do
                                    </th>
                                    <th style="width: 44px"></th>
                                </tr>
                            </thead>
                            <tbody id="expRowsBody"></tbody>
                        </table>
                    </div>
                    <div class="exp-empty" id="expRowsEmpty">
                        <i class="fas fa-flask"></i>
                        Chưa chọn hoá chất nào. Quét mã hoặc bấm <b>Chọn Từ Tồn Kho</b> ở trên để thêm.
                    </div>

                    <div class="md-hint mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ngày sử dụng là ngày bấm Lưu, người sử dụng là người đang đăng nhập
                        (<b>{{ session('user')['fullName'] }}</b>). Được dùng vượt tồn còn lại tối đa
                        <b>{{ $overIssuePercent }}%</b> để bù sai số cân đong (lô nhận lẻ thì không); phần vượt làm
                        tồn bị âm, xử lý bằng nút <b>Cân Đối</b> ở màn hình Tồn Kho Hoá Chất.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="button" class="btn btn-outline-primary exp-submit" data-mode="save" id="expBtnSaveDraft">
                        <i class="fas fa-clock mr-1"></i> Lưu Tạm
                    </button>
                    <button type="button" class="btn btn-primary exp-submit" data-mode="use">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal quét camera dùng chung - đặt ngoài #createModal để không lồng modal trong modal --}}
@include('pages.shared.cameraScan')

@if ($bag->any())
    <script>
        // Lưu lỗi thì dựng lại các dòng đã nhập (số lượng / người kiểm tra / mục đích) thay vì bắt chọn lại từ đầu
        window.expOldItems = {!! json_encode((object) old('items', [])) !!};

        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $modal = $('#createModal');
        var checkUrl = @json(route($expRoute . 'checkAmount'));
        var productUrl = @json(route('pages.category.lookup.productNames'));
        var overPercent = {{ (int) $overIssuePercent }};

        function esc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function trimNum(value) {
            return String(Number(value || 0).toFixed(4)).replace(/\.?0+$/, '');
        }

        // 160000 -> "160,000"; 12.5 -> "12.5" (thập phân luôn là dấu ".")
        function fmt(value) {
            var parts = trimNum(value).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        // "21/10/2028" -> số ngày còn lại tới HSD (null nếu không đọc được)
        function daysLeft(dmy) {
            var m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(dmy || '');
            if (!m) return null;

            var today = new Date();
            today.setHours(0, 0, 0, 0);

            return Math.round((new Date(+m[3], +m[2] - 1, +m[1]) - today) / 86400000);
        }

        // Ô Mục Đích (textarea) cao dần theo số dòng chữ, ô input một dòng thì bỏ qua
        function autoGrow($el) {
            $el.filter('textarea.exp-purpose').each(function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 2 + 'px';
            });
        }

        function checkerOptionsHtml() {
            var html = '<option value="">-- Chọn Quản Lý Kho HC --</option>';

            (window.expCheckerOptions || []).forEach(function(checker) {
                html += '<option value="' + esc(checker.value) + '">' + esc(checker.label) + '</option>';
            });

            return html;
        }

        function syncRows() {
            var $rows = $('#expRowsBody tr');

            $('#expRowsWrap').toggle($rows.length > 0);
            $('#expRowsEmpty').toggle($rows.length === 0);
            $('#expRowsCount').text($rows.length + ' dòng');
            $rows.each(function(i) {
                $(this).find('.exp-row-no').text(i + 1);
            });

            // Cột Người Kiểm Tra chỉ có nghĩa với hoá chất thuộc Nhóm HC Cấm (Luật Đầu tư
            // 2025, số 143/2025/QH15) - danh sách không có dòng nào như vậy thì giấu cả cột.
            $('#expRowsTable .exp-col-checker').toggle($rows.filter('.is-banned').length > 0);
        }

        /* ---------- Ô Mục Đích: gợi ý TÊN SẢN PHẨM (Dữ Liệu Gốc), vẫn là ô chữ tự do ----------
         | Gõ tới đâu gợi ý tới đó (30 tên/lần, cuộn tải thêm). Chọn một tên thì tên được điền vào
         | ô và con trỏ đặt ở cuối - người dùng sửa / gõ thêm tiếp (vd "... - lô 123") như ô chữ
         | thường. Dùng cho mọi ô có class js-product-ac trên trang (modal Sử dụng, Sửa, Phiếu Tạm). */
        var ac = {
            $input: null,
            items: [],
            term: '',
            page: 1,
            more: false,
            active: -1,
            timer: null,
            xhr: null
        };
        var $acMenu = $('<div class="exp-ac-menu" role="listbox"></div>').hide().appendTo(document.body);

        function acPlace() {
            if (!ac.$input || !$acMenu.is(':visible')) return;

            var rect = ac.$input[0].getBoundingClientRect();
            var below = window.innerHeight - rect.bottom;
            var up = below < 220 && rect.top > below;

            $acMenu.css({
                left: rect.left,
                width: Math.max(rect.width, 280),
                top: up ? 'auto' : rect.bottom + 2,
                bottom: up ? window.innerHeight - rect.top + 2 : 'auto'
            });
        }

        function acClose() {
            clearTimeout(ac.timer);
            if (ac.xhr) ac.xhr.abort();
            $acMenu.hide().empty();
            ac.items = [];
            ac.active = -1;
        }

        function acRender() {
            $acMenu.empty();

            if (!ac.items.length) {
                $acMenu.hide();
                return;
            }

            ac.items.forEach(function(item, i) {
                $('<div class="exp-ac-item" role="option">')
                    .toggleClass('is-active', i === ac.active)
                    .attr('data-index', i)
                    .append($('<i class="fas fa-box mr-2">'))
                    .append(document.createTextNode(item.text))
                    .appendTo($acMenu);
            });

            if (ac.more) {
                $('<div class="exp-ac-more">Cuộn xuống để xem thêm...</div>').appendTo($acMenu);
            }

            $acMenu.show();
            acPlace();
        }

        function acFetch(append) {
            if (ac.xhr) ac.xhr.abort();

            var $input = ac.$input;

            ac.xhr = $.getJSON(productUrl, {
                q: ac.term,
                page: ac.page
            }).done(function(res) {
                if (ac.$input !== $input || !$input.is(':focus')) return;

                var items = res.results || [];

                ac.items = append ? ac.items.concat(items) : items;
                ac.more = !!(res.pagination && res.pagination.more);

                // Ô đã đúng y một tên sản phẩm (vừa chọn xong) thì không bật lại danh sách
                if (!append && ac.items.length === 1 && ac.items[0].text === $.trim($input.val())) {
                    ac.items = [];
                }

                acRender();
            });
        }

        function acSearch($input) {
            ac.$input = $input;
            ac.term = $.trim($input.val());
            ac.page = 1;
            ac.active = -1;
            acFetch(false);
        }

        function acPick(index) {
            var item = ac.items[index];
            var $input = ac.$input;

            if (!item || !$input) return;

            $input.val(item.text);
            acClose();
            autoGrow($input);
            ac.justPicked = true;
            $input.trigger('focus');
            ac.justPicked = false;

            var el = $input[0];
            el.setSelectionRange(el.value.length, el.value.length);
        }

        function acMove(step) {
            if (!ac.items.length) return;

            ac.active = (ac.active + step + ac.items.length) % ac.items.length;
            $acMenu.children('.exp-ac-item').removeClass('is-active')
                .eq(ac.active).addClass('is-active')[0].scrollIntoView({
                    block: 'nearest'
                });
        }

        $(document).on('focus', '.js-product-ac', function() {
            // Vừa chọn xong một tên thì không bật lại danh sách
            if (ac.justPicked) {
                ac.justPicked = false;
                return;
            }

            acSearch($(this));
        });

        $(document).on('input', '.js-product-ac', function() {
            var $input = $(this);

            autoGrow($input);
            clearTimeout(ac.timer);
            ac.timer = setTimeout(function() {
                acSearch($input);
            }, 250);
        });

        $(document).on('keydown', '.js-product-ac', function(e) {
            if (!$acMenu.is(':visible')) return;

            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                acMove(e.key === 'ArrowDown' ? 1 : -1);
            } else if (e.key === 'Enter' && ac.active >= 0) {
                e.preventDefault();
                acPick(ac.active);
            } else if (e.key === 'Tab') {
                acClose();
            }
        });

        // Esc chỉ đóng danh sách gợi ý, không đóng luôn modal - bắt ở pha capture vì Bootstrap
        // nghe Esc ngay trên .modal, handler uỷ quyền ở document chạy sau thì đã muộn
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && $acMenu.is(':visible') && $(e.target).is('.js-product-ac')) {
                e.preventDefault();
                e.stopPropagation();
                acClose();
            }
        }, true);

        $(document).on('blur', '.js-product-ac', function() {
            setTimeout(function() {
                if (!$(document.activeElement).is('.js-product-ac')) acClose();
            }, 150);
        });

        // mousedown giữ focus ở ô đang gõ, click mới chọn
        $acMenu.on('mousedown', function(e) {
            e.preventDefault();
        });

        $acMenu.on('click', '.exp-ac-item', function() {
            acPick(+$(this).data('index'));
        });

        $acMenu.on('scroll', function() {
            if (ac.more && !(ac.xhr && ac.xhr.readyState !== 4) &&
                this.scrollTop + this.clientHeight >= this.scrollHeight - 30) {
                ac.page++;
                acFetch(true);
            }
        });

        // Modal / bảng cuộn thì danh sách đi theo ô đang gõ
        document.addEventListener('scroll', function(e) {
            if (e.target !== $acMenu[0]) acPlace();
        }, true);
        $(window).on('resize', acPlace);
        $(document).on('hide.bs.modal', '.modal', acClose);

        /* ---------- Số Lượng: kiểm tra ngay khi gõ ----------
         | Bước 1 (tức thì): so với tồn / hạn mức có sẵn trong trang.
         | Bước 2 (chạy ngầm, sau 350ms ngừng gõ): hỏi server tồn THẬT lúc này + lượng đang giữ ở
         | Phiếu Tạm, cùng quy tắc với lúc Lưu - trang mở lâu, người khác vừa dùng bớt vẫn bắt được.
         | Vượt hạn mức thì setCustomValidity để nút Lưu bị chặn ngay trên form. */
        function paintAmount($row, info) {
            var data = (window.expImportData || {})[$row.data('import-id')] || {};
            var $input = $row.find('.exp-amount-input');
            var $status = $row.find('.exp-amt-status');
            var unit = esc(data.unit || '');
            var raw = $.trim($input.val());
            var amount = raw === '' ? null : Number(raw);
            var remaining = info.remaining;
            var limit = info.limit;
            var cls = '';
            var html;

            $row.find('.exp-remaining-cell').html(fmt(remaining) + ' <span class="exp-unit">' + unit + '</span>');

            if (amount === null || isNaN(amount)) {
                html = 'Tối đa <b>' + fmt(limit) + '</b> ' + unit;
            } else if (amount <= 0 || amount > limit + 0.00005) {
                // Lượng dùng + phần đang giữ ở Phiếu Tạm vượt tồn + 5% -> chặn cả Lưu lẫn Lưu Tạm.
                // Câu ngắn, người đang giữ đã có ở dòng "Đang giữ ở Phiếu Tạm" bên dưới - không lặp lại.
                var capNote = data.partial ? 'lô nhận lẻ không được vượt tồn' : 'tồn + ' + overPercent + '%';

                cls = 'is-bad';
                html = '<i class="fas fa-times-circle mr-1"></i>' + (amount <= 0 ? 'Số lượng phải lớn hơn 0.' :
                    info.reserved > 0.00005 ?
                    'Vượt hạn mức: dùng + giữ chỗ tối đa <b>' + fmt(limit + info.reserved) + '</b> ' + unit +
                    ' (' + capNote + '), chỉ dùng thêm được <b>' + fmt(limit) + '</b> ' + unit + '.' :
                    'Vượt hạn mức: dùng tối đa <b>' + fmt(limit) + '</b> ' + unit + ' (' + capNote + ').');
            } else if (info.message) {
                // Lỗi khác server báo (phiếu nhập bị khoá...)
                cls = 'is-bad';
                html = '<i class="fas fa-times-circle mr-1"></i>' + esc(info.message);
            } else if (amount + info.reserved > remaining + 0.00005) {
                cls = 'is-warn';
                html = '<i class="fas fa-exclamation-triangle mr-1"></i>Vượt tồn ' +
                    fmt(amount + info.reserved - remaining) + ' ' + unit + ' (trong ' + overPercent +
                    '%) - tồn sẽ âm, cần Cân Đối';
            } else {
                cls = 'is-ok';
                html = '<i class="fas fa-check-circle mr-1"></i>Còn lại ' +
                    fmt(remaining - info.reserved - amount) + ' ' + unit;
            }

            // Ai đang giữ bao nhiêu ở Phiếu Tạm trên lô này
            if (info.reserved > 0.00005) {
                var who = (info.holders || []).map(function(holder) {
                    return '<b>' + esc(holder.name) + '</b> ' + fmt(holder.amount) + ' ' + unit;
                }).join(', ');

                html += '<span class="exp-amt-reserved"><i class="fas fa-clock mr-1"></i>Đang giữ ở Phiếu Tạm ' +
                    fmt(info.reserved) + ' ' + unit + (who ? ': ' + who : '') + '</span>';
            }

            $status.removeClass('is-ok is-warn is-bad').addClass(cls).html(html);
            $input.toggleClass('is-invalid', cls === 'is-bad').toggleClass('is-warn', cls === 'is-warn');
            $input[0].setCustomValidity(cls === 'is-bad' ? $status.text() : '');
        }

        function localInfo($row) {
            var data = (window.expImportData || {})[$row.data('import-id')] || {};
            var server = $row.data('server') || {};

            return {
                remaining: server.remaining != null ? server.remaining : Number(data.remaining || 0),
                limit: server.limit != null ? server.limit : Number(data.max_issue || 0),
                reserved: server.reserved != null ? server.reserved : Number(data.reserved || 0),
                holders: server.holders || data.holders || [],
                message: null
            };
        }

        function checkAmount($row) {
            var seq = ($row.data('seq') || 0) + 1;
            var amount = $.trim($row.find('.exp-amount-input').val());

            $row.data('seq', seq);
            paintAmount($row, localInfo($row));

            clearTimeout($row.data('timer'));
            $row.data('timer', setTimeout(function() {
                $.getJSON(checkUrl, {
                    import_id: $row.data('import-id'),
                    amount: amount
                }).done(function(res) {
                    // Gõ tiếp trong lúc chờ thì bỏ kết quả cũ
                    if ($row.data('seq') !== seq || !$row.closest('body').length) return;

                    if (res.remaining != null) {
                        $row.data('server', {
                            remaining: res.remaining,
                            limit: res.limit,
                            reserved: res.reserved,
                            holders: res.holders || []
                        });
                    }

                    paintAmount($row, $.extend(localInfo($row), {
                        message: res.ok ? null : (res.message || 'Không kiểm tra được số lượng.')
                    }));
                });
            }, 350));
        }

        $(document).on('input', '#expRowsBody .exp-amount-input', function() {
            checkAmount($(this).closest('tr'));
        });

        // Thêm 1 dòng vào bảng - gọi từ picker.blade.php khi bấm "Thêm Vào Danh Sách"
        // hoặc khi quét/tra mã thành công. Đã có sẵn thì bỏ qua, không thêm trùng.
        window.expAddRow = function(importId, values) {
            importId = String(importId);

            var $exists = $modal.find('#expRowsBody tr[data-import-id="' + importId + '"]');

            if ($exists.length) {
                $exists.find('.exp-amount-input').trigger('focus');
                return;
            }

            var data = (window.expImportData || {})[importId];

            if (!data) {
                return;
            }

            values = values || {};

            // Hoá chất cấm (Luật Đầu tư 2025, số 143/2025/QH15) bắt buộc có Người Kiểm Tra là
            // Quản Lý Kho Hoá Chất của phòng; hoá chất thường không có ô này.
            var banned = !!data.banned;
            var hasCheckers = (window.expCheckerOptions || []).length > 0;
            var days = daysLeft(data.expired_date);
            var name = 'items[' + importId + ']';

            var checkerCell = '<span class="md-empty">—</span>';
            if (banned) {
                checkerCell = hasCheckers ?
                    '<select name="' + name + '[checked_by]" class="form-control form-control-sm" required>' +
                    checkerOptionsHtml() + '</select>' :
                    '<select class="form-control form-control-sm" required disabled><option value="">Không có người kiểm tra</option></select>' +
                    '<div class="exp-required-note">Phòng chưa có user Quản Lý Kho Hoá Chất - chưa dùng được hoá chất cấm.</div>';
            }

            var $row = $(
                '<tr data-import-id="' + importId + '"' + (banned ? ' class="is-banned"' : '') + '>' +
                '<td class="exp-row-no exp-cell-mid"></td>' +
                '<td>' +
                '<div class="exp-chem-name">' + esc(data.chem_name || '—') + '</div>' +
                (banned ? '<span class="badge badge-warning text-dark mt-1">' + esc(data.banned_label || 'Nhóm HC Cấm') + '</span>' : '') +
                (data.location ? '<div class="md-sub mt-1"><i class="fas fa-map-marker-alt mr-1"></i>' + esc(data.location) + '</div>' : '') +
                '</td>' +
                '<td class="exp-cell-mid"><span class="md-tag">' + esc(data.category_code || '—') + '</span></td>' +
                '<td class="exp-cell-mid"><span class="exp-code">' + esc(data.code) + '</span></td>' +
                '<td class="exp-cell-mid">' + esc(data.batch_no || '—') + '</td>' +
                '<td class="exp-cell-mid exp-num exp-remaining-cell"></td>' +
                '<td class="exp-cell-mid text-center">' + esc(data.expired_date || '—') +
                (days !== null && days <= 30 ? '<div class="exp-date-soon">còn ' + days + ' ngày</div>' : '') + '</td>' +
                '<td class="exp-amount">' +
                '<div class="input-group input-group-sm">' +
                '<input type="text" inputmode="decimal" name="' + name + '[amount]" ' +
                'class="form-control js-decimal exp-amount-input" placeholder="0" autocomplete="off" required>' +
                '<div class="input-group-append"><span class="input-group-text">' + esc(data.unit || '—') + '</span></div>' +
                '</div>' +
                '<div class="exp-amt-status"></div>' +
                '</td>' +
                '<td class="exp-col-checker" style="display: none">' + checkerCell + '</td>' +
                '<td><textarea name="' + name + '[purpose]" rows="1" maxlength="500" autocomplete="off" ' +
                'class="form-control form-control-sm exp-purpose js-product-ac" ' +
                'placeholder="Tìm tên sản phẩm hoặc gõ mục đích"></textarea></td>' +
                '<td class="text-center exp-cell-mid"><button type="button" class="btn btn-sm btn-outline-danger exp-row-remove" title="Bỏ dòng này"><i class="fas fa-times"></i></button></td>' +
                '</tr>'
            );

            $('#expRowsBody').append($row);
            syncRows();

            if (values.amount) $row.find('.exp-amount-input').val(values.amount);
            if (values.checked_by) $row.find('select[name$="[checked_by]"]').val(values.checked_by);
            if (values.purpose) autoGrow($row.find('.exp-purpose').val(values.purpose));

            if (values.amount) {
                checkAmount($row);
            } else {
                paintAmount($row, localInfo($row));
            }

            if (!values.amount) $row.find('.exp-amount-input').trigger('focus');
        };

        $(document).on('click', '#expRowsBody .exp-row-remove', function() {
            var $row = $(this).closest('tr');

            clearTimeout($row.data('timer'));
            $row.remove();
            syncRows();
        });

        function clearRows() {
            $('#expRowsBody tr').each(function() {
                clearTimeout($(this).data('timer'));
            });
            $('#expRowsBody').empty();
            syncRows();
        }

        // Mở modal Thêm mới thì dọn sạch bảng dòng + trả nút Lưu về mặc định "Sử dụng ngay"
        $(document).on('click', '.btn-md-create', function() {
            clearRows();
            $modal.find('.exp-mode-input').val('use');
        });

        syncRows();

        // Lưu bị lỗi: dựng lại đúng các dòng người dùng đã nhập
        $.each(window.expOldItems || {}, function(importId, values) {
            window.expAddRow(importId, values || {});
        });

        $modal.find('.exp-submit').on('click', function() {
            if (!$('#expRowsBody tr').length) {
                alert('Vui lòng chọn ít nhất một hoá chất cần dùng.');
                return;
            }

            $modal.find('.exp-mode-input').val($(this).data('mode'));

            var form = document.getElementById('expCreateForm');

            if (form.reportValidity()) {
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    });
</script>
