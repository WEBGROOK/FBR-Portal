<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbrResponse extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'invoice_id',
        'status',
        'response_code',
        'response_message',
        'fbr_usin',
        'raw_response',
    ];

    public function submission()
    {
        return $this->belongsTo(FbrSubmission::class, 'submission_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
