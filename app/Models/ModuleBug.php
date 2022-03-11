<?php

namespace App\Models;
use App\Models\BugActualResults;
use App\Models\BugDescription;
use App\Models\BugExpectedResults;
use App\Models\BugStepsToReproduce;
use App\Models\BugXpath;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleBug extends Model
{
    use HasFactory;

    protected $table='module_bugs';

    protected $primaryKey = 'bugId';

    protected $fillable=[
        'bugId',
        'moduleId',
    ];

    // 1st 'bugId' is the primary key in 'module_bugs' table.
    // 2nd 'bugId' is the foreign key in 'bug_actual_result' table.
    public function actualResult(){
        return $this->hasOne(BugActualResults::class, 'bugId', 'bugId');
    }

    public function description(){
        return $this->hasOne(BugDescription::class, 'bugId', 'bugId');
    }

    public function expectedResult(){
        return $this->hasOne(BugExpectedResults::class, 'bugId', 'bugId');
    }

    public function stepsToReproduce(){
        return $this->hasOne(BugStepsToReproduce::class, 'bugId', 'bugId');
    }

    public function xpath(){
        return $this->hasOne(BugXpath::class, 'bugId', 'bugId');
    }
}
