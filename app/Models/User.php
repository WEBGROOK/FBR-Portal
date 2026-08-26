<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'pos_id',
        'seller_ntn',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function batches()
    {
        return $this->hasMany(InvoiceBatch::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
