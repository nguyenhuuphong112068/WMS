{{--
| CƠ SỞ DỮ LIỆU (chỉ Admin)
| Toàn bộ nội dung dựng bằng JS từ khối JSON #db-data (App\Support\DatabaseSchema::build):
| 5 tab Bảng / Sơ đồ quan hệ / Danh sách quan hệ / Migration / Kiểm tra.
--}}
@php
    $totalColumns = collect($schema['tables'])->sum(fn ($t) => count($t['columns']));
    $totalSize = collect($schema['tables'])->sum('size');
    $pending = collect($schema['migrations'])->where('ran', false)->count();
    $dangers = collect($schema['issues'])->where('level', 'danger')->count();
    $warnings = collect($schema['issues'])->where('level', 'warning')->count();
    $sizeText = $totalSize >= 1048576
        ? number_format($totalSize / 1048576, 1, ',', '.') . ' MB'
        : number_format($totalSize / 1024, 0, ',', '.') . ' KB';
@endphp

<div class="content-wrapper">
    <div class="db-page" id="dbPage">

        <div class="db-hero">
            <div>
                <h1><i class="fas fa-database"></i> Cơ Sở Dữ Liệu</h1>
                <div class="db-hero-meta">
                    <span><i class="fas fa-server"></i>{{ $schema['database'] }} · {{ $schema['version'] ?: $schema['driver'] }}</span>
                    <span><i class="fas fa-clock"></i>Cập nhật {{ \Carbon\Carbon::parse($schema['generated'])->format('H:i d/m/Y') }}</span>
                    <span><i class="fas fa-file-code"></i>{{ number_format($schema['files'], 0, ',', '.') }} file code đã quét</span>
                    <span class="db-live" id="db-live"><i class="fas fa-circle"></i> Đang theo dõi thay đổi</span>
                </div>
            </div>

            <div class="db-stats">
                <div class="db-stat"><b>{{ count($schema['tables']) }}</b><span>Bảng</span></div>
                <div class="db-stat"><b>{{ number_format($totalColumns, 0, ',', '.') }}</b><span>Trường</span></div>
                <div class="db-stat"><b>{{ count($schema['edges']) }}</b><span>Quan hệ</span></div>
                <div class="db-stat"><b>{{ $sizeText }}</b><span>Dung lượng</span></div>
                <div class="db-stat"><b>{{ count($schema['migrations']) }}</b><span>{{ $pending ? $pending . ' chờ chạy' : 'Migration' }}</span></div>
            </div>

            <a href="{{ route('pages.database.list', ['refresh' => 1]) }}" class="btn btn-sm btn-refresh" id="db-refresh"
                title="Quét lại toàn bộ code, bỏ qua bộ nhớ đệm">
                <i class="fas fa-sync-alt mr-1"></i> Quét lại
            </a>
        </div>

        <div class="db-changed" id="db-changed" hidden>
            <i class="fas fa-bolt"></i>
            <span>Cấu trúc dữ liệu hoặc code vừa thay đổi so với lúc mở trang</span>
            <button type="button" class="btn btn-sm btn-primary db-btn" id="db-reload">
                <i class="fas fa-redo mr-1"></i> Tải lại
            </button>
        </div>

        <div class="db-tabs" role="tablist">
            <button type="button" class="db-tab" data-tab="tables">
                <i class="fas fa-table"></i>Bảng<span class="db-count">{{ count($schema['tables']) }}</span>
            </button>
            <button type="button" class="db-tab" data-tab="diagram">
                <i class="fas fa-project-diagram"></i>Sơ đồ quan hệ
            </button>
            <button type="button" class="db-tab" data-tab="relations">
                <i class="fas fa-link"></i>Danh sách quan hệ<span class="db-count">{{ count($schema['edges']) }}</span>
            </button>
            <button type="button" class="db-tab" data-tab="migrations">
                <i class="fas fa-code-branch"></i>Migration<span class="db-count {{ $pending ? 'is-danger' : '' }}">{{ $pending ?: count($schema['migrations']) }}</span>
            </button>
            <button type="button" class="db-tab" data-tab="issues">
                <i class="fas fa-stethoscope"></i>Kiểm tra
                @if ($dangers)
                    <span class="db-count is-danger" title="Lỗi cần sửa">{{ $dangers }}</span>
                @endif
                @if ($warnings)
                    <span class="db-count is-warning" title="Cảnh báo">{{ $warnings }}</span>
                @endif
                @if (! $dangers && ! $warnings)
                    <span class="db-count"><i class="fas fa-check m-0"></i></span>
                @endif
            </button>
        </div>

        {{-- ===== Bảng ===== --}}
        <section class="db-pane" data-pane="tables" hidden>
            <div class="db-split">
                <aside class="db-side db-card">
                    <div class="db-side-tools">
                        <input type="search" id="db-search" class="form-control form-control-sm" placeholder="Tìm bảng hoặc trường..." autocomplete="off">
                        <select id="db-group" class="form-control form-control-sm">
                            <option value="">Tất cả nhóm</option>
                            @foreach ($schema['groups'] as $g)
                                @if ($g['count'])
                                    <option value="{{ $g['key'] }}">{{ $g['label'] }} ({{ $g['count'] }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="db-table-list" id="db-table-list"></div>
                </aside>

                <div class="db-detail db-card" id="db-detail">
                    <div class="db-placeholder">
                        <i class="fas fa-table"></i>
                        <span>Chọn một bảng ở danh sách bên trái</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== Sơ đồ quan hệ ===== --}}
        <section class="db-pane" data-pane="diagram" hidden>
            <div class="db-card db-erd">
                <div class="db-erd-bar">
                    <div class="db-seg" id="erd-mode">
                        <button type="button" data-mode="overview"><i class="fas fa-th-large mr-1"></i> Toàn cảnh</button>
                        <button type="button" data-mode="focus"><i class="fas fa-bullseye mr-1"></i> Theo bảng</button>
                    </div>

                    <div class="db-select">
                        <select id="erd-table" class="form-control form-control-sm">
                            @foreach ($schema['groups'] as $g)
                                @if ($g['count'])
                                    <optgroup label="{{ $g['label'] }}">
                                        @foreach ($schema['tables'] as $t)
                                            @if ($t['group'] === $g['key'])
                                                <option value="{{ $t['name'] }}">{{ $t['name'] }}</option>
                                            @endif
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="db-seg" id="erd-depth" data-focus-only>
                        <button type="button" data-depth="1">1 cấp</button>
                        <button type="button" data-depth="2">2 cấp</button>
                    </div>

                    <label class="db-check" title="Quan hệ lấy từ điều kiện join / whereColumn trong code">
                        <input type="checkbox" data-src="join" checked> Join trong code
                    </label>
                    <label class="db-check" title="Quan hệ khai trong Eloquent Model">
                        <input type="checkbox" data-src="model" checked> Model
                    </label>
                    <label class="db-check" title="Suy theo quy ước tên trường *_id, code chưa join trực tiếp">
                        <input type="checkbox" data-src="name" checked> Theo tên trường
                    </label>
                    <label class="db-check" title="Join giữa hai trường cùng không phải khoá (ví dụ cùng category_id)">
                        <input type="checkbox" data-src="nn"> Join ngang
                    </label>
                    <label class="db-check" data-focus-only>
                        <input type="checkbox" id="erd-all-cols"> Mọi trường
                    </label>

                    <div class="db-zoom" data-focus-only>
                        <button type="button" data-zoom="out" title="Thu nhỏ"><i class="fas fa-minus"></i></button>
                        <button type="button" data-zoom="fit" title="Vừa khung"><i class="fas fa-expand"></i></button>
                        <button type="button" data-zoom="in" title="Phóng to"><i class="fas fa-plus"></i></button>
                    </div>
                </div>

                <div class="db-legend">
                    <span><svg width="34" height="8"><path d="M0 4H34" class="erd-edge src-join" /></svg>Join trong code</span>
                    <span><svg width="34" height="8"><path d="M0 4H34" class="erd-edge src-model" /></svg>Model</span>
                    <span><svg width="34" height="8"><path d="M0 4H34" class="erd-edge src-name" /></svg>Theo tên trường</span>
                    <span><svg width="34" height="8"><path d="M0 4H34" class="erd-edge src-fk" /></svg>Khoá ngoại</span>
                    <span><svg width="34" height="8"><path d="M0 4H34" class="erd-edge card-nn" /></svg>Join ngang</span>
                    @foreach ($schema['groups'] as $g)
                        @if ($g['count'])
                            <span><i class="db-dot db-g-{{ $g['key'] }}"></i>{{ $g['label'] }}</span>
                        @endif
                    @endforeach
                </div>

                <div class="db-stage" id="erd-stage">
                    <svg class="erd" id="erd-svg" style="display:none">
                        <g id="erd-root"></g>
                    </svg>
                    <div class="db-board" id="erd-board" hidden>
                        <div class="db-board-inner" id="erd-board-inner"></div>
                    </div>
                    <div class="db-tip" id="erd-tip" hidden></div>
                </div>
            </div>
        </section>

        {{-- ===== Danh sách quan hệ ===== --}}
        <section class="db-pane" data-pane="relations" hidden>
            <div class="db-card db-list-card">
                <div class="md-tablebar-item">
                    <select id="db-rel-filter" class="form-control form-control-sm db-filter">
                        <option value="">Tất cả nguồn</option>
                        <option value="join">Join trong code</option>
                        <option value="model">Model</option>
                        <option value="name">Theo tên trường</option>
                        <option value="fk">Khoá ngoại</option>
                        <option value="nn">Join ngang</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table id="db-rel-table" class="table table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Bảng Giữ Khoá</th>
                                <th>Trường</th>
                                <th>Bảng Được Tham Chiếu</th>
                                <th>Trường</th>
                                <th>Kiểu</th>
                                <th>Nguồn</th>
                                <th>Số Chỗ Dùng</th>
                                <th>Vị Trí Trong Code</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>

        {{-- ===== Migration ===== --}}
        <section class="db-pane" data-pane="migrations" hidden>
            <div class="db-card db-list-card">
                <div class="md-tablebar-item">
                    <select id="db-mig-filter" class="form-control form-control-sm db-filter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="ran">Đã chạy</option>
                        <option value="pending">Chờ chạy</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table id="db-mig-table" class="table table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Thời Điểm</th>
                                <th>Migration</th>
                                <th>Bảng Tác Động</th>
                                <th>Batch</th>
                                <th>Trạng Thái</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>

        {{-- ===== Kiểm tra ===== --}}
        <section class="db-pane" data-pane="issues" hidden>
            <div class="db-issues" id="db-issues"></div>
        </section>
    </div>
</div>

<script type="application/json" id="db-data">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
<script type="application/json" id="db-config">{!! json_encode([
    'signatureUrl' => route('pages.database.signature'),
    'rowsUrl'      => route('pages.database.rows'),
    'editor'       => $editor,
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
