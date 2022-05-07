<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkBugStatus extends Model
{
    use HasFactory;

    protected $primaryKey = 'lkBugStatusId';

    protected $fillable = [
        'lkBugStatusId',
        'description',
    ];

    protected $table = 'lk_bug_statuses';
}
