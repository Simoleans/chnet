<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = ['name', 'status'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
