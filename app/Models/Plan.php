<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'price', 'type', 'mbps', 'status'];


    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
