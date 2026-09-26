@php
    /*
    |--------------------------------------------------------------------------
    | SƠ ĐỒ LƯU TRỮ HOÁ CHẤT THEO HÌNH ĐỒ CẢNH BÁO (GHS) - bảng tương kỵ
    |--------------------------------------------------------------------------
    | Vẽ thẳng từ config('chemical.storage_incompatible') qua App\Support\ChemicalCompatibility
    | nên luôn khớp đúng quy tắc hệ thống đang dùng để chặn xếp định khu.
    | Mở bằng: <button data-toggle="modal" data-target="#storageChartModal">. Mở được cả khi
    | đang ở trong modal khác (tự xếp chồng lên trên).
    | Bọc @once nên @include nhiều lần trên một trang vẫn chỉ in ra một bản.
    */
    $scLabels = config('chemical.safety_warnings');
    $scCodes = array_values(array_filter(
        array_keys($scLabels),
        fn ($code) => array_key_exists($code, config('chemical.storage_incompatible', []))
    ));
    $scScope = \App\Support\ChemicalCompatibility::scope();
    $scOthers = array_diff(array_keys($scLabels), $scCodes);
@endphp

@once
    <div class="modal fade md-modal" id="storageChartModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-th"></i> Sơ Đồ Lưu Trữ Hoá Chất Theo Hình Đồ Cảnh Báo</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered sc-table mb-3">
                            <thead>
                                <tr>
                                    <th class="sc-corner">Hình đồ cảnh báo hoá chất nguy hiểm theo GHS</th>
                                    @foreach ($scCodes as $col)
                                        <th class="text-center" title="{{ $scLabels[$col] }}">
                                            @include('pages.shared.safetyPictogram', ['code' => $col, 'size' => 40])
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($scCodes as $row)
                                    <tr>
                                        <th class="sc-row">
                                            @include('pages.shared.safetyPictogram', ['code' => $row, 'size' => 30])
                                            <span>{{ \App\Support\ChemicalCompatibility::shortLabel($row) }}</span>
                                        </th>
                                        @foreach ($scCodes as $col)
                                            @if (\App\Support\ChemicalCompatibility::isIncompatible($row, $col))
                                                <td class="sc-cell is-x" title="Không được lưu trữ cùng nhau">✕</td>
                                            @else
                                                <td class="sc-cell is-o" title="Được lưu trữ cùng nhau">O</td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="sc-legend">
                        <span><b class="sc-cell is-o">O</b> được lưu trữ cùng nhau</span>
                        <span><b class="sc-cell is-x">✕</b> không được lưu trữ cùng nhau</span>
                    </div>

                    <div class="md-hint mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Hệ thống coi hai hoá chất là "để gần nhau" khi cùng <b>{{ $scScope['label'] }}</b>
                        (tính cả định khu đã khai ở Hoá Chất Của Phòng và lô đang còn tồn). Hoá chất có nhiều cảnh
                        báo thì chỉ cần một cặp rơi vào ô ✕ là tương kỵ.
                        @if ($scOthers)
                            Cảnh báo
                            <b>{{ implode(', ', array_map(fn ($code) => \App\Support\ChemicalCompatibility::shortLabel($code), $scOthers)) }}</b>
                            không có trong sơ đồ nên không xét tương kỵ.
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Bootstrap đang dùng không có .modal-xl nên phải tự đặt khổ, nếu không modal co về 500px */
        #storageChartModal .modal-dialog {
            max-width: 1280px;
        }

        @media (min-width: 576px) {
            #storageChartModal .modal-dialog {
                width: calc(100% - 48px);
            }
        }

        .sc-table {
            font-size: 0.9rem;
        }

        .sc-table th,
        .sc-table td {
            vertical-align: middle;
            padding: 8px;
        }

        .sc-table thead th {
            background: var(--primary-soft);
        }

        .sc-table .safety-picto {
            margin: 0 auto;
        }

        .sc-table .sc-corner {
            min-width: 190px;
            color: var(--primary-dark);
            font-weight: 700;
            text-align: center;
        }

        .sc-table .sc-row {
            background: #fff;
            font-weight: 600;
            white-space: nowrap;
        }

        .sc-table .sc-row .safety-picto {
            display: inline-block;
            vertical-align: middle;
            margin-right: 6px;
        }

        .sc-cell {
            text-align: center;
            font-weight: 700;
            font-size: 1rem;
            min-width: 56px;
        }

        .sc-cell.is-o {
            color: #16A34A;
            background: #F0FDF4;
        }

        .sc-cell.is-x {
            color: #DC2626;
            background: #FEF2F2;
        }

        .sc-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            font-size: 0.85rem;
        }

        .sc-legend .sc-cell {
            display: inline-block;
            min-width: 26px;
            border-radius: 6px;
            margin-right: 4px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /* ---------- Mở từ trong modal khác: xếp chồng lên trên ---------- */
            $('#storageChartModal').on('show.bs.modal', function() {
                if (!$('.modal:visible').not(this).length) return;

                $(this).css('z-index', 1075);
                setTimeout(function() {
                    $('.modal-backdrop').last().css('z-index', 1070);
                }, 0);
            });

            $('#storageChartModal').on('hidden.bs.modal', function() {
                $(this).css('z-index', '');
                // Bootstrap gỡ .modal-open khi đóng modal con -> trả lại cho modal cha còn mở
                if ($('.modal:visible').length) $(document.body).addClass('modal-open');
            });
        });
    </script>
@endonce
