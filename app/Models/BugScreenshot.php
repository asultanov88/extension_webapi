<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuleBug;

class BugScreenshot extends Model
{
    use HasFactory;

    protected $table = 'bug_screenshots';

    protected $fillable = [
        'id',
        'bugId',
        'screenshotPath',
    ];

    // 'bugId' is the foreign key in 'bug_screenshots' table.
    public function bug(){
        return $this->belongsTo(ModuleBug::class, 'bugId');
    }
}
