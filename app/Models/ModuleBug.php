<?php

namespace App\Models;

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
}
