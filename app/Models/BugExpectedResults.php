<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BugExpectedResults extends Model
{
    use HasFactory;

    protected $table='bug_expected_result';

    protected $fillable=[
        'bugId',
        'expectedResult'
    ];
}
