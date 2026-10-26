<#
  Xuất dữ liệu CMS 3.0 (Access) ra JSON để các script PHP nạp vào WMS.

  .\export_access.ps1 -Source "Z:\CMS_3.0_L.accdb" -OutDir "<thư mục tạm>" -Departments QC1,QC2

  - Luôn CHÉP file Access ra OutDir rồi đọc bản chép: file gốc thường bị người khác mở độc quyền
    ("placed in a state by user 'Admin'...").
  - OLEDB không có hàm VBA (GetNhomHC) và hàm Access Nz(): không SELECT các query dùng chúng
    (Q_DMHC_QC1HH, Q_Ton...) mà viết lại SQL trên bảng gốc, Nz(x,0) -> IIf(IsNull(x),0,x).
  - Cần ACE OLEDB 12.0 64-bit (Windows PowerShell 5.1 64-bit chạy được).

  File ra:
    dmhc_chung.json : Q_DMHC_ChungHH (danh mục chung, phiên bản MHC mới nhất của từng mã)
    nsx.json        : dbo_T_NSX (NSX viết tắt -> Tendaydu)
    ncc.json        : dbo_T_NCC (MaNCC -> tên, địa chỉ, MST)
    dmhc_dept.json  : danh mục từng bộ phận = SQL của Q_DMHC_<BP>HH không có GetNhomHC (cột Bophan)
    lots.json       : lô còn tồn = SQL của Q_Ton / Q_Ton2 (cột Dept, Ton, TongXuat)
#>
param(
    [string]$Source = 'Z:\CMS_3.0_L.accdb',
    [Parameter(Mandatory = $true)][string]$OutDir,
    [string[]]$Departments = @('QC1', 'QC2')
)

$ErrorActionPreference = 'Stop'
# Gọi qua -File thì "QC1,QC2" đến dưới dạng 1 chuỗi -> tách lại
$Departments = @($Departments | ForEach-Object { $_ -split ',' } | Where-Object { $_ })
New-Item -ItemType Directory -Force $OutDir | Out-Null
$copy = Join-Path $OutDir 'CMS_copy.accdb'
Copy-Item $Source $copy -Force

$c = New-Object System.Data.OleDb.OleDbConnection "Provider=Microsoft.ACE.OLEDB.12.0;Data Source=$copy;Mode=Read"
$c.Open()

function Export-Query([string]$sql, [string]$file, [hashtable]$extra = @{}, [scriptblock]$filter = $null) {
    $cmd = $c.CreateCommand(); $cmd.CommandText = $sql
    $da = New-Object System.Data.OleDb.OleDbDataAdapter $cmd
    $t = New-Object System.Data.DataTable; [void]$da.Fill($t)
    $rows = foreach ($r in $t.Rows) {
        if ($filter -and -not (& $filter $r)) { continue }
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

Save-Json (Export-Query 'SELECT * FROM Q_DMHC_ChungHH ORDER BY Mahoachat' 'dmhc_chung.json') 'dmhc_chung.json'
Save-Json (Export-Query 'SELECT NSX, Tendaydu, Active FROM dbo_T_NSX' 'nsx.json') 'nsx.json'
Save-Json (Export-Query 'SELECT * FROM dbo_T_NCC' 'ncc.json') 'ncc.json'

$dept = @(); $lots = @()
foreach ($b in $Departments) {
    # = Q_DMHC_<BP>HH bỏ GetNhomHC/Nz: phiên bản mới nhất (Max MHC) của từng mã trong dbo_T_DMHC_QC1 theo Mabophan
    $sql = "SELECT d.* FROM (SELECT Mahoachat, Max(MHC) AS MaxOfMHC FROM dbo_T_DMHC_QC1 WHERE Mabophan='$b' GROUP BY Mahoachat) AS v " +
           "INNER JOIN dbo_T_DMHC_QC1 AS d ON v.MaxOfMHC = d.MHC"
    $dept += Export-Query $sql '' @{ Bophan = $b }

    # = Q_Ton (QC1) / Q_Ton2 (QC2): tồn lô = Soluongnhap + SoLuongCanDoi - Σ Soluongxuat, chỉ dòng Het = False
    $sql = "SELECT N.*, IIf(IsNull(X.TongXuat),0,X.TongXuat) AS TongXuat, " +
           "Round(N.Soluongnhap + IIf(IsNull(N.SoLuongCanDoi),0,N.SoLuongCanDoi) - IIf(IsNull(X.TongXuat),0,X.TongXuat),3) AS Ton " +
           "FROM dbo_T_Nhap_$b AS N LEFT JOIN (SELECT Maxn, Sum(Soluongxuat) AS TongXuat FROM dbo_T_Xuat_$b WHERE Het = False GROUP BY Maxn) AS X " +
           "ON N.Maxn = X.Maxn WHERE N.Het = False"
    $lots += Export-Query $sql '' @{ Dept = $b } { param($r) $r.Ton -gt 0 }
}
Save-Json $dept 'dmhc_dept.json'
Save-Json $lots 'lots.json'

# Đối chiếu: Q_Ton2 (QC2) không dùng hàm VBA nên SELECT được trực tiếp - phải khớp số lô / tổng tồn ở trên
try {
    $cmd = $c.CreateCommand(); $cmd.CommandText = 'SELECT COUNT(*) AS n, Round(Sum(Ton),3) AS t FROM Q_Ton2 WHERE Ton > 0'
    $r = $cmd.ExecuteReader(); [void]$r.Read(); "Đối chiếu Q_Ton2: {0} lô, tổng tồn {1}" -f $r['n'], $r['t']; $r.Close()
} catch { "Không đối chiếu được Q_Ton2: $($_.Exception.Message)" }

$c.Close()
Remove-Item $copy -Force
