<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceBatch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_type',
        'file_size',
        'total_count',
        'valid_count',
        'invalid_count',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'batch_id');
    }

    public function submissions()
    {
        return $this->hasMany(FbrSubmission::class, 'batch_id');
    }
}
