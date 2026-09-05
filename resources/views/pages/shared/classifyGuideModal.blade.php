@php
    /*
    |--------------------------------------------------------------------------
    | Chú thích ý nghĩa 4 Phụ lục + 10 nhóm phân loại NĐ 24/2026/NĐ-CP
    |--------------------------------------------------------------------------
    | Dùng chung cho: Tên Hoạt Chất, Tên Hoá Chất, Danh Mục Hoá Chất.
    | Mở bằng: <button data-toggle="modal" data-target="#classifyGuideModal">.
    | Nội dung theo cột "GHI CHÚ" của biểu mẫu Nghị định; nhãn 10 nhóm lấy thẳng
    | từ App\Support\ChemicalClassification::GROUPS để luôn khớp với dữ liệu.
    | Bọc @once nên @include nhiều lần trên một trang vẫn chỉ in ra một bản.
    */
    $cgAppendix = [
        'I' => 'Danh mục hoá chất cơ bản trong lĩnh vực công nghiệp hoá chất trọng điểm. Không thuộc nhóm nào của "hình 1" - chỉ lưu để giữ vết "thuộc Phụ lục I".',
        'II' => 'Danh mục hoá chất, hỗn hợp chất sản xuất, kinh doanh có điều kiện trong lĩnh vực công nghiệp (nhóm 1: hoá chất · nhóm 2: hỗn hợp).',
        'III' => 'Danh mục hoá chất cần kiểm soát đặc biệt. Chia nhóm 1 / nhóm 2, mỗi nhóm có các bảng A (tiền chất công nghiệp), B (hoá chất cấm), C (công ước quốc tế).',
        'IV' => 'Danh mục hoá chất phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố hoá chất. Bảng A: hoá chất đơn · Bảng B: hỗn hợp phân loại theo nhóm nguy hại GHS.',
    ];
@endphp

@once
    <div class="modal fade md-modal" id="classifyGuideModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> Phân loại hoá chất theo Nghị định 24/2026/NĐ-CP</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body cg-body">

                    <h6 class="cg-h">Bốn Phụ lục của Nghị định</h6>
                    <table class="table table-sm cg-table mb-4">
                        <tbody>
                            @foreach ($cgAppendix as $apx => $note)
                                <tr>
                                    <td class="cg-apx"><span class="cg-apx-badge">{{ $apx }}</span></td>
                                    <td>{{ $note }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <h6 class="cg-h">Mười nhóm phân loại ("hình 1")</h6>
                    <table class="table table-sm table-bordered cg-table cg-groups mb-2">
                        <thead>
                            <tr>
                                <th style="width: 74px" class="text-center">Nhóm</th>
                                <th>Ý nghĩa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Support\ChemicalClassification::GROUPS as $g => $label)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge {{ \App\Support\ChemicalClassification::badgeClass($g) }}">Nhóm {{ $g }}</span>
                                    </td>
                                    <td>{{ $label }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="cg-legend mb-4">
                        <span><span class="badge badge-danger">&nbsp;</span> Nhóm 9, 10 – phải lập Kế hoạch phòng ngừa, ứng phó sự cố (Phụ lục IV)</span>
                        <span><span class="badge badge-warning text-dark">&nbsp;</span> Nhóm 4, 6 – hoá chất cấm (Phụ lục III bảng B)</span>
                        <span><span class="badge badge-primary">&nbsp;</span> Các nhóm còn lại</span>
                    </div>

                    <h6 class="cg-h">Cách xác định một hỗn hợp có thuộc nhóm 8 hay không</h6>
                    <p class="cg-p">Xét từng thành phần của hỗn hợp, đối chiếu với danh mục Nghị định 24:</p>
                    <ul class="cg-list mb-4">
                        <li>Có ≥ 1 thành phần thuộc <b>nhóm 3 / 4 / 6 / 7</b> với tỉ lệ <b>&gt; 1%</b> → hỗn hợp thuộc <b>nhóm 8</b>.</li>
                        <li>Có ≥ 1 thành phần thuộc <b>nhóm 5</b> với tỉ lệ <b>&gt; 5%</b> → hỗn hợp thuộc <b>nhóm 8</b>.</li>
                    </ul>

                    <h6 class="cg-h">Cách xác định một hỗn hợp có thuộc nhóm 10 hay không</h6>
                    <p class="cg-p">
                        Xét từng thành phần của hỗn hợp, đối chiếu với danh mục Nghị định 24: có ≥ 1 thành phần
                        thuộc <b>Phụ lục IV Bảng A</b> (nhóm 9) → hỗn hợp thuộc <b>nhóm 10</b>
                        <span class="cg-note">(trên màn Tên Hoá Chất còn cần tick ít nhất một nhóm nguy hại Bảng B).</span>
                    </p>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        <b>Ngưỡng tồn trữ (kg)</b> chỉ có ý nghĩa với hoá chất <b>nhóm 9 &amp; 10</b> – là khối lượng tồn
                        lớn nhất tại một thời điểm tại một bộ phận, dùng để cảnh báo phải lập Kế hoạch phòng ngừa.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .cg-body .cg-h {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }

        .cg-body .cg-table td,
        .cg-body .cg-table th {
            vertical-align: middle;
            font-size: 0.86rem;
        }

        .cg-body .cg-table.cg-groups thead th {
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .cg-body .cg-apx {
            width: 54px;
            text-align: center;
        }

        .cg-body .cg-apx-badge {
            display: inline-block;
            min-width: 34px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            border: 1px solid var(--primary-lighter);
            border-radius: 5px;
            padding: 1px 8px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .cg-body .cg-legend {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 0.82rem;
            color: #475569;
        }

        .cg-body .cg-legend .badge {
            width: 26px;
        }

        .cg-body .cg-p {
            font-size: 0.86rem;
            margin-bottom: 6px;
        }

        .cg-body .cg-list {
            font-size: 0.86rem;
            padding-left: 20px;
        }

        .cg-body .cg-list li {
            margin-bottom: 4px;
        }

        .cg-body .cg-note {
            color: #64748b;
            font-style: italic;
        }
    </style>
@endonce
