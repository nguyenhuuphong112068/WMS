{{--
|--------------------------------------------------------------------------
| DỰ TRÙ - KHỐI QUY TRÌNH KÝ DUYỆT (modal Trình ký) - CHỈ HIỂN THỊ
|--------------------------------------------------------------------------
| Quy trình CỐ ĐỊNH 2 bước (config/estimate.php), cả hai tự gán theo VAI TRÒ + PHẠM VI PHÒNG
| BAN (App\Support\EstimateSigner) - người lập không chọn người ký:
|   Bước 1 - Quản Lý Bộ Phận của phòng đang chọn.
|   Bước 2 - Ban Giám Đốc có phạm vi phòng ban bao gồm phòng đang chọn.
| Mọi người trong danh sách của bước đều ký được bước đó.
|
| Biến vào: $flowErrorBag - tên error bag
--}}
@php
    $esfBag = $errors->getBag($flowErrorBag ?? 'createErrors');
    $esfDeptId = (int) (session('user')['selected_department_id'] ?? 0);
    $esfNames = fn ($people) => $people->map(fn ($p) => $p->fullName ?: $p->userName)->implode(', ');

    $esfSteps = [
        [
            'roles' => \App\Support\EstimateSigner::managerRoles(),
            'people' => \App\Support\EstimateSigner::managers($esfDeptId),
            'tag' => '<i class="fas fa-users mr-1"></i>Cả bộ phận',
            'class' => 'esf-step-tag',
            'empty' => 'để ký bước 1',
        ],
        [
            'roles' => \App\Support\EstimateSigner::bodRoles(),
            'people' => \App\Support\EstimateSigner::bods($esfDeptId),
            'tag' => '<i class="fas fa-stamp mr-1"></i>Bước cuối',
            'class' => 'esf-bod-tag',
            'empty' => 'được phân quyền (phạm vi phòng ban) để ký bước 2',
        ],
    ];
@endphp

<div class="form-group esf-box">
    <label class="mb-1">
        <i class="fas fa-tasks mr-1"></i> Quy Trình Ký Duyệt
    </label>

    <div class="esf-steps">
        @foreach ($esfSteps as $step)
            @php $stepNames = $esfNames($step['people']) ?: 'Chưa có người thuộc vai trò này'; @endphp
            <div class="esf-row {{ $loop->last ? 'esf-bod-row' : '' }}">
                <span class="esf-no">{{ $loop->iteration }}</span>
                <div class="esf-fixed {{ $step['people']->isEmpty() ? 'is-empty' : '' }}" title="{{ $stepNames }}">
                    <b>{{ implode(' / ', $step['roles']) }}</b>
                    <span>&middot; {{ $stepNames }}</span>
                </div>
                <span class="{{ $step['class'] }}">{!! $step['tag'] !!}</span>
            </div>
        @endforeach
    </div>

    @foreach ($esfSteps as $step)
        @if ($step['people']->isEmpty())
            <div class="text-danger small mt-1">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                Phòng ban chưa có tài khoản {{ implode(' / ', $step['roles']) }} nào {{ $step['empty'] }}.
            </div>
        @endif
    @endforeach

    @if ($esfBag->has('signers'))
        <span class="md-error d-block">{{ $esfBag->first('signers') }}</span>
    @endif
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

    .esf-bod-row .esf-no {
        background: var(--primary-dark);
    }

    .esf-row .esf-fixed {
        flex: 1 1 auto;
        min-width: 0;
        padding: 7px 12px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-md);
        background: #fff;
        font-size: 0.86rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .esf-row .esf-fixed.is-empty {
        border-color: #FCA5A5;
        color: #DC2626;
    }

    .esf-bod-tag,
    .esf-step-tag {
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
