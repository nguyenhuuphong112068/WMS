<?php
require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$filePath = 'C:\Users\QA2-Phongnh\Desktop\Quản lý vật tư kỹ thuật.xlsm';
$reader = new Xlsx();
$reader->setReadDataOnly(true);
$reader->setIncludeCharts(false);
// Removed setLoadSheetsOnly to avoid broken references

try {
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getSheetByName('Danh muc');
    
    // Disable calculateFormulas -> 2nd parameter false
    $rows = $sheet->toArray(null, false, false, true);
    
    $count = 0;
    foreach ($rows as $rowIndex => $row) {
        echo "Row $rowIndex: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        $count++;
        if ($count >= 5) break;
    }
} catch (Exception $e) {
    echo "Error loading file: " . $e->getMessage();
}
