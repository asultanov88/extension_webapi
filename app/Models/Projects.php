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
        'description',
        'clientId',
        'lkProjectStatusId',
    ];

    public function modules(){
        return $this->hasMany(Modules::class, 'projectId', 'id');
    }
}
