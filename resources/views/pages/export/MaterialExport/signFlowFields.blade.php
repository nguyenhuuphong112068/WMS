{{--
    Khối QUY TRÌNH KÝ DUYỆT của một đề nghị cấp phát vật tư - CHỈ ĐỌC.

    Quy trình không do người lập phiếu khai mà suy từ dữ liệu gốc "Trình Ký Đề Nghị CP Vật
    Tư": mỗi dòng vật tư khớp một quy trình theo phân loại của danh mục chung + phân loại
    của phòng, phiếu lấy quy trình nhiều bước nhất trong các dòng. JS ở requestModal dựng
    lại khối này mỗi khi người lập thêm / bớt / đổi dòng vật tư (xem meRenderSignFlow).
--}}

<div class="me-flow-box" data-sign-flow>
    <div class="me-flow-head">
        <div class="me-flow-title-wrap">
            <span class="me-flow-icon"><i class="fas fa-tasks"></i></span>
            <span class="me-flow-label">Quy trình ký duyệt</span>
            <span class="me-flow-count"></span>
        </div>
        <span class="me-flow-hint d-none d-md-inline text-muted ml-auto">
            <i class="fas fa-lock mr-1"></i>Theo Dữ Liệu Gốc &rarr; Trình Ký Đề Nghị CP Vật Tư
        </span>
    </div>

    <div class="me-flow-steps"></div>

    <div class="me-flow-none">
        <i class="fas fa-triangle-exclamation text-warning" style="font-size: 1.2rem; flex-shrink: 0;"></i>
        <div>
            <div style="font-weight: 600; color: #1e293b;">Chưa xác định được quy trình ký</div>
            <div class="me-flow-none-detail" style="font-size: 0.78rem; color: #64748b; margin-top: 1px;"></div>
        </div>
    </div>
</div>
