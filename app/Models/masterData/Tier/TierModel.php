<?php

namespace App\Models\masterData\Tier;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TierModel extends Model
{
    use HasFactory;

    protected $table = 'tiers';

    protected $fillable = [
        'code',
        'name',
        'warehouse_id',
        'shelf_id',
        'column_id',
        'status_id',
        'created_by',
    ];

    public function column()
    {
        return $this->belongsTo(\App\Models\masterData\Column\ColumnModel::class, 'column_id');
    }

    public function locations()
    {
        return $this->hasMany(\App\Models\masterData\Location\LocationModel::class, 'tier_id');
    }
}
