<?php

namespace App\Models\masterData\Shelf;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShelfModel extends Model
{
    use HasFactory;

    protected $table = 'shelves';

    protected $fillable = [
        'code',
        'name',
        'warehouse_id',
        'status_id',
        'created_by',
    ];

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\masterData\Warehouse\WarehouseModel::class, 'warehouse_id');
    }

    public function columns()
    {
        return $this->hasMany(\App\Models\masterData\Column\ColumnModel::class, 'shelf_id');
    }
}
