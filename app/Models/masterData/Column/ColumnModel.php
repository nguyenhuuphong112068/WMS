<?php

namespace App\Models\masterData\Column;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColumnModel extends Model
{
    use HasFactory;

    protected $table = 'columns';

    protected $fillable = [
        'code',
        'name',
        'warehouse_id',
        'shelf_id',
        'status_id',
        'created_by',
    ];

    public function shelf()
    {
        return $this->belongsTo(\App\Models\masterData\Shelf\ShelfModel::class, 'shelf_id');
    }

    public function tiers()
    {
        return $this->hasMany(\App\Models\masterData\Tier\TierModel::class, 'column_id');
    }
}
