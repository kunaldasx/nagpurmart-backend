<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = ['uuid', 'title', 'slug', 'type', 'visibility', 'description', 'metadata'];
    protected $casts = ['metadata' => 'array'];
}
