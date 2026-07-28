<?php

namespace App\Models\masterData\DocumentRouting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRoutingStepModel extends Model
{
    use HasFactory;

    protected $table = 'document_routing_steps';

    const STATUS_PENDING  = 'pending';
    const STATUS_RECEIVED = 'received';

    protected $fillable = [
        'routing_id',
        'step_no',
        'from_user_id',
        'from_user_name',
        'to_user_id',
        'to_user_name',
        'to_department_id',
        'handover_date',
        'received_date',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'received_date' => 'date',
    ];

    public function routing()
    {
        return $this->belongsTo(DocumentRoutingModel::class, 'routing_id');
    }
}
