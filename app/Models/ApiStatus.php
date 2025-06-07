<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiStatus extends Model
{
    protected $fillable = ['api_name', 'working_key', 'generated_at'];

    public $timestamps = true;
}
