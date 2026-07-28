<style>
    .reissue-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .reissue-stat {
        background: #fff;
        border-radius: 10px;
        padding: 15px 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 15px;
        border-left: 4px solid #94a3b8;
    }

    .reissue-stat.pending-pm    { border-left-color: #f59e0b; }
    .reissue-stat.pending-qa    { border-left-color: #8b5cf6; }
    .reissue-stat.pending-issue { border-left-color: #3b82f6; }
    .reissue-stat.completed     { border-left-color: #10b981; }

    .reissue-stat i {
        font-size: 1.8rem;
        opacity: 0.8;
    }

    .reissue-stat.pending-pm i    { color: #f59e0b; }
    .reissue-stat.pending-qa i    { color: #8b5cf6; }
    .reissue-stat.pending-issue i { color: #3b82f6; }
    .reissue-stat.completed i     { color: #10b981; }

    .reissue-stat .value {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1;
        color: #1e293b;
    }

    .reissue-stat .label {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Sổ xin cấp lại: bảng nhiều cột, cho phép cuộn ngang */
    .reissue-book {
        overflow-x: auto;
    }

    #data_table_reissue {
        min-width: 1500px;
    }

    #data_table_reissue thead th {
        background: #f8fafc;
        color: #1e293b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        vertical-align: middle;
        text-align: center;
    }

    #data_table_reissue td {
        vertical-align: middle;
        font-size: 0.85rem;
    }

    /* Ô chữ ký: ngày ở trên, người ký ở dưới - giống sổ giấy */
    .sign-cell {
        text-align: center;
        line-height: 1.35;
    }

    .sign-cell .date {
        font-size: 0.78rem;
        color: #64748b;
    }

    .sign-cell .who {
        font-weight: 600;
        color: #1e293b;
    }

    .sign-cell .empty {
        color: #cbd5e1;
        font-style: italic;
        font-size: 0.8rem;
    }

    .opinion-agree {
        color: #047857;
        font-weight: 700;
    }

    .opinion-disagree {
        color: #b91c1c;
        font-weight: 700;
    }

    .reason-cell {
        max-width: 220px;
        white-space: normal;
    }

    .row-rejected  { background: #fef2f2 !important; }
    .row-cancelled { opacity: 0.6; }
</style>

@php
    // Phân quyền nút thao tác theo role của user hiện tại (Admin luôn được phép, xem user_has_any_role()).
    $reissueUserId    = session('user.userId');
    $canRequestReissue = user_has_any_role($reissueUserId, ['Production_Manager', 'Người đề nghị']);
    $canPmSignReissue  = user_has_any_role($reissueUserId, ['Production_Manager']);
    $canQaReviewReissue = user_has_any_role($reissueUserId, ['QA_Manager']);
    $canIssueReissue   = user_has_any_role($reissueUserId, ['QA_Manager', 'Người cho lại hồ sơ']);
@endphp

<div class="content-wrapper">
    <div class="card mt-5">
        <div class="card-body">

            <div class="reissue-stats">
                <div class="reissue-stat pending-pm">
                    <i class="fas fa-user-clock"></i>
                    <div>
                        <div class="value">{{ $pendingPmCount }}</div>
                        <div class="label">Chờ QĐ/P.QĐ PXSX ký</div>
                    </div>
                </div>
                <div class="reissue-stat pending-qa">
                    <i class="fas fa-user-check"></i>
                    <div>
                        <div class="value">{{ $pendingQaCount }}</div>
                        <div class="label">Chờ TP/PP. ĐBCL duyệt</div>
                    </div>
                </div>
                <div class="reissue-stat pending-issue">
                    <i class="fas fa-print"></i>
                    <div>
                        <div class="value">{{ $pendingIssueCount }}</div>
                        <div class="label">Chờ cấp lại hồ sơ</div>
                    </div>
                </div>
                <div class="reissue-stat completed">
                    <i class="fas fa-check-double"></i>
                    <div>
                        <div class="value">{{ $completedCount }}</div>
                        <div class="label">Đã cấp lại</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                @if ($canRequestReissue)
                    <button class="btn btn-success mb-2" data-toggle="modal" data-target="#createReissueModal">
                        <i class="fas fa-plus"></i> Ghi sổ xin cấp lại hồ sơ
                    </button>
                @else
                    <span></span>
                @endif

                <div class="btn-group btn-group-sm mb-2" role="group" id="reissue-filter">
                    <button type="button" class="btn btn-outline-secondary active" data-filter="all">Tất cả</button>
                    <button type="button" class="btn btn-outline-warning" data-filter="pending_pm">Chờ QĐ PXSX</button>
                    <button type="button" class="btn btn-outline-info" data-filter="pending_qa">Chờ ĐBCL</button>
                    <button type="button" class="btn btn-outline-primary" data-filter="pending_issue">Chờ cấp lại</button>
                    <button type="button" class="btn btn-outline-success" data-filter="completed">Đã cấp lại</button>
                    <button type="button" class="btn btn-outline-danger" data-filter="cancelled">Đơn Huỷ</button>
                </div>
            </div>

            <div class="reissue-book">
                <table id="data_table_reissue" class="table table-bordered table-striped w-100">
                    <thead>
                        <tr>
                            <th style="width: 90px">Ngày</th>
                            <th style="width: 200px">Tên sản phẩm<br>BMR/ BPR</th>
                            <th style="width: 100px">Số quy trình</th>
                            <th style="width: 70px">Ấn bản</th>
                            <th style="width: 110px">Số trang cần<br>xin lại</th>
                            <th style="width: 200px">Lý do xin lại</th>
                            <th style="width: 160px">CAPA</th>
                            <th style="width: 130px">Ngày/<br>Người đề nghị</th>
                            <th style="width: 140px">Ngày/<br>QĐ - P.QĐPX<br>Ký duyệt</th>
                            <th style="width: 190px">Ý kiến TP/PP. ĐBCL</th>
                            <th style="width: 150px">Ngày/<br>Người cấp hồ sơ ký tên</th>
                            <th style="width: 120px">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($datas as $data)
                            <tr data-status="{{ $data->status }}"
                                class="{{ $data->status === 'rejected' ? 'row-rejected' : '' }} {{ $data->status === 'cancelled' ? 'row-cancelled' : '' }}">
                                <td class="text-center">
                                    {{ \Carbon\Carbon::parse($data->request_date)->format('d/m/Y') }}
                                    <div><small class="text-muted">{{ $data->code }}</small></div>
                                </td>
                                <td>
                                    {{ $data->product_name }}
                                    {{-- ĐBCL / bộ phận ban hành xem đơn của mọi bộ phận nên phải rõ đơn từ đâu tới --}}
                                    @if ($data->department_short_name)
                                        <div>
                                            <span class="badge badge-light border text-muted">
                                                <i class="fas fa-building"></i> {{ $data->department_short_name }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">{{ $data->process_no ?: '-' }}</td>
                                <td class="text-center">{{ $data->edition ?: '-' }}</td>
                                <td class="text-center font-weight-bold">{{ $data->pages }}</td>
                                <td class="reason-cell">{{ $data->reason }}</td>
                                <td class="reason-cell">{{ $data->capa ?: '-' }}</td>

                                {{-- Ngày / Người đề nghị --}}
                                <td class="sign-cell">
                                    <div class="date">
                                        {{ $data->requested_date ? \Carbon\Carbon::parse($data->requested_date)->format('d/m/Y') : '' }}
                                    </div>
                                    <div class="who">{{ $data->requester_name }}</div>
                                </td>

                                {{-- Ngày / QĐ - P.QĐ PXSX ký duyệt --}}
                                <td class="sign-cell">
                                    @if ($data->pm_signed_date)
                                        <div class="date">
                                            {{ \Carbon\Carbon::parse($data->pm_signed_date)->format('d/m/Y') }}
                                        </div>
                                        <div class="who"><i class="fas fa-signature text-success"></i> {{ $data->pm_name }}</div>
                                        @if ($data->pm_note)
                                            <div class="date font-italic">{{ $data->pm_note }}</div>
                                        @endif
                                    @else
                                        <span class="empty">chưa ký</span>
                                    @endif
                                </td>

                                {{-- Ý kiến TP/PP. ĐBCL --}}
                                <td class="sign-cell">
                                    @if ($data->qa_decision)
                                        <div class="{{ $data->qa_decision === 'agree' ? 'opinion-agree' : 'opinion-disagree' }}">
                                            {{ $data->qa_decision === 'agree' ? 'ĐỒNG Ý' : 'KHÔNG ĐỒNG Ý' }}
                                        </div>
                                        @if ($data->qa_opinion)
                                            <div class="date font-italic">{{ $data->qa_opinion }}</div>
                                        @endif
                                        <div class="date">
                                            {{ $data->qa_signed_date ? \Carbon\Carbon::parse($data->qa_signed_date)->format('d/m/Y') : '' }}
                                        </div>
                                        <div class="who"><i class="fas fa-signature text-success"></i> {{ $data->qa_name }}</div>
                                    @else
                                        <span class="empty">chưa có ý kiến</span>
                                    @endif
                                </td>

                                {{-- Ngày / Người cấp hồ sơ ký tên --}}
                                <td class="sign-cell">
                                    @if ($data->issued_date)
                                        <div class="date">
                                            {{ \Carbon\Carbon::parse($data->issued_date)->format('d/m/Y') }}
                                        </div>
                                        <div class="who"><i class="fas fa-signature text-success"></i> {{ $data->issuer_name }}</div>
                                        <div class="date">Đã cấp trang: <b>{{ $data->issued_pages }}</b></div>
                                        @if ($data->wrong_pages_voided)
                                            <div class="date"><i class="fas fa-check text-success"></i> đã gạch bỏ trang sai</div>
                                        @endif
                                    @else
                                        <span class="empty">chưa cấp lại</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if ($data->status === 'pending_pm')
                                        @if ($canRequestReissue)
                                            <button type="button" class="btn btn-sm btn-secondary btn-edit-reissue mb-1"
                                                data-json="{{ json_encode($data) }}"
                                                data-toggle="modal" data-target="#updateReissueModal"
                                                title="Sửa nội dung đề nghị">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($canPmSignReissue)
                                            <button type="button" class="btn btn-sm btn-warning btn-pm-sign mb-1"
                                                data-id="{{ $data->id }}" data-label="{{ $data->code }} - {{ $data->product_name }}"
                                                data-toggle="modal" data-target="#pmSignReissueModal"
                                                title="QĐ/P.QĐ PXSX ký duyệt">
                                                <i class="fas fa-signature"></i>
                                            </button>
                                        @endif
                                    @elseif ($data->status === 'pending_qa')
                                        @if ($canQaReviewReissue)
                                            <button type="button" class="btn btn-sm btn-info btn-qa-review mb-1"
                                                data-id="{{ $data->id }}" data-label="{{ $data->code }} - {{ $data->product_name }}"
                                                data-pages="{{ $data->pages }}"
                                                data-toggle="modal" data-target="#qaReviewReissueModal"
                                                title="TP/PP. ĐBCL cho ý kiến">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        @endif
                                    @elseif ($data->status === 'pending_issue')
                                        @if ($canIssueReissue)
                                            <button type="button" class="btn btn-sm btn-primary btn-issue mb-1"
                                                data-id="{{ $data->id }}" data-label="{{ $data->code }} - {{ $data->product_name }}"
                                                data-pages="{{ $data->pages }}"
                                                data-toggle="modal" data-target="#issueReissueModal"
                                                title="Cấp lại hồ sơ & ký tên">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        @endif
                                    @elseif ($data->status === 'completed')
                                        <span class="badge badge-success">Hoàn tất</span>
                                    @elseif ($data->status === 'rejected')
                                        <span class="badge badge-danger">Không đồng ý</span>
                                    @else
                                        <span class="badge badge-secondary">Đã huỷ</span>
                                    @endif

                                    @if (!in_array($data->status, ['completed', 'cancelled']) && $canRequestReissue)
                                        <form class="d-inline form-cancel-reissue"
                                            action="{{ route('pages.documentStorage.reissue.cancel') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $data->id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger mb-1" title="Huỷ đề nghị">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td colspan="12" class="text-center text-muted py-4">
                                    Chưa có đề nghị xin cấp lại hồ sơ nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{--
    jQuery / Popper / Bootstrap đã được nạp ở layout.js (cuối trang) nên KHÔNG nạp lại ở đây:
    nạp jQuery lần hai sẽ tạo instance mới và xoá mọi plugin đã gắn vào instance đầu.
    SweetAlert2 layout chưa có nên vẫn phải nạp riêng.
--}}
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 2500,
            showConfirmButton: false
        });
    </script>
@endif

@if (session('error'))
    <script>
        Swal.fire({
            title: 'Không thực hiện được',
            text: '{{ session('error') }}',
            icon: 'error'
        });
    </script>
@endif

<script>
    // Khối này render TRƯỚC layout.js nên $ chưa tồn tại lúc parse; đợi DOMContentLoaded
    // để chắc chắn jQuery của layout đã nạp xong.
    document.addEventListener('DOMContentLoaded', function() {
        // Tên sản phẩm dùng <datalist> thay vì select2: vừa gợi ý từ danh mục sản phẩm,
        // vừa cho phép gõ tay tên BMR/BPR chưa có trong danh mục.

        // Chống double-submit: khoá nút ngay khi bấm để người dùng không bấm lại
        // trong lúc trang đang xử lý. Server vẫn chặn bằng submit_token nếu lọt qua.
        $(document).on('submit', '.form-single-submit', function() {
            const $btn = $(this).find('button[type="submit"]');

            if ($btn.data('locked')) {
                return false;
            }

            $btn.data('locked', true)
                .prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        });

        // Ô "Ngày" mặc định là hôm nay. Giá trị render sẵn sẽ cũ dần nếu tab được mở
        // từ hôm trước (máy dùng chung giữa các ca), nên đặt lại mỗi lần mở modal.
        // Các ô ngày ký (bước 2/3/4) chỉ để xem: server mới là nơi quyết định ngày ghi vào sổ.
        function today(input) {
            const d = new Date();
            const pad = (n) => String(n).padStart(2, '0');

            return input.type === 'date'
                ? d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
                : pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
        }

        // Người dùng đã tự chọn ngày thì tôn trọng lựa chọn đó, không ghi đè.
        $(document).on('change', '[data-default-now]', function() {
            this.dataset.touched = '1';
        });

        $('.modal').on('show.bs.modal', function() {
            $(this).find('[data-default-now]').each(function() {
                if (this.dataset.touched !== '1') {
                    this.value = today(this);
                }
            });
        });

        // Lọc theo trạng thái
        $('#reissue-filter button').on('click', function() {
            const filter = $(this).data('filter');
            $('#reissue-filter button').removeClass('active');
            $(this).addClass('active');

            $('#data_table_reissue tbody tr').not('.no-data-row').each(function() {
                const $tr = $(this);
                const match = (filter === 'all' || $tr.data('status') === filter);
                $tr.toggleClass('d-none', !match);
            });
        });

        // Bước 2 - QĐ/P.QĐ PXSX ký duyệt
        $(document).on('click', '.btn-pm-sign', function() {
            const b = $(this);
            const modal = $('#pmSignReissueModal');
            modal.find('#pm_id').val(b.data('id'));
            modal.find('#pm_doc_label').text(b.data('label'));
        });

        // Bước 3 - TP/PP. ĐBCL cho ý kiến
        $(document).on('click', '.btn-qa-review', function() {
            const b = $(this);
            const modal = $('#qaReviewReissueModal');
            modal.find('#qa_id').val(b.data('id'));
            modal.find('#qa_doc_label').text(b.data('label'));
            modal.find('#qa_pages_label').text(b.data('pages'));
        });

        // Bước 4 - Cấp lại hồ sơ
        $(document).on('click', '.btn-issue', function() {
            const b = $(this);
            const modal = $('#issueReissueModal');
            modal.find('#issue_id').val(b.data('id'));
            modal.find('#issue_doc_label').text(b.data('label'));
            modal.find('#issue_requested_pages').text(b.data('pages'));
            // Mặc định cấp đúng các trang đã xin
            modal.find('#issued_pages').val(b.data('pages'));
            modal.find('#wrong_pages_voided').prop('checked', false);
        });

        // Sửa nội dung đề nghị
        $(document).on('click', '.btn-edit-reissue', function() {
            const d = $(this).data('json');
            const modal = $('#updateReissueModal');
            modal.find('#update_id').val(d.id);
            modal.find('#update_request_date').val(d.request_date ? d.request_date.substring(0, 10) : '');
            modal.find('#update_product_name').val(d.product_name);
            modal.find('#update_process_no').val(d.process_no);
            modal.find('#update_edition').val(d.edition);
            modal.find('#update_pages').val(d.pages);
            modal.find('#update_reason').val(d.reason);
            modal.find('#update_capa').val(d.capa);
            modal.find('#update_note').val(d.note);
        });

        // Xác nhận trước khi huỷ
        $(document).on('submit', '.form-cancel-reissue', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Huỷ đề nghị cấp lại hồ sơ?',
                text: 'Đề nghị sẽ được đánh dấu đã huỷ và không xử lý tiếp.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Huỷ đề nghị',
                cancelButtonText: 'Đóng',
                confirmButtonColor: '#dc3545'
            }).then((r) => {
                if (!r.isConfirmed) return;
                // form.submit() gốc không kích hoạt handler jQuery ở trên nên khoá nút tại đây.
                $(form).find('button[type="submit"]')
                    .prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin"></i>');
                form.submit();
            });
        });
    });
</script>
