@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.shared.assets')

    <style>
        .idt-grid {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) minmax(320px, 2fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 991px) {
            .idt-grid {
                grid-template-columns: 1fr;
            }
        }

        .idt-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .idt-card-title i {
            width: 36px;
            height: 36px;
            border-radius: var(--border-radius-md);
            background: var(--primary-soft);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .idt-download {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .idt-template-form {
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 10px;
        }

        .idt-sheet-groups {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 16px;
        }

        .idt-sheet-groups span {
            background: var(--primary-soft);
            color: var(--primary-dark);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .idt-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .idt-actions .btn,
        .idt-download .btn {
            border-radius: var(--border-radius-md);
            transition: all 0.2s;
        }

        .idt-actions .btn:hover,
        .idt-download .btn:hover {
            transform: translateY(-1px);
        }

        .idt-result-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 16px;
        }

        .idt-result-head .md-sub {
            font-size: 0.85rem;
        }

        .idt-badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .idt-section {
            font-weight: 700;
            color: var(--primary-dark);
            margin: 20px 0 10px;
        }

        .idt-num {
            font-variant-numeric: tabular-nums;
        }
    </style>

    @php
        $sheetGroups = collect(\App\Support\InitialData\InitialDataSchema::sheets())->pluck('group')->unique()->values();
        $errorBag = $errors->getBag('initialDataErrors');
    @endphp

    <div class="content-wrapper">
        <div class="md-page">

            <div class="idt-grid">
                {{-- Tải file mẫu --}}
                <div class="card md-card mb-0">
                    <div class="card-body idt-download">
                        <div>
                            <div class="idt-card-title"><i class="fas fa-file-excel"></i> File Excel mẫu</div>
                            <div class="idt-sheet-groups">
                                @foreach ($sheetGroups as $group)
                                    <span>{{ $group }}</span>
                                @endforeach
                            </div>
                        </div>
                        <form action="{{ route('pages.materData.initialData.template') }}" method="GET" class="idt-template-form">
                            <div class="form-group mb-0">
                                <label for="idtStage">Giai đoạn</label>
                                <select name="stage" id="idtStage" class="form-control"
                                    title="Nạp lần lượt: 1 Tổ chức → 2 Người dùng / 3 Dữ liệu gốc & danh mục → 4 Tồn đầu kỳ. Giai đoạn 1, 2 là một file chung cho mọi phòng ban. File giai đoạn sau lấy dữ liệu giai đoạn trước theo hệ thống lúc tải">
                                    <option value="">Cả 3 giai đoạn</option>
                                    @foreach (\App\Support\InitialData\InitialDataSchema::STAGES as $no => $label)
                                        <option value="{{ $no }}">{{ $no }}. {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label for="idtModule">Module</label>
                                <select name="module" id="idtModule" class="form-control"
                                    title="File riêng một module: chỉ gồm sheet dùng chung + sheet của module đó">
                                    <option value="">Cả 3 module</option>
                                    @foreach (\App\Support\InitialData\InitialDataSchema::MODULES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0 flex-grow-1">
                                <label for="idtDepartment">Phạm vi file</label>
                                <select name="department_id" id="idtDepartment" class="form-control"
                                    title="File của một phòng: chỉ điền sẵn và chỉ cho khai dữ liệu của phòng đó - gửi song song cho nhiều phòng điền">
                                    <option value="">Toàn hệ thống (Admin)</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->shortName }} - {{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"
                                title="Tải file mẫu mới nhất: {{ count(\App\Support\InitialData\InitialDataSchema::sheets()) }} sheet dữ liệu liên kết + sheet hướng dẫn">
                                <i class="fas fa-download mr-1"></i> Tải file mẫu
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Nạp file --}}
                <div class="card md-card mb-0">
                    <div class="card-body">
                        <div class="idt-card-title"><i class="fas fa-file-import"></i> Nạp dữ liệu từ file</div>

                        <form id="idtForm" action="{{ route('pages.materData.initialData.process') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="mode" value="check">

                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label for="idtFile">File khởi tạo đã điền <span class="text-danger">*</span></label>
                                    <div class="custom-file">
                                        <input type="file" name="excel_file" id="idtFile" accept=".xlsx"
                                            class="custom-file-input {{ $errorBag->has('excel_file') ? 'is-invalid' : '' }}">
                                        <label class="custom-file-label" for="idtFile">Chọn file .xlsx</label>
                                    </div>
                                    @if ($errorBag->has('excel_file'))
                                        <div class="text-danger small mt-1">{{ $errorBag->first('excel_file') }}</div>
                                    @endif
                                </div>
                                <div class="form-group col-md-5">
                                    <label for="idtPassword">Mật khẩu khởi tạo tài khoản mới</label>
                                    <input type="password" name="password" id="idtPassword" class="form-control"
                                        autocomplete="new-password" placeholder="VD: Wms@2026"
                                        title="Dùng chung cho mọi tài khoản mới ở sheet 05; người dùng phải đổi ở lần đăng nhập đầu. Tối thiểu 6 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt.">
                                </div>
                            </div>

                            <div class="idt-actions">
                                <button type="button" class="btn btn-outline-primary btn-idt-submit" data-mode="check"
                                    title="Chạy toàn bộ kiểm tra, không ghi dữ liệu">
                                    <i class="fas fa-clipboard-check mr-1"></i> Kiểm tra
                                </button>
                                <button type="button" class="btn btn-primary btn-idt-submit" data-mode="import"
                                    title="Ghi dữ liệu vào hệ thống - chỉ ghi khi toàn bộ file không còn lỗi">
                                    <i class="fas fa-database mr-1"></i> Nạp vào hệ thống
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if ($report)
                <div class="card md-card">
                    <div class="card-body">
                        <div class="idt-result-head">
                            <div class="idt-card-title mb-0"><i class="fas fa-list-check"></i> Kết quả
                                {{ $report['mode'] === 'import' ? 'nạp dữ liệu' : 'kiểm tra' }}</div>
                            <div class="d-flex align-items-center flex-wrap" style="gap: 10px">
                                <span class="md-sub"><i class="far fa-file-excel mr-1"></i>{{ $report['file_name'] }} ·
                                    {{ $report['ran_at'] }}</span>
                                @if ($report['committed'])
                                    <span class="badge badge-success idt-badge"><i class="fas fa-circle-check mr-1"></i>Đã nạp vào hệ thống</span>
                                @elseif ($report['error_total'])
                                    <span class="badge badge-danger idt-badge"><i class="fas fa-ban mr-1"></i>{{ $report['error_total'] }} lỗi - chưa ghi dữ liệu</span>
                                @else
                                    <span class="badge badge-info idt-badge"><i class="fas fa-clipboard-check mr-1"></i>Kiểm tra đạt - chưa ghi dữ liệu</span>
                                @endif
                            </div>
                        </div>

                        @if ($report['error_total'])
                            <div class="idt-section text-danger"><i class="fas fa-triangle-exclamation mr-1"></i>
                                Lỗi cần sửa ({{ $report['error_total'] }}{{ $report['error_total'] > count($report['errors']) ? ', hiển thị '.count($report['errors']).' lỗi đầu' : '' }})
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover w-100 md-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 190px">Sheet</th>
                                            <th class="text-center" style="width: 70px">Dòng</th>
                                            <th style="width: 190px">Cột</th>
                                            <th>Lỗi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($report['errors'] as $error)
                                            <tr>
                                                <td>{{ $error['sheet'] ?: '—' }}</td>
                                                <td class="text-center idt-num">{{ $error['row'] ?? '—' }}</td>
                                                <td>{{ $error['column'] ?? '—' }}</td>
                                                <td class="text-danger">{{ $error['message'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if (count($report['warnings']))
                            <div class="idt-section text-warning"><i class="fas fa-circle-exclamation mr-1"></i>
                                Lưu ý ({{ count($report['warnings']) }})</div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover w-100 md-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 190px">Sheet</th>
                                            <th class="text-center" style="width: 70px">Dòng</th>
                                            <th style="width: 190px">Cột</th>
                                            <th>Nội dung</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($report['warnings'] as $warning)
                                            <tr>
                                                <td>{{ $warning['sheet'] }}</td>
                                                <td class="text-center idt-num">{{ $warning['row'] ?? '—' }}</td>
                                                <td>{{ $warning['column'] ?? '—' }}</td>
                                                <td>{{ $warning['message'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="idt-section"><i class="fas fa-table-list mr-1"></i> Số liệu theo sheet</div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover w-100 md-table">
                                <thead>
                                    <tr>
                                        <th>Sheet</th>
                                        <th class="text-right" style="width: 120px">Số dòng</th>
                                        <th class="text-right" style="width: 160px">{{ $report['committed'] ? 'Đã thêm mới' : 'Sẽ thêm mới' }}</th>
                                        <th class="text-right" style="width: 160px">Đã có sẵn (giữ nguyên)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (collect($report['stats'])->where('rows', '>', 0) as $stat)
                                        <tr>
                                            <td class="font-weight-bold">{{ $stat['title'] }}</td>
                                            <td class="text-right idt-num">{{ number_format($stat['rows']) }}</td>
                                            <td class="text-right idt-num text-success font-weight-bold">{{ number_format($stat['inserted']) }}</td>
                                            <td class="text-right idt-num md-sub">{{ number_format($stat['existing']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#idtFile').on('change', function() {
                $(this).next('.custom-file-label').text(this.files.length ? this.files[0].name : 'Chọn file .xlsx');
            });

            $('.btn-idt-submit').on('click', function() {
                var mode = $(this).data('mode');
                var $form = $('#idtForm');

                if (!$('#idtFile').val()) {
                    Swal.fire({ icon: 'warning', title: 'Chưa chọn file', text: 'Vui lòng chọn file Excel khởi tạo dữ liệu.' });
                    return;
                }

                var submit = function() {
                    $form.find('[name="mode"]').val(mode);
                    $('.btn-idt-submit').prop('disabled', true);
                    Swal.fire({
                        title: mode === 'import' ? 'Đang nạp dữ liệu...' : 'Đang kiểm tra...',
                        allowOutsideClick: false,
                        didOpen: function() { Swal.showLoading(); }
                    });
                    $form[0].submit();
                };

                if (mode !== 'import') {
                    submit();
                    return;
                }

                Swal.fire({
                    icon: 'question',
                    title: 'Nạp file vào hệ thống?',
                    text: 'Dữ liệu chỉ được ghi khi toàn bộ file không còn lỗi. Tồn đầu kỳ của mỗi phòng chỉ nạp được một lần.',
                    showCancelButton: true,
                    confirmButtonText: 'Nạp vào hệ thống',
                    cancelButtonText: 'Huỷ',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#2E7BC4'
                }).then(function(result) {
                    if (result.isConfirmed) submit();
                });
            });
        });
    </script>
@endsection
