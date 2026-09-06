<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Delete mappings first to avoid foreign key constraints
DB::table('role_permission')->where('permission_id', function($query) {
    $query->select('id')->from('permissions')->where('name', 'inventory_standard_balancing');
})->delete();

DB::table('user_permission')->where('permission_id', function($query) {
    $query->select('id')->from('permissions')->where('name', 'inventory_standard_balancing');
})->delete();

// Delete the permission itself
DB::table('permissions')->where('name', 'inventory_standard_balancing')->delete();

echo "Deleted successfully.";
