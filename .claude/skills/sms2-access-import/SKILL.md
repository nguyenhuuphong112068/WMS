---
name: sms2-access-import
description: Nạp dữ liệu CHẤT CHUẨN từ phần mềm cũ SMS 2.0 (Microsoft Access, file SMS_2.0_L.accdb, bảng link dbo_T_* trỏ tới \\stellapharm.int\share-bd\QLHC\Database_SMS) vào WMS - danh mục chất chuẩn công ty + dữ liệu gốc liên quan (tên chuẩn, nguồn gốc/NSX, điều kiện bảo quản, đơn vị, chỉ tiêu kiểm), danh mục chất chuẩn của phòng + định khu kho chất chuẩn, tồn đầu kỳ (mã ống chuẩn), phiếu đánh giá hạn dùng CTC (DOD). Dùng khi người dùng nhắc tới SMS 2.0 / SMS_2.0_L.accdb / Q_DMHC_ChungHH, Q_DMHC_QC1HH, Q_DMHC_QC2HH, Q_Ton, Q_Ton_QC1, Q_Ton_QC2 / dbo_T_DMHC_Chung, dbo_T_DMHC_Phong, dbo_T_Nguongoc, dbo_T_DinhKhu*, dbo_T_Nhap_QC*, dbo_T_Xuat_QC*, dbo_T_DS_DOD_*, dbo_T_DOD_item_* (đánh giá hạn dùng chuẩn thứ cấp CTC), hoặc muốn nạp / nạp lại / chốt sổ chuyển đổi chất chuẩn, thêm bộ phận mới (QC, AD). Gồm script xuất Access (PowerShell + ACE OLEDB), 4 script PHP chạy lại được (--dry), quy tắc ánh xạ đã chốt với người dùng và các bẫy dữ liệu đã gặp.
---

# Nạp chất chuẩn từ SMS 2.0 (Access) vào WMS

Đọc hết file này trước khi chạy. Quy tắc mục 2 là **quyết định đã chốt với người dùng** (26/10/2026) - không tự đổi;
trường hợp mới chưa có ở đây thì hỏi lại. Bộ script cùng khuôn với skill `cms3-access-import` (hoá chất CMS 3.0).

## 1. Quy trình

Script ở `.claude/skills/sms2-access-import/scripts/`; thư mục dữ liệu `<D>` để ngoài repo (scratchpad / %TEMP%).

| Bước | Lệnh | Ghi vào CSDL | Báo cáo |
|---|---|---|---|
| 0 | `powershell -File scripts\export_access.ps1 -Source "Z:\SMS_2.0_L.accdb" -OutDir <D> [-Departments "QC1,QC2"]` | không | dmhc_chung, nguongoc, dmhc_dept, dinhkhu, nhap, xuat_sum (.json) |
| 1 | `php scripts/1_standard_catalog.php <D> [--dry]` | standard_names, manufacturers, storage_conditions, units, standard_categories (+ histories) | 1_standard_catalog_report.csv |
| 2 | `php scripts/2_department_categories.php <D> [--dry]` | warehouses→shelves→columns→tiers→locations (item_type standard), standard_department_categories | 2_department_categories_report.csv |
| 3 | `php scripts/3_stock.php <D> [--dry]` | purposes (Chỉ tiêu kiểm), standard_imports + standard_exports "tổng xuất" (+ histories) = tồn đầu kỳ, standard_categories.density (chuẩn ml có KSKL) | 3_stock_report.csv |
| 4 | `php scripts/4_stability.php <D> [--dry]` | standard_stability_assessment_list / _item / _histories (tab Đánh giá hạn dùng CTC ở Tồn Kho Chất Chuẩn), purposes thiếu | 4_stability_report.csv |

- **Luôn `--dry` trước** (cả transaction rollback), đọc số liệu + CSV (cột "Cần rà"), rồi mới chạy thật.
- **Sao lưu trước khi chạy thật**:
  `C:\xampp\mysql\bin\mysqldump.exe -uroot wms standard_names standard_categories standard_category_histories standard_department_categories standard_imports standard_import_histories standard_exports standard_export_histories manufacturers storage_conditions units purposes warehouses shelves columns tiers locations datamaster_histories --result-file=<D>\backup.sql`
- Bản ghi do script tạo có `created_by = 'Import SMS 2.0 (system)'` (`SMS2['actor']`). Chạy lại bước N = xoá phần script đã tạo của bước N **và các bước sau** rồi dựng lại bước N → phải chạy tiếp các bước sau (1 → 2 → 3 → 4). Dữ liệu gốc (NSX, bảo quản, đơn vị, chỉ tiêu kiểm) chỉ xoá khi không còn bảng nào trỏ tới; NSX do CMS 3.0 tạo không bị đụng.
- Bước 0 xuất thêm `dod_list.json` / `dod_item.json` (dbo_T_DS_DOD_<BP>, dbo_T_DOD_item_<BP>; bảng `dbo_T_DOD_item_update_<BP>` chỉ là nhật ký lần sửa - không lấy). `sms2_delete_stock` (đầu bước 1-3) gọi `sms2_delete_stability` xoá phiếu đánh giá trỏ vào ống script tạo; dừng nếu người dùng đã lập phiếu / ghi thêm trên đó.
- Sao lưu trước bước 4: `mysqldump … standard_stability_assessment_list standard_stability_assessment_item standard_stability_assessment_histories purposes datamaster_histories`.
- Script **tự dừng** khi đã có dữ liệu thật: phiếu nhập / sử dụng chất chuẩn do người dùng tạo (`sms2_delete_stock`, gọi ở đầu cả 3 bước), danh mục do người dùng tạo (mã S00001.. sẽ trùng). `--force` chỉ khi người dùng đồng ý.
- Các bước nối nhau qua **lịch sử "Thêm mới"** của danh mục công ty (`standard_category_histories.change_note` chứa "SMS 2.0" + mã Access S0375… kèm ĐVT / phòng dùng / người tạo / người duyệt gốc), đọc bằng `sms2_codes_from_history('standard_category_histories', 'standard_category_id')`.
- **SMS 2.0 vẫn đang được dùng song song**: số tồn đổi giữa các lần xuất (đã thấy thêm phiếu xuất sau 1 giờ). Lần nạp CHỐT SỔ: ngừng nhập liệu trên SMS → chạy lại bước 0 → 1 → 2 → 3 (dry rồi thật).
- Cấu hình (phòng ban, nhóm chuẩn, bảng bảo quản, đơn vị): hằng `SMS2` trong `scripts/_boot.php`. Hàm dùng chung: `sms2_clean / sms2_nullish / sms2_mode / sms2_unit_id / sms2_unit_key / sms2_unique_rows / sms2_codes_from_history`.
- Gửi người dùng 3 CSV (chép ra Desktop `RaSoat_*.csv`); muốn Excel có tổng hợp thì dùng skill `xlsx`.

## 2. Quy tắc ánh xạ (đã chốt)

### Danh mục công ty + dữ liệu gốc (bước 1) - nguồn `Q_DMHC_ChungHH`
- `Q_DMHC_ChungHH` = phiên bản MHC lớn nhất của từng `Machuan` trong `dbo_T_DMHC_Chung`; join theo MHC nên nhân dòng khi 2 mã trùng MHC (S3785/S3798 cùng S37980) → khử trùng theo Machuan, giữ Ver lớn nhất.
- **Gộp theo Tên chuẩn + Nguồn gốc + Ver + nhóm ĐVT** (`sms2_unit_key`): mg/g/µg chung (khối lượng), ml/µl chung, mỗi đơn vị đếm (lọ, Phổ, ống, gói…) riêng. Lý do: trong 1 phòng Access theo dõi cùng 1 chuẩn bằng nhiều mã khác ĐVT (Opadry: g / lọ / Phổ), WMS chỉ cho 1 ĐVT trên 1 dòng danh mục phòng và g↔lọ↔Phổ không quy đổi được. (Ban đầu chỉ gộp Tên+NSX+Ver → bị lệch ĐVT ở 269 dòng phòng, người dùng chọn tách lại.)
- **Mã danh mục sinh mới S00001… liên tục** theo mã Access nhỏ nhất của nhóm; `version` = Ver Access.
- Tên chuẩn = `Tenchuan` gom khoảng trắng/xuống dòng; trùng theo collation CSDL (hoa/thường, dấu) → 1 tên; `cas_no` = các CAS khác NA; `active_ingredient_name` trống.
- Nguồn gốc = `Nguongoc` → dùng lại `manufacturers` theo short_name (kể cả NSX CMS 3.0); chưa có thì tạo, tên đầy đủ từ `dbo_T_Nguongoc.TenDayDu`; tên trùng → "TenDayDu (viết tắt)" (LGC - Mikromol / LGC - Dr. Ehrenstorfer cùng "LGC GmbH").
- Nhóm chuẩn: API→PRS, IMPU→IMPRS, CTC, VKN, CN, CHH→CHC, CNL; gộp mã → **hợp** các nhóm.
- **Điều kiện bảo quản CHUẨN HOÁ** (`SMS2['storage_map']`, 41 chuỗi Access → 8 nhóm): Tủ lạnh (2-8°C), Tủ đông (-20°C), Nhiệt độ phòng (15-25°C), Nhiệt độ 2-30°C, Tránh ánh sáng, Bảo quản kín, Bảo quản kín + tránh ánh sáng, Nơi khô ráo. Chuỗi gốc → `standard_categories.note`; lệch giữa mã gộp → nhóm nhiều nhất; NA/- → trống. Chuỗi mới chưa khai → trống + báo cáo (bổ sung vào storage_map).
- Duyệt: mọi mã gộp đều `Approved` → approved, còn lại pending; `created_at` = NgayTao sớm nhất.
- Đơn vị thiếu tạo sẵn (vial, cell, miếng, Phổ - nhóm count); `lo`→lọ, `pill`→viên.
- Access không có tỉ trọng, hạn dùng mặc định, số tài liệu, thời gian đặt hàng → trống.

### Danh mục phòng + định khu (bước 2) - nguồn `Q_DMHC_QC1HH` / `Q_DMHC_QC2HH`
- = MHC lớn nhất của từng machuan trong `dbo_T_DMHC_Phong` lọc `Bophan` (MHC "QC1S20350.0", Ver "0.0" = Ver công ty.phiên bản phòng); join thêm Machuan để không nhân dòng.
- Bophan → phòng: **QC1 → id 20 (QC-B1), QC2 → id 9 (QC-B2)**. QC (bảng định khu dbo_T_DinhKhu3) và AD (dbo_T_DinhKhu4) chưa nạp - xem mục 5.
- Nhiều mã cùng phòng vào 1 dòng danh mục → 1 dòng phòng: ĐVT / định khu / hạn dùng lấy giá trị nhiều nhất (bằng nhau → mã Access nhỏ hơn), lệch → báo cáo.
- `unit_id` = DVT; `shelf_life_months` = `STHDNB` (số tháng hạn dùng nội bộ, 0 → trống); **min/max để trống** (khác hoá chất CMS 3.0 là ngẫu nhiên); `storage_condition_id` trống (phòng theo danh mục công ty); `note` = GhiChu (bỏ NA/0/Test) + định khu chưa dựng được.
- **Định khu dựng theo bảng định khu Access** (MaViTri → Tu): Kho `QC1-CC`/`QC2-CC` → Tủ = Tu (mã: có số giữ "QC-INS-036", "T10"; không số viết tắt "TCPHC") → Cột 01 (tủ con: T7M2 → cột T7) → Tầng → Vị trí, `item_type = 'standard'`. Bỏ số hiệu tủ đầu mã rồi tách: `036K.BHA28` → K/BHA28, `T10O7` → O/07, `244A.1` → A/01, `22DT` → DT/01, `2641A` → 1/A, `R4` → R/04, `A` → A/01. Vị trí chữ đánh số sau vị trí số lớn nhất của tầng.
- Mã không có trong bảng định khu → tủ có số hiệu dài nhất khớp đầu mã (20BHA → Tủ 20); số hiệu đầu mã lệch tủ của bảng (328E8 thuộc QC/INS-382) → tin bảng, bỏ phần số; còn lại (BHA2.E4, DC có Tu = NA) → không định khu, ghi note.

### Tồn đầu kỳ (bước 3) - nguồn `Q_Ton_QC1` / `Q_Ton_QC2`
- Người dùng nói "Q_Ton" = **`Q_Ton_QC1`** (không có query tên đúng Q_Ton). Tồn mã ống Maxn = Σ Round(Soluongnhap,3) − Σ Round(Soluongxuat,3) của `dbo_T_Nhap_<BP>` / `dbo_T_Xuat_<BP>`, **không lọc** (kể cả dòng xuất có NguoiLoaiBo); chỉ Machuan có trong Q_DMHC_<BP>HH (qua Q_Lienket / Q_Lienket2). Tồn ≤ 0 bỏ qua, âm → báo cáo.
- Mỗi Maxn = 1 ống = 1 dòng `standard_imports`. **code = Maxn giữ nguyên** (nhãn đã in; Maxn = SPQL; có cả đuôi "w", "QCCHC…5.1", "AD…" là ống chuyển từ bộ phận khác). seq_year/seq_no tách từ mã → STT ống mới trên WMS chạy tiếp; ống mới sinh theo `deparments.shortName` ("QC-B1IMP26101725") nên không trùng mã cũ. group_code theo Loaichuan, **CG → PRS**.
- **amount = Soluongnhap GỐC của Access**; ống đang dùng dở (có xuất) thêm **đúng 1 dòng `standard_exports`** (type `export`, code = mã ống, `reason` "Tổng xuất SMS 2.0: n lần, ngày đầu - ngày cuối", `created_at` = ngày xuất cuối, không gắn đề nghị / tổ) = Σ xuất → tồn WMS = nhập − tổng xuất = tồn Q_Ton; tỉ lệ đã dùng, "sắp hết", độ lệch KSKL tính trên lượng nhập ban đầu (người dùng chốt 26/10/2026, thay cho cách cũ amount = tồn hiện tại).
- Đã kiểm tra Đạt (`is_checked = 1`, `check_result = passed`; để chờ kiểm tra thì tồn bị kẹt), giữ Ngaynhap / Nguoinhap gốc (ngoại lệ có chủ đích với quy tắc "ngày thao tác = now()").
- `Soluongnhap` Access đã theo **ĐVT danh mục phòng**; `DVTQC` chỉ là đơn vị quy cách ống (lọ 110 mg) → ghi note, không quy đổi. Dòng phòng gộp mg/g mà mã Access của lô khác ĐVT → quy đổi theo `units.factor_to_base`.
- Hạn: CheckOnline / Hansudung ≥ 9000 → `check online`; Retest → `retest` (chu kỳ trống); còn lại `Specify`. HDhienthi sớm hơn Hansudung → `internal_expired_date`; Monap mà chưa có hạn nội bộ → báo cáo (người dùng bấm "Xác định hạn dùng nội bộ" ở Tồn Kho).
- KSKL: `weight_controlled` = CanBi, `standard_form` = ThuHinh ("Dạng Bột Min" → "Dạng Bột Mịn"). **Ống đã cân bì đầu ở SMS** (KLBiChuan > 0, đơn vị mg như WMS) → `gross_weight_before` = KLBiChuan (WMS không bắt cân lại ở lần dùng đầu); KLBi > 0 và ≤ KLBiChuan → `tare_weight_after`. Chuẩn ĐVT thể tích (ml) cân theo mg → ghi cột `d` của Access (bỏ d = 1 là mặc định, nhiều giá trị lấy nhiều nhất) vào `standard_categories.density` khi đang trống, kèm lịch sử "Cập nhật" ghi chú "Nạp từ SMS 2.0 - tỉ trọng KSKL" (chạy lại thì trả về trống rồi ghi lại); thiếu d → script in cảnh báo.
- `requires_aliquot` = TrietOng, `stability_batch` = DOD_CTC, `batch_no` = Solo, `coa_no` = PKN, `potency` = HL, `moisture` = LOD (NA/NAN → trống), `location_id` = định khu mặc định của dòng phòng. `Mucdichsudung` tách dấu phẩy → Dữ liệu gốc **Chỉ tiêu kiểm** (`purposes`, khớp theo collation "hoà" = "hòa", thiếu thì tạo), `purpose_id` = JSON id. MaNCC / Sohoadon / SoHS / LinkCoA đều trống ở Access.

### Đánh giá hạn dùng CTC (bước 4) - nguồn `dbo_T_DS_DOD_<BP>` + `dbo_T_DOD_item_<BP>`
- 1 dòng DS_DOD = 1 phiếu, gắn ống `standard_imports.code = Maxn` cùng phòng; ống không có trên WMS (dùng hết, bước 3 không nạp) → bỏ qua + báo cáo (26/10/2026: 3 phiếu "Kết Thúc Đánh Giá").
- `start_date` giữ nguyên; chu kỳ = bước SoThang hay gặp nhất (Access không lưu), 1 mốc → 3 tháng. Mốc: "N Tháng", timepoint = SoThang, `due_date` lấy nguyên Access. SMS 2.0 chỉ có mốc từ lúc dùng phần mềm (phiếu bắt đầu 2024 chỉ có mốc 24-27 tháng) - giữ nguyên, không tự sinh mốc cũ.
- `tests` tách dấu phẩy, mảnh "6S" / "8R Isomer" nối về chỉ tiêu trước ("Giới hạn 4R, 6S, 8R Isomer"), sửa "DĐịnh lượng"; tên phải có trong `purposes` (thiếu thì tạo, ghi chú "Nạp từ SMS 2.0 (Chỉ tiêu đánh giá hạn dùng DOD).") vì controller chặn chỉ tiêu ngoài Dữ liệu gốc khi sửa mốc. DaCapPhat → mọi chỉ tiêu `issued` + note "Cấp phát ngày …", `issured` = 1.
- HoanThanh → "Đạt" (Access không có Không Đạt; result "NA" vẫn Đạt + báo cáo); `done_at` = ngày lớn nhất đọc được trong chuỗi tự do Done_date (bỏ ngày < start_date, vd "18.08.06" gõ nhầm); chuỗi gốc + GhiChu/GhiChu2 (bỏ "1. NA") → note.
- Trạng thái phiếu tính theo mốc như controller; "Kết Thúc Đánh Giá" còn mốc chưa làm → Dừng Đánh Giá. Người tạo / ngày / trạng thái gốc → nhật ký "Lập phiếu".
- Lần nạp 26/10/2026: 45 phiếu (QC1 36, QC2 9), 211 mốc (31 đã đánh giá), 24 chỉ tiêu kiểm mới; 31 Đang Đánh Giá, 14 Ban Đầu.

## 3. Bẫy dữ liệu đã gặp

- SMS_2.0_L.accdb chỉ là front-end: bảng là LINK tới `\\stellapharm.int\share-bd\QLHC\Database_SMS\` (General_Database, Database_QC/QC1/QC2/AD/PROC .accdb) → máy chạy phải vào được ổ mạng.
- `OleDbConnection.GetSchema()` / `GetOleDbSchemaTable` với file này **crash AccessViolation** → liệt kê bảng / đọc SQL query bằng DAO: `New-Object -ComObject DAO.DBEngine.120` → `OpenDatabase(file,$false,$true)` → `TableDefs` / `QueryDefs[i].SQL`.
- Query dùng `Nz()` / hàm VBA (`SoPhanNhom`, `GHKL`, form `[Forms]!…`) không SELECT được qua OLEDB → viết lại SQL trên bảng gốc (export_access.ps1 làm sẵn).
- OLEDB giữ khoá file chép sau `Close()` → `ReleaseObjectPool()`, xoá không được thì bỏ qua.
- `-File script.ps1 -Q a,b` không tách mảng → truyền chuỗi rồi `-split ','`. File .ps1 có tiếng Việt lưu UTF-8 **có BOM** (Edit tool có thể làm mất BOM - kiểm `head -c3 | od` ra `ef bb bf`).
- Access so khớp Maxn không phân biệt hoa/thường; mã gõ lệch chuẩn (QC1CHC260100065 STT 5 số).
- `Tenchuan` nhiều dòng, nhiều tên đồng nghĩa cách "; \n" (≤ 255 ký tự sau khi gom).

## 4. Kiểm tra sau khi nạp

- Số dòng / tổng tồn từng phòng: `SELECT department_id, COUNT(*), SUM(amount) FROM standard_imports GROUP BY department_id` so với số script in ra (chênh lệch chỉ do quy đổi mg/g, xem cột "Cần rà").
- STT ống mới chạy tiếp: `App\Support\StandardCode::previews(20, 'QC-B1', date('Y-m-d'))`.
- Dựng thử màn hình không cần đăng nhập: bootstrap Laravel, đặt `session('user')` như `LoginController` (userId, userName, fullName, userGroup, department, department_id, selected_department, selected_department_id, company_id…), `view()->share('errors', new ViewErrorBag)`, gọi `StandardCategoryController::index()` / `StandardInventoryController::index($request)`; thêm `?_deferred=1` rồi đọc `App\Support\DeferredRows::payload()` để đếm dòng từng bảng.
- Màn Danh Mục Chất Chuẩn (2 tab), Tên Chuẩn đã dùng `@deferredRows` (bảng lớn); bộ lọc Phân nhóm chuẩn (`pages.shared.standardGroupFilter`) đọc dòng nạp sau qua `mdDeferred.rowAttr`.

## 5. Thêm bộ phận mới / việc còn treo

- Thêm bộ phận (QC → ?, AD → ?): hỏi người dùng phòng WMS, thêm vào `SMS2['departments']` (id, warehouse_code, warehouse_name, zone_table = dbo_T_DinhKhu3 / dbo_T_DinhKhu4), chạy bước 0 với `-Departments "QC1,QC2,QC,AD"` rồi 2 → 3. Kiểm lại cách tách mã định khu (QC dạng `086.1A`, AD dạng `C-T1.01`) trước khi chạy thật.
- Lần nạp 26/10/2026: danh mục công ty 3.843 dòng (2.430 tên, 110 NSX mới); QC1 1.951 dòng phòng (61 không định khu) + 2.217 ống / 1.013 phiếu tổng xuất (tồn 466.574,44 theo Access; 536.504,44 trên WMS do 1 lô 70 g → 70.000 mg); QC2 528 dòng (14 không định khu) + 604 ống / 263 phiếu tổng xuất (558.831,81 khớp); 349 ống chép KL Bì + Chuẩn, 5 ống chép KL Bì; 411 vị trí / 18 tủ; 37 chỉ tiêu kiểm mới.
- Màn Sử Dụng Chất Chuẩn ~19 MB vì ô chọn mã ống (2 ô × 2.218) và danh mục (2 ô × 3.844) in sẵn option → nên chuyển sang tìm AJAX (data-lookup-url, xem CategoryLookup) - chưa làm.
- Độ lệch KSKL đã sửa theo Q_ChuanKSKL của Access (26/10/2026, `App\Support\StandardWeight`): KL thực = Bì+Chuẩn − Bì sau dùng (ĐVT ml: /1000/d), chỉ tính khi đã cân bì sau dùng; ống dùng dở hiện "Đang sử dụng - chưa cân bì sau dùng". Sau khi nạp: QC1 chỉ 2 ống tính được (1 ngoài giới hạn thật: QC1IMP26010251 7,82 mg vs 4,9 mg - Access cũng ra 59,6%).
- 3 danh mục ĐVT ml có KSKL thiếu tỉ trọng d (S00269, S01770, S02519 - Access chỉ có d = 1 mặc định) → người dùng khai ở Danh Mục Chất Chuẩn.
- Chờ người dùng: 71 ống đã mở nắp chưa có hạn dùng nội bộ, 14 ống quá hạn còn tồn, 26 dòng phòng dùng Ver cũ, định khu lệch / chưa dựng (xem 3 CSV).
