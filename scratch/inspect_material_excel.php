<?php
require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'C:\Users\QA2-Phongnh\Desktop\Quản lý vật tư kỹ thuật.xlsm';
$spreadsheet = IOFactory::load($filePath);

$sheet = $spreadsheet->getSheetByName('Danh muc');
if (!$sheet) {
    echo "'Danh muc' sheet not found.\n";
    exit(1);
}

// Read first 5 rows to identify headers
$rows = $sheet->toArray(null, true, true, true);
$count = 0;
foreach ($rows as $rowIndex => $row) {
    echo "Row $rowIndex: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    $count++;
    if ($count >= 5) break;
}
