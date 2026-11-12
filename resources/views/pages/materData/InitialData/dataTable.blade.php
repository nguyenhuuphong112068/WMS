@include('pages.materData.shared.assets')

<style>
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

    .idt-form-row {
        display: flex;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 10px;
    }

    .idt-form-row .form-group {
        min-width: 190px;
        margin-bottom: 0;
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

    .idt-form-row .btn {
        border-radius: var(--border-radius-md);
        transition: all 0.2s;
    }

    .idt-form-row .btn:hover {
        transform: translateY(-1px);
    }

</style>

@php
    $stages = \App\Support\InitialData\InitialDataSchema::STAGES;
    $modules = \App\Support\InitialData\InitialDataSchema::MODULES;
    $reviewStatuses = \App\Http\Controllers\Pages\MaterData\InitialDataController::REVIEW_STATUSES;
@endphp

<div class="content-wrapper">
    <div class="md-page">

        {{-- Admin tải file mẫu bất kỳ; phòng ban chỉ tải file của từng giai đoạn / module / phòng mình --}}
        @if ($isAdmin || $uploadDepartments->isNotEmpty())
            <div class="card md-card">
                <div class="card-body">
                    <div class="idt-card-title"><i class="fas fa-file-excel"></i> File Excel mẫu</div>
                    <div class="idt-sheet-groups">
                        @foreach (collect(\App\Support\InitialData\InitialDataSchema::sheets())->pluck('group')->unique() as $group)
                            <span>{{ $group }}</span>
                        @endforeach
                    </div>
                    <form action="{{ route('pages.materData.initialData.template') }}" method="GET" class="idt-form-row">
                        <div class="form-group">
                            <label for="idtStage">Giai đoạn</label>
                            <select name="stage" id="idtStage" class="form-control"
                                title="Thứ tự: 1 Tổ chức → 2 Người dùng & Tổ / 3 Dữ liệu gốc & danh mục → 4 Tồn đầu kỳ. Giai đoạn 1, 2 là một file chung cho mọi phòng ban">
                                @if ($isAdmin)
                                    <option value="">Cả 4 giai đoạn</option>
                                @endif
                                @foreach ($stages as $no => $label)
                                    @continue(! $isAdmin && $no === 1)
                                    <option value="{{ $no }}" @selected(! $isAdmin && $no === 3)>{{ $no }}. {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="idtModule">Module</label>
                            <select name="module" id="idtModule" class="form-control"
                                title="File riêng một module: chỉ gồm sheet dùng chung + sheet của module đó">
                                @if ($isAdmin)
                                    <option value="">Cả 4 module</option>
                                @endif
                                @foreach ($modules as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group flex-grow-1">
                            <label for="idtDepartment">Phạm vi file</label>
                            <select name="department_id" id="idtDepartment" class="form-control"
                                title="File của một phòng: chỉ điền sẵn và chỉ cho khai dữ liệu của phòng đó - gửi song song cho nhiều phòng điền">
                                @if ($isAdmin)
                                    <option value="">Toàn hệ thống (Admin)</option>
                                @endif
                                @foreach ($isAdmin ? $departments : $uploadDepartments as $department)
                                    <option value="{{ $department->id }}">{{ $department->shortName }} - {{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" title="Tải file mẫu mới nhất theo giai đoạn / module / phòng ban đã chọn">
                            <i class="fas fa-download mr-1"></i> Tải file mẫu
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Giai đoạn 1, 2 là file chung cho mọi phòng ban: không chọn module / phòng ban
                var shared = @json(\App\Support\InitialData\InitialDataSchema::SHARED_STAGES);
                $('#idtStage').on('change', function() {
                    var isShared = shared.indexOf(parseInt(this.value, 10)) !== -1;
                    $('#idtModule, #idtDepartment').prop('disabled', isShared);
                }).trigger('change');
            });
        </script>

        {{-- Việc cần làm theo giai đoạn / phòng ban: đính kèm chỉ lưu file, không ghi vào hệ thống --}}
        @include('pages.materData.InitialData.tracker')
    </div>
</div>
