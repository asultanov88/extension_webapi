<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Custom\SaveFileHelper;
use App\Models\BugAttachment;


class BugAttachmentsController extends Controller
{
    public function postAttachment(Request $request){

      $request->validate([
        'bugId'=>'required|integer|exists:module_bugs,bugId',
      ]);

      try {

        if(!empty($_FILES['attachment'])){

          $saveStatus = SaveFileHelper::saveAttachmentAsFile($request, 'attachments');

          if($saveStatus['saved']){

            $attachmentPath = $saveStatus['filePath'];
            $attachment = new BugAttachment();
            $attachment['bugId'] = $request['bugId'];
            $attachment['attachmentPath'] = $attachmentPath;
            $attachment->save();
  
            return response()->
            json(['result' => $attachment], 200);

          }else{

            return response()->
            json(['result' => 'file already exists.'], 500);          

          }

        }else{
          return response()->
          json(['result' => 'not attachment found.'], 500);
        }  
        
      } catch (Exception $e) {
        return response()->
        json($e, 500);
      }           
    }
}
