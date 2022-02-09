<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkProjectStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'description',
    ];

    protected $table = 'lk_project_statuses';

}


