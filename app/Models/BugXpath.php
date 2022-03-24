<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuleBug;

class BugXpath extends Model
{
    use HasFactory;

    protected $table = 'bug_xpath';

    protected $fillable = [
        'bugId',
        'xpath',
    ];

    // 'bugId' is the foreign key in 'bug_xpath' table.
    public function bug(){
        return $this->belongsTo(ModuleBug::class, 'bugId');
    }

}
