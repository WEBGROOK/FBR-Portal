<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'batch_id',
        'user_id',
        'invoice_number',
        'invoice_date',
        'seller_ntn',
        'seller_name',
        'seller_pos_id',
        'buyer_ntn',
        'buyer_name',
        'buyer_cnic',
        'buyer_phone',
        'payment_mode',
        'total_sale_value',
        'total_quantity',
        'total_tax_amount',
        'discount',
        'further_tax',
        'total_bill',
        'validation_status',
        'validation_errors',
        'fbr_status',
        'fbr_invoice_number',
        'retry_count',
        'last_error',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'validation_errors' => 'array',
        'total_sale_value' => 'double',
        'total_quantity' => 'double',
        'total_tax_amount' => 'double',
        'discount' => 'double',
        'further_tax' => 'double',
        'total_bill' => 'double',
        'retry_count' => 'integer',
    ];

    public function batch()
    {
        return $this->belongsTo(InvoiceBatch::class, 'batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function submissions()
    {
        return $this->hasMany(FbrSubmission::class, 'invoice_id');
    }

    public function fbrResponses()
    {
        return $this->hasMany(FbrResponse::class, 'invoice_id');
    }
}
