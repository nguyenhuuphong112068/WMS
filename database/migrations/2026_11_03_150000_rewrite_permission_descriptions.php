<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Viết lại diễn giải (permissions.description) của mọi quyền cho chi tiết, dễ hiểu: được làm gì, ở màn hình / tab
 * nào, kết quả ra sao. Dòng thứ hai trở đi ("Lưu ý: ...") là điều kiện hoặc giới hạn đi kèm; màn Quyền hiện các dòng
 * này nhạt hơn, màn Nhóm Quyền hiện cả diễn giải khi rê chuột lên tên quyền.
 *
 * Quyền con (permissions.parent_id) không ghi "cần có quyền cha" vào diễn giải: màn Quyền tự hiện từ parent_id.
 * Cột description đổi sang text vì diễn giải mới dài hơn 255 ký tự.
 */
return new class extends Migration
{
    /** [tên quyền => [diễn giải, lưu ý...]] */
    private const DESCRIPTIONS = [
        // ===================== DỮ LIỆU GỐC =====================
        'materData_material_view' => [
            'Quyền nền để xem các màn Dữ Liệu Gốc nhóm Vật Tư: Tên Vật Tư, Phân Loại, Trình Ký Đề Nghị CP Vật Tư. Chỉ xem, không thêm hay sửa được.',
            'Lưu ý: chọn màn nào hiện trên menu bằng các quyền con "Xem <màn hình>" bên dưới; chỉ có quyền này mà không có quyền con thì không thấy màn nào.',
        ],
        'materData_chemical_view' => [
            'Quyền nền để xem các màn Dữ Liệu Gốc nhóm Hoá Chất: Đơn Chất, Nhóm Nguy Hại Bảng B, Hoá Chất. Chỉ xem, không thêm hay sửa được.',
            'Lưu ý: chọn màn nào hiện trên menu bằng các quyền con "Xem <màn hình>" bên dưới; chỉ có quyền này mà không có quyền con thì không thấy màn nào.',
        ],
        'materData_standard_view' => [
            'Quyền nền để xem các màn Dữ Liệu Gốc nhóm Chất Chuẩn: Tên Chuẩn, Chỉ Tiêu Kiểm. Chỉ xem, không thêm hay sửa được.',
            'Lưu ý: chọn màn nào hiện trên menu bằng các quyền con "Xem <màn hình>" bên dưới; chỉ có quyền này mà không có quyền con thì không thấy màn nào.',
        ],
        'materData_common_view' => [
            'Quyền nền để xem các màn Dữ Liệu Gốc dùng chung: Công Ty, Phòng Ban, Tổ, Đơn Vị Tính, Quy Cách Đóng Gói, Điều Kiện Bảo Quản, Nhà Sản Xuất, Nhà Cung Cấp, Trạng Thái, Tên Sản Phẩm, Định Khu, Đối Tượng. Chỉ xem, không thêm hay sửa được.',
            'Lưu ý: chọn màn nào hiện trên menu bằng các quyền con "Xem <màn hình>" bên dưới; chỉ có quyền này mà không có quyền con thì không thấy màn nào.',
        ],
        'materData_material_manage' => [
            'Quyền nền cho các nút Thêm mới, Sửa, Khoá / Mở khoá ở các màn Dữ Liệu Gốc nhóm Vật Tư (Tên Vật Tư, Phân Loại, Trình Ký Đề Nghị CP Vật Tư).',
            'Lưu ý: bật thêm quyền con "Thêm/Sửa/Khoá <màn hình>" cho từng màn được phép làm. Ở màn có bước duyệt, sửa bản ghi đã duyệt sẽ đưa bản ghi về Chờ duyệt.',
        ],
        'materData_material_review' => [
            'Quyền nền cho nút Duyệt / Từ chối bản ghi đang Chờ duyệt ở các màn Dữ Liệu Gốc nhóm Vật Tư có bước duyệt (Tên Vật Tư).',
            'Lưu ý: bật thêm quyền con "Duyệt/Từ Chối <màn hình>". Bản ghi chỉ chọn được ở các màn nghiệp vụ sau khi Đã duyệt.',
        ],
        'materData_chemical_manage' => [
            'Quyền nền cho các nút Thêm mới, Sửa, Khoá / Mở khoá ở các màn Dữ Liệu Gốc nhóm Hoá Chất (Đơn Chất, Nhóm Nguy Hại Bảng B, Hoá Chất).',
            'Lưu ý: bật thêm quyền con "Thêm/Sửa/Khoá <màn hình>" cho từng màn được phép làm. Sửa bản ghi đã duyệt sẽ đưa bản ghi về Chờ duyệt.',
        ],
        'materData_chemical_review' => [
            'Quyền nền cho nút Duyệt / Từ chối bản ghi đang Chờ duyệt ở các màn Dữ Liệu Gốc nhóm Hoá Chất (Đơn Chất, Nhóm Nguy Hại Bảng B, Hoá Chất).',
            'Lưu ý: bật thêm quyền con "Duyệt/Từ Chối <màn hình>". Bản ghi chỉ chọn được ở các màn nghiệp vụ sau khi Đã duyệt.',
        ],
        'materData_standard_manage' => [
            'Quyền nền cho các nút Thêm mới, Sửa, Khoá / Mở khoá ở các màn Dữ Liệu Gốc nhóm Chất Chuẩn (Tên Chuẩn, Chỉ Tiêu Kiểm).',
            'Lưu ý: bật thêm quyền con "Thêm/Sửa/Khoá <màn hình>" cho từng màn được phép làm. Ở màn có bước duyệt, sửa bản ghi đã duyệt sẽ đưa bản ghi về Chờ duyệt.',
        ],
        'materData_standard_review' => [
            'Quyền nền cho nút Duyệt / Từ chối bản ghi đang Chờ duyệt ở các màn Dữ Liệu Gốc nhóm Chất Chuẩn có bước duyệt (Tên Chuẩn).',
            'Lưu ý: bật thêm quyền con "Duyệt/Từ Chối <màn hình>". Bản ghi chỉ chọn được ở các màn nghiệp vụ sau khi Đã duyệt.',
        ],
        'materData_common_manage' => [
            'Quyền nền cho các nút Thêm mới, Sửa, Khoá / Mở khoá ở các màn Dữ Liệu Gốc dùng chung (Công Ty, Phòng Ban, Tổ, Đơn Vị Tính, Quy Cách Đóng Gói, Điều Kiện Bảo Quản, Nhà Sản Xuất, Nhà Cung Cấp, Trạng Thái, Tên Sản Phẩm, Định Khu, Đối Tượng).',
            'Lưu ý: bật thêm quyền con "Thêm/Sửa/Khoá <màn hình>" cho từng màn được phép làm. Ở màn có bước duyệt, sửa bản ghi đã duyệt sẽ đưa bản ghi về Chờ duyệt.',
        ],
        'materData_common_review' => [
            'Quyền nền cho nút Duyệt / Từ chối bản ghi đang Chờ duyệt ở các màn Dữ Liệu Gốc dùng chung có bước duyệt (Đơn Vị Tính, Quy Cách Đóng Gói, Điều Kiện Bảo Quản, Nhà Sản Xuất, Nhà Cung Cấp).',
            'Lưu ý: bật thêm quyền con "Duyệt/Từ Chối <màn hình>". Bản ghi chỉ chọn được ở các màn nghiệp vụ sau khi Đã duyệt.',
        ],
        'materData_transferSign_view' => [
            'Thấy mục Trình Ký Đề Nghị Liên Phòng Ban trên menu Dữ Liệu Gốc và xem các bước ký mà đề nghị chuyển vật tư giữa các phòng ban và đề nghị VPP đến Hành Chánh phải đi qua (khai riêng theo loại đề nghị và công ty). Chỉ xem.',
        ],
        'materData_transferSign_create' => [
            'Thêm bước ký mới ở màn Trình Ký Đề Nghị Liên Phòng Ban: loại đề nghị (VPP đến HC hoặc liên phòng ban khác), công ty của phòng đề nghị, thứ tự bước, vai trò ký và phạm vi phòng ban của người ký.',
            'Lưu ý: công ty chưa khai bước ký nào thì đề nghị đi thẳng tới phòng được đề nghị, không qua bước ký.',
        ],
        'materData_transferSign_update' => [
            'Sửa bước ký đã khai ở màn Trình Ký Đề Nghị Liên Phòng Ban (đổi thứ tự bước, vai trò ký, phạm vi phòng ban...).',
        ],
        'materData_transferSign_deActive' => [
            'Khoá bước ký ở màn Trình Ký Đề Nghị Liên Phòng Ban để ngừng áp dụng, hoặc mở khoá để dùng lại. Bước ký không bị xoá hẳn.',
        ],
        'materData_initialData_upload' => [
            'Thấy mục Khởi Tạo Dữ Liệu trên menu Dữ Liệu Gốc và đính kèm các file Excel khởi tạo dữ liệu mà phòng ban đã điền (dùng khi triển khai hệ thống lần đầu).',
            'Lưu ý: file chỉ được lưu để Admin xem, tải về, làm sạch rồi tự nạp; đính kèm không ghi dữ liệu vào hệ thống. Tải file mẫu chỉ dành cho Admin.',
        ],

        // ----- Dữ Liệu Gốc: Xem từng màn hình -----
        'materData_material_view_materialName' => [
            'Thấy mục Tên Vật Tư trên menu Dữ Liệu Gốc và xem danh sách tên vật tư dùng chung toàn công ty (Danh Mục Vật Tư chọn tên từ đây).',
        ],
        'materData_material_view_departmentClassification' => [
            'Thấy mục Phân Loại trên menu Dữ Liệu Gốc và xem bộ nhóm phân loại vật tư riêng của phòng ban đang chọn (ví dụ Vật tư HPLC, Dụng cụ vi sinh).',
        ],
        'materData_material_view_materialSignFlow' => [
            'Thấy mục Trình Ký Đề Nghị CP Vật Tư trên menu Dữ Liệu Gốc và xem bảng khai vai trò nào, cấp ký mấy, với điều kiện gì phải ký đề nghị cấp phát vật tư nội bộ của phòng đang chọn.',
        ],
        'materData_chemical_view_activeIngredient' => [
            'Thấy mục Đơn Chất trên menu Dữ Liệu Gốc và xem danh mục hoạt chất (tên IUPAC, CAS, công thức) kèm ngưỡng tồn trữ Phụ lục IV Bảng A và phân loại theo NĐ 24/2026.',
        ],
        'materData_chemical_view_mixtureHazardCategory' => [
            'Thấy mục Nhóm Nguy Hại Bảng B trên menu Dữ Liệu Gốc và xem các nhóm nguy hại theo GHS của Phụ lục IV Bảng B cùng ngưỡng tồn trữ của từng nhóm.',
        ],
        'materData_chemical_view_chemName' => [
            'Thấy mục Hoá Chất trên menu Dữ Liệu Gốc và xem danh sách tên hoá chất (đơn chất hoặc hỗn hợp) với hoạt chất thành phần, % khối lượng, nhóm Bảng B và các phân loại khác (hoá chất cấm, hàng hoá đặc biệt).',
        ],
        'materData_standard_view_standardName' => [
            'Thấy mục Tên Chuẩn trên menu Dữ Liệu Gốc và xem danh sách tên chất chuẩn dùng trong kiểm nghiệm (tên, hoạt chất chính, số CAS).',
        ],
        'materData_standard_view_purpose' => [
            'Thấy mục Chỉ Tiêu Kiểm trên menu Dữ Liệu Gốc và xem danh sách chỉ tiêu kiểm nghiệm (ví dụ Định lượng, Tạp chất liên quan), dùng khi đề nghị, sử dụng chất chuẩn và lập gói theo sản phẩm.',
        ],
        'materData_common_view_company' => [
            'Thấy mục Công Ty trên menu Dữ Liệu Gốc và xem danh sách công ty dùng chung phần mềm. Mỗi công ty có bộ phòng ban riêng; ngưỡng PL IV cộng tồn trong phạm vi một công ty.',
        ],
        'materData_common_view_department' => [
            'Thấy mục Phòng Ban trên menu Dữ Liệu Gốc và xem các phòng ban của từng công ty (tên viết tắt, công ty trực thuộc, có phải bộ phận mua hàng không).',
        ],
        'materData_common_view_group' => [
            'Thấy mục Tổ trên menu Dữ Liệu Gốc và xem các tổ bên trong phòng ban. Đang ở phòng ban nghiệp vụ thì chỉ thấy tổ của chính phòng đó; phòng ban chung thấy tổ của mọi phòng trong công ty.',
        ],
        'materData_common_view_unit' => [
            'Thấy mục Đơn Vị Tính trên menu Dữ Liệu Gốc và xem các đơn vị (g, kg, ml, chai, hộp...) cùng nhóm đơn vị và hệ số quy đổi về đơn vị gốc.',
        ],
        'materData_common_view_packagingSpecification' => [
            'Thấy mục Quy Cách Đóng Gói trên menu Dữ Liệu Gốc và xem các quy cách dùng chung (ví dụ Chai thuỷ tinh 500 ml, Hộp 100 cái).',
        ],
        'materData_common_view_storageCondition' => [
            'Thấy mục Điều Kiện Bảo Quản trên menu Dữ Liệu Gốc và xem các điều kiện bảo quản gắn vào danh mục hàng (ví dụ Tủ lạnh 2 - 8 °C, Tránh ánh sáng).',
        ],
        'materData_common_view_chemManufacturer' => [
            'Thấy mục Nhà Sản Xuất trên menu Dữ Liệu Gốc và xem danh sách hãng sản xuất (ví dụ Merck, Sigma-Aldrich) dùng khi khai danh mục và nhập hàng.',
        ],
        'materData_common_view_chemSupplier' => [
            'Thấy mục Nhà Cung Cấp trên menu Dữ Liệu Gốc và xem danh sách đơn vị cung cấp hàng (địa chỉ, mã số thuế, đầu mối liên hệ) dùng trên phiếu nhập.',
        ],
        'materData_common_view_status' => [
            'Thấy mục Trạng Thái trên menu Dữ Liệu Gốc và xem danh sách tên trạng thái dùng chung của hệ thống.',
        ],
        'materData_common_view_productName' => [
            'Thấy mục Tên Sản Phẩm trên menu Dữ Liệu Gốc và xem danh sách sản phẩm của nhà máy, dùng khi đề nghị, sử dụng chất chuẩn / hoá chất và lập gói theo sản phẩm.',
        ],
        'materData_common_view_zone' => [
            'Thấy mục Định Khu trên menu Dữ Liệu Gốc và xem cấu trúc kho của phòng ban đang chọn (Kho/Phòng → Kệ/Tủ → Cột → Tầng → Vị Trí), ở cả chế độ Cấu trúc kho và Danh sách định khu.',
        ],
        'materData_common_view_consumptionObject' => [
            'Thấy mục Đối Tượng trên menu Dữ Liệu Gốc và xem danh sách đối tượng tiêu thụ vật tư (thường là thiết bị) theo từng tần suất, kể cả thiết bị đồng bộ từ phần mềm CAL.',
        ],

        // ----- Dữ Liệu Gốc: Thêm/Sửa/Khoá và Duyệt/Từ chối từng màn hình -----
        'materData_material_manage_materialName' => [
            'Thêm tên vật tư mới, sửa tên, khoá / mở khoá ở màn Tên Vật Tư.',
            'Lưu ý: tên mới hoặc tên vừa sửa ở trạng thái Chờ duyệt, phải được duyệt mới chọn được ở Danh Mục Vật Tư.',
        ],
        'materData_material_review_materialName' => [
            'Duyệt hoặc từ chối tên vật tư đang Chờ duyệt ở màn Tên Vật Tư. Tên chỉ chọn được ở Danh Mục Vật Tư sau khi Đã duyệt.',
        ],
        'materData_material_manage_departmentClassification' => [
            'Thêm, đổi tên, khoá / mở khoá nhóm phân loại vật tư của phòng ban đang chọn ở màn Phân Loại. Không có bước duyệt.',
        ],
        'materData_material_manage_materialSignFlow' => [
            'Thêm, sửa, khoá / mở khoá dòng trình ký ở màn Trình Ký Đề Nghị CP Vật Tư của phòng đang chọn: vai trò nhận trình ký, cấp ký, người ký, nhóm điều kiện.',
            'Lưu ý: bảng này quyết định đề nghị cấp phát vật tư lập sau đó phải qua những bước ký nào; không vai trò nào thoả điều kiện thì đề nghị được duyệt thẳng.',
        ],
        'materData_chemical_manage_activeIngredient' => [
            'Thêm, sửa, khoá / mở khoá hoạt chất ở màn Đơn Chất: tên, CAS, công thức, ngưỡng Bảng A, phân loại NĐ 24/2026, mục gộp hoạt chất.',
            'Lưu ý: hoạt chất mới hoặc vừa sửa ở trạng thái Chờ duyệt, phải được duyệt mới ghép được vào tên hoá chất.',
        ],
        'materData_chemical_review_activeIngredient' => [
            'Duyệt hoặc từ chối hoạt chất đang Chờ duyệt ở màn Đơn Chất. Chỉ hoạt chất Đã duyệt mới ghép được vào tên hoá chất ở màn Hoá Chất.',
        ],
        'materData_chemical_manage_mixtureHazardCategory' => [
            'Thêm, sửa, khoá / mở khoá nhóm nguy hại ở màn Nhóm Nguy Hại Bảng B: phần Bảng B, tên nhóm, ngưỡng tồn trữ.',
            'Lưu ý: ngưỡng của nhóm ảnh hưởng trực tiếp tới kết quả đối chiếu ngưỡng PL IV. Bản ghi vừa sửa phải được duyệt lại.',
        ],
        'materData_chemical_review_mixtureHazardCategory' => [
            'Duyệt hoặc từ chối nhóm nguy hại đang Chờ duyệt ở màn Nhóm Nguy Hại Bảng B. Chỉ nhóm Đã duyệt mới tick được cho hoá chất.',
        ],
        'materData_chemical_manage_chemName' => [
            'Thêm, sửa, khoá / mở khoá tên hoá chất ở màn Hoá Chất: hoạt chất thành phần và % khối lượng, nhóm Bảng B, hỗn hợp có điều kiện, hoá chất cấm, hàng hoá đặc biệt.',
            'Lưu ý: phân loại NĐ 24/2026 của danh mục hoá chất tự suy từ các thông tin này. Bản ghi vừa sửa phải được duyệt lại.',
        ],
        'materData_chemical_review_chemName' => [
            'Duyệt hoặc từ chối tên hoá chất đang Chờ duyệt ở màn Hoá Chất. Chỉ tên Đã duyệt mới chọn được khi khai Danh Mục Hoá Chất.',
        ],
        'materData_standard_manage_standardName' => [
            'Thêm, sửa, khoá / mở khoá tên chất chuẩn ở màn Tên Chuẩn (tên, hoạt chất chính, số CAS).',
            'Lưu ý: tên mới hoặc tên vừa sửa ở trạng thái Chờ duyệt, phải được duyệt mới chọn được ở Danh Mục Chất Chuẩn.',
        ],
        'materData_standard_review_standardName' => [
            'Duyệt hoặc từ chối tên chuẩn đang Chờ duyệt ở màn Tên Chuẩn. Chỉ tên Đã duyệt mới chọn được khi khai Danh Mục Chất Chuẩn.',
        ],
        'materData_standard_manage_purpose' => [
            'Thêm, sửa, khoá / mở khoá chỉ tiêu kiểm nghiệm ở màn Chỉ Tiêu Kiểm. Không có bước duyệt.',
        ],
        'materData_common_manage_company' => [
            'Thêm, sửa, khoá / mở khoá công ty ở màn Công Ty (tên viết tắt, tên đầy đủ; mã CT00001... tự sinh). Không có bước duyệt.',
        ],
        'materData_common_manage_department' => [
            'Thêm, sửa, khoá / mở khoá phòng ban ở màn Phòng Ban: công ty trực thuộc, tên viết tắt, tên đầy đủ, đánh dấu bộ phận mua hàng (Cung Ứng / Hành Chánh).',
            'Lưu ý: phòng được đánh dấu bộ phận mua hàng sẽ nhận và xử lý dự trù, đề nghị VPP của các phòng ban.',
        ],
        'materData_common_manage_group' => [
            'Thêm, sửa, khoá / mở khoá tổ ở màn Tổ. Đang ở phòng ban nghiệp vụ thì chỉ làm được với tổ của chính phòng đó.',
        ],
        'materData_common_manage_unit' => [
            'Thêm, sửa, khoá / mở khoá đơn vị tính ở màn Đơn Vị Tính (ký hiệu, tên, nhóm đơn vị, hệ số quy đổi).',
            'Lưu ý: hệ số quy đổi dùng để cộng tồn và đối chiếu ngưỡng. Bản ghi vừa sửa phải được duyệt lại.',
        ],
        'materData_common_review_unit' => [
            'Duyệt hoặc từ chối đơn vị tính đang Chờ duyệt ở màn Đơn Vị Tính. Chỉ đơn vị Đã duyệt mới chọn được ở các màn nghiệp vụ.',
        ],
        'materData_common_manage_packagingSpecification' => [
            'Thêm, sửa, khoá / mở khoá quy cách đóng gói ở màn Quy Cách Đóng Gói.',
            'Lưu ý: bản ghi mới hoặc vừa sửa phải được duyệt mới chọn được khi khai danh mục và nhập hàng.',
        ],
        'materData_common_review_packagingSpecification' => [
            'Duyệt hoặc từ chối quy cách đóng gói đang Chờ duyệt. Chỉ quy cách Đã duyệt mới chọn được ở các màn nghiệp vụ.',
        ],
        'materData_common_manage_storageCondition' => [
            'Thêm, sửa, khoá / mở khoá điều kiện bảo quản ở màn Điều Kiện Bảo Quản (tên điều kiện, ghi chú).',
            'Lưu ý: bản ghi mới hoặc vừa sửa phải được duyệt mới chọn được khi khai danh mục.',
        ],
        'materData_common_review_storageCondition' => [
            'Duyệt hoặc từ chối điều kiện bảo quản đang Chờ duyệt. Chỉ điều kiện Đã duyệt mới chọn được ở các màn nghiệp vụ.',
        ],
        'materData_common_manage_chemManufacturer' => [
            'Thêm, sửa, khoá / mở khoá nhà sản xuất ở màn Nhà Sản Xuất (tên viết tắt, tên đầy đủ).',
            'Lưu ý: bản ghi mới hoặc vừa sửa phải được duyệt mới chọn được khi khai danh mục và nhập hàng.',
        ],
        'materData_common_review_chemManufacturer' => [
            'Duyệt hoặc từ chối nhà sản xuất đang Chờ duyệt. Chỉ nhà sản xuất Đã duyệt mới chọn được ở các màn nghiệp vụ.',
        ],
        'materData_common_manage_chemSupplier' => [
            'Thêm, sửa, khoá / mở khoá nhà cung cấp ở màn Nhà Cung Cấp (tên, địa chỉ, mã số thuế, ghi chú).',
            'Lưu ý: bản ghi mới hoặc vừa sửa phải được duyệt mới chọn được trên phiếu nhập.',
        ],
        'materData_common_review_chemSupplier' => [
            'Duyệt hoặc từ chối nhà cung cấp đang Chờ duyệt. Chỉ nhà cung cấp Đã duyệt mới chọn được trên phiếu nhập.',
        ],
        'materData_common_manage_status' => [
            'Thêm, sửa, khoá / mở khoá tên trạng thái dùng chung ở màn Trạng Thái.',
            'Lưu ý: đây là dữ liệu hệ thống, chỉ nên đổi khi có hướng dẫn của quản trị viên.',
        ],
        'materData_common_manage_productName' => [
            'Thêm, sửa, khoá / mở khoá tên sản phẩm ở màn Tên Sản Phẩm. Không có bước duyệt.',
        ],
        'materData_common_manage_zone' => [
            'Khai và sửa cấu trúc kho của phòng ban đang chọn ở màn Định Khu: thêm, sửa, khoá kho / kệ / cột / tầng / vị trí, đổi phân loại vị trí (Dự Phòng, Sử Dụng, Biệt Trữ, Chờ Quyết Định, Loại Bỏ/Chờ Huỷ), thu nhỏ hoặc mở rộng lưới ở chế độ Cấu trúc kho.',
            'Lưu ý: không khoá hay thu nhỏ được ô đang chứa lô hàng.',
        ],
        'materData_common_manage_consumptionObject' => [
            'Thêm, sửa, khoá / mở khoá đối tượng tiêu thụ vật tư ở màn Đối Tượng, và bấm Đồng bộ từ phần mềm CAL để lấy danh sách thiết bị, lịch bảo trì của khối B1, B2.',
        ],

        // ===================== DANH MỤC =====================
        'category_material_view' => [
            'Thấy mục Danh Mục › Vật Tư trên menu và mở màn hình.',
            'Lưu ý: từng tab bên trong (Danh mục vật tư công ty, Vật tư của phòng, 3 tab danh sách theo chu kỳ) cần thêm quyền xem tab tương ứng.',
        ],
        'category_material_create' => [
            'Thêm mã vật tư mới vào Danh mục vật tư công ty: chọn tên vật tư và nhà sản xuất, khai thông tin kỹ thuật, Part Number, 4 tiêu chí phân loại, bộ phận mua hàng, thời gian đặt hàng.',
            'Lưu ý: mã M00001... tự sinh. Mã mới ở trạng thái Chờ duyệt, các phòng chỉ dùng được sau khi được duyệt.',
        ],
        'category_material_update' => [
            'Sửa thông tin mã vật tư trong Danh mục vật tư công ty (nút Sửa trên dòng). Mỗi lần sửa được lưu vào lịch sử thay đổi của mã.',
            'Lưu ý: sửa xong mã quay về Chờ duyệt và phải được duyệt lại. Thời gian đặt hàng không sửa ở đây mà dùng quyền Điều Chỉnh Thời Gian Đặt Hàng.',
        ],
        'category_material_deActive' => [
            'Khoá hoặc mở khoá mã vật tư trong Danh mục vật tư công ty (bắt buộc ghi lý do). Mã bị khoá không còn xuất hiện khi chọn vật tư ở các màn nghiệp vụ.',
        ],
        'category_material_approve' => [
            'Duyệt mã vật tư đang Chờ duyệt trong Danh mục vật tư công ty (nhập lại mật khẩu khi duyệt). Mã chờ duyệt hiện ở Trang chủ của người có quyền này.',
            'Lưu ý: chỉ mã Đã duyệt mới được các phòng khai vào Vật tư của phòng và dùng ở Nhập, Đề nghị, Dự trù.',
        ],
        'category_material_dept_manage' => [
            'Thêm, sửa, khoá vật tư ở tab Vật tư của phòng (phòng đang chọn): đơn vị tính của phòng, phân loại của phòng, ngưỡng tồn tối thiểu / tối đa, định khu dự kiến.',
            'Lưu ý: chỉ vật tư đã khai ở tab này mới nhập kho và đề nghị nội bộ được ở phòng. Tồn dưới ngưỡng tối thiểu thì vật tư tự vào Danh sách cần dự trù.',
        ],
        'category_chemical_view' => [
            'Thấy mục Danh Mục › Hoá Chất trên menu và mở màn hình.',
            'Lưu ý: từng tab bên trong (Danh mục hoá chất công ty, Hoá chất của phòng, Danh sách hoá chất theo sản phẩm) cần thêm quyền xem tab tương ứng.',
        ],
        'category_chemical_create' => [
            'Thêm mã hoá chất mới vào Danh mục hoá chất công ty: chọn tên hoá chất và nhà sản xuất, khai loại, tỉ trọng, điều kiện bảo quản, hạn dùng mặc định, thời gian đặt hàng.',
            'Lưu ý: mã mới ở trạng thái Chờ duyệt. Phân loại NĐ 24/2026 tự suy từ hoạt chất thành phần, không khai tay.',
        ],
        'category_chemical_update' => [
            'Sửa thông tin mã hoá chất trong Danh mục hoá chất công ty (mỗi lần sửa lưu lịch sử), và xác nhận "Đã khai báo Cổng thông tin quốc gia" cho mã hoá chất (nhập lại mật khẩu).',
            'Lưu ý: sửa thông tin xong mã quay về Chờ duyệt. Xác nhận khai báo Cổng chỉ làm một lần, không gỡ lại được và không làm mã phải duyệt lại.',
        ],
        'category_chemical_deActive' => [
            'Khoá hoặc mở khoá mã hoá chất trong Danh mục hoá chất công ty (bắt buộc ghi lý do). Mã bị khoá không còn xuất hiện khi chọn hoá chất ở các màn nghiệp vụ.',
        ],
        'category_chemical_approve' => [
            'Duyệt mã hoá chất đang Chờ duyệt trong Danh mục hoá chất công ty (nhập lại mật khẩu khi duyệt). Mã chờ duyệt hiện ở Trang chủ của người có quyền này.',
            'Lưu ý: chỉ mã Đã duyệt mới được các phòng khai vào Hoá chất của phòng và dùng ở Nhập, Sử dụng, Dự trù.',
        ],
        'category_chemical_dept_manage' => [
            'Thêm, sửa, khoá hoá chất ở tab Hoá chất của phòng (phòng đang chọn): đơn vị tính, khối lượng quy đổi cho đơn vị đếm, hạn dùng nội bộ (tháng), ngưỡng tồn tối thiểu / tối đa, định khu.',
            'Lưu ý: chỉ hoá chất đã khai ở đây mới nhập kho được ở phòng. Khi đặt định khu, hệ thống chặn đặt hoá chất tương kỵ cùng kệ / tủ.',
        ],
        'category_standard_view' => [
            'Thấy mục Danh Mục › Chất Chuẩn trên menu và mở màn hình.',
            'Lưu ý: từng tab bên trong (Danh mục chất chuẩn công ty, Chất chuẩn của phòng, Danh sách chất chuẩn theo sản phẩm) cần thêm quyền xem tab tương ứng.',
        ],
        'category_standard_create' => [
            'Thêm mã chất chuẩn mới vào Danh mục chất chuẩn công ty: chọn tên chuẩn và nguồn gốc / nhà sản xuất, khai số CAS, phân nhóm chuẩn (Chuẩn Chính, Chuẩn Tạp, Chuẩn Thứ Cấp...), tỷ trọng, hạn dùng mặc định, điều kiện bảo quản, thời gian đặt hàng.',
            'Lưu ý: mã mới ở trạng thái Chờ duyệt. Chuẩn chưa có phân nhóm thì không nhập kho được.',
        ],
        'category_standard_update' => [
            'Sửa thông tin mã chất chuẩn trong Danh mục chất chuẩn công ty (nút Sửa trên dòng). Mỗi lần sửa được lưu vào lịch sử thay đổi.',
            'Lưu ý: sửa xong mã quay về Chờ duyệt và phải được duyệt lại. Thời gian đặt hàng dùng quyền Điều Chỉnh Thời Gian Đặt Hàng.',
        ],
        'category_standard_deActive' => [
            'Khoá hoặc mở khoá mã chất chuẩn trong Danh mục chất chuẩn công ty (bắt buộc ghi lý do). Mã bị khoá không còn xuất hiện khi chọn chất chuẩn ở các màn nghiệp vụ.',
        ],
        'category_standard_approve' => [
            'Duyệt mã chất chuẩn đang Chờ duyệt trong Danh mục chất chuẩn công ty (nhập lại mật khẩu khi duyệt). Mã chờ duyệt hiện ở Trang chủ của người có quyền này.',
            'Lưu ý: chỉ mã Đã duyệt mới được các phòng khai vào Chất chuẩn của phòng và dùng ở Nhập, Đề nghị, Dự trù.',
        ],
        'category_standard_dept_manage' => [
            'Thêm, sửa, khoá chất chuẩn ở tab Chất chuẩn của phòng (phòng đang chọn): đơn vị tính, hạn dùng nội bộ (tháng), ngưỡng tồn tối thiểu / tối đa, định khu.',
            'Lưu ý: chỉ chất chuẩn đã khai ở đây mới nhập kho được ở phòng.',
        ],
        'category_material_periodic_manage' => [
            'Lập và quản lý 3 loại danh sách vật tư theo chu kỳ ở Danh Mục › Vật Tư (đề nghị nội bộ, đề nghị liên phòng ban, dự trù): thêm, sửa, khoá danh sách, đặt lịch (hằng tuần / tháng / quý hoặc mỗi N ngày, ngày bắt đầu) và tạo phiếu ngay từ danh sách.',
            'Lưu ý: đến hạn, hệ thống tự tạo phiếu ở trạng thái Lưu tạm / Nháp để người lập kiểm tra rồi gửi đi.',
        ],
        'category_material_tab_company' => [
            'Xem tab Danh mục vật tư công ty: toàn bộ mã vật tư dùng chung công ty với phân loại, bộ phận mua hàng, thời gian đặt hàng, trạng thái duyệt và lịch sử thay đổi.',
        ],
        'category_material_tab_department' => [
            'Xem tab Vật tư của phòng: các vật tư phòng đang chọn đã khai, kèm đơn vị tính, phân loại của phòng, ngưỡng tồn, định khu.',
        ],
        'category_material_tab_internal' => [
            'Xem tab DS đề nghị nội bộ theo chu kỳ: các danh sách tự tạo đề nghị cấp phát nội bộ theo lịch (có thể gắn với một Đối tượng).',
        ],
        'category_material_tab_external' => [
            'Xem tab DS đề nghị liên phòng ban theo chu kỳ: các danh sách tự tạo đề nghị gửi phòng ban khác (kể cả VPP đến Hành Chánh) theo lịch.',
        ],
        'category_material_tab_estimate' => [
            'Xem tab DS dự trù theo chu kỳ: các danh sách tự lập phiếu dự trù Nháp theo lịch, cho tháng tạo phiếu hoặc 1 - 3 tháng sau.',
        ],
        'category_chemical_tab_company' => [
            'Xem tab Danh mục hoá chất công ty: mã hoá chất dùng chung với nhà sản xuất, phân loại NĐ 24/2026, cờ đã khai báo Cổng thông tin quốc gia, thời gian đặt hàng, trạng thái duyệt.',
        ],
        'category_chemical_tab_department' => [
            'Xem tab Hoá chất của phòng: các hoá chất phòng đang chọn đã khai, kèm đơn vị, khối lượng quy đổi, hạn dùng nội bộ, ngưỡng tồn, định khu.',
        ],
        'category_standard_tab_company' => [
            'Xem tab Danh mục chất chuẩn công ty: mã chất chuẩn dùng chung với nguồn gốc / nhà sản xuất, phân nhóm chuẩn, hạn dùng mặc định, thời gian đặt hàng, trạng thái duyệt.',
        ],
        'category_standard_tab_department' => [
            'Xem tab Chất chuẩn của phòng: các chất chuẩn phòng đang chọn đã khai, kèm đơn vị, hạn dùng nội bộ, ngưỡng tồn, định khu.',
        ],
        'category_material_lead_time' => [
            'Sửa Thời gian đặt hàng (số ngày từ lúc đặt tới lúc hàng về) của mã vật tư do Cung Ứng mua trong Danh mục vật tư công ty, bằng nút ✎ ở cột Thời Gian Đặt Hàng. Không làm mã phải duyệt lại.',
            'Lưu ý: khi có mã mới chưa khai số ngày, người có quyền này nhận thẻ nhắc ở Trang chủ.',
        ],
        'category_chemical_lead_time' => [
            'Sửa Thời gian đặt hàng (số ngày từ lúc đặt tới lúc hàng về) của mã hoá chất trong Danh mục hoá chất công ty, bằng nút ✎ ở cột Thời Gian Đặt Hàng. Không làm mã phải duyệt lại.',
            'Lưu ý: khi có mã mới chưa khai số ngày, người có quyền này nhận thẻ nhắc ở Trang chủ.',
        ],
        'category_standard_lead_time' => [
            'Sửa Thời gian đặt hàng (số ngày từ lúc đặt tới lúc hàng về) của mã chất chuẩn trong Danh mục chất chuẩn công ty, bằng nút ✎ ở cột Thời Gian Đặt Hàng. Không làm mã phải duyệt lại.',
            'Lưu ý: khi có mã mới chưa khai số ngày, người có quyền này nhận thẻ nhắc ở Trang chủ.',
        ],
        'category_chemical_tab_product' => [
            'Xem tab Danh sách hoá chất theo sản phẩm: các gói Sản phẩm + Chỉ tiêu và hoá chất cần dùng của phòng (dùng ở Cách 3 khi ghi phiếu Sử Dụng Hoá Chất).',
        ],
        'category_chemical_product_manage' => [
            'Thêm, sửa, khoá gói Sản phẩm + Chỉ tiêu và danh sách hoá chất cần dùng (kèm lượng dùng gợi ý) của phòng đang chọn, ở tab Danh sách hoá chất theo sản phẩm.',
            'Lưu ý: gói chỉ gồm hoá chất phòng đã khai ở tab Hoá chất của phòng; mỗi cặp Sản phẩm + Chỉ tiêu chỉ có một gói đang dùng.',
        ],
        'category_standard_tab_product' => [
            'Xem tab Danh sách chất chuẩn theo sản phẩm: các gói Sản phẩm + Chỉ tiêu và chất chuẩn cần dùng của phòng (dùng khi lập đề nghị cấp phát chuẩn nội bộ).',
        ],
        'category_standard_product_manage' => [
            'Thêm, sửa, khoá gói Sản phẩm + Chỉ tiêu và danh sách chất chuẩn cần dùng của phòng đang chọn, ở tab Danh sách chất chuẩn theo sản phẩm.',
        ],

        // ===================== NHẬP =====================
        'import_material_view' => [
            'Thấy mục Nhập › Vật Tư trên menu và mở màn hình.',
            'Lưu ý: tab Sổ nhập vật tư và Chờ kiểm tra cần thêm quyền xem tab tương ứng.',
        ],
        'import_material_create' => [
            'Lập phiếu nhập kho vật tư cho phòng đang chọn: chọn vật tư của phòng, số lượng, số lần nhập (mỗi lô một mã xuất nhập, tối đa 50), thông tin chứng từ, định khu tạm trong khu Biệt Trữ, file đính kèm.',
            'Lưu ý: lô mới ở trạng thái Chờ kiểm tra, chưa cộng vào tồn cho tới khi kiểm tra Đạt.',
        ],
        'import_material_update' => [
            'Điều chỉnh thông tin phiếu nhập vật tư (mỗi lần sửa lưu lịch sử) và Xác nhận kiểm tra lô ở tab Chờ kiểm tra: Đạt thì lô vào tồn và chuyển về định khu thật; Không đạt thì trả hàng, lô không vào tồn.',
            'Lưu ý: kết quả kiểm tra là quyết định cuối, không kiểm tra lại được.',
        ],
        'import_chemical_view' => [
            'Thấy mục Nhập › Hoá Chất trên menu và mở màn hình.',
            'Lưu ý: tab Nhập hoá chất, Sổ nhập hoá chất, Báo cáo nhập hoá chất cần thêm quyền xem tab tương ứng.',
        ],
        'import_chemical_create' => [
            'Lập phiếu nhập kho hoá chất cho phòng đang chọn: chọn hoá chất của phòng, số lượng, số lần nhập (mỗi lô một mã, tối đa 50), số lô, nhà cung cấp, hạn sử dụng, số / ngày hoá đơn, định khu tạm trong khu Biệt Trữ, file đính kèm.',
            'Lưu ý: hoá chất thuộc diện ngưỡng PL IV được báo trước tồn trữ toàn công ty sau khi nhập. Lô mới ở trạng thái Chờ kiểm tra, chưa vào tồn.',
        ],
        'import_chemical_update' => [
            'Điều chỉnh thông tin phiếu nhập hoá chất (mỗi lần sửa lưu lịch sử) và Xác nhận kiểm tra lô đang chờ ở tab Nhập hoá chất: Đạt thì lô vào tồn và chuyển về định khu thật; Không đạt thì trả hàng.',
            'Lưu ý: lô chưa kiểm tra chỉ đặt được trong khu Biệt Trữ.',
        ],
        'import_standard_view' => [
            'Thấy mục Nhập › Chất Chuẩn trên menu và mở màn hình.',
            'Lưu ý: tab Nhập chất chuẩn và Sổ nhập chất chuẩn cần thêm quyền xem tab tương ứng.',
        ],
        'import_standard_create' => [
            'Nhập ống chuẩn cho phòng đang chọn: chọn chất chuẩn của phòng, lượng / ống, số lần nhập (mỗi ống một mã ống chuẩn, tối đa 50), thông tin lô, loại hạn dùng, chỉ tiêu kiểm, CoA đính kèm.',
            'Lưu ý: ống mới ở trạng thái Chờ kiểm tra trong khu Biệt Trữ, chưa vào tồn.',
        ],
        'import_standard_update' => [
            'Điều chỉnh thông tin phiếu nhập ống chuẩn (mỗi lần sửa lưu lịch sử) và Xác nhận kiểm tra ống đang chờ ở tab Nhập chất chuẩn: Đạt thì ống vào tồn và chuyển về định khu thật.',
            'Lưu ý: sổ nhập chất chuẩn không khoá phiếu; ống hỏng đưa qua Cách ly ở Tồn Kho Chất Chuẩn.',
        ],
        'import_material_label' => [
            'In nhãn lô vật tư (có mã QR, mục đích sử dụng) từ nút In nhãn trên Sổ nhập vật tư, để dán lên hàng.',
        ],
        'import_chemical_label' => [
            'In nhãn lô hoá chất 60 × 40 mm cho máy in Zebra (mã QR, cảnh báo an toàn, số biểu mẫu) từ nút In nhãn trên Sổ nhập hoá chất. Mỗi lần in được ghi vào Audit Trail.',
        ],
        'import_chemical_attachment_status' => [
            'Đổi trạng thái file đính kèm của phiếu nhập hoá chất giữa Đang sử dụng và Ngưng sử dụng (ví dụ CoA cũ đã thay). File không bị xoá, vẫn xem lại được.',
        ],
        'import_material_attachment_status' => [
            'Đổi trạng thái file đính kèm của phiếu nhập vật tư giữa Đang sử dụng và Ngưng sử dụng. File không bị xoá, vẫn xem lại được.',
        ],
        'import_standard_attachment_status' => [
            'Đổi trạng thái file đính kèm của phiếu nhập chất chuẩn giữa Đang sử dụng và Ngưng sử dụng (ví dụ CoA cũ đã thay). File không bị xoá, vẫn xem lại được.',
        ],
        'import_chemical_attachment_upload' => [
            'Tải thêm file hồ sơ (CoA, hoá đơn, MSDS...) vào phiếu nhập hoá chất đã lập, tối đa 10MB mỗi file.',
        ],
        'import_material_attachment_upload' => [
            'Tải thêm file hồ sơ (hoá đơn, phiếu giao hàng, chứng nhận...) vào phiếu nhập vật tư đã lập.',
        ],
        'import_standard_attachment_upload' => [
            'Tải thêm file hồ sơ (CoA, chứng nhận...) vào phiếu nhập chất chuẩn đã lập.',
        ],
        'import_material_tab_book' => [
            'Xem tab Sổ nhập vật tư: mọi lô vật tư đã nhập của phòng với tình trạng kiểm tra; nơi lập phiếu nhập, điều chỉnh, in nhãn, xem file đính kèm.',
        ],
        'import_material_tab_check' => [
            'Xem tab Chờ kiểm tra: các lô vật tư vừa nhập, đang nằm trong khu Biệt Trữ chờ xác nhận kiểm tra.',
        ],
        'import_chemical_tab_book' => [
            'Xem tab Sổ nhập hoá chất: toàn bộ lô đã nhập của phòng (kể cả lô nhận chuyển từ phòng khác) với tình trạng kiểm tra, nút điều chỉnh, in nhãn, file đính kèm.',
        ],
        'import_chemical_tab_report' => [
            'Xem tab Báo cáo nhập hoá chất: tổng lượng nhập theo mã danh mục trong khoảng thời gian (quy ra kg), tách hoá chất mua và hoá chất nhận chuyển.',
        ],
        'import_chemical_tab_check' => [
            'Xem tab Nhập hoá chất: nơi lập phiếu nhập và kiểm tra các lô đang Chờ kiểm tra.',
        ],
        'import_standard_tab_book' => [
            'Xem tab Sổ nhập chất chuẩn: toàn bộ phiếu nhập ống chuẩn của phòng, lọc theo ngày nhập, từ khoá, mã vạch, phân nhóm chuẩn.',
        ],
        'import_standard_tab_check' => [
            'Xem tab Nhập chất chuẩn: nơi nhập ống chuẩn và kiểm tra các ống đang Chờ kiểm tra.',
        ],

        // ===================== ĐỀ NGHỊ - SỬ DỤNG =====================
        'export_material_view' => [
            'Thấy mục Đề Nghị - Sử Dụng › Vật Tư trên menu và mở màn hình; đồng thời thấy Thống Kê › Vật Tư (lượng vật tư đã cấp theo từng đối tượng).',
            'Lưu ý: từng tab bên trong cần thêm quyền xem tab tương ứng.',
        ],
        'export_material_request' => [
            'Lập, sửa và gửi đề nghị cấp phát vật tư: đề nghị nội bộ (Thường quy, Theo ĐG rủi ro), đề nghị liên phòng ban xin vật tư từ phòng khác và đề nghị VPP đến Hành Chánh; sửa và gửi lại phiếu đang Lưu tạm hoặc Bị từ chối.',
            'Lưu ý: gồm cả gửi đề nghị VPP từ danh sách vật tư cần đề nghị Hành Chính. Đề nghị nội bộ gửi đi sẽ qua các bước ký theo Trình Ký Đề Nghị CP Vật Tư.',
        ],
        'export_material_approve' => [
            'Ký đồng ý hoặc từ chối đề nghị cấp phát vật tư nội bộ khi tới bước ký của mình (nhập lại mật khẩu). Nút ký nằm trong chi tiết phiếu; đề nghị chờ ký hiện ở Trang chủ.',
            'Lưu ý: chỉ có quyền là chưa đủ, người ký từng bước được xác định theo Dữ Liệu Gốc › Trình Ký Đề Nghị CP Vật Tư (vai trò, cấp ký, điều kiện).',
        ],
        'export_material_issue' => [
            'Cấp phát (xuất kho) các dòng đề nghị đã duyệt ở tab Chờ cấp phát: kiểm tra lô và số lượng gợi ý theo FEFO/FIFO rồi bấm Cấp Phát để trừ tồn và tự ghi Sổ cấp phát; in phiếu soạn hàng. Gồm cả gửi / huỷ Đề nghị hiệu chuẩn với vật tư cần hiệu chuẩn trước khi dùng.',
            'Lưu ý: đổi lô hoặc đổi số lượng khác đề xuất cần thêm quyền Điều Chỉnh Mã Xuất Nhập / Điều Chỉnh Số Lượng Khi Cấp Phát Vật Tư.',
        ],
        'export_material_transfer' => [
            'Phòng đang giữ hàng cấp phát đề nghị liên phòng ban và đề nghị VPP do phòng khác gửi tới: chọn mã xuất nhập (lô) của phòng mình và số lượng rồi Cấp phát (trừ tồn ngay), hoặc Từ chối cấp từng mục.',
        ],
        'export_material_transfer_receive' => [
            'Phòng đề nghị nhận vật tư mà phòng khác đã cấp: bấm Nhận, ghi kết luận kiểm tra và chọn định khu thật (bắt buộc). Lô vào tồn của phòng nhận ngay, mã lô giữ mã gốc kèm hậu tố -CK1, -CK2... để truy vết.',
        ],
        'export_material_transfer_return' => [
            'Phòng đề nghị từ chối nhận vật tư đã được cấp (ví dụ hàng không đạt khi kiểm tra). Số lượng tự hoàn lại tồn cho phòng đã cấp.',
        ],
        'export_material_quarantine' => [
            'Bước 1 và 3 của quy trình vật tư hỏng ở tab Cách ly - Loại bỏ - Huỷ: lập phiếu Cách ly (chọn mã xuất nhập, số lượng, định khu Chờ Quyết Định, lý do; hàng bị trừ tồn ngay) và ghi nhận Huỷ khi đã tiêu huỷ thực tế (ghi phương pháp, nhập lại mật khẩu).',
        ],
        'export_material_quarantine_decide' => [
            'Bước 2 của quy trình vật tư hỏng: với vật tư đang cách ly, quyết định Loại bỏ (chuyển sang định khu Loại Bỏ/Chờ Huỷ, không quay lại kho được) hoặc Trả về kho (cộng lại tồn); ghi căn cứ và nhập lại mật khẩu.',
            'Lưu ý: phiếu cách ly chờ quyết định hiện ở Trang chủ của người có quyền này.',
        ],
        'export_material_issue_lot_adjust' => [
            'Khi cấp phát vật tư, được chọn mã xuất nhập (lô) khác với lô hệ thống đề xuất theo FEFO/FIFO, hoặc thêm lô khác.',
            'Lưu ý: không có quyền này thì chỉ cấp được từ đúng các lô hệ thống đề xuất.',
        ],
        'export_material_update' => [
            'Sửa hoặc khoá một dòng ghi sai ở tab Sổ cấp phát (số lượng, mục đích, ghi chú). Mỗi lần điều chỉnh được lưu lịch sử.',
            'Lưu ý: dòng sinh từ phiếu cách ly chỉ xử lý ở tab Cách ly - Loại bỏ - Huỷ; dòng cấp phát liên phòng ban không sửa được.',
        ],
        'export_material_issue_amount_adjust' => [
            'Khi cấp phát vật tư, được nhập số lượng cấp khác số hệ thống đề xuất (cấp một phần, cấp khác số đề nghị).',
            'Lưu ý: không có quyền này thì phải cấp đúng số đề xuất. Mỗi lô vẫn chỉ được cấp vượt tối đa 5%.',
        ],
        'export_material_calibration_confirm' => [
            'QA xác nhận Đạt / Không đạt cho từng đơn vị vật tư được đề nghị hiệu chuẩn trước khi cấp phát, ở tab Xác nhận hiệu chuẩn (nhập lại mật khẩu). Phần Không đạt tự chuyển sang Cách ly; chỉ phần Đạt mới cấp phát được.',
            'Lưu ý: chỉ người ở phòng QA cùng công ty với phòng đề nghị mới xác nhận được.',
        ],
        'export_material_tab_periodic' => [
            'Xem tab Đề nghị nội bộ - Theo định kỳ: các đề nghị cấp phát nội bộ hệ thống tự tạo từ danh sách nội bộ theo chu kỳ (không lập tay ở tab này).',
        ],
        'export_material_tab_risk_assessment' => [
            'Xem tab Đề nghị nội bộ - Theo ĐG rủi ro: các đề nghị cấp phát nội bộ lập tay theo kết quả đánh giá rủi ro.',
        ],
        'export_material_tab_regular' => [
            'Xem tab Đề nghị nội bộ - Thường quy: các đề nghị cấp phát nội bộ thường ngày, lập tay.',
        ],
        'export_material_tab_vpp' => [
            'Xem tab Đề nghị liên phòng ban - VPP đến HC: các đề nghị xin văn phòng phẩm từ kho của Hành Chánh và danh sách vật tư cần đề nghị Hành Chính.',
        ],
        'export_material_tab_transfer' => [
            'Xem tab Đề nghị liên phòng ban - Phòng ban khác: đề nghị phòng mình gửi đi xin vật tư và đề nghị phòng khác gửi tới phòng mình.',
        ],
        'export_material_tab_prepare' => [
            'Xem tab Chờ cấp phát: các dòng đề nghị đã duyệt (nội bộ, liên phòng ban và VPP gửi tới phòng mình) còn phải cấp, kèm tồn khả dụng và lô gợi ý.',
        ],
        'export_material_tab_book' => [
            'Xem tab Sổ cấp phát: nhật ký mọi lần vật tư rời kho của phòng (sử dụng, loại bỏ, cấp phát liên phòng ban).',
        ],
        'export_material_tab_quarantine' => [
            'Xem tab Cách ly - Loại bỏ - Huỷ: các phiếu cách ly vật tư hỏng theo từng bước (chờ quyết định, loại bỏ - chờ huỷ, đã huỷ, trả về kho).',
        ],
        'export_material_tab_late' => [
            'Xem tab Ghi nhận cấp phát trễ: báo cáo theo tháng các dòng đề nghị không cấp kịp ngày mong muốn và nguyên nhân (duyệt trễ, thiếu tồn, cấp phát trễ).',
        ],
        'export_material_tab_calibration' => [
            'Xem tab Xác nhận hiệu chuẩn: các đề nghị hiệu chuẩn vật tư đang chờ QA xác nhận và kết quả đã xác nhận.',
        ],
        'export_chemical_view' => [
            'Thấy mục Đề Nghị - Sử Dụng › Hoá Chất trên menu và mở màn hình.',
            'Lưu ý: từng tab bên trong cần thêm quyền xem tab tương ứng.',
        ],
        'export_chemical_request' => [
            'Lập đề nghị xin hoá chất từ kho của phòng khác (chọn phòng được đề nghị, hoá chất, số lượng, ngày mong muốn), sửa hoặc huỷ đề nghị khi còn ở trạng thái lưu.',
            'Lưu ý: đề nghị chuyển hoá chất liên phòng ban không có bước ký duyệt.',
        ],
        'export_chemical_issue' => [
            'Ghi phiếu Sử dụng hoá chất ở tab Sử Dụng Hoá Chất (quét mã, chọn từ tồn kho hoặc theo gói sản phẩm - chỉ tiêu): lưu là trừ tồn ngay, không cần ký duyệt. Được sửa và khoá / mở khoá dòng ở Sổ sử dụng hoá chất.',
            'Lưu ý: dùng hoá chất cấm bắt buộc chọn Người Kiểm Tra là người có vai trò Quản Lý Kho Hoá Chất của phòng.',
        ],
        'export_chemical_transfer' => [
            'Phòng đang giữ hàng cấp phát đề nghị chuyển hoá chất do phòng khác gửi tới: chọn lô của phòng mình và số lượng rồi Cấp phát (trừ tồn ngay), hoặc Từ chối cấp từng mục.',
            'Lưu ý: lô chưa xác định hạn dùng nội bộ chỉ cấp được nguyên cả lô.',
        ],
        'export_chemical_transfer_receive' => [
            'Phòng đề nghị nhận hoá chất mà phòng khác đã cấp: bấm Nhận, ghi kết luận kiểm tra và chọn định khu thật. Lô vào tồn của phòng nhận ngay, mã lô giữ mã gốc kèm hậu tố -CK1, -CK2...',
            'Lưu ý: hoá chất phải được khai ở Hoá chất của phòng nhận trước khi bấm Nhận.',
        ],
        'export_chemical_transfer_return' => [
            'Phòng đề nghị từ chối nhận hoá chất đã được cấp (ví dụ hàng không đạt khi kiểm tra). Số lượng tự hoàn lại tồn cho phòng đã cấp.',
        ],
        'export_chemical_quarantine' => [
            'Bước 1 và 3 của quy trình hoá chất hỏng: bấm Cách ly trên lô ở Tồn Kho Hoá Chất (số lượng, định khu Chờ Quyết Định, lý do; hàng bị trừ tồn ngay) và ghi nhận Huỷ ở tab Cách ly - Loại bỏ - Huỷ khi đã tiêu huỷ thực tế (nhập lại mật khẩu).',
        ],
        'export_chemical_quarantine_decide' => [
            'Bước 2 của quy trình hoá chất hỏng: với hoá chất đang cách ly, quyết định Loại bỏ (chuyển sang định khu Loại Bỏ/Chờ Huỷ, không quay lại kho được) hoặc Trả về kho (cộng lại tồn); ghi căn cứ và nhập lại mật khẩu.',
            'Lưu ý: phiếu cách ly chờ quyết định hiện ở Trang chủ của người có quyền này.',
        ],
        'export_chemical_tab_book' => [
            'Xem tab Sổ sử dụng hoá chất: nhật ký mọi lần hoá chất rời kho của phòng (sử dụng, loại bỏ, chuyển cho phòng khác) với người sử dụng, người kiểm tra, mục đích.',
        ],
        'export_chemical_tab_draft' => [
            'Xem tab Sử Dụng Hoá Chất: nơi ghi phiếu sử dụng và các dòng đang nằm ở Phiếu Tạm (đã chọn nhưng chưa ghi thật).',
        ],
        'export_chemical_tab_banned' => [
            'Xem tab Hoá chất cấm: sổ theo dõi riêng từng lần nhập, xuất và tồn cộng dồn của hoá chất cấm (nhóm 11). Chỉ để xem, tra cứu và in.',
        ],
        'export_chemical_tab_request' => [
            'Xem tab Đề nghị chuyển liên phòng ban: đề nghị xin hoá chất phòng mình gửi đi và đề nghị phòng khác gửi tới phòng mình.',
        ],
        'export_chemical_tab_report' => [
            'Xem tab Báo cáo theo khoảng thời gian: tổng lượng hoá chất đã sử dụng theo mã danh mục, số lượt xuất và số lượng quy đổi ra kg.',
        ],
        'export_chemical_tab_quarantine' => [
            'Xem tab Cách ly - Loại bỏ - Huỷ: các phiếu cách ly hoá chất hỏng theo từng bước.',
        ],
        'export_standard_view' => [
            'Thấy mục Đề Nghị - Sử Dụng › Chất Chuẩn trên menu và mở màn hình.',
            'Lưu ý: từng tab bên trong cần thêm quyền xem tab tương ứng.',
        ],
        'export_standard_request' => [
            'Lập, sửa và gửi đề nghị cấp phát chuẩn nội bộ (tổ xin ống chuẩn từ kho của phòng) và đề nghị cấp phát chuẩn liên phòng ban (xin từ kho của phòng khác).',
            'Lưu ý: đề nghị chất chuẩn không có bước ký duyệt. Người trong tổ chỉ thấy và dùng được ống đã cấp cho tổ mình.',
        ],
        'export_standard_issue' => [
            'Kho cấp phát ống chuẩn cho tổ theo đề nghị nội bộ; cấp phát (hoặc từ chối) đề nghị liên phòng ban do phòng khác gửi tới; ghi phiếu Sử dụng cho ống đã cấp ở tab Sử dụng chuẩn.',
            'Lưu ý: cấp phát trừ tồn của phòng ngay khi lưu.',
        ],
        'export_standard_transfer_receive' => [
            'Phòng đề nghị nhận chất chuẩn mà phòng khác đã cấp: bấm Nhận, ghi kết luận kiểm tra, chọn định khu thật, có thể đánh dấu chiết ống trước khi dùng. Ống vào tồn của phòng nhận ngay.',
            'Lưu ý: chất chuẩn phải được khai ở Chất chuẩn của phòng nhận trước khi bấm Nhận.',
        ],
        'export_standard_transfer_return' => [
            'Phòng đề nghị từ chối nhận chất chuẩn đã được cấp (ví dụ không đạt khi kiểm tra). Số lượng tự hoàn lại tồn cho phòng đã cấp.',
        ],
        'export_standard_quarantine' => [
            'Bước 1 và 3 của quy trình ống chuẩn hỏng: bấm Cách ly trên ống ở Tồn Kho Chất Chuẩn (số lượng, định khu Chờ Quyết Định, lý do; trừ tồn ngay), sửa phiếu đang chờ quyết định, và ghi nhận Huỷ khi đã tiêu huỷ thực tế (nhập lại mật khẩu).',
        ],
        'export_standard_quarantine_decide' => [
            'Bước 2 của quy trình ống chuẩn hỏng: với ống đang cách ly, quyết định Loại bỏ (chuyển sang định khu Loại Bỏ/Chờ Huỷ, không quay lại kho được) hoặc Trả về kho (cộng lại tồn, chọn vị trí đặt lại); ghi căn cứ và nhập lại mật khẩu.',
            'Lưu ý: phiếu cách ly chờ quyết định hiện ở Trang chủ của người có quyền này.',
        ],
        'export_standard_tab_book' => [
            'Xem tab Sổ sử dụng chất chuẩn: mọi phiếu sử dụng chuẩn của phòng (kèm dòng Loại bỏ sinh từ phiếu cách ly), lọc theo ngày, mã ống, tên chuẩn, số lô, chỉ tiêu.',
        ],
        'export_standard_tab_request' => [
            'Xem tab Đề nghị cấp phát chuẩn nội bộ: đề nghị của các tổ, chia 3 tab con Chưa gửi / Chờ xử lý / Đã xử lý.',
        ],
        'export_standard_tab_transfer' => [
            'Xem tab Đề nghị cấp phát chuẩn liên phòng ban: đề nghị phòng mình gửi đi và đề nghị phòng khác gửi tới phòng mình.',
        ],
        'export_standard_tab_quarantine' => [
            'Xem tab Cách ly - Loại bỏ - Huỷ: các phiếu cách ly ống chuẩn hỏng theo từng bước.',
        ],
        'export_standard_tab_use' => [
            'Xem tab Sử dụng chuẩn (tab mở mặc định): các ống kho đã cấp cho tổ mà chưa lập phiếu sử dụng; nơi bấm Sử dụng để ghi phiếu.',
        ],

        // ===================== TỒN KHO =====================
        'inventory_material_view' => [
            'Thấy mục Tồn Kho › Vật Tư trên menu và xem tồn của phòng đang chọn. Tồn tính tự động từ nhập, cân đối, cấp phát; không có chỗ nhập tồn bằng tay.',
            'Lưu ý: từng tab bên trong cần thêm quyền xem tab tương ứng.',
        ],
        'inventory_chemical_view' => [
            'Thấy mục Tồn Kho › Hoá Chất trên menu và xem tồn của phòng đang chọn theo kỳ (tồn đầu kỳ, nhập, cân đối, sử dụng, huỷ, tồn cuối kỳ). Tồn tính tự động, không nhập tay.',
            'Lưu ý: từng tab bên trong cần thêm quyền xem tab tương ứng.',
        ],
        'inventory_standard_view' => [
            'Thấy mục Tồn Kho › Chất Chuẩn trên menu và xem tồn ống chuẩn của phòng đang chọn theo kỳ. Tồn tính tự động, không nhập tay.',
            'Lưu ý: từng tab bên trong (kể cả Đánh giá hạn dùng CTC, Kế hoạch đánh giá) cần thêm quyền xem tab tương ứng.',
        ],
        'stability_standard_view' => [
            'Xem tab Đánh giá hạn dùng CTC ở Tồn Kho › Chất Chuẩn: các phiếu theo dõi độ ổn định của ống chuẩn thứ cấp qua từng mốc (T0, T3, T6...) và trang chi tiết của từng phiếu.',
        ],
        'stability_standard_create' => [
            'Lập phiếu đánh giá hạn dùng cho ống chuẩn thứ cấp (chọn ống, ngày bắt đầu, chu kỳ, các mốc và chỉ tiêu cần thử) và thêm mốc đánh giá vào phiếu đang chạy.',
            'Lưu ý: chỉ ống CTC được tick Lô đánh giá hạn dùng lúc nhập mới lập phiếu được; mỗi ống chỉ có một phiếu còn hiệu lực.',
        ],
        'stability_standard_update' => [
            'Sửa phiếu đánh giá hạn dùng CTC và các mốc chưa có kết quả; cho phiếu đang Dừng đánh giá chạy tiếp.',
        ],
        'stability_plan_view' => [
            'Xem tab Kế hoạch đánh giá ở Tồn Kho › Chất Chuẩn: mọi mốc đánh giá của các phiếu còn hiệu lực xếp theo ngày đến hạn (quá hạn, sắp đến hạn, chưa tới hạn). Chỉ để xem.',
        ],
        'inventory_material_balancing' => [
            'Bấm Cân đối trên lô ở tab Theo mã xuất nhập để điều chỉnh tồn sổ sách cho khớp thực tế (nhập số + hoặc −, kèm lý do), khi có sai số cân, hao hụt hoặc đếm nhầm.',
            'Lưu ý: tổng cân đối của một lô không vượt quá 5% lượng nhập và tồn không được âm.',
        ],
        'inventory_chemical_balancing' => [
            'Bấm Cân đối trên lô ở tab Theo mã xuất nhập để điều chỉnh tồn sổ sách cho khớp thực tế (nhập số + hoặc −, kèm lý do), khi có sai số cân, hao hụt hoặc đếm nhầm.',
            'Lưu ý: tổng cân đối của một lô không vượt quá 5% lượng nhập và tồn không được âm.',
        ],
        'inventory_material_stocktake' => [
            'Kiểm kê kho vật tư theo quý ở tab Kiểm kê định kỳ: mở phiếu (hệ thống chụp tồn sổ sách), nhập số thực đếm (quét QR trên nhãn), chốt phiếu để hệ thống tự cân đối phần lệch; huỷ phiếu đang đếm để mở lại.',
            'Lưu ý: mỗi phòng mỗi quý chỉ mở một phiếu. Dòng lệch vượt giới hạn cân đối được đánh dấu Chờ Xử Lý thay vì tự cân đối.',
        ],
        'inventory_chemical_internalExpiry' => [
            'Xác định hạn dùng nội bộ cho lô hoá chất: chọn ngày mở nắp / đưa vào dùng, hạn = ngày đó + số tháng hạn dùng mặc định (không vượt hạn nhà sản xuất). Xác định lại được nhiều lần, mỗi lần ghi Audit Trail.',
            'Lưu ý: lô chưa xác định hạn nội bộ chỉ chuyển được nguyên cả lô cho phòng khác.',
        ],
        'inventory_standard_internalExpiry' => [
            'Xác định hạn dùng nội bộ cho ống chuẩn: chọn ngày mở ống, hạn = ngày mở + số tháng hạn dùng (không vượt hạn nhà sản xuất).',
            'Lưu ý: ống của chuẩn có hạn dùng mặc định mà chưa xác định hạn nội bộ thì chưa cấp phát và chưa dùng được.',
        ],
        'inventory_standard_weight' => [
            'Ở tab Kiểm soát khối lượng: nhập Khối lượng bì sau khi sử dụng cho ống có kiểm soát khối lượng, và ghi nhận xét cho ống có sai lệch ngoài giới hạn cho phép.',
        ],
        'stability_standard_assess' => [
            'Ghi (hoặc sửa) kết quả đánh giá cho từng mốc của phiếu đánh giá hạn dùng CTC đang chạy, trên trang chi tiết phiếu.',
        ],
        'stability_standard_issue' => [
            'Cấp phát lượng chuẩn để thử từng chỉ tiêu kiểm của một mốc đánh giá hạn dùng CTC (nút trên dòng mốc ở trang chi tiết phiếu).',
        ],
        'stability_standard_delete' => [
            'Huỷ hoặc mở lại phiếu đánh giá hạn dùng CTC; huỷ mốc đánh giá chưa có kết quả (mốc huỷ không tính vào tiến độ nhưng vẫn lưu trong lịch sử thay đổi).',
        ],
        'inventory_standard_expiryUpdate' => [
            'Cập nhật hạn dùng cho ống chuẩn có loại hạn Cần Retest định kỳ hoặc Check online (bấm badge trên bảng tồn) sau mỗi lần retest / tra cứu trực tuyến. Mỗi lần lưu giá trị cũ → mới, ghi chú và file đính kèm.',
        ],
        'inventory_material_tab_code' => [
            'Xem tab Theo mã xuất nhập: tồn từng lô theo kỳ (đầu kỳ, nhập, sử dụng, cách ly / huỷ, cuối kỳ), hạn dùng, vị trí; nơi có nút Cân đối và Biểu đồ.',
        ],
        'inventory_material_tab_material' => [
            'Xem tab Tồn theo mã vật tư: tổng tồn gộp các lô theo vật tư, hạn gần nhất, nhà sản xuất; lọc theo phân loại vật tư.',
        ],
        'inventory_material_tab_zone' => [
            'Xem tab Tồn kho theo vị trí: sơ đồ kho - kệ - vị trí, bấm vào vị trí để xem lô đang để ở đó.',
        ],
        'inventory_material_tab_next' => [
            'Xem tab Khả dụng tháng tới: tồn hiện có có đủ cho nhu cầu theo chu kỳ của tháng tới không, thiếu bao nhiêu, lượng nên dự trù.',
        ],
        'inventory_material_tab_check' => [
            'Xem tab Kiểm kê định kỳ: phiếu kiểm kê theo quý của phòng, số thực đếm và chênh lệch.',
        ],
        'inventory_chemical_tab_code' => [
            'Xem tab Theo mã xuất nhập: tồn từng lô theo kỳ, hạn dùng, hạn nội bộ, vị trí thực tế; nơi có nút Cân đối, Cách ly, Biểu đồ, file đính kèm, xác định hạn nội bộ.',
        ],
        'inventory_chemical_tab_chemical' => [
            'Xem tab Theo hoá chất: tổng tồn gộp các lô theo hoá chất, hạn gần nhất, tồn theo từng số lô.',
        ],
        'inventory_chemical_tab_zone' => [
            'Xem tab Theo định khu: sơ đồ kho - kệ - vị trí, bấm vào vị trí để xem lô đang để ở đó.',
        ],
        'inventory_chemical_tab_expiring' => [
            'Xem tab Cận hạn dùng: các lô có hạn rơi vào 6 tháng tới hoặc đã quá hạn, lọc nhanh theo mốc ≤ 3 tháng / 1 tháng / 2 tuần / 1 tuần.',
        ],
        'inventory_chemical_tab_internal' => [
            'Xem tab Chưa có hạn nội bộ: các lô của hoá chất có hạn dùng mặc định nhưng chưa xác định hạn dùng nội bộ.',
        ],
        'inventory_standard_tab_code' => [
            'Xem tab Theo mã ống chuẩn: mỗi ống một dòng với tồn theo kỳ, hạn dùng; nơi có nút Cách ly và Xác định hạn nội bộ.',
        ],
        'inventory_standard_tab_standard' => [
            'Xem tab Theo chất chuẩn: gộp các ống theo chất chuẩn (số ống còn tồn / tổng số ống), tồn theo kỳ, hạn gần nhất.',
        ],
        'inventory_standard_tab_zone' => [
            'Xem tab Theo định khu: sơ đồ kho - kệ - vị trí và các ống đang để ở mỗi vị trí.',
        ],
        'inventory_standard_tab_expiring' => [
            'Xem tab Cận hạn dùng: các ống có hạn rơi vào 6 tháng tới hoặc đã quá hạn, lọc nhanh theo mốc ≤ 3 tháng / 1 tháng / 2 tuần / 1 tuần.',
        ],
        'inventory_standard_tab_internal' => [
            'Xem tab Chưa có hạn nội bộ: các ống của chuẩn có hạn dùng mặc định nhưng chưa xác định hạn dùng nội bộ.',
        ],
        'inventory_standard_tab_weight' => [
            'Xem tab Kiểm soát khối lượng: so lượng chuẩn thực có trong ống với tổng lượng đã ghi sử dụng để phát hiện hao hụt bất thường.',
        ],
        'inventory_chemical_tab_threshold' => [
            'Xem tab Đối chiếu ngưỡng PL IV: tổng tồn trữ toàn công ty so với ngưỡng Phụ lục IV NĐ 24/2026 (Bảng A theo hoạt chất, Bảng B theo hỗn hợp), để biết cơ sở có phải xây dựng Kế hoạch phòng ngừa, ứng phó sự cố hoá chất không. Chỉ để xem.',
        ],

        // ===================== DỰ TRÙ =====================
        'estimate_material_view' => [
            'Thấy mục Dự Trù › Vật Tư trên menu và mở màn hình (phiếu dự trù đề nghị bộ phận mua hàng mua vật tư).',
            'Lưu ý: từng tab bên trong cần thêm quyền xem tab tương ứng.',
        ],
        'estimate_material_create' => [
            'Lập phiếu dự trù vật tư mới (chọn tháng / năm dự trù, ghi chú), kể cả lập nhanh bằng cách tick chọn ở Danh sách vật tư cần dự trù. Phiếu mới ở trạng thái Nháp.',
        ],
        'estimate_material_update' => [
            'Sửa phiếu dự trù vật tư đang Nháp hoặc Bị từ chối: đổi tháng, ghi chú, thêm / sửa / bỏ mặt hàng, số lượng từng tháng, file đính kèm; rồi bấm Trình ký để gửi đi ký duyệt.',
        ],
        'estimate_material_sign' => [
            'Ký đồng ý hoặc từ chối phiếu dự trù vật tư khi tới bước của mình (nhập lại mật khẩu). Bước 1: Quản Lý Bộ Phận của phòng lập phiếu; bước 2: Ban Giám Đốc.',
            'Lưu ý: người ký còn được xác định theo vai trò và phạm vi phòng ban, không chỉ dựa vào quyền này. Phiếu chờ ký hiện ở Trang chủ.',
        ],
        'estimate_chemical_view' => [
            'Thấy mục Dự Trù › Hoá Chất trên menu và mở màn hình (phiếu dự trù đề nghị Cung Ứng mua hoá chất).',
            'Lưu ý: từng tab bên trong cần thêm quyền xem tab tương ứng.',
        ],
        'estimate_chemical_create' => [
            'Lập phiếu dự trù hoá chất mới (chọn tháng / năm dự trù, ghi chú). Phiếu mới ở trạng thái Nháp.',
        ],
        'estimate_chemical_update' => [
            'Sửa phiếu dự trù hoá chất đang Nháp hoặc Bị từ chối: đổi tháng, ghi chú, thêm / sửa / bỏ mặt hàng, số lượng từng tháng, file đính kèm; rồi bấm Trình ký để gửi đi ký duyệt.',
            'Lưu ý: hoá chất thuộc diện ngưỡng PL IV được báo trước tồn trữ toàn công ty sau khi mua; hoá chất đang nằm trong phiếu dự trù chưa xong thì không thêm lại được.',
        ],
        'estimate_chemical_sign' => [
            'Ký đồng ý hoặc từ chối phiếu dự trù hoá chất khi tới bước của mình (nhập lại mật khẩu). Bước 1: Quản Lý Bộ Phận của phòng lập phiếu; bước 2: Ban Giám Đốc.',
            'Lưu ý: người ký còn được xác định theo vai trò và phạm vi phòng ban, không chỉ dựa vào quyền này. Phiếu chờ ký hiện ở Trang chủ.',
        ],
        'estimate_standard_view' => [
            'Thấy mục Dự Trù › Chất Chuẩn trên menu và mở màn hình (phiếu dự trù đề nghị Cung Ứng mua chất chuẩn).',
            'Lưu ý: từng tab bên trong cần thêm quyền xem tab tương ứng.',
        ],
        'estimate_standard_create' => [
            'Lập phiếu dự trù chất chuẩn mới (chọn tháng / năm dự trù, ghi chú). Phiếu mới ở trạng thái Nháp.',
        ],
        'estimate_standard_update' => [
            'Sửa phiếu dự trù chất chuẩn đang Nháp hoặc Bị từ chối: đổi tháng, ghi chú, thêm / sửa / bỏ mặt hàng, số lượng từng tháng, file đính kèm; rồi bấm Trình ký để gửi đi ký duyệt.',
        ],
        'estimate_standard_sign' => [
            'Ký đồng ý hoặc từ chối phiếu dự trù chất chuẩn khi tới bước của mình (nhập lại mật khẩu). Bước 1: Quản Lý Bộ Phận của phòng lập phiếu; bước 2: Ban Giám Đốc.',
            'Lưu ý: người ký còn được xác định theo vai trò và phạm vi phòng ban, không chỉ dựa vào quyền này. Phiếu chờ ký hiện ở Trang chủ.',
        ],
        'estimate_material_delete' => [
            'Huỷ phiếu dự trù vật tư khi phiếu còn Nháp hoặc Bị từ chối (chưa trình ký).',
        ],
        'estimate_material_tracking' => [
            'Phía phòng đề nghị theo dõi mặt hàng dự trù đã duyệt: đánh dấu Hoàn thành khi đã nhận đủ hàng, đề nghị Huỷ mặt hàng không cần nữa, hoặc hoàn tác.',
            'Lưu ý: huỷ một mặt hàng cần cả bộ phận mua hàng đồng ý.',
        ],
        'estimate_chemical_delete' => [
            'Huỷ phiếu dự trù hoá chất khi phiếu còn Nháp hoặc Bị từ chối (chưa trình ký).',
        ],
        'estimate_chemical_tracking' => [
            'Phía phòng đề nghị theo dõi mặt hàng dự trù đã duyệt: đánh dấu Hoàn thành khi đã nhận đủ hàng, đề nghị Huỷ mặt hàng không cần nữa, hoặc hoàn tác.',
            'Lưu ý: huỷ một mặt hàng cần cả bộ phận mua hàng đồng ý.',
        ],
        'estimate_standard_delete' => [
            'Huỷ phiếu dự trù chất chuẩn khi phiếu còn Nháp hoặc Bị từ chối (chưa trình ký).',
        ],
        'estimate_standard_tracking' => [
            'Phía phòng đề nghị theo dõi mặt hàng dự trù đã duyệt: đánh dấu Hoàn thành khi đã nhận đủ hàng, đề nghị Huỷ mặt hàng không cần nữa, hoặc hoàn tác.',
            'Lưu ý: huỷ một mặt hàng cần cả bộ phận mua hàng đồng ý.',
        ],
        'estimate_material_purchasing' => [
            'Dành cho bộ phận mua hàng: xem tab Đơn dự trù đã được phê duyệt của mọi phòng ban, bấm Tiếp nhận phiếu, cập nhật Ngày hẹn đáp ứng cho từng tháng của từng vật tư, đồng ý hoặc không đồng ý huỷ mặt hàng.',
            'Lưu ý: chỉ có tác dụng khi đang chọn đúng phòng mua hàng (Cung Ứng / Hành Chánh) ở thanh trên cùng. Phiếu chờ tiếp nhận hiện ở Trang chủ.',
        ],
        'estimate_chemical_purchasing' => [
            'Dành cho bộ phận mua hàng: xem tab Đơn dự trù đã được phê duyệt của mọi phòng ban, bấm Tiếp nhận phiếu, cập nhật Ngày hẹn đáp ứng cho từng tháng của từng hoá chất, đồng ý hoặc không đồng ý huỷ mặt hàng.',
            'Lưu ý: hoá chất luôn do Cung Ứng mua; chỉ có tác dụng khi đang chọn phòng Cung Ứng ở thanh trên cùng.',
        ],
        'estimate_standard_purchasing' => [
            'Dành cho bộ phận mua hàng: xem tab Đơn dự trù đã được phê duyệt của mọi phòng ban, bấm Tiếp nhận phiếu, cập nhật Ngày hẹn đáp ứng cho từng tháng của từng chất chuẩn, đồng ý hoặc không đồng ý huỷ mặt hàng.',
            'Lưu ý: chất chuẩn do Cung Ứng mua; chỉ có tác dụng khi đang chọn phòng Cung Ứng ở thanh trên cùng.',
        ],
        'estimate_material_tab_list' => [
            'Xem tab Danh sách phiếu: các phiếu dự trù vật tư của phòng đang chọn và trạng thái ký.',
        ],
        'estimate_material_tab_tracking' => [
            'Xem tab Theo dõi dự trù: từng mặt hàng đã duyệt của phòng với ngày mong muốn, ngày hẹn đáp ứng, trạng thái và cột Trao đổi.',
        ],
        'estimate_material_tab_watchlist' => [
            'Xem tab Danh sách vật tư cần dự trù: vật tư dưới ngưỡng tồn tối thiểu, do người dùng đề nghị, hoặc thiếu ở Khả dụng tháng tới; tick chọn để lập phiếu dự trù nhanh.',
        ],
        'estimate_material_tab_approved' => [
            'Xem tab Đơn dự trù đã được phê duyệt: phiếu dự trù vật tư đã duyệt của mọi phòng ban chờ bộ phận mua hàng tiếp nhận và hẹn ngày đáp ứng.',
            'Lưu ý: tab chỉ hiện khi đang chọn phòng mua hàng và có thêm quyền Tiếp Nhận Xử Lý Dự Trù Vật Tư.',
        ],
        'estimate_chemical_tab_list' => [
            'Xem tab Danh sách phiếu: các phiếu dự trù hoá chất của phòng đang chọn và trạng thái ký.',
        ],
        'estimate_chemical_tab_tracking' => [
            'Xem tab Theo dõi dự trù: từng mặt hàng đã duyệt của phòng với ngày mong muốn, ngày hẹn đáp ứng, trạng thái và cột Trao đổi.',
        ],
        'estimate_chemical_tab_approved' => [
            'Xem tab Đơn dự trù đã được phê duyệt: phiếu dự trù hoá chất đã duyệt của mọi phòng ban chờ Cung Ứng tiếp nhận và hẹn ngày đáp ứng.',
            'Lưu ý: tab chỉ hiện khi đang chọn phòng Cung Ứng và có thêm quyền Tiếp Nhận Xử Lý Dự Trù Hoá Chất.',
        ],
        'estimate_standard_tab_list' => [
            'Xem tab Danh sách phiếu: các phiếu dự trù chất chuẩn của phòng đang chọn và trạng thái ký.',
        ],
        'estimate_standard_tab_tracking' => [
            'Xem tab Theo dõi dự trù: từng mặt hàng đã duyệt của phòng với ngày mong muốn, ngày hẹn đáp ứng, trạng thái và cột Trao đổi.',
        ],
        'estimate_standard_tab_approved' => [
            'Xem tab Đơn dự trù đã được phê duyệt: phiếu dự trù chất chuẩn đã duyệt của mọi phòng ban chờ Cung Ứng tiếp nhận và hẹn ngày đáp ứng.',
            'Lưu ý: tab chỉ hiện khi đang chọn phòng Cung Ứng và có thêm quyền Tiếp Nhận Xử Lý Dự Trù Chất Chuẩn.',
        ],
        'estimate_material_chat' => [
            'Gửi tin nhắn trao đổi giữa phòng đề nghị và bộ phận mua hàng ở cột Trao đổi của từng vật tư trong tab Theo dõi dự trù.',
        ],
        'estimate_chemical_chat' => [
            'Gửi tin nhắn trao đổi giữa phòng đề nghị và bộ phận mua hàng ở cột Trao đổi của từng hoá chất trong tab Theo dõi dự trù.',
        ],
        'estimate_standard_chat' => [
            'Gửi tin nhắn trao đổi giữa phòng đề nghị và bộ phận mua hàng ở cột Trao đổi của từng chất chuẩn trong tab Theo dõi dự trù.',
        ],
        'estimate_chemical_tab_watchlist' => [
            'Xem tab Danh sách hoá chất cần dự trù: hoá chất có tồn dưới ngưỡng tồn tối thiểu của phòng; tick chọn để lập phiếu dự trù nhanh.',
        ],
        'estimate_standard_tab_watchlist' => [
            'Xem tab Danh sách chất chuẩn cần dự trù: chất chuẩn có tồn dưới ngưỡng tồn tối thiểu của phòng; tick chọn để lập phiếu dự trù nhanh.',
        ],

        // ===================== QUẢN TRỊ =====================
        'user_view' => [
            'Thấy mục Phân Quyền › User trên menu và xem danh sách tài khoản người dùng cùng phòng ban, tổ, nhóm quyền của từng người.',
        ],
        'user_create' => [
            'Tạo tài khoản người dùng mới: tên đăng nhập (không trùng toàn hệ thống), mật khẩu tạm, phòng ban, tổ, nhóm quyền.',
            'Lưu ý: người dùng bị buộc đổi mật khẩu ở lần đăng nhập đầu tiên.',
        ],
        'user_update' => [
            'Sửa thông tin tài khoản: họ tên, phòng ban, tổ, các nhóm quyền và phạm vi phòng ban của từng nhóm quyền.',
            'Lưu ý: nhập mật khẩu mới trong hộp thoại Sửa thì được xử lý như reset (mở khoá, buộc đổi ở lần đăng nhập sau).',
        ],
        'user_deActive' => [
            'Vô hiệu hoá tài khoản khi nhân viên nghỉ việc, chuyển công tác: tài khoản không đăng nhập được nữa nhưng không bị xoá, tên trên phiếu cũ, chữ ký và nhật ký vẫn giữ nguyên.',
        ],
        'role_view' => [
            'Thấy mục Phân Quyền › Nhóm Quyền trên menu và xem bảng phân quyền: mỗi nhóm quyền đang được tick những quyền nào. Chỉ xem.',
        ],
        'role_update' => [
            'Tick hoặc bỏ tick quyền cho các nhóm quyền trên bảng Nhóm Quyền. Thay đổi có hiệu lực ngay với mọi người thuộc nhóm.',
            'Lưu ý: tick quyền con thì quyền cha được tick theo, bỏ quyền cha thì các quyền con bị bỏ theo. Không gỡ được quyền của nhóm Admin.',
        ],
        'permission_view' => [
            'Thấy mục Phân Quyền › Quyền trên menu và xem danh sách mọi quyền của hệ thống kèm diễn giải (màn hình này).',
        ],
        'userPermission_manage' => [
            'Cấp quyền riêng cho từng người ngoài nhóm quyền (nút Quyền Riêng ở màn User): với mỗi quyền chọn Theo nhóm quyền, Cho phép hoặc Từ chối.',
            'Lưu ý: Từ chối chặn quyền đó dù nhóm quyền của người dùng có cấp.',
        ],
        'auditTrail_view' => [
            'Thấy mục Lịch Sử Đăng Nhập › Audit Trail và xem nhật ký hệ thống tự ghi: đăng nhập / đăng xuất, khoá tài khoản, đổi mật khẩu, ký duyệt, thêm - sửa - duyệt dữ liệu... kèm giá trị cũ và mới.',
            'Lưu ý: nhật ký chỉ ghi thêm, không ai sửa hay xoá được, kể cả quản trị viên.',
        ],
        'user_resetPassword' => [
            'Đặt lại mật khẩu tạm cho người dùng (quên mật khẩu, bị khoá do nhập sai): tài khoản được mở khoá ngay, người dùng phải đổi mật khẩu ở lần đăng nhập kế tiếp.',
            'Lưu ý: mật khẩu tạm phải đủ độ phức tạp và không trùng 5 mật khẩu gần nhất của người đó.',
        ],
        'role_manage' => [
            'Tạo nhóm quyền mới, đổi tên, sửa diễn giải và Loại Phụ Trách của nhóm quyền, xoá nhóm quyền.',
            'Lưu ý: không xoá được nhóm quyền đang được gán cho người dùng.',
        ],
        'login_history_view' => [
            'Thấy mục Lịch Sử Đăng Nhập › Toàn Hệ Thống: lượt đăng nhập của mọi tài khoản (thành công, sai mật khẩu, bị khoá), các phiên đang mở và xuất Excel.',
            'Lưu ý: không có quyền này vẫn xem được lịch sử đăng nhập của chính mình ở mục Của Tôi.',
        ],
    ];

    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });

        $now = now();
        foreach (self::DESCRIPTIONS as $name => $lines) {
            DB::table('permissions')->where('name', $name)->update([
                'description' => implode("\n", $lines),
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Không khôi phục diễn giải cũ; cột giữ kiểu text để không cắt mất diễn giải dài
    }
};
