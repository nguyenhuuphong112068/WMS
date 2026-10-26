<#
  Xuất dữ liệu chất chuẩn SMS 2.0 (Access) ra JSON để script PHP nạp vào WMS.

  .\export_access.ps1 -Source "Z:\SMS_2.0_L.accdb" -OutDir "<thư mục tạm>"

  - SMS_2.0_L.accdb chỉ là file giao diện: các bảng dbo_T_* là bảng LINK tới
    \\stellapharm.int\share-bd\QLHC\Database_SMS\*.accdb -> máy chạy phải truy cập được ổ mạng đó.
  - Chép file ra OutDir rồi đọc bản chép (file gốc hay bị người khác mở độc quyền).
  - Không dùng GetSchema()/GetOleDbSchemaTable với file này: ACE OLEDB bị AccessViolation.
    Muốn xem SQL của query thì dùng DAO (DAO.DBEngine.120 -> QueryDefs).
  - Cần ACE OLEDB 12.0 64-bit (Windows PowerShell 5.1 64-bit chạy được).

  File ra:
    dmhc_chung.json : Q_DMHC_ChungHH (danh mục chuẩn chung, phiên bản MHC lớn nhất của từng Machuan)
    nguongoc.json   : dbo_T_Nguongoc (Nguồn gốc viết tắt -> TenDayDu)
    dmhc_dept.json  : danh mục từng bộ phận = SQL của Q_DMHC_<BP>HH (phiên bản MHC lớn nhất của từng machuan
                      trong dbo_T_DMHC_Phong lọc Bophan); join thêm Machuan để không nhân dòng khi 2 mã trùng MHC
    dinhkhu.json    : bảng định khu từng bộ phận (MaViTri -> Tu), cột Bophan
    nhap.json       : dbo_T_Nhap_<BP> (mỗi Maxn = 1 mã ống chuẩn), cột Dept
    xuat_sum.json   : Σ xuất theo Maxn của dbo_T_Xuat_<BP> (TongXuat, SoLanXuat, NgayXuatDau, NgayXuatCuoi), cột Dept
    dod_list.json   : dbo_T_DS_DOD_<BP> (phiếu đánh giá hạn dùng CTC: Maxn, start_date, status...), cột Dept
    dod_item.json   : dbo_T_DOD_item_<BP> (mốc đánh giá: SoThang, due_date, tests, result, Done_date...), cột Dept
#>
param(
    [string]$Source = 'Z:\SMS_2.0_L.accdb',
    [Parameter(Mandatory = $true)][string]$OutDir,
    # Bộ phận => bảng định khu Access của bộ phận đó. Truyền chuỗi "QC1,QC2" (mảng qua -File không tách được).
    [string]$Departments = 'QC1,QC2'
)
$zoneTables = @{ 'QC1' = 'dbo_T_DinhKhu'; 'QC2' = 'dbo_T_DinhKhu2'; 'QC' = 'dbo_T_DinhKhu3'; 'AD' = 'dbo_T_DinhKhu4' }

$ErrorActionPreference = 'Stop'
New-Item -ItemType Directory -Force $OutDir | Out-Null
$copy = Join-Path $OutDir 'SMS_copy.accdb'
Copy-Item $Source $copy -Force

$c = New-Object System.Data.OleDb.OleDbConnection "Provider=Microsoft.ACE.OLEDB.12.0;Data Source=$copy;Mode=Read"
$c.Open()

function Read-Query([string]$sql, [hashtable]$extra = @{}) {
    $cmd = $c.CreateCommand(); $cmd.CommandText = $sql
    $da = New-Object System.Data.OleDb.OleDbDataAdapter $cmd
    $t = New-Object System.Data.DataTable; [void]$da.Fill($t)
    $rows = foreach ($r in $t.Rows) {
        $h = [ordered]@{}
        foreach ($k in $extra.Keys) { $h[$k] = $extra[$k] }
        foreach ($col in $t.Columns) {
            $v = $r[$col]
            if ($v -is [DBNull]) { $v = $null } elseif ($v -is [datetime]) { $v = $v.ToString('yyyy-MM-dd') }
            $h[$col.ColumnName] = $v
        }
        [pscustomobject]$h
    }
    return @($rows)
}

function Save-Json($rows, [string]$file) {
    [IO.File]::WriteAllText((Join-Path $OutDir $file), (ConvertTo-Json -InputObject @($rows) -Depth 3), (New-Object Text.UTF8Encoding $false))
    "{0,-16} {1,6} dòng" -f $file, @($rows).Count
}

Save-Json (Read-Query 'SELECT * FROM Q_DMHC_ChungHH ORDER BY Machuan') 'dmhc_chung.json'
Save-Json (Read-Query 'SELECT NSX, TenDayDu, Active FROM dbo_T_Nguongoc') 'nguongoc.json'

$dept = @(); $zones = @(); $nhap = @(); $xuat = @(); $dodList = @(); $dodItem = @()
foreach ($b in ($Departments -split ',')) {
    $sql = "SELECT d.* FROM (SELECT Machuan, Max(MHC) AS MaxOfMHC FROM dbo_T_DMHC_Phong WHERE Bophan='$b' GROUP BY Machuan) AS v " +
           "INNER JOIN dbo_T_DMHC_Phong AS d ON (v.MaxOfMHC = d.MHC AND v.Machuan = d.Machuan) ORDER BY d.Machuan"
    $dept += Read-Query $sql
    if ($zoneTables[$b]) { $zones += Read-Query "SELECT MaViTri, Tu FROM $($zoneTables[$b])" @{ Bophan = $b } }
    # Q_Ton_<BP> (Q_Ton_QC1 / Q_Ton_QC2): tồn lô Maxn = Σ Round(Soluongnhap,3) - Σ Round(Soluongxuat,3), không lọc gì
    # (kể cả dòng xuất có NguoiLoaiBo). Lấy thô rồi cộng ở PHP; Q_Ton dùng Nz() nên không SELECT thẳng được.
    $nhap += Read-Query "SELECT * FROM dbo_T_Nhap_$b" @{ Dept = $b }
    $xuat += Read-Query ("SELECT Maxn, Round(Sum(Round(Soluongxuat,3)),4) AS TongXuat, Count(*) AS SoLanXuat, " +
                         "Min(Ngayxuat) AS NgayXuatDau, Max(Ngayxuat) AS NgayXuatCuoi FROM dbo_T_Xuat_$b GROUP BY Maxn") @{ Dept = $b }
    # Đánh giá hạn dùng CTC (DOD): phiếu + mốc. dbo_T_DOD_item_update_<BP> chỉ là nhật ký lần cập nhật -> không lấy.
    $dodList += Read-Query "SELECT * FROM dbo_T_DS_DOD_$b ORDER BY ID" @{ Dept = $b }
    $dodItem += Read-Query "SELECT * FROM dbo_T_DOD_item_$b ORDER BY Maxn, SoThang, ID" @{ Dept = $b }
}
Save-Json $dept 'dmhc_dept.json'
Save-Json $zones 'dinhkhu.json'
Save-Json $nhap 'nhap.json'
Save-Json $xuat 'xuat_sum.json'
Save-Json $dodList 'dod_list.json'
Save-Json $dodItem 'dod_item.json'

$c.Close()
# OLEDB giữ khoá file thêm một lúc sau Close -> nhả pool rồi xoá, không xoá được cũng không sao
[System.Data.OleDb.OleDbConnection]::ReleaseObjectPool()
try { Remove-Item $copy -Force -ErrorAction Stop } catch { "(Chưa xoá được bản chép $copy - xoá tay sau)" }
