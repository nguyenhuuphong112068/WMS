<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

$filePath = 'C:\Users\QA2-Phongnh\Desktop\Quản lý vật tư kỹ thuật.xlsm';
$reader = new Xlsx();
$reader->setReadDataOnly(true);
$reader->setIncludeCharts(false);

echo "Loading file...\n";
$spreadsheet = $reader->load($filePath);
$sheet = $spreadsheet->getSheetByName('Danh muc');

$rows = $sheet->toArray(null, false, false, true);

echo "Processing rows...\n";

// Get existing names for quick checking, although insertOrIgnore handles DB duplicates
$existingNames = DB::table('material_names')->pluck('name')->map(function($name) {
    return mb_strtolower(trim($name), 'UTF-8');
})->toArray();

$newNames = [];
$seenInThisRun = [];
$now = Carbon::now();

foreach ($rows as $rowIndex => $row) {
    if ($rowIndex === 1) continue; // Skip header
    
    $name = isset($row['B']) ? trim($row['B']) : '';
    if (empty($name)) continue;
    
    $lowerName = mb_strtolower($name, 'UTF-8');
    
    if (!in_array($lowerName, $existingNames) && !in_array($lowerName, $seenInThisRun)) {
        $seenInThisRun[] = $lowerName;
        
        $newNames[] = [
            'name' => $name,
            'app_status' => 'approved', 
            'status_id' => 1,
            'created_by' => 'Hệ thống (Import)',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}

if (!empty($newNames)) {
    echo "Inserting " . count($newNames) . " new materials...\n";
    $chunks = array_chunk($newNames, 100);
    $inserted = 0;
    foreach ($chunks as $chunk) {
        $inserted += DB::table('material_names')->insertOrIgnore($chunk);
    }
    echo "Done inserting. Actually inserted: $inserted\n";
} else {
    echo "No new materials to insert (all duplicates or empty).\n";
}
