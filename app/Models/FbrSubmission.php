<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbrSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_id',
        'batch_id',
        'submission_time',
        'status',
        'request_payload',
        'response_payload',
        'http_status',
        'error_code',
        'error_message',
        'fbr_invoice_number',
        'idempotency_key',
    ];

    protected $casts = [
        'submission_time' => 'datetime',
        'http_status' => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function batch()
    {
        return $this->belongsTo(InvoiceBatch::class, 'batch_id');
    }

    public function responses()
    {
        return $this->hasMany(FbrResponse::class, 'submission_id');
    }
}
