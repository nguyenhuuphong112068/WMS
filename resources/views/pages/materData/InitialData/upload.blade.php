@php
    $uploadErrors = $errors->getBag('uploadErrors');
    $stages = \App\Support\InitialData\InitialDataSchema::STAGES;
    $modules = \App\Support\InitialData\InitialDataSchema::MODULES;
    $sharedStages = \App\Support\InitialData\InitialDataSchema::SHARED_STAGES;
@endphp

<div class="modal fade md-modal" id="uploadModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-upload"></i> Đính Kèm File Đã Điền</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.materData.initialData.upload') }}" method="POST" enctype="multipart/form-data"
                id="idtUploadForm" autocomplete="off">
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="idtUpStage">Giai Đoạn</label>
                            <select name="stage" id="idtUpStage" class="form-control" title="Giai đoạn của file mẫu đã điền">
                                <option value="">Cả 4 giai đoạn</option>
                                @foreach ($stages as $no => $label)
                                    <option value="{{ $no }}" @selected((string) old('stage') === (string) $no)>{{ $no }}. {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="idtUpModule">Module</label>
                            <select name="module" id="idtUpModule" class="form-control" title="Module của file mẫu đã điền">
                                <option value="">Cả 4 module</option>
                                @foreach ($modules as $key => $label)
                                    <option value="{{ $key }}" @selected(old('module') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-5">
                            <label for="idtUpDepartment" class="required">Phòng Ban Nộp</label>
                            <select name="department_id" id="idtUpDepartment" class="form-control" required>
                                @foreach ($uploadDepartments as $department)
                                    <option value="{{ $department->id }}"
                                        @selected((int) old('department_id', session('user')['selected_department_id'] ?? 0) === (int) $department->id)>
                                        {{ $department->shortName }} - {{ $department->name }}</option>
                                @endforeach
                            </select>
                            @if ($uploadErrors->has('department_id'))
                                <small class="text-danger">{{ $uploadErrors->first('department_id') }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="idtUpFile" class="required">File Đã Điền (.xlsx)</label>
                        <div class="custom-file">
                            <input type="file" name="excel_file" id="idtUpFile" accept=".xlsx" required
                                class="custom-file-input {{ $uploadErrors->has('excel_file') ? 'is-invalid' : '' }}">
                            <label class="custom-file-label" for="idtUpFile">Chọn file .xlsx</label>
                        </div>
                        @if ($uploadErrors->has('excel_file'))
                            <small class="text-danger">{{ $uploadErrors->first('excel_file') }}</small>
                        @endif
                    </div>
                    <div class="form-group mb-0">
                        <label for="idtUpNote">Ghi Chú</label>
                        <input type="text" name="note" id="idtUpNote" class="form-control" maxlength="1000" value="{{ old('note') }}"
                            placeholder="VD: bổ sung 12 mã hoá chất còn thiếu, thay file nộp lần 1">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary" title="Đính kèm để Admin tổng hợp - không ghi dữ liệu vào hệ thống">
                        <i class="fas fa-upload mr-1"></i> Đính kèm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var sharedStages = @json($sharedStages);
        var $modal = $('#uploadModal');

        function syncModule() {
            // Giai đoạn 1, 2 là file chung không chia module
            var shared = sharedStages.indexOf(parseInt($('#idtUpStage').val(), 10)) !== -1;
            $('#idtUpModule').prop('disabled', shared);
            if (shared) $('#idtUpModule').val('');
        }

        $('#idtUpStage').on('change', syncModule);
        syncModule();

        $('#idtUpFile').on('change', function() {
            $(this).next('.custom-file-label').text(this.files.length ? this.files[0].name : 'Chọn file .xlsx');
        });

        // Nút Đính kèm trên từng việc điền sẵn giai đoạn / module / phòng ban của việc đó
        $(document).on('click', '.btn-idt-upload', function() {
            var data = $(this).data();
            var $form = $('#idtUploadForm');

            $form[0].reset();
            $form.find('.custom-file-label').text('Chọn file .xlsx');
            if (data.stage !== undefined) $('#idtUpStage').val(String(data.stage));
            if (data.stage !== undefined) $('#idtUpModule').val(data.module || '');
            if (data.department) $('#idtUpDepartment').val(String(data.department));
            syncModule();
            $modal.modal('show');
        });

        $('#idtUploadForm').on('submit', function() {
            $('#idtUpModule').prop('disabled', false);
            $(this).find('button[type="submit"]').prop('disabled', true);
        });

        @if ($uploadErrors->any())
            $modal.modal('show');
        @endif
    });
</script>
