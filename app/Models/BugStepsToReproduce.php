<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuleBug;

class BugStepsToReproduce extends Model
{
    use HasFactory;

    protected $table = 'bug_steps_to_reproduce';

    protected $fillable = [
        'bugId',
        'stepsToReproduce',
    ];

    // 'bugId' is the foreign key in 'bug_steps_to_reproduce' table.
    public function bug(){
        return $this->belongsTo(ModuleBug::class, 'bugId');
    }
}
