<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuleBug;

class BugActualResults extends Model
{
    use HasFactory;

    protected $table='bug_actual_result';

    protected $fillable=[
        'bugId',
        'actualResults',
    ];

    // 'bugId' is the foreign key in 'bug_actual_result' table.
    public function bug(){
        return $this->belongsTo(ModuleBug::class, 'bugId');
    }
}
