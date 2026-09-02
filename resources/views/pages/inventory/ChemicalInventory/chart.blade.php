{{--
|--------------------------------------------------------------------------
| TỒN - BIỂU ĐỒ NHẬP - XUẤT - TỒN CỦA MỘT HOÁ CHẤT
|--------------------------------------------------------------------------
| Mở bằng nút biểu đồ ở cột cuối bảng "Tồn Cộng Dồn Theo Hoá Chất".
|
| Modal không mang sẵn số liệu: mỗi lần mở, JS gọi
| pages.inventory.chemicalInventory.chart với category_id của dòng vừa bấm và
| ĐÚNG kỳ báo cáo đang xem (data-from / data-to bên dưới), rồi vẽ bằng Chart.js
| đã nạp sẵn ở layout.js. Nhờ vậy số trong biểu đồ luôn khớp số trên bảng, và
| trang không phải tải trước dữ liệu của mọi hoá chất.
|
| Phần JS vẽ biểu đồ nằm ở pages.inventory.shared.assets và dùng chung với màn
| hình Tồn Kho Vật Tư, nên modal này nhận diện bằng CLASS chứ không bằng id:
| - Modal        : class="inv-chart-modal", data-url / data-from / data-to
|                  data-cancel-label = nhãn của cột trừ tồn thứ hai ("Huỷ" ở đây,
|                  màn hình vật tư gọi là "Loại bỏ")
| - Khung vẽ     : class="inv-chart-canvas"
| - Nút mở modal : class="btn-inv-chart" kèm data-category="<category_id>"
--}}

<div class="modal fade md-modal inv-chart-modal" id="chemChartModal" tabindex="-1" role="dialog" data-cancel-label="Huỷ"
    data-url="{{ route('pages.inventory.chemicalInventory.chart') }}" data-from="{{ $period['from'] }}"
    data-to="{{ $period['to'] }}">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 92vw;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-chart-bar"></i> Biểu Đồ Nhập - Xuất - Tồn</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">

                <div class="inv-hist-head">
                    <div>
                        <label>Hoá Chất</label>
                        <div class="inv-chart-chem">—</div>
                    </div>
                    <div>
                        <label>Mã Hoá Chất</label>
                        <div class="inv-code inv-chart-code">—</div>
                    </div>
                    <div>
                        <label>Kỳ Báo Cáo</label>
                        <div class="inv-chart-period">—</div>
                    </div>
                    <div>
                        <label>Mốc Thời Gian</label>
                        <div class="inv-chart-bucket">—</div>
                    </div>
                    <div>
                        <label>Đơn Vị Tính</label>
                        <div class="inv-chart-unit">—</div>
                    </div>
                </div>

                {{-- Cùng một hàng số liệu với bảng cộng dồn, để đối chiếu ngay không phải đóng modal --}}
                <div class="inv-chart-stats">
                    <div class="inv-chart-stat">
                        <label>Tồn Đầu Kỳ</label>
                        <b class="inv-chart-opening">—</b>
                    </div>
                    <div class="inv-chart-stat is-in">
                        <label>Nhập Trong Kỳ</label>
                        <b class="inv-chart-imported">—</b>
                    </div>
                    <div class="inv-chart-stat is-balanced">
                        <label>Cân Đối Trong Kỳ</label>
                        <b class="inv-chart-balanced">—</b>
                    </div>
                    <div class="inv-chart-stat is-used">
                        <label>Sử Dụng Trong Kỳ</label>
                        <b class="inv-chart-used">—</b>
                    </div>
                    <div class="inv-chart-stat is-cancelled">
                        <label>Huỷ Trong Kỳ</label>
                        <b class="inv-chart-cancelled">—</b>
                    </div>
                    <div class="inv-chart-stat is-closing">
                        <label>Tồn Cuối Kỳ</label>
                        <b class="inv-chart-closing">—</b>
                    </div>
                </div>

                <div class="inv-chart-box">
                    <canvas class="inv-chart-canvas"></canvas>

                    {{-- Phủ lên khung vẽ khi đang tải / không có phát sinh / gọi hỏng --}}
                    <div class="inv-chart-state is-loading">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <span>Đang tải số liệu...</span>
                    </div>
                </div>

                <div class="md-hint">
                    <i class="fas fa-info-circle mr-1"></i>
                    Cột là phát sinh trong từng mốc, đường là <b>tồn cuối mốc</b> cộng dồn từ tồn đầu kỳ - đường đi
                    xuống mà cột nhập vắng bóng là dấu hiệu sắp phải đặt hàng. Biểu đồ đọc đúng
                    <b>kỳ báo cáo</b> đang chọn trên màn hình; muốn xem khoảng khác thì đổi kỳ rồi mở lại.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
