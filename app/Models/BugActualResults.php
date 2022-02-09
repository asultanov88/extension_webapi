<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BugActualResults extends Model
{
    use HasFactory;

    protected $table='bug_actual_result';

    protected $fillable=[
        'bugId',
        'actualResults',
    ];
}
