<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Custom\SaveFileHelper;
use App\Models\BugAttachment;


class BugAttachmentsController extends Controller
{
    public function postAttachment(Request $request){
        
        if ($request->hasFile('attachments')) {
        


        }
    }
}
