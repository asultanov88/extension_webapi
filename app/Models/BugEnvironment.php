<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Environment;

class BugEnvironment extends Model
{
    use HasFactory;

    protected $table = 'bug_environments';

    protected $fillable = [
        'bugId',
        'environmentId'
    ];

    // 'bugId' is the foreign key in 'bug_environments' table.
    public function bug(){
        return $this->belongsTo(ModuleBug::class, 'bugId');
    }

    // 1st 'bugId' is the primary key in 'module_bugs' table.
    // 2nd 'bugId' is the foreign key in 'bug_actual_result' table.
    public function environment(){
        return $this->hasOne(Environment::class, 'environmentId', 'environmentId');
    }
}
