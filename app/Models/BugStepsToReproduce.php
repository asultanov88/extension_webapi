<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BugStepsToReproduce extends Model
{
    use HasFactory;

    protected $table = 'bug_steps_to_reproduce';

    protected $fillable = [
        'bugId',
        'stepsToReproduce',
    ];
}
