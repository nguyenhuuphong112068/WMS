{{--
| SỬ DỤNG VẬT TƯ - TAB "ĐỀ NGHỊ VPP" (đề nghị vật tư từ kho VPP của phòng Hành Chính)
|
| Đề nghị VPP là đề nghị chuyển vật tư liên phòng ban GỬI TỚI phòng Hành Chính (xem
| App\Support\MaterialVpp) nên hai bảng gửi đi / gửi đến và các bước Hành Chính cấp phát ->
| phòng đề nghị nhận hàng dùng nguyên của tab Chuyển liên phòng ban (transferLists,
| transferDetailModal).
|
| Hai tab con:
|   - Đề nghị VPP: phiếu phòng mình đã gửi (phòng thường) / phiếu các phòng gửi tới (Hành Chính).
|   - Danh sách vật tư cần đề nghị Hành Chính: vật tư Hành Chính mua đang dưới ngưỡng / người
|     dùng đề nghị / thiếu tồn khả dụng (MaterialWatchlist::forVpp) - tick chọn rồi Lập đề nghị VPP.
|     Phòng Hành Chính không tự đề nghị kho của mình nên không có tab này.
--}}
@php
    $vppOpenCount = $vppWatchlistItems->filter(fn ($item) => ! $item->in_progress)->count();
    $vppShowWatchlist = ! $vppIsAdminDepartment && $vppTarget;
    $vppActiveSub = $vppShowWatchlist ? $vppSubTab : 'requests';
@endphp

@tab('export_material', 'vpp')
<div class="exp-pane {{ $activeTab === 'vpp' ? 'is-active' : '' }}" id="mePaneVpp">

    @if ($vppShowWatchlist)
        {{-- Tab con + nút tạo đề nghị chung một hàng --}}
        <div class="vpp-head">
        <ul class="nav nav-tabs vpp-subtabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $vppActiveSub === 'requests' ? 'active' : '' }}" data-toggle="tab" href="#vppSubRequests" role="tab">
                    <i class="fas fa-list-alt mr-1"></i> Đề nghị cấp phát VPP đến HC
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $vppActiveSub === 'watchlist' ? 'active' : '' }}" data-toggle="tab" href="#vppSubWatchlist" role="tab">
                    <i class="fas fa-clipboard-list mr-1"></i> Danh sách vật tư cần đề nghị Hành Chính
                    <span class="badge badge-warning">{{ $vppOpenCount }}</span>
                </a>
            </li>
        </ul>

        @perm('export_material_request')
            <button type="button" class="btn btn-primary btn-sm vpp-create-btn" data-toggle="modal" data-target="#matVppRequestModal">
                <i class="fas fa-plus mr-1"></i> Tạo đề nghị cấp phát VPP đến HC
            </button>
        @endperm
        </div>
    @endif

    <div class="tab-content">
        <div class="tab-pane fade {{ $vppActiveSub === 'requests' ? 'show active' : '' }}" id="vppSubRequests" role="tabpanel">
            @include('pages.export.MaterialExport.transferLists', [
                'mtTab' => 'vpp',
                'mtPrefix' => 'v',
                'mtTableId' => 'matVpp',
                'mtSent' => $vppSent,
                'mtReceived' => $vppReceived,
                'mtToSign' => $vppToSign,
                'mtSentPerPage' => $vppSentPerPage,
                'mtSentDone' => $vppSentDone,
                'mtSentDoneRange' => $vppSentDoneRange,
                'mtSentDonePerPage' => $vppSentDonePerPage,
                'mtReceivedPerPage' => $vppReceivedPerPage,
                'mtDone' => $vppDone,
                'mtDoneRange' => $vppDoneRange,
                'mtDonePerPage' => $vppDonePerPage,
                'mtReceivedTitle' => 'Đề nghị cấp phát VPP các phòng gửi đến',
                'mtEmptySent' => 'Không có đề nghị gửi đi nào đang chờ xử lý.',
                'mtEmptySentDone' => 'Không có đề nghị đã hoàn thành trong khoảng đã lọc.',
                'mtEmptyReceived' => 'Không còn đề nghị nào cần cấp phát.',
                'mtEmptyDone' => 'Không có đề nghị đã hoàn thành trong khoảng đã lọc.',
                // Chỉ phòng Hành Chính nhận đề nghị VPP
                'mtShowReceived' => $vppIsAdminDepartment,
            ])
        </div>

        @if ($vppShowWatchlist)
            <div class="tab-pane fade {{ $vppActiveSub === 'watchlist' ? 'show active' : '' }}" id="vppSubWatchlist" role="tabpanel">
                @include('pages.estimate.MaterialEstimate.watchlistTable', [
                    'items' => $vppWatchlistItems,
                    'wlMode' => 'vpp',
                ])
            </div>
        @endif
    </div>
</div>
@endtab

<style>
    .vpp-head {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        margin-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }

    .vpp-subtabs {
        flex: 1 1 auto;
        margin-bottom: -1px;
        border-bottom: none;
    }

    .vpp-create-btn {
        margin-bottom: 5px;
        white-space: nowrap;
    }

    .vpp-subtabs .nav-link {
        font-weight: 700;
        font-size: 0.88rem;
        color: #64748b;
    }

    .vpp-subtabs .nav-link.active {
        color: var(--primary);
    }
</style>
