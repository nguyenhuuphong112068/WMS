"""Bóc database/data/nd24_2026_appendices.csv từ file Excel 4 (5) sheet của Nghị định 24/2026/NĐ-CP.

    python nd24_2026_appendices.gen.py "C:/.../Nghi-dinh-24-2026-ND-CP_4-Phu-Luc.xlsx"

Chỉ dùng thư viện chuẩn (zipfile + xml.etree) - không cần openpyxl.

Sheet dùng: 'Phu luc I' .. 'Phu luc IV' (sheet 'Tat ca 4 Phu luc' là bản gộp, bỏ qua).
Cột nguồn (0-based): 0 STT | 1 name_en | 2 name_vi | 3 CAS | 4 công thức |
                     5 Ngưỡng | 6 Phụ lục(1-4) | 7 Nhóm | 8 Bảng | 9 Loại

Xử lý:
  - Phụ lục 1..4  -> I/II/III/IV
  - PL II  : mọi dòng là đơn chất -> group_no=1, table_ref='' (nhóm hình-1 số 1)
  - PL III : group_no = cột Nhóm (1|2), table_ref = cột Bảng (A|B|C)
  - PL IV  : group_no='', table_ref='A'
  - PL I   : group_no='', table_ref=''  (không thuộc nhóm hình 1)
  - CAS giữ lại nếu khớp ^\\d{2,7}-\\d{2}-\\d$, ngược lại để rỗng
  - Bỏ dòng: tên rỗng; mảnh câu ("Ví dụ", "Ngoại trừ:", "và các muối..."); tên kết thúc ':'
    mà không có CAS; đoạn mô tả dài > 120 ký tự không CAS
  - Gộp biến thể đồng phân cùng tên trong cùng bộ (phụ lục, nhóm, bảng) - giữ dòng đầu
"""
import zipfile, xml.etree.ElementTree as ET, re, csv, sys

NS  = "{http://schemas.openxmlformats.org/spreadsheetml/2006/main}"
NSR = "{http://schemas.openxmlformats.org/officeDocument/2006/relationships}"
CAS_RE = re.compile(r"^\d{2,7}-\d{2}-\d$")
APX = {"1": "I", "2": "II", "3": "III", "4": "IV"}
NULL_F = {"", "-", "--", "---", "----", "\u2014", "\u2013", "n/a", "na"}
FRAG = {
    "ví dụ", "e.g", "e.g.", "e. g", "ngoại trừ:", "exemptions:", "exemption:",
    "ngoại trừ: fonofos:",
    "và các muối proton hóa tương ứng", "and corresponding protonated salts",
}
OUT = __file__.rsplit(".gen.py", 1)[0] + ".csv" if __file__.endswith(".gen.py") else "nd24_2026_appendices.csv"


def load(path):
    z = zipfile.ZipFile(path)
    shared = []
    if "xl/sharedStrings.xml" in z.namelist():
        for si in ET.fromstring(z.read("xl/sharedStrings.xml")).findall(f"{NS}si"):
            shared.append("".join(t.text or "" for t in si.iter(f"{NS}t")))
    wb = ET.fromstring(z.read("xl/workbook.xml"))
    rels = {r.get("Id"): r.get("Target")
            for r in ET.fromstring(z.read("xl/_rels/workbook.xml.rels"))}
    sheets = [(s.get("name"), s.get(f"{NSR}id"))
              for s in wb.find(f"{NS}sheets").findall(f"{NS}sheet")]

    def cidx(col):
        n = 0
        for ch in col:
            n = n * 26 + (ord(ch) - 64)
        return n - 1

    def cval(c):
        t, v = c.get("t"), c.find(f"{NS}v")
        if t == "s":
            return shared[int(v.text)] if v is not None else ""
        if t == "inlineStr":
            node = c.find(f"{NS}is")
            return "".join(tn.text or "" for tn in node.iter(f"{NS}t")) if node is not None else ""
        return v.text if v is not None else ""

    def rows_of(tgt):
        if not tgt.startswith("xl/"):
            tgt = "xl/" + tgt
        data = ET.fromstring(z.read(tgt)).find(f"{NS}sheetData")
        out = []
        for row in data.findall(f"{NS}row"):
            cells = {}
            for c in row.findall(f"{NS}c"):
                cells[cidx(re.match(r"[A-Z]+", c.get("r")).group(0))] = (cval(c) or "").strip()
            mx = max(cells) if cells else -1
            out.append([cells.get(i, "") for i in range(mx + 1)])
        return out

    return {name: rows_of(rels[rid]) for name, rid in sheets}


def clean_formula(f):
    f = (f or "").strip()
    return "" if f.casefold() in NULL_F else f


def build(sheets):
    records, seen = [], {}
    for name in ("Phu luc I", "Phu luc II", "Phu luc III", "Phu luc IV"):
        for r in sheets.get(name, [])[1:]:
            g = lambda i: (r[i].strip() if len(r) > i and r[i] is not None else "")
            name_en, name_vi = g(1), g(2)
            cas_raw, formula = g(3), clean_formula(g(4))
            apx = APX.get(g(6), "")
            nhom, bang, loai = g(7), g(8), g(9)
            if not apx or not name_vi:
                continue
            cas = cas_raw if CAS_RE.match(cas_raw) else ""
            low = name_vi.casefold()
            if low in FRAG or low.startswith("và ") or low.startswith("and "):
                continue
            if name_vi.endswith(":") and not cas:
                continue
            if not cas and len(name_vi) > 120:
                continue
            if apx == "II":
                group_no, table_ref = "1", ""
            elif apx == "III":
                group_no, table_ref = nhom, bang
                # Bảng tính gốc để các chất công ước Rotterdam/Stockholm (POP) ở
                # "Bảng B / Loại 3C"; nghị định gọi đó là "Bảng C" (nhóm 2) -> nhóm hình-1 số 7.
                if group_no == "2" and table_ref == "B" and loai.strip().upper() == "3C":
                    table_ref = "C"
                if group_no not in ("1", "2") or table_ref not in ("A", "B", "C"):
                    continue
            elif apx == "IV":
                group_no, table_ref = "", "A"
            else:
                group_no, table_ref = "", ""
            threshold = ""
            if apx == "IV":
                t = g(5).replace(",", "").replace(" ", "")
                if re.match(r"^\d+(\.\d+)?$", t):
                    threshold = t
            key = (apx, group_no, table_ref, low)
            if key in seen:
                rec = records[seen[key]]
                if not rec["cas"] and cas:
                    rec["cas"] = cas
                if not rec["formula"] and formula:
                    rec["formula"] = formula
                if not rec["threshold_kg"] and threshold:
                    rec["threshold_kg"] = threshold
                continue
            seen[key] = len(records)
            records.append(dict(name_vi=name_vi, name_en=name_en, cas=cas, formula=formula,
                                appendix=apx, group_no=group_no, table_ref=table_ref,
                                threshold_kg=threshold, loai=loai))
    order = {"I": 0, "II": 1, "III": 2, "IV": 3}
    records.sort(key=lambda x: (order[x["appendix"]], x["name_vi"].casefold()))
    return records


def main():
    if len(sys.argv) < 2:
        sys.exit("Cần đường dẫn file .xlsx nguồn.")
    recs = build(load(sys.argv[1]))
    with open(OUT, "w", encoding="utf-8", newline="") as f:
        w = csv.writer(f)
        w.writerow(["name_vi", "name_en", "cas", "formula", "appendix",
                    "group_no", "table_ref", "threshold_kg", "loai"])
        for x in recs:
            w.writerow([x["name_vi"], x["name_en"], x["cas"], x["formula"], x["appendix"],
                        x["group_no"], x["table_ref"], x["threshold_kg"], x["loai"]])
    print(f"{len(recs)} dòng -> {OUT}")


if __name__ == "__main__":
    main()
