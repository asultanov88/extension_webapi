<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempAttachment extends Model
{
    use HasFactory;

    protected $table = 'temp_attachments';

    protected $fillable = [
        'id',
        'uuid',
        'clientId',
        'tempPath',
        'fileName',
        'isPermanent',
    ];
}
