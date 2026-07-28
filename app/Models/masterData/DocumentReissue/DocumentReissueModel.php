<?php

namespace App\Models\masterData\DocumentReissue;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentReissueModel extends Model
{
    use HasFactory;

    protected $table = 'document_reissue_requests';

    // Quy trình: người xin ghi sổ -> QĐ/P.QĐ PXSX ký -> TP/PP. ĐBCL cho ý kiến -> bộ phận ban hành cấp lại
    const STATUS_PENDING_PM    = 'pending_pm';
    const STATUS_PENDING_QA    = 'pending_qa';
    const STATUS_PENDING_ISSUE = 'pending_issue';
    const STATUS_COMPLETED     = 'completed';
    const STATUS_REJECTED      = 'rejected';
    const STATUS_CANCELLED     = 'cancelled';

    const DECISION_AGREE    = 'agree';
    const DECISION_DISAGREE = 'disagree';

    protected $fillable = [
        'code',
        'submit_token',
        'department_id',
        'request_date',
        'product_name',
        'process_no',
        'edition',
        'pages',
        'reason',
        'capa',
        'status',
        'requester_user_id',
        'requester_name',
        'requested_date',
        'pm_user_id',
        'pm_name',
        'pm_signed_date',
        'pm_note',
        'qa_user_id',
        'qa_name',
        'qa_signed_date',
        'qa_decision',
        'qa_opinion',
        'issuer_user_id',
        'issuer_name',
        'issued_date',
        'issued_pages',
        'wrong_pages_voided',
        'issue_note',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'request_date'       => 'date',
        'requested_date'     => 'date',
        'pm_signed_date'     => 'date',
        'qa_signed_date'     => 'date',
        'issued_date'        => 'date',
        'wrong_pages_voided' => 'boolean',
    ];
}
