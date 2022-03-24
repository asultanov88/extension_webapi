<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuleBug;

class BugDescription extends Model
{
    use HasFactory;

    protected $table='bug_description';

    protected $fillable=[
        'bugId',
        'description',
    ];

    // 'bugId' is the foreign key in 'bug_description' table.
    public function bug(){
        return $this->belongsTo(ModuleBug::class, 'bugId');
    }
}
