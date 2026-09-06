<?php

namespace App\Models\masterData\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationModel extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $fillable = [
        'code',
        'warehouse_id',
        'shelf_id',
        'column_id',
        'tier_id',
        'status_id',
        'created_by',
    ];

    public function tier()
    {
        return $this->belongsTo(\App\Models\masterData\Tier\TierModel::class, 'tier_id');
    }
}
