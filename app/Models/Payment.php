<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'invoice_id',
        'reference',
        'id_number',
        'bank',
        'phone',
        'payment_date',
        'amount',
        'image_path',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];


    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
