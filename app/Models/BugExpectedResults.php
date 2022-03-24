<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuleBug;

class BugExpectedResults extends Model
{
    use HasFactory;

    protected $table='bug_expected_result';

    protected $fillable=[
        'bugId',
        'expectedResult'
    ];

    // 'bugId' is the foreign key in 'bug_expected_result' table.
    public function bug(){
        return $this->belongsTo(ModuleBug::class, 'bugId');
    }
}
