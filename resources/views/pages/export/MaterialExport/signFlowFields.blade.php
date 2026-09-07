{{--
    Khối khai QUY TRÌNH KÝ DUYỆT của một đề nghị cấp phát vật tư.
    Biến vào: $signerOptions (danh sách người ký chọn được), $flowSigners (mảng user_id đã khai).
    Mỗi dòng là một bước; không có dòng nào nghĩa là 0 bước - phiếu đi thẳng đến người cấp phát.
--}}
@php $flowSigners = $flowSigners ?? []; @endphp

<div class="me-flow-box">
    <div class="me-flow-head">
        <div class="me-flow-title-wrap">
            <span class="me-flow-icon"><i class="fas fa-tasks"></i></span>
            <span class="me-flow-label">Quy trình ký duyệt</span>
            <span class="me-flow-count"></span>
        </div>
        <span class="me-flow-hint d-none d-md-inline text-muted">
            <i class="fas fa-info-circle mr-1"></i>Duyệt tuần tự theo thứ tự các bước
        </span>
        <button type="button" class="btn btn-sm btn-primary shadow-sm me-add-signer ml-auto">
            <i class="fas fa-plus mr-1"></i>Thêm bước ký
        </button>
    </div>

    <div class="me-flow-steps">
        @foreach ($flowSigners as $signerId)
            <div class="me-step-row">
                <div class="me-step-badge">
                    <span class="me-step-no"></span>
                    <span class="me-step-tag">Bước <span class="me-step-idx"></span>:</span>
                </div>
                <select name="signers[]" class="form-control me-step-user" required>
                    <option value="">-- Chọn người ký duyệt --</option>
                    @foreach ($signerOptions as $person)
                        @php
                            $label = ($person->fullName ?: $person->userName)
                                . ($person->role_name ? ' — ' . $person->role_name : '')
                                . ($person->department_short ? ' · ' . $person->department_short : '');
                        @endphp
                        <option value="{{ $person->id }}" title="{{ $label }}" {{ (int) $signerId === (int) $person->id ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="btn me-del-signer" title="Xoá bước ký"><i class="fas fa-trash-alt"></i></button>
            </div>
        @endforeach
    </div>

    <div class="me-flow-none">
        <i class="fas fa-info-circle text-primary" style="font-size: 1.2rem; flex-shrink: 0;"></i>
        <div>
            <div style="font-weight: 600; color: #1e293b;">Chưa thiết lập bước ký nào</div>
            <div style="font-size: 0.78rem; color: #64748b; margin-top: 1px;">
                Khi bấm <b>Trình ký</b>, phiếu sẽ tự động được phê duyệt ngay và gửi thẳng đến bộ phận kho cấp phát.
            </div>
        </div>
    </div>
</div>
