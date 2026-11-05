---
name: cms3-access-import
description: Nạp dữ liệu hoá chất từ phần mềm cũ CMS 3.0 (Microsoft Access, file CMS_3.0_L.accdb, bảng link SQL Server dbo_T_*) vào WMS. Dùng khi người dùng nhắc tới CMS 3.0 / CMS_3.0_L.accdb / file Access / Q_DMHC_Chung, Q_DMHC_ChungHH, Q_DMHC_QC1HH, Q_DMHC_QC2HH, Q_Ton, Q_Ton2, TondauQC, dbo_T_Nhap_QC*, hoặc muốn nạp/nạp lại Tên hoá chất (chem_names), danh mục hoá chất công ty, danh mục hoá chất của phòng + định khu, tồn đầu kỳ hoá chất cho một bộ phận (QC1, QC2, QC, AD), hay thêm một bộ phận mới từ Access. Gồm script xuất Access (PowerShell + ACE OLEDB), 4 script PHP chạy lại được (có --dry), quy tắc ánh xạ đã được người dùng chốt và các bẫy dữ liệu đã gặp.
---

# Nạp dữ liệu hoá chất từ CMS 3.0 (Access) vào WMS

Đọc hết file này trước khi chạy. Các quy tắc ở mục 2 là **quyết định đã chốt với người dùng** (10/2026) - không tự đổi; trường hợp mới chưa có trong đây thì hỏi lại.

## 1. Quy trình

Script nằm ở `.claude/skills/cms3-access-import/scripts/`. Thư mục dữ liệu (`<D>`) để ngoài repo (scratchpad / %TEMP%).

| Bước | Lệnh | Ghi vào CSDL |
|---|---|---|
| 0 | `powershell -File scripts\export_access.ps1 -Source "Z:\CMS_3.0_L.accdb" -OutDir <D> -Departments QC1,QC2` | không - ra 5 file JSON |
| 1 | `php scripts/1_chem_names.php <D> [--dry]` | chem_names, chem_name_active_ingredient, active_ingredients ngoài danh mục, Bảng B mặc định |
| 2 | `php scripts/2_categories.php <D> [--dry]` | manufacturers, chemical_categories, units, warehouses→locations, chemical_department_categories |
| 3 | `php scripts/3_stock.php <D> [--dry]` | suppliers, chemical_imports (+ histories) |
| 4 | `php scripts/4_review.php <D>` | không - CSV rà soát |
| 6 | `php scripts/6_rename_by_ta.php <D> [--dry]` | **chỉ dùng cho dữ liệu nạp TRƯỚC 04/11/2026** (bước 1 cũ đặt tên đơn chất = tên hoạt chất): đổi tên tại chỗ theo TenhoachatTA, tách mã C đã gộp nhiều TenhoachatTA (mã C mới C01013…), chuyển dòng khai phòng + lô theo mã H. Đã chạy 04/11/2026 - không chạy lại |
| 7 | `php scripts/7_add_departments.php <D> QC,AD [--dry]` (xuất bước 0 với `-Departments QC,AD`) | chỉ chemical_department_categories + định khu (warehouses→locations) của phòng mới; KHÔNG đụng danh mục công ty / QC1 / QC2. Dừng nếu phòng đã có phiếu nhập. Đã chạy 04/11/2026 |
| 8 | `php scripts/8_add_new_codes.php <D> [--dry]` | mã Access tạo SAU lần nạp (đang được bộ phận dùng, chưa có mã C): tên hoá chất (tạo nếu chưa có, thành phần theo CAS) + danh mục công ty (gộp tên+NSX+Loại với dòng có sẵn → lịch sử "Cập nhật" "CMS 3.0, gộp thêm mã: H…", không thì mã C tiếp theo). Chạy trước bước 7 |
| 5 | `php scripts/5_compare.php <D>` rồi `python scripts/5_compare_xlsx.py <D> "%USERPROFILE%\Desktop\DoiChieu_DanhMucHoaChat_WMS_CMS3.xlsx"` | không - Excel đối chiếu mã C ↔ mã H (mỗi mã H 1 dòng, so Tên/CAS/NSX/Loại/Trạng thái/Nhóm/Cảnh báo/ĐVT-hạn dùng-định khu QC1, QC2) + sheet mã nghi ngờ (bước 4) + hoạt chất tạo mới |

- Excel có công thức (COUNTIF, SUMPRODUCT): máy này không có LibreOffice (recalc.py của skill xlsx lỗi AF_UNIX trên Windows) → tính lại bằng Excel 2016 qua COM: `$x=New-Object -ComObject Excel.Application; $wb=$x.Workbooks.Open($f); $x.CalculateFull(); $wb.Save(); $x.Quit()`, rồi kiểm lỗi bằng openpyxl `data_only=True`.
- Gọi `export_access.ps1 -Departments QC1,QC2` qua `-File` thì mảng đến thành 1 chuỗi → script đã tự tách (lỗi cũ: "JOIN expression not supported").

- **Luôn chạy `--dry` trước** (cả transaction rollback), đọc số liệu + CSV báo cáo, rồi mới chạy thật.
- **Sao lưu trước khi chạy thật**: `C:\xampp\mysql\bin\mysqldump.exe -uroot wms chem_names chem_name_active_ingredient chem_name_mixture_hazard_category active_ingredients active_ingredient_classifications manufacturers units chemical_categories chemical_category_histories chemical_department_categories warehouses shelves columns tiers locations chemical_imports chemical_import_histories suppliers datamaster_histories > <D>\backup.sql`
- Các bước nối nhau qua **lịch sử "Thêm mới"** trong CSDL (ghi chú chứa "CMS 3.0" + mã Access H…), không qua file trung gian: bước 2 đọc mã Access → tên hoá chất từ `datamaster_histories` (chem_names), bước 3 đọc mã Access → mã danh mục từ `chemical_category_histories`. Vì vậy chạy lại bước 1 thì phải chạy lại 2, 3.
- Mọi bản ghi do script tạo có `created_by = 'Import CMS 3.0 (system)'` (CMS3['actor']) - chạy lại sẽ xoá đúng phần này rồi dựng lại. Bước 1/2/3 **tự dừng** nếu đã có phiếu xuất hoặc phiếu nhập/danh mục do người dùng tạo (`--force` để bỏ qua, chỉ khi người dùng đồng ý).
- Đổi phòng ban / kho / mục gộp / đơn vị mới / mã Bảng B mặc định: sửa hằng `CMS3` trong `scripts/_boot.php`.
- Xuất CSV thành Excel có tổng hợp: dùng skill `xlsx` (font Arial, tổng hợp bằng COUNTIF, ô điền kết luận nền vàng, `fullCalcOnLoad`); file để ở Desktop người dùng.

## 2. Quy tắc ánh xạ (đã chốt)

### Nguồn Access
- "Q_DMHC_Chung" người dùng nói = **`Q_DMHC_ChungHH`** (phiên bản MHC lớn nhất của từng mã trong `dbo_T_DMHC_Chung`).
- Danh mục phòng = `Q_DMHC_<BP>HH` (bảng `dbo_T_DMHC_QC1` lọc `Mabophan`), tồn = `Q_Ton` (QC1) / `Q_Ton2` (QC2) (`dbo_T_Nhap_<BP>` ⟕ `dbo_T_Xuat_<BP>`).
- Mabophan → phòng WMS: **QC1 → id 20 "Kiểm tra chất lượng - Toàn nhà 1" (QC-B1)**, **QC2 → id 9 QC-B2**. (Phòng id 5 có EngshortName=QC1 nhưng đã khoá - KHÔNG dùng.) **QC (Q_DMHC_QC3HH) → id 15 KTCL-NM1 (kho `QCNM1-HC`), AD (Q_DMHC_QC4HH) → id 10 PTPT (kho `PTPT-HC`)** - người dùng chốt 04/11/2026, nạp danh mục phòng bằng bước 7 (chưa nạp tồn đầu kỳ: QC 540 lô, AD 557 lô trong lots.json).

### Tên hoá chất (bước 1)
- **Tên hoá chất = `TenhoachatTA` cho MỌI mã** (đơn chất lẫn hỗn hợp - người dùng đổi 04/11/2026; trước đó đơn chất lấy tên hoạt chất). Thành phần = hoạt chất ở Dữ Liệu Gốc đơn chất. **Mọi tên hoá chất phải liên kết ≥ 1 hoạt chất** (script ném lỗi nếu có tên mồ côi).
- Khoá so trùng tên: bỏ hoa/thường + mọi ký tự không phải chữ/số ("di-Sodium…" = "di- Sodium…"). Cùng tên mà thành phần khác (vd "Buffer solution pH 2.00" vừa đơn chất "Đệm pH 2.00" vừa hỗn hợp Nước + Citric + HCl) → **1 tên, giữ bộ nhiều thành phần nhất** (người dùng chọn gộp; hợp lại sẽ lẫn hoạt chất mang tên sản phẩm vào hỗn hợp).
- Hỗn hợp (≥ 2 thành phần): % lấy số lớn nhất trên dòng của cột `Tenhoatchat`.
- Hệ quả ở bước 2: danh mục gộp theo (tên + NSX + Loại) nên mã Access khác TenhoachatTA KHÔNG còn chung mã C (Ethanol / Ethanol 96 % là 2 mã).
- Khớp thành phần theo thứ tự: CAS → tên (name/name_en) → **CAS tra theo tên ở dòng Access khác** → mục gộp → tạo mới.
- Mục gộp (`CMS3['collectives']`, vd Thủy ngân và hợp chất id 248, Xyanua 281, Chì 3034, Cr6+ 3043…): chỉ gắn `parent_id` khi tên khớp từ khoá **và** cờ PL Access trùng nhóm của mục gộp.
- Tạo mới: `is_statutory=0`, `legal_ref='Ngoài danh mục NĐ 24/2026 (nạp từ CMS 3.0)'`, tên Việt từ `TenhoachatTV` đã bỏ nồng độ cuối tên; nếu là đơn chất thì ghi nhóm theo cờ PL vào `active_ingredient_classifications` (is_statutory=0, note "Theo phân loại CMS 3.0").
- Access có cờ nhóm hỗn hợp (2/8/10) mà chỉ 1 CAS ⇒ **dung dịch pha loãng = hoạt chất + "Nước"** (CAS 7732-18-5).
- Cờ: `PL_2_1…PL_4_B` = nhóm 1…10 theo thứ tự (`PL_1_1` không dùng), `HC_Cam` → `chem_names.is_banned`. Nhóm **không lưu tay** - WMS tự suy (App\Support\ChemicalClassification); lệch với Access chỉ báo cáo.
- **Nhóm 10**: Access không cho biết mục Bảng B ⇒ tick **mặc định B00001 (mục I.1)** cho hỗn hợp có cờ PL_4_B và có thành phần nhóm 9 (điều kiện giống màn Tên Hoá Chất); người dùng tự sửa sau.

### Danh mục công ty + phòng (bước 2)
- Mã danh mục sinh mới **C00001… liên tục**, đã duyệt; gộp theo (tên hoá chất + NSX + Loại); `Loai '-'` → NULL; `Active=False` → status_id 0.
- NSX từ `NSX` + tên đầy đủ `dbo_T_NSX.Tendaydu`. **Điều kiện bảo quản: để trống** (Access không có). Tỉ trọng: trống (Access không có ⇒ ml/L không quy đổi ra kg được - xem 4.).
- Cảnh báo an toàn: Doc→TOXIC, DeChay→FLAMMABLE_LIQUID (ĐVT ml/l) / FLAMMABLE_SOLID, AnMon→CORROSIVE_BASE nếu tên có hydroxide/amine/ammonia/hydrazine còn lại CORROSIVE_ACID (heuristic - cần rà), Oxyhoa→OXIDIZING, DeNo→EXPLOSIVE.
- Định khu **dựng theo mã Dinhkhu Access** (người dùng chọn, chấp nhận vi phạm tương kỵ sẵn có): Kho `QC1-HC`/`QC2-HC` → Tủ → Cột 01 → Tầng → Vị trí, `item_type='chemical'`. Tách mã: `14D4`/`T1H5`/`9AA1` → Tủ/Tầng(chữ)/Vị trí; `15A` → Tủ/Tầng/01; `5.1`, `034.X1` → Tủ . Tầng Vị trí; `Tủ 39`; còn lại cả mã = 1 tủ; `NA`/`OQDK..` → không định khu.
- ĐVT phòng = `DVT`; đơn vị thiếu tạo mới nhóm count (Bản, Que, Thẻ, U, CAP).
- **`SNHD` = số THÁNG hạn dùng sau khi mở nắp** (đã kiểm: HDNB − NgayMoNap = SNHD tháng) → `chemical_department_categories.shelf_life_months` (0 → trống).
- **Ngưỡng tồn min/max: ngẫu nhiên** theo đơn vị (seed cố định) - Access không có ngưỡng.

### Tồn đầu kỳ (bước 3)
- Mỗi lô Access còn tồn → 1 phiếu: `amount` = tồn hiện tại; giữ `Ngaynhap`/`Nguoinhap` gốc (ngoại lệ có chủ đích với quy tắc "ngày thao tác = now()" - đây là chuyển đổi dữ liệu); `check_result='passed'` (để pending thì tồn bị kẹt).
- **`Monap = False` (chưa mở nắp) ⇒ `internal_expired_date = NULL`** (HDNB của Access lúc này chỉ là bản sao Hansudung). Lô đó hiện "chờ xác định hạn dùng nội bộ" và không chọn xuất được cho tới khi bấm "Xác định hạn dùng nội bộ" ở Tồn Kho (nếu có shelf_life_months). `Monap = True` ⇒ giữ HDNB.
- NCC: `MaNCC` → `dbo_T_NCC` → bảng `suppliers` (tạo mới nếu chưa có tên).
- Ghi chú phiếu: mã XN Access, SL nhập gốc, cân đối, đã xuất, mở nắp, ghi chú cũ.

### Nạp lại tồn theo Access mới nhất khi đã có dữ liệu thật (04/11/2026)
- Trình tự: bước 0 (`-Departments QC1,QC2,QC,AD`) → xoá phiếu thử trên WMS (người dùng đồng ý; sao lưu cả CSDL trước) → 8 → 7 (`QC1,QC2,QC,AD`, dựng lại danh mục phòng + định khu) → 3. KHÔNG chạy lại 1, 2 (xoá cả danh mục công ty).
- Bước 3 tra mã H → mã C bằng `cms3_category_of_access_code()` (đúng với mã tách ở bước 6 và mã gộp thêm ở bước 8).
- Lần 04/11/2026: xoá 12 phiếu xuất, 7 phiếu nhập, 1 cách ly, 3 phiếu nháp, 2 phiếu chuyển; thêm H1083 → C01061, H1084 (Hydranal-Titrant 2 = Metanol + Iot) → C01062; tồn QC1 1.319 lô / 1.964.794,806, QC2 568 / 781.659,455 (= Q_Ton2), QC NM1 540 / 953.606,296, PTPT 557 / 514.654,089 - khớp 100% Access.

### Thêm bộ phận khi đã có dữ liệu thật (bước 7, 04/11/2026)
- Bước 2 xoá & dựng lại toàn bộ danh mục công ty → KHÔNG chạy lại; dùng bước 7. Mã Access → mã C qua `cms3_category_of_access_code()` (lịch sử "Thêm mới" theo id: mã tách ở bước 6 lấy danh mục mới).
- Định khu: `cms3_parse_location_ext()` = cms3_parse_location + `T1-A1`/`T088.1-D2` → Tủ/Tầng/Vị trí, `TL1-10`/`T25V-3`/`T088.2-1` → Tủ (trước "-")/Tầng 1/Vị trí n, `TLA1` → Tủ TL/Tầng A/Vị trí 1. `Tủ lạnh`/`Tủ mát` → tủ "LẠNH"/"MÁT"; PTPT có ~110 mã chung tủ `TH`.
- DVT `UN` (enzyme) → đơn vị `U` (`CMS3['unit_aliases']`).
- Lần chạy 04/11/2026: QC NM1 591 dòng, PTPT 353 dòng (+3 mã gộp, 1 dòng thử), 2 kho / 80 tủ / 580 vị trí, 479 dòng tương kỵ cùng tầng (giữ đúng Access).

## 3. Bẫy dữ liệu Access đã gặp

- **File gốc bị khoá độc quyền** ("placed in a state by user 'Admin' on machine …") → luôn chép ra rồi đọc bản chép (export_access.ps1 làm sẵn).
- **OLEDB không chạy được hàm VBA / hàm Access**: `GetNhomHC` (trong Q_DMHC_QC1HH, Q_Ton, TondauQC1 …) và `Nz()`. Viết lại SQL trên bảng gốc, `Nz(x,0)` → `IIf(IsNull(x),0,x)`. Q_Ton2 không dùng hàm VBA nên SELECT được để đối chiếu.
- **PowerShell 5.1 đọc .ps1 UTF-8 không BOM theo ANSI** → file .ps1 có tiếng Việt phải lưu UTF-8 **có BOM**.
- `ConvertTo-Json` trả object (không phải mảng) khi 1 dòng → dùng `-InputObject @($rows)`; PHP đọc qua `cms3_json()`.
- **Mã dùng trùng MHC**: H0783 có MHC `H04170` giống H0417 → khử trùng theo `Mahoachat + MHC`, không chỉ MHC.
- **Dữ liệu thử**: tên `OQ1/OQ02/OQ03`, Loại `OQLHC..`, NSX `OQNSX..` ("nhà sản xuất 0x"), ĐVT `OQDV..`, định khu `OQDK..`, mã lạ `H00391` → `cms3_is_test_row()` bỏ qua.
- CASnumber có `NA` lẫn trong danh sách ("67-56-1, 288-32-4 ,NA"), số dòng `Tenhoatchat` ≠ số CAS, dòng chứa nhiều thành phần cách dấu phẩy, ký tự zero-width (U+200B) → xem cách ghép cặp trong `1_chem_names.php`.
- Tách tên thành phần: KHÔNG cắt ở "1-" ("Sodium 1-octanesulfonate" từng bị cắt thành "Sodium" → khớp nhầm "Natri"); bỏ nồng độ chỉ khi đứng cuối tên ("Sabouraud 4% dextrose agar" giữ nguyên).
- Thành phần không ghi CAS ("Hydrochloric acid" trong "Cleaning") → tra CAS theo tên ở dòng Access khác, nếu không sẽ tạo hoạt chất trùng.
- `TenhoachatTV` có lỗi chính tả / gán nhầm (NaCl ghi "Natri clorit") → tên trùng hoạt chất khác thì dùng tên tiếng Anh.
- Lô quá hạn trong Access đã được đánh `Het = True` nên không có trong tồn.
- **Chủng vi sinh** (Bacillus, E. coli, Candida…, CAS "NA") từng bị khớp hết vào 1 hoạt chất "Bacillus Subtilis" → bước 6 đã tạo hoạt chất riêng (cas_no "Chủng vi sinh"). Nạp lại thì kiểm 4_review: mã không CAS phải ra hoạt chất đúng tên.
- Bản xuất `lots.json` mới hơn lần nạp thì có lô đã hết ở Access → bước 6 báo `lots_unmapped` (tra lại ở bản xuất cũ).
- Mã Access gộp chung 1 mã danh mục nhưng khác DVT (vd ml vs g) → báo "ĐVT Access khác ĐVT phòng - chưa quy đổi", không tự đổi.

## 4. Kiểm tra sau khi nạp

- Số lô / tổng tồn từng phòng = số của Access (QC2 so trực tiếp với Q_Ton2; export_access.ps1 in ra). Kiểm thêm bằng `App\Support\ChemicalStock::onHandByDepartmentCategory($catIds, $deptId)`.
- `chem_names` không có tên mồ côi; đơn chất có tên = tên hoạt chất (trừ tên bị gắn mã vì trùng).
- `App\Support\ChemicalClassification::codesByCategory()` chạy được; nhóm 10 có đúng số hỗn hợp được tick.
- `App\Support\ChemicalCompatibility::conflictsByDepartmentRows(DepartmentChemical::rowsOfDepartment($id))` → số dòng có badge "Tương kỵ cùng tầng" (phạm vi theo `config('chemical.incompatibility_scope')`, hiện là `tier`).
- Báo cáo nhập/xuất cột "Quy đổi (kg)": ml/L thiếu tỉ trọng hiện chip "Thiếu tỉ trọng d (g/ml)", đơn vị đếm hiện "Đơn vị đếm/bao bì" (UnitConverter::check()['cause']).
- Gửi người dùng bảng rà soát (4_review_*.csv → Excel). Các nhóm vấn đề thường gặp: lệch nhóm Access/WMS, Bảng B mặc định, hoạt chất tự gắn mục gộp, đơn chất mất nồng độ khi gộp theo hoạt chất, thành phần nhóm 3-7 thiếu %, tên dạng mã/công thức (CT1, KCl), Loại lạ (R1), không CAS, tương kỵ cùng tầng.

## 5. Việc còn treo (lần nạp 22-23/10/2026)

- Tỉ trọng d chưa có cho mọi mã (ml/L không ra kg - ảnh hưởng ngưỡng PL IV). Đã đề xuất điền tỉ trọng tra cứu cho dung môi phổ biến - **chờ người dùng đồng ý**, không tự điền.
- Báo cáo nhập không đọc `pack_weight_kg` của phòng cho đơn vị đếm.
- Mục Bảng B mặc định B00001 cần người dùng chọn lại; định khu vi phạm tương kỵ cùng tầng cần xếp lại.
