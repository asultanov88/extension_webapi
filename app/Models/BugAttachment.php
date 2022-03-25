<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuleBug;

class BugAttachment extends Model
{
    use HasFactory;

    protected $table = 'bug_attachments';

    protected $fillable = [
        'id',
        'bugId',
        'attachmentPath',
    ];

    // 'bugId' is the foreign key in 'bug_attachments' table.
    public function bug(){
        return $this->belongsTo(ModuleBug::class, 'bugId');
    }
}
