<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BugGlobalSearch extends Model
{
    use HasFactory;

    protected $table = 'bug_global_searches';

    protected $fillable = [
        'bugId',
        'searchKeyword',
    ];
}
