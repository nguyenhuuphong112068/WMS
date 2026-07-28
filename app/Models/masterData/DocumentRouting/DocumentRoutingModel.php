<?php

namespace App\Models\masterData\DocumentRouting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRoutingModel extends Model
{
    use HasFactory;

    protected $table = 'document_routings';

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_CANCELLED   = 'cancelled';

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'created_by_user_id',
        'receiver_user_id',
        'receiver_name',
        'received_date',
        'status',
        'current_step',
        'current_holder_user_id',
        'current_holder_name',
        'final_location_id',
        'completed_at',
        'completed_by',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'received_date' => 'date',
        'completed_at'  => 'datetime',
    ];

    public function steps()
    {
        return $this->hasMany(DocumentRoutingStepModel::class, 'routing_id')->orderBy('step_no');
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\masterData\Location\LocationModel::class, 'final_location_id');
    }
}
