<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BugDescription extends Model
{
    use HasFactory;

    protected $table='bug_description';

    protected $fillable=[
        'bugId',
        'description',
    ];
}
