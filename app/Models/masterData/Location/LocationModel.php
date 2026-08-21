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
        'name',
        'shelf_id',
        'status_id',
        'created_by',
    ];

    public function shelf()
    {
        return $this->belongsTo(\App\Models\masterData\Shelf\ShelfModel::class, 'shelf_id');
    }
}
