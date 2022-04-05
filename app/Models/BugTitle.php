<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BugTitle extends Model
{
    use HasFactory;

    protected $title = 'bug_titles';

    protected $fillable = [
        'bugId',
        'title'
    ];

    // 'bugId' is the foreign key in 'bug_titles' table.
    public function bug(){
        return $this->belongsTo(ModuleBug::class, 'bugId');
    }
}
