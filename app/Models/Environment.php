<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Environment extends Model
{
    use HasFactory;

    protected $primaryKey = 'environmentId';

    protected $table = 'environments';

    protected $fillable = [
        'environmentId',
        'clientId',
        'name',
    ];
}
