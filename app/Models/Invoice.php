<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'plan_id',
        'period',
        'amount_due',
        'amount_paid',
        'status',
    ];

    protected $casts = [
        'period' => 'date',
    ];

    //parent boot code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->code = 'FACT-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
        });
    }

    //payments belongs to invoice
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
