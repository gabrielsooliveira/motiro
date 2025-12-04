<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasUuids;

    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'email',
        'slug',
        'logo',
        'website',
        'description',
        'password',
        'is_active',
        'owner_id',
    ];
}
