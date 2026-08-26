<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'item_code',
        'item_name',
        'pct_code',
        'quantity',
        'unit_price',
        'discount',
        'sale_value',
        'tax_rate',
        'tax_charged',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'double',
        'unit_price' => 'double',
        'discount' => 'double',
        'sale_value' => 'double',
        'tax_rate' => 'double',
        'tax_charged' => 'double',
        'total_amount' => 'double',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
