<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Modules;

class Projects extends Model
{
    use HasFactory;

    protected $table='projects';

    protected $fillable = [
        'id',
        'jiraId',
        'projectKey',
        'clientId',
        'lkProjectStatusId',
    ];
}
