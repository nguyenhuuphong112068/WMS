# database/data

Dữ liệu tra cứu dạng bảng, nạp vào CSDL bởi migration (không phải seeder Eloquent).

## nd24_2026_appendices.csv

4 phụ lục của **Nghị định 24/2026/NĐ-CP** (danh mục hoá chất thuộc phạm vi điều chỉnh
của Luật Hoá chất). Nạp bởi `database/migrations/2026_09_05_100000_seed_appendix_ii_iii_classifications.php`
vào `active_ingredients` + `active_ingredient_classifications`.

| Cột | Ý nghĩa |
|---|---|
| `name_vi` | Tên hoá chất (tiếng Việt, đúng như trong nghị định) — khoá đối chiếu phụ khi thiếu CAS |
| `name_en` | Tên khoa học / danh pháp IUPAC |
| `cas` | Số CAS đã chuẩn hoá (`^\d{2,7}-\d{2}-\d$`); rỗng nếu văn bản không có / sai định dạng |
| `formula` | Công thức hoá học ASCII (migration tự hạ chỉ số qua `App\Support\ChemicalFormula`) |
| `appendix` | `I` \| `II` \| `III` \| `IV` |
| `group_no` | `1` \| `2` \| rỗng — "nhóm" trong phụ lục |
| `table_ref` | `A` \| `B` \| `C` \| rỗng — "bảng" trong phụ lục |
| `threshold_kg` | Ngưỡng tồn trữ (chỉ Phụ lục IV) |
| `loai` | Cột "Loại" gốc của bảng tính (chỉ để tham chiếu, ghi vào `note`) |

Ánh xạ sang 10 nhóm "hình 1" do `App\Support\ChemicalClassification::groupOf()` lo:
`(II,1,-)→1  (III,1,A)→3  (III,1,B)→4  (III,2,A)→5  (III,2,B)→6  (III,2,C)→7  (IV,-,A)→9`.
Phụ lục I không thuộc nhóm nào của hình 1 (vẫn lưu để giữ vết).

> Bảng tính gốc để các chất công ước **Rotterdam/Stockholm** (POP) ở "Bảng B / Loại 3C";
> nghị định gọi đó là **Bảng C** (nhóm 2) → nhóm 7. Script `.gen.py` tự đổi `table_ref` các
> dòng này thành `C` (52 chất). Loại 3A/3B (Công ước cấm vũ khí hoá học – lịch trình 3) vẫn
> là Bảng B → nhóm 6.

Số dòng theo bộ (appendix/group/table): I=39 · II/1=785 · III 1A=22 / 1B=134 / 2A=18 / 2B=17 / 2C=52 · IV/A=273.

### Cập nhật khi nghị định thay đổi

Sửa trực tiếp `nd24_2026_appendices.csv` rồi:

```
php artisan migrate:rollback --step=1
php artisan migrate
```

migration idempotent (khớp CAS → tên, chỉ bổ khuyết field trống, `updateOrInsert` dòng phân loại).

`nd24_2026_appendices.gen.py` là script bóc CSV này từ file Excel gốc 5 sheet
(`Nghi-dinh-24-2026-ND-CP_4-Phu-Luc.xlsx`) — chạy `python nd24_2026_appendices.gen.py <đường-dẫn-xlsx>`.
Chỉ dùng thư viện chuẩn (zipfile + xml), không cần cài openpyxl.
