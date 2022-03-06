<?php

namespace App\Models;
use App\Models\ModuleBug;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modules extends Model
{
    use HasFactory;

    protected $table='modules';
    
    protected $primaryKey = 'moduleId';

    protected $fillable=[
        'moduleId',
        'name',
        'description',
        'projectId',
    ];

    public function bugs(){
        return $this->hasMany(ModuleBug::class, 'moduleId', 'moduleId');
    }
}
